<?php

namespace App\Models\Parivar;

use App\Models\AccessCenter\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataUpdate extends Model
{
    protected $table = 'data_updates';

    public const STATUS_PENDING  = 'pending';
    public const STATUS_SOLVED   = 'solved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'unique_id',
        'personal_info_id',
        'phone',
        'current_data',
        'proposed_data',
        'member_note',
        'status',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'unique_id'      => 'integer',
            'current_data'   => 'array',
            'proposed_data'  => 'array',
            'reviewed_at'    => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(PersonalInfo::class, 'personal_info_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING  => 'Pending',
            self::STATUS_SOLVED   => 'Solved',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }
}
