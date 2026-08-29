# AI Session Context — Laravel Admin Panel

> Hand this file to a new AI session so it can continue work without reading all source files from scratch.
> Last updated: 2026-06-09 (rev 2). Verified against actual source code.

---

## 1. Project Overview

A Laravel admin panel with Spatie Permission for role-based access control. Key modules built so far: **Users, Permissions, Modules, Roles**.

---

## 2. Stack & Environment

| Item | Detail |
|---|---|
| Framework | Laravel (PHP 8+) |
| Database | **PostgreSQL** — connection name `pgsql` |
| DB table prefix | `sr_` (all tables prefixed, e.g. `sr_users`, `sr_roles`, `sr_permissions`, `sr_modules`) |
| Auth package | Spatie Permission (roles + permissions) |
| UI | Bootstrap 5.3.3 + Bootstrap Icons |
| DataTables | jQuery DataTables — **client-side** (serverSide: false) |
| Alerts | SweetAlert2 |
| IDE errors | **All false positives** — `vendor/` not installed, `composer update` never run. Every "Undefined type/function/method" error in the IDE disappears after `composer install`. Do NOT attempt to fix them. |

---

## 3. Design System CSS Variables

Defined in the global layout stylesheet. Use these — never hardcode hex values.

```css
--primary        /* deep red, e.g. used for buttons, links */
--primary-mid    /* gradient partner for --primary */
--accent         /* slightly different red for danger actions */
--bg             /* light pinkish page background */
--text           /* main text */
--text-2         /* secondary text */
--text-muted     /* muted / hint text */
--border         /* default border */
--border-light   /* lighter divider border */
--r              /* default border-radius */
--r-sm           /* smaller radius */
--r-lg           /* large radius (cards) */
--sh-lg          /* card box-shadow */
```

SweetAlert2 confirm button color: `'#8B1A1A'` · cancel: `'#9B7575'`

---

## 4. Global JS Variables (from layout)

Injected by `admin.layouts.app` into every page. Always available in scripts:

```js
var csrfToken = '{{ csrf_token() }}';
var adminUrl  = '{{ url("/admin") }}';   // used for building edit links
```

---

## 5. Architecture

### 5a. AdminController (Abstract Base)

**File:** `app/Http/Controllers/Admin/AdminController.php`

Every admin controller **must** extend this and implement all four abstract methods:

```php
abstract public function index();
abstract public function get_data(): JsonResponse;
abstract public function AddEditAction(int $id = null);
abstract public function handlePost(int $id = null): RedirectResponse;
```

Also provides default (overridable):
- `DeleteRecord(): JsonResponse` — returns 501 by default
- `DeleteSingleRecord(int $id): JsonResponse` — returns 501 by default
- `ExportRecord()` — aborts 501

Constructor auto-sets `$this->moduleName`, `$this->moduleTitle`, `$this->viewBase` via `ModuleResolver`.

---

### 5b. ModuleResolver (Dynamic Routing)

**File:** `app/Services/ModuleResolver.php`

Reads URL segment 2 (e.g. `/admin/users` → `users`) and looks up the `modules` table for a matching active module. Stores result in `Config::set('module.*')`.

Key method: `buildControllerClass()` combines `controller_path` + `controller_class`:
- `path=AccessControl` + `class=RoleController` → `App\Http\Controllers\Admin\AccessControl\RoleController`

Permission helper: `ModuleResolver::permission('edit')` → `"{slug}-edit"`.

---

### 5c. Dynamic Route Registration

**File:** `routes/web.php`

Dynamic routes are registered at **request time** if `ModuleResolver::resolve()` succeeds:

```
GET  /admin/{slug}/           → index()       middleware: {slug}-list
ANY  /admin/{slug}/get_data   → get_data()    middleware: {slug}-list
POST /admin/{slug}/create     → create()      middleware: {slug}-create
POST /admin/{slug}/delete     → deleteRecord() middleware: {slug}-delete
ANY  /admin/{slug}/export     → ExportRecord() middleware: {slug}-export
```

Route names: `admin.{slug}.index`, `admin.{slug}.get_data`, `admin.{slug}.create`, `admin.{slug}.delete`, `admin.{slug}.export`

> **Important:** The dynamic `create` route maps to `deleteRecord` internally — this is because the method is named `DeleteRecord()` in the controller but the route action is `deleteRecord` (case matters in route resolution). Verify when adding new modules.

