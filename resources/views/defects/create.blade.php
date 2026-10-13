@extends('layouts.admin')

@section('title', 'Report Factory Defect')

@section('content')
@php
    $input = 'w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500';
    $productData = $products->mapWithKeys(fn ($p) => [$p->id => ['serialized' => (bool) $p->is_serialized, 'stock' => $p->stock_qty]]);
@endphp

<div class="max-w-3xl">
    <h1 class="text-xl font-semibold">Report a Possible Factory Defect</h1>
    <p class="text-sm text-slate-500 mb-6">
        This only records the report. The Technical Head or Operational Manager inspects the item and decides whether it is a factory defect,
        customer-caused damage, normal wear and tear, or another issue.
    </p>

    <form method="POST" action="{{ route('defects.store') }}" class="bg-white rounded-xl border border-slate-200 p-6"
          x-data="{ source: '{{ old('source', $prefill['source']) }}', product: '{{ old('product_id', $prefill['product_id']) }}', products: @js($productData),
                    get serialized() { return !!(this.products[this.product] || {}).serialized } }"
          data-confirm="Submit this defect report? If the item is from our stock and has a serial number, it will be held from sale until it is inspected."
          data-confirm-title="Report factory defect"
          data-confirm-button="Yes, submit report">
        @csrf

        <fieldset class="mb-5">
            <legend class="block text-sm font-medium mb-2">Where is the item from?</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="flex items-start gap-3 rounded-lg border p-3 cursor-pointer" :class="source === 'inventory' ? 'border-slate-700 ring-1 ring-slate-700' : 'border-slate-200'">
                    <input type="radio" name="source" value="inventory" x-model="source" class="mt-0.5 text-slate-700 focus:ring-slate-500">
                    <span><span class="font-medium text-sm">Our stock</span><span class="block text-xs text-slate-500">Found in inventory, e.g. on delivery or while checking</span></span>
                </label>
                <label class="flex items-start gap-3 rounded-lg border p-3 cursor-pointer" :class="source === 'customer_unit' ? 'border-slate-700 ring-1 ring-slate-700' : 'border-slate-200'">
                    <input type="radio" name="source" value="customer_unit" x-model="source" class="mt-0.5 text-slate-700 focus:ring-slate-500">
                    <span><span class="font-medium text-sm">Customer unit</span><span class="block text-xs text-slate-500">Brought in under a repair job</span></span>
                </label>
            </div>
            @error('source') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </fieldset>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium mb-1">Product</label>
                <select name="product_id" x-model="product" required class="{{ $input }}">
                    <option value="">Select product...</option>
                    @foreach ($products as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}{{ $p->brand ? ' · ' . $p->brand : '' }}{{ $p->is_serialized ? ' (serial-tracked)' : '' }}</option>
                    @endforeach
                </select>
                @error('product_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div x-show="source === 'customer_unit'" class="sm:col-span-2">
                <label class="block text-sm font-medium mb-1">Repair Job</label>
                <select name="repair_job_id" class="{{ $input }}" :disabled="source !== 'customer_unit'">
                    <option value="">Select repair job...</option>
                    @foreach ($jobs as $j)
                        <option value="{{ $j->id }}" @selected(old('repair_job_id', $prefill['repair_job_id']) == $j->id)>
                            {{ $j->job_no }} · {{ $j->customer->name }} · {{ $j->unit_model }}
                        </option>
                    @endforeach
                </select>
                @error('repair_job_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">
                    Serial Number
                    <span class="font-normal text-slate-400" x-text="serialized && source === 'inventory' ? '(required)' : '(if any)'"></span>
                </label>
                <input type="text" name="serial_no" value="{{ old('serial_no', $prefill['serial_no']) }}" class="{{ $input }} font-mono uppercase"
                       :required="serialized && source === 'inventory'">
                @error('serial_no') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Quantity</label>
                <input type="number" name="quantity" min="1" value="{{ old('quantity', 1) }}" class="{{ $input }}"
                       :readonly="serialized" :class="serialized ? 'bg-slate-50' : ''">
                <p class="text-xs text-slate-400 mt-1" x-show="serialized">One report per serial-numbered unit.</p>
                @error('quantity') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Date Discovered</label>
                <input type="date" name="date_discovered" max="{{ now()->toDateString() }}" value="{{ old('date_discovered', now()->toDateString()) }}" required class="{{ $input }}">
                @error('date_discovered') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Supplier <span class="font-normal text-slate-400">(if known)</span></label>
                <select name="supplier_id" class="{{ $input }}">
                    <option value="">Not specified</option>
                    @foreach ($suppliers as $s)
                        <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium mb-1">Defect Description</label>
                <textarea name="description" rows="4" required minlength="5" maxlength="2000"
                          placeholder="What is wrong, how it was noticed, and the condition of the item (packaging, physical damage, etc.)"
                          class="{{ $input }}">{{ old('description') }}</textarea>
                @error('description') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <p class="text-xs text-slate-400 mt-4">Reported by: {{ auth()->user()->name }} · recorded automatically</p>

        <div class="flex gap-3 mt-6">
            <button class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Submit Report</button>
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('defects.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Cancel</a>
        </div>
    </form>
</div>
@endsection