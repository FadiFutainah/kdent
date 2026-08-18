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
        Schema::create('tooth_treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->unsignedTinyInteger('tooth_number'); // 11-48 بالغين | 51-85 أطفال (FDI)
            $table->enum('status', ['Initial', 'Pending', 'Done'])->default('Initial');
            $table->string('treatment_type');
            $table->json('selected_surfaces')->nullable(); // ["M","O",...]
            $table->text('notes')->nullable();
            $table->timestamps();
            //$table->unique(['patient_id', 'tooth_number']); // سن واحد = سجل واحد لكل مريض (updateOrCreate)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tooth_treatments');
    }
};
