@extends('layouts.admin')

@section('title', 'New Repair Job')

@section('content')
@php $input = 'w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500'; @endphp

<div class="max-w-2xl">
    <h1 class="text-xl font-semibold mb-6">New Repair Job</h1>

    <form method="POST" action="{{ route('repairs.store') }}"
          class="bg-white rounded-xl border border-slate-200 p-6"
          x-data="{ units: {{ old('units', 1) }} }">
        @csrf

        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-sm font-medium mb-1">Customer</label>
                <select name="customer_id" class="{{ $input }}">
                    <option value="">Select customer...</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Unit Model</label>
                <input type="text" name="unit_model" value="{{ old('unit_model') }}"
                       placeholder="e.g. Motorola DP4801e" class="{{ $input }}">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Number of Units</label>
                <input type="number" min="1" name="units" x-model.number="units" class="{{ $input }}">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Date Received</label>
                <input type="date" name="date_received" value="{{ old('date_received', now()->toDateString()) }}" class="{{ $input }}">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Job No.</label>
                <input type="text" value="{{ $jobNo }}" disabled class="{{ $input }} bg-slate-50 text-slate-500">
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-medium mb-1">Assessment Notes</label>
                <textarea name="assessment_notes" rows="3" placeholder="Findings, required repair, parts needed..."
                          class="{{ $input }}">{{ old('assessment_notes') }}</textarea>
            </div>
        </div>

        <div class="flex justify-between items-baseline mt-6 pt-4 border-t border-slate-100">
            <p class="text-sm text-slate-500">
                Service fee: <span x-text="units"></span> unit(s) × ₱{{ number_format($fee, 2) }}
            </p>
            <p class="text-xl font-semibold"
               x-text="'₱' + (units * {{ $fee }}).toLocaleString('en-PH', { minimumFractionDigits: 2 })"></p>
        </div>
        <p class="text-xs text-slate-400 mt-1">Parts used are added after the job is created.</p>

        <div class="flex gap-3 mt-6">
            <button class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Create Job</button>
            <a href="{{ route('repairs.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Cancel</a>
        </div>
    </form>
</div>
@endsection