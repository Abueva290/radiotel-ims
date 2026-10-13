<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairJob extends Model
{
    public const SERVICE_FEE_PER_UNIT = 350;

    public const STATUSES = [
        'for_assessment' => 'For Assessment',
        'in_progress'    => 'In Progress',
        'completed'      => 'Completed',
        'released'       => 'Released',
    ];

    // A job only moves forward, one step at a time
    public const NEXT_STATUS = [
        'for_assessment' => 'in_progress',
        'in_progress'    => 'completed',
        'completed'      => 'released',
    ];

    public const WARRANTY = [
        'out_of_warranty' => 'Out of warranty',
        'in_warranty'     => 'In warranty',
    ];

    protected $fillable = [
        'job_no', 'customer_id', 'assessed_by', 'assigned_to', 'assigned_at',
        'unit_model', 'units', 'serial_numbers',
        'warranty_status', 'warranty_until', 'is_factory_defect', 'defect_notes',
        'assessment_notes', 'date_received', 'expected_completion',
        'service_fee', 'parts_cost', 'total_amount', 'status',
        'started_at', 'completed_at', 'completed_by', 'released_at',
    ];

    protected function casts(): array
    {
        return [
            'date_received'       => 'date',
            'expected_completion' => 'date',
            'warranty_until'      => 'date',
            'is_factory_defect'   => 'boolean',
            'assigned_at'         => 'datetime',
            'started_at'          => 'datetime',
            'completed_at'        => 'datetime',
            'released_at'         => 'datetime',
            'service_fee'         => 'decimal:2',
            'parts_cost'          => 'decimal:2',
            'total_amount'        => 'decimal:2',
        ];
    }

    // total = (units x 350) + sum of parts used
    public function recalculateTotals(): void
    {
        $this->service_fee = $this->units * self::SERVICE_FEE_PER_UNIT;
        $this->parts_cost = $this->parts()->sum('subtotal');
        $this->total_amount = $this->service_fee + $this->parts_cost;
        $this->save();
    }

    public static function nextJobNo(): string
    {
        $year = now()->year;
        $count = static::whereYear('date_received', $year)->count() + 1;

        return sprintf('RS-%d-%04d', $year, $count);
    }

    // Serial numbers are typed one per line; return them as a clean list
    public function serialList(): array
    {
        return collect(preg_split('/\r\n|\r|\n|,/', (string) $this->serial_numbers))
            ->map(fn ($s) => trim($s))
            ->filter()
            ->values()
            ->all();
    }

    public function nextStatus(): ?string
    {
        return self::NEXT_STATUS[$this->status] ?? null;
    }

    public function isLocked(): bool
    {
        return in_array($this->status, ['completed', 'released']);
    }

    // Past the target date and not yet finished
    public function isOverdue(): bool
    {
        return $this->expected_completion
            && ! $this->isLocked()
            && $this->expected_completion->lt(now()->startOfDay());
    }

    // Warranty is only "active" when marked in warranty and not past its expiry date
    public function warrantyActive(): bool
    {
        return $this->warranty_status === 'in_warranty'
            && (! $this->warranty_until || $this->warranty_until->gte(now()->startOfDay()));
    }

    // Whole days from receiving the unit to completing (or until today if still open)
    public function turnaroundDays(): int
    {
        $end = $this->completed_at ?? now();

        return (int) $this->date_received->startOfDay()->diffInDays($end->copy()->startOfDay());
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['for_assessment', 'in_progress']);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function assessor()
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function completer()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function parts()
    {
        return $this->hasMany(RepairPartUsed::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(RepairStatusLog::class)->latest('id');
    }

    public function receivable()
    {
        return $this->hasOne(Receivable::class);
    }
}