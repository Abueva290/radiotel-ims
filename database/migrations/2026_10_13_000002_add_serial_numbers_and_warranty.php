<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Panel revisions (Oct 2026): serial numbers and warranty.
 |
 | products.stock_qty stays the single source of the on-hand count, so sales,
 | reports and inventory movements keep working. For products flagged as
 | serialized, each physical unit also gets a row in product_units.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_serialized')->default(false)->after('category');
            // NULL = no warranty / not applicable
            $table->unsignedSmallInteger('warranty_months')->nullable()->after('is_serialized');
        });

        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('serial_no', 100)->unique();
            $table->enum('status', ['in_stock', 'sold', 'under_inspection', 'defective', 'returned_to_supplier', 'written_off'])
                  ->default('in_stock');
            $table->foreignId('sale_item_id')->nullable()->constrained('sale_items')->nullOnDelete();
            $table->dateTime('received_at');
            $table->date('warranty_start')->nullable();
            $table->date('warranty_end')->nullable();
            $table->string('remarks')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'status']);
        });

        // Units a customer brings in for repair (serial may or may not be one we sold)
        Schema::create('repair_job_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_job_id')->constrained()->cascadeOnDelete();
            $table->string('serial_no', 100);
            $table->foreignId('product_unit_id')->nullable()->constrained('product_units')->nullOnDelete();
            $table->date('warranty_start')->nullable();
            $table->date('warranty_end')->nullable();
            $table->timestamps();

            $table->unique(['repair_job_id', 'serial_no']);
            $table->index('serial_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_job_units');
        Schema::dropIfExists('product_units');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_serialized', 'warranty_months']);
        });
    }
};