<?php

namespace App\Http\Controllers\Admin\Parivar;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Parivar\GurukulSaint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GurukulSaintController extends AdminController
{
    public function index()
    {
        return view('admin.gurukul-saints.index');
    }

    public function get_data(): JsonResponse
    {
        $rows = GurukulSaint::with(['createdBy', 'updatedBy'])
            ->withCount('gurukulRecords')
            ->orderBy('name')
            ->get()
            ->map(fn (GurukulSaint $s) => [
                'id'              => $s->id,
                'name'            => $s->name,
                'number'          => $s->number,
                'display_name'    => $s->displayName(),
                'is_active'       => (bool) $s->is_active,
                'members_count'   => (int) $s->gurukul_records_count,
                'created'         => $s->created_at->format('d M Y'),
                'created_by_name' => $s->createdBy?->name,
                'updated_by_name' => $s->updatedBy?->name,
                'updated_at_fmt'  => $s->updated_at?->format('d M Y'),
            ]);

        return response()->json(['data' => $rows]);
    }

    public function create(Request $request): JsonResponse
    {
        $id = $request->input('id') ?: null;

        if ($id && ! auth()->user()->can('gurukul-saints-edit')) {
            return response()->json([
                'success' => false,
                'message' => 'You don\'t have permission to edit saints.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:150',
            'number'    => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['number'] = $data['number'] ?? null;
        $data['is_active'] = array_key_exists('is_active', $data)
            ? (bool) $data['is_active']
            : true;

        if ($id) {
            GurukulSaint::findOrFail($id)->update($data);
            $msg = 'Saint updated successfully.';
        } else {
            GurukulSaint::create($data);
            $msg = 'Saint "' . $data['name'] . '" created.';
        }

        return response()->json(['success' => true, 'message' => $msg]);
    }

    public function DeleteRecord(): JsonResponse
    {
        $ids = request()->input('ids', []);

        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No saints selected.'], 422);
        }

        $inUse = GurukulSaint::whereIn('id', $ids)->has('gurukulRecords')->pluck('name');
        if ($inUse->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete saint(s) still linked to members: ' . $inUse->implode(', '),
            ], 422);
        }

        $count = GurukulSaint::whereIn('id', $ids)->delete();

        return response()->json(['success' => true, 'message' => $count . ' saint(s) deleted.']);
    }

    public function DeleteSingleRecord(int $id): JsonResponse
    {
        $saint = GurukulSaint::findOrFail($id);

        if ($saint->gurukulRecords()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a saint that is still linked to members.',
            ], 422);
        }

        $saint->delete();

        return response()->json(['success' => true, 'message' => 'Saint deleted.']);
    }

    public function AddEditAction(int $id = null)
    {
        abort(404);
    }

    public function handlePost(int $id = null): RedirectResponse
    {
        abort(404);
    }
}
