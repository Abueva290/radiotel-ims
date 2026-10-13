<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'brand', 'category', 'is_serialized', 'warranty_months', 'unit_cost', 'selling_price',
        'stock_qty', 'reorder_level', 'status',
    ];

    public const CATEGORIES = [
        'two_way_radio' => 'Two-Way Radio',
        'antenna'       => 'Antenna',
        'accessory'     => 'Accessory',
        'part'          => 'Part',
    ];

    /** in_stock | low_stock | out_of_stock | discontinued  (see App\Support\Badge 'stock') */
    public function stockStatus(): string
    {
        if ($this->status === 'discontinued') {
            return 'discontinued';
        }
        if ($this->stock_qty <= 0) {
            return 'out_of_stock';
        }

        return $this->isLowStock() ? 'low_stock' : 'in_stock';
    }

    /** "1 year", "6 months", or null when the product has no warranty. */
    public function warrantyLabel(): ?string
    {
        if (! $this->warranty_months) {
            return null;
        }

        return $this->warranty_months % 12 === 0
            ? ($this->warranty_months / 12).' '.str('year')->plural($this->warranty_months / 12)
            : $this->warranty_months.' '.str('month')->plural($this->warranty_months);
    }

    /** Stock that has no serial number recorded yet (serialized products only). */
    public function unregisteredQty(): int
    {
        if (! $this->is_serialized) {
            return 0;
        }

        $registered = $this->relationLoaded('units')
            ? $this->units->where('status', 'in_stock')->count()
            : $this->units()->where('status', 'in_stock')->count();

        return max(0, $this->stock_qty - $registered);
    }

    protected function casts(): array
    {
        return [
            'unit_cost' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'is_serialized' => 'boolean',
        ];
    }

    // Product::lowStock()->get()  -> lahat ng nasa o mababa sa reorder level
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_qty', '<=', 'reorder_level');
    }

    /**
     * Stock filters shared by the Inventory page and the dashboard KPI cards,
     * so a card's count always equals the rows it opens.
     *   low      = active products at or below reorder level (includes out of stock)
     *   low_only = active, still has stock, but at or below reorder level
     *   out      = active and zero stock
     */
    public function scopeStockFilter($query, ?string $filter)
    {
        return match ($filter) {
            'low'          => $query->where('status', 'active')->lowStock(),
            'low_only'     => $query->where('status', 'active')->lowStock()->where('stock_qty', '>', 0),
            'out'          => $query->where('status', 'active')->where('stock_qty', '<=', 0),
            'discontinued' => $query->where('status', 'discontinued'),
            default        => $query,
        };
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

    public function units()
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function defects()
    {
        return $this->hasMany(FactoryDefect::class);
    }
}