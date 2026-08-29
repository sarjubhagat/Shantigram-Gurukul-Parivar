<?php $__env->startSection('title', 'Roles'); ?>
<?php $__env->startSection('page-title', 'Roles'); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item active">Roles</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-actions'); ?>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('roles-create')): ?>
        <a href="<?php echo e(route('admin.roles.add')); ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-shield-lock me-1"></i> Add Role
        </a>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="card uc-card">

    <div class="card-header uc-header">
        <span class="uc-left">
            <span class="card-header-icon"><i class="bi bi-shield-lock"></i></span>
            <span class="uc-title">All Roles</span>
            <span class="uc-count" id="roleCountBadge">—</span>
        </span>
        <div class="uc-search-box">
            <i class="bi bi-search uc-search-ico"></i>
            <input type="text" id="ucSearch" class="uc-search-inp"
                   placeholder="Search roles…" autocomplete="off">
        </div>
    </div>

    <table id="rolesTable" class="table dt-table mb-0" style="width:100%;">
        <thead>
            <tr>
                <th style="width:50px;">#</th>
                <th>Role Name</th>
                <th>Permissions</th>
                <th style="width:150px;">Created By</th>
                <th class="text-center" style="width:100px;">Actions</th>
            </tr>
        </thead>
    </table>

</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
/* ── Card + header ─────────────────────────────────────── */
.uc-card {
    border-radius: var(--r-lg);
    box-shadow: 0 2px 12px rgba(139,26,26,.07), 0 1px 3px rgba(0,0,0,.05);
    overflow: hidden;
}
.uc-header {
    padding: .82rem 1.25rem !important;
    border-bottom: 1.5px solid var(--border-light) !important;
}
.uc-left { display: flex; align-items: center; gap: .55rem; }
.uc-title { font-size: .9rem; font-weight: 700; color: var(--text); }
.uc-count {
    background: rgba(139,26,26,.09); color: var(--primary);
    border-radius: 2rem; padding: .18em .72em;
    font-size: .71rem; font-weight: 700; letter-spacing: .2px;
}
.uc-search-box { position: relative; }
.uc-search-ico {
    position: absolute; left: .75rem; top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted); font-size: .78rem; pointer-events: none;
}
.uc-search-inp {
    border: 1.5px solid var(--border); border-radius: 2rem;
    padding: .38rem 1rem .38rem 2.1rem;
    font-size: .82rem; font-family: inherit;
    color: var(--text); background: var(--bg);
    outline: none; width: 210px;
    transition: border-color .2s, box-shadow .2s, width .25s;
}
.uc-search-inp:focus {
    border-color: var(--primary); background: #fff;
    box-shadow: 0 0 0 3px rgba(139,26,26,.09); width: 255px;
}
.uc-search-inp::placeholder { color: var(--text-muted); }

