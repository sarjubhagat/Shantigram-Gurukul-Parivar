<?php

namespace App\Observers\Parivar;

use App\Models\Parivar\BusinessInfo;

class BusinessInfoAuditObserver
{
    public function created(BusinessInfo $biz): void
    {
        PersonalInfoAuditObserver::businessSaved($biz, true);
    }

    public function updated(BusinessInfo $biz): void
    {
        if ($biz->wasChanged(['business_name', 'business_address'])) {
            PersonalInfoAuditObserver::businessSaved($biz, false);
        }
    }

    public function deleted(BusinessInfo $biz): void
    {
        PersonalInfoAuditObserver::businessDeleted($biz);
    }
}
