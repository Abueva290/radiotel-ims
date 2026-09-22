@extends('layouts.admin')

@section('title', 'Edit Product')

@section('content')
@php $input = 'w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500'; @endphp

<h1 class="text-xl font-semibold mb-6">{{ $product->name }}</h1>

<div class="grid grid-cols-3 gap-6">
    {{-- Product details --}}
    <form method="POST" action="{{ route('inventory.update', $product) }}"
          class="col-span-2 bg-white rounded-xl border border-slate-200 p-6">
        @csrf
        @method('PUT')
        <h2 class="font-semibold mb-4">Product Details</h2>
        @include('inventory._form')

        <div class="flex gap-3 mt-6">
            <button class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Save Changes</button>
            <a href="{{ route('inventory.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Back</a>
        </div>
    </form>

    {{-- Stock movement --}}
    <form method="POST" action="{{ route('inventory.movement', $product) }}"
          class="bg-white rounded-xl border border-slate-200 p-6 self-start">
        @csrf
        <h2 class="font-semibold">Record Stock Movement</h2>
        <p class="text-sm text-slate-500 mb-4">
            Current stock: <span class="font-semibold text-slate-800">{{ $product->stock_qty }}</span>
        </p>

        <label class="block text-sm font-medium mb-1">Type</label>
        <select name="movement_type" class="{{ $input }} mb-3">
            <option value="in" @selected(old('movement_type') === 'in')>Stock In (add)</option>
            <option value="out" @selected(old('movement_type') === 'out')>Stock Out (deduct)</option>
            <option value="adjustment" @selected(old('movement_type') === 'adjustment')>Adjustment (set to counted qty)</option>
        </select>

        <label class="block text-sm font-medium mb-1">Quantity</label>
        <input type="number" name="quantity" value="{{ old('quantity') }}" class="{{ $input }}">
        @error('quantity') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

        <label class="block text-sm font-medium mb-1 mt-3">Remarks</label>
        <input type="text" name="remarks" value="{{ old('remarks') }}" placeholder="e.g. Delivery from supplier" class="{{ $input }}">
        @error('remarks') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

        <button class="w-full mt-4 px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Record Movement</button>
    </form>
</div>

{{-- Movement history --}}
<div class="bg-white rounded-xl border border-slate-200 mt-6 overflow-x-auto">
    <h2 class="font-semibold px-6 pt-5 pb-3">Recent Movements</h2>
    <table class="w-full text-sm">
        <thead class="text-xs uppercase text-slate-400 border-y border-slate-100">
            <tr>
                <th class="px-6 py-3 text-left">Date</th>
                <th class="px-6 py-3 text-left">Type</th>
                <th class="px-6 py-3 text-right">Qty</th>
                <th class="px-6 py-3 text-left">Recorded By</th>
                <th class="px-6 py-3 text-left">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($movements as $m)
                <tr class="border-b border-slate-50">
                    <td class="px-6 py-3 text-slate-500">{{ $m->movement_date->format('M d, Y h:i A') }}</td>
                    <td class="px-6 py-3 capitalize">{{ $m->movement_type }}</td>
                    <td class="px-6 py-3 text-right font-medium">
                        @if ($m->movement_type === 'in') +{{ $m->quantity }}
                        @elseif ($m->movement_type === 'out') -{{ $m->quantity }}
                        @else {{ sprintf('%+d', $m->quantity) }}
                        @endif
                    </td>
                    <td class="px-6 py-3">{{ $m->recorder->name }}</td>
                    <td class="px-6 py-3 text-slate-500">{{ $m->remarks }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-6 text-center text-slate-400">No movements recorded yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection