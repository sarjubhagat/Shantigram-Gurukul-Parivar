<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Parivar\BusinessInfo;
use App\Models\Parivar\DataUpdate;
use App\Models\Parivar\GurukulBranch;
use App\Models\Parivar\GurukulInfo;
use App\Models\Parivar\PersonalInfo;
use App\Services\HostelOtpService;
use App\Services\R2StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SelfServiceController extends Controller
{
    private const SESSION_MEMBER = 'member_self_service_id';
    private const SESSION_PHONE  = 'member_self_service_phone';

    public function index()
    {
        return view('public.member-update', [
            'genders'  => config('parivar.genders', []),
            'maritals' => config('parivar.marital_statuses', []),
            'codes'    => config('parivar.country_codes', []),
            'testMode' => (bool) config('parivar.otp.test_mode', false),
            'branches' => GurukulBranch::where('is_active', true)->orderBy('branch_name')->get(['id', 'branch_name']),
            'years'    => range((int) date('Y') + 1, 1950),
        ]);
    }

    public function sendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'min:6', 'max:20'],
        ], [
            'phone.required' => 'Mobile number is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $phone = $this->digitsOnly($request->input('phone'));
        if (strlen($phone) < 6) {
            return response()->json([
                'success' => false,
                'message' => 'Enter a valid mobile number.',
            ], 422);
        }

        $members = $this->findByPhone($phone);
        if ($members->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No member found with this mobile number.',
            ], 404);
        }

        $testMode = (bool) config('parivar.otp.test_mode', false);
        $otp = $testMode
            ? (string) config('parivar.otp.test_otp', '12345')
            : (string) random_int(10000, 99999);

        Cache::put($this->otpCacheKey($phone), [
            'otp'        => $otp,
            'phone'      => $phone,
            'member_ids' => $members->pluck('id')->all(),
        ], now()->addMinutes((int) config('parivar.otp.ttl_minutes', 10)));

        if (! $testMode) {
            $result = app(HostelOtpService::class)->sendOtp($phone, $otp);
            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to send OTP. Please try again.',
                ], 500);
            }
        }

        return response()->json([
            'success' => true,
            'message' => $testMode
                ? 'OTP ready. Use ' . $otp . ' for testing.'
                : 'OTP sent to your mobile number.',
            'data' => [
                'phone'         => $phone,
                'members_count' => $members->count(),
                'test_mode'     => $testMode,
            ],
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'min:6', 'max:20'],
            'otp'   => ['required', 'digits_between:4,6'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $phone  = $this->digitsOnly($request->input('phone'));
        $otp    = (string) $request->input('otp');
        $cached = Cache::get($this->otpCacheKey($phone));

        $testMode  = (bool) config('parivar.otp.test_mode', false);
        $testOtp   = (string) config('parivar.otp.test_otp', '12345');
        $cachedOtp = is_array($cached) ? (string) ($cached['otp'] ?? '') : '';

        $valid = ($testMode && $otp === $testOtp)
            || ($cachedOtp !== '' && hash_equals($cachedOtp, $otp));

        if (! $valid) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP. Please try again.',
            ], 422);
        }

        $members = $this->findByPhone($phone);
        if ($members->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No member found with this mobile number.',
            ], 404);
        }

        $payload = $members->map(fn (PersonalInfo $m) => $this->memberPayload($m))->values();

        if ($members->count() === 1) {
            session([
                self::SESSION_MEMBER => $members->first()->id,
                self::SESSION_PHONE  => $phone,
            ]);
        } else {
            session()->forget(self::SESSION_MEMBER);
            session([self::SESSION_PHONE => $phone]);
        }

        Cache::forget($this->otpCacheKey($phone));

        return response()->json([
            'success' => true,
            'message' => 'OTP verified.',
            'data' => [
                'members' => $payload,
            ],
        ]);
    }

    public function selectMember(Request $request): JsonResponse
    {
        $phone = session(self::SESSION_PHONE);
        if (! $phone) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify OTP again.',
            ], 401);
        }

        $id = (int) $request->input('member_id');
        $member = $this->findByPhone($phone)->firstWhere('id', $id);

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => 'Selected member was not found for this number.',
            ], 404);
        }

        session([self::SESSION_MEMBER => $member->id]);

        return response()->json([
            'success' => true,
            'message' => 'Member selected.',
            'data' => [
                'member' => $this->memberPayload($member),
            ],
        ]);
    }

    public function profile(): JsonResponse
    {
        $member = $this->sessionMember();
        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => 'Session expired. Please verify OTP again.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'member' => $this->memberPayload($member),
            ],
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        $member = $this->sessionMember();
        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => 'Session expired. Please verify OTP again.',
            ], 401);
        }

        $fields = config('parivar.self_service.editable_fields', []);
        $maxYear = (int) date('Y') + 1;
        $maxKb = (int) config('parivar.photo.max_kb', 2048);
        $rules = [
            'member_note' => 'nullable|string|max:2000',
            'photo' => 'nullable|file|image|mimes:jpeg,jpg,png,webp|max:' . $maxKb,
            'businesses' => 'nullable|array',
            'businesses.*.id' => 'nullable|integer',
            'businesses.*.business_name' => 'nullable|string|max:200',
            'businesses.*.business_address' => 'nullable|string|max:1000',
            'gurukul' => 'nullable|array',
            'gurukul.*.id' => 'nullable|integer',
            'gurukul.*.branch_id' => 'nullable|integer|exists:gurukul_branches,id',
            'gurukul.*.start_year' => 'nullable|integer|min:1950|max:' . $maxYear,
            'gurukul.*.end_year' => 'nullable|integer|min:1950|max:' . $maxYear,
            'gurukul.*.saints_text' => 'nullable|string|max:500',
        ];
        foreach ($fields as $field) {
            $rules[$field] = match ($field) {
                'display_name' => 'nullable|string|max:200',
                'aadhaar_number' => 'nullable|digits:12',
                'date_of_birth' => 'nullable|date|before:today',
                'gender' => ['nullable', Rule::in(array_keys(config('parivar.genders', [])))],
                'marital_status' => ['nullable', Rule::in(array_keys(config('parivar.marital_statuses', [])))],
                'c_code_1', 'c_code_2' => 'nullable|string|max:10',
                'phone_1', 'phone_2' => 'nullable|string|max:20',
                'native_place' => 'nullable|string|max:150',
                'current_address' => 'nullable|string|max:1000',
                'is_gurukul_student' => 'nullable|boolean',
                default => 'nullable|string|max:100',
            };
        }

        $payload = $request->all();
        if (array_key_exists('aadhaar_number', $payload)) {
            $payload['aadhaar_number'] = PersonalInfo::normalizeAadhaar($payload['aadhaar_number'] ?? null);
        }
        $payload['is_gurukul_student'] = ! empty($payload['is_gurukul_student']);

        $validator = Validator::make($payload, $rules, [
            'aadhaar_number.digits' => 'Aadhaar number must be exactly 12 digits.',
            'photo.image' => 'Passport photo must be an image.',
            'photo.mimes' => 'Passport photo must be JPEG, PNG, or WebP.',
            'photo.max' => 'Passport photo must be under ' . round($maxKb / 1024, 1) . ' MB.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $hasPhoto = (bool) $member->photo_key || $request->hasFile('photo');
        if (! $hasPhoto) {
            return response()->json([
                'success' => false,
                'message' => 'Passport-size photo is required. Please upload a photo like the reference image.',
            ], 422);
        }

        $validated = $validator->validated();
        $memberNote = trim((string) ($validated['member_note'] ?? ''));

        $proposed = collect($validated)
            ->only($fields)
            ->map(fn ($v) => $v === null ? null : (is_string($v) ? trim($v) : $v))
            ->all();

        if (array_key_exists('aadhaar_number', $proposed)) {
            $proposed['aadhaar_number'] = PersonalInfo::normalizeAadhaar($proposed['aadhaar_number'] ?? null);
        }
        if (array_key_exists('is_gurukul_student', $proposed)) {
            $proposed['is_gurukul_student'] = ! empty($proposed['is_gurukul_student']);
        }

        $proposedBusinesses = $this->normalizeBusinessesInput($validated['businesses'] ?? []);
        $proposedGurukul = $this->normalizeGurukulInput($validated['gurukul'] ?? []);

        $current = collect($this->editableSnapshot($member))->only($fields)->all();
        $current['_businesses'] = $this->businessSnapshot($member);
        $current['_gurukul'] = $this->gurukulSnapshot($member);
        $current['_photo_key'] = $member->photo_key;

        $proposed['_businesses'] = $proposedBusinesses;
        $proposed['_gurukul'] = $proposedGurukul;
        $proposed['_photo_key'] = $member->photo_key;

        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $folder = trim((string) config('parivar.photo.update_folder', 'parivar-update-photos'), '/');
            $stored = app(R2StorageService::class)->upload(
                $request->file('photo'),
                $folder,
                'update_' . $member->unique_id . '_' . now()->format('YmdHis')
            );
            $proposed['_photo_key'] = $stored['path'];
        }

        $changed = [];
        foreach ($fields as $field) {
            $old = (string) ($current[$field] ?? '');
            $new = (string) ($proposed[$field] ?? '');
            if ($old !== $new) {
                $changed[$field] = [
                    'from' => $current[$field] ?? null,
                    'to'   => $proposed[$field] ?? null,
                ];
            }
        }

        $bizChanged = json_encode($current['_businesses']) !== json_encode($proposed['_businesses']);
        $gkChanged = json_encode($current['_gurukul']) !== json_encode($proposed['_gurukul']);
        $photoChanged = (string) ($current['_photo_key'] ?? '') !== (string) ($proposed['_photo_key'] ?? '');
        if ($bizChanged) {
            $changed['_businesses'] = true;
        }
        if ($gkChanged) {
            $changed['_gurukul'] = true;
        }
        if ($photoChanged) {
            $changed['_photo_key'] = true;
        }

        if (empty($changed) && $memberNote === '') {
            return response()->json([
                'success' => false,
                'message' => 'No changes found. Update fields, photo, or write your request in the note box.',
            ], 422);
        }

        $pending = DataUpdate::where('personal_info_id', $member->id)
            ->where('status', DataUpdate::STATUS_PENDING)
            ->exists();

        if ($pending) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending update request. Please wait for admin review.',
            ], 422);
        }

        $row = DataUpdate::create([
            'unique_id'        => $member->unique_id,
            'personal_info_id' => $member->id,
            'phone'            => session(self::SESSION_PHONE),
            'current_data'     => $current,
            'proposed_data'    => $proposed,
            'member_note'      => $memberNote !== '' ? $memberNote : null,
            'status'           => DataUpdate::STATUS_PENDING,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Update request submitted. Admin will review it soon.',
            'data' => [
                'id'      => $row->id,
                'status'  => $row->status,
                'changed' => $changed,
            ],
        ]);
    }

    public function logout(): JsonResponse
    {
        session()->forget([self::SESSION_MEMBER, self::SESSION_PHONE]);

        return response()->json(['success' => true, 'message' => 'Signed out.']);
    }

    private function sessionMember(): ?PersonalInfo
    {
        $id = session(self::SESSION_MEMBER);
        if (! $id) {
            return null;
        }

        return PersonalInfo::with(['businesses', 'gurukulRecords.branch', 'gurukulRecords.saints'])
            ->find($id);
    }

    private function findByPhone(string $phone)
    {
        return PersonalInfo::query()
            ->where(function ($q) use ($phone) {
                $q->where('phone_1', $phone)
                    ->orWhere('phone_2', $phone)
                    ->orWhereRaw("regexp_replace(coalesce(phone_1, ''), '[^0-9]', '', 'g') = ?", [$phone])
                    ->orWhereRaw("regexp_replace(coalesce(phone_2, ''), '[^0-9]', '', 'g') = ?", [$phone]);
            })
            ->orderBy('unique_id')
            ->get();
    }

    private function memberPayload(PersonalInfo $member): array
    {
        $member->loadMissing(['businesses', 'gurukulRecords.branch', 'gurukulRecords.saints']);

        return [
            'id'         => $member->id,
            'unique_id'  => $member->unique_id,
            'full_name'  => $member->display_name ?: $member->englishFullName(),
            'photo_url'  => $member->photoUrl(),
            'personal'   => array_merge(
                $this->lockedNameSnapshot($member),
                $this->editableSnapshot($member)
            ),
            'aadhaar_formatted' => $member->aadhaarFormatted(),
            'businesses' => $this->businessSnapshot($member),
            'gurukul' => $this->gurukulSnapshot($member),
            'pending_update' => DataUpdate::where('personal_info_id', $member->id)
                ->where('status', DataUpdate::STATUS_PENDING)
                ->exists(),
        ];
    }

    private function editableSnapshot(PersonalInfo $member): array
    {
        $fields = config('parivar.self_service.editable_fields', []);
        $out = [];
        foreach ($fields as $field) {
            if ($field === 'date_of_birth') {
                $out[$field] = $member->date_of_birth?->format('Y-m-d');
            } elseif ($field === 'aadhaar_number') {
                $out[$field] = $member->aadhaar_number;
            } elseif ($field === 'is_gurukul_student') {
                $out[$field] = (bool) $member->is_gurukul_student;
            } else {
                $out[$field] = $member->{$field};
            }
        }

        return $out;
    }

    private function lockedNameSnapshot(PersonalInfo $member): array
    {
        $fields = config('parivar.self_service.locked_name_fields', [
            'en_last_name',
            'en_first_name',
            'en_middle_name',
        ]);
        $out = [];
        foreach ($fields as $field) {
            $out[$field] = $member->{$field};
        }

        return $out;
    }

    private function businessSnapshot(PersonalInfo $member): array
    {
        return $member->businesses->map(fn (BusinessInfo $b) => [
            'id'               => $b->id,
            'business_name'    => (string) $b->business_name,
            'business_address' => (string) ($b->business_address ?? ''),
        ])->values()->all();
    }

    private function gurukulSnapshot(PersonalInfo $member): array
    {
        return $member->gurukulRecords->map(fn (GurukulInfo $g) => [
            'id'          => $g->id,
            'branch_id'   => $g->branch_id,
            'branch'      => $g->branch?->branch_name,
            'start_year'  => $g->start_year,
            'end_year'    => $g->end_year,
            'saints_text' => $g->saints->pluck('name')->filter()->implode(', '),
            'saints'      => $g->saints->pluck('name')->values()->all(),
        ])->values()->all();
    }

    private function normalizeBusinessesInput(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['business_name'] ?? ''));
            $address = trim((string) ($row['business_address'] ?? ''));
            if ($name === '' && $address === '') {
                continue;
            }
            $out[] = [
                'id'               => ! empty($row['id']) ? (int) $row['id'] : null,
                'business_name'    => $name,
                'business_address' => $address,
            ];
        }

        return array_values($out);
    }

    private function normalizeGurukulInput(array $rows): array
    {
        $branchNames = GurukulBranch::pluck('branch_name', 'id');
        $out = [];
        foreach ($rows as $row) {
            $branchId = (int) ($row['branch_id'] ?? 0);
            if ($branchId < 1) {
                continue;
            }
            $saintsText = trim((string) ($row['saints_text'] ?? ''));
            $saintNames = $this->splitSaintNames($saintsText);

            $out[] = [
                'id'          => ! empty($row['id']) ? (int) $row['id'] : null,
                'branch_id'   => $branchId,
                'branch'      => $branchNames[$branchId] ?? null,
                'start_year'  => ($row['start_year'] ?? '') !== '' ? (int) $row['start_year'] : null,
                'end_year'    => ($row['end_year'] ?? '') !== '' ? (int) $row['end_year'] : null,
                'saints_text' => $saintsText,
                'saints'      => $saintNames,
            ];
        }

        return array_values($out);
    }

    private function splitSaintNames(string $text): array
    {
        if ($text === '') {
            return [];
        }

        return collect(preg_split('/[,;\n]+/', $text) ?: [])
            ->map(fn ($name) => trim($name))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function digitsOnly(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?: '';
    }

    private function otpCacheKey(string $phone): string
    {
        return 'parivar_member_otp:' . $phone;
    }
}
