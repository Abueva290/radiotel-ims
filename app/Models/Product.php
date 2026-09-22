<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'brand', 'category', 'unit_cost', 'selling_price',
        'stock_qty', 'reorder_level', 'status',
    ];

        public const CATEGORIES = [
        'two_way_radio' => 'Two-Way Radio',
        'antenna'       => 'Antenna',
        'accessory'     => 'Accessory',
        'part'          => 'Part',
    ];

    public function stockStatus(): string
    {
        if ($this->stock_qty <= 0) {
            return 'Out of Stock';
        }

        return $this->isLowStock() ? 'Low Stock' : 'In Stock';
    }

    protected function casts(): array
    {
        return [
            'unit_cost' => 'decimal:2',
            'selling_price' => 'decimal:2',
        ];
    }

    // Product::lowStock()->get()  -> lahat ng nasa o mababa sa reorder level
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_qty', '<=', 'reorder_level');
    }

    public function isLowStock(): bool
    {
        return $this->stock_qty <= $this->reorder_level;
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function repairPartsUsed()
    {
        return $this->hasMany(RepairPartUsed::class);
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }
}