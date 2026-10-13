@extends('layouts.admin')

@section('title', 'Sales')

@section('content')
@php
    use App\Support\Badge;
    $tabs = ['' => 'All', 'outstanding' => 'Outstanding', 'unpaid' => 'Unpaid', 'partial' => 'Partial', 'overdue' => 'Overdue', 'paid' => 'Paid'];
    $range = array_filter(['from' => $from, 'to' => $to]);
@endphp

<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-xl font-semibold">Sales Management</h1>
        <p class="text-sm text-slate-500">Charge invoices and delivery receipts</p>
    </div>
    <button type="button"
            onclick="window.dispatchEvent(new CustomEvent('open-add-sale'))"
            class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">+ New Sale</button>
</div>

@if ($range)
    <div class="mb-3 flex flex-wrap items-center gap-2 text-sm text-slate-600">
        <i class="ti ti-calendar"></i>
        Showing sales from <span class="font-medium">{{ $from ? \Carbon\Carbon::parse($from)->format('M d, Y') : 'the start' }}</span>
        to <span class="font-medium">{{ $to ? \Carbon\Carbon::parse($to)->format('M d, Y') : 'today' }}</span>
        <a href="{{ route('sales.index', array_filter(['status' => $status])) }}" class="underline text-slate-500">Show all dates</a>
    </div>
@endif

<div class="flex flex-wrap gap-2 mb-4">
    @foreach ($tabs as $value => $label)
        <a href="{{ route('sales.index', ($value ? ['status' => $value] : []) + $range) }}"
           class="px-3 py-1.5 rounded-lg text-sm {{ $status === ($value ?: null) ? 'bg-slate-700 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-xs uppercase text-slate-400 border-b border-slate-100">
            <tr>
                <th class="px-4 py-3 text-left">Invoice No.</th>
                <th class="px-4 py-3 text-left">Date</th>
                <th class="px-4 py-3 text-left">Customer</th>
                <th class="px-4 py-3 text-left">Items</th>
                <th class="px-4 py-3 text-right">Amount</th>
                <th class="px-4 py-3 text-left">DR No.</th>
                <th class="px-4 py-3 text-center">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sales as $sale)
                <tr class="border-b border-slate-50 hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium">{{ $sale->invoice_no }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ $sale->sale_date->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">{{ $sale->customer->name }}</td>
                    <td class="px-4 py-3 text-slate-500">
                        {{ $sale->items->map(fn ($i) => $i->product->name . ' ×' . $i->quantity)->implode(', ') }}
                    </td>
                    <td class="px-4 py-3 text-right font-medium">₱{{ number_format($sale->total_amount, 2) }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ $sale->dr_no }}</td>
                    <td class="px-4 py-3 text-center">
                        <x-badge type="payment" :value="Badge::paymentStatus($sale->status, $sale->receivable?->due_date)" />
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('sales.show', $sale) }}" class="btn-action">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                        {{ $status || $range ? 'No sales match this filter.' : 'No sales recorded yet.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="flex justify-end gap-8 mt-4 text-sm">
    <p class="text-slate-500">Total: <span class="font-semibold text-slate-800">₱{{ number_format($totals['all'], 2) }}</span></p>
    <p class="text-slate-500">Outstanding: <span class="font-semibold text-slate-800">₱{{ number_format($totals['unpaid'], 2) }}</span></p>
</div>

@include('sales._add-modal')
@endsection