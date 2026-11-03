<?php

namespace App\Services\Parivar;

use App\Models\Parivar\PersonalInfo;
use App\Models\Parivar\PersonRelation;
use Illuminate\Support\Collection;

class FamilyTreeBuilder
{
    private const PARENTS = ['father', 'mother'];
    private const CHILDREN = ['son', 'daughter'];
    private const SPOUSES = ['husband', 'wife'];
    private const SIBLINGS = ['brother', 'sister'];
    private const GRANDPARENTS = ['grandfather', 'grandmother'];
    private const GRANDCHILDREN = ['grandson', 'granddaughter'];

    /**
     * @return array{
     *   center: array,
     *   parents: list<array>,
     *   spouses: list<array>,
     *   siblings: list<array>,
     *   children: list<array>,
     *   grandparents: list<array>,
     *   grandchildren: list<array>,
     *   others: list<array>,
     *   edge_count: int
     * }
     */
    public static function for(PersonalInfo $member): array
    {
        $member->loadMissing(['personRelations.relation', 'personRelations.relatedPerson']);

        $buckets = [
            'parents' => [],
            'spouses' => [],
            'siblings' => [],
            'children' => [],
            'grandparents' => [],
            'grandchildren' => [],
            'others' => [],
        ];

        $seen = [];

        foreach ($member->personRelations as $rel) {
            $related = $rel->relatedPerson;
            if (! $related) {
                continue;
            }

            $rid = (int) $related->id;
            if (isset($seen[$rid])) {
                continue;
            }
            $seen[$rid] = true;

            $relName = mb_strtolower((string) ($rel->relation?->relation ?? ''));
            $node = self::node($related, $rel->relation?->relation, $rel->sentenceLabel());

            $bucket = match (true) {
                in_array($relName, self::PARENTS, true) => 'parents',
                in_array($relName, self::CHILDREN, true) => 'children',
                in_array($relName, self::SPOUSES, true) => 'spouses',
                in_array($relName, self::SIBLINGS, true) => 'siblings',
                in_array($relName, self::GRANDPARENTS, true) => 'grandparents',
                in_array($relName, self::GRANDCHILDREN, true) => 'grandchildren',
                default => 'others',
            };

            $buckets[$bucket][] = $node;
        }

        return [
            'center' => self::node($member, null, null),
            'parents' => $buckets['parents'],
            'spouses' => $buckets['spouses'],
            'siblings' => $buckets['siblings'],
            'children' => $buckets['children'],
            'grandparents' => $buckets['grandparents'],
            'grandchildren' => $buckets['grandchildren'],
            'others' => $buckets['others'],
            'edge_count' => count($seen),
        ];
    }

    private static function node(PersonalInfo $p, ?string $relationName, ?string $label): array
    {
        return [
            'id'            => $p->id,
            'unique_id'     => $p->unique_id,
            'name'          => $p->display_name ?: $p->englishFullName() ?: ('#' . $p->unique_id),
            'short_name'    => trim(($p->en_first_name ?: '') . ' ' . ($p->en_last_name ?: '')) ?: ($p->display_name ?: '#' . $p->unique_id),
            'initials'      => $p->initials(),
            'photo_url'     => $p->photoUrl(),
            'gender'        => $p->gender,
            'relation'      => $relationName,
            'label'         => $label,
            'view_url'      => url('/admin/parivar/' . $p->id . '/view'),
        ];
    }
}
