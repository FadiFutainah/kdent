<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // أول شي: نعطي MySQL index بديل يعتمد عليه الـ Foreign Key الخاص بـ patient_id
        Schema::table('tooth_treatments', function (Blueprint $table) {
            $table->index('patient_id');
        });

        // هلق نقدر نحذف الـ unique المركب بأمان لأنو صار في بديل يغطي الـ FK
        Schema::table('tooth_treatments', function (Blueprint $table) {
            $table->dropUnique(['patient_id', 'tooth_number']);
        });
    }

    public function down(): void
    {
        Schema::table('tooth_treatments', function (Blueprint $table) {
            $table->dropIndex(['patient_id']);
            $table->unique(['patient_id', 'tooth_number']);
        });
    }
};