@extends('layouts.admin')

@section('title', 'Sale Details')

@section('content')
@php
    $badge = [
        'unpaid'  => 'bg-red-100 text-red-700',
        'partial' => 'bg-amber-100 text-amber-700',
        'paid'    => 'bg-green-100 text-green-700',
    ];
@endphp

<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-xl font-semibold">{{ $sale->invoice_no }}</h1>
        <p class="text-sm text-slate-500">{{ $sale->customer->name }} · {{ $sale->sale_date->format('F d, Y') }}</p>
    </div>
    <a href="{{ route('sales.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Back to Sales</a>
</div>

<div class="grid grid-cols-3 gap-6">
    <div class="col-span-2 bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-semibold mb-4">Items</h2>
        <table class="w-full text-sm">
            <thead class="text-xs uppercase text-slate-400 border-b border-slate-100">
                <tr>
                    <th class="py-2 text-left">Product</th>
                    <th class="py-2 text-center">Qty</th>
                    <th class="py-2 text-right">Unit Price</th>
                    <th class="py-2 text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr class="border-b border-slate-50">
                        <td class="py-3">{{ $item->product->name }}</td>
                        <td class="py-3 text-center">{{ $item->quantity }}</td>
                        <td class="py-3 text-right">₱{{ number_format($item->unit_price, 2) }}</td>
                        <td class="py-3 text-right font-medium">₱{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="flex justify-end items-baseline gap-6 mt-4 pt-4 border-t border-slate-100">
            <p class="text-sm text-slate-500">Total</p>
            <p class="text-xl font-semibold">₱{{ number_format($sale->total_amount, 2) }}</p>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="font-semibold mb-3">Transaction</h2>
            <dl class="text-sm space-y-2">
                <div class="flex justify-between"><dt class="text-slate-500">DR No.</dt><dd>{{ $sale->dr_no }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Recorded by</dt><dd>{{ $sale->creator->name }}</dd></div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Status</dt>
                    <dd><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $badge[$sale->status] }}">{{ ucfirst($sale->status) }}</span></dd>
                </div>
            </dl>
        </div>

        @if ($sale->receivable)
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h2 class="font-semibold mb-3">Receivable</h2>
                <dl class="text-sm space-y-2">
                    <div class="flex justify-between"><dt class="text-slate-500">Amount due</dt><dd>₱{{ number_format($sale->receivable->amount_due, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Balance</dt><dd class="font-semibold">₱{{ number_format($sale->receivable->balance, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Due date</dt><dd>{{ $sale->receivable->due_date->format('M d, Y') }}</dd></div>
                </dl>
            </div>
        @endif
    </div>
</div>
@endsection