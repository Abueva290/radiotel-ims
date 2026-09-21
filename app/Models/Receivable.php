<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receivable extends Model
{
    protected $fillable = [
        'sale_id', 'repair_job_id', 'customer_id', 'amount_due',
        'due_date', 'balance', 'status',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount_due' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }

    // Receivable::overdue()->get()
    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'paid')->whereDate('due_date', '<', now());
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function repairJob()
    {
        return $this->belongsTo(RepairJob::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments()
    {
        return $this->hasMany(ReceivablePayment::class);
    }
}