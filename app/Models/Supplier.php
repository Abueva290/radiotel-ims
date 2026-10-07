<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = ['name', 'contact_person', 'phone', 'credit_terms_days', 'status'];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    public function invoices()
    {
        return $this->hasMany(SupplierInvoice::class);
    }
}