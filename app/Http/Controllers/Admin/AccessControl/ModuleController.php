<?php

namespace App\Http\Controllers\Admin\AccessControl;

use App\Http\Controllers\Admin\AdminController;
use App\Models\AccessCenter\Module;
use App\Models\AccessCenter\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ModuleController extends AdminController
{
    public function index()
    {
        $sections = Section::orderBy('order')->orderBy('section_name')->get();
        return view('admin.modules.index', compact('sections'));
    }

    public function get_data(): JsonResponse
    {
        $modules = Module::with('section', 'createdBy', 'updatedBy')->orderBy('sort_order')->orderBy('title')->get()->map(fn($m) => [
            'id'               => $m->id,
            'section_id'       => $m->section_id,
            'section_name'     => $m->section?->section_name,
            'name'             => $m->name,
            'title'            => $m->title,
            'icon'             => $m->icon ?: 'bi-box-seam',
            'controller_path'  => $m->controller_path,
            'controller_class' => $m->controller_class,
            'model_class'      => $m->model_class,
            'table_name'       => $m->table_name,
            'sort_order'       => (int) $m->sort_order,
            'is_admin'         => (bool) $m->is_admin,
            'is_active'        => (bool) $m->is_active,
            'created'          => $m->created_at->format('d M Y'),
            'created_by_name'  => $m->createdBy?->name,
            'updated_by_name'  => $m->updatedBy?->name,
            'updated_at_fmt'   => $m->updated_at?->format('d M Y'),
        ]);

        return response()->json(['data' => $modules]);
    }

    // Handles both create (no id) and update (id in body)
    public function create(Request $request): JsonResponse
    {
        $id = $request->input('id') ?: null;

        if ($id && !auth()->user()->can('modules-edit')) {
            return response()->json([
                'success' => false,
                'message' => 'You don\'t have permission to edit modules.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title'            => 'required|string|max:255',
            'name'             => 'required|string|max:100|regex:/^[a-z0-9-]+$/|unique:modules,name' . ($id ? ',' . $id : ''),
            'controller_class' => 'required|string|max:255',
            'controller_path'  => 'nullable|string|max:255',
            'model_class'      => 'nullable|string|max:255',
            'table_name'       => 'nullable|string|max:255',
            'icon'             => 'nullable|string|max:100',
            'sort_order'       => 'nullable|integer|min:0',
            'section_id'       => 'nullable|integer|exists:sections,id',
            'is_admin'         => 'nullable|boolean',
            'is_active'        => 'nullable|boolean',
        ], [
            'name.regex' => 'Slug may only contain lowercase letters, numbers, and hyphens.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['icon']       = !empty($data['icon']) ? $data['icon'] : 'bi-box-seam';
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['section_id'] = !empty($data['section_id']) ? $data['section_id'] : null;
        $data['is_admin']   = $request->boolean('is_admin');
        $data['is_active']  = $request->boolean('is_active');

        if ($id) {
            Module::findOrFail($id)->update($data);
            $msg = 'Module updated successfully.';
        } else {
            Module::createWithPermissions($data);
            $msg = 'Module "' . $data['title'] . '" created with default permissions.';
        }

        return response()->json(['success' => true, 'message' => $msg]);
    }

    public function DeleteRecord(): JsonResponse
    {
        $ids = request()->input('ids', []);

        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No modules selected.'], 422);
        }

        $count = Module::whereIn('id', $ids)->delete();

        return response()->json(['success' => true, 'message' => $count . ' module(s) deleted.']);
    }

    public function DeleteSingleRecord(int $id): JsonResponse
    {
        Module::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Module deleted.']);
    }

    // Not used — modal approach replaces these
    public function AddEditAction(int $id = null) { abort(404); }
    public function handlePost(int $id = null): RedirectResponse { abort(404); }
}
