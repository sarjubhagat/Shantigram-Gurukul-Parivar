<?php

namespace App\Http\Controllers\Admin\Parivar;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Parivar\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RelationController extends AdminController
{
    public function index()
    {
        return view('admin.relations.index');
    }

    public function get_data(): JsonResponse
    {
        $rows = Relation::with(['createdBy', 'updatedBy'])
            ->orderBy('relation')
            ->get()
            ->map(fn (Relation $r) => [
                'id'              => $r->id,
                'relation'        => $r->relation,
                'is_active'       => (bool) $r->is_active,
                'created'         => $r->created_at->format('d M Y'),
                'created_by_name' => $r->createdBy?->name,
                'updated_by_name' => $r->updatedBy?->name,
                'updated_at_fmt'  => $r->updated_at?->format('d M Y'),
            ]);

        return response()->json(['data' => $rows]);
    }

    public function create(Request $request): JsonResponse
    {
        $id = $request->input('id') ?: null;

        if ($id && ! auth()->user()->can('relations-edit')) {
            return response()->json([
                'success' => false,
                'message' => 'You don\'t have permission to edit relations.',
            ], 403);
        }

        if (! $id && ! auth()->user()->can('relations-create')) {
            return response()->json([
                'success' => false,
                'message' => 'You don\'t have permission to create relations.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'relation' => [
                'required',
                'string',
                'max:100',
                Rule::unique('relations', 'relation')->ignore($id)->whereNull('deleted_at'),
            ],
            'is_active' => 'nullable|boolean',
        ], [
            'relation.required' => 'Relation name is required.',
            'relation.unique'   => 'This relation already exists.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['relation'] = trim($data['relation']);
        $data['is_active'] = array_key_exists('is_active', $data)
            ? (bool) $data['is_active']
            : true;

        if ($id) {
            Relation::findOrFail($id)->update($data);
            $msg = 'Relation updated successfully.';
        } else {
            Relation::create($data);
            $msg = 'Relation "' . $data['relation'] . '" created.';
        }

        return response()->json(['success' => true, 'message' => $msg]);
    }

    public function DeleteRecord(): JsonResponse
    {
        $ids = request()->input('ids', []);

        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No relations selected.'], 422);
        }

        $count = Relation::whereIn('id', $ids)->delete();

        return response()->json(['success' => true, 'message' => $count . ' relation(s) deleted.']);
    }

    public function DeleteSingleRecord(int $id): JsonResponse
    {
        Relation::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Relation deleted.']);
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
