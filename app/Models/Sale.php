<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'customer_id', 'created_by', 'invoice_no', 'dr_no',
        'sale_date', 'total_amount', 'status',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    // SI-2026-0088 → next is SI-2026-0089
    public static function nextInvoiceNo(): string
    {
        $year = now()->year;
        $count = static::whereYear('sale_date', $year)->count() + 1;

        return sprintf('SI-%d-%04d', $year, $count);
    }

    public static function nextDrNo(): string
    {
        return sprintf('DR-%04d', static::count() + 1);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function receivable()
    {
        return $this->hasOne(Receivable::class);
    }
        /**
     * Filters for the Sales page (also used by the dashboard KPI link).
     * "overdue" = not fully paid and the receivable is past its due date.
     */
    public function scopeFilter($query, array $f)
    {
        $status = $f['status'] ?? null;

        return $query
            ->when($status === 'overdue', fn ($q) => $q->where('status', '!=', 'paid')
                ->whereHas('receivable', fn ($r) => $r->whereDate('due_date', '<', today())))
            ->when($status === 'outstanding', fn ($q) => $q->where('status', '!=', 'paid'))
            ->when($status && ! in_array($status, ['overdue', 'outstanding'], true), fn ($q) => $q->where('status', $status))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('sale_date', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('sale_date', '<=', $v));
    }
}