<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * 📊 Get dashboard statistics (with caching)
     */
    public function stats()
    {
        try {
            $user = auth()->user();
            $isEncoder = strtolower($user->role ?? '') === 'encoder';

            // ✅ Cache key per user (encoder) or shared (admin)
            $cacheKey = $isEncoder
                ? "dashboard_stats_encoder_{$user->id}"
                : 'dashboard_stats_admin';

            $cacheDuration = 60; // 60 seconds

            $stats = Cache::remember($cacheKey, $cacheDuration, function () use ($user, $isEncoder) {
                // ===== BASIC STATS =====
                $totalStudents = Student::count();
                $archivedStudents = Student::onlyTrashed()->count();

                // ===== PENDING / APPROVED / REJECTED =====
                $pendingQuery = Payment::where('status', 'pending');
                $approvedQuery = Payment::where('status', 'approved');
                $rejectedQuery = Payment::where('status', 'rejected');

                if ($isEncoder) {
                    $pendingQuery->where('encoded_by', $user->id);
                    $approvedQuery->where('encoded_by', $user->id);
                    $rejectedQuery->where('encoded_by', $user->id);
                }

                $pendingPayments  = $pendingQuery->count();
                $approvedPayments = $approvedQuery->count();
                $rejectedPayments = $rejectedQuery->count();

                // ===== FINANCIAL TOTALS =====
                $totalCollection = (float) Payment::where('status', 'approved')->sum('amount_paid');
                $pendingAmount   = (float) Payment::where('status', 'pending')->sum('amount_paid');

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

                return [
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
                ];
            });

            return response()->json([
                'success' => true,
                'stats'   => $stats,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching stats: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ Clear cache on data changes
     * Call this from approve/reject/store/archive methods
     */
    public static function clearCache()
    {
        Cache::forget('dashboard_stats_admin');

        // Clear encoder caches
        $encoderIds = User::where('role', 'encoder')->pluck('id');
        foreach ($encoderIds as $id) {
            Cache::forget("dashboard_stats_encoder_{$id}");
        }
    }
}