---

### 5d. Static Role Routes (exception to dynamic routing)

Roles use a **full-page form** (not modal), so they have hardcoded routes:

```
GET  /admin/roles/add         → RoleController::AddEditAction()  name: admin.roles.add
POST /admin/roles/add         → RoleController::handlePost()     name: admin.roles.add.store
GET  /admin/roles/{id}/edit   → RoleController::AddEditAction($id) name: admin.roles.edit
POST /admin/roles/{id}/edit   → RoleController::handlePost($id)  name: admin.roles.update
```

The dynamic routes still handle: `admin.roles.index`, `admin.roles.get_data`, `admin.roles.delete`.

---

## 6. Two CRUD Patterns

### Pattern A — AJAX Modal (Users, Permissions, Modules)

- `index()` → returns view (passes data the modal needs rendered in PHP, e.g. `$modules`, `$roles`)
- `get_data()` → JSON for DataTable
- `create(Request)` → AJAX POST handling **both create and update** — `id` in body = update, no `id` = create → returns `JsonResponse`
- `DeleteRecord()` → AJAX bulk soft-delete → `JsonResponse`
- `DeleteSingleRecord(int $id)` → AJAX single soft-delete → `JsonResponse`
- `AddEditAction()` / `handlePost()` → `abort(404)` (not used)

### Pattern B — Full-page Form (Roles)

- `index()` → returns view (no data needed, DataTable handles listing)
- `get_data()` → JSON for DataTable  
- `AddEditAction($id = null)` → returns `admin.roles.form` view with grouped permissions
- `handlePost($id = null)` → validates + saves + `redirect(url('/admin/roles'))->with('success', ...)`
- `create(Request)` → still present for AJAX consumers (programmatic use), returns `JsonResponse`
- `DeleteRecord()` → AJAX soft-delete → `JsonResponse`

---

## 7. Models

> **Namespace convention:** Access-control models live under `App\Models\AccessCenter\`, not `App\Models\` directly.

### `App\Models\AccessCenter\User`

```php
use HasFactory, Notifiable, HasRoles, SoftDeletes;
// SoftDeletes: deleted_at column exists (migration: 2026_06_08_000001_...)
// HasRoles: from Spatie
// TracksAudit: provides createdBy() / updatedBy() relations + auto-fills created_by/updated_by
protected $fillable = ['name', 'email', 'number', 'password', 'is_active'];
```

### `App\Models\AccessCenter\Role`

```php
// Extends Spatie\Permission\Models\Role
use TracksAudit;
// TracksAudit auto-sets created_by, updated_by on creating/updating
// Provides createdBy() and updatedBy() belongsTo(User) relations
// NOTE: deleted_at migration (2026_06_08_000002_...) was created but SoftDeletes trait
// is not yet in this model — add 'use SoftDeletes;' if soft-delete is needed.
```

Registered in `config/permission.php`:
```php
'role' => App\Models\AccessCenter\Role::class,
```

### `App\Models\Module`

```php
use SoftDeletes, TracksAudit;
protected $fillable = ['section_id','name','title','controller_path','controller_class',
                       'model_class','table_name','icon','sort_order','is_admin','is_active'];
