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
    $states = [
        0 => ['Overdue',  'bg-red-100 text-red-700'],
        1 => ['Due soon', 'bg-amber-100 text-amber-700'],
        2 => ['Current',  'bg-slate-100 text-slate-700'],
        3 => ['Settled',  'bg-green-100 text-green-700'],
    ];
    $owing   = $supplierCards->where('state_rank', '<', 3);
    $settled = $supplierCards->where('state_rank', 3);
    $filterUrl = fn ($s) => ($selected && $selected->id === $s->id)
        ? route('payables.index')
        : route('payables.index', ['supplier' => $s->id]);
@endphp

<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-xl font-semibold">Accounts Payable</h1>
        <p class="text-sm text-slate-500">Supplier invoices and credit terms</p>
    </div>
    <button type="button"
            onclick="window.dispatchEvent(new CustomEvent('open-add-invoice'))"
            class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">+ Record Invoice</button>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    @foreach ($cards as [$label, $value])
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs text-slate-500">{{ $label }}</p>
            <p class="text-2xl font-semibold mt-2 tabular-nums">{{ $value }}</p>
        </div>
    @endforeach
</div>

{{-- Suppliers with a balance: one card each, most urgent first --}}
<div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 mb-3">
    <h2 class="text-base font-semibold">Suppliers with a balance</h2>
    <p class="text-sm text-slate-500">Click a card to see that supplier's invoices</p>
</div>

@if ($owing->isEmpty())
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-8 text-sm text-slate-500">
        Every supplier is fully paid. New supplier invoices will appear here.
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mb-8">
        @foreach ($owing as $supplier)
            @php
                [$state, $stateCls] = $states[$supplier->state_rank];
                $overdueAmt = (float) $supplier->overdue_amount;
                $nextDue = $supplier->next_due ? \Carbon\Carbon::parse($supplier->next_due) : null;
                $isSelected = $selected && $selected->id === $supplier->id;
            @endphp
            <div class="relative bg-white rounded-xl border p-5 transition-colors hover:border-slate-400
                        {{ $isSelected ? 'border-slate-700 ring-1 ring-slate-700' : 'border-slate-200' }}">
                {{-- The whole card filters the invoice list --}}
                <a href="{{ $filterUrl($supplier) }}" class="absolute inset-0 rounded-xl"
                   aria-label="{{ $isSelected ? 'Show all invoices' : 'Show invoices from ' . $supplier->name }}"></a>

                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold truncate">{{ $supplier->name }}</p>
                        <p class="text-sm text-slate-500 truncate">{{ $supplier->contact_person ?? 'No contact person' }}</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap {{ $stateCls }}">{{ $state }}</span>
                </div>

                <p class="text-2xl font-semibold tabular-nums mt-4">₱{{ number_format((float) $supplier->outstanding, 2) }}</p>
                @if ($overdueAmt > 0)
                    <p class="text-sm text-red-600 tabular-nums">₱{{ number_format($overdueAmt, 2) }} overdue</p>
                @endif

                <p class="text-sm text-slate-600 mt-3">
                    {{ $supplier->open_count }} open {{ \Illuminate\Support\Str::plural('invoice', $supplier->open_count) }},
                    next due <span class="font-medium {{ $overdueAmt > 0 ? 'text-red-600' : 'text-slate-800' }}">{{ $nextDue?->format('M d, Y') }}</span>
                </p>
                <p class="text-sm text-slate-500">{{ $supplier->credit_terms_days > 0 ? $supplier->credit_terms_days . '-day terms' : 'Cash supplier' }}</p>

                <button type="button"
                        onclick="window.dispatchEvent(new CustomEvent('open-add-invoice', { detail: { supplier: {{ $supplier->id }} } }))"
                        class="relative z-10 mt-4 text-sm text-slate-600 underline hover:text-slate-900">
                    Record invoice
                </button>
            </div>
        @endforeach
    </div>
@endif

{{-- Settled suppliers: compact list --}}
@if ($settled->isNotEmpty())
    <div class="bg-white rounded-xl border border-slate-200 mb-8">
        <p class="px-5 py-3 text-sm font-medium border-b border-slate-100">
            Fully paid <span class="text-slate-400 font-normal">({{ $settled->count() }})</span>
        </p>
        @foreach ($settled as $supplier)
            @php $isSelected = $selected && $selected->id === $supplier->id; @endphp
            <div class="flex items-center gap-4 px-5 py-3 border-b border-slate-50 last:border-b-0 text-sm {{ $isSelected ? 'bg-slate-50' : '' }}">
                <a href="{{ $filterUrl($supplier) }}" class="flex-1 min-w-0 truncate font-medium hover:underline">{{ $supplier->name }}</a>
                <span class="hidden md:block w-40 truncate text-slate-500">{{ $supplier->contact_person }}</span>
                <span class="hidden sm:block w-28 text-slate-500">{{ $supplier->credit_terms_days > 0 ? $supplier->credit_terms_days . '-day terms' : 'Cash' }}</span>
                <button type="button"
                        onclick="window.dispatchEvent(new CustomEvent('open-add-invoice', { detail: { supplier: {{ $supplier->id }} } }))"
                        class="text-slate-600 underline hover:text-slate-900 whitespace-nowrap">
                    Record invoice
                </button>
            </div>
        @endforeach
    </div>
@endif

{{-- Invoices --}}
<div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 mb-3">
    <h2 class="text-base font-semibold">{{ $selected ? 'Invoices from ' . $selected->name : 'All supplier invoices' }}</h2>
    @if ($selected)
        <a href="{{ route('payables.index') }}" class="text-sm text-slate-600 underline hover:text-slate-900">Show all suppliers</a>
    @endif
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
                    <td class="px-4 py-3 text-right text-slate-500 tabular-nums">₱{{ number_format($invoice->amount, 2) }}</td>
                    <td class="px-4 py-3 text-right font-medium tabular-nums">₱{{ number_format($invoice->balance, 2) }}</td>
                    <td class="px-4 py-3 text-center text-slate-500">{{ $invoice->supplier->credit_terms_days }} days</td>
                    <td class="px-4 py-3 {{ $overdue ? 'text-red-600 font-medium' : 'text-slate-500' }}">
                        {{ $invoice->due_date->format('M d, Y') }}
                        @if ($overdue)<span class="text-xs">(overdue)</span>@endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $badge[$invoice->status] }}">{{ ucfirst($invoice->status) }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        {{-- Only the Operational Manager (admin) pays suppliers --}}
                        <a href="{{ route('payables.show', $invoice) }}" class="{{ $invoice->status !== 'paid' && auth()->user()->hasRole('admin') ? 'btn-action-primary' : 'btn-action' }}">
                            {{ $invoice->status !== 'paid' && auth()->user()->hasRole('admin') ? 'Pay' : 'View' }}
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-slate-400">{{ $selected ? 'No invoices from this supplier yet.' : 'No supplier invoices recorded yet.' }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('payables._add-modal')
@endsection