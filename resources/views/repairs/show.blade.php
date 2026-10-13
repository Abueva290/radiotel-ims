@extends('layouts.admin')

@section('title', 'Repair Job')

@section('content')
@php
    use App\Models\RepairJob;
    use App\Support\Badge;
    use App\Support\Warranty;

    $input = 'w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500';
    $schedule = $job->scheduleStatus();
    $next = $job->nextStatuses();
    $statusLabels = Badge::options('repair');
    $late = $job->daysPastTarget();
    $scheduleNote = match ($schedule) {
        'overdue'          => $late . ' ' . str('day')->plural($late) . ' past the target date',
        'due_soon'         => $late === 0 ? 'Due today' : 'Due in ' . abs($late) . ' ' . str('day')->plural(abs($late)),
        'on_schedule'      => abs($late) . ' days left before the target date',
        'finished_late'    => 'Work finished after the target date',
        'finished_on_time' => 'Work finished on or before the target date',
        'cancelled'        => 'Job was cancelled',
        'not_recorded'     => 'Finish time was not recorded (job finished before tracking started)',
        default            => 'No target date set',
    };
    $billed = $job->receivable !== null;
    // Shown once the Factory Defects module (routes) exists
    $defectUrl = \Illuminate\Support\Facades\Route::has('defects.create')
        ? route('defects.create', ['repair_job_id' => $job->id, 'serial_no' => $job->serialUnits->first()?->serial_no])
        : null;
@endphp

