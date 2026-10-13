@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
@php
    use App\Support\Badge;
    $user = auth()->user();

    // Each card opens the page (and filter) behind its number
    $cards = [
        ['label' => now()->format('F') . ' Sales', 'value' => '₱' . number_format($stats['month_sales'], 2),
         'note' => $stats['month_count'] . ' transaction(s)', 'roles' => ['admin', 'secretary'], 'icon' => 'receipt',
         'url' => fn () => route('sales.index', ['from' => $stats['month_from'], 'to' => $stats['month_to']])],
        ['label' => 'Receivables', 'value' => '₱' . number_format($stats['receivables'], 2),
         'note' => $stats['ar_overdue'] . ' overdue', 'roles' => ['admin', 'secretary'], 'icon' => 'cash',
         'url' => fn () => route('receivables.index')],
        ['label' => 'Payables', 'value' => '₱' . number_format($stats['payables'], 2),
         'note' => $stats['ap_overdue'] . ' overdue', 'roles' => ['admin', 'secretary'], 'icon' => 'file-invoice',
         'url' => fn () => route('payables.index')],
        ['label' => 'Low Stock', 'value' => $stats['low_stock'],
         'note' => 'At or below reorder level', 'roles' => ['admin', 'staff'], 'icon' => 'package',
         'url' => fn () => route('inventory.index', ['stock' => 'low'])],
        ['label' => 'Open Repairs', 'value' => $stats['open_repairs'],
         'note' => 'Not yet completed', 'roles' => ['admin', 'technical_head', 'technician'], 'icon' => 'tool',
         'url' => fn () => route('repairs.index', ['status' => 'open'])],
    ];
    $visible = array_filter($cards, fn ($c) => in_array($user->role, $c['roles']));
    // Fewer columns on small screens so the cards don't get squeezed
    $colClass = [1 => 'grid-cols-1', 2 => 'grid-cols-1 sm:grid-cols-2', 3 => 'grid-cols-1 sm:grid-cols-3', 4 => 'grid-cols-2 xl:grid-cols-4', 5 => 'grid-cols-2 md:grid-cols-3 xl:grid-cols-5'][count($visible)] ?? 'grid-cols-1 sm:grid-cols-3';
    $showSales = $user->hasRole('admin', 'secretary');
    $showRepairs = $user->hasRole('admin', 'technical_head', 'technician');

    // Repair status breakdown (panel revision 10)
    $repairCards = [
        ['For Assessment',   $stats['for_assessment'],   ['status' => 'for_assessment']],
        ['In Progress',      $stats['in_progress'],      ['status' => 'in_progress']],
        ['Overdue',          $stats['overdue'],          ['overdue' => 1]],
        ['Completed, Awaiting Release', $stats['awaiting_release'], ['status' => 'completed']],
        ['Within Warranty',  $stats['warranty'],         ['warranty' => 'active']],
    ];
@endphp

<div class="rounded-xl bg-slate-700 text-white px-5 sm:px-6 py-5 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
        <p class="text-lg font-semibold">Good day, {{ explode(' ', $user->name)[0] }}!</p>
        <p class="text-sm text-slate-300">Here's an overview of Radiotel operations today.</p>
    </div>
    <p class="self-start sm:self-auto text-sm bg-slate-600 px-3 py-1.5 rounded-lg">{{ now()->format('F d, Y') }}</p>
</div>

<div class="grid {{ $colClass }} gap-4 mb-6">
    @foreach ($visible as $card)
        <a href="{{ $card['url']() }}" class="kpi-card group" aria-label="{{ $card['label'] }}: {{ $card['value'] }}, {{ $card['note'] }}. Open list.">
            <div class="flex items-center justify-between">
                <p class="text-xs text-slate-500">{{ $card['label'] }}</p>
                <i class="ti ti-{{ $card['icon'] }} text-lg text-slate-300"></i>
            </div>
            <p class="text-2xl font-semibold mt-2">{{ $card['value'] }}</p>
            <p class="text-xs text-slate-400 mt-1 flex items-center justify-between gap-2">
                <span class="truncate">{{ $card['note'] }}</span>
                <i class="ti ti-arrow-right kpi-arrow"></i>
            </p>
        </a>
    @endforeach
</div>

