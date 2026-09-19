<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        try {
            $user = auth()->user();
            $isEncoder = strtolower($user->role ?? '') === 'encoder';

            // ===== BASIC STATS =====
            $totalStudents = Student::count();
            $archivedStudents = Student::onlyTrashed()->count();

            // ===== PENDING / APPROVED / REJECTED =====
            $pendingQuery = Payment::where('status', 'pending');
            $approvedQuery = Payment::where('status', 'approved');
            $rejectedQuery = Payment::where('status', 'rejected');

            // ✅ Encoder: filter by their own encodings
            if ($isEncoder) {
                $pendingQuery->where('encoded_by', $user->id);
                $approvedQuery->where('encoded_by', $user->id);
                $rejectedQuery->where('encoded_by', $user->id);
            }

            $pendingPayments = $pendingQuery->count();
            $approvedPayments = $approvedQuery->count();
            $rejectedPayments = $rejectedQuery->count();

            // ===== FINANCIAL TOTALS =====
            $totalCollection = (float) Payment::where('status', 'approved')->sum('amount_paid');
            $pendingAmount = (float) Payment::where('status', 'pending')->sum('amount_paid');

            $totalFees = (float) DB::table('student_fees')->sum('amount');
            $totalOutstanding = max(0, $totalFees - $totalCollection);

            // ===== USERS =====
            $totalUsers = User::count();

            // ===== ENCODER-SPECIFIC =====
            $myEncodedToday = 0;
            $myPending = 0;
            $myRejected = 0;

            if ($isEncoder) {
                $myEncodedToday = Payment::where('encoded_by', $user->id)
                    ->whereDate('created_at', today())
                    ->count();

                $myPending = Payment::where('encoded_by', $user->id)
                    ->where('status', 'pending')
                    ->count();

                $myRejected = Payment::where('encoded_by', $user->id)
                    ->where('status', 'rejected')
                    ->count();
            }

            return response()->json([
                'success' => true,
                'stats' => [
                    'total_students'    => $totalStudents,
                    'archived_students' => $archivedStudents,
                    'pending_payments'  => $pendingPayments,
                    'approved_payments' => $approvedPayments,
                    'rejected_payments' => $rejectedPayments,
                    'total_collection'  => $isEncoder ? 0 : $totalCollection,
                    'pending_amount'    => $isEncoder ? 0 : $pendingAmount,
                    'total_outstanding' => $isEncoder ? 0 : $totalOutstanding,
                    'total_users'       => $totalUsers,
                    'my_encoded_today'  => $myEncodedToday,
                    'my_pending'        => $myPending,
                    'my_rejected'       => $myRejected,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching stats: ' . $e->getMessage(),
            ], 500);
        }
    }
}