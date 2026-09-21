<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// total_amount = (units x 350) + parts_cost
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_no')->unique(); // RS-2026-0041
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('unit_model');
            $table->unsignedInteger('units')->default(1);
            $table->text('assessment_notes')->nullable();
            $table->date('date_received');
            $table->decimal('service_fee', 12, 2)->default(0);
            $table->decimal('parts_cost', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->enum('status', ['for_assessment', 'in_progress', 'completed', 'released'])
                  ->default('for_assessment');
            $table->timestamps();
        });

        Schema::create('repair_parts_used', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->integer('quantity_used');
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_parts_used');
        Schema::dropIfExists('repair_jobs');
    }
};