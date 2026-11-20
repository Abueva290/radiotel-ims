<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order {{ $po->po_no }} · Radiotel IMS</title>
    <style>
        @page { margin: 32px 36px; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1f2430; margin: 0; }
        .screen { background: #eef0f4; padding: 24px; }
        .sheet { background: #fff; }
        .screen .sheet { max-width: 800px; margin: 0 auto; padding: 40px 44px; border-radius: 10px; }
        .toolbar { max-width: 800px; margin: 0 auto 16px; }
        .toolbar a, .toolbar button { display: inline-block; padding: 8px 14px; margin-right: 6px; border-radius: 8px;
            font-size: 13px; text-decoration: none; border: 0; cursor: pointer; font-family: inherit; }
        .btn-dark { background: #334155; color: #fff; }
        .btn-light { background: #fff; color: #334155; border: 1px solid #cbd5e1 !important; }

        table { border-collapse: collapse; }
        .head { width: 100%; border-bottom: 2px solid #1f2430; padding-bottom: 12px; margin-bottom: 18px; }
        .company { font-size: 16px; font-weight: bold; }
        .muted { color: #5a6070; }
        .doc-title { font-size: 20px; font-weight: bold; text-align: right; letter-spacing: 1px; }
        .doc-no { text-align: right; font-size: 12px; margin-top: 4px; }

        .info { width: 100%; margin-bottom: 18px; }
        .info td { vertical-align: top; width: 50%; }
        .box { border: 1px solid #d5d9e2; padding: 10px 12px; }
        .label { font-size: 9.5px; color: #5a6070; text-transform: uppercase; margin-bottom: 3px; }
        .meta td { padding: 2px 0; }
        .meta td.k { color: #5a6070; width: 45%; }

        table.items { width: 100%; }
        table.items th { background: #eef0f4; color: #5a6070; font-size: 9.5px; text-transform: uppercase;
            text-align: left; padding: 7px 8px; border-bottom: 1px solid #c9ced8; }
        table.items td { padding: 7px 8px; border-bottom: 1px solid #e6e8ee; }
        table.items tfoot td { font-weight: bold; font-size: 12px; border-top: 1.5px solid #1f2430; border-bottom: 0; }
        .r { text-align: right !important; }
        .c { text-align: center !important; }

        .note { margin-top: 14px; font-size: 10px; color: #5a6070; }
        .signs { width: 100%; margin-top: 48px; }
        .signs td { width: 33%; padding: 0 12px; vertical-align: bottom; text-align: center; }
        .line { border-top: 1px solid #1f2430; padding-top: 4px; font-size: 10px; }
        .foot { margin-top: 28px; font-size: 9px; color: #8a90a0; border-top: 1px solid #e6e8ee; padding-top: 8px; }

        @media print { .toolbar { display: none; } .screen { background: #fff; padding: 0; } .screen .sheet { padding: 0; } }
    </style>
</head>
<body class="{{ $isPdf ? '' : 'screen' }}">
@php $peso = fn ($v) => '₱' . number_format((float) $v, 2); @endphp

@unless ($isPdf)
    <div class="toolbar">
        <a href="{{ route('purchase-orders.show', $po) }}" class="btn-light">← Back to Purchase Order</a>
        <a href="{{ route('purchase-orders.document', [$po, 'pdf' => 1]) }}" class="btn-dark">Download PDF</a>
        <button onclick="window.print()" class="btn-light">Print</button>
    </div>
@endunless

<div class="sheet">
    <table class="head">
        <tr>
            <td>
                <div class="company">Radiotel Electronics Sales &amp; Services Co.</div>
                <div class="muted">Door 2, Tionko Bldg., Elpidio Quirino Ave., Poblacion District, Davao City</div>
            </td>
            <td>
                <div class="doc-title">PURCHASE ORDER</div>
                <div class="doc-no">No. <strong>{{ $po->po_no }}</strong></div>
            </td>
        </tr>
    </table>

    <table class="info">
        <tr>
            <td style="padding-right: 8px;">
                <div class="box">
                    <div class="label">Supplier</div>
                    <div style="font-size: 12px; font-weight: bold;">{{ $po->supplier->name }}</div>
                    @if ($po->supplier->contact_person)<div>Attn: {{ $po->supplier->contact_person }}</div>@endif
                    @if ($po->supplier->phone)<div class="muted">{{ $po->supplier->phone }}</div>@endif
                </div>
            </td>
            <td style="padding-left: 8px;">
                <div class="box">
                    <table class="meta" style="width: 100%;">
                        <tr><td class="k">PO Date</td><td>{{ $po->order_date->format('F d, Y') }}</td></tr>
                        <tr><td class="k">Expected Delivery</td><td>{{ $po->expected_date?->format('F d, Y') ?? 'As soon as available' }}</td></tr>
                        <tr><td class="k">Terms</td><td>{{ $po->supplier->credit_terms_days ? $po->supplier->credit_terms_days . ' days' : 'Cash' }}</td></tr>
                        <tr><td class="k">Deliver To</td><td>Radiotel, Davao City</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 6%;" class="c">#</th>
                <th>Description</th>
                <th style="width: 12%;" class="c">Qty</th>
                <th style="width: 18%;" class="r">Unit Cost</th>
                <th style="width: 18%;" class="r">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($po->items as $i => $item)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>
                        {{ $item->product->name }}
                        @if ($item->product->brand)<span class="muted"> · {{ $item->product->brand }}</span>@endif
                    </td>
                    <td class="c">{{ $item->quantity }}</td>
                    <td class="r">{{ $peso($item->unit_cost) }}</td>
                    <td class="r">{{ $peso($item->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="r">TOTAL</td>
                <td class="r">{{ $peso($po->total_amount) }}</td>
            </tr>
        </tfoot>
    </table>

    @if ($po->remarks)
        <p class="note"><strong>Remarks:</strong> {{ $po->remarks }}</p>
    @endif
    <p class="note">Please deliver the items above with your delivery receipt and invoice referencing this PO number.</p>

    <table class="signs">
        <tr>
            <td><div class="line">Prepared by<br><strong>{{ $po->creator->name }}</strong></div></td>
            <td><div class="line">Approved by<br><strong>{{ $po->approver?->name ?? '' }}</strong></div></td>
            <td><div class="line">Supplier Conforme<br>Date: ____________</div></td>
        </tr>
    </table>

    <div class="foot">
        Generated {{ now()->format('M d, Y h:i A') }} · Radiotel Inventory and Sales Management System
    </div>
</div>
</body>
</html>