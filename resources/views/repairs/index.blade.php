@extends('layouts.admin')

@section('title', 'Repair & Service')

@section('content')
@php
    $badge = [
        'for_assessment' => 'bg-slate-100 text-slate-700',
        'in_progress'    => 'bg-blue-100 text-blue-700',
        'completed'      => 'bg-green-100 text-green-700',
        'released'       => 'bg-slate-200 text-slate-600',
    ];
    $cards = [
        ['Open Repairs', $stats['open'], 'Pending completion'],
        ['Completed', $stats['completed'], ''],
        ['Total Billed', '₱' . number_format($stats['billed'], 2), ''],
    ];
@endphp

<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-xl font-semibold">Repair & Service Management</h1>
        <p class="text-sm text-slate-500">Service fee: ₱350 per unit + parts cost</p>
    </div>
    <a href="{{ route('repairs.create') }}"
       class="px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">+ New Repair Job</a>
</div>

<div class="grid grid-cols-3 gap-4 mb-6">
    @foreach ($cards as [$label, $value, $note])
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs text-slate-500">{{ $label }}</p>
            <p class="text-2xl font-semibold mt-2">{{ $value }}</p>
            @if ($note)<p class="text-xs text-slate-400 mt-1">{{ $note }}</p>@endif
        </div>
    @endforeach
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="text-xs uppercase text-slate-400 border-b border-slate-100">
            <tr>
                <th class="px-4 py-3 text-left">Job No.</th>
                <th class="px-4 py-3 text-left">Date</th>
                <th class="px-4 py-3 text-left">Customer</th>
                <th class="px-4 py-3 text-left">Assigned To</th>
                <th class="px-4 py-3 text-left">Model</th>
                <th class="px-4 py-3 text-center">Units</th>
                <th class="px-4 py-3 text-right">Svc Fee</th>
                <th class="px-4 py-3 text-right">Total</th>
                <th class="px-4 py-3 text-center">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($jobs as $job)
                <tr class="border-b border-slate-50 hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium">{{ $job->job_no }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ $job->date_received->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">{{ $job->customer->name }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ $job->assessor?->name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $job->unit_model }}</td>
                    <td class="px-4 py-3 text-center">{{ $job->units }}</td>
                    <td class="px-4 py-3 text-right text-slate-500">₱{{ number_format($job->service_fee, 2) }}</td>
                    <td class="px-4 py-3 text-right font-medium">₱{{ number_format($job->total_amount, 2) }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $badge[$job->status] }}">
                            {{ \App\Models\RepairJob::STATUSES[$job->status] }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('repairs.show', $job) }}" class="text-slate-600 hover:underline">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="px-4 py-8 text-center text-slate-400">No repair jobs yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection