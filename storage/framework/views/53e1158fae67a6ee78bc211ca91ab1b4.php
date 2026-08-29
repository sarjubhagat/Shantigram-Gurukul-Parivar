

<?php $__env->startSection('title', 'Parivar'); ?>
<?php $__env->startSection('page-title', 'Parivar'); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item active">Parivar</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-actions'); ?>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('parivar-create')): ?>
        <a href="<?php echo e(route('admin.parivar.add')); ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-person-plus me-1"></i> Add Member
        </a>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="card uc-card">

    <div class="card-header uc-header">
        <span class="uc-left">
            <span class="card-header-icon"><i class="bi bi-people-fill"></i></span>
            <span class="uc-title">All Members</span>
            <span class="uc-count" id="memberCountBadge">—</span>
        </span>
        <div class="uc-search-box">
            <i class="bi bi-search uc-search-ico"></i>
            <input type="text" id="ucSearch" class="uc-search-inp"
                   placeholder="Search members…" autocomplete="off">
        </div>
    </div>

    <table id="membersTable" class="table dt-table mb-0" style="width:100%;">
        <thead>
            <tr>
                <th style="width:50px;">#</th>
                <th style="width:90px;">ID</th>
                <th>Name</th>
                <th>Aadhaar</th>
                <th>Phone</th>
                <th>Gender</th>
                <th>Gurukul</th>
                <th>Created By</th>
                <th class="text-center" style="width:100px;">Actions</th>
            </tr>
        </thead>
    </table>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Gujarati:wght@400;600&display=swap" rel="stylesheet">
