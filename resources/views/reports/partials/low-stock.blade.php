<table class="cards">
    <tr>
        @include('reports.partials._card', ['label' => 'Items Needing Reorder', 'value' => $totals['count'], 'url' => route('inventory.index', ['stock' => 'low'])])
        @include('reports.partials._card', ['label' => 'Out of Stock', 'value' => $rows->where('stock_qty', '<=', 0)->count(), 'url' => route('inventory.index', ['stock' => 'out'])])
        <td style="background: #fff; border: 0;"></td>
        <td style="background: #fff; border: 0;"></td>
    </tr>
</table>

<h3>Items at or Below Reorder Level</h3>
<table class="data">
    <thead>
        <tr>
            <th>ID</th><th>Product</th><th>Brand</th><th>Category</th>
            <th class="c">On Hand</th><th class="c">Reorder Level</th><th class="c">Shortfall</th><th class="c">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $p)
            <tr>
                <td>P{{ str_pad($p->id, 3, '0', STR_PAD_LEFT) }}</td>
                <td>{{ $p->name }}</td>
                <td>{{ $p->brand }}</td>
                <td>{{ \App\Models\Product::CATEGORIES[$p->category] }}</td>
                <td class="c"><strong>{{ $p->stock_qty }}</strong></td>
                <td class="c">{{ $p->reorder_level }}</td>
                <td class="c">{{ max(0, $p->reorder_level - $p->stock_qty) }}</td>
                <td class="c">
                    {!! \App\Support\Badge::tag('stock', $p->stockStatus()) !!}
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="empty">All items are above their reorder level.</td></tr>
        @endforelse
    </tbody>
</table>