<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-xl font-semibold">{{ $job->job_no }}</h1>
            <x-badge type="repair" :value="$job->status" size="md" />
            <x-badge type="schedule" :value="$schedule" size="md" />
            <x-badge type="warranty" :value="$job->warrantyStatus()" size="md" />
        </div>
        <p class="text-sm text-slate-500 mt-1">
            {{ $job->customer->name }} · {{ $job->unit_model }} ({{ $job->units }} unit{{ $job->units > 1 ? 's' : '' }})
        </p>
    </div>
    <div class="flex gap-2">
        @if ($defectUrl)
            <a href="{{ $defectUrl }}" class="px-4 py-2 rounded-lg bg-white border border-slate-200 text-sm hover:bg-slate-50">
                <i class="ti ti-alert-octagon"></i> Report Factory Defect
            </a>
        @endif
        <a href="{{ route('repairs.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Back</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6 min-w-0">

        {{-- Service timeframe --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <h2 class="font-semibold">Service Timeframe</h2>
                <p class="text-sm {{ $schedule === 'overdue' ? 'text-red-600 font-medium' : ($schedule === 'due_soon' ? 'text-amber-700' : 'text-slate-500') }}">{{ $scheduleNote }}</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div class="rounded-lg bg-slate-50 p-3">
                    <p class="text-xs text-slate-500">Received</p>
                    <p class="font-medium mt-1">{{ $job->receivedAt()->format('M d, Y') }}</p>
                    <p class="text-xs text-slate-400">{{ $job->hasReceivedTime() ? $job->receivedAt()->format('h:i A') : 'Time not recorded' }}</p>
                </div>
                <div class="rounded-lg p-3 {{ $schedule === 'overdue' ? 'bg-red-50' : 'bg-slate-50' }}">
                    <p class="text-xs text-slate-500">Target completion</p>
                    <p class="font-medium mt-1">{{ $job->target_completion_date?->format('M d, Y') ?? 'Not set' }}</p>
                    <p class="text-xs text-slate-400">
                        {{ $job->plannedTurnaroundDays() !== null ? $job->plannedTurnaroundDays() . '-day turnaround' : '' }}
                    </p>
                </div>
                <div class="rounded-lg bg-slate-50 p-3">
                    <p class="text-xs text-slate-500">Completed (work finished)</p>
                    <p class="font-medium mt-1">{{ $job->completed_at?->format('M d, Y') ?? (in_array($job->status, ['completed', 'released']) ? 'Not recorded' : 'Not yet') }}</p>
                    <p class="text-xs text-slate-400">{{ $job->completed_at?->format('h:i A') }}</p>
                </div>
                <div class="rounded-lg bg-slate-50 p-3">
                    <p class="text-xs text-slate-500">Released / claimed</p>
                    <p class="font-medium mt-1">{{ $job->released_at?->format('M d, Y') ?? ($job->status === 'released' ? 'Not recorded' : 'Not yet') }}</p>
                    <p class="text-xs text-slate-400">{{ $job->released_at?->format('h:i A') }}</p>
                </div>
            </div>

            <dl class="text-sm mt-4 divide-y divide-slate-100">
                <div class="detail-row">
                    <dt>Work started</dt>
                    <dd>{{ $job->started_at?->format('M d, Y h:i A') ?? ($job->status === 'for_assessment' ? 'Not yet started' : 'Not recorded') }}</dd>
                </div>
                <div class="detail-row">
                    <dt>{{ $job->completed_at ? 'Completion duration (received → completed)' : ($job->cancelled_at ? 'Time before cancellation' : 'Elapsed since received') }}</dt>
                    <dd class="font-medium">
                        @if ($job->completed_at || $job->cancelled_at || in_array($job->status, \App\Models\RepairJob::OPEN_STATUSES))
                            {{ $job->completionDuration() ?? $job->elapsed() ?? 'Not recorded' }}{{ $job->hasReceivedTime() ? '' : ' (from date received)' }}
                        @else
                            Not recorded
                        @endif
                    </dd>
                </div>
                @if ($job->cancelled_at)
                    <div class="detail-row"><dt>Cancelled</dt><dd>{{ $job->cancelled_at->format('M d, Y h:i A') }}</dd></div>
                @endif
            </dl>
        </div>

        {{-- Serial numbers and warranty --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-2 px-6 pt-5 pb-3">
                <h2 class="font-semibold">Units, Serial Numbers & Warranty</h2>
                <p class="w-full text-xs text-slate-400 -mt-1">Warranty status shows only whether the unit is within its warranty period. Whether this repair is covered depends on the warranty conditions.</p>
                <p class="text-xs text-slate-400">{{ $job->serialUnits->count() }} of {{ $job->units }} unit(s) have a serial number</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-slate-400 border-y border-slate-100">
                        <tr>
                            <th class="px-6 py-3 text-left">Serial No.</th>
                            <th class="px-6 py-3 text-left">Our Record</th>
                            <th class="px-6 py-3 text-left">Warranty Period</th>
                            <th class="px-6 py-3 text-center">Warranty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($job->serialUnits as $unit)
                            @php $pu = $unit->productUnit; @endphp
                            <tr class="border-b border-slate-50">
                                <td class="px-6 py-3 font-mono font-medium">{{ $unit->serial_no }}</td>
                                <td class="px-6 py-3 text-slate-600">
                                    @if ($pu)
                                        {{ $pu->product->name }}
                                        <span class="block text-xs text-slate-400">
                                            @if ($pu->saleItem?->sale)
                                                Sold by Radiotel · {{ $pu->saleItem->sale->invoice_no }} to {{ $pu->saleItem->sale->customer->name }}
                                            @else
                                                In Radiotel records · {{ \App\Support\Badge::label('unit', $pu->status) }} (not sold)
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-slate-400">Not in Radiotel's sales records</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-slate-600">
                                    @if ($unit->warranty_end)
                                        {{ $unit->warranty_start?->format('M d, Y') ?? '—' }} – {{ $unit->warranty_end->format('M d, Y') }}
                                        @if ($left = Warranty::remainingText($unit->warranty_end))
                                            <span class="block text-xs text-slate-400">{{ $left }}</span>
                                        @endif
                                    @else
                                        <span class="text-slate-400">No warranty information</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-center"><x-badge type="warranty" :value="$unit->warrantyStatus()" /></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-6 text-center text-slate-400">No serial numbers recorded for this job.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($canWork && ! $job->isClosed() && $job->serialUnits->count() < $job->units)
                <form method="POST" action="{{ route('repairs.units', $job) }}" x-data="{ open: {{ $errors->has('serials') ? 'true' : 'false' }} }"
                      class="border-t border-slate-100 px-6 py-4">
                    @csrf
                    <button type="button" @click="open = !open" class="text-sm font-medium text-slate-600 hover:text-slate-900">
                        <i class="ti ti-plus"></i> Add serial number(s)
                    </button>
                    <div x-show="open" x-transition class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3" style="{{ $errors->has('serials') ? '' : 'display:none' }}">
                        <div class="sm:col-span-3">
                            <textarea name="serials" rows="2" placeholder="One serial number per line" class="{{ $input }} font-mono uppercase">{{ old('serials') }}</textarea>
                            @error('serials') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500 mb-1">Warranty start (card)</label>
                            <input type="date" name="warranty_start" class="{{ $input }}">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500 mb-1">Warranty end (card)</label>
                            <input type="date" name="warranty_end" class="{{ $input }}">
                        </div>
                        <div class="flex items-end">
                            <button class="w-full px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Save Serials</button>
                        </div>
                    </div>
                </form>
            @endif
        </div>

        {{-- Charge breakdown --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="font-semibold mb-4">Charge Computation</h2>
            <dl class="text-sm space-y-2">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Service fee ({{ $job->units }} × ₱{{ number_format(RepairJob::SERVICE_FEE_PER_UNIT, 2) }})</dt>
                    <dd>₱{{ number_format($job->service_fee, 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Parts cost</dt>
                    <dd>₱{{ number_format($job->parts_cost, 2) }}</dd>
                </div>
                <div class="flex justify-between pt-2 border-t border-slate-100 text-base">
                    <dt class="font-medium">Total</dt>
                    <dd class="font-semibold">₱{{ number_format($job->total_amount, 2) }}</dd>
                </div>
            </dl>

            @if ($job->assessment_notes)
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <p class="text-xs uppercase text-slate-400 mb-1">Assessment Notes</p>
                    <p class="text-sm text-slate-600">{{ $job->assessment_notes }}</p>
                </div>
            @endif
        </div>

        {{-- Parts used --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
            <h2 class="font-semibold px-6 pt-5 pb-3">Parts Used</h2>
            <table class="w-full text-sm">
                <thead class="text-xs uppercase text-slate-400 border-y border-slate-100">
                    <tr>
                        <th class="px-6 py-3 text-left">Part</th>
                        <th class="px-6 py-3 text-center">Qty</th>
                        <th class="px-6 py-3 text-right">Unit Price</th>
                        <th class="px-6 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($job->parts as $part)
                        <tr class="border-b border-slate-50">
                            <td class="px-6 py-3">{{ $part->product->name }}</td>
                            <td class="px-6 py-3 text-center">{{ $part->quantity_used }}</td>
                            <td class="px-6 py-3 text-right">₱{{ number_format($part->unit_cost, 2) }}</td>
                            <td class="px-6 py-3 text-right font-medium">₱{{ number_format($part->subtotal, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-6 text-center text-slate-400">No parts used yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Status history --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="font-semibold mb-4">Status History</h2>
            <ol class="relative border-l border-slate-200 ml-2 space-y-5">
                @forelse ($job->statusLogs as $log)
                    <li class="ml-5">
                        <span class="absolute -left-[7px] mt-1.5 w-3.5 h-3.5 rounded-full border-2 border-white {{ $loop->first ? 'bg-slate-700' : 'bg-slate-300' }}"></span>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($log->from_status)
                                <x-badge type="repair" :value="$log->from_status" />
                                <i class="ti ti-arrow-right text-slate-400"></i>
                            @endif
                            <x-badge type="repair" :value="$log->to_status" />
                            @if ($log->is_override)
                                <x-badge tone="red">Workflow override</x-badge>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            {{ $log->changed_at->format('M d, Y h:i A') }} · {{ $log->user?->name ?? 'System' }}
                        </p>
                        @if ($log->remarks)
                            <p class="text-sm text-slate-600 mt-1">“{{ $log->remarks }}”</p>
                        @endif
                    </li>
                @empty
                    <li class="ml-5 text-sm text-slate-400">No status changes recorded yet.</li>
                @endforelse
            </ol>
        </div>
    </div>

    <div class="space-y-6 min-w-0">
        {{-- Status update --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="font-semibold mb-3">Job Status</h2>
            <p class="mb-4"><x-badge type="repair" :value="$job->status" size="md" /></p>

            @if (! $canWork)
                <p class="text-sm text-slate-500">
                    Only the assigned technician, the Technical Head, or the Operational Manager can update this job.
                </p>
            @elseif (empty($next) && ! $isSupervisor)
                <p class="text-sm text-slate-500">This job is closed. No further status changes are allowed.</p>
            @else
                <form method="POST" action="{{ route('repairs.status', $job) }}"
                      x-data="{
                          status: '{{ old('status') }}',
                          override: {{ old('override') ? 'true' : 'false' }},
                          labels: @js($statusLabels),
                          next: @js($next),
                          get options() { return this.override ? Object.keys(this.labels).filter(s => s !== '{{ $job->status }}') : this.next },
                      }"
                      :data-confirm="status ? `Change {{ $job->job_no }} from {{ $statusLabels[$job->status] }} to ${labels[status]}?` + ((status === 'completed' || status === 'released') && {{ $billed ? 'false' : 'true' }} ? '\n\nThis bills the customer: a receivable of ₱{{ number_format($job->total_amount, 2) }} due in 30 days will be created.' : '') + (status === 'cancelled' ? '\n\nA cancelled job is closed and cannot continue.' : '') + (override ? '\n\nThis skips the normal workflow and will be recorded as an override.' : '') : 'Choose a status first.'"
                      data-confirm-title="Update job status"
                      :data-confirm-button="status === 'cancelled' ? 'Yes, cancel job' : 'Yes, update status'"
                      :data-confirm-tone="status === 'cancelled' || override ? 'danger' : 'primary'">
                    @csrf
                    @method('PATCH')

                    <label for="new-status" class="block text-sm font-medium mb-1">Move to</label>
                    <select id="new-status" name="status" x-model="status" required class="{{ $input }}">
                        <option value="">Select next status...</option>
                        <template x-for="s in options" :key="s">
                            <option :value="s" x-text="labels[s]" :selected="s === status"></option>
                        </template>
                    </select>
                    @error('status') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

                    @if (empty($next))
                        <p class="text-xs text-slate-500 mt-2">The normal workflow has ended for this job.</p>
                    @endif

                    <label for="status-remarks" class="block text-sm font-medium mb-1 mt-3">
                        Remarks <span class="font-normal text-slate-400" x-text="status === 'cancelled' || override ? '(required)' : '(optional)'"></span>
                    </label>
                    <textarea id="status-remarks" name="remarks" rows="2" maxlength="500" :required="status === 'cancelled' || override"
                              placeholder="e.g. Replaced PA module, passed bench test" class="{{ $input }}">{{ old('remarks') }}</textarea>
                    @error('remarks') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

                    @if ($isSupervisor)
                        <label class="flex items-start gap-2 mt-3 text-sm text-slate-600">
                            <input type="checkbox" name="override" value="1" x-model="override" @change="status = ''"
                                   class="mt-0.5 rounded border-slate-300 text-red-600 focus:ring-red-500">
                            <span>Override workflow <span class="block text-xs text-slate-400">Allow any status. Use only to correct mistakes; recorded in the history.</span></span>
                        </label>
                    @endif

                    <button class="w-full mt-4 px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Update Status</button>
                </form>

                <div class="mt-4 pt-4 border-t border-slate-100 text-xs text-slate-400 space-y-1">
                    <p class="font-medium text-slate-500">Workflow</p>
                    <p>For Assessment → In Progress → Completed → Released</p>
                    <p>In Progress ⇄ Waiting for Parts · Cancel only before Completed</p>
                    <p>Completed bills the customer (30-day terms). A job must be Completed before it can be Released.</p>
                </div>
            @endif
        </div>

        {{-- Assignment --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="font-semibold mb-3">Assigned Technician</h2>

            @if ($job->technician)
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-slate-700 text-white flex items-center justify-center text-sm font-semibold shrink-0">
                        {{ collect(explode(' ', $job->technician->name))->map(fn ($w) => $w[0])->take(2)->implode('') }}
                    </div>
                    <div class="min-w-0">
                        <p class="font-medium truncate">{{ $job->technician->name }}</p>
                        <p class="text-xs text-slate-500">{{ \App\Models\User::ROLES[$job->technician->role] ?? '' }}</p>
                    </div>
                </div>
                <dl class="text-sm mt-4 divide-y divide-slate-100">
                    <div class="detail-row"><dt>Assigned by</dt><dd>{{ $job->assigner?->name ?? '—' }}</dd></div>
                    <div class="detail-row"><dt>Assigned on</dt><dd>{{ $job->assigned_at?->format('M d, Y h:i A') ?? '—' }}</dd></div>
                    <div class="detail-row"><dt>Expected completion</dt><dd>{{ $job->target_completion_date?->format('M d, Y') ?? 'Not set' }}</dd></div>
                </dl>
            @else
                <p class="text-sm text-slate-500">No technician assigned yet. The job cannot be started until it is assigned.</p>
            @endif

            @if ($isSupervisor && ! $job->isClosed())
                <form method="POST" action="{{ route('repairs.assign', $job) }}" class="mt-4 pt-4 border-t border-slate-100"
                      x-data="{ tech: '{{ old('technician_id', '') }}', names: @js($technicians->pluck('name', 'id')) }"
                      :data-confirm="tech ? '{{ $job->technician ? 'Reassign ' . $job->job_no . ' from ' . e($job->technician->name) . ' to ' : 'Assign ' . $job->job_no . ' to ' }}' + names[tech] + '?' : 'Choose a technician first.'"
                      data-confirm-title="{{ $job->technician ? 'Reassign job' : 'Assign job' }}"
                      data-confirm-button="{{ $job->technician ? 'Yes, reassign' : 'Yes, assign' }}">
                    @csrf
                    <label for="assign-tech" class="block text-sm font-medium mb-1">{{ $job->technician ? 'Reassign to' : 'Assign to' }}</label>
                    <select id="assign-tech" name="technician_id" x-model="tech" required class="{{ $input }}">
                        <option value="">Select technician...</option>
                        @foreach ($technicians as $tech)
                            <option value="{{ $tech->id }}">{{ $tech->name }} — {{ \App\Models\User::ROLES[$tech->role] }}</option>
                        @endforeach
                    </select>
                    @error('technician_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

                    <label for="assign-date" class="block text-sm font-medium mb-1 mt-3">Expected completion <span class="font-normal text-slate-400">(optional)</span></label>
                    <input id="assign-date" type="date" name="expected_completion_date"
                           min="{{ $job->date_received->toDateString() }}"
                           value="{{ old('expected_completion_date', $job->target_completion_date?->toDateString()) }}" class="{{ $input }}">
                    @error('expected_completion_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

                    <label for="assign-remarks" class="block text-sm font-medium mb-1 mt-3">Remarks <span class="font-normal text-slate-400">(optional)</span></label>
                    <input id="assign-remarks" type="text" name="remarks" maxlength="500" value="{{ old('remarks') }}"
                           placeholder="{{ $job->technician ? 'Reason for reassignment' : '' }}" class="{{ $input }}">

                    <button class="w-full mt-4 px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">
                        {{ $job->technician ? 'Reassign' : 'Assign' }}
                    </button>
                </form>
            @endif

            @if ($job->assignments->isNotEmpty())
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <p class="text-xs uppercase text-slate-400 mb-2">Assignment History</p>
                    <ul class="space-y-3 text-sm">
                        @foreach ($job->assignments as $a)
                            <li>
                                <p class="flex items-center gap-2">
                                    <span class="font-medium">{{ $a->technician->name }}</span>
                                    @if (! $a->ended_at)<x-badge tone="green">Current</x-badge>@endif
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ $a->assigned_at->format('M d, Y h:i A') }}
                                    {{ $a->ended_at ? '– ' . $a->ended_at->format('M d, Y h:i A') : '– present' }}
                                    · by {{ $a->assigner->name }}
                                </p>
                                @if ($a->expected_completion_date)
                                    <p class="text-xs text-slate-400">Expected completion: {{ $a->expected_completion_date->format('M d, Y') }}</p>
                                @endif
                                @if ($a->remarks)<p class="text-xs text-slate-500 mt-0.5">“{{ $a->remarks }}”</p>@endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- Add part --}}
        @if ($canWork && ! $job->isLocked())
            <form method="POST" action="{{ route('repairs.parts', $job) }}"
                  class="bg-white rounded-xl border border-slate-200 p-6"
                  x-data="{ product: '', qty: {{ (int) old('quantity_used', 1) }}, names: @js($products->pluck('name', 'id')) }"
                  :data-confirm="product ? `Add ${qty} × ${names[product]} to {{ $job->job_no }}?\n\nThe quantity will be deducted from inventory and charged to the customer.` : 'Choose a part first.'"
                  data-confirm-title="Add part used"
                  data-confirm-button="Yes, add part">
                @csrf
                <h2 class="font-semibold mb-3">Add Part Used</h2>

                <label class="block text-sm font-medium mb-1">Part</label>
                <select name="product_id" x-model="product" required class="{{ $input }}">
                    <option value="">Select part...</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->stock_qty }} in stock)</option>
                    @endforeach
                </select>
                @error('product_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

                <label class="block text-sm font-medium mb-1 mt-3">Quantity</label>
                <input type="number" min="1" name="quantity_used" x-model.number="qty" required class="{{ $input }}">
                @error('quantity_used') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

                <button class="w-full mt-4 px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Add Part</button>
                <p class="text-xs text-slate-400 mt-2">Adding a part deducts it from inventory. Serial-tracked items are not listed.</p>
            </form>
        @endif

        {{-- Receivable --}}
        @if ($job->receivable)
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold">Receivable</h2>
                    <x-badge type="payment" :value="Badge::paymentStatus($job->receivable->status, $job->receivable->due_date)" />
                </div>
                <dl class="text-sm space-y-2">
                    <div class="flex justify-between"><dt class="text-slate-500">Balance</dt><dd class="font-semibold">₱{{ number_format($job->receivable->balance, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Due date</dt><dd>{{ $job->receivable->due_date->format('M d, Y') }}</dd></div>
                </dl>
                @if (auth()->user()->hasRole('admin', 'secretary'))
                    <a href="{{ route('receivables.show', $job->receivable) }}"
                       class="block text-center mt-3 px-4 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Record Payment</a>
                @endif
            </div>
        @endif

        {{-- Factory defects linked to this job --}}
        @if ($job->defects->isNotEmpty() && \Illuminate\Support\Facades\Route::has('defects.show'))
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h2 class="font-semibold mb-3">Factory Defect Reports</h2>
                <ul class="space-y-2 text-sm">
                    @foreach ($job->defects as $d)
                        <li class="flex items-center justify-between gap-2">
                            <a href="{{ route('defects.show', $d) }}" class="font-medium hover:underline">{{ $d->defect_no }}</a>
                            <x-badge type="defect" :value="$d->status" />
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Job info --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <dl class="text-sm divide-y divide-slate-100">
                <div class="detail-row"><dt>Date received</dt><dd>{{ $job->receivedAt()->format('M d, Y') }}{{ $job->hasReceivedTime() ? ' ' . $job->receivedAt()->format('h:i A') : '' }}</dd></div>
                <div class="detail-row"><dt>Received / assessed by</dt><dd>{{ $job->assessor?->name ?? '—' }}</dd></div>
            </dl>
        </div>
    </div>
</div>
@endsection