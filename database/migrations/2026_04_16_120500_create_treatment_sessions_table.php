<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_item_id')->constrained('plan_items')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('exchange_rate_id')->nullable()->constrained('exchange_rates')->nullOnDelete();
            $table->string('name')->nullable();
            $table->decimal('rprice_usd', 10, 2)->nullable();
            $table->decimal('rprice_syp', 12, 2)->nullable();
            $table->dateTime('session_date')->nullable();
            $table->enum('status', ['in_progress', 'completed'])->default('in_progress');
            $table->text('clinical_notes')->nullable();
            $table->boolean('is_last_session')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_sessions');
    }
};
