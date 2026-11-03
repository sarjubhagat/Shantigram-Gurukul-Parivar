<?php

namespace App\Observers\Parivar;

use App\Models\Parivar\GurukulInfo;

class GurukulInfoAuditObserver
{
    public function created(GurukulInfo $gk): void
    {
        PersonalInfoAuditObserver::gurukulSaved($gk, true);
    }

    public function updated(GurukulInfo $gk): void
    {
        if ($gk->wasChanged(['branch_id', 'start_year', 'end_year', 'total_year'])) {
            PersonalInfoAuditObserver::gurukulSaved($gk, false);
        }
    }

    public function deleted(GurukulInfo $gk): void
    {
        PersonalInfoAuditObserver::gurukulDeleted($gk);
    }
}
