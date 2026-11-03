<?php

namespace App\Observers\Parivar;

use App\Models\Parivar\PersonRelation;

class PersonRelationAuditObserver
{
    public function created(PersonRelation $rel): void
    {
        PersonalInfoAuditObserver::relationCreated($rel);
    }

    public function deleted(PersonRelation $rel): void
    {
        PersonalInfoAuditObserver::relationDeleted($rel);
    }
}
