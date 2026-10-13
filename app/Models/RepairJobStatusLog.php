<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairJobStatusLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'repair_job_id', 'from_status', 'to_status', 'changed_by', 'remarks', 'is_override', 'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_override' => 'boolean',
            'changed_at' => 'datetime',
        ];
    }

    public function repairJob()
    {
        return $this->belongsTo(RepairJob::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}