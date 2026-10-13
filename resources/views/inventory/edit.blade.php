@extends('layouts.admin')

@section('title', 'Edit Product')

@section('content')
@php
    $input = 'w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500';
    $unregistered = $product->unregisteredQty();
@endphp

<div class="flex flex-wrap items-start justify-between gap-3 mb-6">
    <div>
        <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-xl font-semibold">{{ $product->name }}</h1>
            <x-badge type="stock" :value="$product->stockStatus()" size="md" />
            @if ($product->is_serialized)
                <x-badge tone="gray" size="md"><i class="ti ti-barcode"></i> Serial-tracked</x-badge>
            @endif
        </div>
        <p class="text-sm text-slate-500 mt-1">
            P{{ str_pad($product->id, 3, '0', STR_PAD_LEFT) }} · {{ \App\Models\Product::CATEGORIES[$product->category] }}
            · Warranty: {{ $product->warrantyLabel() ? $product->warrantyLabel() . ' from date of sale' : 'none recorded' }}
        </p>
    </div>
    @if (\Illuminate\Support\Facades\Route::has('defects.create'))
        <a href="{{ route('defects.create', ['product_id' => $product->id]) }}" class="px-4 py-2 rounded-lg bg-white border border-slate-200 text-sm hover:bg-slate-50">
            <i class="ti ti-alert-octagon"></i> Report Factory Defect
        </a>
    @endif
</div>

@if ($openDefects->isNotEmpty() && \Illuminate\Support\Facades\Route::has('defects.show'))
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <i class="ti ti-alert-triangle"></i>
        {{ $openDefects->count() }} open defect report(s) for this product:
        @foreach ($openDefects as $d)
            <a href="{{ route('defects.show', $d) }}" class="font-medium underline">{{ $d->defect_no }}</a> ({{ \App\Support\Badge::label('defect', $d->status) }}){{ $loop->last ? '' : ',' }}
        @endforeach
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Product details --}}
    <form method="POST" action="{{ route('inventory.update', $product) }}"
          class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-6">
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
          class="bg-white rounded-xl border border-slate-200 p-6 self-start"
          x-data="{
              type: '{{ old('movement_type', 'in') }}',
              qty: '{{ old('quantity') }}',
              picked: @js(array_map('intval', old('unit_ids', []))),
              serialized: {{ $product->is_serialized ? 'true' : 'false' }},
              current: {{ $product->stock_qty }},
              get message() {
                  const q = Number(this.qty || 0);
                  if (this.type === 'in') return `Add ${q} unit(s) to {{ addslashes($product->name) }}?\n\nStock will change from ${this.current} to ${this.current + q}.`;
                  if (this.type === 'out') return `Remove ${q} unit(s) from {{ addslashes($product->name) }}?\n\nStock will change from ${this.current} to ${this.current - q}.`;
                  return `Set the stock of {{ addslashes($product->name) }} to the counted quantity of ${q}?\n\nStock will change from ${this.current} to ${q} (${q - this.current >= 0 ? '+' : ''}${q - this.current}).`;
              },
          }"
          x-effect="if (serialized && type === 'out') qty = picked.length"
          :data-confirm="message"
          data-confirm-title="Record stock movement"
          :data-confirm-tone="type === 'in' ? 'primary' : 'warning'"
          data-confirm-button="Yes, record movement">
        @csrf
        <h2 class="font-semibold">Record Stock Movement</h2>
        <p class="text-sm text-slate-500 mb-4">
            Current stock: <span class="font-semibold text-slate-800">{{ $product->stock_qty }}</span>
        </p>

        <label class="block text-sm font-medium mb-1">Type</label>
        <select name="movement_type" x-model="type" class="{{ $input }} mb-3">
            <option value="in">Stock In (add)</option>
            <option value="out">Stock Out (deduct)</option>
            @unless ($product->is_serialized)
                <option value="adjustment">Adjustment (set to counted qty)</option>
            @endunless
        </select>
        @error('movement_type') <p class="text-xs text-red-600 -mt-2 mb-2">{{ $message }}</p> @enderror

        <label class="block text-sm font-medium mb-1">Quantity</label>
        <input type="number" name="quantity" x-model="qty" min="0" required class="{{ $input }}"
               :readonly="serialized && type === 'out'" :class="serialized && type === 'out' ? 'bg-slate-50' : ''">
        @error('quantity') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

        @if ($product->is_serialized)
            <div x-show="type === 'in'" class="mt-3">
                <label class="block text-sm font-medium mb-1">Serial Numbers <span class="font-normal text-slate-400">(one per unit)</span></label>
                <textarea name="serials" rows="3" class="{{ $input }} font-mono uppercase" :disabled="type !== 'in'">{{ old('serials') }}</textarea>
                @error('serials') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div x-show="type === 'out'" class="mt-3" style="display: none">
                <label class="block text-sm font-medium mb-1">Units to remove</label>
                <div class="max-h-40 overflow-y-auto rounded-lg border border-slate-200 divide-y divide-slate-50">
                    @forelse ($available as $u)
                        <label class="flex items-center gap-2 px-3 py-1.5 text-sm font-mono">
                            <input type="checkbox" name="unit_ids[]" value="{{ $u->id }}" x-model.number="picked" :disabled="type !== 'out'"
                                   class="rounded border-slate-300 text-slate-700 focus:ring-slate-500">
                            {{ $u->serial_no }}
                        </label>
                    @empty
                        <p class="px-3 py-2 text-sm text-slate-400">No in-stock units with serial numbers.</p>
                    @endforelse
                </div>
                @error('unit_ids') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        @endif

        <label class="block text-sm font-medium mb-1 mt-3">Remarks</label>
        <input type="text" name="remarks" value="{{ old('remarks') }}" placeholder="e.g. Delivery from supplier" class="{{ $input }}">
        @error('remarks') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

        <button class="w-full mt-4 px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Record Movement</button>
    </form>
