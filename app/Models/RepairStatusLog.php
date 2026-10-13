<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// One row per status change of a repair job: what changed, who did it, and when
class RepairStatusLog extends Model
{
    protected $fillable = ['repair_job_id', 'from_status', 'to_status', 'changed_by', 'remarks'];

    public function repairJob()
    {
        return $this->belongsTo(RepairJob::class);
    }

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}