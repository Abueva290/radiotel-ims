<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Panel revisions (Oct 2026): factory defect tracking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factory_defects', function (Blueprint $table) {
            $table->id();
            $table->string('defect_no')->unique(); // FD-2026-0001
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_unit_id')->nullable()->constrained('product_units')->nullOnDelete();
            $table->string('serial_no', 100)->nullable();
            $table->foreignId('repair_job_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();

            // inventory = item from our own stock; customer_unit = unit brought in by a customer
            $table->enum('source', ['inventory', 'customer_unit'])->default('inventory');
            $table->unsignedInteger('quantity')->default(1);
            $table->text('description');

            // Separates factory defects from other causes found during inspection
            $table->enum('cause', ['unverified', 'factory_defect', 'customer_damage', 'wear_and_tear', 'other'])
                  ->default('unverified');
            $table->enum('status', [
                'reported', 'under_inspection', 'confirmed', 'returned_to_supplier',
                'repaired', 'replaced', 'resolved',
            ])->default('reported');

            $table->date('date_discovered');
            $table->foreignId('reported_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('inspection_findings')->nullable();
            $table->text('action_taken')->nullable();
            // TRUE while the defective quantity has been taken out of sellable stock
            $table->boolean('stock_quarantined')->default(false);
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('serial_no');
        });

        Schema::create('factory_defect_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_defect_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('remarks', 500)->nullable();
            $table->dateTime('changed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factory_defect_logs');
        Schema::dropIfExists('factory_defects');
    }
};