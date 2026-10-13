@extends('layouts.admin')

@section('title', 'Inventory')

@section('content')
@php
    // Summary cards double as one-click filters
    $cards = [
        ['Total SKUs',      $stats['total'], null,       'All products'],
        ['Low Stock',       $stats['low'],   'low_only', 'At or below reorder level'],
        ['Out of Stock',    $stats['out'],   'out',      'Zero on hand'],
        ['Inventory Value', '₱' . number_format($stats['value'], 2), null, 'At unit cost'],
    ];
    $chips = ['' => 'All products'] + \App\Http\Controllers\ProductController::STOCK_FILTERS;
@endphp

<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-xl font-semibold">Inventory Management</h1>
        <p class="text-sm text-slate-500">Stock levels, movements, and low-stock alerts</p>
    </div>
    <button type="button"
            onclick="window.dispatchEvent(new CustomEvent('open-add-product'))"
            class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">+ Add Product</button>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach ($cards as [$label, $value, $filter, $note])
        @php $selected = $filter && $stock === $filter; @endphp
        <a href="{{ $selected || ! $filter ? route('inventory.index') : route('inventory.index', ['stock' => $filter]) }}"
           class="kpi-card {{ $selected ? 'kpi-card-active' : '' }}"
           aria-label="{{ $label }}: {{ $value }}. {{ $filter ? 'Show these products' : 'Show all products' }}">
            <p class="text-xs text-slate-500">{{ $label }}</p>
            <p class="text-2xl font-semibold mt-2">{{ $value }}</p>
            <p class="text-xs text-slate-400 mt-1 flex items-center justify-between gap-2">
                <span class="truncate">{{ $note }}</span><i class="ti ti-arrow-right kpi-arrow"></i>
            </p>
        </a>
    @endforeach
</div>

<form method="GET" class="flex flex-col sm:flex-row flex-wrap gap-2 mb-4">
    <div class="relative w-full sm:w-80">
        <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
        <input type="search" name="search" value="{{ $search }}" placeholder="Search product, brand, or serial no..."
               class="w-full pl-9 rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
    </div>
    <select name="stock" onchange="this.form.submit()" aria-label="Stock filter"
            class="w-full sm:w-64 rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
        @foreach ($chips as $value => $label)
            <option value="{{ $value }}" @selected(($stock ?? '') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <button class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Search</button>
    @if ($search !== '' || $stock)
        <a href="{{ route('inventory.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-sm text-center hover:bg-slate-200">Reset</a>
    @endif
</form>

@if ($serialMatch)
    <div class="mb-4 flex flex-wrap items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm">
        <i class="ti ti-barcode text-lg text-slate-400"></i>
        Serial <span class="font-mono font-medium">{{ $serialMatch->serial_no }}</span> belongs to
        <a href="{{ route('inventory.edit', $serialMatch->product) }}" class="font-medium underline">{{ $serialMatch->product->name }}</a>
        <x-badge type="unit" :value="$serialMatch->status" />
        <x-badge type="warranty" :value="$serialMatch->warrantyStatus()" />
    </div>
@endif

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
                <th class="px-4 py-3 text-center">Warranty</th>
                <th class="px-4 py-3 text-center">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
                <tr class="border-b border-slate-50 hover:bg-slate-50">
                    <td class="px-4 py-3 text-slate-400">P{{ str_pad($product->id, 3, '0', STR_PAD_LEFT) }}</td>
                    <td class="px-4 py-3 font-medium">
                        {{ $product->name }}
                        @if ($product->is_serialized)
                            <span class="block text-xs font-normal text-slate-400"><i class="ti ti-barcode"></i> Serial-tracked · {{ $product->available_units_count }} unit(s) with serial</span>
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
                    <td class="px-4 py-3 text-center text-slate-500 whitespace-nowrap">{{ $product->warrantyLabel() ?? '—' }}</td>
                    <td class="px-4 py-3 text-center">
                        <x-badge type="stock" :value="$product->stockStatus()" />
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('inventory.edit', $product) }}" class="btn-action">Edit</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="px-4 py-8 text-center text-slate-400">
                        {{ $search !== '' || $stock ? 'No products match this search or filter.' : 'No products yet.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('inventory._add-modal')
@endsection