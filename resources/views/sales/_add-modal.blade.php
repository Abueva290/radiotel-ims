@php
    $input = 'w-full rounded-lg border-slate-300 text-[15px] py-2 px-3 focus:border-slate-500 focus:ring-slate-500';
    $productData = $products->map(fn ($p) => [
        'id' => $p->id, 'name' => $p->name, 'price' => (float) $p->selling_price, 'stock' => $p->stock_qty,
        'serialized' => (bool) $p->is_serialized,
        'units' => $p->is_serialized ? $p->units->map(fn ($u) => ['id' => $u->id, 'serial' => $u->serial_no])->values() : [],
        'warranty' => $p->warrantyLabel(),
    ])->values();
    $isThisForm = old('_form') === 'add-sale';
    $oldItems = $isThisForm ? array_values(old('items', [])) : [];
@endphp

<div x-data="{ open: {{ $isThisForm && $errors->any() ? 'true' : 'false' }} }"
     @open-add-sale.window="open = true"
     @keydown.escape.window="open = false">

    <div x-show="open" style="display: none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-2 sm:p-4"
         @click.self="open = false">

        <div class="bg-white rounded-2xl shadow-xl w-full max-w-5xl max-h-[92vh] overflow-y-auto">

            {{-- Header --}}
            <div class="flex items-center justify-between px-5 sm:px-8 py-5 border-b border-slate-100 sticky top-0 bg-white z-10">
                <div>
                    <h2 class="text-lg font-semibold">New Sale</h2>
                    <p class="text-xs text-slate-400">
                        Invoice No: <span class="font-medium text-slate-700">{{ $invoiceNo }}</span>
                        &nbsp;·&nbsp; DR No: <span class="font-medium text-slate-700">{{ $drNo }}</span>
                    </p>
                </div>
                <button type="button" @click="open = false"
                        class="w-9 h-9 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50">✕</button>
            </div>

            <form method="POST" action="{{ route('sales.store') }}" class="px-4 sm:px-8 py-6"
                  x-data="saleForm(@js($productData), @js($oldItems))"
                  :data-confirm="`Save this sale for ${peso(total())}?\n\nStock will be deducted for every item and a receivable will be created for the customer.`"
                  data-confirm-title="Save sale"
                  data-confirm-button="Yes, save sale">
                @csrf
                <input type="hidden" name="_form" value="add-sale">

                @if ($isThisForm && $errors->any())
                    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <ul class="list-disc pl-5 space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Sale details --}}
                <div class="border border-slate-200 rounded-xl p-4 sm:p-6 mb-5">
                    <h3 class="font-semibold text-[15px] mb-4">Sale Information</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium mb-1.5">Customer</label>
                            <select name="customer_id" class="{{ $input }} truncate">
                                <option value="">Select customer...</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1.5">Sale Date</label>
                            <input type="date" name="sale_date" value="{{ old('sale_date', now()->toDateString()) }}" class="{{ $input }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1.5">Payment Terms</label>
                            <select name="terms_days" class="{{ $input }}">
                                <option value="30" @selected(old('terms_days', 30) == 30)>30 days</option>
                                <option value="60" @selected(old('terms_days') == 60)>60 days</option>
                                <option value="0"  @selected(old('terms_days') === '0')>Due immediately</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Items --}}
                <div class="border border-slate-200 rounded-xl p-4 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-[15px]">Items</h3>
                        <button type="button" @click="addRow()"
                                class="px-3 py-1.5 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">+ Add Item</button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm table-fixed min-w-[640px]">
                            <colgroup>
                                <col>
                                <col class="w-20">
                                <col class="w-24">
                                <col class="w-32">
                                <col class="w-32">
                                <col class="w-10">
                            </colgroup>
                            <thead class="text-xs uppercase text-slate-400 border-b border-slate-100">
                                <tr>
                                    <th class="py-2 text-left">Product</th>
                                    <th class="py-2 text-center">Stock</th>
                                    <th class="py-2 text-center">Qty</th>
                                    <th class="py-2 text-right pr-2">Unit Price</th>
                                    <th class="py-2 text-right">Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <template x-for="(row, i) in rows" :key="i">
                                <tbody>
                                    <tr class="border-b border-slate-50">
                                        <td class="py-2 pr-3">
                                            <select :name="`items[${i}][product_id]`" x-model.number="row.product_id"
                                                    @change="onProductChange(i)" class="{{ $input }} truncate">
                                                <option value="">Select product...</option>
                                                <template x-for="p in products" :key="p.id">
                                                    <option :value="p.id" x-text="p.name" :selected="p.id === row.product_id"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="py-2 text-center text-slate-500" x-text="stockOf(row.product_id)"></td>
                                        <td class="py-2 px-2">
                                            <input type="number" min="1" :name="`items[${i}][quantity]`" x-model.number="row.quantity"
                                                   :readonly="isSerialized(row.product_id)"
                                                   :class="isSerialized(row.product_id) ? 'bg-slate-50' : ''"
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
                                    {{-- Serial-tracked product: pick the exact units being sold --}}
                                    <tr x-show="isSerialized(row.product_id)" class="border-b border-slate-50">
                                        <td colspan="6" class="pb-3">
                                            <div class="rounded-lg bg-slate-50 p-3">
                                                <p class="text-xs font-medium text-slate-600 mb-2">
                                                    Select the serial numbers being sold
                                                    <span class="font-normal text-slate-400" x-text="warrantyOf(row.product_id) ? '· Warranty ' + warrantyOf(row.product_id) + ' from sale date' : '· No warranty'"></span>
                                                </p>
                                                <div class="flex flex-wrap gap-2">
                                                    <template x-for="u in unitsOf(row.product_id)" :key="u.id">
                                                        <label class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-white px-2 py-1 text-xs font-mono cursor-pointer"
                                                               :class="row.unit_ids.includes(u.id) ? 'border-slate-700 ring-1 ring-slate-700' : ''">
                                                            <input type="checkbox" :name="`items[${i}][unit_ids][]`" :value="u.id"
                                                                   :checked="row.unit_ids.includes(u.id)" @change="toggleUnit(i, u.id)"
                                                                   class="rounded border-slate-300 text-slate-700 focus:ring-slate-500">
                                                            <span x-text="u.serial"></span>
                                                        </label>
                                                    </template>
                                                    <p x-show="!unitsOf(row.product_id).length" class="text-xs text-red-600">No serial-numbered units in stock. Record serial numbers in Inventory first.</p>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </template>
                        </table>
                    </div>

                    <div class="flex justify-end items-baseline gap-6 mt-4 pt-4 border-t border-slate-100">
                        <p class="text-sm text-slate-500">Total</p>
                        <p class="text-xl font-semibold" x-text="peso(total())"></p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="open = false"
                            class="px-6 py-2.5 rounded-lg bg-slate-100 text-[15px] hover:bg-slate-200">Cancel</button>
                    <button class="px-6 py-2.5 rounded-lg bg-slate-900 text-white text-[15px] hover:bg-slate-700">Save Sale</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function saleForm(products, oldItems = []) {
        const blank = () => ({ product_id: '', quantity: 1, unit_price: 0, unit_ids: [] });
        const restored = (oldItems || []).map(r => ({
            product_id: r.product_id ? Number(r.product_id) : '',
            quantity: Number(r.quantity) || 1,
            unit_price: Number(r.unit_price) || 0,
            unit_ids: (r.unit_ids || []).map(Number),
        }));

        return {
            products,
            rows: restored.length ? restored : [blank()],
            addRow() {
                this.rows.push(blank());
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
                this.rows[i].unit_ids = [];
                if (p && p.serialized) this.rows[i].quantity = 0;
            },
            isSerialized(id) {
                return !!(id && this.find(id)?.serialized);
            },
            unitsOf(id) {
                return id ? (this.find(id)?.units || []) : [];
            },
            warrantyOf(id) {
                return id ? this.find(id)?.warranty : null;
            },
            toggleUnit(i, unitId) {
                const row = this.rows[i];
                row.unit_ids = row.unit_ids.includes(unitId) ? row.unit_ids.filter(x => x !== unitId) : [...row.unit_ids, unitId];
                row.quantity = row.unit_ids.length;
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