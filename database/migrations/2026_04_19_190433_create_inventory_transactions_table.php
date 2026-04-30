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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
           // $table->foreignId('treatment_session_id')->nullable()->constrained('treatment_sessions')->nullOnDelete();
           $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
// 👇 ربط الحركة الأصلية (للإرجاع)
            $table->foreignId('reference_id')->nullable()->constrained('inventory_transactions')->nullOnDelete();
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->enum('type', ['in', 'out', 'return']);
            $table->integer('quantity');
            $table->dateTime('transaction_date')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
