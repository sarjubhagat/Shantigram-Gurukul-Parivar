<?php

namespace App\Models\Parivar;

use App\Traits\TracksAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonRelationRejection extends Model
{
    use TracksAudit;

    protected $table = 'person_relation_rejections';

    protected $fillable = [
        'person_a_id',
        'person_b_id',
        'note',
        'created_by',
        'updated_by',
    ];

    public function personA(): BelongsTo
    {
        return $this->belongsTo(PersonalInfo::class, 'person_a_id');
    }

    public function personB(): BelongsTo
    {
        return $this->belongsTo(PersonalInfo::class, 'person_b_id');
    }

    /**
     * @return array{0:int,1:int}|null
     */
    public static function canonicalize(int $id1, int $id2): ?array
    {
        if ($id1 <= 0 || $id2 <= 0 || $id1 === $id2) {
            return null;
        }

        return $id1 < $id2 ? [$id1, $id2] : [$id2, $id1];
    }

    public static function rejectPair(int $id1, int $id2, ?string $note = null): ?self
    {
        $pair = self::canonicalize($id1, $id2);
        if (! $pair) {
            return null;
        }

        return self::query()->firstOrCreate(
            [
                'person_a_id' => $pair[0],
                'person_b_id' => $pair[1],
            ],
            [
                'note' => $note,
            ]
        );
    }

    public static function isRejected(int $id1, int $id2): bool
    {
        $pair = self::canonicalize($id1, $id2);
        if (! $pair) {
            return false;
        }

        return self::query()
            ->where('person_a_id', $pair[0])
            ->where('person_b_id', $pair[1])
            ->exists();
    }

    /**
     * Other person IDs rejected for this member.
     *
     * @return list<int>
     */
    public static function rejectedPartnerIds(int $personId): array
    {
        if ($personId <= 0) {
            return [];
        }

        return self::query()
            ->where(function ($q) use ($personId) {
                $q->where('person_a_id', $personId)
                    ->orWhere('person_b_id', $personId);
            })
            ->get(['person_a_id', 'person_b_id'])
            ->flatMap(function (self $row) use ($personId) {
                return [(int) $row->person_a_id, (int) $row->person_b_id];
            })
            ->unique()
            ->reject(fn ($id) => (int) $id === $personId)
            ->values()
            ->all();
    }
}
