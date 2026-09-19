<?php

namespace App\Http\Controllers;

use App\Models\StudentFee;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeController extends Controller
{
    /**
     * 📋 Get fees for a specific student
     */
    public function getStudentFees($studentId)
    {
        try {
            $student = Student::with('fees')->findOrFail($studentId);

            return response()->json([
                'success' => true,
                'student' => [
                    'id'         => $student->id,
                    'student_id' => $student->student_id,
                    'full_name'  => $student->full_name,
                    'course'     => $student->course,
                    'year_level' => $student->year_level,
                ],
                'fees' => $student->fees->map(fn($f) => [
                    'id'       => $f->id,
                    'fee_name' => $f->fee_name,
                    'amount'   => (float) $f->amount,
                    'semester' => $f->semester,
                    'status'   => $f->status,
                ]),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching fees: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✏️ Update a fee (Superadmin only)
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user || strtolower($user->role ?? '') !== 'superadmin') {
            return response()->json([
                'success' => false,
                'message' => 'Only Superadmin can edit fees.',
            ], 403);
        }

        $request->validate([
            'fee_name' => 'required|string|max:255',
            'amount'   => 'required|numeric|min:0',
        ]);

        try {
            $fee = StudentFee::findOrFail($id);

            $fee->update([
                'fee_name' => $request->fee_name,
                'amount'   => $request->amount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fee updated successfully!',
                'fee'     => $fee,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update fee: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🗑️ Delete a fee (Superadmin only)
     */
    public function destroy($id)
    {
        $user = auth()->user();
        if (!$user || strtolower($user->role ?? '') !== 'superadmin') {
            return response()->json([
                'success' => false,
                'message' => 'Only Superadmin can delete fees.',
            ], 403);
        }

        try {
            $fee = StudentFee::findOrFail($id);
            $fee->delete();

            return response()->json([
                'success' => true,
                'message' => 'Fee deleted successfully!',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete fee: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ➕ Add new fee to a student (Superadmin only)
     */
    public function store(Request $request, $studentId)
    {
        $user = auth()->user();
        if (!$user || strtolower($user->role ?? '') !== 'superadmin') {
            return response()->json([
                'success' => false,
                'message' => 'Only Superadmin can add fees.',
            ], 403);
        }

        $request->validate([
            'fee_name' => 'required|string|max:255',
            'amount'   => 'required|numeric|min:0',
        ]);

        try {
            $student = Student::findOrFail($studentId);

            $fee = StudentFee::create([
                'student_id' => $student->id,
                'fee_name'   => $request->fee_name,
                'amount'     => $request->amount,
                'semester'   => $student->semester ?? '1st Semester',
                'status'     => 'unpaid',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fee added successfully!',
                'fee'     => $fee,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add fee: ' . $e->getMessage(),
            ], 500);
        }
    }
}