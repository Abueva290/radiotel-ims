@extends('layouts.admin')

@section('title', 'New Customer Order')

@section('content')
@php $input = 'w-full rounded-lg border-slate-300 text-sm py-2 px-3 focus:border-slate-500 focus:ring-slate-500'; @endphp

<div class="max-w-5xl">
    <div class="mb-6">
        <h1 class="text-xl font-semibold">New Customer Order</h1>
        <p class="text-sm text-slate-500">Record a purchase order a customer placed by phone or walk-in. Order No. <span class="font-medium text-slate-700">{{ $orderNo }}</span></p>
    </div>

    <form method="POST" action="{{ route('customer-orders.store') }}" class="space-y-4"
          data-confirm="Save this customer order? You can then order the items from a supplier or convert it to a sale once stock is available."
          data-confirm-title="Save customer order" data-confirm-button="Yes, save order">
        @csrf

        <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-6">
            <h2 class="font-semibold mb-4">Order Information</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1">Customer</label>
                    <select name="customer_id" class="{{ $input }}">
                        <option value="">Select customer...</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Customer's PO No. <span class="font-normal text-slate-400">(if any)</span></label>
                    <input type="text" name="customer_po_no" value="{{ old('customer_po_no') }}" placeholder="e.g. PO-88213" class="{{ $input }}">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Received Via</label>
                    <select name="order_channel" class="{{ $input }}">
                        @foreach (\App\Models\CustomerOrder::CHANNELS as $value => $label)
                            <option value="{{ $value }}" @selected(old('order_channel', 'phone') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Order Date</label>
                    <input type="date" name="order_date" value="{{ old('order_date', now()->toDateString()) }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Needed By <span class="font-normal text-slate-400">(optional)</span></label>
                    <input type="date" name="needed_by" value="{{ old('needed_by') }}" class="{{ $input }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1">Remarks <span class="font-normal text-slate-400">(optional)</span></label>
                    <input type="text" name="remarks" value="{{ old('remarks') }}" placeholder="Delivery instructions, quotation reference, etc." class="{{ $input }}">
                </div>
            </div>
        </div>

        @include('partials.order-items', [
            'products' => $products, 'rows' => [], 'priceField' => 'unit_price',
            'priceLabel' => 'Selling Price', 'priceAttr' => 'selling_price',
        ])

        <div class="flex justify-end gap-3">
            <a href="{{ route('customer-orders.index') }}" class="px-5 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Cancel</a>
            <button class="px-5 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Save Order</button>
        </div>
    </form>
</div>
@endsection