<table class="cards">
    <tr>
        <td><div class="label">Items Needing Reorder</div><div class="value">{{ $totals['count'] }}</div></td>
        <td><div class="label">Out of Stock</div><div class="value">{{ $rows->where('stock_qty', '<=', 0)->count() }}</div></td>
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
                    <span class="tag {{ $p->stock_qty <= 0 ? 't-red' : 't-amber' }}">{{ $p->stock_qty <= 0 ? 'Out of Stock' : 'Low Stock' }}</span>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="empty">All items are above their reorder level.</td></tr>
        @endforelse
    </tbody>
</table>