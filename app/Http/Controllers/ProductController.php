<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $products = Product::query()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('brand', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => Product::count(),
            'low'   => Product::lowStock()->where('stock_qty', '>', 0)->count(),
            'out'   => Product::where('stock_qty', '<=', 0)->count(),
            'value' => Product::sum(DB::raw('stock_qty * unit_cost')),
        ];

        return view('inventory.index', compact('products', 'stats', 'search'));
    }

    public function create()
    {
        return view('inventory.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules() + [
            'stock_qty' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($data, $request) {
            $product = Product::create($data);

            if ($product->stock_qty > 0) {
                InventoryMovement::create([
                    'product_id'     => $product->id,
                    'movement_type'  => 'in',
                    'quantity'       => $product->stock_qty,
                    'reference_type' => 'manual',
                    'remarks'        => 'Initial stock',
                    'movement_date'  => now(),
                    'recorded_by'    => $request->user()->id,
                ]);
            }
        });

        return redirect()->route('inventory.index')->with('success', 'Product added successfully.');
    }

    public function edit(Product $product)
    {
        $movements = $product->movements()
            ->with('recorder')
            ->latest('movement_date')
            ->take(10)
            ->get();

        return view('inventory.edit', compact('product', 'movements'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate($this->rules() + [
            'status' => 'required|in:active,discontinued',
        ]);

        $product->update($data); // stock_qty is NOT editable here

        return redirect()->route('inventory.index')->with('success', 'Product updated successfully.');
    }

    // Stock In / Stock Out / Adjustment (physical count)
    public function storeMovement(Request $request, Product $product)
    {
        $data = $request->validate([
            'movement_type' => 'required|in:in,out,adjustment',
            'quantity'      => 'required|integer|min:0',
            'remarks'       => 'nullable|string|max:255',
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

        DB::transaction(function () use ($product, $data, $qty, $request) {
            if ($data['movement_type'] === 'in') {
                $product->increment('stock_qty', $qty);
                $logged = $qty;
            } elseif ($data['movement_type'] === 'out') {
                $product->decrement('stock_qty', $qty);
                $logged = $qty;
            } else {
                // Adjustment: set stock to the physically counted quantity
                $logged = $qty - $product->stock_qty;
                $product->update(['stock_qty' => $qty]);
            }

            InventoryMovement::create([
                'product_id'     => $product->id,
                'movement_type'  => $data['movement_type'],
                'quantity'       => $logged,
                'reference_type' => 'manual',
                'remarks'        => $data['remarks'],
                'movement_date'  => now(),
                'recorded_by'    => $request->user()->id,
            ]);
        });

        return redirect()->route('inventory.edit', $product)->with('success', 'Stock movement recorded.');
    }

    private function rules(): array
    {
        return [
            'name'          => 'required|string|max:255',
            'brand'         => 'nullable|string|max:100',
            'category'      => 'required|in:' . implode(',', array_keys(Product::CATEGORIES)),
            'unit_cost'     => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
        ];
    }
}