<style>
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
    position: absolute; left: .75rem; top: 50%; transform: translateY(-50%);
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
#membersTable { border-collapse: collapse !important; }
#membersTable_wrapper { border: none !important; }
#membersTable .btn-edit:hover { background: rgba(139,26,26,.07) !important; border-color: var(--primary) !important; }
#membersTable .btn-del:hover  { background: rgba(192,57,43,.07)  !important; border-color: var(--accent)  !important; }
.dt-foot {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: .5rem;
    padding: .7rem 1.25rem; background: #FAFAFA;
    border-top: 1.5px solid var(--border-light);
    border-radius: 0 0 var(--r-lg) var(--r-lg);
}
.dt-foot .dataTables_length { flex-shrink: 0; }
.dt-foot .dataTables_info   { flex: 1; text-align: center; }
.dt-foot .dataTables_paginate { flex-shrink: 0; }
#membersTable_paginate .paginate_button.first,
#membersTable_paginate .paginate_button.previous,
#membersTable_paginate .paginate_button.next,
#membersTable_paginate .paginate_button.last {
    width: 32px !important; height: 32px !important; min-width: 32px !important;
    padding: 0 !important; display: inline-flex !important;
    align-items: center !important; justify-content: center !important;
    font-size: .85rem !important; line-height: 1 !important;
}
.uid-badge {
    font-family: ui-monospace, monospace;
    font-size: .78rem; font-weight: 700; color: var(--primary);
    background: rgba(139,26,26,.08);
    border-radius: .4rem; padding: .2em .55em;
}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    var URL_DATA   = '<?php echo e(route("admin.parivar.get_data")); ?>';
    var URL_DELETE = '<?php echo e(route("admin.parivar.delete")); ?>';
    var EDIT_BASE  = '<?php echo e(url("/admin/parivar")); ?>';
    var canEdit    = <?php echo e(auth()->user()->can('parivar-edit') ? 'true' : 'false'); ?>;
    var canDelete  = <?php echo e(auth()->user()->can('parivar-delete') ? 'true' : 'false'); ?>;
    var searchTimer;

    var dt = $('#membersTable').DataTable({
        processing : true,
        serverSide : false,
        ajax       : { url: URL_DATA, type: 'GET' },
        order      : [[1, 'desc']],
        pageLength : 10,
        scrollX    : true,
        dom        : 'rt<"dt-foot"lip>',
        columns: [
            {
                data: null, orderable: false, searchable: false, width: '50px',
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            {
                data: 'unique_id',
                render: function (val) {
                    return '<span class="uid-badge">' + esc(String(val)) + '</span>';
                }
            },
            {
                data: 'full_name',
                render: function (val, type, row) {
                    var sub = row.gu_full_name
                        ? '<div style="font-size:.75rem;color:var(--text-muted);font-family:\'Noto Sans Gujarati\',inherit;">' + esc(row.gu_full_name) + '</div>'
                        : '';
                    return '<div class="d-flex align-items-center gap-2">'
                         + (row.photo_url
                            ? '<img src="' + esc(row.photo_url) + '" alt="" style="width:34px;height:42px;object-fit:cover;border-radius:6px;flex-shrink:0;border:1px solid var(--border);">'
                            : '<div class="avatar-init" style="width:34px;height:34px;font-size:.75rem;flex-shrink:0;">' + esc(row.initials || '?') + '</div>')
                         + '<div><div style="font-size:.875rem;font-weight:600;color:var(--text);">' + esc(val) + '</div>' + sub + '</div>'
                         + '</div>';
                }
            },
            {
                data: 'aadhaar',
                render: function (val) {
                    return val
                        ? '<span style="font-family:ui-monospace,monospace;font-size:.8rem;color:var(--text-2);letter-spacing:.02em;">' + esc(val) + '</span>'
                        : '<span style="color:var(--text-muted);">—</span>';
                }
            },
            {
                data: 'phone',
                render: function (val) {
                    return val
                        ? '<span style="color:var(--text-2);">' + esc(val) + '</span>'
                        : '<span style="color:var(--text-muted);">—</span>';
                }
            },
            {
                data: 'gender_label',
                render: function (val) {
                    return val
                        ? '<span style="font-size:.82rem;color:var(--text-2);">' + esc(val) + '</span>'
                        : '<span style="color:var(--text-muted);">—</span>';
                }
            },
            {
                data: 'is_gurukul_student',
                render: function (val, type, row) {
                    var badge = val
                        ? '<span class="badge" style="background:rgba(25,135,84,.12);color:#198754;font-size:.73rem;padding:.32em .75em;border-radius:.35rem;">Student</span>'
                        : '<span class="badge" style="background:rgba(108,117,125,.12);color:#6c757d;font-size:.73rem;padding:.32em .75em;border-radius:.35rem;">—</span>';
                    var extra = row.gurukul_count
                        ? '<div style="font-size:.7rem;color:var(--text-muted);margin-top:.15rem;">' + row.gurukul_count + ' record' + (row.gurukul_count > 1 ? 's' : '') + '</div>'
                        : '';
                    return badge + extra;
                }
            },
            {
                data: 'created_by_name',
                render: function (val, type, row) {
                    return renderAudit(val, row.created, row.updated_by_name, row.updated_at_fmt);
                }
            },
            {
                data: null, orderable: false, searchable: false, className: 'text-center',
                render: function (data) {
                    var html = '<div class="d-flex gap-1 justify-content-center">';
                    if (canEdit) {
                        html += '<a href="' + EDIT_BASE + '/' + data.id + '/edit" class="btn btn-sm btn-icon btn-edit" style="width:30px;height:30px;border:1.5px solid var(--border);background:#fff;border-radius:var(--r-sm);color:var(--primary);display:inline-flex;align-items:center;justify-content:center;" title="Manage"><i class="bi bi-pencil-square" style="font-size:.85rem;"></i></a>';
                    }
                    if (canDelete) {
                        html += '<button class="btn btn-sm btn-icon btn-del" style="width:30px;height:30px;border:1.5px solid var(--border);background:#fff;border-radius:var(--r-sm);color:var(--accent);" data-id="' + data.id + '" data-name="' + esc(data.full_name) + '" title="Delete"><i class="bi bi-trash" style="font-size:.85rem;"></i></button>';
                    }
                    html += '</div>';
                    return html;
                }
            },
        ],
        language: {
            emptyTable    : '<div class="py-4 text-center text-muted"><i class="bi bi-inbox d-block mb-2" style="font-size:2rem;opacity:.4;"></i>No members yet. Add the first Parivar member.</div>',
            zeroRecords   : '<div class="py-4 text-center text-muted"><i class="bi bi-search d-block mb-2" style="font-size:1.6rem;opacity:.35;"></i>No matching members</div>',
            loadingRecords: 'Loading…',
            processing    : '<span style="color:var(--primary);">Loading…</span>',
            lengthMenu    : 'Show _MENU_',
            info          : 'Showing _START_–_END_ of _TOTAL_',
            infoEmpty     : 'No members',
            infoFiltered  : '(of _MAX_)',
            paginate: {
                first    : '<i class="bi bi-chevron-double-left"></i>',
                previous : '<i class="bi bi-chevron-left"></i>',
                next     : '<i class="bi bi-chevron-right"></i>',
                last     : '<i class="bi bi-chevron-double-right"></i>',
            },
        },
        drawCallback: function () {
            var api      = this.api();
            var total    = api.data().length;
            var filtered = api.rows({ search: 'applied' }).count();
            $('#memberCountBadge').text(filtered === total ? total + ' total' : filtered + ' / ' + total);
        },
    });

    $('#ucSearch').on('input', function () {
        clearTimeout(searchTimer);
        var val = this.value;
        searchTimer = setTimeout(function () { dt.search(val).draw(); }, 220);
    });

    $('#membersTable').on('click', '.btn-del', function () {
        var id   = $(this).data('id');
        var name = $(this).data('name');
        Swal.fire({
            title             : 'Delete "' + name + '"?',
            text              : 'Personal, business and gurukul records for this member will be removed.',
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
                data   : { _token: csrfToken, ids: [id], record_type: 'member' },
                success: function (r) {
                    if (r.success) {
                        dt.ajax.reload(null, false);
                        Swal.fire({ icon: 'success', title: 'Deleted!', text: r.message, timer: 1800, showConfirmButton: false });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: r.message });
                    }
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON || {}).message || 'Delete failed.';
                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                }
            });
        });
    });

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
        return $('<span>').text(str == null ? '' : String(str)).html();
    }
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\projects\Gurukul Parivar\resources\views/admin/parivar/index.blade.php ENDPATH**/ ?>