@extends('admin.layouts.app')

@section('title', 'Relations')
@section('page-title', 'Relations')

@section('breadcrumb')
    <li class="breadcrumb-item active">Relations</li>
@endsection

@section('page-actions')
    @can('relations-create')
        <button class="btn btn-primary btn-sm" id="btnAddRelation">
            <i class="bi bi-plus-lg me-1"></i> Add Relation
        </button>
    @endcan
@endsection

@section('content')

<div class="card uc-card">
    <div class="card-header uc-header">
        <span class="uc-left">
            <span class="card-header-icon"><i class="bi bi-people"></i></span>
            <span class="uc-title">Family Relations</span>
            <span class="uc-count" id="rowCountBadge">—</span>
        </span>
        <div class="uc-search-box">
            <i class="bi bi-search uc-search-ico"></i>
            <input type="text" id="ucSearch" class="uc-search-inp" placeholder="Search relations…" autocomplete="off">
        </div>
    </div>

    <table id="relationsTable" class="table dt-table mb-0" style="width:100%;">
        <thead>
            <tr>
                <th style="width:50px;">#</th>
                <th>Relation</th>
                <th style="width:110px;">Status</th>
                <th>Created By</th>
                <th class="text-center" style="width:100px;">Actions</th>
            </tr>
        </thead>
    </table>
</div>

<div class="modal fade" id="relationModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
        <div class="modal-content" style="border:none;border-radius:var(--r-lg);overflow:hidden;box-shadow:var(--sh-lg);">
            <div class="modal-header px-4 py-3"
                 style="background:linear-gradient(135deg,var(--primary) 0%,var(--primary-mid) 100%);border:none;">
                <h5 class="modal-title text-white mb-0" id="relationModalLabel" style="font-weight:700;font-size:.97rem;">
                    <i class="bi bi-plus-lg me-2"></i>Add Relation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="relationForm" novalidate autocomplete="off">
                @csrf
                <input type="hidden" id="relationId" name="id" value="">
                <div class="modal-body px-4 py-3">
                    <div id="modalAlert" class="alert mb-3 d-none" role="alert"
                         style="border-radius:var(--r-sm);font-size:.84rem;padding:.65rem .9rem;"></div>
                    <div class="mb-3">
                        <label class="form-label" for="relationName">Relation name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="relationName" name="relation"
                               placeholder="e.g. Father, Son, Mother" maxlength="100">
                        <div class="invalid-feedback" id="errRelationName"></div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between"
                         style="background:var(--bg);border:1.5px solid var(--border);border-radius:var(--r-sm);padding:.65rem .9rem;">
                        <div>
                            <div style="font-size:.875rem;font-weight:600;">Active</div>
                            <div style="font-size:.77rem;color:var(--text-muted);">Inactive relations stay hidden for new entries</div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="relationActive" name="is_active" value="1"
                                   style="width:2.4em;height:1.3em;cursor:pointer;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer px-4 py-3" style="border-top:1px solid var(--border-light);">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn" style="min-width:90px;">
                        <span id="saveBtnText"><i class="bi bi-check-lg me-1"></i>Save</span>
                        <span id="saveBtnLoad" class="d-none"><span class="spinner-border spinner-border-sm me-1"></span>Saving…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.uc-card { border-radius: var(--r-lg); box-shadow: 0 2px 12px rgba(139,26,26,.07), 0 1px 3px rgba(0,0,0,.05); overflow: hidden; }
