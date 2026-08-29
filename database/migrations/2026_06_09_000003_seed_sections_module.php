<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Register the Sections module so it is auto-routed and appears in the sidebar
        DB::table('modules')->insertOrIgnore([
            'name'             => 'sections',
            'title'            => 'Sections',
            'controller_path'  => 'AccessControl',
            'controller_class' => 'SectionController',
            'model_class'      => 'Section',
            'table_name'       => 'sections',
            'icon'             => 'bi-collection',
            'sort_order'       => 60,
            'is_admin'         => true,
            'is_active'        => true,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        $moduleId = DB::table('modules')->where('name', 'sections')->value('id');

        foreach (['list', 'create', 'edit', 'delete'] as $action) {
            DB::table('permissions')->insertOrIgnore([
                'name'       => 'sections-' . $action,
                'guard_name' => 'web',
                'module_id'  => $moduleId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $moduleId = DB::table('modules')->where('name', 'sections')->value('id');

        DB::table('permissions')->where('module_id', $moduleId)->delete();
        DB::table('modules')->where('name', 'sections')->delete();
    }
};
