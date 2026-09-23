<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class ClearCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries = 3;

    public function handle()
    {
        // ✅ Clear student caches
        Cache::forget('students_json_all');
        Cache::forget('students_json_archived');
        Cache::forget('custom_fields_all');
        Cache::forget('dashboard_stats_admin');

        // ✅ Clear encoder caches
        $encoderIds = User::where('role', 'encoder')->pluck('id');
        foreach ($encoderIds as $id) {
            Cache::forget("dashboard_stats_encoder_{$id}");
        }

        // ✅ Clear report caches (bulk delete, 1 query)
        try {
            DB::table('cache')
                ->where('key', 'LIKE', 'report_%')
                ->delete();
        } catch (\Exception $e) {
            \Log::warning('Report cache clearing failed: ' . $e->getMessage());
        }
    }
}