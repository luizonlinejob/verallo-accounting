<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
{
    Schema::create('custom_fields', function (Blueprint $table) {
        $table->id();
        $table->string('field_label'); // e.g., "LRN Number" o "Discount Code"
        $table->string('field_type')->default('text'); // text, number, etc.
        $table->boolean('is_required')->default(false);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('custom_fields');
    }
};
