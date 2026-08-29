@extends('admin.layouts.app')

@section('title', 'Modules')
@section('page-title', 'Modules')

@section('breadcrumb')
    <li class="breadcrumb-item active">Modules</li>
@endsection

@section('page-actions')
    @can('modules-create')
        <button class="btn btn-primary btn-sm" id="btnAddModule">
            <i class="bi bi-box-seam me-1"></i> Add Module
        </button>
    @endcan
@endsection

@section('content')

{{-- ── Modules Table Card ───────────────────────────────────── --}}
<div class="card uc-card">

    <div class="card-header uc-header">
        <span class="uc-left">
            <span class="card-header-icon"><i class="bi bi-box-seam"></i></span>
            <span class="uc-title">All Modules</span>
            <span class="uc-count" id="modCountBadge">—</span>
        </span>
        <div class="uc-search-box">
            <i class="bi bi-search uc-search-ico"></i>
            <input type="text" id="ucSearch" class="uc-search-inp"
                   placeholder="Search modules…" autocomplete="off">
        </div>
    </div>

    <table id="modulesTable" class="table dt-table mb-0" style="width:100%;">
        <thead>
            <tr>
                <th style="width:50px;">#</th>
                <th>Module</th>
                <th style="width:140px;">Section</th>
                <th>Slug</th>
                <th>Controller</th>
                <th style="width:70px;" class="text-center">Order</th>
                <th style="width:90px;">Status</th>
                <th style="width:160px;">Created By</th>
                <th class="text-center" style="width:100px;">Actions</th>
            </tr>
        </thead>
    </table>
</div>

