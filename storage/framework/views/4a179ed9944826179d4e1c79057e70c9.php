<?php $__env->startSection('title', 'Users'); ?>
<?php $__env->startSection('page-title', 'Users'); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item active">Users</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-actions'); ?>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('users-create')): ?>
        <button class="btn btn-primary btn-sm" id="btnAddUser">
            <i class="bi bi-person-plus"></i> Add User
        </button>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>


<div class="card uc-card">

    
    <div class="card-header uc-header">
        <span class="uc-left">
            <span class="card-header-icon"><i class="bi bi-people-fill"></i></span>
            <span class="uc-title">All Users</span>
            <span class="uc-count" id="userCountBadge">—</span>
        </span>
        <div class="uc-search-box">
            <i class="bi bi-search uc-search-ico"></i>
            <input type="text" id="ucSearch" class="uc-search-inp"
                   placeholder="Search users…" autocomplete="off">
        </div>
    </div>

    
    <table id="usersTable" class="table dt-table mb-0" style="width:100%;">
        <thead>
            <tr>
                <th style="width:55px;">#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Number</th>
                <th>Roles</th>
                <th style="width:90px;">Status</th>
                <th>Created By</th>
                <th class="text-center" style="width:100px;">Actions</th>
            </tr>
        </thead>
    </table>
</div>


<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:500px;">
        <div class="modal-content" style="border:none;border-radius:var(--r-lg);overflow:hidden;box-shadow:var(--sh-lg);">

            
            <div class="modal-header px-4 py-3"
                 style="background:linear-gradient(135deg,var(--primary) 0%,var(--primary-mid) 100%);border:none;">
                <h5 class="modal-title text-white mb-0" id="userModalLabel" style="font-weight:700;font-size:.97rem;">
                    <i class="bi bi-person-plus me-2"></i>Add User
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            
            <form id="userForm" novalidate autocomplete="off">
                <?php echo csrf_field(); ?>
                <input type="hidden" id="userId" name="id" value="">

                <div class="modal-body px-4 py-3">

                    
                    <div id="modalAlert" class="alert mb-3 d-none" role="alert" style="border-radius:var(--r-sm);font-size:.84rem;padding:.65rem .9rem;"></div>

                    <div class="mb-3">
                        <label class="form-label" for="uName">
                            Full Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="uName" name="name"
                               placeholder="Enter full name" autocomplete="off">
                        <div class="invalid-feedback" id="errName"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="uEmail">
                            Email Address <span class="text-danger">*</span>
                        </label>
                        <input type="email" class="form-control" id="uEmail" name="email"
                               placeholder="user@example.com" autocomplete="off">
                        <div class="invalid-feedback" id="errEmail"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="uNumber">
                            Phone Number <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="uNumber" name="number"
                               placeholder="e.g. +1 234 567 8900" autocomplete="off" maxlength="20">
                        <div class="invalid-feedback" id="errNumber"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="uPassword">
                            Password
                            <span class="text-danger" id="pwdStar">*</span>
                            <span class="text-muted" id="pwdHint" style="font-size:.76rem;font-weight:400;"
                                  >(leave blank to keep current)</span>
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="uPassword" name="password"
                                   placeholder="Min. 8 characters" autocomplete="new-password">
                            <button type="button" class="btn btn-light"
                                    style="border:1.5px solid var(--border);border-left:none;"
                                    onclick="toggleModalPwd('uPassword','eyePwd')" tabindex="-1">
                                <i class="bi bi-eye" id="eyePwd"></i>
                            </button>
                        </div>
                        <div class="text-danger mt-1" style="font-size:.77rem;" id="errPassword"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="uPasswordConfirm">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="uPasswordConfirm"
                                   name="password_confirmation" placeholder="Repeat password"
                                   autocomplete="new-password">
                            <button type="button" class="btn btn-light"
                                    style="border:1.5px solid var(--border);border-left:none;"
                                    onclick="toggleModalPwd('uPasswordConfirm','eyeConfirm')" tabindex="-1">
                                <i class="bi bi-eye" id="eyeConfirm"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Assign Roles</label>
                        <div class="border p-3" style="border-radius:var(--r-sm);border-color:var(--border)!important;max-height:150px;overflow-y:auto;background:var(--bg);">
                            <?php $__empty_1 = true; $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <div class="form-check mb-1">
                                    <input class="form-check-input role-chk" type="checkbox"
                                           name="roles[]" value="<?php echo e($role->name); ?>"
                                           id="rl_<?php echo e($role->id); ?>">
                                    <label class="form-check-label" for="rl_<?php echo e($role->id); ?>"
                                           style="font-size:.855rem;cursor:pointer;">
                                        <?php echo e($role->name); ?>

                                    </label>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <span class="text-muted small">No roles available.</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-1 d-flex align-items-center justify-content-between"
                         style="background:var(--bg);border:1.5px solid var(--border);border-radius:var(--r-sm);padding:.65rem .9rem;">
                        <div>
                            <div style="font-size:.875rem;font-weight:600;color:var(--text);">Active Status</div>
                            <div style="font-size:.77rem;color:var(--text-muted);">Inactive users cannot log in</div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="uIsActive" name="is_active" value="1"
                                   style="width:2.4em;height:1.3em;cursor:pointer;">
                        </div>
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
/* ── Card shell ─────────────────────────────────────────── */
.uc-card {
    border-radius: var(--r-lg);
    box-shadow: 0 2px 12px rgba(139,26,26,.07), 0 1px 3px rgba(0,0,0,.05);
    overflow: hidden;
}

