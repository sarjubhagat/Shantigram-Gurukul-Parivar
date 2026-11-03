<?php

namespace App\Providers;

use App\Models\Parivar\BusinessInfo;
use App\Models\Parivar\GurukulInfo;
use App\Models\Parivar\PersonalInfo;
use App\Models\Parivar\PersonRelation;
use App\Observers\Parivar\BusinessInfoAuditObserver;
use App\Observers\Parivar\GurukulInfoAuditObserver;
use App\Observers\Parivar\PersonalInfoAuditObserver;
use App\Observers\Parivar\PersonRelationAuditObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        PersonalInfo::observe(PersonalInfoAuditObserver::class);
        PersonRelation::observe(PersonRelationAuditObserver::class);
        BusinessInfo::observe(BusinessInfoAuditObserver::class);
        GurukulInfo::observe(GurukulInfoAuditObserver::class);
    }
}
