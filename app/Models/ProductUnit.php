<?php

namespace App\Models;

use App\Support\Warranty;
use Illuminate\Database\Eloquent\Model;

/** One physical, serial-numbered unit of a serialized product. */
class ProductUnit extends Model
{
    public const STATUSES = [
        'in_stock' => 'In Stock',
        'sold' => 'Sold',
        'under_inspection' => 'Under Inspection',
        'defective' => 'Defective',
        'returned_to_supplier' => 'Returned to Supplier',
        'written_off' => 'Written Off',
    ];

    protected $fillable = [
        'product_id', 'serial_no', 'status', 'sale_item_id', 'received_at',
        'warranty_start', 'warranty_end', 'remarks', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'warranty_start' => 'date',
            'warranty_end' => 'date',
        ];
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'in_stock');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function repairUnits()
    {
        return $this->hasMany(RepairJobUnit::class);
    }

    public function defects()
    {
        return $this->hasMany(FactoryDefect::class);
    }

    public function warrantyStatus(): string
    {
        return Warranty::status($this->warranty_start, $this->warranty_end);
    }

    /** Normalise how serials are typed so "abc 123" and "ABC123 " match. */
    public static function normalize(?string $serial): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $serial));
    }

    /** Split a textarea (one serial per line, or comma separated) into clean serials. */
    public static function parseList(?string $text): array
    {
        return collect(preg_split('/[\r\n,]+/', (string) $text))
            ->map(fn ($s) => self::normalize($s))
            ->filter()
            ->values()
            ->all();
    }
}