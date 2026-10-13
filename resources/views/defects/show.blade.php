@extends('layouts.admin')

@section('title', 'Factory Defect')

@section('content')
@php
    use App\Models\FactoryDefect;
    use App\Support\Badge;

    $input = 'w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500';
    $next = $defect->nextStatuses();
    $inspecting = in_array($defect->status, ['reported', 'under_inspection'], true);
    $customer = $defect->repairJob?->customer ?? $defect->unit?->saleItem?->sale?->customer;
@endphp

<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-xl font-semibold">{{ $defect->defect_no }}</h1>
            <x-badge type="defect" :value="$defect->status" size="md" />
            <x-badge type="cause" :value="$defect->cause" size="md" />
        </div>
        <p class="text-sm text-slate-500 mt-1">{{ $defect->product->name }}{{ $defect->serial_no ? ' · SN ' . $defect->serial_no : '' }}</p>
    </div>
    <a href="{{ route('defects.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Back</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6 min-w-0">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="font-semibold mb-4">Report</h2>
            <dl class="text-sm divide-y divide-slate-100">
                <div class="detail-row"><dt>Product</dt><dd>
                    @if (auth()->user()->hasRole('admin', 'staff'))
                        <a href="{{ route('inventory.edit', $defect->product) }}" class="underline">{{ $defect->product->name }}</a>
                    @else
                        {{ $defect->product->name }}
                    @endif
                </dd></div>
                <div class="detail-row"><dt>Serial number</dt><dd class="font-mono">{{ $defect->serial_no ?? '—' }}</dd></div>
                @if ($defect->unit)
                    <div class="detail-row"><dt>Unit status</dt><dd><x-badge type="unit" :value="$defect->unit->status" /></dd></div>
                @endif
                <div class="detail-row"><dt>Source</dt><dd>{{ $defect->source === 'inventory' ? 'Our stock' : 'Customer unit' }}</dd></div>
                <div class="detail-row"><dt>Quantity</dt><dd>{{ $defect->quantity }}</dd></div>
                @if ($defect->repairJob)
                    <div class="detail-row"><dt>Repair job</dt><dd>
                        @if (auth()->user()->hasRole('admin', 'technical_head', 'technician'))
                            <a href="{{ route('repairs.show', $defect->repairJob) }}" class="underline">{{ $defect->repairJob->job_no }}</a>
                        @else
                            {{ $defect->repairJob->job_no }}
                        @endif
                    </dd></div>
                @endif
                <div class="detail-row"><dt>Customer</dt><dd>{{ $customer?->name ?? '—' }}</dd></div>
                <div class="detail-row"><dt>Supplier</dt><dd>{{ $defect->supplier?->name ?? 'Not specified' }}</dd></div>
                <div class="detail-row"><dt>Date discovered</dt><dd>{{ $defect->date_discovered->format('M d, Y') }}</dd></div>
                <div class="detail-row"><dt>Reported by</dt><dd>{{ $defect->reporter->name }} · {{ $defect->created_at->format('M d, Y h:i A') }}</dd></div>
                <div class="detail-row"><dt>Stock held out of sale</dt><dd>{{ $defect->stock_quarantined ? 'Yes — ' . $defect->quantity . ' unit(s)' : 'No' }}</dd></div>
            </dl>
            <div class="mt-4 pt-4 border-t border-slate-100">
                <p class="text-xs uppercase text-slate-400 mb-1">Description</p>
                <p class="text-sm text-slate-700 whitespace-pre-line">{{ $defect->description }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="font-semibold mb-4">Inspection & Action</h2>
            <dl class="text-sm divide-y divide-slate-100">
                <div class="detail-row"><dt>Cause</dt><dd><x-badge type="cause" :value="$defect->cause" /></dd></div>
                <div class="detail-row"><dt>Inspected by</dt><dd>{{ $defect->inspector?->name ?? 'Not yet inspected' }}</dd></div>
                <div class="detail-row"><dt>Resolved</dt><dd>{{ $defect->resolved_at?->format('M d, Y h:i A') ?? '—' }}</dd></div>
            </dl>
            <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs uppercase text-slate-400 mb-1">Inspection findings</p>
                    <p class="text-sm text-slate-700 whitespace-pre-line">{{ $defect->inspection_findings ?: 'None recorded yet.' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase text-slate-400 mb-1">Action taken</p>
                    <p class="text-sm text-slate-700 whitespace-pre-line">{{ $defect->action_taken ?: 'None recorded yet.' }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="font-semibold mb-4">History</h2>
            <ol class="relative border-l border-slate-200 ml-2 space-y-5">
                @foreach ($defect->logs as $log)
                    <li class="ml-5">
                        <span class="absolute -left-[7px] mt-1.5 w-3.5 h-3.5 rounded-full border-2 border-white {{ $loop->first ? 'bg-slate-700' : 'bg-slate-300' }}"></span>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($log->from_status && $log->from_status !== $log->to_status)
                                <x-badge type="defect" :value="$log->from_status" />
                                <i class="ti ti-arrow-right text-slate-400"></i>
                            @endif
                            <x-badge type="defect" :value="$log->to_status" />
                        </div>
                        <p class="text-xs text-slate-500 mt-1">{{ $log->changed_at->format('M d, Y h:i A') }} · {{ $log->user?->name ?? 'System' }}</p>
                        @if ($log->remarks)<p class="text-sm text-slate-600 mt-1">“{{ $log->remarks }}”</p>@endif
                    </li>
                @endforeach
            </ol>
        </div>
    </div>

    <div class="space-y-6 min-w-0">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="font-semibold mb-3">Update Assessment</h2>

            @if (! $canAssess)
                <p class="text-sm text-slate-500">Only the Technical Head or the Operational Manager can inspect and update defect reports.</p>
            @else
                <form method="POST" action="{{ route('defects.status', $defect) }}"
                      x-data="{ status: '{{ old('status', $next[0] ?? $defect->status) }}', cause: '{{ old('cause', $defect->cause) }}', labels: @js(Badge::options('defect')) }"
                      :data-confirm="status === '{{ $defect->status }}' ? 'Save the updated findings and action notes?' : `Change {{ $defect->defect_no }} to ${labels[status]}?` + (status === 'confirmed' && '{{ $defect->source }}' === 'inventory' ? '\n\nThe item will be removed from sellable stock.' : '') + (status === 'repaired' && {{ $defect->stock_quarantined ? 'true' : 'false' }} ? '\n\nThe item will be returned to stock.' : '')"
                      data-confirm-title="Update defect report"
                      data-confirm-button="Yes, save"
                      :data-confirm-tone="status === 'confirmed' ? 'warning' : 'primary'">
                    @csrf
                    @method('PATCH')

                    <label class="block text-sm font-medium mb-1">Status</label>
                    <select name="status" x-model="status" class="{{ $input }}">
                        @foreach ($next as $s)
                            <option value="{{ $s }}">{{ FactoryDefect::STATUSES[$s] }}</option>
                        @endforeach
                        <option value="{{ $defect->status }}">Keep as {{ FactoryDefect::STATUSES[$defect->status] }} (update notes only)</option>
                    </select>
                    @error('status') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

                    @if ($inspecting)
                        <label class="block text-sm font-medium mb-1 mt-3">Cause found at inspection</label>
                        <select name="cause" x-model="cause" class="{{ $input }}">
                            @foreach (FactoryDefect::CAUSES as $v => $l)
                                <option value="{{ $v }}">{{ $l }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-slate-400 mt-1">"Confirmed Factory Defect" needs cause = Factory Defect. Any other cause closes the report as Resolved.</p>
                        @error('cause') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    @endif

                    <label class="block text-sm font-medium mb-1 mt-3">Inspection findings</label>
                    <textarea name="inspection_findings" rows="3" maxlength="2000" class="{{ $input }}">{{ old('inspection_findings', $defect->inspection_findings) }}</textarea>
                    @error('inspection_findings') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

                    <label class="block text-sm font-medium mb-1 mt-3">Action taken</label>
                    <textarea name="action_taken" rows="2" maxlength="2000" placeholder="e.g. Sent back to supplier under RMA #123" class="{{ $input }}">{{ old('action_taken', $defect->action_taken) }}</textarea>
                    @error('action_taken') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

                    <div x-show="status === 'returned_to_supplier'">
                        <label class="block text-sm font-medium mb-1 mt-3">Returned to supplier</label>
                        <select name="supplier_id" class="{{ $input }}">
                            <option value="">Select supplier...</option>
                            @foreach ($suppliers as $s)
                                <option value="{{ $s->id }}" @selected(old('supplier_id', $defect->supplier_id) == $s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <label class="block text-sm font-medium mb-1 mt-3">Remarks <span class="font-normal text-slate-400">(for the history)</span></label>
                    <input type="text" name="remarks" maxlength="500" value="{{ old('remarks') }}" class="{{ $input }}">

                    <button class="w-full mt-4 px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Save</button>
                </form>
            @endif

            <div class="mt-4 pt-4 border-t border-slate-100 text-xs text-slate-400 space-y-1">
                <p class="font-medium text-slate-500">Process</p>
                <p>Reported → Under Inspection → Confirmed Factory Defect → Returned to Supplier / Repaired / Replaced → Resolved</p>
                <p>If inspection finds another cause (damage, wear and tear), the report is closed as Resolved.</p>
                <p>A replacement unit from the supplier is received through Inventory → Stock In.</p>
            </div>
        </div>
    </div>
</div>
@endsection