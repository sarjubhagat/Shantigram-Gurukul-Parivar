<?php

namespace App\Observers;

use App\Models\Banner;
use App\Services\BannerService;
use Illuminate\Support\Facades\Auth;

class BannerObserver
{
    public function __construct(
        private BannerService $bannerService
    ) {}

    public function creating(Banner $banner): void
    {
        if (Auth::check()) {
            $banner->created_by = Auth::id();
        }
    }

    public function updating(Banner $banner): void
    {
        if (Auth::check()) {
            $banner->updated_by = Auth::id();
        }
    }

    /**
     * Remove stored file when the banner row is deleted.
     */
    public function deleting(Banner $banner): void
    {
        if ($banner->image_path && ! str_contains((string) $banner->image_path, 'temp/')) {
            $this->bannerService->deleteOldImage($banner->image_path);
        }
    }
}
