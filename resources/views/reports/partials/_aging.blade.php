@php
    $bucketCls = ['Current' => 't-green', '1–30 days' => 't-amber', '31–60 days' => 't-amber', '61–90 days' => 't-red', 'Over 90 days' => 't-red'];
    $total = array_sum($buckets);
@endphp

<h3>Aging Summary</h3>
<table class="data">
    <thead>
        <tr>
            @foreach ($buckets as $label => $amount)<th class="r">{{ $label }}</th>@endforeach
            <th class="r">Total</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            @foreach ($buckets as $amount)<td class="r">{{ $peso($amount) }}</td>@endforeach
            <td class="r"><strong>{{ $peso($total) }}</strong></td>
        </tr>
    </tbody>
</table>

<h3>Outstanding Balances</h3>
<table class="data">
    <thead>
        <tr>
            <th>{{ $partyLabel }}</th><th>{{ $refLabel }}</th><th>Due Date</th>
            <th class="c">Days Overdue</th><th class="c">Aging</th><th class="r">Balance</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>
                <td>{{ $row['party'] }}</td>
                <td>{{ $row['ref'] }}</td>
                <td>{{ $row['due']->format('M d, Y') }}</td>
                <td class="c">{{ $row['days'] }}</td>
                <td class="c"><span class="tag {{ $bucketCls[$row['bucket']] }}">{{ $row['bucket'] }}</span></td>
                <td class="r">{{ $peso($row['balance']) }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">No outstanding balances.</td></tr>
        @endforelse
    </tbody>
    @if ($rows->isNotEmpty())
        <tfoot><tr><td colspan="5">Total Outstanding</td><td class="r">{{ $peso($total) }}</td></tr></tfoot>
    @endif
</table>