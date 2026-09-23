@extends('layouts.admin')

@section('title', 'Record Invoice')

@section('content')
@php
    $input = 'w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500';
    $terms = $suppliers->pluck('credit_terms_days', 'id');
@endphp

<div class="max-w-2xl">
    <h1 class="text-xl font-semibold mb-6">Record Supplier Invoice</h1>

    <form method="POST" action="{{ route('payables.store') }}"
          class="bg-white rounded-xl border border-slate-200 p-6"
          x-data="{
              terms: @js($terms),
              supplier: '{{ old('supplier_id') }}',
              date: '{{ old('invoice_date', now()->toDateString()) }}',
              dueDate() {
                  if (!this.supplier || !this.date) return '—';
                                    const [y, m, day] = this.date.split('-').map(Number);
                  const d = new Date(y, m - 1, day);
                  d.setDate(d.getDate() + Number(this.terms[this.supplier] ?? 0));
                  return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
              },
              termDays() {
                  return this.supplier ? (this.terms[this.supplier] ?? 0) + ' days' : '—';
              }
          }">
        @csrf

        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-sm font-medium mb-1">Supplier</label>
                <select name="supplier_id" x-model="supplier" class="{{ $input }}">
                    <option value="">Select supplier...</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Supplier Invoice No.</label>
                <input type="text" name="invoice_no" value="{{ old('invoice_no') }}"
                       placeholder="e.g. MSP-2026-1183" class="{{ $input }}">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Amount (₱)</label>
                <input type="number" step="0.01" name="amount" value="{{ old('amount') }}" class="{{ $input }}">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Invoice Date</label>
                <input type="date" name="invoice_date" x-model="date" class="{{ $input }}">
            </div>
        </div>

        <div class="flex justify-between mt-6 pt-4 border-t border-slate-100 text-sm">
            <p class="text-slate-500">Credit terms: <span class="font-medium text-slate-800" x-text="termDays()"></span></p>
            <p class="text-slate-500">Due date: <span class="font-medium text-slate-800" x-text="dueDate()"></span></p>
        </div>

        <div class="flex gap-3 mt-6">
            <button class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Save Invoice</button>
            <a href="{{ route('payables.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Cancel</a>
        </div>
    </form>
</div>
@endsection