/* ── DataTable ─────────────────────────────────────────── */
#rolesTable { border-collapse: collapse !important; }
#rolesTable_wrapper { border: none !important; }
#rolesTable .btn-edit:hover { background: rgba(139,26,26,.07) !important; border-color: var(--primary) !important; }
#rolesTable .btn-del:hover  { background: rgba(192,57,43,.07)  !important; border-color: var(--accent)  !important; }
.dt-foot {
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap;
    gap: .5rem; padding: .7rem 1.25rem;
    background: #FAFAFA;
    border-top: 1.5px solid var(--border-light);
    border-radius: 0 0 var(--r-lg) var(--r-lg);
}
.dt-foot .dataTables_length  { flex-shrink: 0; }
.dt-foot .dataTables_info    { flex: 1; text-align: center; }
.dt-foot .dataTables_paginate { flex-shrink: 0; }
#rolesTable_paginate .paginate_button.first,
#rolesTable_paginate .paginate_button.previous,
#rolesTable_paginate .paginate_button.next,
#rolesTable_paginate .paginate_button.last {
    width: 32px !important; height: 32px !important; min-width: 32px !important;
    padding: 0 !important; display: inline-flex !important;
    align-items: center !important; justify-content: center !important;
    font-size: .85rem !important; line-height: 1 !important;
}
#rolesTable_paginate .paginate_button { vertical-align: middle !important; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {

    var URL_DATA   = '<?php echo e(route("admin.roles.get_data")); ?>';
    var URL_DELETE = '<?php echo e(route("admin.roles.delete")); ?>';
    var searchTimer;

    // ── DataTable ───────────────────────────────────────────
    var dt = $('#rolesTable').DataTable({
        processing : true,
        serverSide : false,
        ajax       : { url: URL_DATA, type: 'GET' },
        order      : [[1, 'asc']],
        pageLength : 10,
        scrollX    : true,
        dom        : 'rt<"dt-foot"lip>',
        columns: [
            {
                data      : null,
                orderable : false,
                searchable: false,
                width     : '50px',
                render    : function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            {
                data: 'name',
                render: function (val, type, row) {
                    if (type !== 'display') return val + ' ' + (row.unique_key || '');
                    return '<div class="d-flex align-items-center gap-2">'
                         + '<div style="width:34px;height:34px;background:rgba(139,26,26,.09);border-radius:var(--r-sm);'
                         + 'display:flex;align-items:center;justify-content:center;flex-shrink:0;">'
                         + '<i class="bi bi-shield-lock" style="color:var(--primary);font-size:1rem;"></i></div>'
                         + '<div style="line-height:1.35;">'
                         + '<div style="font-size:.875rem;font-weight:600;color:var(--text);">' + esc(val) + '</div>'
                         + (row.unique_key ? '<div style="font-family:ui-monospace,monospace;font-size:.7rem;color:var(--text-muted);">' + esc(row.unique_key) + '</div>' : '')
                         + '</div>'
                         + '</div>';
                }
            },
            {
                data: 'permissions_count',
                render: function (val, type, row) {
                    if (val === 0) {
                        return '<span style="font-size:.8rem;color:var(--text-muted);">—</span>';
                    }
                    var out = '<span class="badge me-1 mb-1" style="background:rgba(139,26,26,.09);color:var(--primary);'
                            + 'font-size:.72rem;padding:.28em .65em;border-radius:.35rem;">'
                            + val + ' perm' + (val !== 1 ? 's' : '') + '</span>';
                    if (type !== 'display') return out;
                    row.permission_names.slice(0, 3).forEach(function (name) {
                        out += '<span class="badge me-1 mb-1" style="background:rgba(0,0,0,.05);color:var(--text-2);'
                             + 'font-size:.67rem;padding:.22em .5em;border-radius:.3rem;">' + esc(name) + '</span>';
                    });
                    if (val > 3) {
                        out += '<span class="badge mb-1" style="background:rgba(108,117,125,.1);color:#6c757d;'
                             + 'font-size:.67rem;padding:.22em .5em;border-radius:.3rem;">+' + (val - 3) + ' more</span>';
                    }
                    return out;
                }
            },
            {
                data: 'created_by_name',
                render: function (val, type, row) {
                    return renderAudit(val, row.created, row.updated_by_name, row.updated_at_fmt);
                }
            },
            {
                data      : null,
                orderable : false,
                searchable: false,
                className : 'text-center',
                render    : function (data) {
                    return '<div class="d-flex gap-1 justify-content-center">'
                         + '<a href="' + adminUrl + '/roles/' + data.id + '/edit"'
                         + ' class="btn btn-sm btn-icon btn-edit"'
                         + ' style="width:30px;height:30px;border:1.5px solid var(--border);background:#fff;border-radius:var(--r-sm);color:var(--primary);transition:all .18s;"'
                         + ' title="Edit"><i class="bi bi-pencil-square" style="font-size:.85rem;"></i></a>'
                         + '<button class="btn btn-sm btn-icon btn-del"'
                         + ' style="width:30px;height:30px;border:1.5px solid var(--border);background:#fff;border-radius:var(--r-sm);color:var(--accent);transition:all .18s;"'
                         + ' data-id="' + data.id + '" data-name="' + esc(data.name) + '" title="Delete">'
                         + '<i class="bi bi-trash" style="font-size:.85rem;"></i></button>'
                         + '</div>';
                }
            },
        ],
        language: {
            emptyTable    : '<div class="py-4 text-center text-muted"><i class="bi bi-inbox d-block mb-2" style="font-size:2rem;opacity:.4;"></i>No roles found</div>',
            zeroRecords   : '<div class="py-4 text-center text-muted"><i class="bi bi-search d-block mb-2" style="font-size:1.6rem;opacity:.35;"></i>No matching roles</div>',
            loadingRecords: 'Loading…',
            processing    : '<span style="color:var(--primary);">Loading…</span>',
            lengthMenu    : 'Show _MENU_',
            info          : 'Showing _START_–_END_ of _TOTAL_',
            infoEmpty     : 'No roles',
            infoFiltered  : '(of _MAX_)',
            paginate: {
                first    : '<i class="bi bi-chevron-double-left"></i>',
                previous : '<i class="bi bi-chevron-left"></i>',
                next     : '<i class="bi bi-chevron-right"></i>',
                last     : '<i class="bi bi-chevron-double-right"></i>',
            },
        },
        initComplete: function () {
            $('#roleCountBadge').text(this.api().data().length + ' total');
        },
        drawCallback: function () {
            var api      = this.api();
            var total    = api.data().length;
            var filtered = api.rows({ search: 'applied' }).count();
            $('#roleCountBadge').text(
                filtered === total ? total + ' total' : filtered + ' / ' + total
            );
        },
    });

    // ── Header search ───────────────────────────────────────
    $('#ucSearch').on('input', function () {
        clearTimeout(searchTimer);
        var val = this.value;
        searchTimer = setTimeout(function () { dt.search(val).draw(); }, 220);
    });

    // ── Delete ──────────────────────────────────────────────
    $('#rolesTable').on('click', '.btn-del', function () {
        var id   = $(this).data('id');
        var name = $(this).data('name');
        Swal.fire({
            title             : 'Delete "' + name + '"?',
            text              : 'This role will be permanently removed.',
            icon              : 'warning',
            showCancelButton  : true,
            confirmButtonColor: '#8B1A1A',
            cancelButtonColor : '#9B7575',
            confirmButtonText : 'Yes, delete',
        }).then(function (res) {
            if (!res.isConfirmed) return;
            $.ajax({
                url    : URL_DELETE,
                type   : 'POST',
                data   : { _token: csrfToken, ids: [id] },
                success: function (r) {
                    if (r.success) {
                        dt.ajax.reload(null, false);
                        Swal.fire({ icon: 'success', title: 'Deleted!', text: r.message, timer: 1800, showConfirmButton: false });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: r.message });
                    }
                },
                error: function (xhr) {
                    Swal.fire({ icon: 'error', title: 'Error', text: (xhr.responseJSON || {}).message || 'Delete failed.' });
                }
            });
        });
    });

    // ── Helpers ─────────────────────────────────────────────
    function renderAudit(creator, date, updater, updatedDate) {
        var nameHtml = creator
            ? '<span style="font-size:.82rem;font-weight:600;color:var(--text);">' + esc(creator) + '</span>'
            : '<span style="font-size:.82rem;color:var(--text-muted);">—</span>';
        var dateHtml = '<div style="font-size:.74rem;color:var(--text-muted);">' + esc(date) + '</div>';
        var updHtml  = '';
        if (updater && updater !== creator) {
            updHtml = '<div style="font-size:.72rem;color:var(--text-muted);margin-top:.1rem;">'
                    + '<i class="bi bi-pencil-fill" style="font-size:.6rem;opacity:.6;"></i> '
                    + esc(updater)
                    + (updatedDate ? ' · ' + esc(updatedDate) : '')
                    + '</div>';
        }
        return nameHtml + dateHtml + updHtml;
    }

    function esc(str) {
        return $('<span>').text(String(str || '')).html();
    }

})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\projects\Food Management\resources\views/admin/roles/index.blade.php ENDPATH**/ ?>