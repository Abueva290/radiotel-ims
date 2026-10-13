<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Receivable;
use App\Models\RepairJob;
use App\Models\Sale;
use App\Models\SupplierInvoice;

class DashboardController extends Controller
{
    public function index()
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        // The Sales page filter used by the "This month's sales" card
        $monthSales = Sale::filter(['from' => $monthStart, 'to' => $monthEnd]);

        // Every count below uses the same query as the page its card opens,
        // so the number on the card always equals the rows shown there.
        $stats = [
            'month_sales'  => (clone $monthSales)->sum('total_amount'),
            'month_count'  => (clone $monthSales)->count(),
            'month_from'   => $monthStart,
            'month_to'     => $monthEnd,
            'receivables'  => Receivable::sum('balance'),
            'ar_overdue'   => Receivable::overdue()->count(),
            'payables'     => SupplierInvoice::sum('balance'),
            'ap_overdue'   => SupplierInvoice::overdue()->count(),
            'low_stock'    => Product::stockFilter('low')->count(),
            'open_repairs' => RepairJob::filter(['status' => 'open'])->count(),
            // Repair status breakdown
            'for_assessment'   => RepairJob::filter(['status' => 'for_assessment'])->count(),
            'in_progress'      => RepairJob::filter(['status' => 'in_progress'])->count(),
            'overdue'          => RepairJob::filter(['overdue' => 1])->count(),
            'awaiting_release' => RepairJob::filter(['status' => 'completed'])->count(),
            'warranty'         => RepairJob::filter(['warranty' => 'active'])->count(),
        ];

        return view('dashboard', [
            'stats'       => $stats,
            'recentSales' => Sale::with('customer', 'receivable')->latest('sale_date')->latest('id')->take(5)->get(),
            'lowStock'    => Product::stockFilter('low')->orderBy('stock_qty')->take(5)->get(),
            'repairQueue' => RepairJob::with('customer', 'technician')
                                ->filter(['status' => 'open'])
                                ->orderByRaw('target_completion_date IS NULL, target_completion_date')
                                ->latest('date_received')->take(5)->get(),
        ]);
    }
}