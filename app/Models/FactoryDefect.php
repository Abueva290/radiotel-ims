<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FactoryDefect extends Model
{
    public const STATUSES = [
        'reported' => 'Reported',
        'under_inspection' => 'Under Inspection',
        'confirmed' => 'Confirmed Factory Defect',
        'returned_to_supplier' => 'Returned to Supplier',
        'repaired' => 'Repaired',
        'replaced' => 'Replaced',
        'resolved' => 'Resolved',
    ];

    public const CAUSES = [
        'unverified' => 'Not Yet Determined',
        'factory_defect' => 'Factory Defect',
        'customer_damage' => 'Customer-Caused Damage',
        'wear_and_tear' => 'Normal Wear & Tear',
        'other' => 'Other Issue',
    ];

    /** Cases still being worked on. */
    public const OPEN_STATUSES = ['reported', 'under_inspection', 'confirmed', 'returned_to_supplier', 'repaired', 'replaced'];

    /**
     * Reported → Under Inspection → Confirmed Factory Defect → Returned to Supplier / Repaired / Replaced → Resolved.
     * An inspection that finds another cause (customer damage, wear and tear) goes straight to Resolved.
     */
    public const TRANSITIONS = [
        'reported' => ['under_inspection'],
        'under_inspection' => ['confirmed', 'resolved'],
        'confirmed' => ['returned_to_supplier', 'repaired', 'replaced'],
        'returned_to_supplier' => ['replaced', 'resolved'],
        'repaired' => ['resolved'],
        'replaced' => ['resolved'],
        'resolved' => [],
    ];

    /** Roles that can inspect and update a defect case. */
    public const INSPECTOR_ROLES = ['admin', 'technical_head'];

    protected $fillable = [
        'defect_no', 'product_id', 'product_unit_id', 'serial_no', 'repair_job_id', 'supplier_id',
        'source', 'quantity', 'description', 'cause', 'status', 'date_discovered', 'reported_by',
        'inspected_by', 'inspection_findings', 'action_taken', 'stock_quarantined', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'date_discovered' => 'date',
            'resolved_at' => 'datetime',
            'stock_quarantined' => 'boolean',
        ];
    }

    public static function nextDefectNo(): string
    {
        $prefix = 'FD-'.now()->year.'-';
        $last = static::where('defect_no', 'like', $prefix.'%')->orderByDesc('defect_no')->value('defect_no');
        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return sprintf('%s%04d', $prefix, $next);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    public function scopeFilter($query, array $f)
    {
        $status = $f['status'] ?? null;
        $cause = $f['cause'] ?? null;

        return $query
            ->when($status === 'open', fn ($q) => $q->whereIn('status', self::OPEN_STATUSES))
            ->when($status === 'awaiting', fn ($q) => $q->whereIn('status', ['reported', 'under_inspection']))
            ->when($status && isset(self::STATUSES[$status]), fn ($q) => $q->where('status', $status))
            ->when($cause === 'not_factory', fn ($q) => $q->whereIn('cause', ['customer_damage', 'wear_and_tear', 'other']))
            ->when($cause && isset(self::CAUSES[$cause]), fn ($q) => $q->where('cause', $cause));
    }

    public function nextStatuses(): array
    {
        return self::TRANSITIONS[$this->status] ?? [];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }

    public function repairJob()
    {
        return $this->belongsTo(RepairJob::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function logs()
    {
        return $this->hasMany(FactoryDefectLog::class)->latest('changed_at')->latest('id');
    }
}