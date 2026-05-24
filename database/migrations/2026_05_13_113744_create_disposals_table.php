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
        Schema::create('disposals', function (Blueprint $table) {
            $table->id();
             $table->string('disposal_number')->unique();
        $table->date('disposal_date');
        $table->string('reason');
        $table->string('status')->default('completed');
        $table->integer('total_quantity')->default(0);
        $table->text('notes')->nullable();
        $table->string('created_by')->nullable();
        $table->string('approved_by')->nullable();
        $table->string('executed_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disposals');
    }
};
