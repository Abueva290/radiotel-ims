@extends('layouts.admin')

@section('title', 'Purchase Orders')

@section('content')
@php
    $canCreate = in_array(auth()->user()->role, ['admin', 'secretary']);
    $tabs = ['' => 'All', 'open' => 'Open', 'draft' => 'For Approval', 'approved' => 'Approved', 'sent' => 'Sent', 'received' => 'Received', 'cancelled' => 'Cancelled'];
    $cards = [
        ['For Approval',      $stats['draft'],    ['status' => 'draft'],    'Waiting for the Operational Manager'],
        ['Approved',          $stats['approved'], ['status' => 'approved'], 'Ready to send to the supplier'],
        ['Awaiting Delivery', $stats['sent'],     ['status' => 'sent'],     'Sent, not yet received'],
        ['Open PO Value',     '₱' . number_format($stats['open_value'], 2), ['status' => 'open'], 'For approval, approved, and sent'],
    ];
    $keep = array_filter(['q' => $q, 'supplier' => $supplierId]);
@endphp

<div class="flex flex-wrap items-start justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl font-semibold">Purchase Orders</h1>
        <p class="text-sm text-slate-500">Orders sent to suppliers, from approval to delivery</p>
    </div>
    @if ($canCreate)
        <a href="{{ route('purchase-orders.create') }}" class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">+ New Purchase Order</a>
    @endif
</div>

<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    @foreach ($cards as [$label, $value, $params, $note])
        <a href="{{ route('purchase-orders.index', $params) }}" class="kpi-card {{ $status === $params['status'] ? 'kpi-card-active' : '' }}">
            <p class="text-xs text-slate-500">{{ $label }}</p>
            <p class="text-lg sm:text-2xl font-semibold mt-2 [overflow-wrap:anywhere]">{{ $value }}</p>
            <p class="text-xs text-slate-400 mt-1 flex items-center justify-between gap-2"><span class="truncate">{{ $note }}</span><i class="ti ti-arrow-right kpi-arrow"></i></p>
        </a>
    @endforeach
</div>

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div class="chips">
        @foreach ($tabs as $value => $label)
            <a href="{{ route('purchase-orders.index', array_filter(['status' => $value]) + $keep) }}" class="chip {{ ($status ?? '') === $value ? 'on' : '' }}">{{ $label }}</a>
        @endforeach
    </div>
    <form method="GET" class="flex flex-wrap sm:flex-nowrap gap-2 w-full sm:w-auto">
        @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
        <select name="supplier" class="w-full sm:w-52 rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
            <option value="">All suppliers</option>
            @foreach ($suppliers as $s)
                <option value="{{ $s->id }}" @selected($supplierId == $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
        <input type="search" name="q" value="{{ $q }}" placeholder="PO no. or supplier"
               class="w-full sm:w-56 rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
        <button class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Search</button>
    </form>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-xs uppercase text-slate-400 border-b border-slate-100">
            <tr>
                <th class="px-4 py-3 text-left">PO No.</th>
                <th class="px-4 py-3 text-left">Date</th>
                <th class="px-4 py-3 text-left">Supplier</th>
                <th class="px-4 py-3 text-center">Items</th>
                <th class="px-4 py-3 text-right">Amount</th>
                <th class="px-4 py-3 text-left">Expected</th>
                <th class="px-4 py-3 text-center">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $po)
                <tr class="border-b border-slate-50 hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium whitespace-nowrap"><a href="{{ route('purchase-orders.show', $po) }}" class="hover:underline">{{ $po->po_no }}</a></td>
                    <td class="px-4 py-3 text-slate-500 whitespace-nowrap">{{ $po->order_date->format('M d, Y') }}</td>
                    <td class="px-4 py-3">{{ $po->supplier->name }}</td>
                    <td class="px-4 py-3 text-center">{{ $po->items->sum('quantity') }}</td>
                    <td class="px-4 py-3 text-right font-medium">₱{{ number_format($po->total_amount, 2) }}</td>
                    <td class="px-4 py-3 text-slate-500 whitespace-nowrap">
                        @if ($po->status === 'received')
                            Received {{ $po->received_date->format('M d') }}
                        @else
                            {{ $po->expected_date?->format('M d, Y') ?? '—' }}
                            @if ($po->expected_date && $po->status === 'sent' && $po->expected_date->lt(today()))
                                <span class="block text-xs text-red-600">Late</span>
                            @endif
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center"><x-badge type="purchase_order" :value="$po->status" /></td>
                    <td class="px-4 py-3 text-right"><a href="{{ route('purchase-orders.show', $po) }}" class="btn-action">View</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-12 text-center text-slate-400">
                        <i class="ti ti-shopping-cart text-3xl block mb-2 text-slate-300"></i>
                        {{ $status || $keep ? 'No purchase orders match these filters.' : 'No purchase orders yet.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $orders->links() }}</div>
@endsection
