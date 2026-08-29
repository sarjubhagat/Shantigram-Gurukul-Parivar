<?php

namespace App\Models\Parivar;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcardQrToken extends Model
{
    protected $table = 'icard_qr_tokens';

    protected $fillable = [
        'unique_id',
        'qr_token',
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
}
