<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Receivable;
use App\Models\RepairJob;
use App\Models\RepairPartUsed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RepairJobController extends Controller
{
    public function index()
    {
        $jobs = RepairJob::with('customer', 'assessor')
            ->latest('date_received')
            ->latest('id')
            ->get();

        $stats = [
            'open'      => RepairJob::whereIn('status', ['for_assessment', 'in_progress'])->count(),
            'completed' => RepairJob::whereIn('status', ['completed', 'released'])->count(),
            'billed'    => RepairJob::sum('total_amount'),
        ];

        return view('repairs.index', compact('jobs', 'stats'));
    }

    public function create()
    {
        return view('repairs.create', [
            'customers' => Customer::active()->orderBy('name')->get(),
            'jobNo'     => RepairJob::nextJobNo(),
            'fee'       => RepairJob::SERVICE_FEE_PER_UNIT,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id'      => ['required', Rule::exists('customers', 'id')->where('status', 'active')],
            'unit_model'       => 'required|string|max:255',
            'units'            => 'required|integer|min:1',
            'date_received'    => 'required|date',
            'assessment_notes' => 'nullable|string|max:1000',
        ], [
            'customer_id.exists' => 'The selected customer is archived or does not exist.',
        ]);

        $job = RepairJob::create($data + [
            'job_no'       => RepairJob::nextJobNo(),
            'assessed_by'  => $request->user()->id,
            'service_fee'  => $data['units'] * RepairJob::SERVICE_FEE_PER_UNIT,
            'parts_cost'   => 0,
            'total_amount' => $data['units'] * RepairJob::SERVICE_FEE_PER_UNIT,
            'status'       => 'for_assessment',
        ]);

        return redirect()->route('repairs.show', $job)
            ->with('success', "Repair job {$job->job_no} created.");
    }

    public function show(RepairJob $repair)
    {
        $repair->load('customer', 'assessor', 'parts.product', 'receivable');

        return view('repairs.show', [
            'job'      => $repair,
            'products' => Product::where('status', 'active')->where('stock_qty', '>', 0)->orderBy('name')->get(),
        ]);
    }

    // Add a part used during the repair (deducts stock)
    public function storePart(Request $request, RepairJob $repair)
    {
        $data = $request->validate([
            'product_id'    => 'required|exists:products,id',
            'quantity_used' => 'required|integer|min:1',
        ]);

        if (in_array($repair->status, ['completed', 'released'])) {
            throw ValidationException::withMessages([
                'product_id' => 'Cannot add parts to a completed job.',
            ]);
        }

        $product = Product::find($data['product_id']);

        if ($data['quantity_used'] > $product->stock_qty) {
            throw ValidationException::withMessages([
                'quantity_used' => "Not enough stock for {$product->name}. Only {$product->stock_qty} available.",
            ]);
        }

        DB::transaction(function () use ($repair, $product, $data, $request) {
            RepairPartUsed::create([
                'repair_job_id' => $repair->id,
                'product_id'    => $product->id,
                'quantity_used' => $data['quantity_used'],
                'unit_cost'     => $product->selling_price, // charged to customer
                'subtotal'      => $data['quantity_used'] * $product->selling_price,
            ]);

            $product->decrement('stock_qty', $data['quantity_used']);

            InventoryMovement::create([
                'product_id'     => $product->id,
                'movement_type'  => 'out',
                'quantity'       => $data['quantity_used'],
                'reference_type' => 'repair_job',
                'reference_id'   => $repair->id,
                'remarks'        => "Used in {$repair->job_no}",
                'movement_date'  => now(),
                'recorded_by'    => $request->user()->id,
            ]);

            $repair->recalculateTotals();
        });

        return back()->with('success', 'Part added and stock deducted.');
    }

    public function updateStatus(Request $request, RepairJob $repair)
    {
        $data = $request->validate([
            'status' => 'required|in:for_assessment,in_progress,completed,released',
        ]);

        $billed = false;

        DB::transaction(function () use ($repair, $data, &$billed) {
            $repair->update(['status' => $data['status']]);

            // Completing the job bills the customer on 30-day terms
            if ($data['status'] === 'completed' && ! $repair->receivable()->exists()) {
                Receivable::create([
                    'repair_job_id' => $repair->id,
                    'customer_id'   => $repair->customer_id,
                    'amount_due'    => $repair->total_amount,
                    'balance'       => $repair->total_amount,
                    'due_date'      => now()->addDays(30),
                    'status'        => 'unpaid',
                ]);
                $billed = true;
            }
        });

        return back()->with('success', $billed
            ? 'Job completed and receivable created.'
            : 'Repair status updated.');
    }
}