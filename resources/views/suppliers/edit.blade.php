@extends('layouts.admin')

@section('title', 'Edit Supplier')

@section('content')
@php $input = 'w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500'; @endphp

<div class="max-w-2xl">
    <h1 class="text-xl font-semibold mb-1">{{ $supplier->name }}</h1>
    <p class="text-sm text-slate-500 mb-6">
        {{ $supplier->invoices_count }} invoice(s)
        @if ($supplier->isArchived())
            <span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-700">Archived</span>
        @endif
    </p>

    <form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="bg-white rounded-xl border border-slate-200 p-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-sm font-medium mb-1">Supplier Name</label>
                <input type="text" name="name" value="{{ old('name', $supplier->name) }}" class="{{ $input }}">
                @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Contact Person</label>
                <input type="text" name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}" class="{{ $input }}">
                @error('contact_person') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $supplier->phone) }}" class="{{ $input }}">
                @error('phone') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Credit Terms (days)</label>
                <input type="number" name="credit_terms_days" min="0" max="365"
                       value="{{ old('credit_terms_days', $supplier->credit_terms_days) }}" class="{{ $input }}">
                <p class="text-xs text-slate-400 mt-1">Applies to invoices recorded from now on. Use 0 for cash suppliers.</p>
                @error('credit_terms_days') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <button class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Save Changes</button>
            <a href="{{ route('suppliers.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Back</a>
        </div>
    </form>
</div>
@endsection