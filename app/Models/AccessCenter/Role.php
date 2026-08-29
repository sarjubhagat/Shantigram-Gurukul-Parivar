<?php

namespace App\Models\AccessCenter;

use App\Traits\TracksAudit;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use TracksAudit;

    public function getFillable(): array
    {
        return array_merge(parent::getFillable(), ['unique_key','name','guard_name']);
    }

    protected static function booted(): void
    {
        static::creating(function (self $role) {
            if (empty($role->guard_name)) {
                $role->guard_name = 'web';
            }
            if (empty($role->unique_key)) {
                $role->unique_key = static::generateUniqueKey($role->name);
            }
        });
    }

    public static function generateUniqueKey(string $name, ?int $exceptId = null): string
    {
        $base = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $base = trim($base, '-') ?: 'role';

        $key = $base;
        $i   = 2;
        while (static::when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
                      ->where('unique_key', $key)->exists()) {
            $key = $base . '-' . $i++;
        }

        return $key;
    }
}