/* ── Card header ────────────────────────────────────────── */
.uc-header {
    padding: .82rem 1.25rem !important;
    border-bottom: 1.5px solid var(--border-light) !important;
}
.uc-left {
    display: flex;
    align-items: center;
    gap: .55rem;
}
.uc-title {
    font-size: .9rem;
    font-weight: 700;
    color: var(--text);
}
.uc-count {
    background: rgba(139,26,26,.09);
    color: var(--primary);
    border-radius: 2rem;
    padding: .18em .72em;
    font-size: .71rem;
    font-weight: 700;
    letter-spacing: .2px;
}

/* ── Embedded search ────────────────────────────────────── */
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

/* ── Table ─────────────────────────────────────────────── */
#usersTable { border-collapse: collapse !important; }
#usersTable_wrapper { border: none !important; }
#usersTable .btn-edit:hover { background: rgba(139,26,26,.07) !important; border-color: var(--primary) !important; }
#usersTable .btn-del:hover  { background: rgba(192,57,43,.07)  !important; border-color: var(--accent)  !important; }

/* ── Footer bar: Show _MENU_ · info · pagination ────────── */
.dt-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: .5rem;
    padding: .7rem 1.25rem;
    background: #FAFAFA;
    border-top: 1.5px solid var(--border-light);
    border-radius: 0 0 var(--r-lg) var(--r-lg);
}
.dt-foot .dataTables_length { flex-shrink: 0; }
.dt-foot .dataTables_info   { flex: 1; text-align: center; }
.dt-foot .dataTables_paginate { flex-shrink: 0; }

/* ── Pagination: icon-only first/prev/next/last ──────────── */
#usersTable_paginate .paginate_button.first,
#usersTable_paginate .paginate_button.previous,
#usersTable_paginate .paginate_button.next,
#usersTable_paginate .paginate_button.last {
    width: 32px !important;
    height: 32px !important;
    min-width: 32px !important;
    padding: 0 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: .85rem !important;
    line-height: 1 !important;
}
#usersTable_paginate .paginate_button {
    vertical-align: middle !important;
}

