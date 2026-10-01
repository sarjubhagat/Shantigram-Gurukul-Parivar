@extends('admin.layouts.app')

@section('title', 'Parivar')
@section('page-title', 'Parivar')

@section('breadcrumb')
    <li class="breadcrumb-item active">Parivar</li>
@endsection

@section('page-actions')
    @can('parivar-create')
        <a href="{{ route('admin.parivar.add') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-person-plus me-1"></i> Add Member
        </a>
    @endcan
@endsection

@section('content')

<div class="card uc-card">

    <div class="card-header uc-header">
        <span class="uc-left">
            <span class="card-header-icon"><i class="bi bi-people-fill"></i></span>
            <span class="uc-title">All Members</span>
            <span class="uc-count" id="memberCountBadge">—</span>
        </span>
        <div class="uc-filters">
            <select id="searchBy" class="uc-search-by" title="Search by">
                <option value="name">Name</option>
                <option value="phone">Phone</option>
                <option value="address">Address</option>
                <option value="aadhaar">Aadhaar</option>
                <option value="unique_id">Member ID</option>
                <option value="all">All fields</option>
            </select>
            <div class="uc-search-box">
                <i class="bi bi-search uc-search-ico"></i>
                <input type="text" id="ucSearch" class="uc-search-inp"
                       placeholder="Search by name…" autocomplete="off">
                <button type="button" class="uc-search-clear d-none" id="ucSearchClear" title="Clear" aria-label="Clear search">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
    </div>

    <table id="membersTable" class="table dt-table mb-0" style="width:100%;">
        <thead>
            <tr>
                <th style="width:50px;">#</th>
                <th style="width:90px;">ID</th>
                <th>Name</th>
                <th>Number</th>
                <th>Address</th>
                <th>Aadhaar</th>
                <th>Created By</th>
                <th class="text-center" style="width:100px;">Actions</th>
            </tr>
        </thead>
    </table>
</div>

