<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'period' => 'required|in:weekly,monthly,yearly,custom',
            'from'   => 'nullable|date',
            'to'     => 'nullable|date',
        ]);

        try {
            $period = $request->period;
            $now = Carbon::now();

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

            $approvedInRange = Payment::where('status', 'approved')
                ->whereBetween('approved_at', [$from, $to])->get();
            $pendingInRange = Payment::where('status', 'pending')
                ->whereBetween('created_at', [$from, $to])->get();
            $rejectedInRange = Payment::where('status', 'rejected')
                ->whereBetween('rejected_at', [$from, $to])->get();

            $summary = [
                'total_collection' => (float) $approvedInRange->sum('amount_paid'),
                'total_pending'    => (float) $pendingInRange->sum('amount_paid'),
                'total_rejected'   => (float) $rejectedInRange->sum('amount_paid'),
                'approved_count'   => $approvedInRange->count(),
                'pending_count'    => $pendingInRange->count(),
                'rejected_count'   => $rejectedInRange->count(),
            ];

            $methodBreakdown = Payment::where('status', 'approved')
                ->whereBetween('approved_at', [$from, $to])
                ->select('payment_method', DB::raw('SUM(amount_paid) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('payment_method')
                ->get()
                ->map(fn($row) => [
                    'method' => $row->payment_method ?? 'cash',
                    'total'  => (float) $row->total,
                    'count'  => (int) $row->count,
                ]);

            $topStudents = Payment::where('status', 'approved')
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

            $encoderPerformance = Payment::where('status', 'approved')
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

            $transactions = Payment::with(['student', 'encoder', 'approver'])
                ->whereBetween('created_at', [$from, $to])
                ->latest()
                ->get()
                ->map(fn($p) => [
                    'id'               => $p->id,
                    'or_number'        => $p->or_number,
                    'student_id'       => $p->student->student_id ?? '',
                    'student_name'     => $p->student->full_name ?? '',
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
                'success'             => true,
                'period'              => $period,
                'from'                => $from->toDateString(),
                'to'                  => $to->toDateString(),
                'summary'             => $summary,
                'method_breakdown'    => $methodBreakdown,
                'top_students'        => $topStudents,
                'encoder_performance' => $encoderPerformance,
                'transactions'        => $transactions,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating report: ' . $e->getMessage(),
            ], 500);
        }
    }
}