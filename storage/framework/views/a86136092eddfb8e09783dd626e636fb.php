<?php $__env->startSection('title', 'Permissions'); ?>
<?php $__env->startSection('page-title', 'Permissions'); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item active">Permissions</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-actions'); ?>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('permissions-create')): ?>
        <button class="btn btn-primary btn-sm" id="btnAddPermission">
            <i class="bi bi-key-fill me-1"></i> Add Permission
        </button>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>


<div class="card uc-card">

    <div class="card-header uc-header">
        <span class="uc-left">
            <span class="card-header-icon"><i class="bi bi-key-fill"></i></span>
            <span class="uc-title">All Permissions</span>
            <span class="uc-count" id="permCountBadge">—</span>
        </span>
        <div class="uc-search-box">
            <i class="bi bi-search uc-search-ico"></i>
            <input type="text" id="ucSearch" class="uc-search-inp"
                   placeholder="Search permissions…" autocomplete="off">
        </div>
    </div>

    <table id="permissionsTable" class="table dt-table mb-0" style="width:100%;">
        <thead>
            <tr>
                <th style="width:55px;">#</th>
                <th style="width:160px;">Module</th>
                <th>Permission</th>
                <th style="width:100px;">Guard</th>
                <th style="width:150px;">Created By</th>
                <th class="text-center" style="width:100px;">Actions</th>
            </tr>
        </thead>
    </table>
</div>