// Relationships:
public function section() // belongsTo(Section::class)
// Static helpers:
Module::createWithPermissions($data)  // creates module + 4 default permissions (list,create,edit,delete)
Module::activeAdminModules()          // sorted by section.order → section_name → sort_order → title
```

### `App\Traits\TracksAudit`

```php
// Boot: on creating → sets created_by = auth()->id(), updated_by = auth()->id()
//       on updating  → sets updated_by = auth()->id()
// Relations: createdBy() → belongsTo(User, 'created_by')
//            updatedBy() → belongsTo(User, 'updated_by')
```

**The roles table needs `created_by` and `updated_by` columns** for TracksAudit to work. Ensure a migration exists for these.

### `Section` model (used by Module)

Exists at `App\Models\Section` (not yet read). Has columns: `section_name`, `order`. Modules have FK `section_id`.

---

## 8. Database Migrations

| File | What it does |
|---|---|
| `0001_01_01_000000_create_users_table.php` | Base users table |
| `0002_01_01_000000_create_permission_tables.php` | Spatie roles, permissions, pivot tables. Also adds `module_id` nullable FK to permissions |
| `0002_01_01_000001_create_modules_table.php` | Modules table (includes `section_id`, `created_by`, `updated_by`, soft-deletes presumably) |
| `2026_06_08_000001_add_number_is_active_softdelete_to_users_table.php` | Adds `number`, `is_active`, `deleted_at` to users |
| `2026_06_08_000002_add_softdelete_to_roles_table.php` | Adds `deleted_at` to roles table |

> There are likely additional migrations for: sections table, `created_by`/`updated_by` on roles table. Check with `php artisan migrate:status`.

**Raw SQL in migrations / queries:** When using `->orderByRaw()` or other raw SQL, always get the prefix first:
```php
$prefix = DB::getTablePrefix();
->orderByRaw("COALESCE({$prefix}sections.\"order\", 9999)")
```
Never write `->orderByRaw('modules.sort_order ...')` — the prefix is not applied to raw strings.

---

## 9. Permission Naming Convention

Format: `{module-slug}-{action}` — **dash-separated, all lowercase**

Examples: `users-list`, `users-create`, `users-edit`, `users-delete`, `roles-list`, `roles-edit`

Standard actions per module: `list`, `create`, `edit`, `delete` (+ `export` optional)

`Module::createWithPermissions($data)` auto-creates these 4 when a new module is made.

---

## 10. Controllers — Full Details

### `UserController` (`admin/users`)

Imports: `App\Models\AccessCenter\Role`, `App\Models\AccessCenter\User`

```php
// index()     → view('admin.users.index', compact('roles'))
//               $roles = Role::orderBy('name')->get()  (for modal dropdown)
// get_data()  → User::with('roles','createdBy','updatedBy')->latest()->get()
//               returns: id,name,email,number,is_active,roles(string),role_names[],joined,
//                        created_by_name,updated_by_name,updated_at_fmt
// create()    → AJAX: validates name/email/number/password/roles; syncRoles(); soft-delete on delete
// DeleteRecord() → User::whereIn('id',$ids)->delete() — soft-delete (SoftDeletes on model)
```

### `PermissionController` (`admin/permissions`)

```php
// index()     → view('admin.permissions.index', compact('modules'))
//               $modules = Module::where('is_active',true)->orderBy('title')->get()
// get_data()  → LEFT JOIN modules; returns id,name,guard_name,module_id,module_title,module_name,module_icon,created
// create()    → validates name(unique)/guard_name/module_id; saves module_id via DIRECT assignment (bypasses fillable):
//               $permission = Permission::create([...]); $permission->module_id = $moduleId; $permission->save();
// DeleteRecord() → Permission::whereIn('id',$ids)->delete() — HARD delete (no SoftDeletes on Spatie Permission)
```

### `ModuleController` (`admin/modules`)

```php
// index()    → view('admin.modules.index')  — no data passed, DataTable loads via get_data
// get_data() → returns id,name,title,icon,controller_path,controller_class,model_class,table_name,sort_order,is_admin,is_active,created
// create()   → validates all module fields; boolean fix: $request->boolean('is_admin'), $request->boolean('is_active')
//              On CREATE: Module::createWithPermissions($data)  (auto-creates 4 permissions)
//              On UPDATE: Module::findOrFail($id)->update($data)
// DeleteRecord() → Module::whereIn('id',$ids)->delete() — soft-delete (SoftDeletes on model)
```

### `RoleController` (`admin/roles`)

```php
// index()       → view('admin.roles.index')  — no data passed (blade uses DataTable + edit links)
//
// get_data()    → Role::with('permissions','createdBy','updatedBy')
//                 returns: id,name,permissions_count,permission_names[],created,
//                          created_by_name,updated_by_name,updated_at_fmt
//
// AddEditAction($id=null) → Builds permissions grouped by SECTION then by MODULE:
//   DB::table('permissions')
//     ->leftJoin('modules', ...)
//     ->leftJoin('sections', 'modules.section_id','=','sections.id')
//     ->select('permissions.id','permissions.name','modules.title','modules.name',
//              'modules.icon','sections.section_name')
//     ->orderByRaw("COALESCE({$prefix}sections.\"order\", 9999)")
//     ->orderBy('sections.section_name')
//     ->orderBy('modules.sort_order')
//     ->orderBy('modules.title')
//     ->orderBy('permissions.name')
//     ->get()
//     ->groupBy(fn($p) => $p->section_name ?? '')
//     ->map(fn($sectionPerms) => $sectionPerms->groupBy(fn($p) => $p->module_title ?? 'Other'))
//   Returns view('admin.roles.form', compact('permissions'[, 'role', 'rolePermissions']))
//
// handlePost($id=null) → validates name+permissions; syncPermissions(); redirect to /admin/roles
//
// create(Request) → AJAX: same validate/sync logic; returns JsonResponse (for programmatic use)
// DeleteRecord()  → Role::whereIn('id',$ids)->delete() — uses TracksAudit model
//                   NOTE: SoftDeletes is in migration but NOT yet in model — currently HARD delete.
//                   To enable soft-delete: add 'use SoftDeletes;' to app/Models/Role.php
```

---

## 11. Views

### `admin.roles.index`

**File:** `resources/views/admin/roles/index.blade.php`

- `uc-card` pattern (same CSS as users/permissions/modules)
- "Add Role" → link to `route('admin.roles.add')` (NOT a modal button)
- DataTable columns: `#` (serial), `Role Name` (icon+name), `Permissions` (count badge + first 3 names + overflow), `Created By` (renderAudit), `Actions` (edit link + AJAX delete button)
- **Edit button** → `<a href="adminUrl + '/roles/' + data.id + '/edit'">` (full-page link, not modal)
- **Delete button** → AJAX POST to `admin.roles.delete` with SweetAlert2 confirm
- `renderAudit(creator, date, updater, updatedDate)` — helper function renders created-by name + date + "edited by" line
- No modal in this blade

