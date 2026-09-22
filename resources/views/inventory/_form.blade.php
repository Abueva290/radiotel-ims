@php
    $p = $product ?? null;
    $input = 'w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500';
@endphp

<div class="grid grid-cols-2 gap-4">
    <div class="col-span-2">
        <label class="block text-sm font-medium mb-1">Product Name</label>
        <input type="text" name="name" value="{{ old('name', $p?->name) }}" class="{{ $input }}">
        @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Brand</label>
        <input type="text" name="brand" value="{{ old('brand', $p?->brand) }}" class="{{ $input }}">
        @error('brand') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Category</label>
        <select name="category" class="{{ $input }}">
            @foreach (\App\Models\Product::CATEGORIES as $value => $label)
                <option value="{{ $value }}" @selected(old('category', $p?->category) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('category') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Unit Cost (₱)</label>
        <input type="number" step="0.01" name="unit_cost" value="{{ old('unit_cost', $p?->unit_cost) }}" class="{{ $input }}">
        @error('unit_cost') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Selling Price (₱)</label>
        <input type="number" step="0.01" name="selling_price" value="{{ old('selling_price', $p?->selling_price) }}" class="{{ $input }}">
        @error('selling_price') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Reorder Level</label>
        <input type="number" name="reorder_level" value="{{ old('reorder_level', $p?->reorder_level ?? 0) }}" class="{{ $input }}">
        @error('reorder_level') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    @if (! $p)
        <div>
            <label class="block text-sm font-medium mb-1">Initial Stock</label>
            <input type="number" name="stock_qty" value="{{ old('stock_qty', 0) }}" class="{{ $input }}">
            @error('stock_qty') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
    @else
        <div>
            <label class="block text-sm font-medium mb-1">Status</label>
            <select name="status" class="{{ $input }}">
                <option value="active" @selected(old('status', $p->status) === 'active')>Active</option>
                <option value="discontinued" @selected(old('status', $p->status) === 'discontinued')>Discontinued</option>
            </select>
        </div>
    @endif
</div>