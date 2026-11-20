@extends('layouts.admin')

@section('title', 'New Purchase Order')

@section('content')
@php
    $input = 'w-full rounded-lg border-slate-300 text-sm py-2 px-3 focus:border-slate-500 focus:ring-slate-500';
    // From a customer order: copy its items at each product's unit cost
    $rows = $customerOrder ? $customerOrder->items->map(fn ($i) => [
        'product_id' => $i->product_id, 'quantity' => $i->quantity, 'price' => (float) $i->product->unit_cost,
    ])->values()->all() : [];
    $linked = old('customer_order_id', $customerOrder?->id);
@endphp

<div class="max-w-5xl">
    <div class="mb-6">
        <h1 class="text-xl font-semibold">New Purchase Order</h1>
        <p class="text-sm text-slate-500">Order items from a supplier. The Operational Manager approves it before it is sent. PO No. <span class="font-medium text-slate-700">{{ $poNo }}</span></p>
    </div>

    @if ($customerOrder)
        <div class="mb-4 flex items-start gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
            <i class="ti ti-clipboard-list text-lg"></i>
            <p>Items copied from customer order <span class="font-semibold">{{ $customerOrder->order_no }}</span> ({{ $customerOrder->customer->name }}). Adjust quantities if some are already in stock.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('purchase-orders.store') }}" class="space-y-4"
          data-confirm="Submit this purchase order for the Operational Manager's approval?"
          data-confirm-title="Submit purchase order" data-confirm-button="Yes, submit">
        @csrf

        <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-6">
            <h2 class="font-semibold mb-4">Purchase Order Information</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1">Supplier</label>
                    <select name="supplier_id" class="{{ $input }}">
                        <option value="">Select supplier...</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>
                                {{ $supplier->name }} · {{ $supplier->credit_terms_days ? $supplier->credit_terms_days . '-day credit' : 'cash' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1">For Customer Order <span class="font-normal text-slate-400">(optional)</span></label>
                    <select name="customer_order_id" class="{{ $input }}">
                        <option value="">None – stock replenishment</option>
                        @foreach ($openOrders as $co)
                            <option value="{{ $co->id }}" @selected($linked == $co->id)>{{ $co->order_no }} · {{ $co->customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">PO Date</label>
                    <input type="date" name="order_date" value="{{ old('order_date', now()->toDateString()) }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Expected Delivery <span class="font-normal text-slate-400">(optional)</span></label>
                    <input type="date" name="expected_date" value="{{ old('expected_date') }}" class="{{ $input }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1">Remarks <span class="font-normal text-slate-400">(optional)</span></label>
                    <input type="text" name="remarks" value="{{ old('remarks') }}" placeholder="Delivery address, contact, special instructions" class="{{ $input }}">
                </div>
            </div>
        </div>

        @include('partials.order-items', [
            'products' => $products, 'rows' => $rows, 'priceField' => 'unit_cost',
            'priceLabel' => 'Unit Cost', 'priceAttr' => 'unit_cost',
        ])

        <div class="flex justify-end gap-3">
            <a href="{{ $customerOrder ? route('customer-orders.show', $customerOrder) : route('purchase-orders.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Cancel</a>
            <button class="px-5 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Submit for Approval</button>
        </div>
    </form>
</div>
@endsection