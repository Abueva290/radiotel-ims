<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Client request: the separate Customer Order module is removed.
 * Every sale now records the customer's own Purchase Order number instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('customer_po_no')->nullable()->after('dr_no');
        });

        // Keep the customer's PO number on sales that came from a customer order
        if (Schema::hasTable('customer_orders')) {
            DB::table('customer_orders')
                ->whereNotNull('sale_id')
                ->whereNotNull('customer_po_no')
                ->orderBy('id')
                ->each(function ($order) {
                    DB::table('sales')->where('id', $order->sale_id)
                        ->update(['customer_po_no' => $order->customer_po_no]);
                });
        }

        if (Schema::hasColumn('purchase_orders', 'customer_order_id')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropConstrainedForeignId('customer_order_id');
            });
        }

        Schema::dropIfExists('customer_order_items');
        Schema::dropIfExists('customer_orders');
    }

    public function down(): void
    {
        Schema::create('customer_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('customer_po_no')->nullable();
            $table->date('order_date');
            $table->date('needed_by')->nullable();
            $table->enum('order_channel', ['phone', 'walk_in'])->default('phone');
            $table->enum('status', ['pending', 'ordered', 'completed', 'cancelled'])->default('pending');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->foreignId('sale_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('customer_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('customer_order_id')->nullable()->after('supplier_id')
                ->constrained()->nullOnDelete();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('customer_po_no');
        });
    }
};