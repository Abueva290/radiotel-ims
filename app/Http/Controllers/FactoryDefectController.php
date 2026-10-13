<?php

namespace App\Http\Controllers;

use App\Models\FactoryDefect;
use App\Models\FactoryDefectLog;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\RepairJob;
use App\Models\Supplier;
use App\Models\User;
use App\Support\Badge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Factory defect reports (panel revision 5).
 *
 * Reporting is open to staff and technicians; only the Technical Head and the
 * Operational Manager inspect and decide whether it is really a factory defect.
 * Nothing is classified as a factory defect automatically.
 */
class FactoryDefectController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_merge(array_keys(FactoryDefect::STATUSES), ['open', 'awaiting']))],
            'cause'  => ['nullable', Rule::in(array_merge(array_keys(FactoryDefect::CAUSES), ['not_factory']))],
            'q'      => 'nullable|string|max:100',
        ]);

        $defects = FactoryDefect::with('product', 'repairJob.customer', 'reporter')
            ->filter($filters)
            ->when(trim($filters['q'] ?? '') !== '', function ($q) use ($filters) {
                $term = '%' . trim($filters['q']) . '%';
                $q->where(fn ($w) => $w->where('defect_no', 'like', $term)
                    ->orWhere('serial_no', 'like', '%' . ProductUnit::normalize($filters['q']) . '%')
                    ->orWhereHas('product', fn ($p) => $p->where('name', 'like', $term))
                    ->orWhereHas('repairJob', fn ($r) => $r->where('job_no', 'like', $term)));
            })
            ->orderByRaw("FIELD(status, 'reported', 'under_inspection', 'confirmed', 'returned_to_supplier', 'repaired', 'replaced', 'resolved')")
            ->latest('date_discovered')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            // Same filter as each card's link, so the numbers match the list
            'open'      => FactoryDefect::filter(['status' => 'open'])->count(),
            'reported'  => FactoryDefect::filter(['status' => 'awaiting'])->count(),
            'confirmed' => FactoryDefect::filter(['cause' => 'factory_defect'])->count(),
            'other'     => FactoryDefect::filter(['cause' => 'not_factory'])->count(),
        ];

        return view('defects.index', compact('defects', 'stats', 'filters'));
    }

    public function create(Request $request)
    {
        $job = $request->filled('repair_job_id') ? RepairJob::with('customer', 'serialUnits.productUnit')->find($request->input('repair_job_id')) : null;
        $serial = ProductUnit::normalize($request->input('serial_no'));
        $unit = $serial !== '' ? ProductUnit::where('serial_no', $serial)->first() : null;

        return view('defects.create', [
            'products'  => Product::orderBy('name')->get(['id', 'name', 'brand', 'is_serialized', 'stock_qty', 'status']),
            'suppliers' => Supplier::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'jobs'      => RepairJob::with('customer')->whereNotIn('status', ['cancelled'])->latest('id')->take(200)->get(['id', 'job_no', 'customer_id', 'unit_model']),
            'prefill'   => [
                'product_id'    => $request->input('product_id', $unit?->product_id),
                'repair_job_id' => $job?->id,
                'serial_no'     => $serial ?: null,
                'source'        => $job ? 'customer_unit' : 'inventory',
            ],
            'job'       => $job,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id'      => 'required|exists:products,id',
            'source'          => 'required|in:inventory,customer_unit',
            'serial_no'       => 'nullable|string|max:100',
            'quantity'        => 'required|integer|min:1|max:10000',
            'repair_job_id'   => 'nullable|exists:repair_jobs,id',
            'supplier_id'     => ['nullable', Rule::exists('suppliers', 'id')],
            'date_discovered' => 'required|date|before_or_equal:today',
            'description'     => 'required|string|min:5|max:2000',
        ], [
            'date_discovered.before_or_equal' => 'The date discovered cannot be in the future.',
        ]);

        $product = Product::findOrFail($data['product_id']);
        $serial = ProductUnit::normalize($data['serial_no'] ?? '') ?: null;
        $unit = $serial ? ProductUnit::where('serial_no', $serial)->first() : null;

        if ($unit && (int) $unit->product_id !== (int) $product->id) {
            throw ValidationException::withMessages([
                'serial_no' => "Serial {$serial} belongs to {$unit->product->name}, not {$product->name}.",
            ]);
        }

        if ($data['source'] === 'inventory') {
            if ($product->is_serialized) {
                // One physical unit from our stock
                if (! $unit || $unit->status !== 'in_stock') {
                    throw ValidationException::withMessages([
                        'serial_no' => $product->name . ' is serial-tracked: enter the serial number of an in-stock unit.',
                    ]);
                }
                $data['quantity'] = 1;
            } elseif ($data['quantity'] > $product->stock_qty) {
                throw ValidationException::withMessages([
                    'quantity' => "Only {$product->stock_qty} {$product->name} in stock.",
                ]);
            }
        } elseif (empty($data['repair_job_id'])) {
            throw ValidationException::withMessages([
                'repair_job_id' => 'Choose the repair job of the customer unit.',
            ]);
        }

        $defect = DB::transaction(function () use ($data, $product, $serial, $unit, $request) {
            $defect = FactoryDefect::create([
                'defect_no'       => FactoryDefect::nextDefectNo(),
                'product_id'      => $product->id,
                'product_unit_id' => $unit?->id,
                'serial_no'       => $serial,
                'repair_job_id'   => $data['repair_job_id'] ?? null,
                'supplier_id'     => $data['supplier_id'] ?? null,
                'source'          => $data['source'],
                'quantity'        => $data['quantity'],
                'description'     => $data['description'],
                'cause'           => 'unverified',
                'status'          => 'reported',
                'date_discovered' => $data['date_discovered'],
                'reported_by'     => $request->user()->id,
            ]);

            // An in-stock unit being checked must not be sold in the meantime
            if ($data['source'] === 'inventory' && $unit) {
                $unit->update(['status' => 'under_inspection']);
            }

            $this->log($defect, null, 'reported', $request->user(), 'Defect reported');

            return $defect;
        });

        return redirect()->route('defects.show', $defect)
            ->with('success', "Defect report {$defect->defect_no} recorded. It will be inspected by the Technical Head.");
    }

    public function show(FactoryDefect $defect)
    {
        $defect->load('product', 'unit.saleItem.sale.customer', 'repairJob.customer', 'supplier', 'reporter', 'inspector', 'logs.user');

        return view('defects.show', [
            'defect'    => $defect,
            'canAssess' => request()->user()->hasRole(...FactoryDefect::INSPECTOR_ROLES),
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Inspection, classification and resolution (Technical Head / Operational Manager). */
    public function updateStatus(Request $request, FactoryDefect $defect)
    {
        $data = $request->validate([
            'status'              => ['required', Rule::in(array_keys(FactoryDefect::STATUSES))],
            'cause'               => ['nullable', Rule::in(array_keys(FactoryDefect::CAUSES))],
            'inspection_findings' => 'nullable|string|max:2000',
            'action_taken'        => 'nullable|string|max:2000',
            'supplier_id'         => 'nullable|exists:suppliers,id',
            'remarks'             => 'nullable|string|max:500',
        ]);

        $to = $data['status'];
        $from = $defect->status;
        $user = $request->user();
        $fail = fn ($field, $msg) => throw ValidationException::withMessages([$field => $msg]);

        // Same status = update the assessment notes only
        if ($to !== $from && ! in_array($to, $defect->nextStatuses(), true)) {
            $allowed = collect($defect->nextStatuses())->map(fn ($s) => Badge::label('defect', $s))->implode(', ') ?: 'none';
            $fail('status', Badge::label('defect', $from) . ' cannot move to ' . Badge::label('defect', $to) . ". Allowed next: {$allowed}.");
        }

        $cause = $data['cause'] ?? $defect->cause;

        if ($to === 'confirmed') {
            if ($cause !== 'factory_defect') {
                $fail('cause', 'Set the cause to "Factory Defect" to confirm it.');
            }
            if (blank($data['inspection_findings'] ?? $defect->inspection_findings)) {
                $fail('inspection_findings', 'Record the inspection findings before confirming a factory defect.');
            }
        }
        if ($to === 'resolved' && $from === 'under_inspection') {
            if (in_array($cause, ['unverified', 'factory_defect'], true)) {
                $fail('cause', 'Choose what the inspection found (customer damage, wear and tear, or other). To confirm a factory defect use "Confirmed Factory Defect".');
            }
            if (blank($data['inspection_findings'] ?? $defect->inspection_findings)) {
                $fail('inspection_findings', 'Record the inspection findings.');
            }
        }
        if (in_array($to, ['returned_to_supplier', 'repaired', 'replaced', 'resolved'], true) && $to !== $from
            && blank($data['action_taken'] ?? null) && blank($defect->action_taken)) {
            $fail('action_taken', 'Describe the action taken.');
        }
        if ($to === 'returned_to_supplier' && ! ($data['supplier_id'] ?? $defect->supplier_id)) {
            $fail('supplier_id', 'Choose the supplier the item was returned to.');
        }
        // The cause can only be set while inspecting; afterwards it is part of the record
        if (isset($data['cause']) && $data['cause'] !== $defect->cause && ! in_array($from, ['reported', 'under_inspection'], true)) {
            $fail('cause', 'The cause was already decided at inspection and cannot be changed now.');
        }

        $stockNote = DB::transaction(function () use ($defect, $data, $to, $from, $cause, $user) {
            $defect = FactoryDefect::whereKey($defect->id)->lockForUpdate()->first();
            $note = null;

            $changes = [
                'status'              => $to,
                'cause'               => $cause,
                'inspection_findings' => $data['inspection_findings'] ?? $defect->inspection_findings,
                'action_taken'        => $data['action_taken'] ?? $defect->action_taken,
                'supplier_id'         => $data['supplier_id'] ?? $defect->supplier_id,
            ];
            if ($to === 'under_inspection' || ($to !== $from && $from === 'under_inspection')) {
                $changes['inspected_by'] = $user->id;
            }
            if ($to === 'resolved' && $from !== 'resolved') {
                $changes['resolved_at'] = now();
            }

            if ($defect->source === 'inventory' && $to !== $from) {
                $note = $this->applyStockEffect($defect, $to, $from, $user, $changes);
            }

            $defect->update($changes);

            $this->log($defect, $from, $to, $user, $data['remarks'] ?? ($to === $from ? 'Assessment updated' : null));

            return $note;
        });

        return redirect()->route('defects.show', $defect)
            ->with('success', ($to === $from ? 'Assessment updated.' : 'Status updated to ' . Badge::label('defect', $to) . '.') . ($stockNote ? " {$stockNote}" : ''));
    }

    /**
     * Keep defective stock out of what can be sold:
     *   confirmed          -> removed from stock (unit marked Defective)
     *   inspection cleared -> serial unit goes back to In Stock
     *   repaired           -> returned to stock
     *   returned           -> unit marked Returned to Supplier
     */
    private function applyStockEffect(FactoryDefect $defect, string $to, string $from, User $user, array &$changes): ?string
    {
        $product = Product::whereKey($defect->product_id)->lockForUpdate()->first();
        $unit = $defect->product_unit_id ? ProductUnit::whereKey($defect->product_unit_id)->lockForUpdate()->first() : null;

        if ($to === 'confirmed' && ! $defect->stock_quarantined) {
            if ($defect->quantity > $product->stock_qty) {
                throw ValidationException::withMessages(['status' => "Only {$product->stock_qty} {$product->name} left in stock; cannot remove {$defect->quantity}."]);
            }
            $product->decrement('stock_qty', $defect->quantity);
            $unit?->update(['status' => 'defective']);
            $this->movement($product, 'out', $defect->quantity, $defect, $user, 'Confirmed factory defect — removed from sellable stock');
            $changes['stock_quarantined'] = true;

            return "{$defect->quantity} unit(s) removed from sellable stock.";
        }

        if ($to === 'resolved' && $from === 'under_inspection' && $unit && $unit->status === 'under_inspection') {
            $unit->update(['status' => 'in_stock']);

            return 'Unit is available for sale again.';
        }

        if ($to === 'repaired' && $defect->stock_quarantined) {
            $product->increment('stock_qty', $defect->quantity);
            $unit?->update(['status' => 'in_stock']);
            $this->movement($product, 'in', $defect->quantity, $defect, $user, 'Defective item repaired — returned to stock');
            $changes['stock_quarantined'] = false;

            return "{$defect->quantity} unit(s) returned to stock.";
        }

        if ($to === 'returned_to_supplier') {
            $unit?->update(['status' => 'returned_to_supplier']);
        }

        return null;
    }

    private function movement(Product $product, string $type, int $qty, FactoryDefect $defect, User $user, string $remarks): void
    {
        InventoryMovement::create([
            'product_id'     => $product->id,
            'movement_type'  => $type,
            'quantity'       => $qty,
            'reference_type' => 'factory_defect',
            'reference_id'   => $defect->id,
            'remarks'        => "{$defect->defect_no}: {$remarks}" . ($defect->serial_no ? " (SN {$defect->serial_no})" : ''),
            'movement_date'  => now(),
            'recorded_by'    => $user->id,
        ]);
    }

    private function log(FactoryDefect $defect, ?string $from, string $to, User $user, ?string $remarks): void
    {
        FactoryDefectLog::create([
            'factory_defect_id' => $defect->id,
            'from_status'       => $from,
            'to_status'         => $to,
            'changed_by'        => $user->id,
            'remarks'           => $remarks,
            'changed_at'        => now(),
        ]);
    }
}