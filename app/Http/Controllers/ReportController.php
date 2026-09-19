<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * 📊 Generate Report
     */
    public function generate(Request $request)
    {
        $request->validate([
            'period' => 'required|in:weekly,monthly,yearly,custom',
            'from'   => 'nullable|date',
            'to'     => 'nullable|date',
            'course' => 'nullable|string',
        ]);

        try {
            $period = $request->period;
            $courseFilter = $request->course;
            $now = Carbon::now();

            // ===== DETERMINE DATE RANGE =====
            switch ($period) {
                case 'weekly':
                    $from = $now->copy()->startOfWeek();
                    $to = $now->copy()->endOfWeek();
                    break;
                case 'monthly':
                    $from = $now->copy()->startOfMonth();
                    $to = $now->copy()->endOfMonth();
                    break;
                case 'yearly':
                    $from = $now->copy()->startOfYear();
                    $to = $now->copy()->endOfYear();
                    break;
                case 'custom':
                    if (!$request->from || !$request->to) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Custom period requires both "from" and "to" dates.',
                        ], 422);
                    }
                    $from = Carbon::parse($request->from)->startOfDay();
                    $to = Carbon::parse($request->to)->endOfDay();
                    break;
                default:
                    $from = $now->copy()->startOfMonth();
                    $to = $now->copy()->endOfMonth();
            }

            // ===== FILTER STUDENT IDs BY COURSE =====
            $studentQuery = Student::query();
            if ($courseFilter) {
                $studentQuery->where('course', $courseFilter);
            }
            $studentIds = $studentQuery->pluck('id')->toArray();

            // ===== SUMMARY =====
            $approvedInRange = Payment::where('status', 'approved')
                ->whereIn('student_id', $studentIds)
                ->whereBetween('approved_at', [$from, $to])->get();
            $pendingInRange = Payment::where('status', 'pending')
                ->whereIn('student_id', $studentIds)
                ->whereBetween('created_at', [$from, $to])->get();
            $rejectedInRange = Payment::where('status', 'rejected')
                ->whereIn('student_id', $studentIds)
                ->whereBetween('rejected_at', [$from, $to])->get();

            $summary = [
                'total_collection' => (float) $approvedInRange->sum('amount_paid'),
                'total_pending'    => (float) $pendingInRange->sum('amount_paid'),
                'total_rejected'   => (float) $rejectedInRange->sum('amount_paid'),
                'approved_count'   => $approvedInRange->count(),
                'pending_count'    => $pendingInRange->count(),
                'rejected_count'   => $rejectedInRange->count(),
            ];

            // ===== PER-COURSE BREAKDOWN =====
            $perCourseData = $this->getPerCourseBreakdown($from, $to);

            // ===== FEE CATEGORY BREAKDOWN =====
            $feeCategoryData = $this->getFeeCategoryBreakdown($from, $to, $courseFilter);

            // ===== AGING REPORT =====
            $agingReport = $this->getAgingReport($courseFilter);

            // ===== ENROLLMENT REALIZATION =====
            $enrollmentRealization = $this->getEnrollmentRealization($from, $to, $courseFilter);

            // ===== ACCOUNTING SUMMARY =====
            $accountingSummary = $this->getAccountingSummary($courseFilter);

            // ===== 🆕 STUDENT LIST (flat, filtered by course) =====
            $studentList = $this->getStudentListPerCourse($courseFilter);

            // ===== PAYMENT METHOD BREAKDOWN =====
            $methodBreakdown = Payment::where('status', 'approved')
                ->whereIn('student_id', $studentIds)
                ->whereBetween('approved_at', [$from, $to])
                ->select('payment_method', DB::raw('SUM(amount_paid) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('payment_method')
                ->get()
                ->map(fn($row) => [
                    'method' => $row->payment_method ?? 'cash',
                    'total'  => (float) $row->total,
                    'count'  => (int) $row->count,
                ]);

            // ===== TOP PAYING STUDENTS =====
            $topStudents = Payment::where('status', 'approved')
                ->whereIn('student_id', $studentIds)
                ->whereBetween('approved_at', [$from, $to])
                ->with('student')
                ->get()
                ->groupBy('student_id')
                ->map(function ($payments) {
                    $student = $payments->first()->student;
                    return [
                        'student_id'    => $student->student_id ?? 'N/A',
                        'student_name'  => $student->full_name ?? 'Unknown',
                        'course'        => $student->course ?? '',
                        'year_level'    => $student->year_level ?? '',
                        'total_paid'    => (float) $payments->sum('amount_paid'),
                        'payment_count' => $payments->count(),
                    ];
                })
                ->sortByDesc('total_paid')
                ->take(10)
                ->values();

            // ===== ENCODER PERFORMANCE =====
            $encoderPerformance = Payment::where('status', 'approved')
                ->whereIn('student_id', $studentIds)
                ->whereBetween('approved_at', [$from, $to])
                ->with('encoder')
                ->get()
                ->groupBy('encoded_by')
                ->map(function ($payments) {
                    $encoder = $payments->first()->encoder;
                    return [
                        'encoder_name'  => $encoder->name ?? 'Unknown',
                        'total_encoded' => (float) $payments->sum('amount_paid'),
                        'count'         => $payments->count(),
                    ];
                })
                ->sortByDesc('total_encoded')
                ->values();

            // ===== DETAILED TRANSACTIONS =====
            $transactions = Payment::with(['student', 'encoder', 'approver'])
                ->whereIn('student_id', $studentIds)
                ->whereBetween('created_at', [$from, $to])
                ->latest()
                ->get()
                ->map(fn($p) => [
                    'id'               => $p->id,
                    'or_number'        => $p->or_number,
                    'student_id'       => $p->student->student_id ?? '',
                    'student_name'     => $p->student->full_name ?? '',
                    'course'           => $p->student->course ?? '',
                    'amount'           => (float) $p->amount_paid,
                    'payment_method'   => $p->payment_method ?? 'cash',
                    'status'           => $p->status,
                    'encoded_at'       => $p->created_at?->toIso8601String(),
                    'approved_at'      => $p->approved_at?->toIso8601String(),
                    'rejected_at'      => $p->rejected_at?->toIso8601String(),
                    'encoder'          => $p->encoder->name ?? '',
                    'approver'         => $p->approver->name ?? '',
                    'remarks'          => $p->remarks,
                    'rejection_reason' => $p->rejection_reason,
                ]);

            return response()->json([
                'success'                => true,
                'period'                 => $period,
                'course_filter'          => $courseFilter,
                'from'                   => $from->toDateString(),
                'to'                     => $to->toDateString(),
                'summary'                => $summary,
                'per_course'             => $perCourseData,
                'fee_category'           => $feeCategoryData,
                'aging_report'           => $agingReport,
                'enrollment_realization' => $enrollmentRealization,
                'accounting_summary'     => $accountingSummary,
                'student_list'           => $studentList,
                'method_breakdown'       => $methodBreakdown,
                'top_students'           => $topStudents,
                'encoder_performance'    => $encoderPerformance,
                'transactions'           => $transactions,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Per-Course Breakdown
     */
    private function getPerCourseBreakdown($from, $to)
    {
        $students = Student::all();
        $grouped = $students->groupBy('course');
        $result = [];

        foreach ($grouped as $course => $courseStudents) {
            $studentIds = $courseStudents->pluck('id')->toArray();

            $totalAssessment = (float) StudentFee::whereIn('student_id', $studentIds)->sum('amount');
            $collectionInRange = (float) Payment::whereIn('student_id', $studentIds)
                ->where('status', 'approved')
                ->whereBetween('approved_at', [$from, $to])
                ->sum('amount_paid');
            $totalCollection = (float) Payment::whereIn('student_id', $studentIds)
                ->where('status', 'approved')
                ->sum('amount_paid');
            $pendingAmount = (float) Payment::whereIn('student_id', $studentIds)
                ->where('status', 'pending')
                ->sum('amount_paid');
            $receivable = max(0, $totalAssessment - $totalCollection);

            $result[] = [
                'course'               => $course,
                'student_count'        => count($courseStudents),
                'total_assessment'     => $totalAssessment,
                'total_collection'     => $totalCollection,
                'collection_in_range'  => $collectionInRange,
                'pending_amount'       => $pendingAmount,
                'receivable'           => $receivable,
                'collection_rate'      => $totalAssessment > 0
                    ? round(($totalCollection / $totalAssessment) * 100, 1)
                    : 0,
            ];
        }

        usort($result, fn($a, $b) => $b['student_count'] <=> $a['student_count']);
        return $result;
    }

    /**
     * Fee Category Breakdown
     */
    private function getFeeCategoryBreakdown($from, $to, $courseFilter = null)
    {
        $studentQuery = Student::query();
        if ($courseFilter) {
            $studentQuery->where('course', $courseFilter);
        }
        $studentIds = $studentQuery->pluck('id')->toArray();

        $fees = StudentFee::whereIn('student_id', $studentIds)->get();

        $categories = [
            'tuition' => ['label' => 'Tuition Fees', 'amount' => 0, 'collected' => 0, 'percent' => 0],
            'misc'    => ['label' => 'Miscellaneous Fees', 'amount' => 0, 'collected' => 0, 'percent' => 0],
            'lab'     => ['label' => 'Laboratory Fees', 'amount' => 0, 'collected' => 0, 'percent' => 0],
            'other'   => ['label' => 'Other Special Fees', 'amount' => 0, 'collected' => 0, 'percent' => 0],
        ];

        foreach ($fees as $fee) {
            $name = strtolower($fee->fee_name ?? '');
            $amount = (float) $fee->amount;

            if (str_contains($name, 'tuition')) {
                $categories['tuition']['amount'] += $amount;
            } elseif (str_contains($name, 'lab')) {
                $categories['lab']['amount'] += $amount;
            } elseif (str_contains($name, 'misc') || str_contains($name, 'nstp') || str_contains($name, 'insurance') || str_contains($name, 'tts') || str_contains($name, 'entrance')) {
                $categories['misc']['amount'] += $amount;
            } else {
                $categories['other']['amount'] += $amount;
            }
        }

        $totalAssessment = array_sum(array_column($categories, 'amount'));
        $totalCollected = (float) Payment::whereIn('student_id', $studentIds)
            ->where('status', 'approved')
            ->whereBetween('approved_at', [$from, $to])
            ->sum('amount_paid');

        foreach ($categories as &$cat) {
            $ratio = $totalAssessment > 0 ? ($cat['amount'] / $totalAssessment) : 0;
            $cat['collected'] = round($totalCollected * $ratio, 2);
            $cat['percent'] = $totalAssessment > 0 ? round(($cat['amount'] / $totalAssessment) * 100, 1) : 0;
        }

        return [
            'categories'       => array_values($categories),
            'total_assessment' => $totalAssessment,
            'total_collected'  => $totalCollected,
        ];
    }

    /**
     * Accounts Receivable Aging Report
     */
    private function getAgingReport($courseFilter = null)
    {
        $studentQuery = Student::query();
        if ($courseFilter) {
            $studentQuery->where('course', $courseFilter);
        }
        $students = $studentQuery->get();

        $agingBuckets = [
            'current' => ['label' => 'Current (0-30 days)', 'amount' => 0, 'count' => 0],
            'days_30' => ['label' => '30-60 days', 'amount' => 0, 'count' => 0],
            'days_60' => ['label' => '60-90 days', 'amount' => 0, 'count' => 0],
            'days_90' => ['label' => '90+ days', 'amount' => 0, 'count' => 0],
        ];

        $now = Carbon::now();

        foreach ($students as $student) {
            $totalFees = (float) StudentFee::where('student_id', $student->id)->sum('amount');
            $totalPaid = (float) Payment::where('student_id', $student->id)
                ->where('status', 'approved')
                ->sum('amount_paid');
            $balance = $totalFees - $totalPaid;

            if ($balance <= 0) continue;

            $enrolledAt = $student->created_at ?? $now;
            $daysSince = $enrolledAt->diffInDays($now);

            if ($daysSince <= 30) {
                $agingBuckets['current']['amount'] += $balance;
                $agingBuckets['current']['count']++;
            } elseif ($daysSince <= 60) {
                $agingBuckets['days_30']['amount'] += $balance;
                $agingBuckets['days_30']['count']++;
            } elseif ($daysSince <= 90) {
                $agingBuckets['days_60']['amount'] += $balance;
                $agingBuckets['days_60']['count']++;
            } else {
                $agingBuckets['days_90']['amount'] += $balance;
                $agingBuckets['days_90']['count']++;
            }
        }

        $totalReceivable = array_sum(array_column($agingBuckets, 'amount'));

        return [
            'buckets'          => array_values($agingBuckets),
            'total_receivable' => $totalReceivable,
        ];
    }

    /**
     * Enrollment and Fee Realization Report
     */
    private function getEnrollmentRealization($from, $to, $courseFilter = null)
    {
        $studentQuery = Student::query();
        if ($courseFilter) {
            $studentQuery->where('course', $courseFilter);
        }
        $students = $studentQuery->get();
        $grouped = $students->groupBy('course');

        $result = [];

        foreach ($grouped as $course => $courseStudents) {
            $studentIds = $courseStudents->pluck('id')->toArray();

            $expected = (float) StudentFee::whereIn('student_id', $studentIds)->sum('amount');
            $actual = (float) Payment::whereIn('student_id', $studentIds)
                ->where('status', 'approved')
                ->whereBetween('approved_at', [$from, $to])
                ->sum('amount_paid');

            $realizationRate = $expected > 0 ? round(($actual / $expected) * 100, 1) : 0;

            $result[] = [
                'course'           => $course,
                'student_count'    => count($courseStudents),
                'expected'         => $expected,
                'actual'           => $actual,
                'variance'         => $expected - $actual,
                'realization_rate' => $realizationRate,
            ];
        }

        usort($result, fn($a, $b) => $b['expected'] <=> $a['expected']);
        return $result;
    }

    /**
     * 🆕 Student List (flat — para sa Chairman)
     */
    private function getStudentListPerCourse($courseFilter = null)
    {
        $studentQuery = Student::query();
        if ($courseFilter) {
            $studentQuery->where('course', $courseFilter);
        }
        $students = $studentQuery->orderBy('course')->orderBy('full_name')->get();

        $rows = [];

        foreach ($students as $student) {
            $totalAssessment = (float) StudentFee::where('student_id', $student->id)->sum('amount');

            $totalPaid = (float) Payment::where('student_id', $student->id)
                ->where('status', 'approved')
                ->sum('amount_paid');

            $balance = max(0, $totalAssessment - $totalPaid);

            $rows[] = [
                'student_id'        => $student->student_id,
                'full_name'         => $student->full_name,
                'course'            => $student->course,
                'year_level'        => $student->year_level,
                'total_assessment'  => $totalAssessment,
                'total_receivables' => $balance,
                'total_paid'        => $totalPaid,
                'balance'           => $balance,
            ];
        }

        return [
            'students'         => $rows,
            'student_count'    => count($rows),
            'total_assessment' => array_sum(array_column($rows, 'total_assessment')),
            'total_paid'       => array_sum(array_column($rows, 'total_paid')),
            'total_balance'    => array_sum(array_column($rows, 'balance')),
        ];
    }

    /**
     * Accounting Summary
     */
    private function getAccountingSummary($courseFilter = null)
    {
        $studentQuery = Student::query();
        if ($courseFilter) {
            $studentQuery->where('course', $courseFilter);
        }
        $students = $studentQuery->get();
        $studentIds = $students->pluck('id')->toArray();

        $totalAssessment = (float) StudentFee::whereIn('student_id', $studentIds)->sum('amount');
        $totalCollection = (float) Payment::whereIn('student_id', $studentIds)
            ->where('status', 'approved')
            ->sum('amount_paid');
        $totalPending = (float) Payment::whereIn('student_id', $studentIds)
            ->where('status', 'pending')
            ->sum('amount_paid');
        $totalReceivable = max(0, $totalAssessment - $totalCollection);

        $studentsWithBalance = 0;
        $fullyPaidStudents = 0;

        foreach ($students as $student) {
            $fees = StudentFee::where('student_id', $student->id)->sum('amount');
            $paid = Payment::where('student_id', $student->id)
                ->where('status', 'approved')
                ->sum('amount_paid');
            $balance = $fees - $paid;

            if ($balance > 0) $studentsWithBalance++;
            elseif ($fees > 0 && $balance <= 0) $fullyPaidStudents++;
        }

        return [
            'total_assessment'      => $totalAssessment,
            'total_collection'      => $totalCollection,
            'total_pending'         => $totalPending,
            'total_receivable'      => $totalReceivable,
            'collection_rate'       => $totalAssessment > 0
                ? round(($totalCollection / $totalAssessment) * 100, 1)
                : 0,
            'students_with_balance' => $studentsWithBalance,
            'fully_paid_students'   => $fullyPaidStudents,
        ];
    }
}