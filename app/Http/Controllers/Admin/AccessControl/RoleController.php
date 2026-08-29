<?php

namespace App\Http\Controllers\Admin\AccessControl;

use App\Http\Controllers\Admin\AdminController;
use App\Models\AccessCenter\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RoleController extends AdminController
{
    public function index()
    {
        return view('admin.roles.index');
    }

    public function get_data(): JsonResponse
    {
        $roles = Role::with('permissions', 'createdBy', 'updatedBy')->orderBy('created_at','desc')->get()->map(fn($r) => [
            'id'                => $r->id,
            'name'              => $r->name,
            'unique_key'        => $r->unique_key,
            'permissions_count' => $r->permissions->count(),
            'permission_names'  => $r->permissions->pluck('name')->values()->toArray(),
            'created'           => $r->created_at->format('d M Y'),
            'created_by_name'   => $r->createdBy?->name,
            'updated_by_name'   => $r->updatedBy?->name,
            'updated_at_fmt'    => $r->updated_at?->format('d M Y'),
        ]);

        return response()->json(['data' => $roles]);
    }

    // AJAX create/update — still used by any programmatic consumers
    public function create(Request $request): JsonResponse
    {
        $id = $request->input('id') ?: null;

        if ($id && !auth()->user()->can('roles-edit')) {
            return response()->json([
                'success' => false,
                'message' => 'You don\'t have permission to edit roles.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255|unique:roles,name' . ($id ? ',' . $id : ''),
            'unique_key'    => 'nullable|string|max:100|regex:/^[a-z0-9\-]+$/|unique:roles,unique_key' . ($id ? ',' . $id : ''),
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if ($id) {
            $role = Role::findOrFail($id);
            $uk   = $data['unique_key'] ?? Role::generateUniqueKey($data['name'], $id);
            $role->update(['name' => $data['name'], 'unique_key' => $uk]);
            $msg = 'Role updated successfully.';
        } else {
            $uk   = $data['unique_key'] ?? Role::generateUniqueKey($data['name']);
            $role = Role::create(['name' => $data['name'], 'unique_key' => $uk]);
            $msg = 'Role "' . $role->name . '" created.';
        }

        $role->syncPermissions($data['permissions'] ?? []);

        return response()->json(['success' => true, 'message' => $msg]);
    }
    
    public function DeleteRecord(): JsonResponse
    {
        $ids = request()->input('ids', []);

        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No roles selected.'], 422);
        }

        $count = Role::whereIn('id', $ids)->delete();

        return response()->json(['success' => true, 'message' => $count . ' role(s) deleted.']);
    }

    public function DeleteSingleRecord(int $id): JsonResponse
    {
        Role::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Role deleted.']);
    }

    // ── Full-page create / edit ───────────────────────────────

    public function AddEditAction($id = null)
    {
        $prefix = DB::getTablePrefix();

        $permissions = DB::table('permissions')
            ->leftJoin('modules', 'permissions.module_id', '=', 'modules.id')
            ->leftJoin('sections', 'modules.section_id', '=', 'sections.id')
            ->select(
                'permissions.id',
                'permissions.name',
                'modules.title as module_title',
                'modules.name  as module_slug',
                'modules.icon  as module_icon',
                'sections.section_name',
            )
            ->orderByRaw("COALESCE({$prefix}sections.\"order\", 9999)")
            ->orderBy('sections.section_name')
            ->orderBy('modules.sort_order')
            ->orderBy('modules.title')
            ->orderBy('permissions.name')
            ->get()
            ->groupBy(fn($p) => $p->section_name ?? '')
            ->map(fn($sectionPerms) => $sectionPerms->groupBy(fn($p) => $p->module_title ?? 'Other'));

        if ($id) {
            $role            = Role::findOrFail($id);
            $rolePermissions = $role->permissions->pluck('name')->toArray();
            return view('admin.roles.form', compact('permissions', 'role', 'rolePermissions'));
        }

        return view('admin.roles.form', compact('permissions'));
    }

    public function handlePost(int $id = null): RedirectResponse
    {
        $validator = Validator::make(request()->all(), [
            'name'          => 'required|string|max:255|unique:roles,name' . ($id ? ',' . $id : ''),
            'unique_key'    => 'nullable|string|max:100|regex:/^[a-z0-9\-]+$/|unique:roles,unique_key' . ($id ? ',' . $id : ''),
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);


;        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        if ($id) {
            $role = Role::findOrFail($id);
            $uk   = $data['unique_key'] ?? Role::generateUniqueKey($data['name'], $id);
            $role->update(['name' => $data['name'], 'unique_key' => $uk]);
            $msg = 'Role "' . $role->name . '" updated successfully.';
        } else {
            $uk   = $data['unique_key'] ?? Role::generateUniqueKey($data['name']);
            $role = Role::create(['name' => $data['name'], 'unique_key' => $uk]);
            $msg = 'Role "' . $role->name . '" created successfully.';
        }

        $role->syncPermissions($data['permissions'] ?? []);

        return redirect(url('/admin/roles'))->with('success', $msg);
    }
}
