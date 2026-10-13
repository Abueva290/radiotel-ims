<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public const STOCK_FILTERS = [
        'low'          => 'Low stock (at or below reorder level)',
        'low_only'     => 'Low stock, still available',
        'out'          => 'Out of stock',
        'discontinued' => 'Discontinued',
    ];

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $stock = array_key_exists($request->input('stock'), self::STOCK_FILTERS) ? $request->input('stock') : null;

        $products = Product::query()
            ->stockFilter($stock)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    // Find a product by any of its unit serial numbers
                    ->orWhereHas('units', fn ($u) => $u->where('serial_no', 'like', '%' . ProductUnit::normalize($search) . '%')));
            })
            ->withCount(['units as available_units_count' => fn ($q) => $q->where('status', 'in_stock')])
            ->orderBy('name')
            ->get();

        // Same scopes as the filters, so each card's number equals the rows it opens
        $stats = [
            'total' => Product::count(),
            'low'   => Product::stockFilter('low_only')->count(),
            'out'   => Product::stockFilter('out')->count(),
            'value' => Product::sum(DB::raw('stock_qty * unit_cost')),
        ];

        // When searching by serial, show which unit matched
        $serialMatch = $search !== ''
            ? ProductUnit::with('product')->where('serial_no', ProductUnit::normalize($search))->first()
            : null;

        return view('inventory.index', compact('products', 'stats', 'search', 'stock', 'serialMatch'));
    }

    public function create()
    {
        return view('inventory.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules() + [
            'stock_qty' => 'required|integer|min:0',
            'serials'   => 'nullable|string|max:20000',
        ]);

        $data['is_serialized'] = $request->boolean('is_serialized');
        $serials = $data['is_serialized'] ? ProductUnit::parseList($data['serials'] ?? '') : [];

        // A serialized product's opening stock must come with one serial per unit
        if ($data['is_serialized'] && count($serials) !== (int) $data['stock_qty']) {
            throw ValidationException::withMessages([
                'serials' => "Enter exactly {$data['stock_qty']} serial number(s), one per unit (you entered " . count($serials) . ').',
            ]);
        }
        $this->assertNewSerials($serials);

        DB::transaction(function () use ($data, $serials, $request) {
            $product = Product::create(collect($data)->except('serials')->all());

            if ($product->stock_qty > 0) {
                InventoryMovement::create([
                    'product_id'     => $product->id,
                    'movement_type'  => 'in',
                    'quantity'       => $product->stock_qty,
                    'reference_type' => 'manual',
                    'remarks'        => 'Initial stock' . ($serials ? ' (SN ' . $this->serialSummary($serials) . ')' : ''),
                    'movement_date'  => now(),
                    'recorded_by'    => $request->user()->id,
                ]);
            }

            $this->createUnits($product, $serials, $request->user()->id);
        });

        return redirect()->route('inventory.index')->with('success', 'Product added successfully.');
    }

    public function edit(Request $request, Product $product)
    {
        $movements = $product->movements()
            ->with('recorder')
            ->latest('movement_date')
            ->take(10)
            ->get();

        $unitSearch = trim((string) $request->input('serial'));
        $units = $product->units()
            ->with('saleItem.sale.customer')
            ->withCount('repairUnits')
            ->when($unitSearch !== '', fn ($q) => $q->where('serial_no', 'like', '%' . ProductUnit::normalize($unitSearch) . '%'))
            ->orderByRaw("FIELD(status, 'in_stock', 'under_inspection', 'defective', 'sold', 'returned_to_supplier', 'written_off')")
            ->orderBy('serial_no')
            ->paginate(20, ['*'], 'units_page')
            ->withQueryString();

        $available = $product->units()->where('status', 'in_stock')->orderBy('serial_no')->get(['id', 'serial_no']);
        $openDefects = $product->defects()->open()->latest()->get();

        return view('inventory.edit', compact('product', 'movements', 'units', 'unitSearch', 'available', 'openDefects'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate($this->rules() + [
            'status' => 'required|in:active,discontinued',
        ]);

        $data['is_serialized'] = $request->boolean('is_serialized');

        if (! $data['is_serialized'] && $product->is_serialized && $product->units()->exists()) {
            throw ValidationException::withMessages([
                'is_serialized' => 'Serial tracking cannot be turned off because this product already has serial-numbered units.',
            ]);
        }

        $product->update($data); // stock_qty is NOT editable here

        $note = $data['is_serialized'] && $product->unregisteredQty() > 0
            ? ' ' . $product->unregisteredQty() . ' unit(s) in stock still need serial numbers.'
            : '';

        return redirect()->route('inventory.edit', $product)->with('success', 'Product updated successfully.' . $note);
    }

    // Stock In / Stock Out / Adjustment (physical count)
    public function storeMovement(Request $request, Product $product)
    {
        $data = $request->validate([
            'movement_type' => 'required|in:in,out,adjustment',
            'quantity'      => 'required|integer|min:0',
            'remarks'       => 'nullable|string|max:255',
            'serials'       => 'nullable|string|max:20000',
            'unit_ids'      => 'nullable|array',
            'unit_ids.*'    => 'integer',
        ]);

        $qty = (int) $data['quantity'];

        if ($data['movement_type'] !== 'adjustment' && $qty < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be at least 1.']);
        }

        if ($data['movement_type'] === 'out' && $qty > $product->stock_qty) {
            throw ValidationException::withMessages([
                'quantity' => "Not enough stock. Only {$product->stock_qty} available.",
            ]);
        }

        $serials = [];
        $units = collect();

        if ($product->is_serialized) {
            if ($data['movement_type'] === 'adjustment') {
                throw ValidationException::withMessages([
                    'movement_type' => 'Serial-tracked products are counted unit by unit. Use Stock In with the serial numbers, or Stock Out with the units to remove.',
                ]);
            }

            if ($data['movement_type'] === 'in') {
                $serials = ProductUnit::parseList($data['serials'] ?? '');
                if (count($serials) !== $qty) {
                    throw ValidationException::withMessages([
                        'serials' => "Enter exactly {$qty} serial number(s), one per unit (you entered " . count($serials) . ').',
                    ]);
                }
                $this->assertNewSerials($serials);
            } else {
                $ids = array_unique($data['unit_ids'] ?? []);
                $units = $product->units()->whereIn('id', $ids)->where('status', 'in_stock')->get();
                if (count($ids) !== $qty || $units->count() !== $qty) {
                    throw ValidationException::withMessages([
                        'unit_ids' => "Select exactly {$qty} in-stock unit(s) to remove.",
                    ]);
                }
            }
        }

        DB::transaction(function () use ($product, $data, $qty, $request, $serials, $units) {
            $product = Product::whereKey($product->id)->lockForUpdate()->first();

            if ($data['movement_type'] === 'in') {
                $product->increment('stock_qty', $qty);
                $logged = $qty;
            } elseif ($data['movement_type'] === 'out') {
                if ($qty > $product->stock_qty) {
                    throw ValidationException::withMessages(['quantity' => "Not enough stock. Only {$product->stock_qty} available."]);
                }
                $product->decrement('stock_qty', $qty);
                $logged = $qty;
            } else {
                // Adjustment: set stock to the physically counted quantity
                $logged = $qty - $product->stock_qty;
                $product->update(['stock_qty' => $qty]);
            }

            $snList = $serials ?: $units->pluck('serial_no')->all();

            InventoryMovement::create([
                'product_id'     => $product->id,
                'movement_type'  => $data['movement_type'],
                'quantity'       => $logged,
                'reference_type' => 'manual',
                'remarks'        => trim(($data['remarks'] ?? '') . ($snList ? ' (SN ' . $this->serialSummary($snList) . ')' : '')) ?: null,
                'movement_date'  => now(),
                'recorded_by'    => $request->user()->id,
            ]);

            $this->createUnits($product, $serials, $request->user()->id);

            if ($units->isNotEmpty()) {
                ProductUnit::whereIn('id', $units->pluck('id'))->update([
                    'status'  => 'written_off',
                    'remarks' => $data['remarks'] ?? 'Stock out',
                ]);
            }
        });

        return redirect()->route('inventory.edit', $product)->with('success', 'Stock movement recorded.');
    }

    /** Record serial numbers for units already counted in stock (does not change the quantity). */
    public function storeUnits(Request $request, Product $product)
    {
        $data = $request->validate(['serials' => 'required|string|max:20000']);

        if (! $product->is_serialized) {
            return back()->with('error', 'Turn on serial tracking for this product first.');
        }

        $serials = ProductUnit::parseList($data['serials']);
        $missing = $product->unregisteredQty();

        if (count($serials) === 0) {
            throw ValidationException::withMessages(['serials' => 'Enter at least one serial number.']);
        }
        if (count($serials) > $missing) {
            throw ValidationException::withMessages([
                'serials' => "Only {$missing} unit(s) in stock are without a serial number. Use Stock In for new units.",
            ]);
        }
        $this->assertNewSerials($serials);

        DB::transaction(fn () => $this->createUnits($product, $serials, $request->user()->id));

        return back()->with('success', count($serials) . ' serial number(s) recorded for existing stock.');
    }

    // ------------------------------------------------------------------ helpers

    private function rules(): array
    {
        return [
            'name'            => 'required|string|max:255',
            'brand'           => 'nullable|string|max:100',
            'category'        => 'required|in:' . implode(',', array_keys(Product::CATEGORIES)),
            'unit_cost'       => 'required|numeric|min:0',
            'selling_price'   => 'required|numeric|min:0',
            'reorder_level'   => 'required|integer|min:0',
            'warranty_months' => 'nullable|integer|min:1|max:120',
            'is_serialized'   => 'nullable|boolean',
        ];
    }

    /** Serial numbers must be unique across all products. */
    private function assertNewSerials(array $serials): void
    {
        if (count($serials) !== count(array_unique($serials))) {
            throw ValidationException::withMessages(['serials' => 'The same serial number is listed more than once.']);
        }

        foreach ($serials as $serial) {
            if (strlen($serial) > 100) {
                throw ValidationException::withMessages(['serials' => "Serial number {$serial} is too long (max 100 characters)."]);
            }
        }

        $taken = ProductUnit::whereIn('serial_no', $serials)->pluck('serial_no');
        if ($taken->isNotEmpty()) {
            throw ValidationException::withMessages([
                'serials' => 'Serial number already recorded: ' . $taken->implode(', ') . '.',
            ]);
        }
    }

    private function createUnits(Product $product, array $serials, int $userId): void
    {
        $now = now();

        foreach ($serials as $serial) {
            ProductUnit::create([
                'product_id'  => $product->id,
                'serial_no'   => $serial,
                'status'      => 'in_stock',
                'received_at' => $now,
                'recorded_by' => $userId,
            ]);
        }
    }

    private function serialSummary(array $serials): string
    {
        return count($serials) > 5
            ? implode(', ', array_slice($serials, 0, 5)) . ' +' . (count($serials) - 5) . ' more'
            : implode(', ', $serials);
    }
}