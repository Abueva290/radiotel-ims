@extends('layouts.admin')

@section('title', 'Reports')

@section('content')
@php $input = 'rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500'; @endphp

<div class="mb-6">
    <h1 class="text-xl font-semibold">Reports & Analytics</h1>
    <p class="text-sm text-slate-500">Generate, preview, and export reports</p>
</div>

<form method="GET" class="bg-white rounded-xl border border-slate-200 px-6 py-4 mb-6 flex items-center gap-3">
    <label class="text-sm font-medium">Date Range</label>
    <input type="date" name="from" value="{{ $from }}" class="{{ $input }}">
    <span class="text-slate-400">to</span>
    <input type="date" name="to" value="{{ $to }}" class="{{ $input }}">
    <button class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Apply</button>
    <p class="text-xs text-slate-400 ml-auto">Aging and low-stock reports always show balances as of today.</p>
</form>

<div class="grid grid-cols-3 gap-4">
    @foreach ($reports as $key => $report)
        @php $params = ['type' => $key, 'from' => $from, 'to' => $to]; @endphp
        <div class="bg-white rounded-xl border border-slate-200 p-5 flex flex-col">
            <div class="flex items-start justify-between">
                <p class="font-semibold">{{ $report['title'] }}</p>
                <span class="px-2 py-0.5 rounded text-xs bg-slate-100 text-slate-600">{{ $report['tag'] }}</span>
            </div>
            <p class="text-sm text-slate-500 mt-1">{{ $report['desc'] }}</p>
            <p class="text-xs text-slate-400 mt-2">
                {{ $report['dated'] ? 'Uses the selected date range' : 'As of today' }}
            </p>
            <div class="flex gap-2 mt-4">
                <a href="{{ route('reports.show', $params) }}" target="_blank" class="btn-action-primary">Preview</a>
                <a href="{{ route('reports.show', $params + ['pdf' => 1]) }}" class="btn-action">Export PDF</a>
            </div>
        </div>
    @endforeach
</div>
@endsection