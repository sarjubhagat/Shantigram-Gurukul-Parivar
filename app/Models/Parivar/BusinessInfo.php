<?php

namespace App\Models\Parivar;

use App\Traits\TracksAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessInfo extends Model
{
    use SoftDeletes, TracksAudit;

    protected $table = 'business_info';

    protected $fillable = [
        'unique_id',
        'business_name',
        'business_address',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'unique_id' => 'integer',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(PersonalInfo::class, 'unique_id', 'unique_id');
    }

    public function toFormArray(): array
    {
        return [
            'id'               => $this->id,
            'unique_id'        => $this->unique_id,
            'business_name'    => $this->business_name,
            'business_address' => $this->business_address,
        ];
    }
}
