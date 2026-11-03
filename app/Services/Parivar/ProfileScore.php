<?php

namespace App\Services\Parivar;

use App\Models\Parivar\PersonalInfo;

class ProfileScore
{
    /**
     * Weighted checklist for profile completeness.
     *
     * @return array{score:int,filled:int,total:int,missing:list<array{key:string,label:string}>,items:list<array{key:string,label:string,done:bool}>}
     */
    public static function for(PersonalInfo $member): array
    {
        $member->loadMissing(['businesses', 'gurukulRecords', 'personRelations']);

        $checks = [
            ['key' => 'photo', 'label' => 'Passport photo', 'done' => (bool) $member->photo_key],
            ['key' => 'en_last_name', 'label' => 'Surname', 'done' => filled($member->en_last_name)],
            ['key' => 'en_first_name', 'label' => 'Name', 'done' => filled($member->en_first_name)],
            ['key' => 'en_middle_name', 'label' => 'Father name', 'done' => filled($member->en_middle_name)],
            ['key' => 'display_name', 'label' => 'Display name', 'done' => filled($member->display_name)],
            ['key' => 'aadhaar', 'label' => 'Aadhaar number', 'done' => filled($member->aadhaar_number)],
            ['key' => 'date_of_birth', 'label' => 'Date of birth', 'done' => (bool) $member->date_of_birth],
            ['key' => 'gender', 'label' => 'Gender', 'done' => filled($member->gender)],
            ['key' => 'marital_status', 'label' => 'Marital status', 'done' => filled($member->marital_status)],
            ['key' => 'phone_1', 'label' => 'Primary phone', 'done' => filled($member->phone_1)],
            ['key' => 'native_place', 'label' => 'Native place', 'done' => filled($member->native_place)],
            ['key' => 'current_address', 'label' => 'Current address', 'done' => filled($member->current_address)],
            ['key' => 'gu_name', 'label' => 'Gujarati name', 'done' => filled($member->gu_first_name) || filled($member->gu_last_name)],
            ['key' => 'relation', 'label' => 'At least one family relation', 'done' => $member->personRelations->isNotEmpty()],
            ['key' => 'business_or_gurukul', 'label' => 'Business or Gurukul record', 'done' => $member->businesses->isNotEmpty() || $member->gurukulRecords->isNotEmpty()],
        ];

        $total = count($checks);
        $filled = collect($checks)->where('done', true)->count();
        $score = $total > 0 ? (int) round(($filled / $total) * 100) : 0;

        $missing = collect($checks)
            ->where('done', false)
            ->map(fn ($c) => ['key' => $c['key'], 'label' => $c['label']])
            ->values()
            ->all();

        return [
            'score'   => $score,
            'filled'  => $filled,
            'total'   => $total,
            'missing' => $missing,
            'items'   => $checks,
            'level'   => self::level($score),
        ];
    }

    public static function level(int $score): string
    {
        return match (true) {
            $score >= 90 => 'excellent',
            $score >= 70 => 'good',
            $score >= 45 => 'fair',
            default      => 'low',
        };
    }
}