</div>

{{-- Serial-numbered units --}}
@if ($product->is_serialized)
    <div class="bg-white rounded-xl border border-slate-200 mt-6 overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 px-6 pt-5 pb-3">
            <div>
                <h2 class="font-semibold">Serial-Numbered Units</h2>
                <p class="text-xs text-slate-400">Warranty status is calculated from the dates; units in stock have no warranty period until sold.</p>
            </div>
            <form method="GET" class="flex gap-2">
                <input type="search" name="serial" value="{{ $unitSearch }}" placeholder="Find serial no." class="{{ $input }} w-44">
                <button class="px-3 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Find</button>
            </form>
        </div>

        @if ($unregistered > 0)
            <form method="POST" action="{{ route('inventory.units', $product) }}" class="mx-6 mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4"
                  data-confirm="Record these serial numbers for units already in stock? The stock quantity will not change."
                  data-confirm-title="Record serial numbers">
                @csrf
                <p class="text-sm text-amber-800 mb-2">
                    <i class="ti ti-alert-triangle"></i>
                    {{ $unregistered }} unit(s) in stock have no serial number yet. Record them here (stock quantity will not change).
                </p>
                <div class="flex flex-col sm:flex-row gap-2">
                    <textarea name="serials" rows="2" placeholder="One serial number per line" class="{{ $input }} font-mono uppercase">{{ old('serials') }}</textarea>
                    <button class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800 sm:self-start whitespace-nowrap">Save Serials</button>
                </div>
            </form>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs uppercase text-slate-400 border-y border-slate-100">
                    <tr>
                        <th class="px-6 py-3 text-left">Serial No.</th>
                        <th class="px-6 py-3 text-center">Unit Status</th>
                        <th class="px-6 py-3 text-left">Received</th>
                        <th class="px-6 py-3 text-left">Sold To</th>
                        <th class="px-6 py-3 text-left">Warranty Period</th>
                        <th class="px-6 py-3 text-center">Warranty</th>
                        <th class="px-6 py-3 text-center">Repairs</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($units as $u)
                        <tr class="border-b border-slate-50">
                            <td class="px-6 py-3 font-mono font-medium">{{ $u->serial_no }}</td>
                            <td class="px-6 py-3 text-center"><x-badge type="unit" :value="$u->status" /></td>
                            <td class="px-6 py-3 text-slate-500">{{ $u->received_at->format('M d, Y') }}</td>
                            <td class="px-6 py-3">
                                @if ($u->saleItem?->sale)
                                    @if (auth()->user()->hasRole('admin', 'secretary'))
                                        <a href="{{ route('sales.show', $u->saleItem->sale) }}" class="hover:underline">{{ $u->saleItem->sale->customer->name }}</a>
                                    @else
                                        {{ $u->saleItem->sale->customer->name }}
                                    @endif
                                    <span class="block text-xs text-slate-400">{{ $u->saleItem->sale->invoice_no }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-slate-500">
                                @if ($u->warranty_end)
                                    {{ $u->warranty_start?->format('M d, Y') }} – {{ $u->warranty_end->format('M d, Y') }}
                                    @if ($left = \App\Support\Warranty::remainingText($u->warranty_end))
                                        <span class="block text-xs text-slate-400">{{ $left }}</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-3 text-center"><x-badge type="warranty" :value="$u->warrantyStatus()" /></td>
                            <td class="px-6 py-3 text-center">
                                @if ($u->repair_units_count && auth()->user()->hasRole('admin', 'technical_head', 'technician'))
                                    <a href="{{ route('repairs.index', ['q' => $u->serial_no]) }}" class="underline">{{ $u->repair_units_count }}</a>
                                @else
                                    {{ $u->repair_units_count ?: '—' }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-6 text-center text-slate-400">
                                {{ $unitSearch !== '' ? 'No unit matches that serial number.' : 'No serial numbers recorded yet.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($units->hasPages())
            <div class="px-6 py-3 border-t border-slate-100">{{ $units->links() }}</div>
        @endif
    </div>
@endif

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