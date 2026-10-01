<?php

namespace App\Http\Controllers\Admin\Parivar;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Parivar\BusinessInfo;
use App\Models\Parivar\GurukulBranch;
use App\Models\Parivar\GurukulInfo;
use App\Models\Parivar\GurukulSaint;
use App\Models\Parivar\PersonalInfo;
use App\Services\R2StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ParivarController extends AdminController
{
    public function index()
    {
        return view('admin.parivar.index');
    }

    public function get_data(): JsonResponse
    {
        $request = request();
        $draw    = (int) $request->input('draw', 0);
        $start   = max(0, (int) $request->input('start', 0));
        $length  = (int) $request->input('length', 10);
        if ($length < 1) {
            $length = 10;
        }
        if ($length > 100) {
            $length = 100;
        }

        $searchBy  = (string) $request->input('search_by', 'name');
        $searchRaw = trim((string) (
            $request->input('search_value')
            ?: data_get($request->input('search'), 'value', '')
        ));

        $allowedSearch = ['name', 'phone', 'address', 'aadhaar', 'unique_id', 'all'];
        if (! in_array($searchBy, $allowedSearch, true)) {
            $searchBy = 'name';
        }

        $base = PersonalInfo::query();
        $recordsTotal = (clone $base)->count();

        $filtered = clone $base;
        if ($searchRaw !== '') {
            $this->applyMemberSearch($filtered, $searchBy, $searchRaw);
        }
        $recordsFiltered = (clone $filtered)->count();

        $orderColMap = [
            1 => 'unique_id',
            2 => 'en_first_name',
            3 => 'phone_1',
            4 => 'current_address',
            5 => 'aadhaar_number',
            6 => 'created_at',
        ];
        $orderInput = $request->input('order.0', []);
        $orderIdx   = (int) ($orderInput['column'] ?? 1);
        $orderDir   = (($orderInput['dir'] ?? 'desc') === 'asc') ? 'asc' : 'desc';
        $orderCol   = $orderColMap[$orderIdx] ?? 'unique_id';

        $rows = $filtered
            ->with(['createdBy', 'updatedBy'])
            ->withCount(['businesses', 'gurukulRecords'])
            ->orderBy($orderCol, $orderDir)
            ->orderByDesc('id')
            ->skip($start)
            ->take($length)
            ->get()
            ->map(fn (PersonalInfo $p) => [
                'id'                 => $p->id,
                'unique_id'          => $p->unique_id,
                'full_name'          => $p->display_name ?: $p->englishFullName(),
                'gu_full_name'       => $p->gujaratiFullName(),
                'initials'           => $p->initials(),
                'aadhaar'            => $p->aadhaarFormatted(),
                'gender'             => $p->gender,
                'gender_label'       => config('parivar.genders.' . $p->gender, $p->gender),
                'phone'              => $p->primaryPhone(),
                'native_place'       => $p->native_place,
                'current_address'    => $p->current_address,
                'is_gurukul_student' => (bool) $p->is_gurukul_student,
                'photo_url'          => $p->photoUrl(),
                'business_count'     => (int) $p->businesses_count,
                'gurukul_count'      => (int) $p->gurukul_records_count,
                'created'            => $p->created_at->format('d M Y'),
                'created_by_name'    => $p->createdBy?->name,
                'updated_by_name'    => $p->updatedBy?->name,
                'updated_at_fmt'     => $p->updated_at?->format('d M Y'),
            ])
            ->values();

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $rows,
        ]);
    }

    private function applyMemberSearch($query, string $searchBy, string $term): void
    {
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
        $digits = preg_replace('/\D+/', '', $term) ?: '';

        $query->where(function ($q) use ($searchBy, $like, $digits, $term) {
            if ($searchBy === 'name' || $searchBy === 'all') {
                $q->orWhere(function ($nameQ) use ($like) {
                    $nameQ->where('en_first_name', 'ilike', $like)
                        ->orWhere('en_last_name', 'ilike', $like)
                        ->orWhere('en_middle_name', 'ilike', $like)
                        ->orWhere('display_name', 'ilike', $like)
                        ->orWhere('gu_first_name', 'ilike', $like)
                        ->orWhere('gu_last_name', 'ilike', $like)
                        ->orWhere('gu_middle_name', 'ilike', $like)
                        ->orWhereRaw(
                            "concat_ws(' ', en_first_name, en_middle_name, en_last_name) ilike ?",
                            [$like]
                        )
                        ->orWhereRaw(
                            "concat_ws(' ', en_first_name, en_last_name) ilike ?",
                            [$like]
                        );
                });
            }

            if ($searchBy === 'phone' || $searchBy === 'all') {
                $q->orWhere(function ($phoneQ) use ($like, $digits) {
                    $phoneQ->where('phone_1', 'ilike', $like)
                        ->orWhere('phone_2', 'ilike', $like);
                    if ($digits !== '') {
                        $phoneQ->orWhereRaw(
                            "regexp_replace(coalesce(phone_1, ''), '[^0-9]', '', 'g') like ?",
                            ['%' . $digits . '%']
                        )->orWhereRaw(
                            "regexp_replace(coalesce(phone_2, ''), '[^0-9]', '', 'g') like ?",
                            ['%' . $digits . '%']
                        );
                    }
                });
            }

            if ($searchBy === 'address' || $searchBy === 'all') {
                $q->orWhere(function ($addrQ) use ($like) {
                    $addrQ->where('current_address', 'ilike', $like)
                        ->orWhere('native_place', 'ilike', $like);
                });
            }

            if ($searchBy === 'aadhaar' || $searchBy === 'all') {
                $q->orWhere(function ($aadhaarQ) use ($like, $digits) {
                    $aadhaarQ->where('aadhaar_number', 'ilike', $like);
                    if ($digits !== '') {
                        $aadhaarQ->orWhere('aadhaar_number', 'like', '%' . $digits . '%');
                    }
                });
            }

            if ($searchBy === 'unique_id' || $searchBy === 'all') {
                if (ctype_digit($term) || $digits !== '') {
                    $q->orWhere('unique_id', (int) ($digits !== '' ? $digits : $term));
                }
            }
        });
    }

    public function AddEditAction($id = null)
    {
        $member   = null;
        $tab      = request('tab', 'personal');
        $allowed  = ['personal', 'business', 'gurukul'];

        if (! in_array($tab, $allowed, true)) {
            $tab = 'personal';
        }

        if ($id) {
            $member = PersonalInfo::with([
                'businesses',
                'gurukulRecords.branch',
                'gurukulRecords.saints',
            ])->findOrFail($id);
        } else {
            $tab = 'personal';
        }

        $branches = GurukulBranch::orderBy('branch_name')->get();
        $saints   = GurukulSaint::orderBy('name')->get();
        $years    = range((int) date('Y') + 1, 1950);

        return view('admin.parivar.form', compact('member', 'tab', 'branches', 'saints', 'years'));
    }

    public function handlePost(int $id = null): RedirectResponse
    {
        $validator = $this->validatePersonal($id);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $member = $this->savePersonal($validator->validated(), $id);
        $msg    = $id
            ? 'Personal information updated.'
            : 'Member #' . $member->unique_id . ' created. You can now add business and gurukul details.';

        return redirect()
            ->to(url('/admin/parivar/' . $member->id . '/edit') . '?tab=personal')
            ->with('success', $msg);
    }

    public function create(Request $request): JsonResponse
    {
        $section = $request->input('section', 'personal');

        if (in_array($section, ['branch', 'saint'], true)) {
            return $this->saveMaster($request, $section);
        }

        if ($section !== 'personal') {
            return response()->json([
                'success' => false,
                'message' => 'Create the member first, then add other details from the member page.',
            ], 422);
        }

        $validator = $this->validatePersonal();

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $member = $this->savePersonal($validator->validated());

        return response()->json([
            'success'  => true,
            'message'  => 'Member #' . $member->unique_id . ' created. Other sections are now unlocked.',
            'member'   => $member->toFormArray(),
            'redirect' => url('/admin/parivar/' . $member->id . '/edit') . '?tab=personal',
        ]);
    }

    public function generateAadhaar(Request $request): JsonResponse
    {
        if (! auth()->user()->can('parivar-create') && ! auth()->user()->can('parivar-edit')) {
            return response()->json([
                'success' => false,
                'message' => 'You don\'t have permission to generate an Aadhaar number.',
            ], 403);
        }

        $exceptId = $request->integer('except_id') ?: null;

        try {
            $aadhaar = PersonalInfo::generateAadhaarNumber($exceptId ?: null);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Could not generate Aadhaar.',
            ], 422);
        }

        return response()->json([
            'success'           => true,
            'aadhaar_number'    => $aadhaar,
            'aadhaar_formatted' => PersonalInfo::formatAadhaar($aadhaar),
        ]);
    }

    public function checkPhone(Request $request): JsonResponse
    {
        $phone = preg_replace('/\D+/', '', (string) $request->input('phone', ''));

        if (strlen($phone) < 6) {
            return response()->json(['success' => true, 'matches' => []]);
        }

        $exceptId = $request->integer('except_id') ?: null;

        $matches = PersonalInfo::query()
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->where(function ($q) use ($phone) {
                $q->where('phone_1', $phone)
                    ->orWhere('phone_2', $phone)
                    ->orWhereRaw("regexp_replace(coalesce(phone_1, ''), '[^0-9]', '', 'g') = ?", [$phone])
                    ->orWhereRaw("regexp_replace(coalesce(phone_2, ''), '[^0-9]', '', 'g') = ?", [$phone]);
            })
            ->orderBy('unique_id')
            ->limit(8)
            ->get()
            ->map(fn (PersonalInfo $p) => [
                'id'        => $p->id,
                'unique_id' => $p->unique_id,
                'full_name' => $p->display_name ?: $p->englishFullName(),
                'phone'     => $p->primaryPhone(),
                'edit_url'  => url('/admin/parivar/' . $p->id . '/edit'),
            ])
            ->values();

        return response()->json(['success' => true, 'matches' => $matches]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $member  = PersonalInfo::findOrFail($id);
        $section = $request->input('section', 'personal');

        return match ($section) {
            'personal' => $this->updatePersonal($request, $member),
            'business' => $this->saveBusiness($request, $member),
            'gurukul'  => $this->saveGurukul($request, $member),
            'branch'   => $this->saveMaster($request, 'branch'),
            'saint'    => $this->saveMaster($request, 'saint'),
            default    => response()->json(['success' => false, 'message' => 'Unknown section.'], 422),
        };
    }

    public function DeleteRecord(): JsonResponse
    {
        $ids  = request()->input('ids', []);
        $type = request()->input('record_type', 'member');

        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'Nothing selected.'], 422);
        }

        $count = match ($type) {
            'business' => BusinessInfo::whereIn('id', $ids)->delete(),
            'gurukul'  => $this->deleteGurukulRows($ids),
            'branch'   => GurukulBranch::whereIn('id', $ids)->delete(),
            'saint'    => GurukulSaint::whereIn('id', $ids)->delete(),
            default    => $this->deleteMembers($ids),
        };

        $labels = [
            'member'   => 'member(s)',
            'business' => 'business record(s)',
            'gurukul'  => 'gurukul record(s)',
            'branch'   => 'branch(es)',
            'saint'    => 'saint(s)',
        ];

        return response()->json([
            'success' => true,
            'message' => $count . ' ' . ($labels[$type] ?? 'record(s)') . ' deleted.',
        ]);
    }

    private function deleteMembers(array $ids): int
    {
        $rows = PersonalInfo::whereIn('id', $ids)->get();
        $rows->each->forceDelete();

        return $rows->count();
    }

    private function deleteGurukulRows(array $ids): int
    {
        $rows = GurukulInfo::whereIn('id', $ids)->get();
        $rows->each->forceDelete();

        return $rows->count();
    }

    public function DeleteSingleRecord(int $id): JsonResponse
    {
        PersonalInfo::findOrFail($id)->forceDelete();

        return response()->json(['success' => true, 'message' => 'Member deleted.']);
    }

    // ── Personal ──────────────────────────────────────────

    private function personalRules(?int $id = null): array
    {
        return [
            'aadhaar_number'      => [
                'required',
                'digits:12',
                Rule::unique('personal_info', 'aadhaar_number')->ignore($id)->whereNull('deleted_at'),
            ],
            'en_first_name'       => 'required|string|max:100',
            'en_last_name'        => 'required|string|max:100',
            'en_middle_name'      => 'nullable|string|max:100',
            'en_grandfather_name' => 'nullable|string|max:100',
            'display_name'        => 'nullable|string|max:200',
            'gu_first_name'       => 'nullable|string|max:100',
            'gu_last_name'        => 'nullable|string|max:100',
            'gu_middle_name'      => 'nullable|string|max:100',
            'gu_grandfather_name' => 'nullable|string|max:100',
            'marital_status'      => ['nullable', Rule::in(array_keys(config('parivar.marital_statuses', [])))],
            'date_of_birth'       => 'nullable|date|before:today',
            'gender'              => ['nullable', Rule::in(array_keys(config('parivar.genders', [])))],
            'c_code_1'            => 'nullable|string|max:10',
            'phone_1'             => 'nullable|string|max:20',
            'c_code_2'            => 'nullable|string|max:10',
            'phone_2'             => 'nullable|string|max:20',
            'native_place'        => 'nullable|string|max:150',
            'current_address'     => 'nullable|string|max:1000',
            'is_gurukul_student'  => 'nullable|boolean',
            'photo'               => 'nullable|file|image|mimes:jpeg,jpg,png,webp|max:' . (int) config('parivar.photo.max_kb', 2048),
            'remove_photo'        => 'nullable|boolean',
        ];
    }

    private function personalMessages(): array
    {
        return [
            'aadhaar_number.required' => 'Aadhaar number is required.',
            'aadhaar_number.digits'   => 'Aadhaar number must be exactly 12 digits.',
            'aadhaar_number.unique'   => 'This Aadhaar number is already registered to another member.',
            'photo.image'             => 'Passport photo must be an image.',
            'photo.mimes'             => 'Passport photo must be JPEG, PNG, or WebP.',
            'photo.max'               => 'Passport photo must be under ' . round(((int) config('parivar.photo.max_kb', 2048)) / 1024, 1) . ' MB.',
        ];
    }

    private function validatePersonal(?int $id = null)
    {
        $payload = request()->all();
        $payload['aadhaar_number'] = PersonalInfo::normalizeAadhaar($payload['aadhaar_number'] ?? null);

        return Validator::make($payload, $this->personalRules($id), $this->personalMessages());
    }

    private function savePersonal(array $data, ?int $id = null): PersonalInfo
    {
        $data['is_gurukul_student'] = ! empty($data['is_gurukul_student']);
        $data['aadhaar_number']     = PersonalInfo::normalizeAadhaar($data['aadhaar_number'] ?? null);

        unset($data['photo'], $data['remove_photo']);

        if ($id) {
            $member = PersonalInfo::findOrFail($id);
            $member->update($data);
        } else {
            $member = PersonalInfo::create($data);
        }

        $this->syncPassportPhoto($member);

        return $member->fresh();
    }

    private function syncPassportPhoto(PersonalInfo $member): void
    {
        $request = request();
        $r2      = app(R2StorageService::class);
        $folder  = trim((string) config('parivar.photo.folder', 'parivar-photos'), '/');

        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $old = $member->photo_key;
            $stored = $r2->upload($request->file('photo'), $folder, 'member_' . $member->unique_id . '_' . now()->format('YmdHis'));
            $member->update(['photo_key' => $stored['path']]);

            if ($old && $old !== $stored['path']) {
                $r2->delete($old);
            }

            return;
        }

        if ($request->boolean('remove_photo') && $member->photo_key) {
            $r2->delete($member->photo_key);
            $member->update(['photo_key' => null]);
        }
    }

    private function updatePersonal(Request $request, PersonalInfo $member): JsonResponse
    {
        $validator = $this->validatePersonal($member->id);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $member = $this->savePersonal($validator->validated(), $member->id);

        return response()->json([
            'success' => true,
            'message' => 'Personal information saved.',
            'member'  => $member->toFormArray(),
        ]);
    }

    // ── Business ──────────────────────────────────────────

    private function saveBusiness(Request $request, PersonalInfo $member): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'record_id'        => 'nullable|integer',
            'business_name'    => 'required|string|max:200',
            'business_address' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $rid  = $data['record_id'] ?? null;

        if ($rid) {
            $row = BusinessInfo::where('unique_id', $member->unique_id)->findOrFail($rid);
            $row->update([
                'business_name'    => $data['business_name'],
                'business_address' => $data['business_address'] ?? null,
            ]);
            $msg = 'Business details updated.';
        } else {
            $row = BusinessInfo::create([
                'unique_id'        => $member->unique_id,
                'business_name'    => $data['business_name'],
                'business_address' => $data['business_address'] ?? null,
            ]);
            $msg = 'Business details added.';
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'record'  => $row->fresh()->toFormArray(),
        ]);
    }

    // ── Gurukul ───────────────────────────────────────────

    private function saveGurukul(Request $request, PersonalInfo $member): JsonResponse
    {
        $maxYear = (int) date('Y') + 1;

        $validator = Validator::make($request->all(), [
            'record_id'  => 'nullable|integer',
            'branch_id'  => 'required|integer|exists:gurukul_branches,id',
            'start_year' => 'nullable|integer|min:1950|max:' . $maxYear,
            'end_year'   => [
                'nullable',
                'integer',
                'min:1950',
                'max:' . $maxYear,
                function ($attribute, $value, $fail) use ($request) {
                    $start = $request->input('start_year');
                    if ($value !== null && $value !== '' && $start !== null && $start !== '' && (int) $value < (int) $start) {
                        $fail('End year must be greater than or equal to start year.');
                    }
                },
            ],
            'total_year' => 'nullable|integer|min:0|max:80',
            'saints'     => 'nullable|array',
            'saints.*'   => 'integer|exists:gurukul_saints,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $rid  = $data['record_id'] ?? null;

        $payload = [
            'branch_id'  => $data['branch_id'],
            'start_year' => $data['start_year'] ?? null,
            'end_year'   => $data['end_year'] ?? null,
            'total_year' => $data['total_year'] ?? null,
        ];

        if (empty($payload['total_year']) && $payload['start_year'] && $payload['end_year']) {
            $payload['total_year'] = max(0, (int) $payload['end_year'] - (int) $payload['start_year']);
        }

        if ($rid) {
            $row = GurukulInfo::where('unique_id', $member->unique_id)->findOrFail($rid);
            $row->update($payload);
            $msg = 'Gurukul details updated.';
        } else {
            $payload['unique_id'] = $member->unique_id;
            $row = GurukulInfo::create($payload);
            $msg = 'Gurukul details added.';
        }

        $row->syncSaints($data['saints'] ?? []);

        return response()->json([
            'success' => true,
            'message' => $msg,
            'record'  => $row->fresh(['branch', 'saints'])->toFormArray(),
        ]);
    }

    // ── Masters (branches / saints) ───────────────────────

    private function saveMaster(Request $request, string $type): JsonResponse
    {
        if ($type === 'branch') {
            $validator = Validator::make($request->all(), [
                'record_id'   => 'nullable|integer',
                'branch_name' => 'required|string|max:150',
                'is_active'   => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $data = $validator->validated();
            $row  = ! empty($data['record_id'])
                ? tap(GurukulBranch::findOrFail($data['record_id']), fn ($b) => $b->update([
                    'branch_name' => $data['branch_name'],
                    'is_active'   => ! empty($data['is_active']),
                ]))
                : GurukulBranch::create([
                    'branch_name' => $data['branch_name'],
                    'is_active'   => array_key_exists('is_active', $data) ? ! empty($data['is_active']) : true,
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Branch saved.',
                'record'  => [
                    'id'          => $row->id,
                    'branch_name' => $row->branch_name,
                    'is_active'   => (bool) $row->is_active,
                ],
            ]);
        }

        $validator = Validator::make($request->all(), [
            'record_id' => 'nullable|integer',
            'name'      => 'required|string|max:150',
            'number'    => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $row  = ! empty($data['record_id'])
            ? tap(GurukulSaint::findOrFail($data['record_id']), fn ($s) => $s->update([
                'name'      => $data['name'],
                'number'    => $data['number'] ?? null,
                'is_active' => ! empty($data['is_active']),
            ]))
            : GurukulSaint::create([
                'name'      => $data['name'],
                'number'    => $data['number'] ?? null,
                'is_active' => array_key_exists('is_active', $data) ? ! empty($data['is_active']) : true,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Saint saved.',
            'record'  => [
                'id'         => $row->id,
                'name'       => $row->name,
                'number'     => $row->number,
                'is_active'  => (bool) $row->is_active,
                'label'      => $row->displayName(),
            ],
        ]);
    }
}
