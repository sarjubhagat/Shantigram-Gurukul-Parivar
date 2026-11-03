<?php

namespace App\Observers\Parivar;

use App\Models\Parivar\BusinessInfo;
use App\Models\Parivar\GurukulInfo;
use App\Models\Parivar\PersonalInfo;
use App\Models\Parivar\PersonalInfoAudit;
use App\Models\Parivar\PersonRelation;

class PersonalInfoAuditObserver
{
    private const TRACK_FIELDS = [
        'en_first_name', 'en_last_name', 'en_middle_name', 'en_grandfather_name',
        'display_name', 'gu_first_name', 'gu_last_name', 'gu_middle_name', 'gu_grandfather_name',
        'aadhaar_number', 'date_of_birth', 'gender', 'marital_status',
        'c_code_1', 'phone_1', 'c_code_2', 'phone_2',
        'native_place', 'current_address', 'is_gurukul_student', 'photo_key',
    ];

    public function created(PersonalInfo $member): void
    {
        PersonalInfoAudit::record(
            $member->id,
            'created',
            'Member #' . $member->unique_id . ' created'
        );
    }

    public function updated(PersonalInfo $member): void
    {
        foreach ($member->getDirty() as $field => $new) {
            if (! in_array($field, self::TRACK_FIELDS, true)) {
                continue;
            }
            if (in_array($field, ['updated_at', 'updated_by', 'created_by'], true)) {
                continue;
            }

            $old = $member->getOriginal($field);
            if ((string) $old === (string) $new) {
                continue;
            }

            $label = str_replace('_', ' ', $field);
            PersonalInfoAudit::record(
                $member->id,
                $field === 'photo_key' ? 'photo_updated' : 'updated',
                'Updated ' . $label,
                $field,
                $old,
                $new
            );
        }
    }

    public function deleted(PersonalInfo $member): void
    {
        // Soft-deleted rows may still reference audits via FK; skip if force-deleting cascade handles it.
        if (! method_exists($member, 'isForceDeleting') || ! $member->isForceDeleting()) {
            PersonalInfoAudit::record(
                $member->id,
                'deleted',
                'Member soft-deleted'
            );
        }
    }

    public static function relationCreated(PersonRelation $rel): void
    {
        $rel->loadMissing(['relation', 'relatedPerson']);
        PersonalInfoAudit::record(
            (int) $rel->personal_info_id,
            'relation_added',
            $rel->sentenceLabel() ?: 'Relation added',
            'relation',
            null,
            $rel->relation?->relation,
            [
                'relation_id' => $rel->relation_id,
                'related_id'  => $rel->related_personal_info_id,
            ]
        );
    }

    public static function relationDeleted(PersonRelation $rel): void
    {
        $rel->loadMissing(['relation', 'relatedPerson', 'person']);
        PersonalInfoAudit::record(
            (int) $rel->personal_info_id,
            'relation_removed',
            'Removed: ' . ($rel->sentenceLabel() ?: 'relation'),
            'relation',
            $rel->relation?->relation,
            null,
            [
                'relation_id' => $rel->relation_id,
                'related_id'  => $rel->related_personal_info_id,
            ]
        );
    }

    public static function businessSaved(BusinessInfo $biz, bool $created): void
    {
        $person = PersonalInfo::query()->where('unique_id', $biz->unique_id)->first();
        if (! $person) {
            return;
        }
        PersonalInfoAudit::record(
            $person->id,
            $created ? 'business_added' : 'business_updated',
            ($created ? 'Business added: ' : 'Business updated: ') . ($biz->business_name ?: 'record'),
            'business',
            null,
            $biz->business_name
        );
    }

    public static function businessDeleted(BusinessInfo $biz): void
    {
        $person = PersonalInfo::query()->where('unique_id', $biz->unique_id)->first();
        if (! $person) {
            return;
        }
        PersonalInfoAudit::record(
            $person->id,
            'business_removed',
            'Business removed: ' . ($biz->business_name ?: 'record'),
            'business',
            $biz->business_name,
            null
        );
    }

    public static function gurukulSaved(GurukulInfo $gk, bool $created): void
    {
        $person = PersonalInfo::query()->where('unique_id', $gk->unique_id)->first();
        if (! $person) {
            return;
        }
        $gk->loadMissing('branch');
        $name = $gk->branch?->branch_name ?: 'Gurukul stay';
        PersonalInfoAudit::record(
            $person->id,
            $created ? 'gurukul_added' : 'gurukul_updated',
            ($created ? 'Gurukul added: ' : 'Gurukul updated: ') . $name,
            'gurukul',
            null,
            $name
        );
    }

    public static function gurukulDeleted(GurukulInfo $gk): void
    {
        $person = PersonalInfo::query()->where('unique_id', $gk->unique_id)->first();
        if (! $person) {
            return;
        }
        $gk->loadMissing('branch');
        PersonalInfoAudit::record(
            $person->id,
            'gurukul_removed',
            'Gurukul removed: ' . ($gk->branch?->branch_name ?: 'record'),
            'gurukul',
            $gk->branch?->branch_name,
            null
        );
    }
}
