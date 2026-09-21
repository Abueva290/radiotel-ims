<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['name', 'contact_person', 'phone', 'address'];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function repairJobs()
    {
        return $this->hasMany(RepairJob::class);
    }

    public function receivables()
    {
        return $this->hasMany(Receivable::class);
    }
}