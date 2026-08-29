<?php

namespace App\Http\Controllers\Admin\AccessControl;

use App\Http\Controllers\Admin\AdminController;
use App\Models\AccessCenter\Module;
use App\Models\AccessCenter\Permission;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PermissionController extends AdminController
{
    public function index()
    {
        $modules = Module::where('is_active', true)->orderBy('title')->get();
        return view('admin.permissions.index', compact('modules'));
    }

    public function get_data(): JsonResponse
    {
        $permissions = DB::table('permissions')
            ->leftJoin('modules', 'permissions.module_id', '=', 'modules.id')
            ->leftJoin('sections', 'modules.section_id', '=', 'sections.id')
            ->leftJoin('users as cb', 'permissions.created_by', '=', 'cb.id')
            ->leftJoin('users as ub', 'permissions.updated_by', '=', 'ub.id')
            ->select(
                'permissions.id',
                'permissions.name',
                'permissions.guard_name',
                'permissions.module_id',
                'modules.title as module_title',
                'modules.name  as module_name',
                'modules.icon  as module_icon',
                'sections.section_name',
                'permissions.created_at',
                'permissions.updated_at',
                'cb.name as created_by_name',
                'ub.name as updated_by_name',
            )
            ->orderBy('permissions.name')
            ->get()
            ->map(fn($p) => [
                'id'              => $p->id,
                'name'            => $p->name,
                'guard_name'      => $p->guard_name,
                'module_id'       => $p->module_id,
                'module_title'    => $p->module_title,
                'module_name'     => $p->module_name,
                'module_icon'     => $p->module_icon,
                'section_name'    => $p->section_name,
                'created'         => Carbon::parse($p->created_at)->format('d M Y'),
                'created_by_name' => $p->created_by_name,
                'updated_by_name' => $p->updated_by_name,
                'updated_at_fmt'  => $p->updated_at ? Carbon::parse($p->updated_at)->format('d M Y') : null,
            ]);

        return response()->json(['data' => $permissions]);
    }

    // Handles both create (no id in body) and update (id in body)
    public function create(Request $request): JsonResponse
    {
        $id = $request->input('id') ?: null;

        if ($id && !auth()->user()->can('permissions-edit')) {
            return response()->json([
                'success' => false,
                'message' => 'You don\'t have permission to edit permissions.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255|unique:permissions,name' . ($id ? ',' . $id : ''),
            'guard_name' => 'nullable|string|max:255',
            'module_id'  => 'nullable|integer|exists:modules,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data      = $validator->validated();
        $guardName = !empty($data['guard_name']) ? $data['guard_name'] : 'web';
        $moduleId  = $data['module_id'] ?? null;

        if ($id) {
            $permission             = Permission::findOrFail($id);
            $permission->name       = $data['name'];
            $permission->guard_name = $guardName;
            $permission->module_id  = $moduleId;
            $permission->save();
            $msg = 'Permission updated successfully.';
        } else {
            $permission            = Permission::create(['name' => $data['name'], 'guard_name' => $guardName]);
            $permission->module_id = $moduleId;
            $permission->save();
            $msg = 'Permission "' . $data['name'] . '" created.';
        }

        return response()->json(['success' => true, 'message' => $msg]);
    }

    public function DeleteRecord(): JsonResponse
    {
        $ids = request()->input('ids', []);

        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No permissions selected.'], 422);
        }

        $count = Permission::whereIn('id', $ids)->delete();

        return response()->json(['success' => true, 'message' => $count . ' permission(s) deleted.']);
    }

    public function DeleteSingleRecord(int $id): JsonResponse
    {
        Permission::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Permission deleted.']);
    }

    // Not used — modal approach replaces these
    public function AddEditAction(int $id = null) { abort(404); }
    public function handlePost(int $id = null): RedirectResponse { abort(404); }
}