@if ($showRepairs)
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Repair Jobs by Status</p>
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3">
            @foreach ($repairCards as [$label, $value, $params])
                @php $alert = $label === 'Overdue' && $value > 0; @endphp
                <a href="{{ route('repairs.index', $params) }}" class="kpi-card !p-4 {{ $alert ? '!border-red-200 bg-red-50/50' : '' }}"
                   aria-label="{{ $label }}: {{ $value }} job(s). Open list.">
                    <p class="text-xs {{ $alert ? 'text-red-700' : 'text-slate-500' }}">{{ $label }}</p>
                    <p class="text-xl font-semibold mt-1 flex items-center justify-between {{ $alert ? 'text-red-600' : '' }}">
                        {{ $value }} <i class="ti ti-arrow-right kpi-arrow text-base"></i>
                    </p>
                </a>
            @endforeach
        </div>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    @if ($showSales)
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200">
            <div class="flex items-center justify-between px-6 pt-5 pb-3">
                <h2 class="font-semibold">Recent Sales</h2>
                <a href="{{ route('sales.index') }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">View all <i class="ti ti-arrow-right"></i></a>
            </div>
            <div class="divide-y divide-slate-50">
                @forelse ($recentSales as $sale)
                    <a href="{{ route('sales.show', $sale) }}" class="flex items-center justify-between px-6 py-3 hover:bg-slate-50">
                        <div>
                            <p class="font-medium text-sm">{{ $sale->customer->name }}</p>
                            <p class="text-xs text-slate-400">{{ $sale->invoice_no }} · {{ $sale->sale_date->format('Y-m-d') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-medium text-sm">₱{{ number_format($sale->total_amount, 2) }}</p>
                            <x-badge type="payment" :value="Badge::paymentStatus($sale->status, $sale->receivable?->due_date)" />
                        </div>
                    </a>
                @empty
                    <p class="px-6 py-6 text-center text-slate-400 text-sm">No sales recorded yet.</p>
                @endforelse
            </div>
        </div>
    @endif

    <div class="space-y-6 {{ $showSales ? '' : 'lg:col-span-3' }}">
        @if ($user->hasRole('admin', 'staff'))
            <div class="bg-white rounded-xl border border-slate-200">
                <div class="flex items-center justify-between px-6 pt-5 pb-3">
                    <h2 class="font-semibold text-amber-700">Low Stock Alert</h2>
                    <a href="{{ route('inventory.index', ['stock' => 'low']) }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">View all <i class="ti ti-arrow-right"></i></a>
                </div>
                <div class="divide-y divide-slate-50">
                    @forelse ($lowStock as $product)
                        <a href="{{ route('inventory.edit', $product) }}" class="flex items-center justify-between px-6 py-3 hover:bg-slate-50">
                            <p class="text-sm">{{ $product->name }}</p>
                            <x-badge type="stock" :value="$product->stockStatus()">{{ $product->stock_qty <= 0 ? 'Out of Stock' : 'Low · ' . $product->stock_qty . ' left' }}</x-badge>
                        </a>
                    @empty
                        <p class="px-6 py-6 text-center text-slate-400 text-sm">All items are above reorder level.</p>
                    @endforelse
                </div>
            </div>
        @endif

        @if ($user->hasRole('admin', 'technical_head', 'technician'))
            <div class="bg-white rounded-xl border border-slate-200">
                <div class="flex items-center justify-between px-6 pt-5 pb-3">
                    <h2 class="font-semibold">Repair Queue</h2>
                    <a href="{{ route('repairs.index', ['status' => 'open']) }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">View all <i class="ti ti-arrow-right"></i></a>
                </div>
                <div class="divide-y divide-slate-50">
                    @forelse ($repairQueue as $job)
                        <a href="{{ route('repairs.show', $job) }}" class="flex items-center justify-between px-6 py-3 hover:bg-slate-50">
                            <div>
                                <p class="text-sm">{{ $job->customer->name }}</p>
                                <p class="text-xs text-slate-400">{{ $job->job_no }} · {{ $job->unit_model }} · {{ $job->technician?->name ?? 'Not assigned' }}</p>
                            </div>
                            <span class="flex flex-col items-end gap-1">
                                <x-badge type="repair" :value="$job->status" />
                                @if ($job->isOverdue())<x-badge type="schedule" value="overdue" />@endif
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