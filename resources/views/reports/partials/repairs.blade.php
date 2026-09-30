@php $status = ['for_assessment' => 't-gray', 'in_progress' => 't-amber', 'completed' => 't-green', 'released' => 't-gray']; @endphp

<table class="cards">
    <tr>
        <td><div class="label">Repair Jobs</div><div class="value">{{ $totals['count'] }}</div></td>
        <td><div class="label">Service Fees</div><div class="value">{{ $peso($totals['service']) }}</div></td>
        <td><div class="label">Parts Charged</div><div class="value">{{ $peso($totals['parts']) }}</div></td>
        <td><div class="label">Total Billed</div><div class="value">{{ $peso($totals['total']) }}</div></td>
    </tr>
</table>

<h3>Repair Jobs</h3>
<table class="data">
    <thead>
        <tr>
            <th>Job No.</th><th>Date</th><th>Customer</th><th>Model</th><th class="c">Units</th>
            <th>Parts Used</th><th class="r">Svc Fee</th><th class="r">Parts</th><th class="r">Total</th><th class="c">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $job)
            <tr>
                <td>{{ $job->job_no }}</td>
                <td>{{ $job->date_received->format('M d, Y') }}</td>
                <td>{{ $job->customer->name }}</td>
                <td>{{ $job->unit_model }}</td>
                <td class="c">{{ $job->units }}</td>
                <td>{{ $job->parts->map(fn ($p) => $p->product->name . ' ×' . $p->quantity_used)->implode(', ') ?: '—' }}</td>
                <td class="r">{{ $peso($job->service_fee) }}</td>
                <td class="r">{{ $peso($job->parts_cost) }}</td>
                <td class="r">{{ $peso($job->total_amount) }}</td>
                <td class="c"><span class="tag {{ $status[$job->status] }}">{{ \App\Models\RepairJob::STATUSES[$job->status] }}</span></td>
            </tr>
        @empty
            <tr><td colspan="10" class="empty">No repair jobs in this period.</td></tr>
        @endforelse
    </tbody>
    @if ($rows->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="6">Total</td>
                <td class="r">{{ $peso($totals['service']) }}</td>
                <td class="r">{{ $peso($totals['parts']) }}</td>
                <td class="r">{{ $peso($totals['total']) }}</td>
                <td></td>
            </tr>
        </tfoot>
    @endif
</table>