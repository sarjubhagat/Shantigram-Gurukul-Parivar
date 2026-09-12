<?php

namespace App\Models\Parivar;

use App\Services\R2StorageService;
use App\Traits\TracksAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalInfo extends Model
{
    use SoftDeletes, TracksAudit;

    protected $table = 'personal_info';

    protected $fillable = [
        'unique_id',
        'aadhaar_number',
        'en_first_name',
        'en_last_name',
        'en_middle_name',
        'en_grandfather_name',
        'display_name',
        'gu_first_name',
        'gu_last_name',
        'gu_middle_name',
        'gu_grandfather_name',
        'marital_status',
        'date_of_birth',
        'gender',
        'c_code_1',
        'phone_1',
        'c_code_2',
        'phone_2',
        'native_place',
        'current_address',
        'is_gurukul_student',
        'photo_key',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'unique_id'          => 'integer',
            'date_of_birth'      => 'date',
            'is_gurukul_student' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $person) {
            if (empty($person->unique_id)) {
                $person->unique_id = static::nextUniqueId();
            }
        });

        static::deleting(function (self $person) {
            $isForce = method_exists($person, 'isForceDeleting') && $person->isForceDeleting();

            $person->deletePhotoFromStorage();

            $person->allBusinesses()->get()->each(function (BusinessInfo $b) use ($isForce) {
                if ($isForce) {
                    $b->forceDelete();
                } else {
                    $b->delete();
                }
            });

            $person->allGurukulRecords()->get()->each(function (GurukulInfo $record) use ($isForce) {
                $record->connectedSaints()->delete();
                if ($isForce) {
                    $record->forceDelete();
                } else {
                    $record->delete();
                }
            });

            if ($isForce) {
                $person->qrToken()->delete();
                $person->dataUpdates()->delete();
            }
        });
    }

    public static function nextUniqueId(): int
    {
        $start = (int) config('parivar.unique_id_start', 8001);
        $max   = (int) static::withTrashed()->max('unique_id');

        return max($start, $max + 1);
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(BusinessInfo::class, 'unique_id', 'unique_id');
    }

    public function allBusinesses(): HasMany
    {
        return $this->hasMany(BusinessInfo::class, 'unique_id', 'unique_id')->withTrashed();
    }

    public function gurukulRecords(): HasMany
    {
        return $this->hasMany(GurukulInfo::class, 'unique_id', 'unique_id');
    }

    public function allGurukulRecords(): HasMany
    {
        return $this->hasMany(GurukulInfo::class, 'unique_id', 'unique_id')->withTrashed();
    }

    public function qrToken(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(IcardQrToken::class, 'unique_id', 'unique_id');
    }

    public function dataUpdates(): HasMany
    {
        return $this->hasMany(DataUpdate::class, 'personal_info_id', 'id');
    }

    public function englishFullName(): string
    {
        return trim(collect([
            $this->en_first_name,
            $this->en_middle_name,
            $this->en_last_name,
        ])->filter()->implode(' '));
    }

    public function gujaratiFullName(): string
    {
        return trim(collect([
            $this->gu_first_name,
            $this->gu_middle_name,
            $this->gu_last_name,
        ])->filter()->implode(' '));
    }

    public function primaryPhone(): ?string
    {
        if (! $this->phone_1) {
            return null;
        }

        $code = $this->c_code_1 ? $this->c_code_1 . ' ' : '';

        return $code . $this->phone_1;
    }

    public function initials(): string
    {
        $first = mb_substr((string) $this->en_first_name, 0, 1);
        $last  = mb_substr((string) $this->en_last_name, 0, 1);

        return strtoupper($first . $last) ?: '?';
    }

    public static function normalizeAadhaar(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        return $digits === '' ? null : $digits;
    }

    public static function formatAadhaar(?string $value): string
    {
        $digits = static::normalizeAadhaar($value) ?? '';

        if (strlen($digits) !== 12) {
            return $digits;
        }

        return substr($digits, 0, 4) . '-' . substr($digits, 4, 4) . '-' . substr($digits, 8, 4);
    }

    /**
     * Auto Aadhaar always starts with 0000, then 8 random unique digits.
     */
    public static function generateAadhaarNumber(?int $exceptId = null): string
    {
        $attempts = 0;

        do {
            $number = '0000' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
            $exists = static::withTrashed()
                ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
                ->where('aadhaar_number', $number)
                ->exists();
            $attempts++;
        } while ($exists && $attempts < 50);

        if ($exists) {
            throw new \RuntimeException('Could not generate a unique Aadhaar number. Please try again.');
        }

        return $number;
    }

    public function aadhaarFormatted(): ?string
    {
        if (! $this->aadhaar_number) {
            return null;
        }

        return static::formatAadhaar($this->aadhaar_number);
    }

    public function photoUrl(?int $ttl = null): ?string
    {
        if (! $this->photo_key) {
            return null;
        }

        return app(R2StorageService::class)->signedUrl(
            $this->photo_key,
            $ttl ?? (int) config('parivar.photo.signed_url_ttl', 120)
        );
    }

    public function deletePhotoFromStorage(): void
    {
        if (! $this->photo_key) {
            return;
        }

        app(R2StorageService::class)->delete($this->photo_key);
    }

    public function toFormArray(): array
    {
        return [
            'id'                   => $this->id,
            'unique_id'            => $this->unique_id,
            'aadhaar_number'       => $this->aadhaar_number,
            'aadhaar_formatted'    => $this->aadhaarFormatted(),
            'en_first_name'        => $this->en_first_name,
            'en_last_name'         => $this->en_last_name,
            'en_middle_name'       => $this->en_middle_name,
            'en_grandfather_name'  => $this->en_grandfather_name,
            'display_name'         => $this->display_name,
            'gu_first_name'        => $this->gu_first_name,
            'gu_last_name'         => $this->gu_last_name,
            'gu_middle_name'       => $this->gu_middle_name,
            'gu_grandfather_name'  => $this->gu_grandfather_name,
            'marital_status'       => $this->marital_status,
            'date_of_birth'        => $this->date_of_birth?->format('Y-m-d'),
            'gender'               => $this->gender,
            'c_code_1'             => $this->c_code_1,
            'phone_1'              => $this->phone_1,
            'c_code_2'             => $this->c_code_2,
            'phone_2'              => $this->phone_2,
            'native_place'         => $this->native_place,
            'current_address'      => $this->current_address,
            'is_gurukul_student'   => (bool) $this->is_gurukul_student,
            'photo_url'            => $this->photoUrl(),
            'has_photo'            => (bool) $this->photo_key,
            'full_name'            => $this->englishFullName(),
            'gu_full_name'         => $this->gujaratiFullName(),
            'initials'             => $this->initials(),
            'primary_phone'        => $this->primaryPhone(),
        ];
    }
}
