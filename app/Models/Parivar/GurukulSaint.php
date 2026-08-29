<?php

namespace App\Models\Parivar;

use App\Traits\TracksAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GurukulSaint extends Model
{
    use SoftDeletes, TracksAudit;

    protected $table = 'gurukul_saints';

    protected $fillable = [
        'name',
        'number',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function gurukulRecords(): BelongsToMany
    {
        return $this->belongsToMany(GurukulInfo::class, 'connected_saints', 'saints_id', 'gurukul_info_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function displayName(): string
    {
        return $this->number
            ? $this->name . ' (' . $this->number . ')'
            : $this->name;
    }
}
