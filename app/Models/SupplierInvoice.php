<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierInvoice extends Model
{
    protected $fillable = [
        'supplier_id', 'invoice_no', 'invoice_date', 'amount',
        'due_date', 'balance', 'status', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'paid')->whereDate('due_date', '<', now());
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }
}