@php
    use App\Support\Badge;
    $period = ['received_from' => $from->toDateString(), 'received_to' => $to->toDateString()];
@endphp

<table class="cards">
    <tr>
        @include('reports.partials._card', ['label' => 'Repair Jobs', 'value' => $totals['count'], 'url' => route('repairs.index', $period)])
        @include('reports.partials._card', ['label' => 'Service Fees', 'value' => $peso($totals['service']), 'url' => route('repairs.index', $period)])
        @include('reports.partials._card', ['label' => 'Parts Charged', 'value' => $peso($totals['parts']), 'url' => route('repairs.index', $period)])
        @include('reports.partials._card', ['label' => 'Total Billed', 'value' => $peso($totals['total']), 'url' => route('repairs.index', $period)])
    </tr>
</table>

<table class="cards">
    <tr>
        @include('reports.partials._card', ['label' => 'Overdue (unfinished, past target)', 'value' => $totals['overdue'], 'url' => route('repairs.index', $period + ['overdue' => 1])])
        @include('reports.partials._card', ['label' => 'Completed (work finished)', 'value' => $totals['finished'], 'url' => route('repairs.index', $period + ['status' => 'finished'])])
        @include('reports.partials._card', ['label' => 'Completed After Target', 'value' => $totals['late'], 'url' => route('repairs.index', $period + ['status' => 'finished_late'])])
        <td><div class="label">Avg. Turnaround (received → completed)</div><div class="value">{{ $totals['avg_hours'] !== null ? ($totals['avg_hours'] >= 24 ? round($totals['avg_hours'] / 24, 1) . ' days' : $totals['avg_hours'] . ' hrs') : 'n/a' }}</div></td>
    </tr>
</table>

<h3>Repair Jobs</h3>
<table class="data">
    <thead>
        <tr>
            <th>Job No.</th><th>Received</th><th>Customer</th><th>Model / Serial</th><th class="c">Units</th>
            <th>Technician</th><th>Target</th><th>Completed</th><th>Duration</th>
            <th>Parts Used</th><th class="r">Svc Fee</th><th class="r">Parts</th><th class="r">Total</th><th class="c">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $job)
            <tr>
                <td>{{ $job->job_no }}</td>
                <td>{{ $job->date_received->format('M d, Y') }}</td>
                <td>{{ $job->customer->name }}</td>
                <td>{{ $job->unit_model }}@if ($job->serialUnits->isNotEmpty())<br><span class="muted">SN {{ $job->serialUnits->pluck('serial_no')->implode(', ') }}</span>@endif</td>
                <td class="c">{{ $job->units }}</td>
                <td>{{ $job->technician?->name ?? '—' }}</td>
                <td>{{ $job->target_completion_date?->format('M d, Y') ?? '—' }}</td>
                <td>{{ $job->completed_at?->format('M d, Y') ?? '—' }}</td>
                <td>
                    {{ $job->completionDuration() ?? (in_array($job->status, \App\Models\RepairJob::OPEN_STATUSES) ? $job->elapsed() . ' so far' : '—') }}
                    @if ($job->isOverdue())<br>{!! Badge::tag('schedule', 'overdue') !!}@endif
                </td>
                <td>{{ $job->parts->map(fn ($p) => $p->product->name . ' ×' . $p->quantity_used)->implode(', ') ?: '—' }}</td>
                <td class="r">{{ $peso($job->service_fee) }}</td>
                <td class="r">{{ $peso($job->parts_cost) }}</td>
                <td class="r">{{ $peso($job->total_amount) }}</td>
                <td class="c">{!! Badge::tag('repair', $job->status) !!}</td>
            </tr>
        @empty
            <tr><td colspan="14" class="empty">No repair jobs in this period.</td></tr>
        @endforelse
    </tbody>
    @if ($rows->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="10">Total</td>
                <td class="r">{{ $peso($totals['service']) }}</td>
                <td class="r">{{ $peso($totals['parts']) }}</td>
                <td class="r">{{ $peso($totals['total']) }}</td>
                <td></td>
            </tr>
        </tfoot>
    @endif
</table>