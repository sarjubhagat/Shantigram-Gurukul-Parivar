<?php

namespace App\Models\Parivar;

use App\Services\R2StorageService;
use Illuminate\Database\Eloquent\Model;

class IcardImage extends Model
{
    protected $table = 'icard_images';

    protected $fillable = [
        'image_key',
        'image_path',
        'color_codes',
    ];

    protected function casts(): array
    {
        return [
            'color_codes' => 'array',
        ];
    }

    public function signedUrl(?int $ttl = null): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return app(R2StorageService::class)->signedUrl(
            $this->image_path,
            $ttl ?? (int) config('icard.storage.signed_url_ttl', 180)
        );
    }
}
