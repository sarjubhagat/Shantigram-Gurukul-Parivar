@extends('admin.layouts.app')

@section('title', 'Data Updates')
@section('page-title', 'Data Updates')

@section('breadcrumb')
    <li class="breadcrumb-item active">Data Updates</li>
@endsection

@section('content')

<div class="card uc-card mb-3">
    <div class="card-body py-3 px-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select id="statusFilter" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" @selected($key === 'pending')>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <div class="form-text mb-0">Member self-service requests. Solve applies proposed details to the member record.</div>
            </div>
        </div>
    </div>
</div>

<div class="card uc-card">
    <div class="card-header uc-header">
        <span class="uc-left">
            <span class="card-header-icon"><i class="bi bi-arrow-repeat"></i></span>
            <span class="uc-title">Update requests</span>
            <span class="uc-count" id="rowCountBadge">—</span>
        </span>
        <div class="uc-search-box">
            <i class="bi bi-search uc-search-ico"></i>
            <input type="text" id="ucSearch" class="uc-search-inp" placeholder="Search name, phone, ID…" autocomplete="off">
            <button type="button" class="uc-search-clear d-none" id="ucSearchClear" title="Clear search" aria-label="Clear search">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <table id="updatesTable" class="table dt-table mb-0" style="width:100%;">
        <thead>
            <tr>
                <th style="width:50px;">#</th>
                <th>Member</th>
                <th>Phone</th>
                <th>Changes</th>
                <th>Status</th>
                <th>Submitted</th>
                <th class="text-center" style="width:110px;">Actions</th>
            </tr>
        </thead>
    </table>
</div>

