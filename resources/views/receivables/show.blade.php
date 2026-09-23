@extends('layouts.admin')

@section('title', 'Receivable')

@section('content')
@php
    $input = 'w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500';
    $badge = [
        'unpaid'  => 'bg-red-100 text-red-700',
        'partial' => 'bg-amber-100 text-amber-700',
        'paid'    => 'bg-green-100 text-green-700',
    ];
    $reference = $receivable->sale?->invoice_no ?? $receivable->repairJob?->job_no ?? '—';
@endphp

<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-xl font-semibold">{{ $receivable->customer->name }}</h1>
        <p class="text-sm text-slate-500">{{ $reference }} · Due {{ $receivable->due_date->format('F d, Y') }}</p>
    </div>
    <a href="{{ route('receivables.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Back</a>
</div>

<div class="grid grid-cols-3 gap-6">
    <div class="col-span-2 space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <p class="text-xs text-slate-500">Amount Due</p>
                    <p class="text-xl font-semibold mt-1">₱{{ number_format($receivable->amount_due, 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Total Paid</p>
                    <p class="text-xl font-semibold mt-1">₱{{ number_format($receivable->payments->sum('amount_paid'), 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Balance</p>
                    <p class="text-xl font-semibold mt-1">₱{{ number_format($receivable->balance, 2) }}</p>
                </div>
            </div>
            <div class="flex justify-center mt-4">
                <span class="px-3 py-1 rounded-full text-xs font-medium {{ $badge[$receivable->status] }}">
                    {{ ucfirst($receivable->status) }}
                </span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
            <h2 class="font-semibold px-6 pt-5 pb-3">Payment History</h2>
            <table class="w-full text-sm">
                <thead class="text-xs uppercase text-slate-400 border-y border-slate-100">
                    <tr>
                        <th class="px-6 py-3 text-left">Receipt</th>
                        <th class="px-6 py-3 text-left">Date</th>
                        <th class="px-6 py-3 text-right">Amount</th>
                        <th class="px-6 py-3 text-left">Mode</th>
                        <th class="px-6 py-3 text-left">Received By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($receivable->payments->sortBy('payment_date') as $payment)
                        <tr class="border-b border-slate-50">
                            <td class="px-6 py-3 font-medium">{{ $payment->receipt_no }}</td>
                            <td class="px-6 py-3 text-slate-500">{{ $payment->payment_date->format('M d, Y') }}</td>
                            <td class="px-6 py-3 text-right font-medium">₱{{ number_format($payment->amount_paid, 2) }}</td>
                            <td class="px-6 py-3 text-slate-500">{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                            <td class="px-6 py-3">{{ $payment->receiver->name }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-6 text-center text-slate-400">No payments yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        @if ($receivable->status === 'paid')
            <div class="bg-white rounded-xl border border-slate-200 p-6 text-center">
                <p class="font-semibold text-green-700">Fully Paid</p>
                <p class="text-sm text-slate-500 mt-1">This account has been settled.</p>
            </div>
        @else
            <form method="POST" action="{{ route('receivables.payment', $receivable) }}"
                  class="bg-white rounded-xl border border-slate-200 p-6">
                @csrf
                <h2 class="font-semibold">Record Payment</h2>
                <p class="text-sm text-slate-500 mb-4">
                    Remaining: <span class="font-semibold text-slate-800">₱{{ number_format($receivable->balance, 2) }}</span>
                </p>

                <label class="block text-sm font-medium mb-1">Amount Paid (₱)</label>
                <input type="number" step="0.01" name="amount_paid" value="{{ old('amount_paid') }}" class="{{ $input }}">
                @error('amount_paid') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

                <label class="block text-sm font-medium mb-1 mt-3">Payment Method</label>
                <select name="payment_method" class="{{ $input }}">
                    <option value="cash" @selected(old('payment_method') === 'cash')>Cash</option>
                    <option value="check" @selected(old('payment_method') === 'check')>Check</option>
                    <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>Bank Transfer</option>
                </select>

                <label class="block text-sm font-medium mb-1 mt-3">Payment Date</label>
                <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" class="{{ $input }}">
                @error('payment_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

                <button class="w-full mt-4 px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Record Payment</button>
            </form>
        @endif
    </div>
</div>
@endsection