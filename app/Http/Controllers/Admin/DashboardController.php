<?php

namespace App\Http\Controllers\Admin;

use App\Models\AccessCenter\Module;
use App\Models\AccessCenter\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DashboardController extends AdminController
{
    public function index()
    {
        $stats = [
            // 'users'       => User::count(),
            // 'roles'       => Role::count(),
            // 'permissions' => Permission::count(),
            // 'modules'     => Module::where('is_active', true)->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    // Not used on dashboard but required by AdminController contract
    public function get_data(): \Illuminate\Http\JsonResponse
    {
        return response()->json([]);
    }

    public function AddEditAction(int $id = null)
    {
        abort(404);
    }

    public function handlePost(int $id = null): \Illuminate\Http\RedirectResponse
    {
        abort(404);
    }
}
