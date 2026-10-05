@extends('layouts.admin')

@section('title', 'Reports')

@section('content')
@php
    $input  = 'rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500';
    $peso   = fn ($v) => '₱' . number_format((float) $v, 2);
    $range  = ['from' => $from->toDateString(), 'to' => $to->toDateString()];
    $cards  = [
        ['Sales',               $peso($overview['sales']),       $overview['sales_count'] . ' transactions in period'],
        ['Payments collected',  $peso($overview['collected']),   'In period'],
        ['Receivables',         $peso($overview['receivables']), 'Unpaid, as of today'],
        ['Payables',            $peso($overview['payables']),    'Unpaid, as of today'],
        ['Repairs billed',      $peso($overview['repairs']),     'In period'],
        ['Low-stock items',     $overview['low_stock'],          'At or below reorder level'],
    ];
@endphp

{{-- Report tables reuse the same partials as the printable PDF, styled for the screen --}}
<style>
    .report-view { font-variant-numeric: tabular-nums; }
    .report-view table.cards { width: 100%; border-collapse: separate; border-spacing: 12px 0; margin: 0 -12px 8px; }
    .report-view table.cards td { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; width: 25%; vertical-align: top; }
    .report-view .cards .label { font-size: .75rem; color: #64748b; }
    .report-view .cards .value { font-size: 1.2rem; font-weight: 600; margin-top: 4px; color: #1e293b; }
    .report-view h3 { font-size: .9rem; font-weight: 600; color: #334155; margin: 22px 0 8px; }
    .report-view table.data { width: 100%; border-collapse: collapse; font-size: .875rem; }
    .report-view table.data th { text-align: left; font-weight: 500; font-size: .75rem; color: #64748b; background: #f8fafc; padding: 10px 12px; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
    .report-view table.data td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
    .report-view table.data tfoot td { font-weight: 600; border-top: 1px solid #cbd5e1; border-bottom: 0; }
    .report-view .r, .report-view table.data th.r { text-align: right; }
    .report-view .c, .report-view table.data th.c { text-align: center; }
    .report-view .tag { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: .75rem; font-weight: 500; white-space: nowrap; }
    .report-view .t-red   { background: #fef2f2; color: #b91c1c; }
    .report-view .t-amber { background: #fffbeb; color: #b45309; }
    .report-view .t-green { background: #ecfdf5; color: #047857; }
    .report-view .t-gray  { background: #f1f5f9; color: #475569; }
    .report-view .empty { text-align: center; color: #94a3b8; padding: 28px; }
    .report-view .muted { color: #94a3b8; }
</style>

<div class="flex items-start justify-between gap-4 mb-5">
    <div>
        <h1 class="text-xl font-semibold">Reports & Analytics</h1>
        <p class="text-sm text-slate-500">{{ $from->format('M d, Y') }} to {{ $to->format('M d, Y') }}</p>
    </div>
</div>

{{-- Date range --}}
<form method="GET" class="bg-white rounded-xl border border-slate-200 px-5 py-4 mb-5 flex flex-wrap items-end gap-3">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <div>
        <label class="block text-xs text-slate-500 mb-1">From</label>
        <input type="date" name="from" value="{{ $range['from'] }}" class="{{ $input }}">
    </div>
    <div>
        <label class="block text-xs text-slate-500 mb-1">To</label>
        <input type="date" name="to" value="{{ $range['to'] }}" class="{{ $input }}">
    </div>
    <button class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Generate report</button>
    <div class="flex gap-2 ml-auto">
        <a href="{{ route('reports.show', ['type' => $tab] + $range) }}" target="_blank"
           class="px-4 py-2 rounded-lg bg-white border border-slate-300 text-sm hover:bg-slate-50">Print</a>
        <a href="{{ route('reports.show', ['type' => $tab, 'pdf' => 1] + $range) }}"
           class="px-4 py-2 rounded-lg bg-white border border-slate-300 text-sm hover:bg-slate-50">Export PDF</a>
    </div>
</form>

{{-- Summary across all modules --}}
<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 mb-6">
    @foreach ($cards as [$label, $value, $note])
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-xs text-slate-500">{{ $label }}</p>
            <p class="text-lg font-semibold mt-1 tabular-nums">{{ $value }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $note }}</p>
        </div>
    @endforeach
</div>

{{-- One tab per report --}}
<div class="flex flex-wrap gap-2 mb-4">
    @foreach ($reports as $key => $report)
        <a href="{{ route('reports.index', ['tab' => $key] + $range) }}"
           class="px-4 py-2 rounded-lg text-sm {{ $tab === $key ? 'bg-slate-800 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
            {{ $report['title'] }}
        </a>
    @endforeach
</div>

{{-- The selected report --}}
<div class="bg-white rounded-xl border border-slate-200 p-6">
    <div class="flex items-baseline justify-between gap-4 mb-4">
        <div>
            <h2 class="text-lg font-semibold">{{ $meta['title'] }}</h2>
            <p class="text-sm text-slate-500">{{ $meta['desc'] }}</p>
        </div>
        <p class="text-sm text-slate-500 whitespace-nowrap">
            {{ $meta['dated'] ? $from->format('M d, Y') . ' to ' . $to->format('M d, Y') : 'As of ' . now()->format('M d, Y') }}
        </p>
    </div>

    <div class="report-view overflow-x-auto">
        @include('reports.partials.' . $tab)
    </div>
</div>
@endsection