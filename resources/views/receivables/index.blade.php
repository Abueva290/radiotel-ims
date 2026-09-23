@extends('layouts.admin')

@section('title', 'Receivables')

@section('content')
@php
    $badge = [
        'unpaid'  => 'bg-red-100 text-red-700',
        'partial' => 'bg-amber-100 text-amber-700',
        'paid'    => 'bg-green-100 text-green-700',
    ];
    $cards = [
        ['Total Receivables', '₱' . number_format($stats['total'], 2), ''],
        ['Overdue', '₱' . number_format($stats['overdue'], 2), $stats['overdue_count'] . ' account(s)'],
        ['Current', '₱' . number_format($stats['current'], 2), ''],
    ];
@endphp

<div class="mb-6">
    <h1 class="text-xl font-semibold">Accounts Receivable</h1>
    <p class="text-sm text-slate-500">Customer balances and collections</p>
</div>

<div class="grid grid-cols-3 gap-4 mb-6">
    @foreach ($cards as [$label, $value, $note])
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs text-slate-500">{{ $label }}</p>
            <p class="text-2xl font-semibold mt-2">{{ $value }}</p>
            @if ($note)<p class="text-xs text-slate-400 mt-1">{{ $note }}</p>@endif
        </div>
    @endforeach
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto mb-6">
    <h2 class="font-semibold px-6 pt-5 pb-3">Customer Balances</h2>
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
                <tr class="border-b border-slate-50 hover:bg-slate-50">
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
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $badge[$ar->status] }}">{{ ucfirst($ar->status) }}</span>
                    </td>
                    <td class="px-6 py-3 text-right">
                        <a href="{{ route('receivables.show', $ar) }}" class="text-slate-600 hover:underline">
                            {{ $ar->status === 'paid' ? 'View' : 'Record Payment' }}
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-slate-400">No receivables yet. Record a sale first.</td>
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