### `admin.roles.form`

**File:** `resources/views/admin/roles/form.blade.php`

Full-page two-column layout:
- **Left (col-xl-3, sticky):** Role name input + permission count progress bar + Save/Cancel buttons
- **Right (col-xl-9):** Permissions grid with search filter, grouped by Section → Module

Permission grouping: `$permissions` is `Collection<sectionName, Collection<moduleTitle, Collection<Permission>>>`

Permission chips: `.perm-chip` → `label:has(input:checked)` CSS for checked state

Buttons:
- `.btn-tgl-mod[data-group]` — toggles all permissions in a module
- `.btn-tgl-sec[data-sec]` — toggles all permissions in a section
- `#btnGlobalToggle` — toggles all permissions

Filter: `#permSearch` filters `.perm-chip` by `data-label` (action name) and `data-mod` (module slug)

Counter: `#selCount` shows "N / total", `#selBar` shows progress bar width

### `admin.users.index`, `admin.permissions.index`, `admin.modules.index`

All use the **AJAX modal pattern**:
- `uc-card` with custom header (icon + title + count badge + search input)
- DataTable `dom: 'rt<"dt-foot"lip>'`
- Modal with `data-bs-backdrop="static"`, `modal-dialog-scrollable`, gradient header
- Form submits via `$.ajax()` to `POST /{slug}/create`
- On success: `dt.ajax.reload(null, false)` + SweetAlert2 toast

#### Permissions index specifics:
- Module dropdown in modal → auto-fills permission name with `{slug}-`
- Quick-action buttons (List/Create/Edit/Delete/Export) → fill `{slug}-{action}`
- Permission name column: splits on `-` or `.`, shows module part as badge + action part bold

#### Modules index specifics:
- Auto-suggest: Title → Slug (toSlug), ControllerClass (toPascal+'Controller'), ModelClass (toPascal), TableName (toSnake)
- Fields track `data('edited', true)` to stop auto-suggest after manual edit
- Is Admin + Is Active: `<input type="hidden" name="is_admin" value="0">` before checkbox — ensures `0` submits when unchecked
- Create-only note: "Default permissions list,create,edit,delete will be created automatically"

---

## 12. DataTable Standard Setup

All DataTables use this pattern:

```js
var dt = $('#xTable').DataTable({
    processing : true,
    serverSide : false,
    ajax       : { url: URL_DATA, type: 'GET' },
    order      : [[colIndex, 'asc']],
    pageLength : 10,
    scrollX    : true,   // horizontal scroll when table wider than viewport
    dom        : 'rt<"dt-foot"lip>',
    columns: [
        // Serial number column (always first):
        {
            data: null, orderable: false, searchable: false,
            render: function(data, type, row, meta) {
                return meta.row + meta.settings._iDisplayStart + 1;
            }
        },
        // ... data columns ...
    ],
    language: {
        emptyTable: '<div class="py-4 text-center text-muted">...</div>',
        zeroRecords: '<div ...>No matching results</div>',
        lengthMenu: 'Show _MENU_',
        info: 'Showing _START_–_END_ of _TOTAL_',
        infoFiltered: '(of _MAX_)',
        paginate: {
            first: '<i class="bi bi-chevron-double-left"></i>',
            previous: '<i class="bi bi-chevron-left"></i>',
            next: '<i class="bi bi-chevron-right"></i>',
            last: '<i class="bi bi-chevron-double-right"></i>',
        },
    },
    initComplete: function () { $('#xCountBadge').text(this.api().data().length + ' total'); },
    drawCallback: function () {
        var api = this.api(), total = api.data().length,
            filtered = api.rows({ search: 'applied' }).count();
        $('#xCountBadge').text(filtered === total ? total + ' total' : filtered + ' / ' + total);
    },
});

// Custom search box (NOT DataTable's built-in — it uses #ucSearch):
var searchTimer;
$('#ucSearch').on('input', function () {
    clearTimeout(searchTimer);
    var val = this.value;
    searchTimer = setTimeout(function() { dt.search(val).draw(); }, 220);
});
```

Edit button `data-row` attribute: `JSON.stringify(rowData).replace(/'/g, "&#39;")` — stored as single-quoted HTML attribute.

---

## 13. AJAX Delete Pattern (Modules, Users, Permissions, Roles list)

```js
Swal.fire({
    title: 'Delete "' + name + '"?',
    text:  '...',
    icon:  'warning',
    showCancelButton: true,
    confirmButtonColor: '#8B1A1A',
    cancelButtonColor:  '#9B7575',
    confirmButtonText:  'Yes, delete',
}).then(function(res) {
    if (!res.isConfirmed) return;
    $.ajax({
        url:  URL_DELETE,
        type: 'POST',
        data: { _token: csrfToken, ids: [id] },
        success: function(r) {
            if (r.success) { dt.ajax.reload(null, false); Swal.fire({...}); }
            else { Swal.fire({ icon: 'error', ... }); }
        },
        error: function(xhr) { Swal.fire({ icon: 'error', ... }); }
    });
});
```

---

## 14. Audit Column Rendering (Roles list and Users list)

```js
function renderAudit(creator, date, updater, updatedDate) {
    var nameHtml = creator
        ? '<span style="font-size:.82rem;font-weight:600;color:var(--text);">' + esc(creator) + '</span>'
        : '<span style="font-size:.82rem;color:var(--text-muted);">—</span>';
    var dateHtml = '<div style="font-size:.74rem;color:var(--text-muted);">' + esc(date) + '</div>';
    var updHtml = '';
    if (updater && updater !== creator) {
        updHtml = '<div style="font-size:.72rem;color:var(--text-muted);margin-top:.1rem;">'
                + '<i class="bi bi-pencil-fill" style="font-size:.6rem;opacity:.6;"></i> '
                + esc(updater) + (updatedDate ? ' · ' + esc(updatedDate) : '') + '</div>';
    }
    return nameHtml + dateHtml + updHtml;
}
```

`get_data()` must return: `created_by_name`, `created` (formatted date), `updated_by_name`, `updated_at_fmt`.

Both `UserController` and `RoleController` return these fields. The users blade uses the same `renderAudit` helper pattern.

---

## 15. Boolean Checkbox Pattern (Modules modal)

Problem: unchecked HTML checkboxes submit nothing. Solution:

```html
<input type="hidden"   name="is_active" value="0">  <!-- always submits 0 if unchecked -->
<input type="checkbox" name="is_active" value="1" id="mIsActive">  <!-- submits 1 if checked -->
```

Controller reads: `$request->boolean('is_active')` — returns `false` when value is `"0"` or absent.

---

## 16. module_id Fillable Bypass (Permissions)

Spatie's `Permission` model does not have `module_id` in `$fillable`. Direct attribute assignment bypasses mass-assignment guards:

```php
// Create:
$permission = Permission::create(['name' => $name, 'guard_name' => 'web']);
$permission->module_id = $moduleId;  // direct assignment — not mass assignment
$permission->save();

// Update:
$permission->name = $data['name'];
$permission->guard_name = $guardName;
$permission->module_id = $moduleId;
$permission->save();
```

---

## 17. Known Pending / Incomplete Items

1. **Role soft-delete not fully wired:** Migration `2026_06_08_000002_add_softdelete_to_roles_table.php` adds `deleted_at` to roles table, but `App\Models\Role` currently only has `use TracksAudit` — no `use SoftDeletes`. Add `use Illuminate\Database\Eloquent\SoftDeletes;` and `use SoftDeletes;` to the Role model to complete this.

2. **Role TracksAudit columns:** `created_by` and `updated_by` must exist on the roles table for `TracksAudit` to work. Verify migration exists (or create one if not).

3. **Sections table / model:** `Section` model and `sections` table are referenced by Module and RoleController but their migration/model files were not reviewed. Ensure `sections` table has `id`, `section_name`, `order` columns.

4. **`admin.roles.form` (roles/form.blade.php) is complete** — two-column sticky-panel layout with full permission search/filter/toggle. No further work needed there unless new features are requested.

5. **Old role files** — `resources/views/admin/roles/create.blade.php` and `resources/views/admin/roles/edit.blade.php` are **unused legacy files** (superseded by `form.blade.php`). Safe to delete.

6. **`AdminController` abstract method mismatch:** The abstract requires `AddEditAction(int $id = null)` and `handlePost(int $id = null): RedirectResponse`. The RoleController uses `$id = null` without the `int` type hint (to accept the route-injected value). This is fine at runtime but can be noted.

---

## 18. File Index (All Files Touched This Session)

| File | Status |
|---|---|
| `app/Http/Controllers/Admin/AdminController.php` | Read only — not modified |
| `app/Http/Controllers/Admin/AccessControl/UserController.php` | Imports `App\Models\AccessCenter\{Role,User}`; `get_data()` now includes audit fields (createdBy, updatedBy) |
| `app/Http/Controllers/Admin/AccessControl/PermissionController.php` | Full rewrite (AJAX modal, module_id support) |
| `app/Http/Controllers/Admin/AccessControl/ModuleController.php` | Full rewrite (AJAX modal, boolean fix, createWithPermissions) |
| `app/Http/Controllers/Admin/AccessControl/RoleController.php` | Full rewrite (extends AdminController, get_data with audit, AddEditAction full-page, handlePost) |
| `app/Models/AccessCenter/User.php` | Has SoftDeletes + TracksAudit (namespace moved to AccessCenter) |
| `app/Models/AccessCenter/Role.php` | Extends SpatieRole, uses TracksAudit (namespace moved to AccessCenter) |
| `app/Models/Module.php` | Read only |
| `app/Traits/TracksAudit.php` | Read only |
| `app/Services/ModuleResolver.php` | Read only |
| `config/permission.php` | `role` model → `App\Models\AccessCenter\Role::class` |
| `resources/views/admin/permissions/index.blade.php` | Full rewrite (AJAX DataTable + modal, module grouping, auto-prefix) |
| `resources/views/admin/modules/index.blade.php` | Full rewrite (AJAX DataTable + modal, auto-suggest, boolean switches) |
| `resources/views/admin/roles/index.blade.php` | Full rewrite — then user modified: link-based navigation, Created By column, renderAudit helper, no modal |
| `resources/views/admin/roles/form.blade.php` | Created by user — full-page sticky-panel + permission grid with section/module grouping, search filter |
| `database/migrations/2026_06_08_000002_add_softdelete_to_roles_table.php` | Created — adds `deleted_at` to roles table |
| `routes/web.php` | Hardcoded role routes added (add, add.store, edit, update) |
| `resources/views/admin/users/index.blade.php` | Added `scrollX: true` to DataTable |
| `resources/views/admin/permissions/index.blade.php` | Added `scrollX: true` to DataTable |
| `resources/views/admin/modules/index.blade.php` | Added `scrollX: true` to DataTable |
| `resources/views/admin/roles/index.blade.php` | Added `scrollX: true` to DataTable |
| `resources/views/admin/sections/index.blade.php` | Added `scrollX: true` to DataTable |