<div class="modal fade" id="permissionModal" tabindex="-1" aria-labelledby="permissionModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content" style="border:none;border-radius:var(--r-lg);overflow:hidden;box-shadow:var(--sh-lg);">

            <div class="modal-header px-4 py-3"
                 style="background:linear-gradient(135deg,var(--primary) 0%,var(--primary-mid) 100%);border:none;">
                <h5 class="modal-title text-white mb-0" id="permissionModalLabel" style="font-weight:700;font-size:.97rem;">
                    <i class="bi bi-key-fill me-2"></i>Add Permission
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="permissionForm" novalidate autocomplete="off">
                <?php echo csrf_field(); ?>
                <input type="hidden" id="permissionId" name="id" value="">

                <div class="modal-body px-4 py-3">

                    <div id="modalAlert" class="alert mb-3 d-none" role="alert"
                         style="border-radius:var(--r-sm);font-size:.84rem;padding:.65rem .9rem;"></div>

                    
                    <div class="mb-3">
                        <label class="form-label" for="pModule">Module</label>
                        <select class="form-select" id="pModule" name="module_id">
                            <option value="">— No module —</option>
                            <?php $__currentLoopData = $modules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($module->id); ?>"
                                        data-slug="<?php echo e($module->name); ?>"
                                        data-icon="<?php echo e($module->icon); ?>">
                                    <?php echo e($module->title); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <div class="text-danger mt-1" style="font-size:.77rem;" id="errModule"></div>
                    </div>

                    
                    <div id="quickActions" class="mb-3 d-none">
                        <label class="form-label mb-1" style="font-size:.8rem;color:var(--text-muted);">Quick actions</label>
                        <div class="d-flex flex-wrap gap-1">
                            <?php $__currentLoopData = ['list','create','edit','delete','export']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <button type="button" class="btn btn-sm qa-btn"
                                        style="font-size:.75rem;padding:.28em .7em;border:1.5px solid var(--border);background:#fff;border-radius:.4rem;color:var(--text);transition:all .15s;"
                                        data-action="<?php echo e($action); ?>">
                                    <?php echo e(ucfirst($action)); ?>

                                </button>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>

                    
                    <div class="mb-3">
                        <label class="form-label" for="pName">
                            Permission Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="pName" name="name"
                               placeholder="e.g. users-list" autocomplete="off">
                        <div class="form-text" style="font-size:.76rem;color:var(--text-muted);">
                            Format: <code>module-action</code>
                        </div>
                        <div class="text-danger mt-1" style="font-size:.77rem;" id="errName"></div>
                    </div>

                    
                    <div class="mb-1">
                        <label class="form-label" for="pGuard">Guard Name</label>
                        <input type="text" class="form-control" id="pGuard" name="guard_name"
                               placeholder="web" autocomplete="off">
                        <div class="form-text" style="font-size:.76rem;color:var(--text-muted);">
                            Leave blank to use <code>web</code>.
                        </div>
                        <div class="text-danger mt-1" style="font-size:.77rem;" id="errGuard"></div>
                    </div>

                </div>

                <div class="modal-footer px-4 py-3" style="border-top:1px solid var(--border-light);">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn" style="min-width:90px;">
                        <span id="saveBtnText"><i class="bi bi-check-lg me-1"></i>Save</span>
                        <span id="saveBtnLoad" class="d-none">
                            <span class="spinner-border spinner-border-sm me-1"></span>Saving…
                        </span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
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
    background: rgba(139,26,26,.09);
    color: var(--primary);
    border-radius: 2rem;
    padding: .18em .72em;
    font-size: .71rem; font-weight: 700; letter-spacing: .2px;
}
.uc-search-box { position: relative; }
.uc-search-ico {
    position: absolute; left: .75rem; top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted); font-size: .78rem; pointer-events: none;
}
.uc-search-inp {
    border: 1.5px solid var(--border);
    border-radius: 2rem;
    padding: .38rem 1rem .38rem 2.1rem;
    font-size: .82rem; font-family: inherit;
    color: var(--text); background: var(--bg);
    outline: none; width: 210px;
    transition: border-color .2s, box-shadow .2s, width .25s;
}
.uc-search-inp:focus {
    border-color: var(--primary);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(139,26,26,.09);
    width: 255px;
}
.uc-search-inp::placeholder { color: var(--text-muted); }
#permissionsTable { border-collapse: collapse !important; }
#permissionsTable_wrapper { border: none !important; }
#permissionsTable .btn-edit:hover { background: rgba(139,26,26,.07) !important; border-color: var(--primary) !important; }
#permissionsTable .btn-del:hover  { background: rgba(192,57,43,.07)  !important; border-color: var(--accent)  !important; }
.dt-foot {
    display: flex; align-items: center;
    justify-content: space-between; flex-wrap: wrap;
    gap: .5rem; padding: .7rem 1.25rem;
    background: #FAFAFA;
    border-top: 1.5px solid var(--border-light);
    border-radius: 0 0 var(--r-lg) var(--r-lg);
}
.dt-foot .dataTables_length { flex-shrink: 0; }
.dt-foot .dataTables_info   { flex: 1; text-align: center; }
.dt-foot .dataTables_paginate { flex-shrink: 0; }
#permissionsTable_paginate .paginate_button.first,
#permissionsTable_paginate .paginate_button.previous,
#permissionsTable_paginate .paginate_button.next,
#permissionsTable_paginate .paginate_button.last {
    width: 32px !important; height: 32px !important; min-width: 32px !important;
    padding: 0 !important; display: inline-flex !important;
    align-items: center !important; justify-content: center !important;
    font-size: .85rem !important; line-height: 1 !important;
}
#permissionsTable_paginate .paginate_button { vertical-align: middle !important; }
.qa-btn:hover {
    background: rgba(139,26,26,.07) !important;
    border-color: var(--primary) !important;
    color: var(--primary) !important;
}
.qa-btn.active {
    background: var(--primary) !important;
    border-color: var(--primary) !important;
    color: #fff !important;
}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {

    var URL_DATA   = '<?php echo e(route("admin.permissions.get_data")); ?>';
    var URL_SAVE   = '<?php echo e(route("admin.permissions.create")); ?>';
    var URL_DELETE = '<?php echo e(route("admin.permissions.delete")); ?>';
    var searchTimer;

    // ── DataTable ──────────────────────────────────────────────
    var dt = $('#permissionsTable').DataTable({
        processing : true,
        serverSide : false,
        ajax       : { url: URL_DATA, type: 'GET' },
        order      : [[1, 'asc'], [2, 'asc']],
        pageLength : 10,
        scrollX    : true,
        dom        : 'rt<"dt-foot"lip>',
        columns: [
            {
                data      : null,
                orderable : false,
                searchable: false,
                width     : '55px',
                render    : function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            {
                data: 'module_title',
                render: function (val, type, row) {
                    if (!val) return '<span style="color:var(--text-muted);">—</span>';
                    var icon = row.module_icon || 'bi-box-seam';
                    var moduleBadge = '<span class="d-inline-flex align-items-center gap-1 badge" '
                         + 'style="background:rgba(139,26,26,.09);color:var(--primary);font-size:.75rem;'
                         + 'padding:.38em .75em;border-radius:.4rem;font-weight:600;">'
                         + '<i class="bi ' + esc(icon) + '"></i>' + esc(val) + '</span>';
                    var sectionLabel = row.section_name
                        ? '<div style="font-size:.7rem;color:var(--text-muted);margin-top:.2rem;">'
                          + '<i class="bi bi-collection" style="font-size:.6rem;"></i> ' + esc(row.section_name) + '</div>'
                        : '';
                    return moduleBadge + sectionLabel;
                }
            },
            {
                data: 'name',
                render: function (val, type, row) {
                    var action = val;
                    if (row.module_name) {
                        var prefix = row.module_name + '-';
                        if (val.indexOf(prefix) === 0) action = val.substring(prefix.length);
                    }
                    return '<span style="font-size:.875rem;font-weight:600;color:var(--text);">' + esc(action) + '</span>'
                         + '<span style="font-size:.75rem;color:var(--text-muted);margin-left:.35rem;">(' + esc(val) + ')</span>';
                }
            },
            {
                data: 'guard_name',
                render: function (val) {
                    return '<span class="badge" style="background:rgba(108,117,125,.12);color:#6c757d;font-size:.72rem;padding:.3em .65em;border-radius:.35rem;">' + esc(val) + '</span>';
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
                         + '<button class="btn btn-sm btn-icon btn-edit" style="width:30px;height:30px;border:1.5px solid var(--border);background:#fff;border-radius:var(--r-sm);color:var(--primary);transition:all .18s;" '
                         + 'data-row=\'' + JSON.stringify(data).replace(/'/g, "&#39;") + '\' title="Edit"><i class="bi bi-pencil-square" style="font-size:.85rem;"></i></button>'
                         + '<button class="btn btn-sm btn-icon btn-del" style="width:30px;height:30px;border:1.5px solid var(--border);background:#fff;border-radius:var(--r-sm);color:var(--accent);transition:all .18s;" '
                         + 'data-id="' + data.id + '" data-name="' + esc(data.name) + '" title="Delete"><i class="bi bi-trash" style="font-size:.85rem;"></i></button>'
                         + '</div>';
                }
            },
        ],
        language: {
            emptyTable    : '<div class="py-4 text-center text-muted"><i class="bi bi-inbox d-block mb-2" style="font-size:2rem;opacity:.4;"></i>No permissions found</div>',
            zeroRecords   : '<div class="py-4 text-center text-muted"><i class="bi bi-search d-block mb-2" style="font-size:1.6rem;opacity:.35;"></i>No matching permissions</div>',
            loadingRecords: 'Loading…',
            processing    : '<span style="color:var(--primary);">Loading…</span>',
            lengthMenu    : 'Show _MENU_',
            info          : 'Showing _START_–_END_ of _TOTAL_',
            infoEmpty     : 'No permissions',
            infoFiltered  : '(of _MAX_)',
            paginate: {
                first    : '<i class="bi bi-chevron-double-left"></i>',
                previous : '<i class="bi bi-chevron-left"></i>',
                next     : '<i class="bi bi-chevron-right"></i>',
                last     : '<i class="bi bi-chevron-double-right"></i>',
            },
        },
        initComplete: function () {
            var total = this.api().data().length;
            $('#permCountBadge').text(total + ' total');
        },
        drawCallback: function () {
            var api      = this.api();
            var total    = api.data().length;
            var filtered = api.rows({ search: 'applied' }).count();
            $('#permCountBadge').text(
                filtered === total ? total + ' total' : filtered + ' / ' + total
            );
        },
    });

    // ── Header search ──────────────────────────────────────────
    $('#ucSearch').on('input', function () {
        clearTimeout(searchTimer);
        var val = this.value;
        searchTimer = setTimeout(function () { dt.search(val).draw(); }, 220);
    });

    // ── Module dropdown → auto-suggest name prefix ─────────────
    $('#pModule').on('change', function () {
        var slug = $(this).find(':selected').data('slug') || '';
        if (slug) {
            $('#quickActions').removeClass('d-none');
            var current = $('#pName').val().trim();
            // Only auto-prefix if the field is empty or already has a module prefix
            if (!current || /^[a-z0-9_]+-[a-z]*$/.test(current)) {
                var action = current.includes('-') ? current.split('-').slice(1).join('-') : '';
                $('#pName').val(slug + '-' + action);
            }
        } else {
            $('#quickActions').addClass('d-none');
        }
        updateActiveAction();
    });

    // ── Quick action buttons ───────────────────────────────────
    $(document).on('click', '.qa-btn', function () {
        var action = $(this).data('action');
        var slug   = $('#pModule').find(':selected').data('slug') || '';
        if (slug) {
            $('#pName').val(slug + '-' + action);
        }
        updateActiveAction();
    });

    function updateActiveAction() {
        var name   = $('#pName').val();
        var slug   = $('#pModule').find(':selected').data('slug') || '';
        var action = slug && name.indexOf(slug + '-') === 0
            ? name.substring(slug.length + 1)
            : '';
        $('.qa-btn').each(function () {
            $(this).toggleClass('active', $(this).data('action') === action);
        });
    }

    // Track name changes to keep quick buttons in sync
    $('#pName').on('input', updateActiveAction);

    // ── Open CREATE modal ──────────────────────────────────────
    $('#btnAddPermission').on('click', function () {
        resetModal(false);
        $('#permissionModal').modal('show');
    });

    // ── Open EDIT modal ────────────────────────────────────────
    $('#permissionsTable').on('click', '.btn-edit', function () {
        var row = $(this).data('row');
        resetModal(true);
        $('#permissionId').val(row.id);
        $('#pName').val(row.name);
        $('#pGuard').val(row.guard_name);

        if (row.module_id) {
            $('#pModule').val(row.module_id).trigger('change');
        }
        updateActiveAction();
        $('#permissionModal').modal('show');
    });

    // ── DELETE ─────────────────────────────────────────────────
    $('#permissionsTable').on('click', '.btn-del', function () {
        var id   = $(this).data('id');
        var name = $(this).data('name');
        Swal.fire({
            title             : 'Delete "' + name + '"?',
            text              : 'This will permanently remove the permission.',
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
                    var msg = (xhr.responseJSON || {}).message || 'Delete failed.';
                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                }
            });
        });
    });

    // ── FORM SUBMIT ────────────────────────────────────────────
    $('#permissionForm').on('submit', function (e) {
        e.preventDefault();
        clearErrors();
        $('#saveBtnText').addClass('d-none');
        $('#saveBtnLoad').removeClass('d-none');
        $('#saveBtn').prop('disabled', true);

        $.ajax({
            url    : URL_SAVE,
            type   : 'POST',
            data   : $(this).serialize(),
            success: function (r) {
                if (r.success) {
                    $('#permissionModal').modal('hide');
                    dt.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: 'Done!', text: r.message, timer: 2200, showConfirmButton: false });
                } else {
                    showAlert('danger', r.message || 'Something went wrong.');
                }
            },
            error: function (xhr) {
                var res = xhr.responseJSON || {};
                if (xhr.status === 422 && res.errors) {
                    renderFieldErrors(res.errors);
                } else {
                    showAlert('danger', res.message || 'An error occurred. Please try again.');
                }
            },
            complete: function () {
                $('#saveBtnText').removeClass('d-none');
                $('#saveBtnLoad').addClass('d-none');
                $('#saveBtn').prop('disabled', false);
            }
        });
    });

    // ── Helpers ────────────────────────────────────────────────
    function resetModal(isEdit) {
        $('#permissionForm')[0].reset();
        $('#permissionId').val('');
        $('#pModule').val('');
        $('#quickActions').addClass('d-none');
        $('.qa-btn').removeClass('active');
        clearErrors();
        if (isEdit) {
            $('#permissionModalLabel').html('<i class="bi bi-pencil me-2"></i>Edit Permission');
        } else {
            $('#permissionModalLabel').html('<i class="bi bi-key-fill me-2"></i>Add Permission');
        }
    }

    function clearErrors() {
        $('#modalAlert').addClass('d-none').html('');
        $('.form-control, .form-select').removeClass('is-invalid');
        $('#errName, #errGuard, #errModule').text('');
    }

    function showAlert(type, msg) {
        var icon = type === 'danger' ? 'exclamation-circle-fill' : 'check-circle-fill';
        $('#modalAlert')
            .removeClass('d-none alert-danger alert-success alert-warning')
            .addClass('alert-' + type)
            .html('<i class="bi bi-' + icon + ' me-2"></i>' + msg);
    }

    function renderFieldErrors(errors) {
        var fieldMap = {
            name      : { err: '#errName',   inp: '#pName'   },
            guard_name: { err: '#errGuard',  inp: '#pGuard'  },
            module_id : { err: '#errModule', inp: '#pModule' },
        };
        var count = 0;
        $.each(errors, function (field, msgs) {
            count++;
            if (fieldMap[field]) {
                $(fieldMap[field].err).text(msgs[0]);
                $(fieldMap[field].inp).addClass('is-invalid');
            }
        });
        if (count) showAlert('danger', 'Please correct the highlighted fields.');
    }

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

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\projects\serve\resources\views/admin/permissions/index.blade.php ENDPATH**/ ?>