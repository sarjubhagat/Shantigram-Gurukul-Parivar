@extends('admin.layouts.app')

@section('title', 'Sections')
@section('page-title', 'Sections')

@section('breadcrumb')
    <li class="breadcrumb-item active">Sections</li>
@endsection

@section('page-actions')
    @can('sections-create')
        <button class="btn btn-primary btn-sm" id="btnAddSection">
            <i class="bi bi-collection me-1"></i> Add Section
        </button>
    @endcan
@endsection

@section('content')

<div class="card uc-card">

    <div class="card-header uc-header">
        <span class="uc-left">
            <span class="card-header-icon"><i class="bi bi-collection"></i></span>
            <span class="uc-title">All Sections</span>
            <span class="uc-count" id="secCountBadge">—</span>
        </span>
        <div class="uc-search-box">
            <i class="bi bi-search uc-search-ico"></i>
            <input type="text" id="ucSearch" class="uc-search-inp"
                   placeholder="Search sections…" autocomplete="off">
        </div>
    </div>

    <table id="sectionsTable" class="table dt-table mb-0" style="width:100%;">
        <thead>
            <tr>
                <th style="width:50px;">#</th>
                <th>Section Name</th>
                <th style="width:90px;" class="text-center">Icon</th>
                <th style="width:100px;" class="text-center">Order</th>
                <th style="width:120px;" class="text-center">Modules</th>
                <th style="width:160px;">Created By</th>
                <th class="text-center" style="width:100px;">Actions</th>
            </tr>
        </thead>
    </table>
</div>

{{-- Create / Edit Section Modal --}}
<div class="modal fade" id="sectionModal" tabindex="-1" aria-labelledby="sectionModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
        <div class="modal-content" style="border:none;border-radius:var(--r-lg);overflow:hidden;box-shadow:var(--sh-lg);">

            <div class="modal-header px-4 py-3"
                 style="background:linear-gradient(135deg,var(--primary) 0%,var(--primary-mid) 100%);border:none;">
                <h5 class="modal-title text-white mb-0" id="sectionModalLabel" style="font-weight:700;font-size:.97rem;">
                    <i class="bi bi-collection me-2"></i>Add Section
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form id="sectionForm" novalidate autocomplete="off">
                @csrf
                <input type="hidden" id="sectionId" name="id" value="">

                <div class="modal-body px-4 py-3">

                    <div id="modalAlert" class="alert mb-3 d-none" role="alert"
                         style="border-radius:var(--r-sm);font-size:.84rem;padding:.65rem .9rem;"></div>

                    <div class="mb-3">
                        <label class="form-label" for="sSectionName">
                            Section Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="sSectionName" name="section_name"
                               placeholder="e.g. Access Control" autocomplete="off">
                        <div class="text-danger mt-1" style="font-size:.77rem;" id="errSectionName"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="sIcon">Icon</label>
                        <div class="input-group">
                            <span class="input-group-text"
                                  style="width:40px;justify-content:center;background:var(--bg);border-color:var(--border);">
                                <i id="sIconPreview" class="bi bi-collection"
                                   style="font-size:1rem;color:var(--primary);"></i>
                            </span>
                            <input type="text" class="form-control" id="sIcon" name="icon"
                                   placeholder="bi-collection" autocomplete="off">
                        </div>
                        <div class="form-text" style="font-size:.75rem;color:var(--text-muted);">
                            Bootstrap Icons class (shown in sidebar section header)
                        </div>
                        <div class="text-danger mt-1" style="font-size:.77rem;" id="errIcon"></div>
                    </div>

                    <div class="mb-1">
                        <label class="form-label" for="sOrder">Sort Order</label>
                        <input type="number" class="form-control" id="sOrder" name="order"
                               placeholder="0" min="0" autocomplete="off">
                        <div class="form-text" style="font-size:.75rem;color:var(--text-muted);">
                            Lower number = appears first in sidebar
                        </div>
                        <div class="text-danger mt-1" style="font-size:.77rem;" id="errOrder"></div>
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
#sectionsTable { border-collapse: collapse !important; }
#sectionsTable_wrapper { border: none !important; }
#sectionsTable .btn-edit:hover { background: rgba(139,26,26,.07) !important; border-color: var(--primary) !important; }
#sectionsTable .btn-del:hover  { background: rgba(192,57,43,.07)  !important; border-color: var(--accent)  !important; }
.dt-foot {
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap;
    gap: .5rem; padding: .7rem 1.25rem;
    background: #FAFAFA;
    border-top: 1.5px solid var(--border-light);
    border-radius: 0 0 var(--r-lg) var(--r-lg);
}
.dt-foot .dataTables_length { flex-shrink: 0; }
.dt-foot .dataTables_info   { flex: 1; text-align: center; }
.dt-foot .dataTables_paginate { flex-shrink: 0; }
#sectionsTable_paginate .paginate_button.first,
#sectionsTable_paginate .paginate_button.previous,
#sectionsTable_paginate .paginate_button.next,
#sectionsTable_paginate .paginate_button.last {
    width: 32px !important; height: 32px !important; min-width: 32px !important;
    padding: 0 !important; display: inline-flex !important;
    align-items: center !important; justify-content: center !important;
    font-size: .85rem !important; line-height: 1 !important;
}
#sectionsTable_paginate .paginate_button { vertical-align: middle !important; }
</style>
@endpush

