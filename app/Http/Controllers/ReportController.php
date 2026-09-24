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
     * 📊 Generate Report (with term filtering + caching)
     */
    public function generate(Request $request)
    {
        $request->validate([
            'period' => 'required|in:weekly,monthly,yearly,custom',
            'from'   => 'nullable|date',
            'to'     => 'nullable|date',
            'course' => 'nullable|string',
            'term'   => 'nullable|in:all,prelim,midterm,semi-final,final',
        ]);

        try {
            $period       = $request->period;
            $courseFilter = $request->course;
            $termFilter   = $request->term ?? 'all';

            $cacheKey = 'report_' . md5(json_encode([
                'period' => $period,
                'course' => $courseFilter,
                'term'   => $termFilter,
                'from'   => $request->from,
                'to'     => $request->to,
            ]));

            $reportData = Cache::remember($cacheKey, 300, function () use ($period, $courseFilter, $termFilter, $request) {
                return $this->buildReportData($period, $courseFilter, $termFilter, $request);
            });

            // ✅ Do NOT use spread operator (causes "Cannot unpack array with string keys" on PHP < 8.1)
            return response()->json(
                array_merge(['success' => true], $reportData),
                200
            );

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🔧 Build report data with term filtering
     */
    private function buildReportData($period, $courseFilter, $termFilter, $request)
    {
        $now = Carbon::now();

        // ===== DETERMINE DATE RANGE =====
        switch ($period) {
            case 'weekly':
                $from = $now->copy()->startOfWeek();
                $to   = $now->copy()->endOfWeek();
                break;
            case 'monthly':
                $from = $now->copy()->startOfMonth();
                $to   = $now->copy()->endOfMonth();
                break;
            case 'yearly':
                $from = $now->copy()->startOfYear();
                $to   = $now->copy()->endOfYear();
                break;
            case 'custom':
                $from = Carbon::parse($request->from)->startOfDay();
                $to   = Carbon::parse($request->to)->endOfDay();
                break;
            default:
                $from = $now->copy()->startOfMonth();
                $to   = $now->copy()->endOfMonth();
        }

        // ===== LOAD ALL STUDENTS =====
        $studentQuery = Student::with([
            'fees:id,student_id,fee_name,amount',
            'payments:id,student_id,amount_paid,status,created_at,approved_at,rejected_at,rejection_reason,payment_method,encoded_by,approved_by,or_number,remarks'
        ]);

        if ($courseFilter) {
            $studentQuery->where('course', $courseFilter);
        }

        $allStudents = $studentQuery->get();
        $studentIds  = $allStudents->pluck('id')->toArray();

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

        // ===== PER-COURSE BREAKDOWN =====
        $perCourseData = $this->getPerCourseBreakdownFast($allStudents, $from, $to);

        // ===== FEE CATEGORY BREAKDOWN =====
        $feeCategoryData = $this->getFeeCategoryBreakdownFast($allStudents, $from, $to);

        // ===== AGING REPORT =====
        $agingReport = $this->getAgingReportFast($allStudents);

        // ===== ENROLLMENT REALIZATION =====
        $enrollmentRealization = $this->getEnrollmentRealizationFast($allStudents, $from, $to);

        // ===== ACCOUNTING SUMMARY =====
        $accountingSummary = $this->getAccountingSummaryFast($allStudents);

        // ===== STUDENT LIST WITH TERM FILTER =====
        $studentList = $this->getStudentListWithTerm($allStudents, $termFilter);

        // ===== TERM SUMMARY + BREAKDOWN =====
        $termBundle    = $this->getTermBundle($allStudents, $termFilter);
        $termSummary   = $termBundle['term_summary'];
        $termBreakdown = $termBundle['term_breakdown'];

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
            ])
            ->values();

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

        // ===== DETAILED TRANSACTIONS =====
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
            ->map(function ($p) {
                return [
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
                ];
            });

        return [
            'period'                 => $period,
            'course_filter'          => $courseFilter,
            'term_filter'            => $termFilter,
            'from'                   => $from->toDateString(),
            'to'                     => $to->toDateString(),
            'summary'                => $summary,
            'term_summary'           => $termSummary,
            'term_breakdown'         => $termBreakdown,
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
     * ✅ Compute per-term SOA with CARRY-OVER (running balance)
     *
     * Ang wala mabayran sa usa ka term kay mo-carry over sa sunod.
     *
     * Example (total ₱11,438.34, down ₱1,500.00):
     *   Prelim:     ₱2,859.58 − ₱1,500.00 = ₱1,359.58
     *   Midterm:    ₱1,359.58 + ₱2,859.58 = ₱4,219.16
     *   Semi-Final: ₱4,219.16 + ₱2,859.58 = ₱7,078.74
     *   Final:      ₱7,078.74 + ₱2,859.60 = ₱9,938.34
     *
     * @param  \App\Models\Student  $student
     * @return array
     */
    private function computeTermSchedule($student)
    {
        // 1) Total assessment = sum of all fees
        $totalAssessment = (float) $student->fees->sum('amount');

        // 2) Split equally across 4 terms (last absorbs rounding)
        $base = floor(($totalAssessment / 4) * 100) / 100;
        $last = round($totalAssessment - $base * 3, 2);

        $termKeys   = ['prelim', 'midterm', 'semi-final', 'final'];
        $termLabels = [
            'prelim'     => 'Prelim',
            'midterm'    => 'Midterm',
            'semi-final' => 'Semi-Final',
            'final'      => 'Final',
        ];
        $termAmounts = [
            'prelim'     => $base,
            'midterm'    => $base,
            'semi-final' => $base,
            'final'      => $last,
        ];

        // 3) Total approved payments (cumulative — down payment + all approved)
        $totalPaid = (float) $student->payments
            ->where('status', 'approved')
            ->sum('amount_paid');

        // 4) Waterfall allocation with CARRY-OVER
        $remaining      = $totalPaid;
        $runningBalance = 0;
        $result         = [];

        foreach ($termKeys as $key) {
            $amount = $termAmounts[$key];

            // Apply payment to this term first
            $applied   = min($remaining, $amount);
            $remaining = max(0, $remaining - $applied);

            // Unpaid for this term (base amount − applied)
            $termUnpaid = $amount - $applied;

            // ✅ Carry over unpaid to running balance
            $runningBalance += $termUnpaid;

            $result[$key] = [
                'key'             => $key,
                'label'           => $termLabels[$key],
                'amount'          => $amount,
                'paid'            => $applied,
                'unpaid'          => $termUnpaid,
                'running_balance' => $runningBalance, // ✅ gamiton sa reports panel
            ];
        }

        return $result;
    }

    /**
     * ✅ Get student list with term filter (uses running balance)
     */
    private function getStudentListWithTerm($allStudents, $termFilter)
    {
        $rows = [];

        foreach ($allStudents as $student) {
            $totalAssessment = (float) $student->fees->sum('amount');
            $totalPaid       = (float) $student->payments->where('status', 'approved')->sum('amount_paid');
            $balance         = max(0, $totalAssessment - $totalPaid);

            $terms = $this->computeTermSchedule($student);

            if ($termFilter !== 'all') {
                $termData = $terms[$termFilter] ?? null;
                if (!$termData) continue;

                $rows[] = [
                    'student_id'        => $student->student_id,
                    'full_name'         => $student->full_name,
                    'course'            => $student->course,
                    'year_level'        => $student->year_level,
                    'term'              => $termData['label'],
                    'term_amount'       => $termData['amount'],
                    'term_paid'         => $termData['paid'],
                    'term_unpaid'       => $termData['running_balance'], // ✅ running balance
                    'total_assessment'  => $totalAssessment,
                    'total_paid'        => $totalPaid,
                    'total_receivables' => $termData['running_balance'], // ✅ running balance
                    'balance'           => $balance,
                ];
            } else {
                $rows[] = [
                    'student_id'        => $student->student_id,
                    'full_name'         => $student->full_name,
                    'course'            => $student->course,
                    'year_level'        => $student->year_level,
                    'term'              => 'All Terms',
                    'term_amount'       => $totalAssessment,
                    'term_paid'         => $totalPaid,
                    'term_unpaid'       => $balance,
                    'total_assessment'  => $totalAssessment,
                    'total_paid'        => $totalPaid,
                    'total_receivables' => $balance,
                    'balance'           => $balance,
                ];
            }
        }

        usort($rows, function ($a, $b) {
            return strcmp($a['course'], $b['course']) ?: strcmp($a['full_name'], $b['full_name']);
        });

        return [
            'students'         => $rows,
            'student_count'    => count($rows),
            'total_assessment' => array_sum(array_column($rows, 'total_assessment')),
            'total_paid'       => array_sum(array_column($rows, 'total_paid')),
            'total_balance'    => array_sum(array_column($rows, 'balance')),
            'term_filter'      => $termFilter,
        ];
    }

    /**
     * ✅ Compute term_summary (single term) + term_breakdown (all terms)
     *    Uses RUNNING BALANCE (carry-over) to match SOA.
     */
    private function getTermBundle($allStudents, $termFilter)
    {
        $totals = [
            'prelim'     => ['label' => 'Prelim',     'amount' => 0, 'collected' => 0, 'balance' => 0, 'student_count' => 0],
            'midterm'    => ['label' => 'Midterm',    'amount' => 0, 'collected' => 0, 'balance' => 0, 'student_count' => 0],
            'semi-final' => ['label' => 'Semi-Final', 'amount' => 0, 'collected' => 0, 'balance' => 0, 'student_count' => 0],
            'final'      => ['label' => 'Final',      'amount' => 0, 'collected' => 0, 'balance' => 0, 'student_count' => 0],
        ];

        foreach ($allStudents as $student) {
            $totalAssessment = (float) $student->fees->sum('amount');
            if ($totalAssessment <= 0) continue;

            $terms = $this->computeTermSchedule($student);

            foreach ($terms as $key => $termData) {
                $totals[$key]['amount']        += $termData['amount'];
                $totals[$key]['collected']     += $termData['paid'];
                // ✅ Use running_balance (carry-over), dili ang unpaid
                $totals[$key]['balance']       += $termData['running_balance'];
                $totals[$key]['student_count'] += 1;
            }
        }

        // ---- TERM BREAKDOWN (All Terms view) ----
        $termBreakdown = [];
        foreach ($totals as $key => $t) {
            $rate = $t['amount'] > 0
                ? round(($t['collected'] / $t['amount']) * 100, 1)
                : 0;

            $termBreakdown[] = [
                'term'       => $key,
                'assessment' => round($t['amount'], 2),
                'collected'  => round($t['collected'], 2),
                'balance'    => round($t['balance'], 2),
                'rate'       => $rate,
            ];
        }

        // ---- TERM SUMMARY (specific term only) ----
        $termSummary = null;
        if ($termFilter !== 'all' && isset($totals[$termFilter])) {
            $t    = $totals[$termFilter];
            $rate = $t['amount'] > 0
                ? round(($t['collected'] / $t['amount']) * 100, 1)
                : 0;

            $termSummary = [
                'term'            => $termFilter,
                'label'           => $t['label'],
                'amount'          => round($t['amount'], 2),
                'collected'       => round($t['collected'], 2),
                'balance'         => round($t['balance'], 2), // running balance total
                'collection_rate' => $rate,
                'student_count'   => $t['student_count'],
            ];
        }

        return [
            'term_summary'   => $termSummary,
            'term_breakdown' => $termBreakdown,
        ];
    }

    // ===== FAST METHODS (walay N+1) =====

    private function getPerCourseBreakdownFast($allStudents, $from, $to)
    {
        $grouped = $allStudents->groupBy('course');
        $result  = [];

        foreach ($grouped as $course => $courseStudents) {
            $totalAssessment   = 0;
            $totalCollection   = 0;
            $collectionInRange = 0;
            $pendingAmount     = 0;

            foreach ($courseStudents as $student) {
                $totalAssessment += $student->fees->sum('amount');

                $approvedPayments = $student->payments->where('status', 'approved');
                $totalCollection += $approvedPayments->sum('amount_paid');
                $collectionInRange += $approvedPayments
                    ->filter(fn($p) => $p->approved_at && $p->approved_at >= $from && $p->approved_at <= $to)
                    ->sum('amount_paid');

                $pendingAmount += $student->payments->where('status', 'pending')->sum('amount_paid');
            }

            $receivable = max(0, $totalAssessment - $totalCollection);

            $result[] = [
                'course'              => $course,
                'student_count'       => $courseStudents->count(),
                'total_assessment'    => $totalAssessment,
                'total_collection'    => $totalCollection,
                'collection_in_range' => $collectionInRange,
                'pending_amount'      => $pendingAmount,
                'receivable'          => $receivable,
                'collection_rate'     => $totalAssessment > 0
                    ? round(($totalCollection / $totalAssessment) * 100, 1)
                    : 0,
            ];
        }

        usort($result, fn($a, $b) => $b['student_count'] <=> $a['student_count']);
        return $result;
    }

    private function getFeeCategoryBreakdownFast($allStudents, $from, $to)
    {
        $categories = [
            'tuition' => ['label' => 'Tuition Fees',         'amount' => 0, 'collected' => 0, 'percent' => 0],
            'misc'    => ['label' => 'Miscellaneous Fees',   'amount' => 0, 'collected' => 0, 'percent' => 0],
            'lab'     => ['label' => 'Laboratory Fees',      'amount' => 0, 'collected' => 0, 'percent' => 0],
            'other'   => ['label' => 'Other Special Fees',   'amount' => 0, 'collected' => 0, 'percent' => 0],
        ];

        foreach ($allStudents as $student) {
            foreach ($student->fees as $fee) {
                $name   = strtolower($fee->fee_name ?? '');
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
        $totalCollected  = 0;

        foreach ($allStudents as $student) {
            $totalCollected += $student->payments
                ->where('status', 'approved')
                ->filter(fn($p) => $p->approved_at && $p->approved_at >= $from && $p->approved_at <= $to)
                ->sum('amount_paid');
        }

        foreach ($categories as &$cat) {
            $ratio            = $totalAssessment > 0 ? ($cat['amount'] / $totalAssessment) : 0;
            $cat['collected'] = round($totalCollected * $ratio, 2);
            $cat['percent']   = $totalAssessment > 0 ? round(($cat['amount'] / $totalAssessment) * 100, 1) : 0;
        }

        return [
            'categories'       => array_values($categories),
            'total_assessment' => $totalAssessment,
            'total_collected'  => $totalCollected,
        ];
    }

    private function getAgingReportFast($allStudents)
    {
        $agingBuckets = [
            'current' => ['label' => 'Current (0-30 days)', 'amount' => 0, 'count' => 0],
            'days_30' => ['label' => '30-60 days',          'amount' => 0, 'count' => 0],
            'days_60' => ['label' => '60-90 days',          'amount' => 0, 'count' => 0],
            'days_90' => ['label' => '90+ days',            'amount' => 0, 'count' => 0],
        ];

        $now = Carbon::now();

        foreach ($allStudents as $student) {
            $totalFees = $student->fees->sum('amount');
            $totalPaid = $student->payments->where('status', 'approved')->sum('amount_paid');
            $balance   = $totalFees - $totalPaid;

            if ($balance <= 0) continue;

            $enrolledAt = $student->created_at ?? $now;
            $daysSince  = $enrolledAt->diffInDays($now);

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

        return [
            'buckets'          => array_values($agingBuckets),
            'total_receivable' => array_sum(array_column($agingBuckets, 'amount')),
        ];
    }

    private function getEnrollmentRealizationFast($allStudents, $from, $to)
    {
        $grouped = $allStudents->groupBy('course');
        $result  = [];

        foreach ($grouped as $course => $courseStudents) {
            $expected = 0;
            $actual   = 0;

            foreach ($courseStudents as $student) {
                $expected += $student->fees->sum('amount');
                $actual   += $student->payments
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

    private function getAccountingSummaryFast($allStudents)
    {
        $totalAssessment     = 0;
        $totalCollection     = 0;
        $totalPending        = 0;
        $studentsWithBalance = 0;
        $fullyPaidStudents   = 0;

        foreach ($allStudents as $student) {
            $fees    = $student->fees->sum('amount');
            $paid    = $student->payments->where('status', 'approved')->sum('amount_paid');
            $pending = $student->payments->where('status', 'pending')->sum('amount_paid');

            $totalAssessment += $fees;
            $totalCollection += $paid;
            $totalPending    += $pending;

            $balance = $fees - $paid;

            if ($balance > 0) {
                $studentsWithBalance++;
            } elseif ($fees > 0 && $balance <= 0) {
                $fullyPaidStudents++;
            }
        }

        return [
            'total_assessment'      => (float) $totalAssessment,
            'total_collection'      => (float) $totalCollection,
            'total_pending'         => (float) $totalPending,
            'total_receivable'      => (float) max(0, $totalAssessment - $totalCollection),
            'collection_rate'       => $totalAssessment > 0
                ? round(($totalCollection / $totalAssessment) * 100, 1)
                : 0,
            'students_with_balance' => $studentsWithBalance,
            'fully_paid_students'   => $fullyPaidStudents,
        ];
    }
}