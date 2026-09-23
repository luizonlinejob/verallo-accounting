<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * 📊 Generate Report (with caching)
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

            // ✅ Cache key based on request params
            $cacheKey = 'report_' . md5(json_encode([
                'period' => $period,
                'course' => $courseFilter,
                'from'   => $request->from,
                'to'     => $request->to,
            ]));

            // ✅ Cache for 5 minutes (300 seconds)
            $reportData = Cache::remember($cacheKey, 300, function () use ($period, $courseFilter, $request) {
                return $this->buildReportData($period, $courseFilter, $request);
            });

            return response()->json([
                'success' => true,
                ...$reportData,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🔧 Build report data (extracted for caching)
     */
    private function buildReportData($period, $courseFilter, $request)
    {
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
                $from = Carbon::parse($request->from)->startOfDay();
                $to = Carbon::parse($request->to)->endOfDay();
                break;
            default:
                $from = $now->copy()->startOfMonth();
                $to = $now->copy()->endOfMonth();
        }

        // ✅ Load ALL students with fees + payments ONCE (avoid N+1)
        $studentQuery = Student::with([
            'fees:id,student_id,fee_name,amount',
            'payments:id,student_id,amount_paid,status,created_at,approved_at,rejected_at,rejection_reason,payment_method,encoded_by,approved_by,or_number,remarks'
        ]);

        if ($courseFilter) {
            $studentQuery->where('course', $courseFilter);
        }

        $allStudents = $studentQuery->get();
        $studentIds = $allStudents->pluck('id')->toArray();

        // ===== FILTER PAYMENTS IN RANGE =====
        $approvedInRange = Payment::where('status', 'approved')
            ->whereIn('student_id', $studentIds)
            ->whereBetween('approved_at', [$from, $to])
            ->get();
        $pendingInRange = Payment::where('status', 'pending')
            ->whereIn('student_id', $studentIds)
            ->whereBetween('created_at', [$from, $to])
            ->get();
        $rejectedInRange = Payment::where('status', 'rejected')
            ->whereIn('student_id', $studentIds)
            ->whereBetween('rejected_at', [$from, $to])
            ->get();

        $summary = [
            'total_collection' => (float) $approvedInRange->sum('amount_paid'),
            'total_pending'    => (float) $pendingInRange->sum('amount_paid'),
            'total_rejected'   => (float) $rejectedInRange->sum('amount_paid'),
            'approved_count'   => $approvedInRange->count(),
            'pending_count'    => $pendingInRange->count(),
            'rejected_count'   => $rejectedInRange->count(),
        ];

        // ===== PER-COURSE BREAKDOWN (no N+1) =====
        $perCourseData = $this->getPerCourseBreakdownFast($allStudents, $from, $to);

        // ===== FEE CATEGORY BREAKDOWN =====
        $feeCategoryData = $this->getFeeCategoryBreakdownFast($allStudents, $from, $to);

        // ===== AGING REPORT =====
        $agingReport = $this->getAgingReportFast($allStudents);

        // ===== ENROLLMENT REALIZATION =====
        $enrollmentRealization = $this->getEnrollmentRealizationFast($allStudents, $from, $to);

        // ===== ACCOUNTING SUMMARY =====
        $accountingSummary = $this->getAccountingSummaryFast($allStudents);

        // ===== STUDENT LIST =====
        $studentList = $this->getStudentListFast($allStudents);

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
            ->with('student:id,student_id,full_name,course,year_level')
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
            ->with('encoder:id,name')
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

        // ===== DETAILED TRANSACTIONS (limited to 500) =====
        $transactions = Payment::with([
                'student:id,student_id,full_name,course',
                'encoder:id,name',
                'approver:id,name'
            ])
            ->whereIn('student_id', $studentIds)
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->limit(500)
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

        return [
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
        ];
    }

    /**
     * ⚡ FAST: Per-Course Breakdown (compute from loaded collections)
     */
    private function getPerCourseBreakdownFast($allStudents, $from, $to)
    {
        $grouped = $allStudents->groupBy('course');
        $result = [];

        foreach ($grouped as $course => $courseStudents) {
            $totalAssessment = 0;
            $totalCollection = 0;
            $collectionInRange = 0;
            $pendingAmount = 0;

            foreach ($courseStudents as $student) {
                // Assessment (from loaded fees)
                $totalAssessment += $student->fees->sum('amount');

                // Approved payments (from loaded payments)
                $approvedPayments = $student->payments->where('status', 'approved');
                $totalCollection += $approvedPayments->sum('amount_paid');

                // Collection in range
                $collectionInRange += $approvedPayments
                    ->filter(fn($p) => $p->approved_at && $p->approved_at >= $from && $p->approved_at <= $to)
                    ->sum('amount_paid');

                // Pending
                $pendingAmount += $student->payments
                    ->where('status', 'pending')
                    ->sum('amount_paid');
            }

            $receivable = max(0, $totalAssessment - $totalCollection);

            $result[] = [
                'course'               => $course,
                'student_count'        => $courseStudents->count(),
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
     * ⚡ FAST: Fee Category Breakdown
     */
    private function getFeeCategoryBreakdownFast($allStudents, $from, $to)
    {
        $categories = [
            'tuition' => ['label' => 'Tuition Fees', 'amount' => 0, 'collected' => 0, 'percent' => 0],
            'misc'    => ['label' => 'Miscellaneous Fees', 'amount' => 0, 'collected' => 0, 'percent' => 0],
            'lab'     => ['label' => 'Laboratory Fees', 'amount' => 0, 'collected' => 0, 'percent' => 0],
            'other'   => ['label' => 'Other Special Fees', 'amount' => 0, 'collected' => 0, 'percent' => 0],
        ];

        foreach ($allStudents as $student) {
            foreach ($student->fees as $fee) {
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
        }

        $totalAssessment = array_sum(array_column($categories, 'amount'));

        // Total collected in range
        $totalCollected = 0;
        foreach ($allStudents as $student) {
            $totalCollected += $student->payments
                ->where('status', 'approved')
                ->filter(fn($p) => $p->approved_at && $p->approved_at >= $from && $p->approved_at <= $to)
                ->sum('amount_paid');
        }

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
     * ⚡ FAST: Aging Report
     */
    private function getAgingReportFast($allStudents)
    {
        $agingBuckets = [
            'current' => ['label' => 'Current (0-30 days)', 'amount' => 0, 'count' => 0],
            'days_30' => ['label' => '30-60 days', 'amount' => 0, 'count' => 0],
            'days_60' => ['label' => '60-90 days', 'amount' => 0, 'count' => 0],
            'days_90' => ['label' => '90+ days', 'amount' => 0, 'count' => 0],
        ];

        $now = Carbon::now();

        foreach ($allStudents as $student) {
            $totalFees = $student->fees->sum('amount');
            $totalPaid = $student->payments->where('status', 'approved')->sum('amount_paid');
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
     * ⚡ FAST: Enrollment Realization
     */
    private function getEnrollmentRealizationFast($allStudents, $from, $to)
    {
        $grouped = $allStudents->groupBy('course');
        $result = [];

        foreach ($grouped as $course => $courseStudents) {
            $expected = 0;
            $actual = 0;

            foreach ($courseStudents as $student) {
                $expected += $student->fees->sum('amount');
                $actual += $student->payments
                    ->where('status', 'approved')
                    ->filter(fn($p) => $p->approved_at && $p->approved_at >= $from && $p->approved_at <= $to)
                    ->sum('amount_paid');
            }

            $realizationRate = $expected > 0 ? round(($actual / $expected) * 100, 1) : 0;

            $result[] = [
                'course'           => $course,
                'student_count'    => $courseStudents->count(),
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
     * ⚡ FAST: Student List
     */
    private function getStudentListFast($allStudents)
    {
        $rows = [];

        foreach ($allStudents as $student) {
            $totalAssessment = $student->fees->sum('amount');
            $totalPaid = $student->payments->where('status', 'approved')->sum('amount_paid');
            $balance = max(0, $totalAssessment - $totalPaid);

            $rows[] = [
                'student_id'        => $student->student_id,
                'full_name'         => $student->full_name,
                'course'            => $student->course,
                'year_level'        => $student->year_level,
                'total_assessment'  => (float) $totalAssessment,
                'total_receivables' => (float) $balance,
                'total_paid'        => (float) $totalPaid,
                'balance'           => (float) $balance,
            ];
        }

        // Sort by course then name
        usort($rows, function ($a, $b) {
            return strcmp($a['course'], $b['course']) ?: strcmp($a['full_name'], $b['full_name']);
        });

        return [
            'students'         => $rows,
            'student_count'    => count($rows),
            'total_assessment' => array_sum(array_column($rows, 'total_assessment')),
            'total_paid'       => array_sum(array_column($rows, 'total_paid')),
            'total_balance'    => array_sum(array_column($rows, 'balance')),
        ];
    }

    /**
     * ⚡ FAST: Accounting Summary
     */
    private function getAccountingSummaryFast($allStudents)
    {
        $totalAssessment = 0;
        $totalCollection = 0;
        $totalPending = 0;
        $studentsWithBalance = 0;
        $fullyPaidStudents = 0;

        foreach ($allStudents as $student) {
            $fees = $student->fees->sum('amount');
            $paid = $student->payments->where('status', 'approved')->sum('amount_paid');
            $pending = $student->payments->where('status', 'pending')->sum('amount_paid');

            $totalAssessment += $fees;
            $totalCollection += $paid;
            $totalPending += $pending;

            $balance = $fees - $paid;

            if ($balance > 0) $studentsWithBalance++;
            elseif ($fees > 0 && $balance <= 0) $fullyPaidStudents++;
        }

        $totalReceivable = max(0, $totalAssessment - $totalCollection);

        return [
            'total_assessment'      => (float) $totalAssessment,
            'total_collection'      => (float) $totalCollection,
            'total_pending'         => (float) $totalPending,
            'total_receivable'      => (float) $totalReceivable,
            'collection_rate'       => $totalAssessment > 0
                ? round(($totalCollection / $totalAssessment) * 100, 1)
                : 0,
            'students_with_balance' => $studentsWithBalance,
            'fully_paid_students'   => $fullyPaidStudents,
        ];
    }
}