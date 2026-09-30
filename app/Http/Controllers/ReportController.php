<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Receivable;
use App\Models\RepairJob;
use App\Models\Sale;
use App\Models\SupplierInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public const REPORTS = [
        'sales'     => ['title' => 'Sales Report',         'desc' => 'Sales by date range and customer',  'tag' => 'Sales',       'dated' => true],
        'inventory' => ['title' => 'Inventory Report',     'desc' => 'Stock levels and movements',        'tag' => 'Inventory',   'dated' => true],
        'ar-aging'  => ['title' => 'AR Aging Report',      'desc' => 'Customer balance aging',            'tag' => 'Receivables', 'dated' => false],
        'ap-aging'  => ['title' => 'AP Aging Report',      'desc' => 'Supplier balance aging',            'tag' => 'Payables',    'dated' => false],
        'repairs'   => ['title' => 'Repair & Service Log', 'desc' => 'Job history and parts used',        'tag' => 'Repair',      'dated' => true],
        'low-stock' => ['title' => 'Low Stock Report',     'desc' => 'Items at or below reorder level',   'tag' => 'Inventory',   'dated' => false],
    ];

    public const BUCKETS = ['Current', '1–30 days', '31–60 days', '61–90 days', 'Over 90 days'];

    public function index(Request $request)
    {
        return view('reports.index', [
            'reports' => self::REPORTS,
            'from'    => $request->input('from', now()->startOfMonth()->toDateString()),
            'to'      => $request->input('to', now()->toDateString()),
        ]);
    }

    public function show(Request $request, string $type)
    {
        abort_unless(array_key_exists($type, self::REPORTS), 404);

        $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date|after_or_equal:from',
        ]);

        $from = Carbon::parse($request->input('from', now()->startOfMonth()))->startOfDay();
        $to   = Carbon::parse($request->input('to', now()))->endOfDay();

        $data = [
            'type'        => $type,
            'meta'        => self::REPORTS[$type],
            'from'        => $from,
            'to'          => $to,
            'generatedBy' => $request->user()->name,
            'generatedAt' => now(),
            'isPdf'       => $request->boolean('pdf'),
        ] + $this->{Str::camel($type)}($from, $to);

        if ($data['isPdf']) {
            return Pdf::loadView('reports.document', $data)
                ->setPaper('a4', 'landscape')
                ->download("{$type}-report-" . now()->format('Y-m-d') . '.pdf');
        }

        return view('reports.document', $data);
    }

    private function sales(Carbon $from, Carbon $to): array
    {
        $sales = Sale::with('customer', 'receivable')
            ->whereBetween('sale_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('sale_date')->orderBy('id')
            ->get();

        return [
            'rows'   => $sales,
            'totals' => [
                'count'       => $sales->count(),
                'amount'      => $sales->sum('total_amount'),
                'outstanding' => $sales->sum(fn ($s) => $s->receivable?->balance ?? 0),
            ],
            'byCustomer' => $sales->groupBy(fn ($s) => $s->customer->name)
                ->map(fn ($group) => ['count' => $group->count(), 'amount' => $group->sum('total_amount')])
                ->sortByDesc('amount'),
        ];
    }

    private function inventory(Carbon $from, Carbon $to): array
    {
        $products = Product::orderBy('name')->get();

        $moves = InventoryMovement::whereBetween('movement_date', [$from, $to])
            ->selectRaw("product_id,
                SUM(CASE WHEN movement_type = 'in' THEN quantity ELSE 0 END) AS qty_in,
                SUM(CASE WHEN movement_type = 'out' THEN quantity ELSE 0 END) AS qty_out,
                SUM(CASE WHEN movement_type = 'adjustment' THEN quantity ELSE 0 END) AS qty_adj")
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        return [
            'rows'   => $products,
            'moves'  => $moves,
            'totals' => [
                'count' => $products->count(),
                'low'   => $products->filter(fn ($p) => $p->isLowStock())->count(),
                'value' => $products->sum(fn ($p) => $p->stock_qty * $p->unit_cost),
            ],
        ];
    }

    private function arAging(Carbon $from, Carbon $to): array
    {
        $rows = Receivable::with('customer', 'sale', 'repairJob')
            ->where('status', '!=', 'paid')
            ->orderBy('due_date')
            ->get()
            ->map(fn ($r) => $this->agingRow(
                $r->customer->name,
                $r->sale?->invoice_no ?? $r->repairJob?->job_no ?? '—',
                $r->due_date,
                $r->balance
            ));

        return ['rows' => $rows, 'buckets' => $this->bucketTotals($rows)];
    }

    private function apAging(Carbon $from, Carbon $to): array
    {
        $rows = SupplierInvoice::with('supplier')
            ->where('status', '!=', 'paid')
            ->orderBy('due_date')
            ->get()
            ->map(fn ($i) => $this->agingRow($i->supplier->name, $i->invoice_no, $i->due_date, $i->balance));

        return ['rows' => $rows, 'buckets' => $this->bucketTotals($rows)];
    }

    private function repairs(Carbon $from, Carbon $to): array
    {
        $jobs = RepairJob::with('customer', 'assessor', 'parts.product')
            ->whereBetween('date_received', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date_received')->orderBy('id')
            ->get();

        return [
            'rows'   => $jobs,
            'totals' => [
                'count'   => $jobs->count(),
                'service' => $jobs->sum('service_fee'),
                'parts'   => $jobs->sum('parts_cost'),
                'total'   => $jobs->sum('total_amount'),
            ],
        ];
    }

    private function lowStock(Carbon $from, Carbon $to): array
    {
        $products = Product::lowStock()->where('status', 'active')->orderBy('stock_qty')->orderBy('name')->get();

        return ['rows' => $products, 'totals' => ['count' => $products->count()]];
    }

    private function agingRow(string $party, string $ref, Carbon $due, $balance): array
    {
        $days = (int) max(0, $due->copy()->startOfDay()->diffInDays(today(), false));

        return [
            'party'   => $party,
            'ref'     => $ref,
            'due'     => $due,
            'days'    => $days,
            'bucket'  => match (true) {
                $days === 0 => 'Current',
                $days <= 30 => '1–30 days',
                $days <= 60 => '31–60 days',
                $days <= 90 => '61–90 days',
                default     => 'Over 90 days',
            },
            'balance' => (float) $balance,
        ];
    }

    private function bucketTotals($rows): array
    {
        return collect(self::BUCKETS)
            ->mapWithKeys(fn ($b) => [$b => $rows->where('bucket', $b)->sum('balance')])
            ->all();
    }
}