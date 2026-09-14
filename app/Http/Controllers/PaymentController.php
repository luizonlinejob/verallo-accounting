<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PaymentController extends Controller
{
    /**
     * Helper: Check if user is Superadmin
     */
    private function isSuperadmin($user)
    {
        if (!$user) return false;

        if (isset($user->role) && strtolower($user->role) === 'superadmin') {
            return true;
        }

        if (isset($user->roles)) {
            if (is_array($user->roles) && in_array('superadmin', array_map('strtolower', $user->roles))) {
                return true;
            }
            if (is_object($user->roles) && method_exists($user->roles, 'contains')) {
                return $user->roles->contains(fn($r) => strtolower($r->name ?? $r) === 'superadmin');
            }
        }

        return false;
    }

    /**
     * Helper: Check if user is Admin OR Superadmin
     */
    private function isAdminOrSuperadmin($user)
    {
        if (!$user) return false;

        $adminRoles = ['admin', 'superadmin'];

        if (isset($user->role) && in_array(strtolower($user->role), $adminRoles)) {
            return true;
        }

        if (isset($user->roles)) {
            if (is_array($user->roles)) {
                return count(array_intersect(array_map('strtolower', $user->roles), $adminRoles)) > 0;
            }
            if (is_object($user->roles) && method_exists($user->roles, 'contains')) {
                return $user->roles->contains(fn($r) => in_array(strtolower($r->name ?? $r), $adminRoles));
            }
        }

        return false;
    }

    /**
     * 1. Encoder: Encode Payment (Pending Status)
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_id'     => 'required|exists:students,id',
            'amount_paid'    => 'required|numeric|min:1',
            'or_number'      => 'nullable|string',
            'payment_method' => 'nullable|string',
            'remarks'        => 'nullable|string',
        ]);

        try {
            $payment = Payment::create([
                'student_id'     => $request->student_id,
                'amount_paid'    => $request->amount_paid,
                'or_number'      => $request->or_number,
                'payment_method' => $request->payment_method ?? 'cash',
                'remarks'        => $request->remarks,
                'status'         => 'pending',
                'encoded_by'     => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'status'  => 'success',
                'message' => 'Payment posted successfully! It needs to be verified and approved by the Admin.',
                'payment' => $payment->load('student')
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 2. Get all Pending Payments
     */
    public function getPendingPayments()
    {
        $pending = Payment::with(['student', 'encoder'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        return response()->json([
            'success'  => true,
            'payments' => $pending
        ], 200);
    }

    /**
     * 3. 🛡️ Admin/Superadmin: Approve Payment & Deduct Balance
     * ✅ Clears rejection notes on ALL rejected payments for the same student.
     */
    public function approve($id)
    {
        if (!$this->isAdminOrSuperadmin(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin or Superadmin approval is required.'
            ], 403);
        }

        DB::beginTransaction();

        try {
            // Smart lookup: try payment ID, fallback to student ID
            $payment = Payment::where('id', $id)->where('status', 'pending')->first();
            if (!$payment) {
                $payment = Payment::where('student_id', $id)
                    ->where('status', 'pending')
                    ->latest()
                    ->first();
            }

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending payment record found for ID: ' . $id
                ], 404);
            }

            // 1. Update the pending payment → approved
            $payment->update([
                'status'           => 'approved',
                'approved_by'      => auth()->id(),
                'approved_at'      => now(),
                'rejection_reason' => null,
                'rejected_at'      => null,
                'rejected_by'      => null,
            ]);

            // 2. 🧹 Clear rejection data on ALL OTHER rejected payments for this student
            Payment::where('student_id', $payment->student_id)
                ->where('status', 'rejected')
                ->update([
                    'rejection_reason' => null,
                    'rejected_at'      => null,
                    'rejected_by'      => null,
                ]);

            // 3. Deduct student balance
            $student = Student::find($payment->student_id);
            if ($student && isset($student->balance)) {
                $amountToDeduct = $payment->amount_paid ?? $payment->amount ?? 0;
                $student->decrement('balance', $amountToDeduct);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'status'  => 'success',
                'message' => 'Payment approved successfully!',
                'payment' => $payment->fresh(['student'])
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error approving payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 4. 🛡️ Admin/Superadmin: Reject Payment
     */
    public function reject(Request $request, $id)
    {
        if (!$this->isAdminOrSuperadmin(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin or Superadmin access is required.'
            ], 403);
        }

        $request->validate([
            'reason'           => 'nullable|string|max:500',
            'rejection_reason' => 'nullable|string|max:500'
        ]);

        try {
            $payment = Payment::where('id', $id)->where('status', 'pending')->first();
            if (!$payment) {
                $payment = Payment::where('student_id', $id)
                    ->where('status', 'pending')
                    ->latest()
                    ->first();
            }

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending payment record found to reject for ID: ' . $id
                ], 404);
            }

            $reasonInput = $request->input('reason')
                ?? $request->input('rejection_reason')
                ?? 'Rejected by Admin';

            $payment->update([
                'status'           => 'rejected',
                'rejection_reason' => $reasonInput,
                'rejected_at'      => now(),
                'rejected_by'      => auth()->id(),
                'approved_by'      => null,
                'approved_at'      => null,
            ]);

            return response()->json([
                'success' => true,
                'status'  => 'success',
                'message' => 'The payment has been rejected.',
                'payment' => $payment->load('student')
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error rejecting payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 5. Get all Rejected Payments (Legacy endpoint)
     */
    public function getRejectedPayments()
    {
        $rejected = Payment::with(['student', 'rejector'])
            ->where('status', 'rejected')
            ->whereNotNull('rejection_reason')
            ->latest('rejected_at')
            ->get();

        return response()->json([
            'success'  => true,
            'payments' => $rejected
        ], 200);
    }

    /**
     * 6. 🆕 REJECTION LOGS — Visible sa Encoder, Admin, ug Superadmin
     * ✅ Filters out cleared rejections (already approved by admin).
     */
    public function rejectionLogs()
    {
        try {
            $logs = Payment::with(['student', 'rejector'])
                ->where('status', 'rejected')
                ->whereNotNull('rejection_reason')
                ->whereNotNull('rejected_at')
                ->latest('rejected_at')
                ->get()
                ->map(function ($payment) {
                    return [
                        'id'           => $payment->id,
                        'payment_id'   => $payment->id,
                        'student_id'   => $payment->student->student_id ?? '',
                        'student_name' => $payment->student->full_name ?? 'Unknown Student',
                        'student_pk'   => $payment->student_id,
                        'amount'       => (float) ($payment->amount_paid ?? $payment->amount ?? 0),
                        'reason'       => $payment->rejection_reason,
                        'or_number'    => $payment->or_number,
                        'remarks'      => $payment->remarks,
                        'rejected_at'  => $payment->rejected_at?->toIso8601String(),
                        'rejected_by'  => $payment->rejector->name ?? 'Admin',
                    ];
                });

            return response()->json([
                'success' => true,
                'logs'    => $logs,
                'count'   => $logs->count(),
            ], 200);

        } catch (Exception $e) {
            Log::error('Failed to fetch rejection logs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching rejection logs: ' . $e->getMessage(),
                'logs'    => [],
            ], 500);
        }
    }

    /**
     * 7. Encoder: Re-encode / Resubmit Payment
     */
    public function resubmit(Request $request, $id)
    {
        $request->validate([
            'amount_paid'    => 'required|numeric|min:1',
            'or_number'      => 'nullable|string',
            'payment_method' => 'nullable|string',
            'remarks'        => 'nullable|string',
        ]);

        try {
            $payment = Payment::findOrFail($id);

            if ($payment->status !== 'rejected') {
                return response()->json([
                    'success' => false,
                    'status'  => 'error',
                    'message' => 'Only rejected payments can be re-encoded.'
                ], 400);
            }

            $payment->update([
                'amount_paid'      => $request->amount_paid,
                'or_number'        => $request->or_number ?? $payment->or_number,
                'payment_method'   => $request->payment_method ?? $payment->payment_method,
                'remarks'          => $request->remarks ?? $payment->remarks,
                'status'           => 'pending',
                'rejection_reason' => null,
                'rejected_at'      => null,
                'rejected_by'      => null,
                'encoded_by'       => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'status'  => 'success',
                'message' => 'Payment successfully re-encoded and resubmitted for approval!',
                'payment' => $payment->load('student')
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error resubmitting payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 8. Get all Approved Payments / History
     */
    public function getApprovedPayments()
    {
        $approved = Payment::with(['student', 'approver', 'encoder'])
            ->where('status', 'approved')
            ->latest('approved_at')
            ->get();

        return response()->json([
            'success'  => true,
            'payments' => $approved
        ], 200);
    }

    /**
     * 9. Get Payment History for a Specific Student
     */
    public function getStudentPaymentHistory($studentId)
    {
        $payments = Payment::with(['encoder', 'approver', 'rejector'])
            ->where('student_id', $studentId)
            ->latest()
            ->get();

        return response()->json([
            'success'  => true,
            'payments' => $payments
        ], 200);
    }
}