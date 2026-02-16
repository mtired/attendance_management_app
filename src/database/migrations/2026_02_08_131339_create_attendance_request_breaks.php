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
        Schema::create('attendance_request_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('attendance_change_requests')->restrictOnDelete();
            $table->foreignId('target_break_id')->nullable()->constrained('breaks')->restrictOnDelete();
            $table->tinyInteger('status')->comment('0:追加, 1:更新');
            $table->dateTime('proposed_break_start_at');
            $table->dateTime('proposed_break_end_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_request_breaks');
    }
};
