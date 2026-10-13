@extends('layouts.admin')

@section('title', 'Receivables')

@section('content')
@php
    // Each card filters the list below to the accounts behind its amount
    $cards = [
        ['Total Receivables', '₱' . number_format($stats['total'], 2), 'All accounts', null],
        ['Overdue', '₱' . number_format($stats['overdue'], 2), $stats['overdue_count'] . ' account(s) past due', 'overdue'],
        ['Current', '₱' . number_format($stats['current'], 2), $stats['current_count'] . ' account(s) not yet due', 'current'],
    ];
@endphp

<div class="mb-6">
    <h1 class="text-xl font-semibold">Accounts Receivable</h1>
    <p class="text-sm text-slate-500">Customer balances and collections</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    @foreach ($cards as [$label, $value, $note, $key])
        @php $selected = $filter === $key; @endphp
        <a href="{{ route('receivables.index', array_filter(['filter' => $selected ? null : $key, 'sort' => $sort !== 'asc' ? $sort : null])) }}"
           class="kpi-card {{ $selected && $key ? 'kpi-card-active' : '' }}"
           aria-label="{{ $label }}: {{ $value }}. {{ $key ? 'Show these accounts' : 'Show all accounts' }}">
            <p class="text-xs {{ $key === 'overdue' && $stats['overdue_count'] ? 'text-red-700' : 'text-slate-500' }}">{{ $label }}</p>
            <p class="text-2xl font-semibold mt-2 {{ $key === 'overdue' && $stats['overdue_count'] ? 'text-red-600' : '' }}">{{ $value }}</p>
            <p class="text-xs text-slate-400 mt-1 flex items-center justify-between gap-2">
                <span class="truncate">{{ $note }}</span><i class="ti ti-arrow-right kpi-arrow"></i>
            </p>
        </a>
    @endforeach
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto mb-6">
    <div class="flex flex-wrap items-center justify-between gap-3 px-6 pt-5 pb-3">
        <h2 class="font-semibold">
            Customer Balances
            @if ($filter)
                <span class="ml-2 text-sm font-normal text-slate-500">· {{ $filter === 'overdue' ? 'Overdue only' : 'Not yet due' }}
                    <a href="{{ route('receivables.index', $sort !== 'asc' ? ['sort' => $sort] : []) }}" class="underline">Show all</a></span>
            @endif
        </h2>
        <form method="GET" action="{{ route('receivables.index') }}" class="flex items-center gap-2">
        @if ($filter)<input type="hidden" name="filter" value="{{ $filter }}">@endif
        <label for="sort" class="text-sm text-slate-500">Sort by due date</label>
        <select id="sort" name="sort" onchange="this.form.submit()"
                class="rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
            <option value="asc"  @selected($sort === 'asc')>Ascending (nearest due date first)</option>
            <option value="desc" @selected($sort === 'desc')>Descending (farthest due date first)</option>
        </select>
    </form>
    </div>
    <table class="w-full text-sm">
        <thead class="text-xs uppercase text-slate-400 border-y border-slate-100">
            <tr>
                <th class="px-6 py-3 text-left">Reference</th>
                <th class="px-6 py-3 text-left">Customer</th>
                <th class="px-6 py-3 text-right">Amount Due</th>
                <th class="px-6 py-3 text-right">Balance</th>
                <th class="px-6 py-3 text-left">Due Date</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($receivables as $ar)
                @php $overdue = $ar->status !== 'paid' && $ar->due_date->isPast(); @endphp
                <tr class="border-b border-slate-50 hover:bg-slate-50 {{ $overdue ? 'bg-red-50/40' : '' }}">
                    <td class="px-6 py-3 font-medium">
                        {{ $ar->sale?->invoice_no ?? $ar->repairJob?->job_no ?? '—' }}
                    </td>
                    <td class="px-6 py-3">{{ $ar->customer->name }}</td>
                    <td class="px-6 py-3 text-right text-slate-500">₱{{ number_format($ar->amount_due, 2) }}</td>
                    <td class="px-6 py-3 text-right font-medium">₱{{ number_format($ar->balance, 2) }}</td>
                    <td class="px-6 py-3 {{ $overdue ? 'text-red-600 font-medium' : 'text-slate-500' }}">
                        {{ $ar->due_date->format('M d, Y') }}
                        @if ($overdue)<span class="text-xs">(overdue)</span>@endif
                    </td>
                    <td class="px-6 py-3 text-center">
                        <x-badge type="payment" :value="\App\Support\Badge::paymentStatus($ar->status, $ar->due_date)" />
                    </td>
                    <td class="px-6 py-3 text-right">
                        <a href="{{ route('receivables.show', $ar) }}" class="{{ $ar->status === 'paid' ? 'btn-action' : 'btn-action-primary' }}">
                            {{ $ar->status === 'paid' ? 'View' : 'Record Payment' }}
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-slate-400">{{ $filter ? 'No accounts match this filter.' : 'No receivables yet. Record a sale first.' }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <h2 class="font-semibold px-6 pt-5 pb-3">Recent Collections</h2>
    <table class="w-full text-sm">
        <thead class="text-xs uppercase text-slate-400 border-y border-slate-100">
            <tr>
                <th class="px-6 py-3 text-left">Receipt</th>
                <th class="px-6 py-3 text-left">Date</th>
                <th class="px-6 py-3 text-left">Customer</th>
                <th class="px-6 py-3 text-right">Amount</th>
                <th class="px-6 py-3 text-left">Mode</th>
                <th class="px-6 py-3 text-left">Received By</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($recent as $payment)
                <tr class="border-b border-slate-50">
                    <td class="px-6 py-3 font-medium">{{ $payment->receipt_no }}</td>
                    <td class="px-6 py-3 text-slate-500">{{ $payment->payment_date->format('M d, Y') }}</td>
                    <td class="px-6 py-3">{{ $payment->receivable->customer->name }}</td>
                    <td class="px-6 py-3 text-right font-medium">₱{{ number_format($payment->amount_paid, 2) }}</td>
                    <td class="px-6 py-3 text-slate-500">{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                    <td class="px-6 py-3">{{ $payment->receiver->name }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-6 text-center text-slate-400">No collections recorded yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection