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
    Schema::create('patients', function (Blueprint $table) {
    
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->enum('gender', ['male', 'female'])->nullable();
    $table->string('address')->nullable();
    $table->string('occupation')->nullable();
    $table->date('file_open_date')->nullable();
    // 🩺 medical history
    $table->boolean('medical_history_heart_disease')->default(false);
    $table->boolean('medical_history_diabetes')->default(false);
    $table->boolean('medical_history_blood_pressure')->default(false);
    $table->boolean('medical_history_asthma')->default(false);
    $table->boolean('medical_history_allergies_meds')->default(false);
    $table->boolean('medical_history_liver_disease')->default(false);
    $table->boolean('medical_history_kidney_disease')->default(false);
    $table->boolean('medical_history_blood_disorders')->default(false);
    $table->boolean('medical_history_pregnancy')->default(false);

    $table->text('current_medications')->nullable();
    $table->text('known_allergies')->nullable();
    $table->softDeletes();
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
