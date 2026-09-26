@extends('layouts.admin')

@section('title', 'Payables')

@section('content')
@php
    $badge = [
        'unpaid'  => 'bg-red-100 text-red-700',
        'partial' => 'bg-amber-100 text-amber-700',
        'paid'    => 'bg-green-100 text-green-700',
    ];
    $cards = [
        ['Total Payables', '₱' . number_format($stats['total'], 2)],
        ['Overdue', '₱' . number_format($stats['overdue'], 2)],
        ['Due within 14 days', '₱' . number_format($stats['due_soon'], 2)],
    ];
@endphp

<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-xl font-semibold">Accounts Payable</h1>
        <p class="text-sm text-slate-500">Supplier invoices and credit terms</p>
    </div>
    <a href="{{ route('payables.create') }}"
       class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">+ Record Invoice</a>
</div>

<div class="grid grid-cols-3 gap-4 mb-6">
    @foreach ($cards as [$label, $value])
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs text-slate-500">{{ $label }}</p>
            <p class="text-2xl font-semibold mt-2">{{ $value }}</p>
        </div>
    @endforeach
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-xs uppercase text-slate-400 border-b border-slate-100">
            <tr>
                <th class="px-4 py-3 text-left">Invoice No.</th>
                <th class="px-4 py-3 text-left">Supplier</th>
                <th class="px-4 py-3 text-right">Amount</th>
                <th class="px-4 py-3 text-right">Outstanding</th>
                <th class="px-4 py-3 text-center">Terms</th>
                <th class="px-4 py-3 text-left">Due Date</th>
                <th class="px-4 py-3 text-center">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoices as $invoice)
                @php $overdue = $invoice->status !== 'paid' && $invoice->due_date->isPast(); @endphp
                <tr class="border-b border-slate-50 hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium">{{ $invoice->invoice_no }}</td>
                    <td class="px-4 py-3">{{ $invoice->supplier->name }}</td>
                    <td class="px-4 py-3 text-right text-slate-500">₱{{ number_format($invoice->amount, 2) }}</td>
                    <td class="px-4 py-3 text-right font-medium">₱{{ number_format($invoice->balance, 2) }}</td>
                    <td class="px-4 py-3 text-center text-slate-500">{{ $invoice->supplier->credit_terms_days }} days</td>
                    <td class="px-4 py-3 {{ $overdue ? 'text-red-600 font-medium' : 'text-slate-500' }}">
                        {{ $invoice->due_date->format('M d, Y') }}
                        @if ($overdue)<span class="text-xs">(overdue)</span>@endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $badge[$invoice->status] }}">{{ ucfirst($invoice->status) }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('payables.show', $invoice) }}" class="{{ $invoice->status === 'paid' ? 'btn-action' : 'btn-action-primary' }}">
                            {{ $invoice->status === 'paid' ? 'View' : 'Pay' }}
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-slate-400">No supplier invoices recorded yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection