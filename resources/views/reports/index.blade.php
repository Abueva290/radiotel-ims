@extends('layouts.admin')

@section('title', 'Reports')

@section('content')
@php
    $input  = 'rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500';
    $peso   = fn ($v) => '₱' . number_format((float) $v, 2);
    $range  = ['from' => $from->toDateString(), 'to' => $to->toDateString()];
    $cards  = [
        // [label, value, note, link] — each card opens the report (same dates) or page behind its number
        ['Sales',               $peso($overview['sales']),       $overview['sales_count'] . ' transactions in period', route('reports.index', ['tab' => 'sales'] + $range)],
        ['Payments collected',  $peso($overview['collected']),   'In period',                 route('receivables.index')],
        ['Receivables',         $peso($overview['receivables']), 'Unpaid, as of today',       route('reports.index', ['tab' => 'ar-aging'] + $range)],
        ['Payables',            $peso($overview['payables']),    'Unpaid, as of today',       route('reports.index', ['tab' => 'ap-aging'] + $range)],
        ['Repairs billed',      $peso($overview['repairs']),     'In period',                 route('reports.index', ['tab' => 'repairs'] + $range)],
        ['Low-stock items',     $overview['low_stock'],          'At or below reorder level', route('reports.index', ['tab' => 'low-stock'] + $range)],
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
    /* Clickable report cards (screen only; the PDF keeps plain cards) */
    .report-view table.cards td.is-link { padding: 0; transition: border-color .15s, box-shadow .15s, background-color .15s; }
    .report-view table.cards td.is-link:hover { background: #fff; border-color: #94a3b8; box-shadow: 0 4px 12px rgb(15 23 42 / .08); }
    .report-view .card-link { display: block; padding: 12px 14px; border-radius: 10px; color: inherit; text-decoration: none; }
    .report-view .card-link:focus-visible { outline: 2px solid #64748b; outline-offset: 2px; }
    .report-view .card-arrow { float: right; color: #cbd5e1; transition: color .15s, transform .15s; }
    .report-view td.is-link:hover .card-arrow, .report-view .card-link:focus-visible .card-arrow { color: #334155; transform: translateX(2px); }
    .report-view .t-blue  { background: #eff6ff; color: #1d4ed8; }
    .report-view .t-purple { background: #faf5ff; color: #7e22ce; }
    .report-view .t-orange { background: #fff7ed; color: #c2410c; }
    .report-view .t-teal  { background: #f0fdfa; color: #0f766e; }
    .report-view .t-dark  { background: #334155; color: #fff; }
</style>

<div class="flex items-start justify-between gap-4 mb-5">
    <div>
        <h1 class="text-xl font-semibold">Reports & Analytics</h1>
        <p class="text-sm text-slate-500">{{ $from->format('M d, Y') }} to {{ $to->format('M d, Y') }}</p>
    </div>
</div>

{{-- Date range --}}
<form method="GET" x-data="{ blocked: {{ $errors->has('export') ? 'true' : 'false' }} }" class="bg-white rounded-xl border border-slate-200 px-5 py-4 mb-5 flex flex-wrap items-end gap-3">
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
        @if ($noData)
            <button type="button" @click="blocked = true" aria-disabled="true"
                    class="px-4 py-2 rounded-lg bg-slate-100 border border-slate-200 text-sm text-slate-400 cursor-not-allowed">Print</button>
            <button type="button" @click="blocked = true" aria-disabled="true"
                    class="px-4 py-2 rounded-lg bg-slate-100 border border-slate-200 text-sm text-slate-400 cursor-not-allowed">Export PDF</button>
        @else
            <a href="{{ route('reports.show', ['type' => $tab] + $range) }}" target="_blank"
               class="px-4 py-2 rounded-lg bg-white border border-slate-300 text-sm hover:bg-slate-50">Print</a>
            <a href="{{ route('reports.show', ['type' => $tab, 'pdf' => 1] + $range) }}"
               class="px-4 py-2 rounded-lg bg-white border border-slate-300 text-sm hover:bg-slate-50">Export PDF</a>
        @endif
    </div>
    <p x-show="blocked" style="display:none" role="alert" class="basis-full text-sm text-red-700">Dates selected contain no data.</p>
</form>

@if ($noData)
    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-sm text-red-700" role="alert">
        No data found for the dates selected ({{ $from->format('M d, Y') }} to {{ $to->format('M d, Y') }}). Please choose another date range.
    </div>
@endif

{{-- Summary across all modules --}}
<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 mb-6">
    @foreach ($cards as [$label, $value, $note, $link])
        @php $isOpen = $link === route('reports.index', ['tab' => $tab] + $range); @endphp
        <a href="{{ $link }}" class="kpi-card !p-4 {{ $isOpen ? 'kpi-card-active' : '' }}"
           aria-label="{{ $label }}: {{ $value }}. Open the details.">
            <p class="text-xs text-slate-500">{{ $label }}</p>
            <p class="text-lg font-semibold mt-1 tabular-nums">{{ $value }}</p>
            <p class="text-xs text-slate-400 mt-1 flex items-center justify-between gap-2">
                <span class="truncate">{{ $note }}</span><i class="ti ti-arrow-right kpi-arrow"></i>
            </p>
        </a>
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
@unless ($noData)
<div class="bg-white rounded-xl border border-slate-200 p-6">
    <div class="flex items-baseline justify-between gap-4 mb-4">
        <div>
            <h2 class="text-lg font-semibold">{{ $meta['title'] }}</h2>
            <p class="text-sm text-slate-500">{{ $meta['desc'] }}</p>
        </div>
        <p class="text-sm text-slate-500 whitespace-nowrap">
            {{ $meta['dated'] ? $from->format('M d, Y') . ' to ' . $to->format('M d, Y') : 'As of ' . $to->format('M d, Y') }}
        </p>
    </div>

    <div class="report-view overflow-x-auto">
        @include('reports.partials.' . $tab, ['cardLinks' => true])
    </div>
</div>
@endunless
@endsection