.uc-header { display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; padding: .82rem 1.25rem !important; border-bottom: 1.5px solid var(--border-light) !important; }
.uc-left { display: flex; align-items: center; gap: .55rem; }
.uc-title { font-size: .9rem; font-weight: 700; color: var(--text); }
.uc-count { background: rgba(139,26,26,.09); color: var(--primary); border-radius: 2rem; padding: .18em .72em; font-size: .71rem; font-weight: 700; }
.uc-search-box { position: relative; }
.uc-search-ico { position: absolute; left: .75rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: .78rem; pointer-events: none; }
.uc-search-inp { border: 1.5px solid var(--border); border-radius: 2rem; padding: .38rem 1rem .38rem 2.1rem; font-size: .82rem; font-family: inherit; color: var(--text); background: var(--bg); outline: none; width: 210px; transition: border-color .2s, box-shadow .2s, width .25s; }
.uc-search-inp:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(139,26,26,.09); width: 255px; }
#relationsTable { border-collapse: collapse !important; }
.dt-foot { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .5rem; padding: .7rem 1.25rem; background: #FAFAFA; border-top: 1.5px solid var(--border-light); border-radius: 0 0 var(--r-lg) var(--r-lg); }
#relationActive:checked { background-color: var(--primary); border-color: var(--primary); }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var URL_DATA   = '{{ route("admin.relations.get_data") }}';
    var URL_SAVE   = '{{ route("admin.relations.create") }}';
    var URL_DELETE = '{{ route("admin.relations.delete") }}';
    var canEdit    = {{ auth()->user()->can('relations-edit') ? 'true' : 'false' }};
    var canDelete  = {{ auth()->user()->can('relations-delete') ? 'true' : 'false' }};
    var searchTimer;

    var dt = $('#relationsTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: { url: URL_DATA, type: 'GET' },
        order: [[1, 'asc']],
        pageLength: 25,
        scrollX: true,
        dom: 'rt<"dt-foot"lip>',
        columns: [
            {
                data: null, orderable: false, searchable: false, width: '50px',
                render: function (d, t, r, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            {
                data: 'relation',
                render: function (val) {
                    return '<span style="font-weight:600;color:var(--text);">' + esc(val) + '</span>';
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
                    return renderAudit(val, row.created, row.updated_by_name, row.updated_at_fmt);
                }
            },
            {
                data: null, orderable: false, searchable: false, className: 'text-center',
                render: function (data) {
                    var html = '<div class="d-flex gap-1 justify-content-center">';
                    if (canEdit) {
                        html += '<button class="btn btn-sm btn-icon btn-edit" style="width:30px;height:30px;border:1.5px solid var(--border);background:#fff;border-radius:var(--r-sm);color:var(--primary);" data-row=\'' + JSON.stringify(data).replace(/'/g, "&#39;") + '\' title="Edit"><i class="bi bi-pencil-square"></i></button>';
                    }
                    if (canDelete) {
                        html += '<button class="btn btn-sm btn-icon btn-del" style="width:30px;height:30px;border:1.5px solid var(--border);background:#fff;border-radius:var(--r-sm);color:var(--accent);" data-id="' + data.id + '" data-name="' + esc(data.relation) + '" title="Delete"><i class="bi bi-trash"></i></button>';
                    }
                    return html + '</div>';
                }
            },
        ],
        language: {
            emptyTable: '<div class="py-4 text-center text-muted"><i class="bi bi-people d-block mb-2" style="font-size:2rem;opacity:.4;"></i>No relations yet</div>',
            zeroRecords: 'No matching relations',
            lengthMenu: 'Show _MENU_',
            info: 'Showing _START_–_END_ of _TOTAL_',
            infoEmpty: 'No relations',
            paginate: {
                first: '<i class="bi bi-chevron-double-left"></i>',
                previous: '<i class="bi bi-chevron-left"></i>',
                next: '<i class="bi bi-chevron-right"></i>',
                last: '<i class="bi bi-chevron-double-right"></i>'
            },
        },
        drawCallback: function () {
            var api = this.api();
            var total = api.data().length;
            var filtered = api.rows({ search: 'applied' }).count();
            $('#rowCountBadge').text(filtered === total ? total + ' total' : filtered + ' / ' + total);
        },
    });

    $('#ucSearch').on('input', function () {
        clearTimeout(searchTimer);
        var val = this.value;
        searchTimer = setTimeout(function () { dt.search(val).draw(); }, 220);
    });

    $('#btnAddRelation').on('click', function () {
        resetModal(false);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('relationModal')).show();
    });

    $('#relationsTable').on('click', '.btn-edit', function () {
        var row = $(this).data('row');
        resetModal(true);
        $('#relationId').val(row.id);
        $('#relationName').val(row.relation);
        $('#relationActive').prop('checked', !!row.is_active);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('relationModal')).show();
    });

    $('#relationsTable').on('click', '.btn-del', function () {
        var id = $(this).data('id');
        var name = $(this).data('name');
        Swal.fire({
            title: 'Delete "' + name + '"?',
            text: 'This relation will be removed from the list.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#8B1A1A',
            cancelButtonColor: '#9B7575',
            confirmButtonText: 'Yes, delete',
        }).then(function (res) {
            if (!res.isConfirmed) return;
            $.ajax({
                url: URL_DELETE,
                type: 'POST',
                data: { _token: csrfToken, ids: [id] },
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

    $('#relationForm').on('submit', function (e) {
        e.preventDefault();
        clearErrors();
        $('#saveBtnText').addClass('d-none');
        $('#saveBtnLoad').removeClass('d-none');
        $('#saveBtn').prop('disabled', true);
        $.ajax({
            url: URL_SAVE,
            type: 'POST',
            data: $(this).serialize(),
            success: function (r) {
                if (r.success) {
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('relationModal')).hide();
                    dt.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: 'Done!', text: r.message, timer: 1800, showConfirmButton: false });
                } else {
                    showAlert('danger', r.message || 'Something went wrong.');
                }
            },
            error: function (xhr) {
                var res = xhr.responseJSON || {};
                if (xhr.status === 422 && res.errors) {
                    renderFieldErrors(res.errors);
                } else {
                    showAlert('danger', res.message || 'An error occurred.');
                }
            },
            complete: function () {
                $('#saveBtnText').removeClass('d-none');
                $('#saveBtnLoad').addClass('d-none');
                $('#saveBtn').prop('disabled', false);
            }
        });
    });

    function resetModal(isEdit) {
        $('#relationForm')[0].reset();
        $('#relationId').val('');
        $('#relationActive').prop('checked', true);
        clearErrors();
        $('#relationModalLabel').html(isEdit
            ? '<i class="bi bi-pencil me-2"></i>Edit Relation'
            : '<i class="bi bi-plus-lg me-2"></i>Add Relation');
    }

    function clearErrors() {
        $('#modalAlert').addClass('d-none').html('');
        $('.form-control').removeClass('is-invalid');
        $('#errRelationName').text('');
    }

    function showAlert(type, msg) {
        $('#modalAlert').removeClass('d-none alert-danger alert-success').addClass('alert-' + type)
            .html('<i class="bi bi-exclamation-circle-fill me-2"></i>' + msg);
    }

    function renderFieldErrors(errors) {
        if (errors.relation) {
            $('#errRelationName').text(errors.relation[0]);
            $('#relationName').addClass('is-invalid');
        }
        showAlert('danger', 'Please correct the highlighted fields.');
    }

    function renderAudit(creator, date, updater, updatedDate) {
        var html = creator
            ? '<span style="font-size:.82rem;font-weight:600;color:var(--text);">' + esc(creator) + '</span>'
            : '<span style="font-size:.82rem;color:var(--text-muted);">—</span>';
        html += '<div style="font-size:.74rem;color:var(--text-muted);">' + esc(date) + '</div>';
        if (updater && updater !== creator) {
            html += '<div style="font-size:.72rem;color:var(--text-muted);margin-top:.1rem;"><i class="bi bi-pencil-fill" style="font-size:.6rem;opacity:.6;"></i> ' + esc(updater) + (updatedDate ? ' · ' + esc(updatedDate) : '') + '</div>';
        }
        return html;
    }

    function esc(str) {
        return $('<span>').text(str == null ? '' : String(str)).html();
    }
})();
</script>
@endpush
