<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status');

        $sales = Sale::with('customer', 'items.product')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('sale_date')
            ->latest('id')
            ->get();

        $totals = [
            'all'    => Sale::sum('total_amount'),
            'unpaid' => Receivable::whereNotNull('sale_id')->sum('balance'),
        ];

        return view('sales.index', compact('sales', 'totals', 'status'));
    }

    public function create()
    {
        $customers = Customer::active()->orderBy('name')->get();
        $products  = Product::where('status', 'active')->orderBy('name')->get();

        return view('sales.create', [
            'customers'  => $customers,
            'products'   => $products,
            'invoiceNo'  => Sale::nextInvoiceNo(),
            'drNo'       => Sale::nextDrNo(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id'          => ['required', Rule::exists('customers', 'id')->where('status', 'active')],
            'sale_date'            => 'required|date',
            'terms_days'           => 'required|integer|min:0|max:120',
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|exists:products,id',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.unit_price'   => 'required|numeric|min:0',
        ], [
            'items.required'     => 'Add at least one product to the sale.',
            'customer_id.exists' => 'The selected customer is archived or does not exist.',
        ]);

        // Combine duplicate rows of the same product, then check stock
        $lines = [];
        foreach ($data['items'] as $item) {
            $id = $item['product_id'];
            $lines[$id]['quantity'] = ($lines[$id]['quantity'] ?? 0) + (int) $item['quantity'];
            $lines[$id]['unit_price'] = (float) $item['unit_price'];
        }

        foreach ($lines as $productId => $line) {
            $product = Product::find($productId);
            if ($line['quantity'] > $product->stock_qty) {
                throw ValidationException::withMessages([
                    'items' => "Not enough stock for {$product->name}. Only {$product->stock_qty} available.",
                ]);
            }
        }

        $sale = DB::transaction(function () use ($data, $lines, $request) {
            $total = 0;
            foreach ($lines as $line) {
                $total += $line['quantity'] * $line['unit_price'];
            }

            $sale = Sale::create([
                'customer_id'  => $data['customer_id'],
                'created_by'   => $request->user()->id,
                'invoice_no'   => Sale::nextInvoiceNo(),
                'dr_no'        => Sale::nextDrNo(),
                'sale_date'    => $data['sale_date'],
                'total_amount' => $total,
                'status'       => 'unpaid',
            ]);

            foreach ($lines as $productId => $line) {
                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $productId,
                    'quantity'   => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'subtotal'   => $line['quantity'] * $line['unit_price'],
                ]);

                // Deduct stock and log the movement
                Product::find($productId)->decrement('stock_qty', $line['quantity']);

                InventoryMovement::create([
                    'product_id'     => $productId,
                    'movement_type'  => 'out',
                    'quantity'       => $line['quantity'],
                    'reference_type' => 'sale',
                    'reference_id'   => $sale->id,
                    'remarks'        => "Sold via {$sale->invoice_no}",
                    'movement_date'  => now(),
                    'recorded_by'    => $request->user()->id,
                ]);
            }

            // Charge invoice on credit → becomes a receivable
            Receivable::create([
                'sale_id'     => $sale->id,
                'customer_id' => $sale->customer_id,
                'amount_due'  => $total,
                'balance'     => $total,
                'due_date'    => $sale->sale_date->copy()->addDays((int) $data['terms_days']),
                'status'      => 'unpaid',
            ]);

            return $sale;
        });

        return redirect()->route('sales.show', $sale)
            ->with('success', "Sale {$sale->invoice_no} recorded. Stock deducted and receivable created.");
    }

    public function show(Sale $sale)
    {
        $sale->load('customer', 'creator', 'items.product', 'receivable.payments');

        return view('sales.show', compact('sale'));
    }
}