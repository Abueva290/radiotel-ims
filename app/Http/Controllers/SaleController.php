<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Receivable;
use App\Models\Sale;
use App\Models\SaleItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => 'nullable|in:unpaid,partial,paid,overdue,outstanding',
            'from'   => 'nullable|date',
            'to'     => 'nullable|date|after_or_equal:from',
        ]);
        $status = $filters['status'] ?? null;

        $sales = Sale::with('customer', 'items.product', 'receivable')
            ->filter($filters)
            ->latest('sale_date')
            ->latest('id')
            ->get();

        $totals = [
            'all'    => Sale::sum('total_amount'),
            'unpaid' => Receivable::whereNotNull('sale_id')->sum('balance'),
        ];

        return view('sales.index', [
            'sales'     => $sales,
            'totals'    => $totals,
            'status'    => $status,
            'from'      => $filters['from'] ?? null,
            'to'        => $filters['to'] ?? null,
            'customers' => Customer::active()->orderBy('name')->get(),
            'products'  => $this->sellableProducts(),
            'invoiceNo' => Sale::nextInvoiceNo(),
            'drNo'      => Sale::nextDrNo(),
        ]);
    }

    public function create()
    {
        $customers = Customer::active()->orderBy('name')->get();
        $products  = $this->sellableProducts();

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
            'items.*.unit_ids'     => 'nullable|array',
            'items.*.unit_ids.*'   => 'integer',
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
            $lines[$id]['unit_ids'] = array_merge($lines[$id]['unit_ids'] ?? [], array_map('intval', $item['unit_ids'] ?? []));
        }

        foreach ($lines as $productId => $line) {
            $product = Product::find($productId);
            if ($line['quantity'] > $product->stock_qty) {
                throw ValidationException::withMessages([
                    'items' => "Not enough stock for {$product->name}. Only {$product->stock_qty} available.",
                ]);
            }

            // Serial-tracked products: the exact units being sold must be picked
            if ($product->is_serialized) {
                $ids = array_unique($line['unit_ids']);
                $valid = ProductUnit::where('product_id', $product->id)->where('status', 'in_stock')->whereIn('id', $ids)->count();
                if (count($ids) !== $line['quantity'] || $valid !== $line['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => "Select exactly {$line['quantity']} serial number(s) for {$product->name}.",
                    ]);
                }
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
                $saleItem = SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $productId,
                    'quantity'   => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'subtotal'   => $line['quantity'] * $line['unit_price'],
                ]);

                // Deduct stock and log the movement
                $product = Product::whereKey($productId)->lockForUpdate()->first();
                if ($line['quantity'] > $product->stock_qty) {
                    throw ValidationException::withMessages([
                        'items' => "Not enough stock for {$product->name}. Only {$product->stock_qty} available.",
                    ]);
                }
                $product->decrement('stock_qty', $line['quantity']);

                // Mark the serial-numbered units as sold; their warranty starts on the sale date
                $serials = [];
                if ($product->is_serialized) {
                    $units = ProductUnit::whereIn('id', array_unique($line['unit_ids']))
                        ->where('product_id', $productId)->where('status', 'in_stock')
                        ->lockForUpdate()->get();
                    if ($units->count() !== $line['quantity']) {
                        throw ValidationException::withMessages(['items' => "Some selected units of {$product->name} are no longer available."]);
                    }
                    foreach ($units as $unit) {
                        $unit->update([
                            'status'         => 'sold',
                            'sale_item_id'   => $saleItem->id,
                            'warranty_start' => $product->warranty_months ? $sale->sale_date : null,
                            'warranty_end'   => $product->warranty_months ? $sale->sale_date->copy()->addMonthsNoOverflow($product->warranty_months)->subDay() : null,
                        ]);
                    }
                    $serials = $units->pluck('serial_no')->all();
                }

                InventoryMovement::create([
                    'product_id'     => $productId,
                    'movement_type'  => 'out',
                    'quantity'       => $line['quantity'],
                    'reference_type' => 'sale',
                    'reference_id'   => $sale->id,
                    'remarks'        => "Sold via {$sale->invoice_no}" . ($serials ? ' (SN ' . implode(', ', $serials) . ')' : ''),
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
        $sale->load('customer', 'creator', 'items.product', 'items.units', 'receivable.payments');

        return view('sales.show', compact('sale'));
    }

    // Printable Charge Invoice (/sales/{id}/invoice) or Delivery Receipt (/sales/{id}/dr)
    public function document(Request $request, Sale $sale, string $type)
    {
        $sale->load('customer', 'creator', 'items.product', 'items.units', 'receivable');

        $isInvoice = $type === 'invoice';

        $data = [
            'sale'      => $sale,
            'type'      => $type,
            'title'     => $isInvoice ? 'Charge Invoice' : 'Delivery Receipt',
            'number'    => $isInvoice ? $sale->invoice_no : $sale->dr_no,
            'termsDays' => $sale->receivable
                ? (int) round(abs($sale->sale_date->diffInDays($sale->receivable->due_date)))
                : 0,
            'isPdf'     => $request->boolean('pdf'),
        ];

        if ($data['isPdf']) {
            return Pdf::loadView('sales.document', $data)
                ->setPaper('a4', 'portrait')
                ->download($data['number'] . '.pdf');
        }

        return view('sales.document', $data);
    }

    /** Active products, with the serial numbers that can be sold for serial-tracked ones. */
    private function sellableProducts()
    {
        return Product::where('status', 'active')
            ->with(['units' => fn ($q) => $q->where('status', 'in_stock')->orderBy('serial_no')])
            ->orderBy('name')
            ->get();
    }
}