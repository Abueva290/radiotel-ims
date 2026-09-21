<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->string('invoice_no');
            $table->date('invoice_date');
            $table->decimal('amount', 12, 2);
            $table->date('due_date'); // invoice_date + credit_terms_days
            $table->decimal('balance', 12, 2);
            $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['supplier_id', 'invoice_no']);
        });

        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_invoice_id')->constrained()->restrictOnDelete();
            $table->date('payment_date');
            $table->decimal('amount_paid', 12, 2);
            $table->enum('payment_method', ['cash', 'check', 'bank_transfer']);
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('supplier_invoices');
    }
};