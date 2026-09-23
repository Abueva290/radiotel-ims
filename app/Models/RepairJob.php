<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairJob extends Model
{
    public const SERVICE_FEE_PER_UNIT = 350;

    protected $fillable = [
        'job_no', 'customer_id', 'assessed_by', 'unit_model', 'units',
        'assessment_notes', 'date_received', 'service_fee', 'parts_cost',
        'total_amount', 'status',
    ];

    protected function casts(): array
    {
        return [
            'date_received' => 'date',
            'service_fee' => 'decimal:2',
            'parts_cost' => 'decimal:2',
            'total_amount' => 'decimal:2',
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

    public const STATUSES = [
        'for_assessment' => 'For Assessment',
        'in_progress'    => 'In Progress',
        'completed'      => 'Completed',
        'released'       => 'Released',
    ];


    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function assessor()
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function parts()
    {
        return $this->hasMany(RepairPartUsed::class);
    }

    public function receivable()
    {
        return $this->hasOne(Receivable::class);
    }
}