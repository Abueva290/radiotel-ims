@extends('layouts.admin')

@section('title', 'Purchase Order')

@section('content')
@php
    $role = auth()->user()->role;
    $isAdmin = $role === 'admin';
    $isOffice = in_array($role, ['admin', 'secretary']);   // prepares, sends, cancels, records invoices
    $canReceive = in_array($role, ['admin', 'staff']);
    $input = 'w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500';

    $steps = [
        'draft'    => ['Prepared',  $po->creator?->name,  $po->created_at],
        'approved' => ['Approved',  $po->approver?->name, $po->approved_at],
        'sent'     => ['Sent',      null,                 $po->sent_at],
        'received' => ['Received',  $po->receiver?->name, $po->received_date],
    ];
    $order = array_keys($steps);
    $reached = $po->status === 'cancelled' ? -1 : array_search($po->status, $order);
@endphp

<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-xl font-semibold">{{ $po->po_no }}</h1>
            <x-badge type="purchase_order" :value="$po->status" size="md" />
        </div>
        <p class="text-sm text-slate-500">{{ $po->supplier->name }} · {{ $po->order_date->format('F d, Y') }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if (in_array($po->status, ['approved', 'sent', 'received']) && $isOffice)
            <a href="{{ route('purchase-orders.document', $po) }}" target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white border border-slate-200 text-sm hover:bg-slate-50">
                <i class="ti ti-printer text-base"></i> Print PO
            </a>
        @endif
        <a href="{{ route('purchase-orders.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Back</a>
    </div>
</div>

{{-- Progress --}}
@if ($po->status === 'cancelled')
    <div class="mb-6 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
        Cancelled by {{ $po->canceller?->name ?? '—' }} on {{ $po->cancelled_at?->format('M d, Y g:i A') }}.
    </div>
@else
    <ol class="mb-6 grid grid-cols-2 sm:grid-cols-4 gap-3">
        @foreach ($steps as $key => [$label, $who, $when])
            @php $done = array_search($key, $order) <= $reached; @endphp
            <li class="rounded-xl border p-4 {{ $done ? 'bg-white border-slate-300' : 'bg-slate-50 border-dashed border-slate-200' }}">
                <p class="flex items-center gap-2 text-sm font-medium {{ $done ? 'text-slate-800' : 'text-slate-400' }}">
                    <i class="ti {{ $done ? 'ti-circle-check text-emerald-600' : 'ti-circle text-slate-300' }} text-lg"></i>{{ $label }}
                </p>
                <p class="text-xs text-slate-400 mt-1">
                    @if ($done && $when){{ $when->format('M d, Y') }}@if ($who) · {{ $who }}@endif @else Pending @endif
                </p>
            </li>
        @endforeach
    </ol>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-6">
            <h2 class="font-semibold mb-4">Items Ordered</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm [&_th]:whitespace-nowrap [&_th:not(:first-child)]:pl-3 [&_td:not(:first-child)]:whitespace-nowrap [&_td:not(:first-child)]:pl-3 [&_th:first-child]:w-full [&_td:first-child]:!whitespace-normal">
                    <thead class="text-xs uppercase text-slate-400 border-b border-slate-100">
                        <tr>
                            <th class="py-2 text-left">Product</th>
                            <th class="py-2 text-center">Qty</th>
                            <th class="py-2 text-right hidden sm:table-cell">Unit Cost</th>
                            <th class="py-2 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($po->items as $item)
                            <tr class="border-b border-slate-50">
                                <td class="py-3">{{ $item->product->name }}<span class="block text-xs text-slate-400">{{ $item->product->brand }}</span></td>
                                <td class="py-3 text-center">{{ $item->quantity }}</td>
                                <td class="py-3 text-right hidden sm:table-cell">₱{{ number_format($item->unit_cost, 2) }}</td>
                                <td class="py-3 text-right font-medium">₱{{ number_format($item->subtotal, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex justify-end items-baseline gap-6 mt-4 pt-4 border-t border-slate-100">
                <p class="text-sm text-slate-500">Total</p>
                <p class="text-xl font-semibold">₱{{ number_format($po->total_amount, 2) }}</p>
            </div>
        </div>

        @if ($po->status === 'received')
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-900">
                <p class="font-medium flex items-center gap-2"><i class="ti ti-package-import text-lg"></i> Delivery received {{ $po->received_date->format('M d, Y') }} · Supplier DR {{ $po->supplier_dr_no }}</p>
                <p class="mt-1">Stock is not added automatically. Check the delivered items against this PO and the supplier's delivery receipt, then record a stock-in for each item in Inventory.</p>
                @if ($canReceive)
                    <a href="{{ route('inventory.index') }}" class="inline-block mt-3 px-3 py-1.5 rounded-lg bg-white border border-emerald-200 text-emerald-800 text-xs font-medium hover:bg-emerald-100">Go to Inventory</a>
                @endif
            </div>
        @endif
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="font-semibold mb-3">Details</h2>
            <dl class="text-sm space-y-2">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Credit terms</dt><dd>{{ $po->supplier->credit_terms_days ? $po->supplier->credit_terms_days . ' days' : 'Cash' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Expected delivery</dt><dd>{{ $po->expected_date?->format('M d, Y') ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Prepared by</dt><dd>{{ $po->creator->name }}</dd></div>
                @if ($po->remarks)
                    <div><dt class="text-slate-500">Remarks</dt><dd class="mt-1">{{ $po->remarks }}</dd></div>
                @endif
            </dl>
        </div>

        {{-- Next step for this user --}}
        @if ($po->status === 'draft')
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h2 class="font-semibold mb-2">Approval</h2>
                @if ($isAdmin)
                    <p class="text-sm text-slate-500 mb-4">Check the supplier, items, and costs before approving.</p>
                    <form method="POST" action="{{ route('purchase-orders.approve', $po) }}"
                          data-confirm="Approve {{ $po->po_no }} for ₱{{ number_format($po->total_amount, 2) }}?" data-confirm-title="Approve purchase order" data-confirm-button="Yes, approve">
                        @csrf @method('PATCH')
                        <button class="w-full px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Approve Purchase Order</button>
                    </form>
                @else
                    <p class="text-sm text-slate-500">Waiting for the Operational Manager's approval.</p>
                @endif
            </div>
        @elseif ($po->status === 'approved' && $isOffice)
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h2 class="font-semibold mb-2">Send to Supplier</h2>
                <p class="text-sm text-slate-500 mb-4">Print or download the PO, send it to {{ $po->supplier->name }}, then mark it as sent.</p>
                <form method="POST" action="{{ route('purchase-orders.send', $po) }}"
                      data-confirm="Mark {{ $po->po_no }} as sent to {{ $po->supplier->name }}?" data-confirm-title="Mark as sent" data-confirm-button="Yes, mark as sent">
                    @csrf @method('PATCH')
                    <button class="w-full px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Mark as Sent</button>
                </form>
            </div>
        @elseif ($po->status === 'sent' && $canReceive)
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h2 class="font-semibold mb-2">Receive Delivery</h2>
                <p class="text-sm text-slate-500 mb-4">Confirm the supplier's delivery for this PO.</p>
                <form method="POST" action="{{ route('purchase-orders.receive', $po) }}" class="space-y-3"
                      data-confirm="Mark {{ $po->po_no }} as received? Remember to record the stock-in in Inventory." data-confirm-title="Receive delivery" data-confirm-button="Yes, received">
                    @csrf @method('PATCH')
                    <div>
                        <label class="block text-sm font-medium mb-1">Date Received</label>
                        <input type="date" name="received_date" value="{{ old('received_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Supplier Delivery Receipt No.</label>
                        <input type="text" name="supplier_dr_no" value="{{ old('supplier_dr_no') }}" placeholder="e.g. DR-55102" class="{{ $input }}">
                    </div>
                    <button class="w-full px-4 py-2 rounded-lg bg-slate-700 text-white text-sm hover:bg-slate-800">Mark as Received</button>
                </form>
            </div>
        @endif

        @if ($isOffice && in_array($po->status, ['approved', 'sent', 'received']))
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h2 class="font-semibold mb-3">Supplier Invoices</h2>
                @forelse ($po->invoices as $inv)
                    <a href="{{ route('payables.show', $inv) }}" class="flex items-center justify-between gap-3 py-2 border-b border-slate-50 last:border-0 text-sm">
                        <span class="font-medium">{{ $inv->invoice_no }}</span>
                        <span>₱{{ number_format($inv->amount, 2) }}</span>
                    </a>
                @empty
                    <p class="text-sm text-slate-400 mb-3">No supplier invoice recorded for this PO yet.</p>
                @endforelse
                <a href="{{ route('payables.create', ['purchase_order' => $po->id]) }}" class="block text-center mt-3 px-4 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">Record Supplier Invoice</a>
            </div>
        @endif

        @if ($isOffice && $po->canBeCancelled())
            <form method="POST" action="{{ route('purchase-orders.cancel', $po) }}"
                  data-confirm="Cancel {{ $po->po_no }}? This cannot be undone." data-confirm-title="Cancel purchase order"
                  data-confirm-button="Yes, cancel PO" data-confirm-tone="danger">
                @csrf @method('PATCH')
                <button class="w-full px-4 py-2 rounded-lg bg-red-50 text-red-700 text-sm hover:bg-red-100">Cancel Purchase Order</button>
            </form>
        @endif
    </div>
</div>
@endsection