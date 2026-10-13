<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Receivable;
use App\Models\RepairJob;
use App\Models\RepairJobAssignment;
use App\Models\RepairJobStatusLog;
use App\Models\RepairJobUnit;
use App\Models\RepairPartUsed;
use App\Models\User;
use App\Support\Badge;
use App\Support\Warranty;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RepairJobController extends Controller
{
    public const PER_PAGE = 15;

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100',
            'status' => ['nullable', Rule::in(array_merge(array_keys(RepairJob::STATUSES), ['open', 'active', 'finished', 'finished_late']))],
            'technician' => ['nullable', 'regex:/^(unassigned|\d+)$/'],
            'received_from' => 'nullable|date',
            'received_to' => 'nullable|date|after_or_equal:received_from',
            'due_from' => 'nullable|date',
            'due_to' => 'nullable|date|after_or_equal:due_from',
            'overdue' => 'nullable|boolean',
            'warranty' => 'nullable|in:active',
        ]);

        $jobs = RepairJob::with('customer', 'technician', 'serialUnits')
            ->filter($filters)
            // Overdue first, then jobs still in the shop, then newest
            ->orderByRaw("CASE WHEN status IN ('".implode("','", RepairJob::OPEN_STATUSES)."') AND target_completion_date < ? THEN 0 ELSE 1 END", [today()->toDateString()])
            ->orderByRaw("CASE WHEN status IN ('".implode("','", RepairJob::ACTIVE_STATUSES)."') THEN 0 ELSE 1 END")
            ->latest('date_received')
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        // Same queries as the dashboard cards, so the numbers match
        $stats = [
            'for_assessment' => RepairJob::filter(['status' => 'for_assessment'])->count(),
            'in_progress' => RepairJob::filter(['status' => 'in_progress'])->count(),
            'overdue' => RepairJob::filter(['overdue' => 1])->count(),
            'completed' => RepairJob::filter(['status' => 'completed'])->count(),
            'warranty' => RepairJob::filter(['warranty' => 'active'])->count(),
        ];

        return view('repairs.index', [
            'jobs' => $jobs,
            'stats' => $stats,
            'filters' => $filters,
            'hasFilters' => collect($filters)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty(),
            'technicians' => $this->technicians(withInactive: true),
            'customers' => Customer::active()->orderBy('name')->get(),
            'jobNo' => RepairJob::nextJobNo(),
            'fee' => RepairJob::SERVICE_FEE_PER_UNIT,
            'activeTechs' => $this->technicians(),
        ]);
    }

    public function create()
    {
        return view('repairs.create', [
            'customers' => Customer::active()->orderBy('name')->get(),
            'jobNo' => RepairJob::nextJobNo(),
            'fee' => RepairJob::SERVICE_FEE_PER_UNIT,
            'activeTechs' => $this->technicians(),
        ]);
    }

    public function store(Request $request)
    {
        $canAssign = $request->user()->hasRole(...RepairJob::SUPERVISOR_ROLES);

        $data = $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('status', 'active')],
            'unit_model' => 'required|string|max:255',
            'units' => 'required|integer|min:1|max:500',
            'date_received' => 'required|date|before_or_equal:today',
            'time_received' => 'nullable|date_format:H:i',
            'target_completion_date' => 'nullable|date|after_or_equal:date_received',
            'assessment_notes' => 'nullable|string|max:1000',
            'serials' => 'nullable|string|max:5000',
            'warranty_start' => 'nullable|date',
            'warranty_end' => 'nullable|date|after_or_equal:warranty_start',
            'assigned_to' => ['nullable', Rule::exists('users', 'id')
                ->whereIn('role', RepairJob::TECHNICIAN_ROLES)->where('status', 'active')],
        ], [
            'customer_id.exists' => 'The selected customer is archived or does not exist.',
            'assigned_to.exists' => 'Choose an active technician from the list.',
            'target_completion_date.after_or_equal' => 'The target completion date cannot be before the date received.',
        ]);

        if (! empty($data['assigned_to']) && ! $canAssign) {
            abort(403, 'Only the Technical Head or Operational Manager can assign jobs.');
        }

        $serials = ProductUnit::parseList($data['serials'] ?? '');
        $this->validateSerials($serials, (int) $data['units']);

        $receivedAt = Carbon::parse($data['date_received'].' '.($data['time_received'] ?? now()->format('H:i')));

        $job = DB::transaction(function () use ($data, $serials, $receivedAt, $request) {
            $fee = $data['units'] * RepairJob::SERVICE_FEE_PER_UNIT;

            $job = RepairJob::create([
                'job_no' => RepairJob::nextJobNo(),
                'customer_id' => $data['customer_id'],
                'assessed_by' => $request->user()->id,
                'unit_model' => $data['unit_model'],
                'units' => $data['units'],
                'assessment_notes' => $data['assessment_notes'] ?? null,
                'date_received' => $data['date_received'],
                'received_at' => $receivedAt,
                'target_completion_date' => $data['target_completion_date'] ?? null,
                'service_fee' => $fee,
                'parts_cost' => 0,
                'total_amount' => $fee,
                'status' => 'for_assessment',
            ]);

            $this->log($job, null, 'for_assessment', $request->user(), 'Job received');

            foreach ($serials as $serial) {
                $this->attachUnit($job, $serial, $data['warranty_start'] ?? null, $data['warranty_end'] ?? null);
            }

            if (! empty($data['assigned_to'])) {
                $this->assignTo($job, User::find($data['assigned_to']), $request->user(), $data['target_completion_date'] ?? null, 'Assigned when the job was created');
            }

            return $job;
        });

        return redirect()->route('repairs.show', $job)
            ->with('success', "Repair job {$job->job_no} created.");
    }

    public function show(RepairJob $repair)
    {
        $repair->load(
            'customer', 'assessor', 'technician', 'assigner', 'parts.product', 'receivable',
            'serialUnits.productUnit.product', 'serialUnits.productUnit.saleItem.sale.customer',
            'assignments.technician', 'assignments.assigner', 'statusLogs.user', 'defects',
        );

        $user = request()->user();

        return view('repairs.show', [
            'job' => $repair,
            'products' => Product::where('status', 'active')->where('is_serialized', false)
                ->where('stock_qty', '>', 0)->orderBy('name')->get(),
            'technicians' => $this->technicians(),
            'canWork' => $repair->canBeWorkedOnBy($user),
            'isSupervisor' => $user->hasRole(...RepairJob::SUPERVISOR_ROLES),
        ]);
    }

    /** Assign or reassign the job to a technician (Technical Head / Operational Manager). */
    public function assign(Request $request, RepairJob $repair)
    {
        $data = $request->validate([
            'technician_id' => ['required', Rule::exists('users', 'id')
                ->whereIn('role', RepairJob::TECHNICIAN_ROLES)->where('status', 'active')],
            'expected_completion_date' => 'nullable|date|after_or_equal:'.$repair->date_received->toDateString(),
            'remarks' => 'nullable|string|max:500',
        ], [
            'technician_id.exists' => 'Choose an active technician from the list.',
            'expected_completion_date.after_or_equal' => 'The expected completion date cannot be before the date received.',
        ]);

        if ($repair->isClosed()) {
            return back()->with('error', "{$repair->job_no} is already {$this->label($repair->status)} and can no longer be reassigned.");
        }

        $expected = $data['expected_completion_date'] ?? null;
        $sameTech = (int) $repair->assigned_to === (int) $data['technician_id'];
        $sameDate = $expected === null || $repair->target_completion_date?->toDateString() === $expected;

        if ($sameTech && $sameDate) {
            return back()->with('error', 'This job is already assigned to that technician with the same expected completion date.');
        }

        $technician = User::find($data['technician_id']);
        $isReassign = $repair->assigned_to !== null;

        DB::transaction(fn () => $this->assignTo(
            $repair, $technician, $request->user(), $expected, $data['remarks'] ?? null
        ));

        return back()->with('success', ($isReassign ? 'Job reassigned to ' : 'Job assigned to ').$technician->name.'.');
    }

    public function updateStatus(Request $request, RepairJob $repair)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(RepairJob::STATUSES))],
            'remarks' => 'nullable|string|max:500',
            'override' => 'nullable|boolean',
        ]);

        $user = $request->user();
        $to = $data['status'];
        $override = (bool) ($data['override'] ?? false);

        if (! $repair->canBeWorkedOnBy($user)) {
            abort(403, 'Only the assigned technician, the Technical Head, or the Operational Manager can update this job.');
        }

        $billed = false;

        DB::transaction(function () use ($repair, $to, $override, $data, $user, &$billed) {
            // Lock the row so two people cannot move the same job at the same time
            $job = RepairJob::whereKey($repair->id)->lockForUpdate()->first();
            $from = $job->status;
            $isOverride = ! $job->canMoveTo($to);

            $this->guardTransition($job, $to, $override, $data['remarks'] ?? null, $user);

            $changes = ['status' => $to];
            $now = now();

            if ($to === 'in_progress' && ! $job->started_at) {
                $changes['started_at'] = $now;
            }
            if (in_array($to, RepairJob::BILLED_STATUSES, true) && ! $job->completed_at) {
                $changes['completed_at'] = $now;   // work finished
            }
            if ($to === 'released') {
                $changes['released_at'] = $now;    // claimed by the customer
            }
            if ($to === 'cancelled') {
                $changes['cancelled_at'] = $now;
            }
            // Re-opening a cancelled job (override) clears only the cancellation time;
            // all other timestamps are history and are never overwritten
            if ($from === 'cancelled' && $to !== 'cancelled') {
                $changes['cancelled_at'] = null;
            }

            $job->update($changes);
            $this->log($job, $from, $to, $user, $data['remarks'] ?? null, $isOverride);

            // Completing the work bills the customer on 30-day terms (same rule as before)
            if (in_array($to, RepairJob::BILLED_STATUSES, true) && ! $job->receivable()->exists()) {
                Receivable::create([
                    'repair_job_id' => $job->id,
                    'customer_id' => $job->customer_id,
                    'amount_due' => $job->total_amount,
                    'balance' => $job->total_amount,
                    'due_date' => now()->addDays(30),
                    'status' => 'unpaid',
                ]);
                $billed = true;
            }
        });

        $label = $this->label($to);

        return back()->with('success', $billed
            ? "Job marked {$label}. The customer has been billed (receivable created, due in 30 days)."
            : "Job status updated to {$label}.");
    }

    // Add a part used during the repair (deducts stock)
    public function storePart(Request $request, RepairJob $repair)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity_used' => 'required|integer|min:1',
        ]);

        if (! $repair->canBeWorkedOnBy($request->user())) {
            abort(403, 'Only the assigned technician, the Technical Head, or the Operational Manager can add parts to this job.');
        }

        if ($repair->isLocked()) {
            throw ValidationException::withMessages([
                'product_id' => "Parts can no longer be added: the job is {$this->label($repair->status)}.",
            ]);
        }

        DB::transaction(function () use ($repair, $data, $request) {
            $product = Product::whereKey($data['product_id'])->lockForUpdate()->first();

            if ($product->is_serialized) {
                throw ValidationException::withMessages([
                    'product_id' => "{$product->name} is tracked by serial number and cannot be used as a repair part here.",
                ]);
            }

            if ($data['quantity_used'] > $product->stock_qty) {
                throw ValidationException::withMessages([
                    'quantity_used' => "Not enough stock for {$product->name}. Only {$product->stock_qty} available.",
                ]);
            }

            RepairPartUsed::create([
                'repair_job_id' => $repair->id,
                'product_id' => $product->id,
                'quantity_used' => $data['quantity_used'],
                'unit_cost' => $product->selling_price, // charged to customer
                'subtotal' => $data['quantity_used'] * $product->selling_price,
            ]);

            $product->decrement('stock_qty', $data['quantity_used']);

            InventoryMovement::create([
                'product_id' => $product->id,
                'movement_type' => 'out',
                'quantity' => $data['quantity_used'],
                'reference_type' => 'repair_job',
                'reference_id' => $repair->id,
                'remarks' => "Used in {$repair->job_no}",
                'movement_date' => now(),
                'recorded_by' => $request->user()->id,
            ]);

            $repair->recalculateTotals();
        });

        return back()->with('success', 'Part added and stock deducted.');
    }

    /** Add serial-numbered units to an existing job (e.g. serial was read after opening the unit). */
    public function storeUnits(Request $request, RepairJob $repair)
    {
        $data = $request->validate([
            'serials' => 'required|string|max:5000',
            'warranty_start' => 'nullable|date',
            'warranty_end' => 'nullable|date|after_or_equal:warranty_start',
        ]);

        if (! $repair->canBeWorkedOnBy($request->user())) {
            abort(403, 'You cannot update this job.');
        }

        if ($repair->isClosed()) {
            return back()->with('error', "Serial numbers cannot be added to a {$this->label($repair->status)} job.");
        }

        $serials = ProductUnit::parseList($data['serials']);
        $existing = $repair->serialUnits()->pluck('serial_no')->all();
        $this->validateSerials($serials, $repair->units - count($existing), $existing);

        DB::transaction(function () use ($repair, $serials, $data) {
            foreach ($serials as $serial) {
                $this->attachUnit($repair, $serial, $data['warranty_start'] ?? null, $data['warranty_end'] ?? null);
            }
        });

        return back()->with('success', count($serials).' serial number(s) recorded.');
    }

    /**
     * JSON lookup used by the New Repair Job form: shows whether a serial is one we sold,
     * its warranty, and its previous repairs.
     */
    public function serialLookup(Request $request)
    {
        $serial = ProductUnit::normalize($request->query('serial'));

        if ($serial === '') {
            return response()->json(['found' => false]);
        }

        $unit = ProductUnit::with('product', 'saleItem.sale.customer')->where('serial_no', $serial)->first();

        $history = RepairJobUnit::with('repairJob')
            ->where('serial_no', $serial)
            ->latest('id')
            ->take(5)
            ->get()
            ->map(fn ($u) => [
                'job_no' => $u->repairJob->job_no,
                'date' => $u->repairJob->date_received->format('M d, Y'),
                'status' => Badge::label('repair', $u->repairJob->status),
                'url' => route('repairs.show', $u->repairJob),
            ]);

        if (! $unit) {
            return response()->json([
                'found' => false,
                'serial' => $serial,
                'history' => $history,
            ]);
        }

        $status = $unit->warrantyStatus();

        return response()->json([
            'found' => true,
            'serial' => $serial,
            'product' => $unit->product->name,
            'unit_status' => Badge::label('unit', $unit->status),
            'sold_to' => $unit->saleItem?->sale?->customer?->name,
            'invoice_no' => $unit->saleItem?->sale?->invoice_no,
            'warranty_status' => $status,
            'warranty_label' => Badge::label('warranty', $status),
            'warranty_tone' => Badge::resolve('warranty', $status)[1],
            'warranty_start' => $unit->warranty_start?->format('M d, Y'),
            'warranty_end' => $unit->warranty_end?->format('M d, Y'),
            'remaining' => Warranty::remainingText($unit->warranty_end),
            'history' => $history,
        ]);
    }

    // ------------------------------------------------------------------ helpers

    /** Active technicians (and the Technical Head) to choose from — never free text. */
    private function technicians(bool $withInactive = false)
    {
        return User::whereIn('role', RepairJob::TECHNICIAN_ROLES)
            ->when(! $withInactive, fn ($q) => $q->where('status', 'active'))
            ->orderBy('name')
            ->get();
    }

    private function label(string $status): string
    {
        return Badge::label('repair', $status);
    }

    private function guardTransition(RepairJob $job, string $to, bool $override, ?string $remarks, User $user): void
    {
        $from = $job->status;
        $fail = fn (string $msg, string $field = 'status') => throw ValidationException::withMessages([$field => $msg]);

        if ($from === $to) {
            $fail("The job is already {$this->label($to)}.");
        }

        if (! $job->canMoveTo($to)) {
            if (! $override) {
                $allowed = collect($job->nextStatuses())->map(fn ($s) => $this->label($s))->implode(', ') ?: 'none';
                $fail("{$this->label($from)} cannot move directly to {$this->label($to)}. Allowed next status: {$allowed}.");
            }
            if (! $user->hasRole(...RepairJob::SUPERVISOR_ROLES)) {
                abort(403, 'Only the Technical Head or Operational Manager can override the workflow.');
            }
            if (blank($remarks)) {
                $fail('Remarks are required when overriding the workflow. Explain why the step is skipped.', 'remarks');
            }
            // A billed job cannot go back into the workshop or be cancelled — its receivable already exists
            if ($job->receivable()->exists() && ! in_array($to, RepairJob::BILLED_STATUSES, true)) {
                $fail('This job has already been billed. It cannot be moved back or cancelled because a receivable exists.');
            }
        }

        if ($to === 'cancelled' && blank($remarks)) {
            $fail('Please give the reason for cancelling the job.', 'remarks');
        }

        // Work cannot start until somebody is responsible for it
        if ($to === 'in_progress' && ! $job->assigned_to) {
            $fail('Assign a technician before starting the job.');
        }
    }

    private function assignTo(RepairJob $job, User $technician, User $by, ?string $expectedDate, ?string $remarks): void
    {
        $now = now();

        // Close the current assignment so the history shows when responsibility changed
        $job->assignments()->whereNull('ended_at')->update(['ended_at' => $now]);

        RepairJobAssignment::create([
            'repair_job_id' => $job->id,
            'technician_id' => $technician->id,
            'assigned_by' => $by->id,
            'assigned_at' => $now,
            'expected_completion_date' => $expectedDate,
            'remarks' => $remarks,
        ]);

        $job->update([
            'assigned_to' => $technician->id,
            'assigned_by' => $by->id,
            'assigned_at' => $now,
            'target_completion_date' => $expectedDate ?? $job->target_completion_date,
        ]);
    }

    private function log(RepairJob $job, ?string $from, string $to, User $user, ?string $remarks, bool $override = false): void
    {
        RepairJobStatusLog::create([
            'repair_job_id' => $job->id,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $user->id,
            'remarks' => $remarks,
            'is_override' => $override,
            'changed_at' => now(),
        ]);
    }

    private function attachUnit(RepairJob $job, string $serial, ?string $warrantyStart, ?string $warrantyEnd): void
    {
        $unit = ProductUnit::where('serial_no', $serial)->first();

        // A unit we sold carries its own warranty; otherwise use the customer's warranty card dates
        RepairJobUnit::create([
            'repair_job_id' => $job->id,
            'serial_no' => $serial,
            'product_unit_id' => $unit?->id,
            'warranty_start' => $unit ? $unit->warranty_start : $warrantyStart,
            'warranty_end' => $unit ? $unit->warranty_end : $warrantyEnd,
        ]);
    }

    private function validateSerials(array $serials, int $maxUnits, array $existing = []): void
    {
        if (count($serials) !== count(array_unique($serials))) {
            throw ValidationException::withMessages(['serials' => 'The same serial number is listed more than once.']);
        }
        if ($dupe = array_intersect($serials, $existing)) {
            throw ValidationException::withMessages(['serials' => 'Already recorded on this job: '.implode(', ', $dupe).'.']);
        }
        if (count($serials) > $maxUnits) {
            throw ValidationException::withMessages([
                'serials' => 'You listed '.count($serials)." serial number(s) but only {$maxUnits} more unit(s) can be recorded on this job.",
            ]);
        }
        foreach ($serials as $serial) {
            if (strlen($serial) > 100) {
                throw ValidationException::withMessages(['serials' => "Serial number {$serial} is too long (max 100 characters)."]);
            }
        }
    }
}