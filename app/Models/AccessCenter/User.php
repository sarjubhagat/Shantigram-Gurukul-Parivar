<?php

namespace App\Models\AccessCenter;

use App\Traits\TracksAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes, TracksAudit;

    protected $fillable = [
        'name',
        'email',
        'number',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->roles()->where('unique_key', 'super-admin')->exists();
    }

    /** Super-admin and admin roles see all library records; others see only their own. */
    public function canViewAllLibraryRecords(): bool
    {
        return $this->roles()->whereIn('unique_key', ['super-admin', 'admin'])->exists();
    }
}
