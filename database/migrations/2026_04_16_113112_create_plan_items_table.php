<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('treatment_plans')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('treatment_categories')->cascadeOnDelete();
            $table->decimal('price_usd', 10, 2);
            $table->decimal('price_syp', 12, 2)->nullable();
            $table->text('target_teeth')->nullable();
            $table->enum('status', ['in_progress', 'completed'])->default('in_progress');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_items');
    }
};