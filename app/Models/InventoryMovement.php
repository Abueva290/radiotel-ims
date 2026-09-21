<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $fillable = [
        'product_id', 'movement_type', 'quantity', 'reference_type',
        'reference_id', 'remarks', 'movement_date', 'recorded_by',
    ];

    protected function casts(): array
    {
        return ['movement_date' => 'datetime'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}