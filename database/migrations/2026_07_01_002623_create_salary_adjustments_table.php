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
        Schema::create('salary_adjustments', function (Blueprint $table) {
            $table->id();
             $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // الموظف
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // مين أضافه
            $table->enum('type', ['bonus', 'deduction']); // مكافأة أو خصم
            $table->decimal('amount_usd', 10, 2);
            $table->string('reason')->nullable(); // مثلاً: "تأخير 3 أيام" أو "مكافأة أداء"
            $table->date('salary_month'); // الشهر يلي رح يطبق عليه
            $table->foreignId('salary_payment_id')->nullable()->constrained('salary_payments')->nullOnDelete(); // يترَبط بالدفعة بعد ما يدفع
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_adjustments');
    }
};
