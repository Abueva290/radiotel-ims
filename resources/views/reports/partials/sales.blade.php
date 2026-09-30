@php $status = ['unpaid' => 't-red', 'partial' => 't-amber', 'paid' => 't-green']; @endphp

<table class="cards">
    <tr>
        <td><div class="label">Transactions</div><div class="value">{{ $totals['count'] }}</div></td>
        <td><div class="label">Total Sales</div><div class="value">{{ $peso($totals['amount']) }}</div></td>
        <td><div class="label">Collected</div><div class="value">{{ $peso($totals['amount'] - $totals['outstanding']) }}</div></td>
        <td><div class="label">Outstanding</div><div class="value">{{ $peso($totals['outstanding']) }}</div></td>
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
                <td class="c"><span class="tag {{ $status[$sale->status] }}">{{ ucfirst($sale->status) }}</span></td>
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