@push('scripts')
<script>
(function () {

    var URL_DATA   = '{{ route("admin.sections.get_data") }}';
    var URL_SAVE   = '{{ route("admin.sections.create") }}';
    var URL_DELETE = '{{ route("admin.sections.delete") }}';
    var searchTimer;

    var dt = $('#sectionsTable').DataTable({
        processing : true,
        serverSide : false,
        ajax       : { url: URL_DATA, type: 'GET' },
        order      : [[3, 'asc'], [1, 'asc']],
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
                data: 'section_name',
                render: function (val, type, row) {
                    var icon = row.icon || 'bi-collection';
                    return '<div class="d-flex align-items-center gap-2">'
                         + '<div style="width:34px;height:34px;background:rgba(139,26,26,.09);border-radius:var(--r-sm);'
                         + 'display:flex;align-items:center;justify-content:center;flex-shrink:0;">'
                         + '<i class="bi ' + esc(icon) + '" style="color:var(--primary);font-size:1rem;"></i></div>'
                         + '<span style="font-size:.875rem;font-weight:600;color:var(--text);">' + esc(val) + '</span>'
                         + '</div>';
                }
            },
            {
                data: 'icon', className: 'text-center', orderable: false,
                render: function (val) {
                    var icon = val || 'bi-collection';
                    return '<i class="bi ' + esc(icon) + '" style="font-size:1.15rem;color:var(--primary);" title="' + esc(icon) + '"></i>';
                }
            },
            {
                data: 'order', className: 'text-center',
                render: function (val) {
                    return '<span style="font-size:.82rem;color:var(--text-muted);">' + val + '</span>';
                }
            },
            {
                data: 'modules_count', className: 'text-center',
                render: function (val) {
                    return val > 0
                        ? '<span class="badge" style="background:rgba(139,26,26,.09);color:var(--primary);font-size:.73rem;padding:.32em .75em;border-radius:.35rem;">' + val + ' module' + (val !== 1 ? 's' : '') + '</span>'
                        : '<span style="color:var(--text-muted);font-size:.82rem;">—</span>';
                }
            },
            {
                data: null, orderable: false, searchable: false,
                render: function (data) { return renderAudit(data); }
            },
            {
                data: null, orderable: false, searchable: false, className: 'text-center',
                render: function (data) {
                    return '<div class="d-flex gap-1 justify-content-center">'
                         + '<button class="btn btn-sm btn-icon btn-edit" style="width:30px;height:30px;border:1.5px solid var(--border);background:#fff;border-radius:var(--r-sm);color:var(--primary);transition:all .18s;" '
                         + 'data-row=\'' + JSON.stringify(data).replace(/'/g, "&#39;") + '\' title="Edit"><i class="bi bi-pencil-square" style="font-size:.85rem;"></i></button>'
                         + '<button class="btn btn-sm btn-icon btn-del" style="width:30px;height:30px;border:1.5px solid var(--border);background:#fff;border-radius:var(--r-sm);color:var(--accent);transition:all .18s;" '
                         + 'data-id="' + data.id + '" data-name="' + esc(data.section_name) + '" title="Delete"><i class="bi bi-trash" style="font-size:.85rem;"></i></button>'
                         + '</div>';
                }
            },
        ],
        language: {
            emptyTable    : '<div class="py-4 text-center text-muted"><i class="bi bi-inbox d-block mb-2" style="font-size:2rem;opacity:.4;"></i>No sections found</div>',
            zeroRecords   : '<div class="py-4 text-center text-muted"><i class="bi bi-search d-block mb-2" style="font-size:1.6rem;opacity:.35;"></i>No matching sections</div>',
            loadingRecords: 'Loading…',
            processing    : '<span style="color:var(--primary);">Loading…</span>',
            lengthMenu    : 'Show _MENU_',
            info          : 'Showing _START_–_END_ of _TOTAL_',
            infoEmpty     : 'No sections',
            infoFiltered  : '(of _MAX_)',
            paginate: {
                first    : '<i class="bi bi-chevron-double-left"></i>',
                previous : '<i class="bi bi-chevron-left"></i>',
                next     : '<i class="bi bi-chevron-right"></i>',
                last     : '<i class="bi bi-chevron-double-right"></i>',
            },
        },
        initComplete: function () { $('#secCountBadge').text(this.api().data().length + ' total'); },
        drawCallback: function () {
            var api = this.api(), total = api.data().length, filtered = api.rows({ search: 'applied' }).count();
            $('#secCountBadge').text(filtered === total ? total + ' total' : filtered + ' / ' + total);
        },
    });

    $('#ucSearch').on('input', function () {
        clearTimeout(searchTimer);
        var val = this.value;
        searchTimer = setTimeout(function () { dt.search(val).draw(); }, 220);
    });

    $('#btnAddSection').on('click', function () {
        resetModal(false);
        $('#sectionModal').modal('show');
    });

    $('#sIcon').on('input', function () {
        var cls = ($(this).val() || 'bi-collection').trim().replace(/^bi\s+/, '');
        $('#sIconPreview').attr('class', 'bi ' + cls);
    });

    $('#sectionsTable').on('click', '.btn-edit', function () {
        var row = $(this).data('row');
        resetModal(true);
        $('#sectionId').val(row.id);
        $('#sSectionName').val(row.section_name);
        $('#sIcon').val(row.icon || 'bi-collection');
        $('#sIconPreview').attr('class', 'bi ' + (row.icon || 'bi-collection'));
        $('#sOrder').val(row.order);
        $('#sectionModal').modal('show');
    });

    $('#sectionsTable').on('click', '.btn-del', function () {
        var id   = $(this).data('id');
        var name = $(this).data('name');
        Swal.fire({
            title             : 'Delete "' + name + '"?',
            text              : 'Modules in this section will become ungrouped.',
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

    $('#sectionForm').on('submit', function (e) {
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
                    $('#sectionModal').modal('hide');
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

    function resetModal(isEdit) {
        $('#sectionForm')[0].reset();
        $('#sectionId').val('');
        clearErrors();
        $('#sectionModalLabel').html(
            isEdit
                ? '<i class="bi bi-pencil me-2"></i>Edit Section'
                : '<i class="bi bi-collection me-2"></i>Add Section'
        );
        $('#sIconPreview').attr('class', 'bi bi-collection');
    }

    function clearErrors() {
        $('#modalAlert').addClass('d-none').html('');
        $('.form-control').removeClass('is-invalid');
        $('#errSectionName, #errIcon, #errOrder').text('');
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
            section_name: { err: '#errSectionName', inp: '#sSectionName' },
            icon         : { err: '#errIcon',         inp: '#sIcon'         },
            order        : { err: '#errOrder',       inp: '#sOrder'       },
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
