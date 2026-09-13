<?php

namespace App\Http\Controllers\Admin\Parivar;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Parivar\BusinessInfo;
use App\Models\Parivar\DataUpdate;
use App\Models\Parivar\GurukulInfo;
use App\Models\Parivar\GurukulSaint;
use App\Models\Parivar\PersonalInfo;
use App\Services\R2StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DataUpdateController extends AdminController
{
    public function index()
    {
        return view('admin.data_updates.index', [
            'statuses' => DataUpdate::statusLabels(),
        ]);
    }

    public function get_data(): JsonResponse
    {
        $status = request('status');

        $rows = DataUpdate::with(['member', 'reviewer'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->get()
            ->map(function (DataUpdate $row) {
                $member = $row->member;

                return [
                    'id'              => $row->id,
                    'unique_id'       => $row->unique_id,
                    'member_name'     => $member
                        ? ($member->display_name ?: $member->englishFullName())
                        : '—',
                    'phone'           => $row->phone,
                    'status'          => $row->status,
                    'status_label'    => DataUpdate::statusLabels()[$row->status] ?? $row->status,
                    'changes_count'   => $this->diffCount($row),
                    'created'         => $row->created_at?->format('d M Y H:i'),
                    'reviewed_by_name'=> $row->reviewer?->name,
                    'reviewed_at_fmt' => $row->reviewed_at?->format('d M Y H:i'),
                ];
            });

        return response()->json(['data' => $rows]);
    }

    public function show(int $id): JsonResponse
    {
        $row = DataUpdate::with(['member', 'reviewer'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id'            => $row->id,
                'unique_id'     => $row->unique_id,
                'member_name'   => $row->member
                    ? ($row->member->display_name ?: $row->member->englishFullName())
                    : '—',
                'phone'         => $row->phone,
                'status'        => $row->status,
                'status_label'  => DataUpdate::statusLabels()[$row->status] ?? $row->status,
                'admin_note'    => $row->admin_note,
                'member_note'   => $row->member_note,
                'current_data'  => $row->current_data,
                'proposed_data' => $row->proposed_data,
                'current_photo' => ! empty($row->current_data['_photo_key'] ?? null)
                    ? app(R2StorageService::class)->signedUrl($row->current_data['_photo_key'])
                    : null,
                'proposed_photo'=> ! empty($row->proposed_data['_photo_key'] ?? null)
                    ? app(R2StorageService::class)->signedUrl($row->proposed_data['_photo_key'])
                    : null,
                'diff'          => $this->buildDiff($row),
                'created'       => $row->created_at?->format('d M Y H:i'),
                'reviewed_by'   => $row->reviewer?->name,
                'reviewed_at'   => $row->reviewed_at?->format('d M Y H:i'),
            ],
        ]);
    }

    public function review(Request $request, int $id): JsonResponse
    {
        if (! auth()->user()->can('data-updates-edit')) {
            return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
        }

        $row = DataUpdate::with('member')->findOrFail($id);

        if ($row->status !== DataUpdate::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'This request was already reviewed.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'action'     => ['required', Rule::in(['solve', 'reject'])],
            'admin_note' => 'nullable|string|max:1000',
            'apply'      => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $data = $validator->validated();
        $solve = $data['action'] === 'solve';
        $apply = $solve && (! array_key_exists('apply', $data) || ! empty($data['apply']));

        DB::transaction(function () use ($row, $solve, $apply, $data) {
            if ($apply && $row->member) {
                $fields = config('parivar.self_service.editable_fields', []);
                $proposed = $row->proposed_data ?? [];
                $payload = collect($proposed)->only($fields)->all();
                if (array_key_exists('aadhaar_number', $payload)) {
                    $payload['aadhaar_number'] = PersonalInfo::normalizeAadhaar($payload['aadhaar_number'] ?? null);
                }
                if (array_key_exists('is_gurukul_student', $payload)) {
                    $payload['is_gurukul_student'] = ! empty($payload['is_gurukul_student']);
                }
                $row->member->update($payload);
                $this->applyBusinesses($row->member, $proposed['_businesses'] ?? []);
                $this->applyGurukul($row->member, $proposed['_gurukul'] ?? []);
                $this->applyPhoto($row->member, $proposed['_photo_key'] ?? null);
            }

            $row->update([
                'status'      => $solve ? DataUpdate::STATUS_SOLVED : DataUpdate::STATUS_REJECTED,
                'admin_note'  => $data['admin_note'] ?? null,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => $solve
                ? ($apply ? 'Request solved and member details updated.' : 'Request marked as solved.')
                : 'Request rejected.',
        ]);
    }

    public function DeleteRecord(): JsonResponse
    {
        $ids = request()->input('ids', []);
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'Nothing selected.'], 422);
        }

        $count = DataUpdate::whereIn('id', $ids)
            ->whereIn('status', [DataUpdate::STATUS_SOLVED, DataUpdate::STATUS_REJECTED])
            ->delete();

        return response()->json([
            'success' => true,
            'message' => $count . ' reviewed request(s) deleted. Pending requests were kept.',
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not used.'], 501);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Use the review action instead.'], 501);
    }

    public function AddEditAction(int $id = null)
    {
        return redirect()->to(url('/admin/data-updates'));
    }

    public function handlePost(int $id = null): RedirectResponse
    {
        abort(404);
    }

    private function buildDiff(DataUpdate $row): array
    {
        $current  = $row->current_data ?? [];
        $proposed = $row->proposed_data ?? [];
        $keys = collect(array_keys($current))->merge(array_keys($proposed))->unique()->values();
        $diff = [];

        foreach ($keys as $key) {
            if (in_array($key, ['_businesses', '_gurukul', '_photo_key'], true)) {
                continue;
            }
            $from = $current[$key] ?? null;
            $to   = $proposed[$key] ?? null;
            if ((string) $from !== (string) $to) {
                $diff[] = [
                    'field' => $key,
                    'label' => str_replace('_', ' ', ucfirst($key)),
                    'from'  => is_bool($from) ? ($from ? 'Yes' : 'No') : $from,
                    'to'    => is_bool($to) ? ($to ? 'Yes' : 'No') : $to,
                ];
            }
        }

        $bizFrom = $this->formatBusinessList($current['_businesses'] ?? []);
        $bizTo = $this->formatBusinessList($proposed['_businesses'] ?? []);
        if ($bizFrom !== $bizTo) {
            $diff[] = [
                'field' => '_businesses',
                'label' => 'Business',
                'from'  => $bizFrom,
                'to'    => $bizTo,
            ];
        }

        $gkFrom = $this->formatGurukulList($current['_gurukul'] ?? []);
        $gkTo = $this->formatGurukulList($proposed['_gurukul'] ?? []);
        if ($gkFrom !== $gkTo) {
            $diff[] = [
                'field' => '_gurukul',
                'label' => 'Gurukul',
                'from'  => $gkFrom,
                'to'    => $gkTo,
            ];
        }

        $photoFrom = $current['_photo_key'] ?? null;
        $photoTo = $proposed['_photo_key'] ?? null;
        if ((string) $photoFrom !== (string) $photoTo) {
            $diff[] = [
                'field' => '_photo_key',
                'label' => 'Passport photo',
                'from'  => $photoFrom ? 'Existing photo' : '—',
                'to'    => $photoTo ? 'New photo uploaded' : '—',
            ];
        }

        return $diff;
    }

    private function formatBusinessList(array $rows): string
    {
        if (empty($rows)) {
            return '—';
        }

        return collect($rows)->map(function ($row) {
            $name = trim((string) ($row['business_name'] ?? ''));
            $addr = trim((string) ($row['business_address'] ?? ''));

            return $name . ($addr !== '' ? ' (' . $addr . ')' : '');
        })->filter()->implode('; ');
    }

    private function formatGurukulList(array $rows): string
    {
        if (empty($rows)) {
            return '—';
        }

        return collect($rows)->map(function ($row) {
            $branch = $row['branch'] ?? ('Branch #' . ($row['branch_id'] ?? '?'));
            $years = collect([$row['start_year'] ?? null, $row['end_year'] ?? null])->filter()->implode('-');
            $saints = trim((string) ($row['saints_text'] ?? ''));
            if ($saints === '' && is_array($row['saints'] ?? null)) {
                $saints = implode(', ', $row['saints']);
            }

            return trim($branch . ($years ? ' ' . $years : '') . ($saints !== '' ? ' [' . $saints . ']' : ''));
        })->filter()->implode('; ');
    }

    private function applyBusinesses(PersonalInfo $member, array $rows): void
    {
        $keepIds = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['business_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $payload = [
                'business_name'    => $name,
                'business_address' => trim((string) ($row['business_address'] ?? '')) ?: null,
            ];
            $id = ! empty($row['id']) ? (int) $row['id'] : null;
            if ($id) {
                $existing = BusinessInfo::where('unique_id', $member->unique_id)->find($id);
                if ($existing) {
                    $existing->update($payload);
                    $keepIds[] = $existing->id;
                    continue;
                }
            }
            $created = BusinessInfo::create(array_merge($payload, [
                'unique_id' => $member->unique_id,
            ]));
            $keepIds[] = $created->id;
        }

        BusinessInfo::where('unique_id', $member->unique_id)
            ->when(! empty($keepIds), fn ($q) => $q->whereNotIn('id', $keepIds))
            ->when(empty($keepIds), fn ($q) => $q)
            ->get()
            ->each->delete();
    }

    private function applyGurukul(PersonalInfo $member, array $rows): void
    {
        $keepIds = [];
        foreach ($rows as $row) {
            $branchId = (int) ($row['branch_id'] ?? 0);
            if ($branchId < 1) {
                continue;
            }
            $payload = [
                'branch_id'  => $branchId,
                'start_year' => ($row['start_year'] ?? null) !== null && $row['start_year'] !== '' ? (int) $row['start_year'] : null,
                'end_year'   => ($row['end_year'] ?? null) !== null && $row['end_year'] !== '' ? (int) $row['end_year'] : null,
            ];
            $saintIds = $this->resolveSaintIdsFromText(
                (string) ($row['saints_text'] ?? ''),
                is_array($row['saints'] ?? null) ? $row['saints'] : []
            );
            $id = ! empty($row['id']) ? (int) $row['id'] : null;

            if ($id) {
                $existing = GurukulInfo::where('unique_id', $member->unique_id)->find($id);
                if ($existing) {
                    $existing->update($payload);
                    $existing->syncSaints($saintIds);
                    $keepIds[] = $existing->id;
                    continue;
                }
            }

            $created = GurukulInfo::create(array_merge($payload, [
                'unique_id' => $member->unique_id,
            ]));
            $created->syncSaints($saintIds);
            $keepIds[] = $created->id;
        }

        GurukulInfo::where('unique_id', $member->unique_id)
            ->when(! empty($keepIds), fn ($q) => $q->whereNotIn('id', $keepIds))
            ->get()
            ->each(function (GurukulInfo $record) {
                $record->connectedSaints()->delete();
                $record->delete();
            });
    }

    private function applyPhoto(PersonalInfo $member, ?string $photoKey): void
    {
        $photoKey = trim((string) $photoKey);
        if ($photoKey === '' || $photoKey === (string) $member->photo_key) {
            return;
        }

        $old = $member->photo_key;
        $member->update(['photo_key' => $photoKey]);

        if ($old && $old !== $photoKey) {
            app(R2StorageService::class)->delete($old);
        }
    }

    private function resolveSaintIdsFromText(string $text, array $fallbackNames = []): array
    {
        $names = collect(preg_split('/[,;\n]+/', $text) ?: [])
            ->map(fn ($name) => trim($name))
            ->filter()
            ->values();

        if ($names->isEmpty()) {
            $names = collect($fallbackNames)->map(fn ($name) => trim((string) $name))->filter()->values();
        }

        if ($names->isEmpty()) {
            return [];
        }

        $ids = [];
        foreach ($names as $name) {
            $saint = GurukulSaint::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
            if (! $saint) {
                $saint = GurukulSaint::create(['name' => $name]);
            }
            $ids[] = $saint->id;
        }

        return collect($ids)->unique()->values()->all();
    }

    private function diffCount(DataUpdate $row): int
    {
        return count($this->buildDiff($row));
    }
}
