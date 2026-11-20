<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerOrder extends Model
{
    protected $fillable = [
        'order_no', 'customer_id', 'customer_po_no', 'order_date', 'needed_by', 'order_channel',
        'status', 'total_amount', 'remarks', 'sale_id', 'created_by',
    ];

    public const STATUSES = [
        'pending'   => 'Pending',
        'ordered'   => 'Ordered from Supplier',
        'completed' => 'Completed (Sold)',
        'cancelled' => 'Cancelled',
    ];

    public const CHANNELS = [
        'phone'   => 'Phone Call',
        'walk_in' => 'Walk-in',
    ];

    protected function casts(): array
    {
        return [
            'order_date'   => 'date',
            'needed_by'    => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    // CO-2026-0001 → next is CO-2026-0002
    public static function nextOrderNo(): string
    {
        $year = now()->year;
        $count = static::where('order_no', 'like', "CO-{$year}-%")->count() + 1;

        return sprintf('CO-%d-%04d', $year, $count);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['pending', 'ordered'], true);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['pending', 'ordered']);
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
        return $this->hasMany(CustomerOrderItem::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}