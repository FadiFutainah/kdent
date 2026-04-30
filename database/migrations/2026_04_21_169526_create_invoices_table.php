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
            $table->string('type'); // supplier | patient | doctor | salary
            $table->foreignId('supplier_id')->constrained();
            $table->string('invoice_number')->unique()->nullable();
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('total_amount_USD', 12, 2)->default(0);
            $table->decimal('total_amount_SYP', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->string('currency')->default('USD');
            $table->decimal('exchange_rate', 12, 4)->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->enum('status', ['draft', 'issued', 'partial', 'paid', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->string('pdf_url')->nullable();
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
