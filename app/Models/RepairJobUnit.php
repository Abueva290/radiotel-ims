<?php

namespace App\Models;

use App\Support\Warranty;
use Illuminate\Database\Eloquent\Model;

/** A unit (by serial number) that a customer brought in under a repair job. */
class RepairJobUnit extends Model
{
    protected $fillable = ['repair_job_id', 'serial_no', 'product_unit_id', 'warranty_start', 'warranty_end'];

    protected function casts(): array
    {
        return [
            'warranty_start' => 'date',
            'warranty_end' => 'date',
        ];
    }

    public function repairJob()
    {
        return $this->belongsTo(RepairJob::class);
    }

    /** Set when the serial matches a unit Radiotel sold. */
    public function productUnit()
    {
        return $this->belongsTo(ProductUnit::class);
    }

    public function warrantyStatus(): string
    {
        return Warranty::status($this->warranty_start, $this->warranty_end);
    }
}