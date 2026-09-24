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
        $stats = [
            'month_sales'  => Sale::whereMonth('sale_date', now()->month)
                                  ->whereYear('sale_date', now()->year)
                                  ->sum('total_amount'),
            'month_count'  => Sale::whereMonth('sale_date', now()->month)
                                  ->whereYear('sale_date', now()->year)
                                  ->count(),
            'receivables'  => Receivable::sum('balance'),
            'ar_overdue'   => Receivable::overdue()->count(),
            'payables'     => SupplierInvoice::sum('balance'),
            'ap_overdue'   => SupplierInvoice::overdue()->count(),
            'low_stock'    => Product::lowStock()->count(),
            'open_repairs' => RepairJob::whereIn('status', ['for_assessment', 'in_progress'])->count(),
        ];

        return view('dashboard', [
            'stats'       => $stats,
            'recentSales' => Sale::with('customer')->latest('sale_date')->latest('id')->take(5)->get(),
            'lowStock'    => Product::lowStock()->orderBy('stock_qty')->take(5)->get(),
            'repairQueue' => RepairJob::with('customer', 'assessor')
                                ->whereIn('status', ['for_assessment', 'in_progress'])
                                ->latest('date_received')->take(5)->get(),
        ]);
    }
}