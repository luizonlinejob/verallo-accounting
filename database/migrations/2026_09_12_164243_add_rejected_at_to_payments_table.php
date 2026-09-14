<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {

            // ✅ Payment lifecycle status: pending | approved | rejected
            if (!Schema::hasColumn('payments', 'status')) {
                $table->string('status')->default('pending')->after('amount_paid');
            }

            // ✅ Rejection details (who rejected, when, and why)
            if (!Schema::hasColumn('payments', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_by');
            }

            if (!Schema::hasColumn('payments', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('rejected_at');
            }

            if (!Schema::hasColumn('payments', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_by');
            }

            // ✅ Approval timestamp (for display "✅ Approved at ...")
            if (!Schema::hasColumn('payments', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            // ✅ Encoder tracking
            if (!Schema::hasColumn('payments', 'encoded_by')) {
                $table->unsignedBigInteger('encoded_by')->nullable()->after('status');
            }

            // ✅ OR number / remarks if not yet existing
            if (!Schema::hasColumn('payments', 'or_number')) {
                $table->string('or_number')->nullable()->after('amount_paid');
            }

            if (!Schema::hasColumn('payments', 'remarks')) {
                $table->text('remarks')->nullable()->after('or_number');
            }
        });

        // ✅ Add foreign key constraints (separate block to avoid SQLite issues)
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'rejected_by')) {
                try {
                    $table->foreign('rejected_by')
                        ->references('id')
                        ->on('users')
                        ->nullOnDelete();
                } catch (\Exception $e) {
                    // Foreign key already exists — skip
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Drop foreign key first (if exists)
            try {
                $table->dropForeign(['rejected_by']);
            } catch (\Exception $e) {
                // Foreign key doesn't exist — skip
            }

            // Drop the columns added by this migration
            $columns = [
                'status',
                'rejected_at',
                'rejected_by',
                'rejection_reason',
                'approved_at',
                'encoded_by',
                'or_number',
                'remarks',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};