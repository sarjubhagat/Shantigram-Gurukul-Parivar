<?php

namespace App\Models\Parivar;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConnectedSaint extends Model
{
    public $timestamps = false;

    protected $table = 'connected_saints';

    protected $fillable = [
        'gurukul_info_id',
        'saints_id',
    ];

    public function gurukulInfo(): BelongsTo
    {
        return $this->belongsTo(GurukulInfo::class, 'gurukul_info_id');
    }

    public function saint(): BelongsTo
    {
        return $this->belongsTo(GurukulSaint::class, 'saints_id');
    }
}
