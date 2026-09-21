<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairPartUsed extends Model
{
    protected $table = 'repair_parts_used';

    protected $fillable = ['repair_job_id', 'product_id', 'quantity_used', 'unit_cost', 'subtotal'];

    protected function casts(): array
    {
        return [
            'unit_cost' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function repairJob()
    {
        return $this->belongsTo(RepairJob::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}