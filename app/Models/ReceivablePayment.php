<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceivablePayment extends Model
{
    protected $fillable = [
        'receivable_id', 'receipt_no', 'amount_paid',
        'payment_method', 'payment_date', 'received_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount_paid' => 'decimal:2',
        ];
    }

    public function receivable()
    {
        return $this->belongsTo(Receivable::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}