<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ModuleResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

abstract class AdminController extends Controller
{
    protected string $moduleName  = '';
    protected string $moduleTitle = '';
    protected string $viewBase    = '';

    public function __construct()
    {
        $this->moduleName  = ModuleResolver::get('name', '');
        $this->moduleTitle = ModuleResolver::get('title', '');
        // Convert "leave-application" → "admin.leave_application"
        $this->viewBase = 'admin.' . str_replace('-', '_', $this->moduleName);
    }

    abstract public function index();

    abstract public function get_data(): JsonResponse;

    abstract public function AddEditAction(int $id = null);

    abstract public function handlePost(int $id = null): RedirectResponse;

    public function DeleteRecord(): JsonResponse
    {
        return response()->json(['status' => 'error', 'message' => 'Not implemented.'], 501);
    }

    public function DeleteSingleRecord(int $id): JsonResponse
    {
        return response()->json(['status' => 'error', 'message' => 'Not implemented.'], 501);
    }

    public function ExportRecord()
    {
        abort(501, 'Export not implemented for this module.');
    }
}
