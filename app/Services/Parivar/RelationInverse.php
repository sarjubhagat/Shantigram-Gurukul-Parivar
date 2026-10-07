<?php

namespace App\Services\Parivar;

use App\Models\Parivar\PersonalInfo;
use App\Models\Parivar\Relation;

class RelationInverse
{
    /**
     * Resolve the inverse relation name for the other person.
     *
     * Example: Rahul (male) → Son → Amit
     *   Inverse on Amit: Father (because Rahul is male)
     *
     * Example: Amit → Father → Rahul
     *   Inverse on Rahul: Son or Daughter (from Amit's gender)
     */
    public static function resolveName(string $relationName, ?PersonalInfo $fromPerson, ?PersonalInfo $relatedPerson): ?string
    {
        $name = self::normalize($relationName);
        $fromGender = self::gender($fromPerson);
        $relatedGender = self::gender($relatedPerson);

        return match ($name) {
            'father', 'mother' => self::childOf($relatedGender),
            'son', 'daughter' => self::parentOf($fromGender),
            'grandfather', 'grandmother' => self::grandchildOf($relatedGender),
            'grandson', 'granddaughter' => self::grandparentOf($fromGender),
            'husband' => 'Wife',
            'wife' => 'Husband',
            'brother' => self::siblingOf($relatedGender, 'Brother'),
            'sister' => self::siblingOf($relatedGender, 'Sister'),
            'uncle' => self::nephewNieceOf($relatedGender),
            'aunt' => self::nephewNieceOf($relatedGender),
            'nephew', 'niece' => self::uncleAuntOf($fromGender),
            'cousin' => 'Cousin',
            'friend' => 'Friend',
            'father-in-law', 'mother-in-law' => self::inLawChildOf($relatedGender),
            'son-in-law', 'daughter-in-law' => self::inLawParentOf($fromGender),
            'brother-in-law' => self::siblingInLawOf($relatedGender, 'Brother-in-law'),
            'sister-in-law' => self::siblingInLawOf($relatedGender, 'Sister-in-law'),
            'other' => 'Other',
            default => null,
        };
    }

    public static function resolveRelation(string $relationName, ?PersonalInfo $fromPerson, ?PersonalInfo $relatedPerson): ?Relation
    {
        $inverseName = self::resolveName($relationName, $fromPerson, $relatedPerson);

        if (! $inverseName) {
            return null;
        }

        return Relation::query()
            ->whereRaw('lower(relation) = ?', [mb_strtolower($inverseName)])
            ->where('is_active', true)
            ->first();
    }

    private static function normalize(string $name): string
    {
        return mb_strtolower(trim($name));
    }

    private static function gender(?PersonalInfo $person): ?string
    {
        $g = mb_strtolower((string) ($person?->gender ?? ''));

        return in_array($g, ['male', 'female'], true) ? $g : null;
    }

    private static function parentOf(?string $fromGender): string
    {
        return $fromGender === 'female' ? 'Mother' : 'Father';
    }

    private static function childOf(?string $relatedGender): string
    {
        return $relatedGender === 'female' ? 'Daughter' : 'Son';
    }

    private static function grandparentOf(?string $fromGender): string
    {
        return $fromGender === 'female' ? 'Grandmother' : 'Grandfather';
    }

    private static function grandchildOf(?string $relatedGender): string
    {
        return $relatedGender === 'female' ? 'Granddaughter' : 'Grandson';
    }

    private static function nephewNieceOf(?string $relatedGender): string
    {
        return $relatedGender === 'female' ? 'Niece' : 'Nephew';
    }

    private static function uncleAuntOf(?string $fromGender): string
    {
        return $fromGender === 'female' ? 'Aunt' : 'Uncle';
    }

    private static function inLawParentOf(?string $fromGender): string
    {
        return $fromGender === 'female' ? 'Mother-in-law' : 'Father-in-law';
    }

    private static function inLawChildOf(?string $relatedGender): string
    {
        return $relatedGender === 'female' ? 'Daughter-in-law' : 'Son-in-law';
    }

    private static function siblingOf(?string $relatedGender, string $fallback): string
    {
        if ($relatedGender === 'female') {
            return 'Sister';
        }
        if ($relatedGender === 'male') {
            return 'Brother';
        }

        return $fallback;
    }

    private static function siblingInLawOf(?string $relatedGender, string $fallback): string
    {
        if ($relatedGender === 'female') {
            return 'Sister-in-law';
        }
        if ($relatedGender === 'male') {
            return 'Brother-in-law';
        }

        return $fallback;
    }
}
