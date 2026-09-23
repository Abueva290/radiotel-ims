@extends('layouts.admin')

@section('title', 'Repair Job')

@section('content')
@php
    $input = 'w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500';
    $badge = [
        'for_assessment' => 'bg-slate-100 text-slate-700',
        'in_progress'    => 'bg-blue-100 text-blue-700',
        'completed'      => 'bg-green-100 text-green-700',
        'released'       => 'bg-slate-200 text-slate-600',
    ];
    $locked = in_array($job->status, ['completed', 'released']);
@endphp

<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-xl font-semibold">{{ $job->job_no }}</h1>
        <p class="text-sm text-slate-500">
            {{ $job->customer->name }} · {{ $job->unit_model }} ({{ $job->units }} unit{{ $job->units > 1 ? 's' : '' }})
        </p>
    </div>
    <a href="{{ route('repairs.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Back</a>
</div>

<div class="grid grid-cols-3 gap-6">
    <div class="col-span-2 space-y-6">
        {{-- Charge breakdown --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="font-semibold mb-4">Charge Computation</h2>
            <dl class="text-sm space-y-2">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Service fee ({{ $job->units }} × ₱{{ number_format(\App\Models\RepairJob::SERVICE_FEE_PER_UNIT, 2) }})</dt>
                    <dd>₱{{ number_format($job->service_fee, 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Parts cost</dt>
                    <dd>₱{{ number_format($job->parts_cost, 2) }}</dd>
                </div>
                <div class="flex justify-between pt-2 border-t border-slate-100 text-base">
                    <dt class="font-medium">Total</dt>
                    <dd class="font-semibold">₱{{ number_format($job->total_amount, 2) }}</dd>
                </div>
            </dl>

            @if ($job->assessment_notes)
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <p class="text-xs uppercase text-slate-400 mb-1">Assessment Notes</p>
                    <p class="text-sm text-slate-600">{{ $job->assessment_notes }}</p>
                </div>
            @endif
        </div>

        {{-- Parts used --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
            <h2 class="font-semibold px-6 pt-5 pb-3">Parts Used</h2>
            <table class="w-full text-sm">
                <thead class="text-xs uppercase text-slate-400 border-y border-slate-100">
                    <tr>
                        <th class="px-6 py-3 text-left">Part</th>
                        <th class="px-6 py-3 text-center">Qty</th>
                        <th class="px-6 py-3 text-right">Unit Price</th>
                        <th class="px-6 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($job->parts as $part)
                        <tr class="border-b border-slate-50">
                            <td class="px-6 py-3">{{ $part->product->name }}</td>
                            <td class="px-6 py-3 text-center">{{ $part->quantity_used }}</td>
                            <td class="px-6 py-3 text-right">₱{{ number_format($part->unit_cost, 2) }}</td>
                            <td class="px-6 py-3 text-right font-medium">₱{{ number_format($part->subtotal, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-6 text-center text-slate-400">No parts used yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-6">
        {{-- Status --}}
        <form method="POST" action="{{ route('repairs.status', $job) }}"
              class="bg-white rounded-xl border border-slate-200 p-6">
            @csrf
            @method('PATCH')
            <h2 class="font-semibold mb-3">Job Status</h2>
            <p class="mb-3">
                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $badge[$job->status] }}">
                    {{ \App\Models\RepairJob::STATUSES[$job->status] }}
                </span>
            </p>

            <select name="status" class="{{ $input }}">
                @foreach (\App\Models\RepairJob::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected($job->status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="w-full mt-3 px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Update Status</button>
            <p class="text-xs text-slate-400 mt-2">Marking a job Completed bills the customer on 30-day terms.</p>
        </form>

        {{-- Add part --}}
        @if (! $locked)
            <form method="POST" action="{{ route('repairs.parts', $job) }}"
                  class="bg-white rounded-xl border border-slate-200 p-6">
                @csrf
                <h2 class="font-semibold mb-3">Add Part Used</h2>

                <label class="block text-sm font-medium mb-1">Part</label>
                <select name="product_id" class="{{ $input }}">
                    <option value="">Select part...</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->stock_qty }} in stock)</option>
                    @endforeach
                </select>
                @error('product_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

                <label class="block text-sm font-medium mb-1 mt-3">Quantity</label>
                <input type="number" min="1" name="quantity_used" value="{{ old('quantity_used', 1) }}" class="{{ $input }}">
                @error('quantity_used') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

                <button class="w-full mt-4 px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Add Part</button>
                <p class="text-xs text-slate-400 mt-2">Adding a part deducts it from inventory.</p>
            </form>
        @endif

        {{-- Receivable --}}
        @if ($job->receivable)
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h2 class="font-semibold mb-3">Receivable</h2>
                <dl class="text-sm space-y-2">
                    <div class="flex justify-between"><dt class="text-slate-500">Balance</dt><dd class="font-semibold">₱{{ number_format($job->receivable->balance, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Due date</dt><dd>{{ $job->receivable->due_date->format('M d, Y') }}</dd></div>
                </dl>
                <a href="{{ route('receivables.show', $job->receivable) }}"
                   class="block text-center mt-3 px-4 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Record Payment</a>
            </div>
        @endif

        {{-- Job info --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <dl class="text-sm space-y-2">
                <div class="flex justify-between"><dt class="text-slate-500">Date received</dt><dd>{{ $job->date_received->format('M d, Y') }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Assessed by</dt><dd>{{ $job->assessor?->name ?? '—' }}</dd></div>
            </dl>
        </div>
    </div>
</div>
@endsection