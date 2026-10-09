@php $input = 'w-full rounded-lg border-slate-300 text-[15px] px-3.5 py-2.5 focus:border-slate-500 focus:ring-slate-500'; @endphp

<div x-data="{ open: {{ old('_form') === 'add-product' && $errors->any() ? 'true' : 'false' }} }"
     @open-add-product.window="open = true"
     @keydown.escape.window="open = false">

    <div x-show="open" style="display: none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-2 sm:p-4"
         @click.self="open = false">

        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

            {{-- Header --}}
            <div class="flex items-center justify-between px-5 sm:px-8 py-5 border-b border-slate-100">
                <h2 class="text-lg font-semibold">Add Product</h2>
                <button type="button" @click="open = false"
                        class="w-9 h-9 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50">✕</button>
            </div>

            <form method="POST" action="{{ route('inventory.store') }}" class="px-4 sm:px-8 py-6">
                @csrf
                <input type="hidden" name="_form" value="add-product">

                <div class="border border-slate-200 rounded-xl p-4 sm:p-6">
                    <h3 class="font-semibold text-[15px]">Product Information</h3>
                    <p class="text-xs text-slate-400 mb-5">Initial stock is logged as a stock-in movement.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium mb-1.5">Product Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="{{ $input }}">
                            @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="min-w-0">
                            <label class="block text-sm font-medium mb-1.5">Brand</label>
                            <input type="text" name="brand" value="{{ old('brand') }}" class="{{ $input }}">
                            @error('brand') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="min-w-0">
                            <label class="block text-sm font-medium mb-1.5">Category</label>
                            <select name="category" class="{{ $input }}">
                                @foreach (\App\Models\Product::CATEGORIES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('category') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="min-w-0">
                            <label class="block text-sm font-medium mb-1.5">Unit Cost (₱)</label>
                            <input type="number" step="0.01" min="0" name="unit_cost" value="{{ old('unit_cost') }}" class="{{ $input }}">
                            @error('unit_cost') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="min-w-0">
                            <label class="block text-sm font-medium mb-1.5">Selling Price (₱)</label>
                            <input type="number" step="0.01" min="0" name="selling_price" value="{{ old('selling_price') }}" class="{{ $input }}">
                            @error('selling_price') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="min-w-0">
                            <label class="block text-sm font-medium mb-1.5">Reorder Level</label>
                            <input type="number" min="0" name="reorder_level" value="{{ old('reorder_level', 0) }}" class="{{ $input }}">
                            @error('reorder_level') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="min-w-0">
                            <label class="block text-sm font-medium mb-1.5">Initial Stock</label>
                            <input type="number" min="0" name="stock_qty" value="{{ old('stock_qty', 0) }}" class="{{ $input }}">
                            @error('stock_qty') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="open = false"
                            class="px-6 py-2.5 rounded-lg bg-slate-100 text-[15px] hover:bg-slate-200">Cancel</button>
                    <button class="px-6 py-2.5 rounded-lg bg-slate-900 text-white text-[15px] hover:bg-slate-700">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>