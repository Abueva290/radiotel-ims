@extends('layouts.admin')

@section('title', 'Inventory')

@section('content')
@php
    $badge = [
        'In Stock'     => 'bg-green-100 text-green-700',
        'Low Stock'    => 'bg-amber-100 text-amber-700',
        'Out of Stock' => 'bg-red-100 text-red-700',
    ];
    $cards = [
        ['Total SKUs', $stats['total']],
        ['Low Stock', $stats['low']],
        ['Out of Stock', $stats['out']],
        ['Inventory Value', '₱' . number_format($stats['value'], 2)],
    ];
@endphp

<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-xl font-semibold">Inventory Management</h1>
        <p class="text-sm text-slate-500">Stock levels, movements, and low-stock alerts</p>
    </div>
    <a href="{{ route('inventory.create') }}"
       class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">+ Add Product</a>
</div>

<div class="grid grid-cols-4 gap-4 mb-6">
    @foreach ($cards as [$label, $value])
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs text-slate-500">{{ $label }}</p>
            <p class="text-2xl font-semibold mt-2">{{ $value }}</p>
        </div>
    @endforeach
</div>

<form method="GET" class="mb-4">
    <input type="text" name="search" value="{{ $search }}" placeholder="Search product or brand..."
           class="w-72 rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
</form>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-xs uppercase text-slate-400 border-b border-slate-100">
            <tr>
                <th class="px-4 py-3 text-left">ID</th>
                <th class="px-4 py-3 text-left">Product Name</th>
                <th class="px-4 py-3 text-left">Brand</th>
                <th class="px-4 py-3 text-left">Category</th>
                <th class="px-4 py-3 text-center">Stock</th>
                <th class="px-4 py-3 text-center">Reorder</th>
                <th class="px-4 py-3 text-right">Unit Cost</th>
                <th class="px-4 py-3 text-right">Selling Price</th>
                <th class="px-4 py-3 text-center">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
                @php $status = $product->stockStatus(); @endphp
                <tr class="border-b border-slate-50 hover:bg-slate-50">
                    <td class="px-4 py-3 text-slate-400">P{{ str_pad($product->id, 3, '0', STR_PAD_LEFT) }}</td>
                    <td class="px-4 py-3 font-medium">
                        {{ $product->name }}
                        @if ($product->status === 'discontinued')
                            <span class="text-xs text-slate-400">(discontinued)</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $product->brand }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ \App\Models\Product::CATEGORIES[$product->category] }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-0.5 rounded bg-slate-100">{{ $product->stock_qty }}</span>
                    </td>
                    <td class="px-4 py-3 text-center text-slate-500">{{ $product->reorder_level }}</td>
                    <td class="px-4 py-3 text-right">₱{{ number_format($product->unit_cost, 2) }}</td>
                    <td class="px-4 py-3 text-right font-medium">₱{{ number_format($product->selling_price, 2) }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $badge[$status] }}">{{ $status }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('inventory.edit', $product) }}" class="text-slate-600 hover:underline">Edit</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="px-4 py-8 text-center text-slate-400">No products found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection