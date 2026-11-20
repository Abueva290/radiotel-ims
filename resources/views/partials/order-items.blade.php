{{--
    Item rows shared by the Customer Order and Purchase Order forms.
    $rows: [['product_id' => 1, 'quantity' => 2, 'price' => 1500.00], ...]
--}}
@php
    $input = 'w-full rounded-lg border-slate-300 text-sm py-2 px-3 focus:border-slate-500 focus:ring-slate-500';
    $productData = $products->map(fn ($p) => [
        'id' => $p->id, 'name' => $p->name . ($p->brand ? " ({$p->brand})" : ''),
        'price' => (float) $p->{$priceAttr}, 'stock' => $p->stock_qty, 'reorder' => $p->reorder_level,
    ])->values();
    $oldRows = old('items') ? collect(old('items'))->map(fn ($r) => [
        'product_id' => $r['product_id'] ?? '', 'quantity' => $r['quantity'] ?? 1, 'price' => $r[$priceField] ?? 0,
    ])->values()->all() : $rows;
@endphp

<div x-data="orderItems(@js($productData), @js($oldRows))" class="bg-white rounded-xl border border-slate-200 p-4 sm:p-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold">Items</h2>
        <button type="button" @click="addRow()" class="px-3 py-1.5 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">+ Add Item</button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm table-fixed min-w-[640px]">
            <colgroup>
                <col><col class="w-24"><col class="w-24"><col class="w-36"><col class="w-32"><col class="w-10">
            </colgroup>
            <thead class="text-xs uppercase text-slate-400 border-b border-slate-100">
                <tr>
                    <th class="py-2 text-left">Product</th>
                    <th class="py-2 text-center">In Stock</th>
                    <th class="py-2 text-center">Qty</th>
                    <th class="py-2 text-right pr-2">{{ $priceLabel }}</th>
                    <th class="py-2 text-right">Subtotal</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(row, i) in rows" :key="row.key">
                    <tr class="border-b border-slate-50">
                        <td class="py-2 pr-3">
                            <select :name="`items[${i}][product_id]`" x-model.number="row.product_id" @change="onProductChange(i)" class="{{ $input }} truncate">
                                <option value="">Select product...</option>
                                <template x-for="p in products" :key="p.id">
                                    <option :value="p.id" x-text="p.name" :selected="p.id === row.product_id"></option>
                                </template>
                            </select>
                        </td>
                        <td class="py-2 text-center" :class="lowStock(row.product_id) ? 'text-amber-600 font-medium' : 'text-slate-500'" x-text="stockOf(row.product_id)"></td>
                        <td class="py-2 px-2">
                            <input type="number" min="1" :name="`items[${i}][quantity]`" x-model.number="row.quantity" class="{{ $input }} text-center">
                        </td>
                        <td class="py-2 px-2">
                            <input type="number" step="0.01" min="0" :name="`items[${i}][{{ $priceField }}]`" x-model.number="row.price" class="{{ $input }} text-right">
                        </td>
                        <td class="py-2 text-right font-medium" x-text="peso(row.quantity * row.price)"></td>
                        <td class="py-2 text-right">
                            <button type="button" @click="rows.splice(i, 1)" x-show="rows.length > 1" class="text-red-500 hover:underline" aria-label="Remove item">✕</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <div class="flex justify-end items-baseline gap-6 mt-4 pt-4 border-t border-slate-100">
        <p class="text-sm text-slate-500">Total</p>
        <p class="text-xl font-semibold" x-text="peso(total())"></p>
    </div>
</div>

@once
<script>
    function orderItems(products, initial) {
        let seq = 0;
        const make = (r = {}) => ({ key: ++seq, product_id: r.product_id ? Number(r.product_id) : '', quantity: Number(r.quantity) || 1, price: Number(r.price) || 0 });

        return {
            products,
            rows: (initial && initial.length ? initial : [{}]).map(make),
            addRow() { this.rows.push(make()); },
            find(id) { return this.products.find(p => p.id === id); },
            stockOf(id) { return id ? (this.find(id)?.stock ?? '—') : '—'; },
            lowStock(id) { const p = id && this.find(id); return p ? p.stock <= p.reorder : false; },
            onProductChange(i) { const p = this.find(this.rows[i].product_id); if (p) this.rows[i].price = p.price; },
            total() { return this.rows.reduce((sum, r) => sum + (r.quantity * r.price || 0), 0); },
            peso(v) { return '₱' + (v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        };
    }
</script>
@endonce