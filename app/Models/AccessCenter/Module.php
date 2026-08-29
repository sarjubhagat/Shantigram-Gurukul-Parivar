<?php

namespace App\Models\AccessCenter;

use App\Traits\TracksAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Permission;

class Module extends Model
{
    use SoftDeletes, TracksAudit;

    protected $table = 'modules';

    protected $fillable = [
        'section_id',
        'name',
        'title',
        'controller_path',
        'controller_class',
        'model_class',
        'table_name',
        'icon',
        'sort_order',
        'is_admin',
        'is_active',
    ];

    protected $casts = [
        'is_admin'  => 'boolean',
        'is_active' => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    // ── Permissions ──────────────────────────────────────

    public static array $defaultActions = ['list', 'create', 'edit', 'delete'];

    public function permissions()
    {
        return Permission::where('name', 'like', $this->name . '-%')->get();
    }

    public static function createWithPermissions(array $data, array $actions = []): self
    {
        $module  = static::create($data);
        $actions = $actions ?: static::$defaultActions;

        foreach ($actions as $action) {
            Permission::firstOrCreate(
                ['name' => $module->name . '-' . $action, 'guard_name' => 'web'],
                ['module_id' => $module->id]
            );
        }

        return $module;
    }

    // ── Helpers ───────────────────────────────────────────

    public function getFullControllerClass(string $namespace = 'App\\Http\\Controllers\\Admin'): string
    {
        return rtrim($namespace, '\\') . '\\' . $this->controller_class;
    }

    public static function activeAdminModules()
    {
        return static::with('section')
            ->where('is_admin', true)
            ->where('is_active', true)
            ->get()
            ->sort(function ($a, $b) {
                $ao = $a->section?->order ?? 9999;
                $bo = $b->section?->order ?? 9999;
                if ($ao !== $bo) return $ao - $bo;

                $cmp = strcmp($a->section?->section_name ?? '', $b->section?->section_name ?? '');
                if ($cmp !== 0) return $cmp;

                if ($a->sort_order !== $b->sort_order) return $a->sort_order - $b->sort_order;

                return strcmp($a->title ?? '', $b->title ?? '');
            })
            ->values();
    }
}
