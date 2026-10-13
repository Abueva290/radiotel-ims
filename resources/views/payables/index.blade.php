@extends('layouts.admin')

@section('title', 'Payables')

@section('content')
@php
    // Each card filters the invoice list below to the invoices behind its amount
    $cards = [
        ['Total Payables', '₱' . number_format($stats['total'], 2), $stats['open_count'] . ' open invoice(s)', null],
        ['Overdue', '₱' . number_format($stats['overdue'], 2), $stats['overdue_count'] . ' invoice(s) past due', 'overdue'],
        ['Due within 14 days', '₱' . number_format($stats['due_soon'], 2), $stats['due_soon_count'] . ' invoice(s) due soon', 'due_soon'],
    ];
    $filterLabels = ['overdue' => 'Overdue only', 'due_soon' => 'Due within 14 days'];
    $states = [
        0 => ['Overdue',  'red'],
        1 => ['Due soon', 'amber'],
        2 => ['Current',  'gray'],
        3 => ['Settled',  'green'],
    ];
    $owing   = $supplierCards->where('state_rank', '<', 3);
    $settled = $supplierCards->where('state_rank', 3);
    // Builds a list URL, keeping the other active filters unless overridden
    $listUrl = fn (array $override = []) => route('payables.index', array_filter(array_merge([
        'supplier' => $selected?->id,
        'filter'   => $filter,
        'sort'     => $sort !== 'asc' ? $sort : null,
    ], $override))) . '#invoices';
    $filterUrl = fn ($s) => $listUrl(['supplier' => ($selected && $selected->id === $s->id) ? null : $s->id]);
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
    @foreach ($cards as [$label, $value, $note, $key])
        @php
            $active = $key && $filter === $key;
            $alert = $key === 'overdue' && $stats['overdue_count'];
        @endphp
        <a href="{{ $listUrl(['filter' => $active ? null : $key]) }}"
           class="kpi-card {{ $active ? 'kpi-card-active' : '' }}"
           aria-label="{{ $label }}: {{ $value }}. {{ $key ? 'Show these invoices' : 'Show all invoices' }}">
            <p class="text-xs {{ $alert ? 'text-red-700' : 'text-slate-500' }}">{{ $label }}</p>
            <p class="text-2xl font-semibold mt-2 tabular-nums {{ $alert ? 'text-red-600' : '' }}">{{ $value }}</p>
            <p class="text-xs text-slate-400 mt-1 flex items-center justify-between gap-2">
                <span class="truncate">{{ $note }}</span><i class="ti ti-arrow-right kpi-arrow"></i>
            </p>
        </a>
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
                    <x-badge :tone="$stateCls" size="md" class="relative">{{ $state }}</x-badge>
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
<div id="invoices" class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 mb-3 scroll-mt-6">
    <h2 class="text-base font-semibold">
        {{ $selected ? 'Invoices from ' . $selected->name : 'All supplier invoices' }}
        @if ($filter)
            <span class="ml-2 text-sm font-normal text-slate-500">· {{ $filterLabels[$filter] }}
                <a href="{{ $listUrl(['filter' => null]) }}" class="underline">Clear</a></span>
        @endif
    </h2>
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
        @if ($selected)
            <a href="{{ $listUrl(['supplier' => null]) }}" class="text-sm text-slate-600 underline hover:text-slate-900">Show all suppliers</a>
        @endif
        <form method="GET" action="{{ route('payables.index') }}#invoices" class="flex items-center gap-2">
        @if ($selected)<input type="hidden" name="supplier" value="{{ $selected->id }}">@endif
        @if ($filter)<input type="hidden" name="filter" value="{{ $filter }}">@endif
        <label for="sort" class="text-sm text-slate-500">Sort by due date</label>
        <select id="sort" name="sort" onchange="this.form.submit()"
                class="rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
            <option value="asc"  @selected($sort === 'asc')>Ascending (nearest due date first)</option>
            <option value="desc" @selected($sort === 'desc')>Descending (farthest due date first)</option>
        </select>
    </form>
    </div>
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
                <tr class="border-b border-slate-50 hover:bg-slate-50 {{ $overdue ? 'bg-red-50/40' : '' }}">
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
                        <x-badge type="payment" :value="\App\Support\Badge::paymentStatus($invoice->status, $invoice->due_date)" />
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
                    <td colspan="8" class="px-4 py-8 text-center text-slate-400">{{ $filter ? 'No invoices match this filter.' : ($selected ? 'No invoices from this supplier yet.' : 'No supplier invoices recorded yet.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('payables._add-modal')
@endsection