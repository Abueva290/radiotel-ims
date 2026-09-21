<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receivables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('repair_job_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->decimal('amount_due', 12, 2);
            $table->date('due_date'); // 30 days default, up to 60
            $table->decimal('balance', 12, 2);
            $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->timestamps();
        });

        Schema::create('receivable_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receivable_id')->constrained()->restrictOnDelete();
            $table->string('receipt_no')->unique(); // REC-0044
            $table->decimal('amount_paid', 12, 2);
            $table->enum('payment_method', ['cash', 'check', 'bank_transfer']);
            $table->date('payment_date');
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receivable_payments');
        Schema::dropIfExists('receivables');
    }
};