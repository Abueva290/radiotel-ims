@extends('layouts.admin')

@section('title', 'Suppliers')

@section('content')
@php $input = 'w-full rounded-lg border-slate-300 text-[15px] px-3.5 py-2.5 focus:border-slate-500 focus:ring-slate-500'; @endphp

<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-xl font-semibold">Supplier Management</h1>
        <p class="text-sm text-slate-500">Suppliers that Radiotel buys products from, and their credit terms</p>
    </div>
    <button type="button"
            onclick="window.dispatchEvent(new CustomEvent('open-add-supplier'))"
            class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">+ Add Supplier</button>
</div>

<div class="flex items-center justify-between mb-4">
    <div class="flex gap-2">
        <a href="{{ route('suppliers.index') }}"
           class="px-3 py-1.5 rounded-lg text-sm {{ ! $showArchived ? 'bg-slate-700 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
            Active ({{ $activeCount }})
        </a>
        <a href="{{ route('suppliers.index', ['archived' => 1]) }}"
           class="px-3 py-1.5 rounded-lg text-sm {{ $showArchived ? 'bg-slate-700 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
            Archived ({{ $archivedCount }})
        </a>
    </div>

    <form method="GET">
        @if ($showArchived)<input type="hidden" name="archived" value="1">@endif
        <input type="text" name="search" value="{{ $search }}" placeholder="Search supplier..."
               class="w-64 rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
    </form>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-xs uppercase text-slate-400 border-b border-slate-100">
            <tr>
                <th class="px-4 py-3 text-left">ID</th>
                <th class="px-4 py-3 text-left">Supplier</th>
                <th class="px-4 py-3 text-left">Contact Person</th>
                <th class="px-4 py-3 text-left">Phone</th>
                <th class="px-4 py-3 text-center">Credit Terms</th>
                <th class="px-4 py-3 text-center">Invoices</th>
                <th class="px-4 py-3 text-right">Unpaid Balance</th>
                <th class="px-4 py-3 text-right"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($suppliers as $supplier)
                <tr class="border-b border-slate-50 hover:bg-slate-50">
                    <td class="px-4 py-3 text-slate-400">S{{ str_pad($supplier->id, 3, '0', STR_PAD_LEFT) }}</td>
                    <td class="px-4 py-3 font-medium">{{ $supplier->name }}</td>
                    <td class="px-4 py-3">{{ $supplier->contact_person ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ $supplier->phone ?? '—' }}</td>
                    <td class="px-4 py-3 text-center">
                        {{ $supplier->credit_terms_days > 0 ? $supplier->credit_terms_days . ' days' : 'Cash' }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if ($supplier->invoices_count > 0)
                            <a href="{{ route('payables.index', ['supplier' => $supplier->id]) }}" class="underline hover:text-slate-900">{{ $supplier->invoices_count }}</a>
                        @else
                            0
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-medium">₱{{ number_format($supplier->outstanding ?? 0, 2) }}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="inline-flex gap-2">
                            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn-action">Edit</a>

                            <form method="POST" action="{{ route('suppliers.archive', $supplier) }}"
                                  onsubmit="return confirm(@js(($showArchived ? 'Restore ' : 'Archive ') . $supplier->name . '?'))">
                                @csrf
                                @method('PATCH')
                                <button class="{{ $showArchived ? 'btn-action-success' : 'btn-action-warning' }}">
                                    {{ $showArchived ? 'Restore' : 'Archive' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                        {{ $showArchived ? 'No archived suppliers.' : 'No suppliers found.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<p class="text-xs text-slate-400 mt-3">
    Archiving hides a supplier from new invoices. Past invoices and payments are kept.
    A supplier with an unpaid balance cannot be archived.
</p>

{{-- Add Supplier modal --}}
<div x-data="{ open: {{ old('_form') === 'add-supplier' && $errors->any() ? 'true' : 'false' }} }"
     @open-add-supplier.window="open = true"
     @keydown.escape.window="open = false">

    <div x-show="open" style="display: none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
         @click.self="open = false">

        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-8 py-5 border-b border-slate-100">
                <h2 class="text-lg font-semibold">Add Supplier</h2>
                <button type="button" @click="open = false"
                        class="w-9 h-9 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50">✕</button>
            </div>

            <form method="POST" action="{{ route('suppliers.store') }}" class="px-8 py-6">
                @csrf
                <input type="hidden" name="_form" value="add-supplier">

                <div class="border border-slate-200 rounded-xl p-6">
                    <h3 class="font-semibold text-[15px]">Supplier Information</h3>
                    <p class="text-xs text-slate-400 mb-5">Used when recording supplier invoices in Payables.</p>

                    <div class="grid grid-cols-2 gap-5">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium mb-1.5">Supplier Name</label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                   placeholder="e.g. Motorola Solutions PH" class="{{ $input }}">
                            @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-sm font-medium mb-1.5">Contact Person</label>
                            <input type="text" name="contact_person" value="{{ old('contact_person') }}" class="{{ $input }}">
                            @error('contact_person') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-sm font-medium mb-1.5">Phone</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" class="{{ $input }}">
                            @error('phone') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-sm font-medium mb-1.5">Credit Terms (days)</label>
                            <input type="number" name="credit_terms_days" value="{{ old('credit_terms_days', 60) }}"
                                   min="0" max="365" class="{{ $input }}">
                            <p class="text-xs text-slate-400 mt-1">Days before an invoice is due. Use 0 for cash suppliers.</p>
                            @error('credit_terms_days') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="open = false"
                            class="px-6 py-2.5 rounded-lg bg-slate-100 text-[15px] hover:bg-slate-200">Cancel</button>
                    <button class="px-6 py-2.5 rounded-lg bg-slate-900 text-white text-[15px] hover:bg-slate-700">Save Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection