@extends('layouts.admin')

@section('title', 'Repair & Service')

@section('content')
@php
    $input = 'w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500';
    $f = $filters;
    // Summary cards double as one-click filters
    $cards = [
        ['For Assessment',    $stats['for_assessment'], ['status' => 'for_assessment'], 'Received, not yet started', 'clock'],
        ['In Progress',       $stats['in_progress'], ['status' => 'in_progress'],       'Being worked on',         'tool'],
        ['Overdue',           $stats['overdue'],     ['overdue' => 1],                  'Past target date',        'alert-triangle'],
        ['Completed',         $stats['completed'],   ['status' => 'completed'],         'Billed, awaiting release', 'package-export'],
        ['Within Warranty',   $stats['warranty'],    ['warranty' => 'active'],          'Unreleased jobs, unit within warranty period', 'shield-check'],
    ];
    $isCard = fn ($params) => collect($params)->every(fn ($v, $k) => (string) ($f[$k] ?? '') === (string) $v)
        && count(array_filter($f, fn ($v) => $v !== null && $v !== '')) === count($params);
    $chips = ['' => 'All'] + ['open' => 'Open (not finished)', 'finished' => 'Work finished', 'finished_late' => 'Finished after target'] + \App\Models\RepairJob::STATUSES;
@endphp

<div class="flex flex-wrap items-start justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl font-semibold">Repair & Service Management</h1>
        <p class="text-sm text-slate-500">Service fee: ₱350 per unit + parts cost</p>
    </div>
    <button type="button"
            onclick="window.dispatchEvent(new CustomEvent('open-add-repair'))"
            class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">+ New Repair Job</button>
</div>

