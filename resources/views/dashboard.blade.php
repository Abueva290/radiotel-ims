@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
@php
    $user = auth()->user();
    $badge = [
        'unpaid'  => 'bg-red-100 text-red-700',
        'partial' => 'bg-amber-100 text-amber-700',
        'paid'    => 'bg-green-100 text-green-700',
    ];
    $repairBadge = [
        'for_assessment' => 'bg-slate-100 text-slate-700',
        'in_progress'    => 'bg-blue-100 text-blue-700',
    ];

    $cards = [
        ['label' => now()->format('F') . ' Sales', 'value' => '₱' . number_format($stats['month_sales'], 2),
         'note' => $stats['month_count'] . ' transaction(s)', 'roles' => ['admin', 'secretary']],
        ['label' => 'Receivables', 'value' => '₱' . number_format($stats['receivables'], 2),
         'note' => $stats['ar_overdue'] . ' overdue', 'roles' => ['admin', 'secretary']],
        ['label' => 'Payables', 'value' => '₱' . number_format($stats['payables'], 2),
         'note' => $stats['ap_overdue'] . ' overdue', 'roles' => ['admin', 'secretary']],
        ['label' => 'Low Stock', 'value' => $stats['low_stock'],
         'note' => 'Below reorder level', 'roles' => ['admin', 'staff']],
        ['label' => 'Open Repairs', 'value' => $stats['open_repairs'],
         'note' => 'Pending completion', 'roles' => ['admin', 'technical_head']],
    ];
    $visible = array_filter($cards, fn ($c) => in_array($user->role, $c['roles']));
@endphp

<div class="rounded-xl bg-slate-700 text-white px-6 py-5 mb-6 flex items-center justify-between">
    <div>
        <p class="text-lg font-semibold">Good day, {{ explode(' ', $user->name)[0] }}!</p>
        <p class="text-sm text-slate-300">Here's an overview of Radiotel operations today.</p>
    </div>
    <p class="text-sm bg-slate-600 px-3 py-1.5 rounded-lg">{{ now()->format('F d, Y') }}</p>
</div>

@php
    $colClass = [1 => 'grid-cols-1', 2 => 'grid-cols-2', 3 => 'grid-cols-3', 4 => 'grid-cols-4', 5 => 'grid-cols-5'][count($visible)] ?? 'grid-cols-3';
@endphp
<div class="grid {{ $colClass }} gap-4 mb-6">
    @foreach ($visible as $card)
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs text-slate-500">{{ $card['label'] }}</p>
            <p class="text-2xl font-semibold mt-2">{{ $card['value'] }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $card['note'] }}</p>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-3 gap-6">
    @if ($user->hasRole('admin', 'secretary'))
        <div class="col-span-2 bg-white rounded-xl border border-slate-200">
            <h2 class="font-semibold px-6 pt-5 pb-3">Recent Sales</h2>
            <div class="divide-y divide-slate-50">
                @forelse ($recentSales as $sale)
                    <a href="{{ route('sales.show', $sale) }}" class="flex items-center justify-between px-6 py-3 hover:bg-slate-50">
                        <div>
                            <p class="font-medium text-sm">{{ $sale->customer->name }}</p>
                            <p class="text-xs text-slate-400">{{ $sale->invoice_no }} · {{ $sale->sale_date->format('Y-m-d') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-medium text-sm">₱{{ number_format($sale->total_amount, 2) }}</p>
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $badge[$sale->status] }}">{{ ucfirst($sale->status) }}</span>
                        </div>
                    </a>
                @empty
                    <p class="px-6 py-6 text-center text-slate-400 text-sm">No sales recorded yet.</p>
                @endforelse
            </div>
        </div>
    @endif

    <div class="space-y-6 {{ $user->hasRole('admin', 'secretary') ? '' : 'col-span-3' }}">
        @if ($user->hasRole('admin', 'staff'))
            <div class="bg-white rounded-xl border border-slate-200">
                <h2 class="font-semibold px-6 pt-5 pb-3 text-amber-700">Low Stock Alert</h2>
                <div class="divide-y divide-slate-50">
                    @forelse ($lowStock as $product)
                        <div class="flex items-center justify-between px-6 py-3">
                            <p class="text-sm">{{ $product->name }}</p>
                            <span class="px-2 py-0.5 rounded text-xs {{ $product->stock_qty <= 0 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                                QTY {{ $product->stock_qty }}
                            </span>
                        </div>
                    @empty
                        <p class="px-6 py-6 text-center text-slate-400 text-sm">All items are above reorder level.</p>
                    @endforelse
                </div>
            </div>
        @endif

        @if ($user->hasRole('admin', 'technical_head'))
            <div class="bg-white rounded-xl border border-slate-200">
                <h2 class="font-semibold px-6 pt-5 pb-3">Repair Queue</h2>
                <div class="divide-y divide-slate-50">
                    @forelse ($repairQueue as $job)
                        <a href="{{ route('repairs.show', $job) }}" class="flex items-center justify-between px-6 py-3 hover:bg-slate-50">
                            <div>
                                <p class="text-sm">{{ $job->customer->name }}</p>
                                <p class="text-xs text-slate-400">{{ $job->unit_model }} · {{ $job->assessor?->name ?? '—' }}</p>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $repairBadge[$job->status] }}">
                                {{ \App\Models\RepairJob::STATUSES[$job->status] }}
                            </span>
                        </a>
                    @empty
                        <p class="px-6 py-6 text-center text-slate-400 text-sm">No open repair jobs.</p>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</div>
@endsection