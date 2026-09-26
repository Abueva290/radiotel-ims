@php
    $c = $customer ?? null;
    $input = 'w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500';
@endphp

<div class="grid grid-cols-2 gap-4">
    <div class="col-span-2">
        <label class="block text-sm font-medium mb-1">Customer Name</label>
        <input type="text" name="name" value="{{ old('name', $c?->name) }}"
               placeholder="e.g. Davao City Police Office" class="{{ $input }}">
        @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Contact Person</label>
        <input type="text" name="contact_person" value="{{ old('contact_person', $c?->contact_person) }}" class="{{ $input }}">
        @error('contact_person') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $c?->phone) }}" class="{{ $input }}">
        @error('phone') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="col-span-2">
        <label class="block text-sm font-medium mb-1">Address</label>
        <input type="text" name="address" value="{{ old('address', $c?->address) }}" class="{{ $input }}">
        @error('address') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>
</div>