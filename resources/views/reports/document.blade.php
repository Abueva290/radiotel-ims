<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }} {{ $number }} · Radiotel IMS</title>
    <style>
        @page { margin: 32px 36px; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 13.5px; color: #1f2430; margin: 0; }
        .screen { background: #eef0f4; padding: 24px; }
        .sheet { background: #fff; }
        .screen .sheet { max-width: 900px; margin: 0 auto; padding: 44px 48px; border-radius: 10px; }
        .toolbar { max-width: 900px; margin: 0 auto 16px; }
        .toolbar a, .toolbar button { display: inline-block; padding: 9px 16px; margin-right: 6px; border-radius: 8px;
            font-size: 14px; text-decoration: none; border: 0; cursor: pointer; font-family: inherit; }
        .btn-dark { background: #334155; color: #fff; }
        .btn-light { background: #fff; color: #334155; border: 1px solid #cbd5e1 !important; }

        table { border-collapse: collapse; }
        .head { width: 100%; border-bottom: 2px solid #1f2430; padding-bottom: 14px; margin-bottom: 20px; }
        .company { font-size: 19px; font-weight: bold; }
        .muted { color: #5a6070; }
        .doc-title { font-size: 25px; font-weight: bold; text-align: right; letter-spacing: 1px; }
        .doc-no { text-align: right; font-size: 14px; margin-top: 4px; }

        .info { width: 100%; margin-bottom: 20px; }
        .info td { vertical-align: top; width: 50%; }
        .box { border: 1px solid #d5d9e2; padding: 12px 14px; }
        .label { font-size: 11px; color: #5a6070; text-transform: uppercase; margin-bottom: 4px; }
        .meta td { padding: 3px 0; }
        .meta td.k { color: #5a6070; width: 45%; }

        table.items { width: 100%; }
        table.items th { background: #eef0f4; color: #5a6070; font-size: 11px; text-transform: uppercase;
            text-align: left; padding: 9px 10px; border-bottom: 1px solid #c9ced8; }
        table.items td { padding: 9px 10px; border-bottom: 1px solid #e6e8ee; }
        table.items tfoot td { font-weight: bold; font-size: 15px; border-top: 1.5px solid #1f2430; border-bottom: 0; }
        .r { text-align: right !important; }
        .c { text-align: center !important; }

        .note { margin-top: 16px; font-size: 12.5px; color: #5a6070; }
        .signs { width: 100%; margin-top: 56px; }
        .signs td { width: 33%; padding: 0 12px; vertical-align: bottom; text-align: center; }
        .line { border-top: 1px solid #1f2430; padding-top: 5px; font-size: 12.5px; }
        .foot { margin-top: 30px; font-size: 11px; color: #8a90a0; border-top: 1px solid #e6e8ee; padding-top: 10px; }

        @media print { .toolbar { display: none; } .screen { background: #fff; padding: 0; } .screen .sheet { padding: 0; } }
    </style>
</head>
<body class="{{ $isPdf ? '' : 'screen' }}">
@php $peso = fn ($v) => '₱' . number_format((float) $v, 2); @endphp

@unless ($isPdf)
    <div class="toolbar">
        <a href="{{ route('sales.show', $sale) }}" class="btn-light">← Back to Sale</a>
        <a href="{{ route('sales.document', [$sale, $type, 'pdf' => 1]) }}" class="btn-dark">Download PDF</a>
        <button onclick="window.print()" class="btn-light">Print</button>
        @if ($type === 'invoice')
            <a href="{{ route('sales.document', [$sale, 'dr']) }}" class="btn-light">View Delivery Receipt</a>
        @else
            <a href="{{ route('sales.document', [$sale, 'invoice']) }}" class="btn-light">View Charge Invoice</a>
        @endif
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
                <div class="doc-title">{{ strtoupper($title) }}</div>
                <div class="doc-no">No. <strong>{{ $number }}</strong></div>
            </td>
        </tr>
    </table>

    <table class="info">
        <tr>
            <td style="padding-right: 8px;">
                <div class="box">
                    <div class="label">{{ $type === 'invoice' ? 'Bill To' : 'Deliver To' }}</div>
                    <div style="font-size: 15px; font-weight: bold;">{{ $sale->customer->name }}</div>
                    @if ($sale->customer->contact_person)<div>Attn: {{ $sale->customer->contact_person }}</div>@endif
                    @if ($sale->customer->address)<div class="muted">{{ $sale->customer->address }}</div>@endif
                    @if ($sale->customer->phone)<div class="muted">{{ $sale->customer->phone }}</div>@endif
                </div>
            </td>
            <td style="padding-left: 8px;">
                <div class="box">
                    <table class="meta" style="width: 100%;">
                        <tr><td class="k">Date</td><td>{{ $sale->sale_date->format('F d, Y') }}</td></tr>
                        @if ($type === 'invoice')
                            <tr><td class="k">DR Reference</td><td>{{ $sale->dr_no }}</td></tr>
                            @if ($sale->receivable)
                                <tr><td class="k">Terms</td><td>{{ $termsDays > 0 ? $termsDays . ' days' : 'Due immediately' }}</td></tr>
                                <tr><td class="k">Due Date</td><td>{{ $sale->receivable->due_date->format('F d, Y') }}</td></tr>
                            @endif
                        @else
                            <tr><td class="k">Invoice Reference</td><td>{{ $sale->invoice_no }}</td></tr>
                        @endif
                        <tr><td class="k">Prepared by</td><td>{{ $sale->creator->name }}</td></tr>
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
                @if ($type === 'invoice')
                    <th style="width: 18%;" class="r">Unit Price</th>
                    <th style="width: 18%;" class="r">Amount</th>
                @else
                    <th style="width: 30%;">Remarks</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $i => $item)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>
                        {{ $item->product->name }}
                        @if ($item->product->brand)<span class="muted"> · {{ $item->product->brand }}</span>@endif
                    </td>
                    <td class="c">{{ $item->quantity }}</td>
                    @if ($type === 'invoice')
                        <td class="r">{{ $peso($item->unit_price) }}</td>
                        <td class="r">{{ $peso($item->subtotal) }}</td>
                    @else
                        <td></td>
                    @endif
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                @if ($type === 'invoice')
                    <td colspan="4" class="r">TOTAL AMOUNT DUE</td>
                    <td class="r">{{ $peso($sale->total_amount) }}</td>
                @else
                    <td colspan="2" class="r">TOTAL QUANTITY</td>
                    <td class="c">{{ $sale->items->sum('quantity') }}</td>
                    <td></td>
                @endif
            </tr>
        </tfoot>
    </table>

    @if ($type === 'invoice')
        <p class="note">This charge invoice is payable on or before the due date stated above. Partial payments are accepted and will be reflected in the remaining balance.</p>
    @else
        <p class="note">Received the above items in good order and condition.</p>
    @endif

    <table class="signs">
        <tr>
            <td><div class="line">Prepared by<br><strong>{{ $sale->creator->name }}</strong></div></td>
            <td><div class="line">{{ $type === 'invoice' ? 'Approved by' : 'Delivered by' }}<br>&nbsp;</div></td>
            <td><div class="line">Received by (Signature over Printed Name)<br>Date: ____________</div></td>
        </tr>
    </table>

    <div class="foot">
        Generated {{ now()->format('M d, Y h:i A') }} · Radiotel Inventory and Sales Management System
    </div>
</div>
</body>
</html>