<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerOrder;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Customer purchase orders: what a customer orders by phone or walk-in,
 * before the items are sourced from a supplier and delivered as a sale.
 */
class CustomerOrderController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => 'nullable|in:open,' . implode(',', array_keys(CustomerOrder::STATUSES)),
            'q'      => 'nullable|string|max:100',
        ]);
        $status = $filters['status'] ?? null;

        $orders = CustomerOrder::with('customer', 'items.product', 'sale', 'purchaseOrders')
            ->when($status === 'open', fn ($q) => $q->open())
            ->when($status && $status !== 'open', fn ($q) => $q->where('status', $status))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('order_no', 'like', "%{$term}%")
                ->orWhere('customer_po_no', 'like', "%{$term}%")
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%"))))
            ->latest('order_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'pending'    => CustomerOrder::where('status', 'pending')->count(),
            'ordered'    => CustomerOrder::where('status', 'ordered')->count(),
            'open_value' => CustomerOrder::open()->sum('total_amount'),
            'completed'  => CustomerOrder::where('status', 'completed')->count(),
        ];

        return view('customer-orders.index', compact('orders', 'stats', 'status') + ['q' => $filters['q'] ?? '']);
    }

    public function create()
    {
        return view('customer-orders.create', [
            'customers' => Customer::active()->orderBy('name')->get(),
            'products'  => Product::where('status', 'active')->orderBy('name')->get(),
            'orderNo'   => CustomerOrder::nextOrderNo(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id'        => ['required', Rule::exists('customers', 'id')->where('status', 'active')],
            'customer_po_no'     => 'nullable|string|max:100',
            'order_date'         => 'required|date',
            'needed_by'          => 'nullable|date|after_or_equal:order_date',
            'order_channel'      => 'required|in:' . implode(',', array_keys(CustomerOrder::CHANNELS)),
            'remarks'            => 'nullable|string|max:1000',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('status', 'active')],
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ], [
            'items.required'     => 'Add at least one product to the order.',
            'customer_id.exists' => 'The selected customer is archived or does not exist.',
        ]);

        $lines = self::combineLines($data['items'], 'unit_price');

        $order = DB::transaction(function () use ($data, $lines, $request) {
            $order = CustomerOrder::create([
                'order_no'       => CustomerOrder::nextOrderNo(),
                'customer_id'    => $data['customer_id'],
                'customer_po_no' => $data['customer_po_no'] ?? null,
                'order_date'     => $data['order_date'],
                'needed_by'      => $data['needed_by'] ?? null,
                'order_channel'  => $data['order_channel'],
                'remarks'        => $data['remarks'] ?? null,
                'status'         => 'pending',
                'total_amount'   => collect($lines)->sum(fn ($l) => $l['quantity'] * $l['price']),
                'created_by'     => $request->user()->id,
            ]);

            foreach ($lines as $productId => $line) {
                $order->items()->create([
                    'product_id' => $productId,
                    'quantity'   => $line['quantity'],
                    'unit_price' => $line['price'],
                    'subtotal'   => $line['quantity'] * $line['price'],
                ]);
            }

            return $order;
        });

        return redirect()->route('customer-orders.show', $order)
            ->with('success', "Customer order {$order->order_no} recorded.");
    }

    public function show(CustomerOrder $customerOrder)
    {
        $customerOrder->load('customer', 'creator', 'items.product', 'sale', 'purchaseOrders.supplier');

        return view('customer-orders.show', ['order' => $customerOrder]);
    }

    public function cancel(CustomerOrder $customerOrder)
    {
        if (! $customerOrder->isOpen()) {
            throw ValidationException::withMessages(['status' => 'Only pending or ordered customer orders can be cancelled.']);
        }

        $customerOrder->update(['status' => 'cancelled']);

        return redirect()->route('customer-orders.show', $customerOrder)
            ->with('success', "Customer order {$customerOrder->order_no} cancelled.");
    }

    /**
     * Merge duplicate rows of the same product (adds quantities, keeps the last price).
     * Returns [product_id => ['quantity' => int, 'price' => float]].
     */
    public static function combineLines(array $items, string $priceKey): array
    {
        $lines = [];
        foreach ($items as $item) {
            $id = (int) $item['product_id'];
            $lines[$id]['quantity'] = ($lines[$id]['quantity'] ?? 0) + (int) $item['quantity'];
            $lines[$id]['price'] = (float) $item[$priceKey];
        }

        return $lines;
    }
}