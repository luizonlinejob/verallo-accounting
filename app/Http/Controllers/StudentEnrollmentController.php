<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentFee;
use App\Models\CustomField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class StudentEnrollmentController extends Controller
{
    /**
     * ✅ Clear student caches
     */
    private function clearCaches()
    {
        Cache::forget('students_json_all');
        Cache::forget('students_json_archived');
        Cache::flush();
        DashboardController::clearCache();
    }

    public function index()
    {
        $students = Student::with(['fees', 'payments'])->latest()->get();

        return Inertia::render('StudentEnrollment', [
            'students' => $students
        ]);
    }

    /**
     * 🔧 Transform a student (no additional queries if eager-loaded)
     */
    private function transformStudent($student)
    {
        $totalFees = $student->fees ? $student->fees->sum('amount') : 0;

        $approvedPayments = $student->payments
            ? $student->payments->where('status', 'approved')
            : collect();
        $totalPaid = $approvedPayments->sum('amount_paid');

        $pendingPayment = $student->payments
            ? $student->payments->where('status', 'pending')->sortByDesc('created_at')->first()
            : null;

        $rejectedPayment = $student->payments
            ? $student->payments
                ->where('status', 'rejected')
                ->whereNotNull('rejection_reason')
                ->sortByDesc('rejected_at')
                ->first()
            : null;

        $lastApprovedPayment = $approvedPayments->sortByDesc('approved_at')->first();

        $student->total_fees    = (float) $totalFees;
        $student->total_paid    = (float) $totalPaid;
        $student->total_balance = (float) max(0, $totalFees - $totalPaid);

        $student->has_pending_payment = $pendingPayment ? true : false;
        $student->pending_amount      = $pendingPayment ? (float) $pendingPayment->amount_paid : 0;
        $student->pending_date        = $pendingPayment && $pendingPayment->created_at
            ? $pendingPayment->created_at->format('M d, Y - g:i A')
            : null;

        $student->last_paid_date = $lastApprovedPayment && $lastApprovedPayment->created_at
            ? $lastApprovedPayment->created_at->format('M d, Y - g:i A')
            : null;
        $student->approved_at = $lastApprovedPayment && $lastApprovedPayment->approved_at
            ? $lastApprovedPayment->approved_at->toIso8601String()
            : null;

        $student->is_rejected       = $rejectedPayment ? true : false;
        $student->rejection_reason  = $rejectedPayment->rejection_reason ?? null;
        $student->rejected_at       = $rejectedPayment && $rejectedPayment->rejected_at
            ? $rejectedPayment->rejected_at->toIso8601String()
            : null;
        $student->payment_status    = $pendingPayment ? 'pending' : ($rejectedPayment ? 'rejected' : null);

        return $student;
    }

    /**
     * 📋 JSON: Active Students list (with caching + N+1 fix)
     */
    public function getStudentsJson()
    {
        try {
            // ✅ Cache 60 seconds
            $students = Cache::remember('students_json_all', 60, function () {
                return Student::with([
                    'fees:id,student_id,fee_name,amount',
                    'payments:id,student_id,amount_paid,status,created_at,approved_at,rejected_at,rejection_reason'
                ])
                ->select('id', 'student_id', 'full_name', 'email', 'course', 'year_level', 'semester', 'created_at')
                ->latest()
                ->get()
                ->map(fn($s) => $this->transformStudent($s));
            });

            return response()->json($students, 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error fetching students: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===== CUSTOM FIELDS =====
    public function getCustomFields()
    {
        return Cache::remember('custom_fields_all', 300, function () {
            return CustomField::all();
        });
    }

    public function addCustomField(Request $request)
    {
        $request->validate([
            'field_label' => 'required|string|max:255',
        ]);

        $field = CustomField::create([
            'field_label' => $request->field_label,
            'field_type'  => 'text',
        ]);

        Cache::forget('custom_fields_all');

        return response()->json([
            'message' => 'Custom field successfully added!',
            'field'   => $field
        ]);
    }

    public function removeCustomField($id)
    {
        try {
            $field = CustomField::findOrFail($id);
            $field->delete();

            Cache::forget('custom_fields_all');

            return response()->json([
                'success' => true,
                'message' => 'Custom field deleted successfully!',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete custom field: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 📝 Enroll new student (batch insert + cache clear)
     */
    public function enroll(Request $request)
    {
        $request->validate([
            'student_id'      => 'required|unique:students,student_id',
            'full_name'       => 'required|string|max:255',
            'email'           => 'required|email|unique:students,email',
            'course'          => 'required|string',
            'year_level'      => 'required|string',
            'fees'            => 'required|array|min:1',
            'fees.*.fee_name' => 'required|string',
            'fees.*.amount'   => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $student = Student::create([
                'student_id'    => $request->student_id,
                'full_name'     => $request->full_name,
                'email'         => $request->email,
                'course'        => $request->course,
                'year_level'    => $request->year_level,
                'semester'      => $request->semester ?? '1st Semester',
                'custom_values' => $request->custom_values ?? [],
            ]);

            // ✅ Batch insert fees (1 query instead of N)
            $now = now();
            $feesData = collect($request->fees)->map(fn($fee) => [
                'student_id' => $student->id,
                'fee_name'   => $fee['fee_name'],
                'amount'     => $fee['amount'],
                'semester'   => $request->semester ?? '1st Semester',
                'status'     => 'unpaid',
                'created_at' => $now,
                'updated_at' => $now,
            ])->toArray();

            StudentFee::insert($feesData);

            DB::commit();

            // ✅ Clear caches
            $this->clearCaches();

            return response()->json([
                'status'  => 'success',
                'message' => 'Enrollment Successful: ' . $student->full_name . ' (' . $student->year_level . ')!'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Nawala sa pag-save sa Database: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✏️ Update student
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'student_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('students', 'student_id')->ignore($id),
            ],
            'full_name'  => 'required|string|max:255',
            'course'     => 'required|string|max:100',
            'year_level' => 'required|string|max:50',
        ]);

        try {
            $student = Student::findOrFail($id);

            $student->update([
                'student_id' => $request->student_id,
                'full_name'  => $request->full_name,
                'course'     => $request->course,
                'year_level' => $request->year_level,
            ]);

            $this->clearCaches();

            return response()->json([
                'success' => true,
                'status'  => 'success',
                'message' => 'Student details updated successfully!',
                'student' => $student
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status'  => 'error',
                'message' => 'Failed to update student: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📦 Archive student
     */
    public function archive($id)
    {
        try {
            $student = Student::where('id', $id)
                ->orWhere('student_id', $id)
                ->firstOrFail();

            $student->delete();

            $this->clearCaches();

            return response()->json([
                'success' => true,
                'status'  => 'success',
                'message' => 'Student archived successfully!'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status'  => 'error',
                'message' => 'Failed to archive student: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📁 GET ARCHIVED STUDENTS (with caching)
     */
    public function getArchivedStudents()
    {
        try {
            $archivedStudents = Cache::remember('students_json_archived', 60, function () {
                return Student::onlyTrashed()
                    ->with([
                        'fees:id,student_id,fee_name,amount',
                        'payments:id,student_id,amount_paid,status,created_at,approved_at,rejected_at,rejection_reason'
                    ])
                    ->select('id', 'student_id', 'full_name', 'email', 'course', 'year_level', 'semester', 'deleted_at')
                    ->latest('deleted_at')
                    ->get()
                    ->map(fn($s) => $this->transformStudent($s));
            });

            return response()->json([
                'success'  => true,
                'students' => $archivedStudents,
                'count'    => $archivedStudents->count(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success'  => false,
                'status'   => 'error',
                'message'  => 'Error fetching archived students: ' . $e->getMessage(),
                'students' => [],
                'count'    => 0,
            ], 500);
        }
    }

    /**
     * ♻️ Restore archived student
     */
    public function restore($id)
    {
        try {
            $student = Student::onlyTrashed()
                ->where('id', $id)
                ->orWhere('student_id', $id)
                ->firstOrFail();

            $student->restore();

            $this->clearCaches();

            return response()->json([
                'success' => true,
                'status'  => 'success',
                'message' => 'Student restored successfully!'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status'  => 'error',
                'message' => 'Failed to restore student: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🗑️ Permanently delete student
     */
    public function forceDelete($id)
    {
        try {
            $student = Student::onlyTrashed()
                ->where('id', $id)
                ->orWhere('student_id', $id)
                ->firstOrFail();

            $studentName = $student->full_name;

            DB::beginTransaction();

            if (method_exists($student, 'fees')) {
                $student->fees()->delete();
            }
            if (method_exists($student, 'payments')) {
                $student->payments()->delete();
            }

            $student->forceDelete();

            DB::commit();

            $this->clearCaches();

            return response()->json([
                'success' => true,
                'status'  => 'success',
                'message' => "Student \"{$studentName}\" permanently deleted."
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'status'  => 'error',
                'message' => 'Failed to delete student: ' . $e->getMessage()
            ], 500);
        }
    }
}