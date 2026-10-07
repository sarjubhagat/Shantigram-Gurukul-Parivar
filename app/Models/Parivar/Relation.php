<?php

namespace App\Models\Parivar;

use App\Traits\TracksAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Relation extends Model
{
    use SoftDeletes, TracksAudit;

    protected $table = 'relations';

    protected $fillable = [
        'relation',
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function personRelations(): HasMany
    {
        return $this->hasMany(PersonRelation::class, 'relation_id');
    }
}
