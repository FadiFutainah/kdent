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
        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // الموظف المستلم
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete(); // مين سجّل الدفعة (أدمن/محاسب)
            $table->foreignId('exchange_rate_id')->nullable()->constrained('exchange_rates')->nullOnDelete();
            $table->decimal('amount_usd', 10, 2)->nullable();
            $table->decimal('base_amount_usd', 10, 2)->nullable(); // الراتب الأساسي قبل التعديلات
            $table->decimal('bonus_total_usd', 10, 2)->default(0);
            $table->decimal('deduction_total_usd', 10, 2)->default(0);
            $table->decimal('amount_syp', 12, 2)->nullable();
            $table->date('salary_month'); // الشهر اللي عم يتقاضى راتبه عنه (مثلاً 2026-06-01)
            $table->dateTime('payment_date');
            $table->string('status')->default('paid'); // paid / pending لو حبيت تستخدمها لاحقًا
            $table->text('notes')->nullable();
            $table->unique(['user_id', 'salary_month']); // يمنع دفع راتب نفس الشهر مرتين لنفس الموظف
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
    }
};
