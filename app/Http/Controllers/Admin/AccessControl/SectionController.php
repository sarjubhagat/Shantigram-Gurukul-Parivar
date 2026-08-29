<?php

namespace App\Http\Controllers\Admin\AccessControl;

use App\Http\Controllers\Admin\AdminController;
use App\Models\AccessCenter\Module;
use App\Models\AccessCenter\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SectionController extends AdminController
{
    public function index()
    {
        return view('admin.sections.index');
    }

    public function get_data(): JsonResponse
    {
        $sections = Section::withCount('modules')->with('createdBy', 'updatedBy')->orderBy('order')->orderBy('section_name')->get()->map(fn($s) => [
            'id'              => $s->id,
            'section_name'    => $s->section_name,
            'icon'            => $s->icon ?: 'bi-collection',
            'order'           => (int) $s->order,
            'modules_count'   => $s->modules_count,
            'created'         => $s->created_at->format('d M Y'),
            'created_by_name' => $s->createdBy?->name,
            'updated_by_name' => $s->updatedBy?->name,
            'updated_at_fmt'  => $s->updated_at?->format('d M Y'),
        ]);

        return response()->json(['data' => $sections]);
    }

    // Handles both create (no id) and update (id in body)
    public function create(Request $request): JsonResponse
    {
        $id = $request->input('id') ?: null;

        if ($id && !auth()->user()->can('sections-edit')) {
            return response()->json([
                'success' => false,
                'message' => 'You don\'t have permission to edit sections.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'section_name' => 'required|string|max:255|unique:sections,section_name' . ($id ? ',' . $id : ''),
            'icon'         => 'nullable|string|max:100',
            'order'        => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['order'] = $data['order'] ?? 0;
        $data['icon']  = ! empty($data['icon']) ? trim($data['icon']) : 'bi-collection';

        if ($id) {
            Section::findOrFail($id)->update($data);
            $msg = 'Section updated successfully.';
        } else {
            Section::create($data);
            $msg = 'Section "' . $data['section_name'] . '" created.';
        }

        return response()->json(['success' => true, 'message' => $msg]);
    }

    public function DeleteRecord(): JsonResponse
    {
        $ids = request()->input('ids', []);

        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No sections selected.'], 422);
        }

        // Detach modules before deleting (set section_id to null)
        Module::whereIn('section_id', $ids)->update(['section_id' => null]);

        $count = Section::whereIn('id', $ids)->delete();

        return response()->json(['success' => true, 'message' => $count . ' section(s) deleted.']);
    }

    public function DeleteSingleRecord(int $id): JsonResponse
    {
        Module::where('section_id', $id)->update(['section_id' => null]);
        Section::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Section deleted.']);
    }

    // Not used — modal approach replaces these
    public function AddEditAction(int $id = null) { abort(404); }
    public function handlePost(int $id = null): RedirectResponse { abort(404); }
}
