<?php

namespace App\Models\Parivar;

use App\Traits\TracksAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GurukulInfo extends Model
{
    use SoftDeletes, TracksAudit;

    protected $table = 'gurukul_info';

    protected $fillable = [
        'unique_id',
        'branch_id',
        'start_year',
        'end_year',
        'total_year',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'unique_id'  => 'integer',
            'start_year' => 'integer',
            'end_year'   => 'integer',
            'total_year' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $record) {
            if ($record->start_year && $record->end_year && empty($record->total_year)) {
                $record->total_year = max(0, (int) $record->end_year - (int) $record->start_year);
            }
        });

        static::deleting(function (self $record) {
            $record->connectedSaints()->delete();
        });
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(PersonalInfo::class, 'unique_id', 'unique_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(GurukulBranch::class, 'branch_id');
    }

    public function connectedSaints(): HasMany
    {
        return $this->hasMany(ConnectedSaint::class, 'gurukul_info_id');
    }

    public function saints(): BelongsToMany
    {
        return $this->belongsToMany(GurukulSaint::class, 'connected_saints', 'gurukul_info_id', 'saints_id');
    }

    public function syncSaints(array $saintIds): void
    {
        $ids = collect($saintIds)->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $this->saints()->sync($ids->all());
    }

    public function toFormArray(): array
    {
        $this->loadMissing(['branch', 'saints']);

        return [
            'id'           => $this->id,
            'unique_id'    => $this->unique_id,
            'branch_id'    => $this->branch_id,
            'branch_name'  => $this->branch?->branch_name,
            'start_year'   => $this->start_year,
            'end_year'     => $this->end_year,
            'total_year'   => $this->total_year,
            'saint_ids'    => $this->saints->pluck('id')->values()->all(),
            'saint_names'  => $this->saints->map->displayName()->values()->all(),
        ];
    }
}
