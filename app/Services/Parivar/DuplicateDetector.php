<?php

namespace App\Services\Parivar;

use App\Models\Parivar\PersonalInfo;
use Illuminate\Support\Collection;

class DuplicateDetector
{
    /**
     * @return list<array{id:int,unique_id:int,full_name:string,phone:?string,aadhaar:?string,reasons:list<string>,score:int,view_url:string,edit_url:string}>
     */
    public static function for(PersonalInfo $member, int $limit = 12): array
    {
        $candidates = collect();

        $aadhaar = PersonalInfo::normalizeAadhaar($member->aadhaar_number);
        if ($aadhaar && ! str_starts_with($aadhaar, '0000')) {
            PersonalInfo::query()
                ->where('id', '!=', $member->id)
                ->where('aadhaar_number', $aadhaar)
                ->limit(8)
                ->get()
                ->each(fn (PersonalInfo $p) => self::push($candidates, $p, 'Same Aadhaar', 100));
        }

        $phone = preg_replace('/\D+/', '', (string) $member->phone_1);
        if (strlen($phone) >= 8) {
            PersonalInfo::query()
                ->where('id', '!=', $member->id)
                ->where(function ($q) use ($phone) {
                    $q->whereRaw("regexp_replace(coalesce(phone_1, ''), '[^0-9]', '', 'g') = ?", [$phone])
                        ->orWhereRaw("regexp_replace(coalesce(phone_2, ''), '[^0-9]', '', 'g') = ?", [$phone]);
                })
                ->limit(8)
                ->get()
                ->each(fn (PersonalInfo $p) => self::push($candidates, $p, 'Same primary phone', 90));
        }

        $phone2 = preg_replace('/\D+/', '', (string) $member->phone_2);
        if (strlen($phone2) >= 8) {
            PersonalInfo::query()
                ->where('id', '!=', $member->id)
                ->where(function ($q) use ($phone2) {
                    $q->whereRaw("regexp_replace(coalesce(phone_1, ''), '[^0-9]', '', 'g') = ?", [$phone2])
                        ->orWhereRaw("regexp_replace(coalesce(phone_2, ''), '[^0-9]', '', 'g') = ?", [$phone2]);
                })
                ->limit(8)
                ->get()
                ->each(fn (PersonalInfo $p) => self::push($candidates, $p, 'Same secondary phone', 80));
        }

        $first = mb_strtolower(trim((string) $member->en_first_name));
        $last = mb_strtolower(trim((string) $member->en_last_name));
        $middle = mb_strtolower(trim((string) $member->en_middle_name));

        if ($first !== '' && $last !== '') {
            $nameQ = PersonalInfo::query()
                ->where('id', '!=', $member->id)
                ->whereRaw('lower(trim(coalesce(en_last_name, \'\'))) = ?', [$last])
                ->whereRaw('lower(trim(coalesce(en_first_name, \'\'))) = ?', [$first])
                ->limit(10)
                ->get();

            foreach ($nameQ as $p) {
                $reason = 'Same surname + name';
                $score = 70;
                $pMiddle = mb_strtolower(trim((string) $p->en_middle_name));
                if ($middle !== '' && $pMiddle !== '' && $middle === $pMiddle) {
                    $reason = 'Same surname + name + father name';
                    $score = 85;
                }
                self::push($candidates, $p, $reason, $score);
            }
        }

        return $candidates
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->all();
    }

    private static function push(Collection $bag, PersonalInfo $p, string $reason, int $score): void
    {
        $id = (int) $p->id;
        if ($bag->has($id)) {
            $row = $bag->get($id);
            if (! in_array($reason, $row['reasons'], true)) {
                $row['reasons'][] = $reason;
            }
            $row['score'] = max($row['score'], $score);
            $bag->put($id, $row);

            return;
        }

        $bag->put($id, [
            'id'         => $p->id,
            'unique_id'  => $p->unique_id,
            'full_name'  => $p->display_name ?: $p->englishFullName(),
            'phone'      => $p->primaryPhone(),
            'aadhaar'    => $p->aadhaarFormatted(),
            'reasons'    => [$reason],
            'score'      => $score,
            'view_url'   => url('/admin/parivar/' . $p->id . '/view'),
            'edit_url'   => url('/admin/parivar/' . $p->id . '/edit'),
        ]);
    }
}
