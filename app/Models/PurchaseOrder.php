<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'po_no', 'supplier_id', 'customer_order_id', 'order_date', 'expected_date', 'status',
        'total_amount', 'remarks', 'created_by', 'approved_by', 'approved_at', 'sent_at',
        'received_by', 'received_date', 'supplier_dr_no', 'cancelled_by', 'cancelled_at',
    ];

    public const STATUSES = [
        'draft'     => 'For Approval',
        'approved'  => 'Approved',
        'sent'      => 'Sent to Supplier',
        'received'  => 'Received',
        'cancelled' => 'Cancelled',
    ];

    protected function casts(): array
    {
        return [
            'order_date'    => 'date',
            'expected_date' => 'date',
            'received_date' => 'date',
            'approved_at'   => 'datetime',
            'sent_at'       => 'datetime',
            'cancelled_at'  => 'datetime',
            'total_amount'  => 'decimal:2',
        ];
    }

    // PO-2026-0001 → next is PO-2026-0002
    public static function nextPoNo(): string
    {
        $year = now()->year;
        $count = static::where('po_no', 'like', "PO-{$year}-%")->count() + 1;

        return sprintf('PO-%d-%04d', $year, $count);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'approved', 'sent'], true);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function customerOrder()
    {
        return $this->belongsTo(CustomerOrder::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function invoices()
    {
        return $this->hasMany(SupplierInvoice::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}