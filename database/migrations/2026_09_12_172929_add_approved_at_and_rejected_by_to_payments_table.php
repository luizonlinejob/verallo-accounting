<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {

            if (!Schema::hasColumn('payments', 'status')) {
                $table->string('status')->default('pending')->after('amount_paid');
            }

            if (!Schema::hasColumn('payments', 'encoded_by')) {
                $table->unsignedBigInteger('encoded_by')->nullable()->after('status');
            }

            if (!Schema::hasColumn('payments', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable();
            }

            if (!Schema::hasColumn('payments', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            if (!Schema::hasColumn('payments', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('approved_at');
            }

            if (!Schema::hasColumn('payments', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_by');
            }

            if (!Schema::hasColumn('payments', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
            }

            if (!Schema::hasColumn('payments', 'or_number')) {
                $table->string('or_number')->nullable();
            }

            if (!Schema::hasColumn('payments', 'payment_method')) {
                $table->string('payment_method')->nullable();
            }

            if (!Schema::hasColumn('payments', 'remarks')) {
                $table->text('remarks')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            foreach ([
                'status', 'encoded_by', 'approved_by', 'approved_at',
                'rejected_by', 'rejection_reason', 'rejected_at',
                'or_number', 'payment_method', 'remarks'
            ] as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};