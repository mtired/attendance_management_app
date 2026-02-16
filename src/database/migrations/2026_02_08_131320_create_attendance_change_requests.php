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
        Schema::create('attendance_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('attendance_id')->constrained()->restrictOnDelete();
            $table->dateTime('proposed_clock_in_at');
            $table->dateTime('proposed_clock_out_at');
            $table->string('remarks', 255)->nullable();
            $table->tinyInteger('status')->default(0)->comment('0:承認待ち, 1:承認済み');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_change_requests');
    }
};
