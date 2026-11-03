<?php

namespace App\Models\Parivar;

use App\Models\AccessCenter\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalInfoAudit extends Model
{
    public $timestamps = false;

    protected $table = 'personal_info_audits';

    protected $fillable = [
        'personal_info_id',
        'action',
        'summary',
        'field',
        'old_value',
        'new_value',
        'meta',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta'       => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(PersonalInfo::class, 'personal_info_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function record(
        int $personalInfoId,
        string $action,
        string $summary,
        ?string $field = null,
        mixed $old = null,
        mixed $new = null,
        ?array $meta = null
    ): self {
        return self::query()->create([
            'personal_info_id' => $personalInfoId,
            'action'           => $action,
            'summary'          => mb_substr($summary, 0, 500),
            'field'            => $field,
            'old_value'        => self::stringify($old),
            'new_value'        => self::stringify($new),
            'meta'             => $meta,
            'created_by'       => auth()->id(),
            'created_at'       => now(),
        ]);
    }

    private static function stringify(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_array($value)) {
            return json_encode($value);
        }

        $str = (string) $value;

        return mb_strlen($str) > 2000 ? mb_substr($str, 0, 2000) . '…' : $str;
    }

    public function toTimelineArray(): array
    {
        return [
            'id'         => $this->id,
            'action'     => $this->action,
            'summary'    => $this->summary,
            'field'      => $this->field,
            'old_value'  => $this->old_value,
            'new_value'  => $this->new_value,
            'actor'      => $this->actor?->name,
            'created_at' => $this->created_at?->format('d M Y H:i'),
            'created_ts' => $this->created_at?->toIso8601String(),
        ];
    }
}
