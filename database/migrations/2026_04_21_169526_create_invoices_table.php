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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
           $table->enum('type', ['supplier', 'patient', 'doctor', 'salary'])->default('supplier');
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('treatment_plans')->nullOnDelete();
            $table->string('invoice_number')->unique()->nullable();
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('total_amount_USD', 12, 2)->default(0);
            $table->decimal('total_amount_SYP', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
             $table->decimal('total_amount_USD_after_discount', 12, 2)->default(0)->nullable();
            $table->decimal('total_amount_SYP_after_discount', 12, 2)->default(0)->nullable();
           // $table->string('currency')->default('USD');
            $table->decimal('exchange_rate', 12, 4)->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->enum('status', ['draft', 'issued', 'partial', 'paid', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamp('last_reminder_sent_at')->nullable();
          //  $table->string('pdf_url')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
