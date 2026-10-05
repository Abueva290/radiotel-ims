<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\User;
use Illuminate\Http\Request;

class AuditTrailController extends Controller
{
    // Friendly names for the tables shown in the log
    public const TABLES = [
        'sales'               => 'Sale',
        'receivable_payments' => 'Customer Payment',
        'supplier_invoices'   => 'Supplier Invoice',
        'supplier_payments'   => 'Supplier Payment',
        'products'            => 'Product',
        'inventory_movements' => 'Stock Movement',
        'repair_jobs'         => 'Repair Job',
        'repair_parts_used'   => 'Repair Part',
        'customers'           => 'Customer',
        'users'               => 'User Account',
    ];

    public const ACTIONS = ['created', 'updated', 'deleted', 'login', 'logout'];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'action'  => 'nullable|in:' . implode(',', self::ACTIONS),
            'table'   => 'nullable|in:' . implode(',', array_keys(self::TABLES)),
            'from'    => 'nullable|date',
            'to'      => 'nullable|date|after_or_equal:from',
        ]);

        $logs = AuditTrail::with('user')
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($filters['action'] ?? null, fn ($q, $v) => $q->where('action', $v))
            ->when($filters['table'] ?? null, fn ($q, $v) => $q->where('table_affected', $v))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest('created_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('audit.index', [
            'logs'    => $logs,
            'filters' => $filters,
            'users'   => User::orderBy('name')->get(['id', 'name']),
            'tables'  => self::TABLES,
            'actions' => self::ACTIONS,
            'today'   => AuditTrail::whereDate('created_at', today())->count(),
        ]);
    }
}