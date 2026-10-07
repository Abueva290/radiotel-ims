<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayableController extends Controller
{
    public function index(Request $request)
    {
        $supplierId = $request->integer('supplier') ?: null;

        $invoices = SupplierInvoice::with('supplier')
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->orderByRaw("FIELD(status, 'unpaid', 'partial', 'paid')")
            ->orderBy('due_date')
            ->get();

        $stats = [
            'total'   => SupplierInvoice::sum('balance'),
            'overdue' => SupplierInvoice::overdue()->sum('balance'),
            'due_soon' => SupplierInvoice::where('status', '!=', 'paid')
                ->whereBetween('due_date', [now(), now()->addDays(14)])
                ->sum('balance'),
        ];

        // Per supplier: what is owed, how much is overdue, how many invoices are open, and the next due date
        $unpaid = fn ($q) => $q->where('status', '!=', 'paid');

        $suppliers = Supplier::orderBy('name')
            ->withSum(['invoices as outstanding' => $unpaid], 'balance')
            ->withSum(['invoices as overdue_amount' => fn ($q) => $unpaid($q)->whereDate('due_date', '<', today())], 'balance')
            ->withCount(['invoices as open_count' => $unpaid])
            ->withMin(['invoices as next_due' => $unpaid], 'due_date')
            ->get();

        // Priority: 0 = overdue, 1 = due within 14 days, 2 = owed but not yet due, 3 = settled
        foreach ($suppliers as $s) {
            $s->state_rank = match (true) {
                (float) $s->overdue_amount > 0                                     => 0,
                $s->next_due && Carbon::parse($s->next_due)->lte(today()->addDays(14)) => 1,
                (float) $s->outstanding > 0                                        => 2,
                default                                                            => 3,
            };
        }

        return view('payables.index', [
            'invoices'      => $invoices,
            'stats'         => $stats,
            'suppliers'     => $suppliers, // alphabetical, used by the Record Invoice form
            'supplierCards' => $suppliers->sortBy([['state_rank', 'asc'], ['outstanding', 'desc']])->values(),
            'selected'      => $supplierId ? $suppliers->firstWhere('id', $supplierId) : null,
        ]);
    }

    public function create()
    {
        return view('payables.create', [
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id'  => 'required|exists:suppliers,id,status,active',
            'invoice_no'   => 'required|string|max:100',
            'invoice_date' => 'required|date',
            'amount'       => 'required|numeric|min:0.01',
        ]);

        $supplier = Supplier::find($data['supplier_id']);

        $duplicate = SupplierInvoice::where('supplier_id', $supplier->id)
            ->where('invoice_no', $data['invoice_no'])
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'invoice_no' => "Invoice {$data['invoice_no']} is already recorded for {$supplier->name}.",
            ]);
        }

        $invoice = SupplierInvoice::create($data + [
            'due_date'    => now()->parse($data['invoice_date'])->addDays($supplier->credit_terms_days),
            'balance'     => $data['amount'],
            'status'      => 'unpaid',
            'recorded_by' => $request->user()->id,
        ]);

        return redirect()->route('payables.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_no} recorded. Due {$invoice->due_date->format('M d, Y')}.");
    }

    public function show(SupplierInvoice $payable)
    {
        $payable->load('supplier', 'recorder', 'payments.recorder');

        return view('payables.show', ['invoice' => $payable]);
    }

    public function storePayment(Request $request, SupplierInvoice $payable)
    {
        $data = $request->validate([
            'amount_paid'    => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,check,bank_transfer',
            'payment_date'   => 'required|date',
        ]);

        if ($payable->status === 'paid') {
            throw ValidationException::withMessages([
                'amount_paid' => 'This invoice is already fully paid.',
            ]);
        }

        if ($data['amount_paid'] > $payable->balance) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Payment exceeds the remaining balance of ₱'
                    . number_format($payable->balance, 2) . '.',
            ]);
        }

        DB::transaction(function () use ($payable, $data, $request) {
            SupplierPayment::create([
                'supplier_invoice_id' => $payable->id,
                'amount_paid'         => $data['amount_paid'],
                'payment_method'      => $data['payment_method'],
                'payment_date'        => $data['payment_date'],
                'recorded_by'         => $request->user()->id,
            ]);

            $balance = $payable->balance - $data['amount_paid'];

            $payable->update([
                'balance' => $balance,
                'status'  => $balance <= 0 ? 'paid' : 'partial',
            ]);
        });

        return redirect()->route('payables.show', $payable)
            ->with('success', 'Supplier payment recorded.');
    }
}