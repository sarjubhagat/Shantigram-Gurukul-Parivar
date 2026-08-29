<?php

namespace App\Http\Controllers\Admin\AccessControl;

use App\Http\Controllers\Admin\AdminController;
use App\Models\AccessCenter\Role;
use App\Models\AccessCenter\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends AdminController
{
    public function index()
    {
        $roles = Role::orderBy('name')->get();
        return view('admin.users.index', compact('roles'));
    }

    public function get_data(): JsonResponse
    {
        $users = User::with('roles', 'createdBy', 'updatedBy')->latest()->get()->map(fn($u) => [
            'id'              => $u->id,
            'name'            => $u->name,
            'email'           => $u->email,
            'number'          => $u->number,
            'is_active'       => (bool) $u->is_active,
            'roles'           => $u->roles->pluck('name')->implode(', '),
            'role_names'      => $u->roles->pluck('name')->values()->toArray(),
            'joined'          => $u->created_at->format('d M Y'),
            'created_by_name' => $u->createdBy?->name,
            'updated_by_name' => $u->updatedBy?->name,
            'updated_at_fmt'  => $u->updated_at?->format('d M Y'),
        ]);

        return response()->json(['data' => $users]);
    }

    // Handles both create (no id in body) and update (id in body)
    public function create(Request $request): JsonResponse
    {
        $id = $request->input('id') ?: null;

        if ($id && !auth()->user()->can('users-edit')) {
            return response()->json([
                'success' => false,
                'message' => 'You don\'t have permission to edit users.',
            ], 403);
        }

        $rules = [
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email' . ($id ? ',' . $id : ''),
            'number'    => 'required|string|max:20',
            'roles'     => 'nullable|array',
            'roles.*'   => 'string|exists:roles,name',
            'is_active' => 'nullable|boolean',
        ];

        $rules['password']              = $id ? 'nullable|min:8|confirmed' : 'required|min:8|confirmed';
        $rules['password_confirmation'] = $id ? 'nullable'                 : 'required';

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if ($id) {
            $user = User::findOrFail($id);
            $updateData = [
                'name'      => $data['name'],
                'email'     => $data['email'],
                'number'    => $data['number'],
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : false,
            ];
            if (!empty($data['password'])) {
                $updateData['password'] = $data['password'];
            }
            $user->update($updateData);
            $msg = 'User updated successfully.';
        } else {
            $user = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'number'    => $data['number'],
                'password'  => $data['password'],
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            ]);
            $msg = 'User created successfully.';
        }

        $user->syncRoles($data['roles'] ?? []);

        return response()->json(['success' => true, 'message' => $msg]);
    }

    public function DeleteRecord(): JsonResponse
    {
        $ids = request()->input('ids', []);

        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No users selected.'], 422);
        }

        $count = User::whereIn('id', $ids)->delete();

        return response()->json(['success' => true, 'message' => $count . ' user(s) deleted.']);
    }

    public function DeleteSingleRecord(int $id): JsonResponse
    {
        User::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'User deleted.']);
    }

    // Not used — modal approach replaces these
    public function AddEditAction(int $id = null) { abort(404); }
    public function handlePost(int $id = null): RedirectResponse { abort(404); }
}
