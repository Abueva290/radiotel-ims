@extends('layouts.admin')

@section('title', 'Customer Orders')

@section('content')
@php
    $tabs = ['' => 'All', 'open' => 'Open', 'pending' => 'Pending', 'ordered' => 'Ordered from Supplier', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
    $cards = [
        ['Pending',               $stats['pending'],   ['status' => 'pending'],   'Not yet ordered from a supplier'],
        ['Ordered from Supplier', $stats['ordered'],   ['status' => 'ordered'],   'Waiting for supplier delivery'],
        ['Open Order Value',      '₱' . number_format($stats['open_value'], 2), ['status' => 'open'], 'Pending + ordered'],
        ['Completed',             $stats['completed'], ['status' => 'completed'], 'Converted to a sale'],
    ];
@endphp

<div class="flex flex-wrap items-start justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl font-semibold">Customer Orders</h1>
        <p class="text-sm text-slate-500">Purchase orders placed by customers through phone calls and walk-ins</p>
    </div>
    <a href="{{ route('customer-orders.create') }}" class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">+ New Customer Order</a>
</div>

<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    @foreach ($cards as [$label, $value, $params, $note])
        <a href="{{ route('customer-orders.index', $params) }}" class="kpi-card {{ $status === $params['status'] ? 'kpi-card-active' : '' }}">
            <p class="text-xs text-slate-500">{{ $label }}</p>
            <p class="text-2xl font-semibold mt-2">{{ $value }}</p>
            <p class="text-xs text-slate-400 mt-1 flex items-center justify-between gap-2"><span class="truncate">{{ $note }}</span><i class="ti ti-arrow-right kpi-arrow"></i></p>
        </a>
    @endforeach
</div>

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div class="chips">
        @foreach ($tabs as $value => $label)
            <a href="{{ route('customer-orders.index', array_filter(['status' => $value, 'q' => $q])) }}" class="chip {{ ($status ?? '') === $value ? 'on' : '' }}">{{ $label }}</a>
        @endforeach
    </div>
    <form method="GET" class="flex gap-2 w-full sm:w-auto">
        @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
        <input type="search" name="q" value="{{ $q }}" placeholder="Order no., customer PO, or customer"
               class="w-full sm:w-72 rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
        <button class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Search</button>
    </form>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-xs uppercase text-slate-400 border-b border-slate-100">
            <tr>
                <th class="px-4 py-3 text-left">Order No.</th>
                <th class="px-4 py-3 text-left">Date</th>
                <th class="px-4 py-3 text-left">Customer</th>
                <th class="px-4 py-3 text-left">Items</th>
                <th class="px-4 py-3 text-right">Amount</th>
                <th class="px-4 py-3 text-left">Supplier PO / Sale</th>
                <th class="px-4 py-3 text-center">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr class="border-b border-slate-50 hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium whitespace-nowrap">
                        <a href="{{ route('customer-orders.show', $order) }}" class="hover:underline">{{ $order->order_no }}</a>
                        @if ($order->customer_po_no)<span class="block text-xs text-slate-400">Their PO: {{ $order->customer_po_no }}</span>@endif
                    </td>
                    <td class="px-4 py-3 text-slate-500 whitespace-nowrap">
                        {{ $order->order_date->format('M d, Y') }}
                        <span class="block text-xs text-slate-400">{{ \App\Models\CustomerOrder::CHANNELS[$order->order_channel] }}</span>
                    </td>
                    <td class="px-4 py-3">{{ $order->customer->name }}</td>
                    <td class="px-4 py-3 text-slate-600">
                        {{ $order->items->first()?->product->name }}
                        @if ($order->items->count() > 1)<span class="text-xs text-slate-400">+{{ $order->items->count() - 1 }} more</span>@endif
                    </td>
                    <td class="px-4 py-3 text-right font-medium">₱{{ number_format($order->total_amount, 2) }}</td>
                    <td class="px-4 py-3 text-xs text-slate-500">
                        @foreach ($order->purchaseOrders->where('status', '!=', 'cancelled') as $po)
                            <span class="block">{{ $po->po_no }}</span>
                        @endforeach
                        @if ($order->sale)<span class="block text-slate-700">{{ $order->sale->invoice_no }}</span>@endif
                    </td>
                    <td class="px-4 py-3 text-center"><x-badge type="customer_order" :value="$order->status" /></td>
                    <td class="px-4 py-3 text-right"><a href="{{ route('customer-orders.show', $order) }}" class="btn-action">View</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-12 text-center text-slate-400">
                        <i class="ti ti-clipboard-list text-3xl block mb-2 text-slate-300"></i>
                        {{ $status || $q ? 'No customer orders match these filters.' : 'No customer orders yet.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $orders->links() }}</div>
@endsection