<div class="modal fade" id="photoPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content" style="border:none;border-radius:var(--r-lg);overflow:hidden;box-shadow:var(--sh-lg);">
            <div class="modal-header px-3 py-2 border-0">
                <h6 class="modal-title mb-0" id="photoPreviewTitle" style="font-weight:700;font-size:.9rem;">Photo</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center px-3 pb-3 pt-0">
                <img id="photoPreviewImg" src="" alt="Member photo"
                     style="max-width:100%;max-height:70vh;border-radius:10px;border:1px solid var(--border);object-fit:contain;background:#fafafa;">
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Gujarati:wght@400;600&display=swap" rel="stylesheet">
<style>
.uc-card {
    border-radius: var(--r-lg);
    box-shadow: 0 2px 12px rgba(139,26,26,.07), 0 1px 3px rgba(0,0,0,.05);
    overflow: hidden;
}
.uc-header {
    display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;
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
.uc-filters { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
.uc-search-by {
    border: 1.5px solid var(--border); border-radius: 2rem;
    padding: .38rem .9rem; font-size: .8rem; font-family: inherit;
    color: var(--text); background: var(--bg); outline: none; min-width: 120px;
    cursor: pointer;
}
.uc-search-by:focus {
    border-color: var(--primary); background: #fff;
    box-shadow: 0 0 0 3px rgba(139,26,26,.09);
}
.uc-search-box { position: relative; }
.uc-search-ico {
    position: absolute; left: .75rem; top: 50%; transform: translateY(-50%);
    color: var(--text-muted); font-size: .78rem; pointer-events: none;
}
.uc-search-inp {
    border: 1.5px solid var(--border); border-radius: 2rem;
    padding: .38rem 2.1rem .38rem 2.1rem;
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
.uc-search-clear {
    position: absolute; right: .45rem; top: 50%; transform: translateY(-50%);
    width: 22px; height: 22px; border: none; border-radius: 50%;
    background: transparent; color: var(--text-muted);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .7rem; padding: 0; cursor: pointer;
}
.uc-search-clear:hover { background: rgba(139,26,26,.08); color: var(--primary); }
.member-photo {
    width: 34px; height: 42px; object-fit: cover; border-radius: 6px;
    flex-shrink: 0; border: 1px solid var(--border); cursor: zoom-in;
    transition: transform .15s ease, box-shadow .15s ease;
}
.member-photo:hover {
    transform: scale(1.06);
    box-shadow: 0 2px 8px rgba(139,26,26,.18);
}
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
@media (max-width: 576px) {
    .uc-search-inp, .uc-search-inp:focus { width: 100%; min-width: 160px; }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    var URL_DATA   = '{{ route("admin.parivar.get_data") }}';
    var URL_DELETE = '{{ route("admin.parivar.delete") }}';
    var EDIT_BASE  = '{{ url("/admin/parivar") }}';
    var canEdit    = {{ auth()->user()->can('parivar-edit') ? 'true' : 'false' }};
    var canDelete  = {{ auth()->user()->can('parivar-delete') ? 'true' : 'false' }};
    var searchTimer;
    var placeholders = {
        name: 'Search by name…',
        phone: 'Search by phone…',
        address: 'Search by address / native place…',
        aadhaar: 'Search by Aadhaar…',
        unique_id: 'Search by member ID…',
        all: 'Search all fields…'
    };

    var photoModalEl = document.getElementById('photoPreviewModal');
    function photoModal() {
        return bootstrap.Modal.getOrCreateInstance(photoModalEl);
    }

    var dt = $('#membersTable').DataTable({
        processing : true,
        serverSide : true,
        ajax: {
            url: URL_DATA,
            type: 'GET',
            data: function (d) {
                d.search_by = $('#searchBy').val() || 'name';
                d.search_value = String($('#ucSearch').val() || '').trim();
                // Prevent DataTables empty search[value] from overriding our field.
                if (!d.search) d.search = {};
                d.search.value = d.search_value;
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Could not load members.';
                Swal.fire({ icon: 'error', title: 'Error', text: msg });
            }
        },
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
                    var photo = row.photo_url
                        ? '<img src="' + esc(row.photo_url) + '" alt="" class="member-photo" data-photo="' + esc(row.photo_url) + '" data-name="' + esc(val) + '" title="View photo">'
                        : '<div class="avatar-init" style="width:34px;height:34px;font-size:.75rem;flex-shrink:0;">' + esc(row.initials || '?') + '</div>';
                    return '<div class="d-flex align-items-center gap-2">'
                         + photo
                         + '<div><div style="font-size:.875rem;font-weight:600;color:var(--text);">' + esc(val) + '</div>' + sub + '</div>'
                         + '</div>';
                }
            },
            {
                data: 'phone',
                orderable: true,
                render: function (val) {
                    return val
                        ? '<span style="color:var(--text-2);">' + esc(val) + '</span>'
                        : '<span style="color:var(--text-muted);">—</span>';
                }
            },
            {
                data: 'current_address',
                orderable: true,
                render: function (val, type, row) {
                    var address = val || '';
                    var native = row.native_place || '';
                    if (!address && !native) {
                        return '<span style="color:var(--text-muted);">—</span>';
                    }
                    var html = address
                        ? '<div style="font-size:.82rem;color:var(--text-2);max-width:260px;white-space:normal;line-height:1.35;">' + esc(address) + '</div>'
                        : '';
                    if (native) {
                        html += '<div style="font-size:.72rem;color:var(--text-muted);margin-top:.15rem;">Native: ' + esc(native) + '</div>';
                    }
                    return html;
                }
            },
            {
                data: 'aadhaar',
                orderable: true,
                render: function (val) {
                    return val
                        ? '<span style="font-family:ui-monospace,monospace;font-size:.8rem;color:var(--text-2);letter-spacing:.02em;">' + esc(val) + '</span>'
                        : '<span style="color:var(--text-muted);">—</span>';
                }
            },
            {
                data: 'created_by_name',
                orderable: true,
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
            var api = this.api();
            var info = api.page.info();
            $('#memberCountBadge').text(
                info.recordsDisplay === info.recordsTotal
                    ? info.recordsTotal + ' total'
                    : info.recordsDisplay + ' / ' + info.recordsTotal
            );
        },
    });

    function reloadSearch() {
        dt.ajax.reload();
    }

    function syncClearBtn() {
        $('#ucSearchClear').toggleClass('d-none', !$('#ucSearch').val());
    }

    $('#searchBy').on('change', function () {
        var key = $(this).val() || 'name';
        $('#ucSearch').attr('placeholder', placeholders[key] || 'Search…');
        if ($('#ucSearch').val()) {
            reloadSearch();
        }
    });

    $('#ucSearch').on('input', function () {
        syncClearBtn();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(reloadSearch, 280);
    });

    $('#ucSearch').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(searchTimer);
            reloadSearch();
        }
    });

    $('#ucSearchClear').on('click', function () {
        $('#ucSearch').val('');
        syncClearBtn();
        reloadSearch();
        $('#ucSearch').focus();
    });

    $('#membersTable').on('click', '.member-photo', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var src = $(this).data('photo');
        var name = $(this).data('name') || 'Photo';
        if (!src) return;
        $('#photoPreviewImg').attr('src', src).attr('alt', name);
        $('#photoPreviewTitle').text(name);
        photoModal().show();
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
@endpush