{{-- ════════════════════════════════════════════════════════════
     Create / Edit Module Modal
     ════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="moduleModal" tabindex="-1" aria-labelledby="moduleModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:680px;">
        <div class="modal-content" style="border:none;border-radius:var(--r-lg);overflow:hidden;box-shadow:var(--sh-lg);">

            <div class="modal-header px-4 py-3"
                 style="background:linear-gradient(135deg,var(--primary) 0%,var(--primary-mid) 100%);border:none;">
                <h5 class="modal-title text-white mb-0" id="moduleModalLabel" style="font-weight:700;font-size:.97rem;">
                    <i class="bi bi-box-seam me-2"></i>Add Module
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="moduleForm" novalidate autocomplete="off">
                @csrf
                <input type="hidden" id="moduleId" name="id" value="">

                <div class="modal-body px-4 py-3">

                    <div id="modalAlert" class="alert mb-3 d-none" role="alert"
                         style="border-radius:var(--r-sm);font-size:.84rem;padding:.65rem .9rem;"></div>

                    {{-- Title / Slug --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label" for="mTitle">
                                Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="mTitle" name="title"
                                   placeholder="e.g. Blog Posts" autocomplete="off">
                            <div class="text-danger mt-1" style="font-size:.77rem;" id="errTitle"></div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="mName">
                                Slug <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="mName" name="name"
                                   placeholder="e.g. blog-posts" autocomplete="off">
                            <div class="form-text" style="font-size:.75rem;color:var(--text-muted);">
                                Lowercase, hyphens only
                            </div>
                            <div class="text-danger mt-1" style="font-size:.77rem;" id="errName"></div>
                        </div>
                    </div>

                    {{-- Controller Class / Path --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label" for="mControllerClass">
                                Controller Class <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="mControllerClass" name="controller_class"
                                   placeholder="e.g. BlogPostsController" autocomplete="off">
                            <div class="text-danger mt-1" style="font-size:.77rem;" id="errControllerClass"></div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="mControllerPath">Controller Path</label>
                            <input type="text" class="form-control" id="mControllerPath" name="controller_path"
                                   placeholder="e.g. AccessControl" autocomplete="off">
                            <div class="form-text" style="font-size:.75rem;color:var(--text-muted);">
                                Subfolder under <code>Admin/</code>
                            </div>
                            <div class="text-danger mt-1" style="font-size:.77rem;" id="errControllerPath"></div>
                        </div>
                    </div>

                    {{-- Model Class / Table Name --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="mModelClass">Model Class</label>
                            <input type="text" class="form-control" id="mModelClass" name="model_class"
                                   placeholder="e.g. BlogPost" autocomplete="off">
                            <div class="text-danger mt-1" style="font-size:.77rem;" id="errModelClass"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="mTableName">Table Name</label>
                            <input type="text" class="form-control" id="mTableName" name="table_name"
                                   placeholder="e.g. blog_posts" autocomplete="off">
                            <div class="text-danger mt-1" style="font-size:.77rem;" id="errTableName"></div>
                        </div>
                    </div>

                    {{-- Section --}}
                    <div class="mb-3">
                        <label class="form-label" for="mSection">Section</label>
                        <select class="form-select" id="mSection" name="section_id">
                            <option value="">— No section —</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}">{{ $section->section_name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text" style="font-size:.75rem;color:var(--text-muted);">
                            Groups this module in the sidebar
                        </div>
                        <div class="text-danger mt-1" style="font-size:.77rem;" id="errSection"></div>
                    </div>

                    {{-- Icon / Sort Order --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label" for="mIcon">Icon</label>
                            <div class="input-group">
                                <span class="input-group-text"
                                      style="width:40px;justify-content:center;background:var(--bg);border-color:var(--border);">
                                    <i id="iconPreview" class="bi bi-box-seam"
                                       style="font-size:1rem;color:var(--primary);"></i>
                                </span>
                                <input type="text" class="form-control" id="mIcon" name="icon"
                                       placeholder="bi-box-seam" autocomplete="off">
                            </div>
                            <div class="form-text" style="font-size:.75rem;color:var(--text-muted);">
                                Bootstrap icon class
                            </div>
                            <div class="text-danger mt-1" style="font-size:.77rem;" id="errIcon"></div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="mSortOrder">Sort Order</label>
                            <input type="number" class="form-control" id="mSortOrder" name="sort_order"
                                   placeholder="0" min="0" autocomplete="off">
                            <div class="form-text" style="font-size:.75rem;color:var(--text-muted);">
                                Lower = appears first
                            </div>
                            <div class="text-danger mt-1" style="font-size:.77rem;" id="errSortOrder"></div>
                        </div>
                    </div>

                    {{-- Switches --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            {{-- hidden ensures "0" is always submitted when checkbox is unchecked --}}
                            <input type="hidden" name="is_admin" value="0">
                            <div class="d-flex align-items-center justify-content-between"
                                 style="background:var(--bg);border:1.5px solid var(--border);border-radius:var(--r-sm);padding:.6rem .9rem;">
                                <div>
                                    <div style="font-size:.855rem;font-weight:600;color:var(--text);">Admin Panel</div>
                                    <div style="font-size:.75rem;color:var(--text-muted);">Show in admin sidebar</div>
                                </div>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="mIsAdmin" name="is_admin" value="1"
                                           style="width:2.4em;height:1.3em;cursor:pointer;">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            {{-- hidden ensures "0" is always submitted when checkbox is unchecked --}}
                            <input type="hidden" name="is_active" value="0">
                            <div class="d-flex align-items-center justify-content-between"
                                 style="background:var(--bg);border:1.5px solid var(--border);border-radius:var(--r-sm);padding:.6rem .9rem;">
                                <div>
                                    <div style="font-size:.855rem;font-weight:600;color:var(--text);">Active Status</div>
                                    <div style="font-size:.75rem;color:var(--text-muted);">Enable this module</div>
                                </div>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="mIsActive" name="is_active" value="1"
                                           style="width:2.4em;height:1.3em;cursor:pointer;">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Create-only note --}}
                    <div id="createNote"
                         style="background:rgba(139,26,26,.05);border:1px solid rgba(139,26,26,.18);border-radius:var(--r-sm);padding:.6rem .9rem;font-size:.79rem;color:var(--primary);">
                        <i class="bi bi-info-circle me-1"></i>
                        Default permissions <strong>list, create, edit, delete</strong> will be created automatically.
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

@endsection

@push('styles')
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
#modulesTable { border-collapse: collapse !important; }
#modulesTable_wrapper { border: none !important; }
#modulesTable .btn-edit:hover { background: rgba(139,26,26,.07) !important; border-color: var(--primary) !important; }
#modulesTable .btn-del:hover  { background: rgba(192,57,43,.07)  !important; border-color: var(--accent)  !important; }
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
#modulesTable_paginate .paginate_button.first,
#modulesTable_paginate .paginate_button.previous,
#modulesTable_paginate .paginate_button.next,
#modulesTable_paginate .paginate_button.last {
    width: 32px !important; height: 32px !important; min-width: 32px !important;
    padding: 0 !important; display: inline-flex !important;
    align-items: center !important; justify-content: center !important;
    font-size: .85rem !important; line-height: 1 !important;
}
#modulesTable_paginate .paginate_button { vertical-align: middle !important; }
#mIsAdmin:checked,
#mIsActive:checked { background-color: var(--primary); border-color: var(--primary); }
</style>
@endpush

@push('scripts')
<script>
(function () {

    var URL_DATA   = '{{ route("admin.modules.get_data") }}';
    var URL_SAVE   = '{{ route("admin.modules.create") }}';
    var URL_DELETE = '{{ route("admin.modules.delete") }}';
    var searchTimer;

    // ── DataTable ──────────────────────────────────────────────
    var dt = $('#modulesTable').DataTable({
        processing : true,
        serverSide : false,
        ajax       : { url: URL_DATA, type: 'GET' },
        order      : [[5, 'asc'], [1, 'asc']],
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
                data: 'title',
                render: function (val, type, row) {
                    var icon     = row.icon || 'bi-box-seam';
                    var adminBadge = row.is_admin
                        ? '<span class="badge ms-1" style="background:rgba(201,162,39,.15);color:#a07c10;font-size:.65rem;padding:.22em .55em;border-radius:.3rem;vertical-align:middle;">Admin</span>'
                        : '';
                    return '<div class="d-flex align-items-center gap-2">'
                         + '<div style="width:34px;height:34px;background:rgba(139,26,26,.09);border-radius:var(--r-sm);'
                         + 'display:flex;align-items:center;justify-content:center;flex-shrink:0;">'
                         + '<i class="bi ' + esc(icon) + '" style="color:var(--primary);font-size:1rem;"></i></div>'
                         + '<span style="font-size:.875rem;font-weight:600;color:var(--text);">' + esc(val) + adminBadge + '</span>'
                         + '</div>';
                }
            },
            {
                data: 'section_name',
                render: function (val) {
                    return val
                        ? '<span class="badge" style="background:rgba(139,26,26,.09);color:var(--primary);font-size:.73rem;padding:.32em .75em;border-radius:.35rem;">'
                          + '<i class="bi bi-collection me-1" style="font-size:.65rem;"></i>' + esc(val) + '</span>'
                        : '<span style="color:var(--text-muted);font-size:.8rem;">—</span>';
                }
            },
            {
                data: 'name',
                render: function (val) {
                    return '<code style="font-size:.8rem;background:rgba(0,0,0,.04);color:var(--text);'
                         + 'padding:.22em .55em;border-radius:.35rem;">' + esc(val) + '</code>';
                }
            },
            {
                data: 'controller_class',
                render: function (val, type, row) {
                    var path = row.controller_path ? esc(row.controller_path) + '\\' : '';
                    return '<code style="font-size:.78rem;background:rgba(0,0,0,.04);color:var(--text-2);">'
                         + path + esc(val) + '</code>';
                }
            },
            {
                data: 'sort_order',
                className: 'text-center',
                render: function (val) {
                    return '<span style="font-size:.82rem;color:var(--text-muted);">' + val + '</span>';
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
                data: null, orderable: false, searchable: false,
                render: function (data) { return renderAudit(data); }
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
                         + 'data-id="' + data.id + '" data-name="' + esc(data.title) + '" title="Delete"><i class="bi bi-trash" style="font-size:.85rem;"></i></button>'
                         + '</div>';
                }
            },
        ],
        language: {
            emptyTable    : '<div class="py-4 text-center text-muted"><i class="bi bi-inbox d-block mb-2" style="font-size:2rem;opacity:.4;"></i>No modules found</div>',
            zeroRecords   : '<div class="py-4 text-center text-muted"><i class="bi bi-search d-block mb-2" style="font-size:1.6rem;opacity:.35;"></i>No matching modules</div>',
            loadingRecords: 'Loading…',
            processing    : '<span style="color:var(--primary);">Loading…</span>',
            lengthMenu    : 'Show _MENU_',
            info          : 'Showing _START_–_END_ of _TOTAL_',
            infoEmpty     : 'No modules',
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
            $('#modCountBadge').text(total + ' total');
        },
        drawCallback: function () {
            var api      = this.api();
            var total    = api.data().length;
            var filtered = api.rows({ search: 'applied' }).count();
            $('#modCountBadge').text(
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

    // ── Auto-suggest from Title ────────────────────────────────
    $('#mTitle').on('input', function () {
        var val = this.value;
        if (!$('#mName').data('edited'))
            $('#mName').val(toSlug(val));
        if (!$('#mControllerClass').data('edited'))
            $('#mControllerClass').val(toPascal(val) + 'Controller');
        if (!$('#mModelClass').data('edited'))
            $('#mModelClass').val(toPascal(val));
        if (!$('#mTableName').data('edited'))
            $('#mTableName').val(toSnake(val));
    });

    // Mark field as manually edited so auto-suggest stops
    ['#mName','#mControllerClass','#mModelClass','#mTableName'].forEach(function (sel) {
        $(sel).on('input', function () { $(this).data('edited', true); });
    });

    // ── Icon live preview ──────────────────────────────────────
    $('#mIcon').on('input', function () {
        var cls = this.value.trim() || 'bi-box-seam';
        $('#iconPreview').attr('class', 'bi ' + cls);
    });

    // ── Open CREATE modal ──────────────────────────────────────
    $('#btnAddModule').on('click', function () {
        resetModal(false);
        $('#moduleModal').modal('show');
    });

    // ── Open EDIT modal ────────────────────────────────────────
    $('#modulesTable').on('click', '.btn-edit', function () {
        var row = $(this).data('row');
        resetModal(true);
        // Mark all auto-suggest fields as edited to prevent overwrite
        ['#mName','#mControllerClass','#mModelClass','#mTableName'].forEach(function (sel) {
            $(sel).data('edited', true);
        });
        $('#moduleId').val(row.id);
        $('#mTitle').val(row.title);
        $('#mName').val(row.name);
        $('#mControllerClass').val(row.controller_class || '');
        $('#mControllerPath').val(row.controller_path || '');
        $('#mModelClass').val(row.model_class || '');
        $('#mTableName').val(row.table_name || '');
        $('#mSection').val(row.section_id || '');
        $('#mIcon').val(row.icon);
        $('#mSortOrder').val(row.sort_order);
        $('#mIsAdmin').prop('checked', row.is_admin);
        $('#mIsActive').prop('checked', row.is_active);
        $('#iconPreview').attr('class', 'bi ' + (row.icon || 'bi-box-seam'));
        $('#moduleModal').modal('show');
    });

    // ── DELETE ─────────────────────────────────────────────────
    $('#modulesTable').on('click', '.btn-del', function () {
        var id   = $(this).data('id');
        var name = $(this).data('name');
        Swal.fire({
            title             : 'Delete "' + name + '"?',
            text              : 'This will delete the module and its associated permissions.',
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

    // ── FORM SUBMIT ────────────────────────────────────────────
    $('#moduleForm').on('submit', function (e) {
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
                    $('#moduleModal').modal('hide');
                    dt.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: 'Done!', text: r.message, timer: 2400, showConfirmButton: false });
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
        $('#moduleForm')[0].reset();
        $('#moduleId').val('');
        $('#mSection').val('');
        $('#iconPreview').attr('class', 'bi bi-box-seam');
        $('#mIsAdmin').prop('checked', true);
        $('#mIsActive').prop('checked', true);
        // Clear manual-edit flags so auto-suggest kicks in for new entries
        ['#mName','#mControllerClass','#mModelClass','#mTableName'].forEach(function (sel) {
            $(sel).removeData('edited');
        });
        clearErrors();
        if (isEdit) {
            $('#moduleModalLabel').html('<i class="bi bi-pencil me-2"></i>Edit Module');
            $('#createNote').hide();
        } else {
            $('#moduleModalLabel').html('<i class="bi bi-box-seam me-2"></i>Add Module');
            $('#createNote').show();
        }
    }

    function clearErrors() {
        $('#modalAlert').addClass('d-none').html('');
        $('.form-control').removeClass('is-invalid');
        $('#errTitle,#errName,#errControllerClass,#errControllerPath,#errModelClass,#errTableName,#errIcon,#errSortOrder,#errSection').text('');
    }

    function showAlert(type, msg) {
        var icon = type === 'danger' ? 'exclamation-circle-fill' : 'check-circle-fill';
        $('#modalAlert')
            .removeClass('d-none alert-danger alert-success alert-warning')
            .addClass('alert-' + type)
            .html('<i class="bi bi-' + icon + ' me-2"></i>' + msg);
    }

    function renderFieldErrors(errors) {
        var map = {
            title            : { err: '#errTitle',            inp: '#mTitle'           },
            name             : { err: '#errName',             inp: '#mName'            },
            controller_class : { err: '#errControllerClass',  inp: '#mControllerClass' },
            controller_path  : { err: '#errControllerPath',   inp: '#mControllerPath'  },
            model_class      : { err: '#errModelClass',       inp: '#mModelClass'      },
            table_name       : { err: '#errTableName',        inp: '#mTableName'       },
            icon             : { err: '#errIcon',             inp: '#mIcon'            },
            sort_order       : { err: '#errSortOrder',        inp: '#mSortOrder'       },
            section_id       : { err: '#errSection',          inp: '#mSection'         },
        };
        var count = 0;
        $.each(errors, function (field, msgs) {
            count++;
            if (map[field]) {
                $(map[field].err).text(msgs[0]);
                $(map[field].inp).addClass('is-invalid');
            }
        });
        if (count) showAlert('danger', 'Please correct the highlighted fields.');
    }

    function toSlug(str) {
        return str.toLowerCase()
            .replace(/\s+/g, '-')
            .replace(/[^a-z0-9-]/g, '')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    }

    function toPascal(str) {
        return str.split(/\s+/)
            .filter(Boolean)
            .map(function (w) { return w.charAt(0).toUpperCase() + w.slice(1).toLowerCase(); })
            .join('');
    }

    function toSnake(str) {
        return str.toLowerCase()
            .replace(/\s+/g, '_')
            .replace(/[^a-z0-9_]/g, '')
            .replace(/_+/g, '_')
            .replace(/^_|_$/g, '');
    }

    function renderAudit(row) {
        var byName  = row.created_by_name ? esc(row.created_by_name) : '<span style="color:var(--text-muted);">—</span>';
        var date    = row.created ? '<div style="font-size:.75rem;color:var(--text-muted);">' + esc(row.created) + '</div>' : '';
        var updLine = row.updated_by_name
            ? '<div style="font-size:.73rem;color:var(--text-muted);margin-top:.1rem;">Upd: ' + esc(row.updated_by_name)
              + (row.updated_at_fmt ? ' · ' + esc(row.updated_at_fmt) : '') + '</div>'
            : '';
        return '<div style="font-size:.82rem;font-weight:600;color:var(--text);">' + byName + '</div>' + date + updLine;
    }

    function esc(str) {
        return $('<span>').text(String(str || '')).html();
    }

})();
</script>
@endpush
