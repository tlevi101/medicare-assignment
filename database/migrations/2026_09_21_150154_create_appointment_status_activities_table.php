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
        Schema::create('appointment_status_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->on('appointments')->onDelete('cascade');
            $table->enum('previous', ['pending', 'confirmed', 'completed', 'canceled']);
            $table->enum('new', ['pending', 'confirmed', 'completed', 'canceled']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_status_activities');
    }
};