/* ── Active switch colour ───────────────────────────────── */
#uIsActive:checked { background-color: var(--primary); border-color: var(--primary); }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {

    var URL_DATA   = '<?php echo e(route("admin.users.get_data")); ?>';
    var URL_SAVE   = '<?php echo e(route("admin.users.create")); ?>';
    var URL_DELETE = '<?php echo e(route("admin.users.delete")); ?>';
    var searchTimer;

    // ── DataTable ──────────────────────────────────────────────
    var dt = $('#usersTable').DataTable({
        processing : true,
        serverSide : false,
        ajax       : { url: URL_DATA, type: 'GET' },
        order      : [[1, 'asc']],
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
                data: 'name',
                render: function (val) {
                    var i = val.charAt(0).toUpperCase();
                    return '<div class="d-flex align-items-center gap-2">'
                         + '<div class="avatar-init" style="width:34px;height:34px;font-size:.8rem;flex-shrink:0;">' + i + '</div>'
                         + '<div style="font-size:.875rem;font-weight:600;color:var(--text);">' + esc(val) + '</div>'
                         + '</div>';
                }
            },
            {
                data: 'email',
                render: function (val) {
                    return '<span style="color:var(--text-2);">' + esc(val) + '</span>';
                }
            },
            {
                data: 'number',
                render: function (val) {
                    return val
                        ? '<span style="color:var(--text-2);">' + esc(val) + '</span>'
                        : '<span style="color:var(--text-muted);">—</span>';
                }
            },
            {
                data: 'roles',
                render: function (val) {
                    if (!val) return '<span style="font-size:.82rem;color:var(--text-muted);">—</span>';
                    return val.split(', ').map(function (r) {
                        return '<span class="badge me-1" style="background:rgba(139,26,26,.09);color:var(--primary);font-weight:600;font-size:.73rem;padding:.32em .65em;border-radius:.35rem;">' + esc(r) + '</span>';
                    }).join('');
                }
            },
            {
                data: 'is_active',
                render: function (val) {
                    return val
                        ? '<span class="badge" style="background:rgba(25,135,84,.12);color:#198754;font-size:.73rem;padding:.32em .75em;border-radius:.35rem;">Active</span>'
                        : '<span class="badge" style="background:rgba(108,117,125,.12);color:#6c757d;font-size:.73rem;padding:.32em .75em;border-radius:.35rem;">Inactive</span>';
                }
            },
            {
                data: 'created_by_name',
                render: function (val, type, row) {
                    return renderAudit(val, row.joined, row.updated_by_name, row.updated_at_fmt);
                }
            },
            {
                data      : null,
                orderable : false,
                searchable: false,
                className : 'text-center',
                render    : function (data) {
                    return '<div class="d-flex gap-1 justify-content-center">'
                         + '<button class="btn btn-sm btn-icon btn-edit" style="width:30px;height:30px;border:1.5px solid var(--border);background:#fff;border-radius:var(--r-sm);color:var(--primary);transition:all .18s;" data-row=\'' + JSON.stringify(data).replace(/'/g, "&#39;") + '\' title="Edit"><i class="bi bi-pencil-square" style="font-size:.85rem;"></i></button>'
                         + '<button class="btn btn-sm btn-icon btn-del" style="width:30px;height:30px;border:1.5px solid var(--border);background:#fff;border-radius:var(--r-sm);color:var(--accent);transition:all .18s;" data-id="' + data.id + '" data-name="' + esc(data.name) + '" title="Delete"><i class="bi bi-trash" style="font-size:.85rem;"></i></button>'
                         + '</div>';
                }
            },
        ],
        language: {
            emptyTable    : '<div class="py-4 text-center text-muted"><i class="bi bi-inbox d-block mb-2" style="font-size:2rem;opacity:.4;"></i>No users found</div>',
            zeroRecords   : '<div class="py-4 text-center text-muted"><i class="bi bi-search d-block mb-2" style="font-size:1.6rem;opacity:.35;"></i>No matching users</div>',
            loadingRecords: 'Loading…',
            processing    : '<span style="color:var(--primary);">Loading…</span>',
            lengthMenu    : 'Show _MENU_',
            info          : 'Showing _START_–_END_ of _TOTAL_',
            infoEmpty     : 'No users',
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
            $('#userCountBadge').text(total + ' total');
        },
        drawCallback: function () {
            var api      = this.api();
            var total    = api.data().length;
            var filtered = api.rows({ search: 'applied' }).count();
            $('#userCountBadge').text(
                filtered === total
                    ? total + ' total'
                    : filtered + ' / ' + total
            );
        },
    });

    // ── Wire header search to DataTable ───────────────────────
    $('#ucSearch').on('input', function () {
        clearTimeout(searchTimer);
        var val = this.value;
        searchTimer = setTimeout(function () { dt.search(val).draw(); }, 220);
    });

    // ── Open CREATE modal ──────────────────────────────────────
    $('#btnAddUser').on('click', function () {
        resetModal(false);
        $('#userModal').modal('show');
    });

    // ── Open EDIT modal ────────────────────────────────────────
    $('#usersTable').on('click', '.btn-edit', function () {
        var row = $(this).data('row');
        resetModal(true);
        $('#userId').val(row.id);
        $('#uName').val(row.name);
        $('#uEmail').val(row.email);
        $('#uNumber').val(row.number);
        $('#uIsActive').prop('checked', row.is_active);
        if (row.role_names && row.role_names.length) {
            row.role_names.forEach(function (r) {
                $('.role-chk[value="' + r + '"]').prop('checked', true);
            });
        }
        $('#userModal').modal('show');
    });

    // ── DELETE ─────────────────────────────────────────────────
    $('#usersTable').on('click', '.btn-del', function () {
        var id   = $(this).data('id');
        var name = $(this).data('name');
        Swal.fire({
            title             : 'Delete "' + name + '"?',
            text              : 'This action will soft-delete the user.',
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
    $('#userForm').on('submit', function (e) {
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
                    $('#userModal').modal('hide');
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
        $('#userForm')[0].reset();
        $('#userId').val('');
        $('#uIsActive').prop('checked', true);
        clearErrors();
        if (isEdit) {
            $('#userModalLabel').html('<i class="bi bi-pencil me-2"></i>Edit User');
            $('#pwdStar').hide();
            $('#pwdHint').show();
        } else {
            $('#userModalLabel').html('<i class="bi bi-person-plus me-2"></i>Add User');
            $('#pwdStar').show();
            $('#pwdHint').hide();
        }
        ['uPassword', 'uPasswordConfirm'].forEach(function (id) {
            document.getElementById(id).type = 'password';
        });
        $('#eyePwd, #eyeConfirm').attr('class', 'bi bi-eye');
    }

    function clearErrors() {
        $('#modalAlert').addClass('d-none').html('');
        $('.form-control').removeClass('is-invalid');
        $('#errName, #errEmail, #errNumber, #errPassword').text('');
    }

    function showAlert(type, msg) {
        var icon = type === 'danger' ? 'exclamation-circle-fill' : 'check-circle-fill';
        $('#modalAlert')
            .removeClass('d-none alert-danger alert-success alert-warning')
            .addClass('alert-' + type)
            .html('<i class="bi bi-' + icon + ' me-2"></i>' + msg);
    }

    function renderFieldErrors(errors) {
        var map = { name: '#errName', email: '#errEmail', number: '#errNumber', password: '#errPassword' };
        var count = 0;
        $.each(errors, function (field, msgs) {
            count++;
            if (map[field]) {
                $(map[field]).text(msgs[0]);
                var inputId = '#u' + field.charAt(0).toUpperCase() + field.slice(1);
                $(inputId).addClass('is-invalid');
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
        return $('<span>').text(str).html();
    }

})();

function toggleModalPwd(inputId, iconId) {
    var el   = document.getElementById(inputId);
    var icon = document.getElementById(iconId);
    if (el.type === 'password') {
        el.type        = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        el.type        = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\projects\serve\resources\views/admin/users/index.blade.php ENDPATH**/ ?>