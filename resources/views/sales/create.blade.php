@extends('layouts.admin')

@section('title', 'New Sale')

@section('content')
@php
    $input = 'w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500';
    $productData = $products->map(fn ($p) => [
        'id' => $p->id, 'name' => $p->name, 'price' => (float) $p->selling_price, 'stock' => $p->stock_qty,
    ])->values();
@endphp

<h1 class="text-xl font-semibold mb-6">New Sale</h1>

<form method="POST" action="{{ route('sales.store') }}"
      x-data="saleForm(@js($productData))">
    @csrf

    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
        <div class="grid grid-cols-4 gap-4">
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
                <label class="block text-sm font-medium mb-1">Sale Date</label>
                <input type="date" name="sale_date" value="{{ old('sale_date', now()->toDateString()) }}" class="{{ $input }}">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Payment Terms</label>
                <select name="terms_days" class="{{ $input }}">
                    <option value="30" @selected(old('terms_days', 30) == 30)>30 days</option>
                    <option value="60" @selected(old('terms_days') == 60)>60 days</option>
                    <option value="0"  @selected(old('terms_days') === '0')>Due immediately</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4 mt-4 text-sm text-slate-500">
            <p>Invoice No: <span class="font-medium text-slate-800">{{ $invoiceNo }}</span></p>
            <p>DR No: <span class="font-medium text-slate-800">{{ $drNo }}</span></p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold">Items</h2>
            <button type="button" @click="addRow()"
                    class="px-3 py-1.5 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">+ Add Item</button>
        </div>

        <table class="w-full text-sm">
            <thead class="text-xs uppercase text-slate-400 border-b border-slate-100">
                <tr>
                    <th class="py-2 text-left">Product</th>
                    <th class="py-2 text-center w-24">Stock</th>
                    <th class="py-2 text-center w-28">Qty</th>
                    <th class="py-2 text-right w-36">Unit Price</th>
                    <th class="py-2 text-right w-36">Subtotal</th>
                    <th class="w-10"></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(row, i) in rows" :key="i">
                    <tr class="border-b border-slate-50">
                        <td class="py-2 pr-3">
                            <select :name="`items[${i}][product_id]`" x-model.number="row.product_id"
                                    @change="onProductChange(i)" class="{{ $input }}">
                                <option value="">Select product...</option>
                                <template x-for="p in products" :key="p.id">
                                    <option :value="p.id" x-text="p.name"></option>
                                </template>
                            </select>
                        </td>
                        <td class="py-2 text-center text-slate-500" x-text="stockOf(row.product_id)"></td>
                        <td class="py-2 px-2">
                            <input type="number" min="1" :name="`items[${i}][quantity]`" x-model.number="row.quantity"
                                   class="{{ $input }} text-center">
                        </td>
                        <td class="py-2 px-2">
                            <input type="number" step="0.01" :name="`items[${i}][unit_price]`" x-model.number="row.unit_price"
                                   class="{{ $input }} text-right">
                        </td>
                        <td class="py-2 text-right font-medium" x-text="peso(row.quantity * row.unit_price)"></td>
                        <td class="py-2 text-right">
                            <button type="button" @click="rows.splice(i, 1)"
                                    class="text-red-500 hover:underline" x-show="rows.length > 1">✕</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>

        <div class="flex justify-end mt-4 pt-4 border-t border-slate-100">
            <p class="text-sm text-slate-500 mr-6">Total</p>
            <p class="text-xl font-semibold" x-text="peso(total())"></p>
        </div>

        <div class="flex gap-3 mt-6">
            <button class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Save Sale</button>
            <a href="{{ route('sales.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Cancel</a>
        </div>
    </div>
</form>

<script>
    function saleForm(products) {
        return {
            products,
            rows: [{ product_id: '', quantity: 1, unit_price: 0 }],
            addRow() {
                this.rows.push({ product_id: '', quantity: 1, unit_price: 0 });
            },
            find(id) {
                return this.products.find(p => p.id === id);
            },
            stockOf(id) {
                return id ? (this.find(id)?.stock ?? '—') : '—';
            },
            onProductChange(i) {
                const p = this.find(this.rows[i].product_id);
                if (p) this.rows[i].unit_price = p.price;
            },
            total() {
                return this.rows.reduce((sum, r) => sum + (r.quantity * r.unit_price || 0), 0);
            },
            peso(value) {
                return '₱' + (value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
        };
    }
</script>
@endsection