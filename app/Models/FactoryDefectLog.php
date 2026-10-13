<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FactoryDefectLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['factory_defect_id', 'from_status', 'to_status', 'changed_by', 'remarks', 'changed_at'];

    protected function casts(): array
    {
        return ['changed_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}