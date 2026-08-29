<?php

namespace App\Http\Controllers\Admin\Parivar;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Parivar\GurukulBranch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GurukulBranchController extends AdminController
{
    public function index()
    {
        return view('admin.gurukul-branches.index');
    }

    public function get_data(): JsonResponse
    {
        $rows = GurukulBranch::with(['createdBy', 'updatedBy'])
            ->withCount('gurukulRecords')
            ->orderBy('branch_name')
            ->get()
            ->map(fn (GurukulBranch $b) => [
                'id'              => $b->id,
                'branch_name'     => $b->branch_name,
                'is_active'       => (bool) $b->is_active,
                'members_count'   => (int) $b->gurukul_records_count,
                'created'         => $b->created_at->format('d M Y'),
                'created_by_name' => $b->createdBy?->name,
                'updated_by_name' => $b->updatedBy?->name,
                'updated_at_fmt'  => $b->updated_at?->format('d M Y'),
            ]);

        return response()->json(['data' => $rows]);
    }

    public function create(Request $request): JsonResponse
    {
        $id = $request->input('id') ?: null;

        if ($id && ! auth()->user()->can('gurukul-branches-edit')) {
            return response()->json([
                'success' => false,
                'message' => 'You don\'t have permission to edit branches.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'branch_name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('gurukul_branches', 'branch_name')->ignore($id)->whereNull('deleted_at'),
            ],
            'is_active'   => 'nullable|boolean',
        ], [
            'branch_name.unique' => 'A branch with this name already exists.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['is_active'] = array_key_exists('is_active', $data)
            ? (bool) $data['is_active']
            : true;

        if ($id) {
            GurukulBranch::findOrFail($id)->update($data);
            $msg = 'Branch updated successfully.';
        } else {
            GurukulBranch::create($data);
            $msg = 'Branch "' . $data['branch_name'] . '" created.';
        }

        return response()->json(['success' => true, 'message' => $msg]);
    }

    public function DeleteRecord(): JsonResponse
    {
        $ids = request()->input('ids', []);

        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No branches selected.'], 422);
        }

        $inUse = GurukulBranch::whereIn('id', $ids)->has('gurukulRecords')->pluck('branch_name');
        if ($inUse->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete branch(es) still linked to members: ' . $inUse->implode(', '),
            ], 422);
        }

        $count = GurukulBranch::whereIn('id', $ids)->delete();

        return response()->json(['success' => true, 'message' => $count . ' branch(es) deleted.']);
    }

    public function DeleteSingleRecord(int $id): JsonResponse
    {
        $branch = GurukulBranch::findOrFail($id);

        if ($branch->gurukulRecords()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a branch that is still linked to members.',
            ], 422);
        }

        $branch->delete();

        return response()->json(['success' => true, 'message' => 'Branch deleted.']);
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
