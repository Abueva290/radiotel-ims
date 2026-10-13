<table class="cards">
    <tr>
        @php $period = ['from' => $from->toDateString(), 'to' => $to->toDateString()]; @endphp
        @include('reports.partials._card', ['label' => 'Transactions', 'value' => $totals['count'], 'url' => route('sales.index', $period)])
        @include('reports.partials._card', ['label' => 'Total Sales', 'value' => $peso($totals['amount']), 'url' => route('sales.index', $period)])
        @include('reports.partials._card', ['label' => 'Collected', 'value' => $peso($totals['amount'] - $totals['outstanding']), 'url' => route('sales.index', $period)])
        @include('reports.partials._card', ['label' => 'Outstanding', 'value' => $peso($totals['outstanding']), 'url' => route('sales.index', $period + ['status' => 'outstanding'])])
    </tr>
</table>

<h3>Sales Transactions</h3>
<table class="data">
    <thead>
        <tr>
            <th>Invoice No.</th><th>DR No.</th><th>Date</th><th>Customer</th>
            <th class="r">Amount</th><th class="r">Balance</th><th class="c">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $sale)
            <tr>
                <td>{{ $sale->invoice_no }}</td>
                <td>{{ $sale->dr_no }}</td>
                <td>{{ $sale->sale_date->format('M d, Y') }}</td>
                <td>{{ $sale->customer->name }}</td>
                <td class="r">{{ $peso($sale->total_amount) }}</td>
                <td class="r">{{ $peso($sale->receivable?->balance ?? 0) }}</td>
                <td class="c">{!! \App\Support\Badge::tag('payment', \App\Support\Badge::paymentStatus($sale->status, $sale->receivable?->due_date)) !!}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">No sales in this period.</td></tr>
        @endforelse
    </tbody>
    @if ($rows->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="4">Total</td>
                <td class="r">{{ $peso($totals['amount']) }}</td>
                <td class="r">{{ $peso($totals['outstanding']) }}</td>
                <td></td>
            </tr>
        </tfoot>
    @endif
</table>

@if ($byCustomer->isNotEmpty())
    <h3>Sales by Customer</h3>
    <table class="data" style="width: 65%;">
        <thead>
            <tr>
                <th>Customer</th>
                <th class="c" style="width: 18%;">Transactions</th>
                <th class="r" style="width: 24%;">Total</th>
                <th class="r" style="width: 16%;">% of Sales</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($byCustomer as $name => $row)
                <tr>
                    <td>{{ $name }}</td>
                    <td class="c">{{ $row['count'] }}</td>
                    <td class="r">{{ $peso($row['amount']) }}</td>
                    <td class="r">{{ $totals['amount'] > 0 ? number_format($row['amount'] / $totals['amount'] * 100, 1) : '0.0' }}%</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total</td>
                <td class="c">{{ $totals['count'] }}</td>
                <td class="r">{{ $peso($totals['amount']) }}</td>
                <td class="r">100%</td>
            </tr>
        </tfoot>
    </table>
@endif