<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4 mb-6">
    @foreach ($cards as [$label, $value, $params, $note, $icon])
        @php $selected = $isCard($params); @endphp
        <a href="{{ $selected ? route('repairs.index') : route('repairs.index', $params) }}"
           class="kpi-card {{ $selected ? 'kpi-card-active' : '' }}"
           aria-label="{{ $label }}: {{ $value }}. {{ $selected ? 'Showing these jobs; click to clear' : 'Show these jobs' }}">
            <div class="flex items-center justify-between">
                <p class="text-xs text-slate-500">{{ $label }}</p>
                <i class="ti ti-{{ $icon }} text-lg {{ $label === 'Overdue' && $value > 0 ? 'text-red-500' : 'text-slate-300' }}"></i>
            </div>
            <p class="text-2xl font-semibold mt-2 {{ $label === 'Overdue' && $value > 0 ? 'text-red-600' : '' }}">{{ $value }}</p>
            <p class="text-xs text-slate-400 mt-1 flex items-center justify-between gap-2">
                <span class="truncate">{{ $note }}</span>
                <i class="ti ti-arrow-right kpi-arrow"></i>
            </p>
        </a>
    @endforeach
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('repairs.index') }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-4"
      x-data="{ more: {{ ($f['received_from'] ?? $f['received_to'] ?? $f['due_from'] ?? $f['due_to'] ?? null) ? 'true' : 'false' }} }">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
        <div class="lg:col-span-4">
            <label for="f-q" class="block text-xs font-medium text-slate-500 mb-1">Search</label>
            <div class="relative">
                <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input id="f-q" type="search" name="q" value="{{ $f['q'] ?? '' }}"
                       placeholder="Job no., customer, unit model, or serial no."
                       class="{{ $input }} pl-9">
            </div>
        </div>
        <div class="lg:col-span-2">
            <label for="f-status" class="block text-xs font-medium text-slate-500 mb-1">Status</label>
            <select id="f-status" name="status" class="{{ $input }}">
                @foreach ($chips as $value => $label)
                    <option value="{{ $value }}" @selected(($f['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="lg:col-span-2">
            <label for="f-tech" class="block text-xs font-medium text-slate-500 mb-1">Assigned technician</label>
            <select id="f-tech" name="technician" class="{{ $input }}">
                <option value="">Anyone</option>
                <option value="unassigned" @selected(($f['technician'] ?? '') === 'unassigned')>Not yet assigned</option>
                @foreach ($technicians as $tech)
                    <option value="{{ $tech->id }}" @selected((string) ($f['technician'] ?? '') === (string) $tech->id)>
                        {{ $tech->name }}{{ $tech->isActive() ? '' : ' (disabled)' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="lg:col-span-2 flex flex-wrap gap-x-4 gap-y-2 pb-2">
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="overdue" value="1" @checked(! empty($f['overdue'])) class="rounded border-slate-300 text-slate-700 focus:ring-slate-500">
                Overdue only
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="warranty" value="active" @checked(($f['warranty'] ?? '') === 'active') class="rounded border-slate-300 text-slate-700 focus:ring-slate-500">
                Within warranty
            </label>
        </div>
        <div class="lg:col-span-2 flex gap-2">
            <button class="flex-1 px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Apply</button>
            <a href="{{ route('repairs.index') }}" class="flex-1 text-center px-4 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Reset</a>
        </div>
    </div>

    <button type="button" @click="more = !more" class="mt-3 text-xs font-medium text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
        <i class="ti" :class="more ? 'ti-chevron-up' : 'ti-chevron-down'"></i> Date filters
    </button>
    <div x-show="more" x-transition class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-3" style="{{ ($f['received_from'] ?? $f['received_to'] ?? $f['due_from'] ?? $f['due_to'] ?? null) ? '' : 'display:none' }}">
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Received from</label>
            <input type="date" name="received_from" value="{{ $f['received_from'] ?? '' }}" class="{{ $input }}">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Received to</label>
            <input type="date" name="received_to" value="{{ $f['received_to'] ?? '' }}" class="{{ $input }}">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Target completion from</label>
            <input type="date" name="due_from" value="{{ $f['due_from'] ?? '' }}" class="{{ $input }}">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Target completion to</label>
            <input type="date" name="due_to" value="{{ $f['due_to'] ?? '' }}" class="{{ $input }}">
        </div>
    </div>
</form>

<div class="flex flex-wrap items-center justify-between gap-2 mb-2 text-sm text-slate-500">
    <p>
        @if ($jobs->total())
            Showing {{ $jobs->firstItem() }}–{{ $jobs->lastItem() }} of {{ $jobs->total() }} job(s)
        @else
            No jobs to show
        @endif
        @if ($hasFilters) <span class="text-slate-400">· filtered</span> @endif
    </p>
    <p class="text-xs text-slate-400">Overdue jobs are listed first. A job is overdue when its work is unfinished after its target date.</p>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm [&_td]:whitespace-nowrap">
        <thead class="text-xs uppercase text-slate-400 border-b border-slate-100">
            <tr>
                <th class="px-4 py-3 text-left">Job No.</th>
                <th class="px-4 py-3 text-left">Received</th>
                <th class="px-4 py-3 text-left">Customer</th>
                <th class="px-4 py-3 text-left">Unit</th>
                <th class="px-4 py-3 text-left">Technician</th>
                <th class="px-4 py-3 text-left">Target / Timeframe</th>
                <th class="px-4 py-3 text-center">Warranty</th>
                <th class="px-4 py-3 text-right">Total</th>
                <th class="px-4 py-3 text-center">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($jobs as $job)
                @php $schedule = $job->scheduleStatus(); @endphp
                <tr class="border-b border-slate-50 hover:bg-slate-50 {{ $schedule === 'overdue' ? 'bg-red-50/40' : '' }}">
                    <td class="px-4 py-3 font-medium">
                        <a href="{{ route('repairs.show', $job) }}" class="hover:underline">{{ $job->job_no }}</a>
                    </td>
                    <td class="px-4 py-3 text-slate-500">
                        {{ $job->receivedAt()->format('M d, Y') }}
                        <span class="block text-xs text-slate-400">{{ $job->hasReceivedTime() ? $job->receivedAt()->format('h:i A') : 'Time not recorded' }}</span>
                    </td>
                    <td class="px-4 py-3 !whitespace-normal min-w-[9rem]">{{ $job->customer->name }}</td>
                    <td class="px-4 py-3">
                        {{ $job->unit_model }} <span class="text-slate-400">× {{ $job->units }}</span>
                        @if ($job->serialUnits->isNotEmpty())
                            <span class="block text-xs text-slate-400 font-mono">
                                SN {{ $job->serialUnits->pluck('serial_no')->take(2)->implode(', ') }}{{ $job->serialUnits->count() > 2 ? ' +' . ($job->serialUnits->count() - 2) : '' }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if ($job->technician)
                            {{ $job->technician->name }}
                        @else
                            <span class="text-slate-400 italic">Not assigned</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-slate-700">{{ $job->target_completion_date?->format('M d, Y') ?? '—' }}</span>
                        <span class="flex items-center gap-2 mt-0.5">
                            <x-badge type="schedule" :value="$schedule" />
                            @if ($job->elapsed())
                                <span class="text-xs text-slate-400" title="{{ $job->completed_at ? 'Received → completed' : 'Elapsed since received' }}">{{ $job->elapsed() }}</span>
                            @endif
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <x-badge type="warranty" :value="$job->warrantyStatus()" />
                    </td>
                    <td class="px-4 py-3 text-right font-medium">₱{{ number_format($job->total_amount, 2) }}</td>
                    <td class="px-4 py-3 text-center">
                        <x-badge type="repair" :value="$job->status" />
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('repairs.show', $job) }}" class="btn-action">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="px-4 py-12 text-center text-slate-400">
                        <i class="ti ti-tool text-3xl block mb-2 text-slate-300"></i>
                        @if ($hasFilters)
                            No repair jobs match these filters.
                            <a href="{{ route('repairs.index') }}" class="block mt-2 text-slate-600 underline">Clear filters</a>
                        @else
                            No repair jobs yet.
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $jobs->links() }}
</div>

@include('repairs._add-modal')
@endsection