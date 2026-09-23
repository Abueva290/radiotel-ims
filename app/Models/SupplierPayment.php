<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    protected $fillable = [
        'supplier_invoice_id', 'payment_date', 'amount_paid',
        'payment_method', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount_paid' => 'decimal:2',
        ];
    }

    public static function nextVoucherNo(): string
    {
        return sprintf('PV-%04d', static::count() + 1);
    }

    public function invoice()
    {
        return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}