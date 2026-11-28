<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Supplier purchase orders.
 *   Secretary (or Admin) prepares the PO       → draft
 *   Operational Manager / Admin approves it    → approved
 *   Secretary / Admin sends it to the supplier → sent
 *   Staff / Admin confirms the delivery        → received
 * Stock is NOT added automatically on receipt: staff record the stock-in in Inventory.
 */
class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status'   => 'nullable|in:open,' . implode(',', array_keys(PurchaseOrder::STATUSES)),
            'supplier' => 'nullable|integer',
            'q'        => 'nullable|string|max:100',
        ]);
        $status = $filters['status'] ?? null;

        $orders = PurchaseOrder::with('supplier', 'items')
            ->when($status === 'open', fn ($q) => $q->whereIn('status', ['draft', 'approved', 'sent']))
            ->when($status && $status !== 'open', fn ($q) => $q->where('status', $status))
            ->when($filters['supplier'] ?? null, fn ($q, $id) => $q->where('supplier_id', $id))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('po_no', 'like', "%{$term}%")
                ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$term}%"))))
            ->latest('order_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'draft'      => PurchaseOrder::where('status', 'draft')->count(),
            'approved'   => PurchaseOrder::where('status', 'approved')->count(),
            'sent'       => PurchaseOrder::where('status', 'sent')->count(),
            'open_value' => PurchaseOrder::whereIn('status', ['draft', 'approved', 'sent'])->sum('total_amount'),
        ];

        return view('purchase-orders.index', [
            'orders'     => $orders,
            'stats'      => $stats,
            'status'     => $status,
            'q'          => $filters['q'] ?? '',
            'suppliers'  => Supplier::orderBy('name')->get(),
            'supplierId' => $filters['supplier'] ?? null,
        ]);
    }

    public function create()
    {
        return view('purchase-orders.create', [
            'suppliers' => Supplier::active()->orderBy('name')->get(),
            'products'  => Product::where('status', 'active')->orderBy('name')->get(),
            'poNo'      => PurchaseOrder::nextPoNo(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id'        => ['required', Rule::exists('suppliers', 'id')->where('status', 'active')],
            'order_date'         => 'required|date',
            'expected_date'      => 'nullable|date|after_or_equal:order_date',
            'remarks'            => 'nullable|string|max:1000',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('status', 'active')],
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_cost'  => 'required|numeric|min:0',
        ], [
            'items.required'     => 'Add at least one product to the purchase order.',
            'supplier_id.exists' => 'The selected supplier is archived or does not exist.',
        ]);

        $lines = self::combineLines($data['items'], 'unit_cost');

        $po = DB::transaction(function () use ($data, $lines, $request) {
            $po = PurchaseOrder::create([
                'po_no'             => PurchaseOrder::nextPoNo(),
                'supplier_id'       => $data['supplier_id'],
                'order_date'        => $data['order_date'],
                'expected_date'     => $data['expected_date'] ?? null,
                'remarks'           => $data['remarks'] ?? null,
                'status'            => 'draft',
                'total_amount'      => collect($lines)->sum(fn ($l) => $l['quantity'] * $l['price']),
                'created_by'        => $request->user()->id,
            ]);

            foreach ($lines as $productId => $line) {
                $po->items()->create([
                    'product_id' => $productId,
                    'quantity'   => $line['quantity'],
                    'unit_cost'  => $line['price'],
                    'subtotal'   => $line['quantity'] * $line['price'],
                ]);
            }

            return $po;
        });

        return redirect()->route('purchase-orders.show', $po)
            ->with('success', "Purchase order {$po->po_no} saved and waiting for the Operational Manager's approval.");
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load('supplier', 'items.product', 'invoices',
            'creator', 'approver', 'receiver', 'canceller');

        return view('purchase-orders.show', ['po' => $purchaseOrder]);
    }

    /** Operational Manager / Admin only. */
    public function approve(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->requireStatus($purchaseOrder, 'draft', 'Only purchase orders waiting for approval can be approved.');

        $purchaseOrder->update([
            'status'      => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', "{$purchaseOrder->po_no} approved. It can now be sent to {$purchaseOrder->supplier->name}.");
    }

    public function send(PurchaseOrder $purchaseOrder)
    {
        $this->requireStatus($purchaseOrder, 'approved', 'Only approved purchase orders can be sent to the supplier.');

        $purchaseOrder->update(['status' => 'sent', 'sent_at' => now()]);

        return back()->with('success', "{$purchaseOrder->po_no} marked as sent to {$purchaseOrder->supplier->name}.");
    }

    /** Staff / Admin confirm the supplier's delivery against the PO. */
    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
                $data = $request->validate([
            'received_date'  => 'required|date|before_or_equal:today',
            'supplier_dr_no' => 'required|string|max:100',
        ], [], [
            'received_date'  => 'date received',
            'supplier_dr_no' => 'supplier delivery receipt no.',
        ]);

        $this->requireStatus($purchaseOrder, 'sent', 'Only purchase orders sent to the supplier can be marked as received.');

        $purchaseOrder->update($data + ['status' => 'received', 'received_by' => $request->user()->id]);

        return back()->with('success', "{$purchaseOrder->po_no} marked as received. Record the stock-in for each item in Inventory.");
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (! $purchaseOrder->canBeCancelled()) {
            throw ValidationException::withMessages(['status' => 'Received or cancelled purchase orders cannot be cancelled.']);
        }

        $purchaseOrder->update([
            'status'       => 'cancelled',
            'cancelled_by' => $request->user()->id,
            'cancelled_at' => now(),
        ]);

        return back()->with('success', "{$purchaseOrder->po_no} cancelled.");
    }

    /** Printable purchase order (/purchase-orders/{id}/print, ?pdf=1 to download). */
    public function document(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (in_array($purchaseOrder->status, ['draft', 'cancelled'], true)) {
            abort(403, 'Only approved purchase orders can be printed.');
        }

        $purchaseOrder->load('supplier', 'items.product', 'creator', 'approver');
        $data = ['po' => $purchaseOrder, 'isPdf' => $request->boolean('pdf')];

        if ($data['isPdf']) {
            return Pdf::loadView('purchase-orders.document', $data)
                ->setPaper('a4', 'portrait')
                ->download($purchaseOrder->po_no . '.pdf');
        }

        return view('purchase-orders.document', $data);
    }

    private function requireStatus(PurchaseOrder $po, string $status, string $message): void
    {
        if ($po->status !== $status) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }

    /** Merge duplicate rows of the same product: [product_id => ['quantity' => n, 'price' => x]]. */
    private static function combineLines(array $items, string $priceKey): array
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