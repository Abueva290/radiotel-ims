@extends('layouts.admin')

@section('title', 'Customer Order')

@section('content')
@php
    $shortItems = $order->items->filter(fn ($i) => $i->quantity > $i->product->stock_qty);
@endphp

<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-xl font-semibold">{{ $order->order_no }}</h1>
            <x-badge type="customer_order" :value="$order->status" size="md" />
        </div>
        <p class="text-sm text-slate-500">{{ $order->customer->name }} · {{ $order->order_date->format('F d, Y') }} · {{ \App\Models\CustomerOrder::CHANNELS[$order->order_channel] }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if ($order->isOpen())
            <a href="{{ route('purchase-orders.create', ['customer_order' => $order->id]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white border border-slate-200 text-sm hover:bg-slate-50">
                <i class="ti ti-shopping-cart text-base"></i> Order from Supplier
            </a>
            <a href="{{ route('sales.index', ['from_order' => $order->id]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">
                <i class="ti ti-receipt text-base"></i> Convert to Sale
            </a>
        @endif
        <a href="{{ route('customer-orders.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Back</a>
    </div>
</div>

@if ($order->isOpen() && $shortItems->isNotEmpty())
    <div class="mb-6 flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <i class="ti ti-alert-triangle text-lg"></i>
        <p>Not enough stock yet for {{ $shortItems->pluck('product.name')->join(', ', ' and ') }}.
            Order the items from a supplier, then record the stock-in in Inventory when they arrive before converting this order to a sale.</p>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-semibold mb-4">Ordered Items</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs uppercase text-slate-400 border-b border-slate-100">
                    <tr>
                        <th class="py-2 text-left">Product</th>
                        <th class="py-2 text-center">Qty</th>
                        <th class="py-2 text-center">In Stock</th>
                        <th class="py-2 text-right">Unit Price</th>
                        <th class="py-2 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr class="border-b border-slate-50">
                            <td class="py-3">{{ $item->product->name }}</td>
                            <td class="py-3 text-center">{{ $item->quantity }}</td>
                            <td class="py-3 text-center {{ $order->isOpen() && $item->quantity > $item->product->stock_qty ? 'text-amber-600 font-medium' : 'text-slate-500' }}">{{ $item->product->stock_qty }}</td>
                            <td class="py-3 text-right">₱{{ number_format($item->unit_price, 2) }}</td>
                            <td class="py-3 text-right font-medium">₱{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="flex justify-end items-baseline gap-6 mt-4 pt-4 border-t border-slate-100">
            <p class="text-sm text-slate-500">Total</p>
            <p class="text-xl font-semibold">₱{{ number_format($order->total_amount, 2) }}</p>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="font-semibold mb-3">Order Details</h2>
            <dl class="text-sm space-y-2">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Customer's PO No.</dt><dd>{{ $order->customer_po_no ?: '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Needed by</dt><dd>{{ $order->needed_by?->format('M d, Y') ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Recorded by</dt><dd>{{ $order->creator->name }}</dd></div>
                @if ($order->remarks)
                    <div><dt class="text-slate-500">Remarks</dt><dd class="mt-1">{{ $order->remarks }}</dd></div>
                @endif
            </dl>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="font-semibold mb-3">Supplier Purchase Orders</h2>
            @forelse ($order->purchaseOrders as $po)
                <a href="{{ route('purchase-orders.show', $po) }}" class="flex items-center justify-between gap-3 py-2 border-b border-slate-50 last:border-0 text-sm hover:text-slate-900">
                    <span><span class="font-medium">{{ $po->po_no }}</span><span class="block text-xs text-slate-400">{{ $po->supplier->name }}</span></span>
                    <x-badge type="purchase_order" :value="$po->status" />
                </a>
            @empty
                <p class="text-sm text-slate-400">No purchase order to a supplier yet.</p>
            @endforelse
        </div>

        @if ($order->sale)
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h2 class="font-semibold mb-3">Sale</h2>
                <a href="{{ route('sales.show', $order->sale) }}" class="text-sm underline">{{ $order->sale->invoice_no }}</a>
                <p class="text-xs text-slate-400 mt-1">Delivered on {{ $order->sale->sale_date->format('M d, Y') }} · {{ $order->sale->dr_no }}</p>
            </div>
        @endif

        @if ($order->isOpen())
            <form method="POST" action="{{ route('customer-orders.cancel', $order) }}"
                  data-confirm="Cancel {{ $order->order_no }}? This cannot be undone." data-confirm-title="Cancel customer order"
                  data-confirm-button="Yes, cancel order" data-confirm-tone="danger">
                @csrf @method('PATCH')
                <button class="w-full px-4 py-2 rounded-lg bg-red-50 text-red-700 text-sm hover:bg-red-100">Cancel Order</button>
            </form>
        @endif
    </div>
</div>
@endsection