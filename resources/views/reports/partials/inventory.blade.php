<table class="cards">
    <tr>
        <td><div class="label">Total SKUs</div><div class="value">{{ $totals['count'] }}</div></td>
        <td><div class="label">Low / Out of Stock</div><div class="value">{{ $totals['low'] }}</div></td>
        <td><div class="label">Inventory Value (at cost)</div><div class="value">{{ $peso($totals['value']) }}</div></td>
        <td><div class="label">Movements Period</div><div class="value" style="font-size: 12px;">{{ $from->format('M d') }} – {{ $to->format('M d, Y') }}</div></td>
    </tr>
</table>

<h3>Stock Levels and Movements</h3>
<table class="data">
    <thead>
        <tr>
            <th>ID</th><th>Product</th><th>Brand</th><th>Category</th>
            <th class="c">In</th><th class="c">Out</th><th class="c">Adj.</th>
            <th class="c">On Hand</th><th class="c">Reorder</th>
            <th class="r">Unit Cost</th><th class="r">Value</th><th class="c">Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $p)
            @php
                $m = $moves[$p->id] ?? null;
                $state = $p->stockStatus();
                $cls = ['In Stock' => 't-green', 'Low Stock' => 't-amber', 'Out of Stock' => 't-red'][$state];
            @endphp
            <tr>
                <td>P{{ str_pad($p->id, 3, '0', STR_PAD_LEFT) }}</td>
                <td>{{ $p->name }}@if ($p->status === 'discontinued') <span class="muted">(discontinued)</span>@endif</td>
                <td>{{ $p->brand }}</td>
                <td>{{ \App\Models\Product::CATEGORIES[$p->category] }}</td>
                <td class="c">{{ $m ? (int) $m->qty_in : 0 }}</td>
                <td class="c">{{ $m ? (int) $m->qty_out : 0 }}</td>
                <td class="c">{{ $m ? sprintf('%+d', (int) $m->qty_adj) : 0 }}</td>
                <td class="c"><strong>{{ $p->stock_qty }}</strong></td>
                <td class="c">{{ $p->reorder_level }}</td>
                <td class="r">{{ $peso($p->unit_cost) }}</td>
                <td class="r">{{ $peso($p->stock_qty * $p->unit_cost) }}</td>
                <td class="c"><span class="tag {{ $cls }}">{{ $state }}</span></td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr><td colspan="10">Total Inventory Value</td><td class="r">{{ $peso($totals['value']) }}</td><td></td></tr>
    </tfoot>
</table>