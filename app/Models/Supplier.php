<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = ['name', 'contact_person', 'phone', 'credit_terms_days'];

    public function invoices()
    {
        return $this->hasMany(SupplierInvoice::class);
    }
}