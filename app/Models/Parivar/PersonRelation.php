<?php

namespace App\Models\Parivar;

use App\Traits\TracksAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonRelation extends Model
{
    use SoftDeletes, TracksAudit;

    protected $table = 'person_relations';

    protected $fillable = [
        'personal_info_id',
        'relation_id',
        'related_personal_info_id',
        'mirror_id',
        'created_by',
        'updated_by',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(PersonalInfo::class, 'personal_info_id');
    }

    public function relatedPerson(): BelongsTo
    {
        return $this->belongsTo(PersonalInfo::class, 'related_personal_info_id');
    }

    public function relation(): BelongsTo
    {
        return $this->belongsTo(Relation::class, 'relation_id');
    }

    public function mirror(): BelongsTo
    {
        return $this->belongsTo(self::class, 'mirror_id');
    }

    public function sentenceLabel(): string
    {
        $person = $this->person;
        $related = $this->relatedPerson;
        $relation = $this->relation?->relation ?? 'relation';

        $from = $person
            ? ($person->display_name ?: $person->englishFullName() ?: ('#' . $person->unique_id))
            : 'This person';

        $to = $related
            ? ($related->display_name ?: $related->englishFullName() ?: ('#' . $related->unique_id))
            : '______';

        return $from . "'s " . mb_strtolower($relation) . ' is ' . $to;
    }

    public function toFormArray(): array
    {
        $related = $this->relatedPerson;
        $relation = $this->relation;

        return [
            'id'                       => $this->id,
            'personal_info_id'         => $this->personal_info_id,
            'relation_id'              => $this->relation_id,
            'related_personal_info_id' => $this->related_personal_info_id,
            'relation_name'            => $relation?->relation,
            'related_name'             => $related
                ? ($related->display_name ?: $related->englishFullName())
                : null,
            'related_unique_id'        => $related?->unique_id,
            'label'                    => $this->sentenceLabel(),
        ];
    }
}
