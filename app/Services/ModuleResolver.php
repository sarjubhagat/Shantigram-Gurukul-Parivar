<?php

namespace App\Services;

use App\Models\AccessCenter\Module;
use Illuminate\Support\Facades\Config;

class ModuleResolver
{
    public static function resolve(): bool
    {
        // Segment layout: /admin/{module}/...
        $moduleName = request()->segment(2);

        if (!$moduleName || $moduleName === 'dashboard') {
            return false;
        }

        $module = Module::where('name', $moduleName)
            ->where('is_active', true)
            ->first();

        if (!$module) {
            return false;
        }

        Config::set('module.id',         $module->id);
        Config::set('module.name',       $module->name);
        Config::set('module.title',      $module->title);
        Config::set('module.path',       $module->controller_path);
        Config::set('module.controller', $module->controller_class);
        Config::set('module.model',      $module->model_class);
        Config::set('module.table',      $module->table_name);
        Config::set('module.icon',       $module->icon);

        return true;
    }

    /**
     * Build the fully-qualified controller class.
     *
     * Base is always: App\Http\Controllers\Admin
     *
     * controller_path = optional subfolder inside Admin
     * controller_class = the controller file
     *
     * Examples:
     *   path=null,            class="UserController"
     *   → App\Http\Controllers\Admin\UserController
     *
     *   path="AccessControl", class="RoleController"
     *   → App\Http\Controllers\Admin\AccessControl\RoleController
     */
    public static function buildControllerClass(): string
    {
        $path  = trim(static::get('path', ''), '\\');
        $class = static::get('controller', '');
        $base  = 'App\\Http\\Controllers\\Admin';

        return $path
            ? $base . '\\' . $path . '\\' . $class
            : $base . '\\' . $class;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Config::get('module.' . $key, $default);
    }

    public static function permission(string $action): string
    {
        return static::get('name', '') . '-' . $action;
    }
}
