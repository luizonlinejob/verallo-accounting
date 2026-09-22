<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Students
        Schema::table('students', function (Blueprint $table) {
            $table->index('course', 'idx_students_course');
            $table->index('year_level', 'idx_students_year');
            $table->index('created_at', 'idx_students_created');
            $table->index(['course', 'year_level'], 'idx_students_course_year');
        });

        // Payments
        Schema::table('payments', function (Blueprint $table) {
            $table->index('status', 'idx_payments_status');
            $table->index('student_id', 'idx_payments_student');
            $table->index('encoded_by', 'idx_payments_encoder');
            $table->index('approved_at', 'idx_payments_approved');
            $table->index('created_at', 'idx_payments_created');
            $table->index(['student_id', 'status'], 'idx_payments_student_status');
            $table->index(['status', 'approved_at'], 'idx_payments_status_approved');
        });

        // Student Fees
        Schema::table('student_fees', function (Blueprint $table) {
            $table->index('student_id', 'idx_fees_student');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('idx_students_course');
            $table->dropIndex('idx_students_year');
            $table->dropIndex('idx_students_created');
            $table->dropIndex('idx_students_course_year');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_status');
            $table->dropIndex('idx_payments_student');
            $table->dropIndex('idx_payments_encoder');
            $table->dropIndex('idx_payments_approved');
            $table->dropIndex('idx_payments_created');
            $table->dropIndex('idx_payments_student_status');
            $table->dropIndex('idx_payments_status_approved');
        });

        Schema::table('student_fees', function (Blueprint $table) {
            $table->dropIndex('idx_fees_student');
        });
    }
};