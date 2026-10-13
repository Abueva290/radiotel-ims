<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairJobAssignment extends Model
{
    protected $fillable = [
        'repair_job_id', 'technician_id', 'assigned_by', 'assigned_at',
        'expected_completion_date', 'ended_at', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'expected_completion_date' => 'date',
            'ended_at' => 'datetime',
        ];
    }

    public function repairJob()
    {
        return $this->belongsTo(RepairJob::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}