<div class="modal fade" id="reviewModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border:none;border-radius:var(--r-lg);overflow:hidden;">
            <div class="modal-header px-4 py-3"
                 style="background:linear-gradient(135deg,var(--primary) 0%,var(--primary-mid) 100%);border:none;">
                <h5 class="modal-title text-white mb-0" style="font-weight:700;font-size:.97rem;">
                    <i class="bi bi-eye me-2"></i>Review update
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div id="reviewMeta" class="mb-3" style="font-size:.86rem;color:var(--text-muted);"></div>
                <div id="memberNoteBox" class="alert alert-light border mb-3 d-none" style="font-size:.88rem;">
                    <div class="fw-bold mb-1">Member note / વિનંતી</div>
                    <div id="memberNoteText"></div>
                </div>
                <div id="photoCompare" class="row g-3 mb-3 d-none">
                    <div class="col-6">
                        <div class="small text-muted mb-1">Current photo</div>
                        <img id="currentPhotoImg" src="" alt="" style="max-width:120px;border-radius:8px;border:1px solid var(--border);">
                    </div>
                    <div class="col-6">
                        <div class="small text-muted mb-1">Requested photo</div>
                        <img id="proposedPhotoImg" src="" alt="" style="max-width:120px;border-radius:8px;border:1px solid var(--border);">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-3">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Current</th>
                                <th>Requested</th>
                            </tr>
                        </thead>
                        <tbody id="diffBody"></tbody>
                    </table>
                </div>
                <div class="mb-2">
                    <label class="form-label">Admin note</label>
                    <textarea id="adminNote" class="form-control" rows="2" placeholder="Optional note"></textarea>
                </div>
                <div class="form-check mb-0" id="applyWrap">
                    <input class="form-check-input" type="checkbox" id="applyChanges" checked>
                    <label class="form-check-label" for="applyChanges">Apply requested values to member when marking Solved</label>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-3" id="reviewActions">
                @can('data-updates-edit')
                    <button type="button" class="btn btn-light" id="btnReject">Reject</button>
                    <button type="button" class="btn btn-primary" id="btnSolve">Mark Solved</button>
                @endcan
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.uc-card { border-radius: var(--r-lg); box-shadow: 0 2px 12px rgba(139,26,26,.07), 0 1px 3px rgba(0,0,0,.05); overflow: hidden; border: 1.5px solid var(--border-light); }
.uc-header { display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; padding:.82rem 1.25rem !important; border-bottom:1.5px solid var(--border-light) !important; background:#fff; }
.uc-left { display:flex; align-items:center; gap:.55rem; }
.uc-title { font-size:.9rem; font-weight:700; color:var(--text); }
.uc-count { background:rgba(139,26,26,.09); color:var(--primary); border-radius:2rem; padding:.18em .72em; font-size:.71rem; font-weight:700; letter-spacing:.2px; }
.uc-search-box { position:relative; }
.uc-search-ico { position:absolute; left:.75rem; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:.78rem; pointer-events:none; }
.uc-search-inp {
    border:1.5px solid var(--border); border-radius:2rem;
    padding:.38rem 2.1rem .38rem 2.1rem;
    font-size:.82rem; font-family:inherit;
    color:var(--text); background:var(--bg);
    outline:none; width:230px;
    transition:border-color .2s, box-shadow .2s, width .25s;
}
.uc-search-inp:focus {
    border-color:var(--primary); background:#fff;
    box-shadow:0 0 0 3px rgba(139,26,26,.09); width:280px;
}
.uc-search-inp::placeholder { color:var(--text-muted); }
.uc-search-clear {
    position:absolute; right:.45rem; top:50%; transform:translateY(-50%);
    width:22px; height:22px; border:none; border-radius:50%;
    background:transparent; color:var(--text-muted);
    display:inline-flex; align-items:center; justify-content:center;
    font-size:.7rem; padding:0; cursor:pointer;
}
.uc-search-clear:hover { background:rgba(139,26,26,.08); color:var(--primary); }
.btn-view { min-width:34px; min-height:30px; }
.btn-view.is-loading { pointer-events:none; opacity:.85; }
.badge-st { font-size:.72rem; font-weight:700; border-radius:999px; padding:.28em .7em; }
.badge-pending { background:#FFF3CD; color:#856404; }
.badge-solved { background:#D1E7DD; color:#0F5132; }
.badge-rejected { background:#F8D7DA; color:#842029; }
.diff-from { color:#842029; text-decoration:line-through; }
.diff-to { color:#0F5132; font-weight:700; }
.dt-foot {
    display:flex; align-items:center; justify-content:space-between;
    flex-wrap:wrap; gap:.5rem;
    padding:.7rem 1.25rem; background:#FAFAFA;
    border-top:1.5px solid var(--border-light);
    border-radius:0 0 var(--r-lg) var(--r-lg);
}
@media (max-width: 576px) {
    .uc-search-inp, .uc-search-inp:focus { width:100%; min-width:180px; }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    var currentId = null;
    var canEdit = @json(auth()->user()->can('data-updates-edit'));
    var URL_DATA = gridUrl || (adminUrl + '/data-updates/get_data');
    var reviewModalEl = document.getElementById('reviewModal');

    function reviewModal() {
        return bootstrap.Modal.getOrCreateInstance(reviewModalEl);
    }

    var table = $('#updatesTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: URL_DATA,
            type: 'GET',
            data: function (d) {
                d.status = $('#statusFilter').val();
            },
            dataSrc: 'data',
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Could not load update requests.';
                Swal.fire({ icon: 'error', title: 'Error', text: msg });
            }
        },
        columns: [
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            {
                data: 'member_name',
                render: function (val, type, row) {
                    return '<strong>' + esc(val) + '</strong>'
                        + '<div class="text-muted" style="font-size:.75rem;">#' + esc(String(row.unique_id)) + '</div>';
                }
            },
            { data: 'phone', defaultContent: '—' },
            { data: 'changes_count' },
            {
                data: 'status',
                render: function (s, type, row) {
                    return '<span class="badge-st badge-' + esc(s) + '">' + esc(row.status_label) + '</span>';
                }
            },
            { data: 'created' },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function (data, type, row) {
                    return '<button type="button" class="btn btn-light btn-sm btn-view" data-id="' + row.id + '" title="Review">'
                        + '<span class="btn-view-ico"><i class="bi bi-eye"></i></span>'
                        + '<span class="btn-view-load d-none"><span class="spinner-border spinner-border-sm" role="status"></span></span>'
                        + '</button>';
                }
            }
        ],
        order: [[5, 'desc']],
        pageLength: 25,
        dom: 'rt<"dt-foot"lip>',
        language: {
            emptyTable: 'No update requests yet.',
            zeroRecords: 'No matching requests found.',
            processing: 'Loading…'
        },
        drawCallback: function () {
            var api = this.api();
            var total = api.data().length;
            var filtered = api.rows({ search: 'applied' }).count();
            $('#rowCountBadge').text(filtered === total ? total + ' total' : filtered + ' / ' + total);
        }
    });

    var searchTimer = null;

    function syncSearchClear() {
        $('#ucSearchClear').toggleClass('d-none', !$('#ucSearch').val());
    }

    $('#ucSearch').on('input', function () {
        var val = this.value;
        syncSearchClear();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            table.search(val).draw();
        }, 180);
    });

    $('#ucSearchClear').on('click', function () {
        $('#ucSearch').val('').trigger('input').focus();
    });

    $('#statusFilter').on('change', function () {
        table.ajax.reload();
    });

    function setViewLoading($btn, on) {
        $btn.toggleClass('is-loading', on).prop('disabled', on);
        $btn.find('.btn-view-ico').toggleClass('d-none', on);
        $btn.find('.btn-view-load').toggleClass('d-none', !on);
    }

    function setReviewBusy(on) {
        $('#btnSolve, #btnReject').prop('disabled', on);
        if (on) {
            $('#btnSolve').data('old-html', $('#btnSolve').html());
            $('#btnReject').data('old-html', $('#btnReject').html());
            $('#btnSolve').html('<span class="spinner-border spinner-border-sm me-1"></span>Saving…');
            $('#btnReject').html('<span class="spinner-border spinner-border-sm me-1"></span>…');
        } else {
            if ($('#btnSolve').data('old-html')) $('#btnSolve').html($('#btnSolve').data('old-html'));
            if ($('#btnReject').data('old-html')) $('#btnReject').html($('#btnReject').data('old-html'));
        }
    }

    $(document).on('click', '.btn-view', function () {
        var $btn = $(this);
        if ($btn.hasClass('is-loading')) return;

        currentId = $btn.data('id');
        setViewLoading($btn, true);

        $.get(adminUrl + '/data-updates/' + currentId + '/show')
            .done(function (r) {
                var d = r.data;
                $('#reviewMeta').html(
                    '<strong>' + esc(d.member_name) + '</strong> · #' + esc(String(d.unique_id))
                    + ' · ' + esc(d.phone || '—')
                    + ' · <span class="badge-st badge-' + esc(d.status) + '">' + esc(d.status_label) + '</span>'
                    + '<div class="mt-1">Submitted: ' + esc(d.created || '—') + '</div>'
                );

                if (d.member_note) {
                    $('#memberNoteText').text(d.member_note);
                    $('#memberNoteBox').removeClass('d-none');
                } else {
                    $('#memberNoteBox').addClass('d-none');
                    $('#memberNoteText').text('');
                }

                if (d.current_photo || d.proposed_photo) {
                    $('#currentPhotoImg').attr('src', d.current_photo || '').toggle(!!d.current_photo);
                    $('#proposedPhotoImg').attr('src', d.proposed_photo || '').toggle(!!d.proposed_photo);
                    $('#photoCompare').removeClass('d-none');
                } else {
                    $('#photoCompare').addClass('d-none');
                }

                var html = '';
                (d.diff || []).forEach(function (row) {
                    html += '<tr><td>' + esc(row.label) + '</td>'
                        + '<td class="diff-from">' + esc(row.from == null || row.from === '' ? '—' : row.from) + '</td>'
                        + '<td class="diff-to">' + esc(row.to == null || row.to === '' ? '—' : row.to) + '</td></tr>';
                });
                if (!html) {
                    html = '<tr><td colspan="3" class="text-muted">No field differences.</td></tr>';
                }
                $('#diffBody').html(html);
                $('#adminNote').val(d.admin_note || '');

                var pending = d.status === 'pending';
                $('#reviewActions, #applyWrap').toggle(pending && canEdit);
                reviewModal().show();
            })
            .fail(function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: (xhr.responseJSON || {}).message || 'Could not load request.'
                });
            })
            .always(function () {
                setViewLoading($btn, false);
            });
    });

    function review(action) {
        setReviewBusy(true);
        $.ajax({
            url: adminUrl + '/data-updates/' + currentId + '/review',
            type: 'POST',
            data: {
                _token: csrfToken,
                action: action,
                admin_note: $('#adminNote').val(),
                apply: $('#applyChanges').is(':checked') ? 1 : 0
            },
            success: function (r) {
                reviewModal().hide();
                Swal.fire({ icon: 'success', title: 'Done', text: r.message, timer: 1800, showConfirmButton: false });
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: (xhr.responseJSON || {}).message || 'Could not review.'
                });
            },
            complete: function () {
                setReviewBusy(false);
            }
        });
    }

    $('#btnSolve').on('click', function () { review('solve'); });
    $('#btnReject').on('click', function () { review('reject'); });

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
        });
    }
})();
</script>
@endpush
