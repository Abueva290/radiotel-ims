@extends('layouts.admin')

@section('title', 'Factory Defects')

@section('content')
@php
    $input = 'w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500';
    $f = $filters;
    $cards = [
        ['Open Cases',            $stats['open'],      ['status' => 'open'],             'Not yet resolved'],
        ['Awaiting Inspection',   $stats['reported'],  ['status' => 'awaiting'], 'Reported or under inspection'],
        ['Confirmed Factory Defects', $stats['confirmed'], ['cause' => 'factory_defect'], 'Cause confirmed at inspection'],
        ['Other Causes Found',    $stats['other'],     ['cause' => 'not_factory'],       'Damage, wear and tear, other'],
    ];
@endphp

<div class="flex flex-wrap items-start justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl font-semibold">Factory Defects</h1>
        <p class="text-sm text-slate-500">Items reported as possibly defective from the factory, their inspection, and how they were resolved</p>
    </div>
    <a href="{{ route('defects.create') }}" class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">+ Report Defect</a>
</div>

<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    @foreach ($cards as [$label, $value, $params, $note])
        <a href="{{ route('defects.index', $params) }}" class="kpi-card" aria-label="{{ $label }}: {{ $value }}">
            <p class="text-xs text-slate-500">{{ $label }}</p>
            <p class="text-2xl font-semibold mt-2">{{ $value }}</p>
            <p class="text-xs text-slate-400 mt-1 flex items-center justify-between gap-2"><span class="truncate">{{ $note }}</span><i class="ti ti-arrow-right kpi-arrow"></i></p>
        </a>
    @endforeach
</div>

<form method="GET" class="bg-white rounded-xl border border-slate-200 p-4 mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
    <div class="lg:col-span-5">
        <label class="block text-xs font-medium text-slate-500 mb-1">Search</label>
        <input type="search" name="q" value="{{ $f['q'] ?? '' }}" placeholder="Defect no., product, serial no., or job no." class="{{ $input }}">
    </div>
    <div class="lg:col-span-3">
        <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
        <select name="status" class="{{ $input }}">
            <option value="">All</option>
            <option value="open" @selected(($f['status'] ?? '') === 'open')>Open (not resolved)</option>
            <option value="awaiting" @selected(($f['status'] ?? '') === 'awaiting')>Awaiting inspection</option>
            @foreach (\App\Models\FactoryDefect::STATUSES as $v => $l)
                <option value="{{ $v }}" @selected(($f['status'] ?? '') === $v)>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <div class="lg:col-span-2">
        <label class="block text-xs font-medium text-slate-500 mb-1">Cause</label>
        <select name="cause" class="{{ $input }}">
            <option value="">All</option>
            <option value="not_factory" @selected(($f['cause'] ?? '') === 'not_factory')>Not a factory defect</option>
            @foreach (\App\Models\FactoryDefect::CAUSES as $v => $l)
                <option value="{{ $v }}" @selected(($f['cause'] ?? '') === $v)>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <div class="lg:col-span-2 flex gap-2">
        <button class="flex-1 px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Apply</button>
        <a href="{{ route('defects.index') }}" class="flex-1 text-center px-4 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Reset</a>
    </div>
</form>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-xs uppercase text-slate-400 border-b border-slate-100">
            <tr>
                <th class="px-4 py-3 text-left">Defect No.</th>
                <th class="px-4 py-3 text-left">Discovered</th>
                <th class="px-4 py-3 text-left">Product / Serial</th>
                <th class="px-4 py-3 text-left">Source</th>
                <th class="px-4 py-3 text-center">Qty</th>
                <th class="px-4 py-3 text-left">Reported By</th>
                <th class="px-4 py-3 text-center">Cause</th>
                <th class="px-4 py-3 text-center">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($defects as $d)
                <tr class="border-b border-slate-50 hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium"><a href="{{ route('defects.show', $d) }}" class="hover:underline">{{ $d->defect_no }}</a></td>
                    <td class="px-4 py-3 text-slate-500">{{ $d->date_discovered->format('M d, Y') }}</td>
                    <td class="px-4 py-3">
                        {{ $d->product->name }}
                        @if ($d->serial_no)<span class="block text-xs font-mono text-slate-400">SN {{ $d->serial_no }}</span>@endif
                    </td>
                    <td class="px-4 py-3 text-slate-500">
                        @if ($d->source === 'inventory')
                            Our stock
                        @else
                            Customer unit
                            @if ($d->repairJob)<span class="block text-xs text-slate-400">{{ $d->repairJob->job_no }} · {{ $d->repairJob->customer->name }}</span>@endif
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">{{ $d->quantity }}</td>
                    <td class="px-4 py-3">{{ $d->reporter->name }}</td>
                    <td class="px-4 py-3 text-center"><x-badge type="cause" :value="$d->cause" /></td>
                    <td class="px-4 py-3 text-center"><x-badge type="defect" :value="$d->status" /></td>
                    <td class="px-4 py-3 text-right"><a href="{{ route('defects.show', $d) }}" class="btn-action">View</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-4 py-12 text-center text-slate-400">
                        <i class="ti ti-alert-octagon text-3xl block mb-2 text-slate-300"></i>
                        {{ array_filter($f) ? 'No defect reports match these filters.' : 'No defect reports yet.' }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $defects->links() }}</div>
@endsection