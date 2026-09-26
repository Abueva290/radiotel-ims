<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLES = [
        'admin'          => 'Admin / Operational Manager',
        'secretary'      => 'Secretary',
        'technical_head' => 'Technical Head',
        'staff'          => 'Staff',
    ];

    protected $fillable = [
        'name', 'email', 'password', 'role', 'status',
        'last_login_at', 'must_change_password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'last_login_at'        => 'datetime',
            'must_change_password' => 'boolean',
            'password'             => 'hashed',
        ];
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'created_by');
    }

    public function auditTrails()
    {
        return $this->hasMany(AuditTrail::class);
    }
}