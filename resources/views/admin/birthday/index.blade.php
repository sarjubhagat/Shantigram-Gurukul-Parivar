@extends('admin.layouts.app')

@section('title', 'Birthday')
@section('page-title', 'Birthday')

@section('breadcrumb')
    <li class="breadcrumb-item active">Birthday</li>
@endsection

@section('content')

<div class="card uc-card mb-3">
    <div class="card-body py-3 px-4">
        <div class="row g-2 align-items-end">
            <div class="col-sm-auto">
                <label class="form-label mb-1" for="birthdayDate">Date</label>
                <input type="date" id="birthdayDate" class="form-control form-control-sm" value="{{ $today }}">
            </div>
            <div class="col-sm-auto">
                <button type="button" class="btn btn-primary btn-sm" id="btnToday">
                    <i class="bi bi-calendar-event me-1"></i> Today
                </button>
            </div>
            <div class="col-sm">
                <div class="form-text mb-0" id="birthdayHint">Showing birthdays for today.</div>
            </div>
        </div>
    </div>
</div>

<div class="card uc-card">
    <div class="card-header uc-header">
        <span class="uc-left">
            <span class="card-header-icon"><i class="bi bi-cake2"></i></span>
            <span class="uc-title" id="listTitle">Today's birthdays</span>
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

    <table id="birthdayTable" class="table dt-table mb-0" style="width:100%;">
        <thead>
            <tr>
                <th style="width:50px;">#</th>
                <th style="width:90px;">ID</th>
                <th>Member</th>
                <th>Phone</th>
                <th>Date of birth</th>
                <th style="width:90px;">Age</th>
                <th>Native place</th>
                <th class="text-center" style="width:90px;">Actions</th>
            </tr>
        </thead>
    </table>
</div>

@endsection

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Gujarati:wght@400;600&display=swap" rel="stylesheet">
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
.uid-badge {
    display:inline-block; background:rgba(139,26,26,.08); color:var(--primary);
    border-radius:2rem; padding:.18em .72em; font-size:.71rem; font-weight:700;
}
.age-badge {
    display:inline-block; background:#FFF3CD; color:#856404;
    border-radius:2rem; padding:.22em .7em; font-size:.75rem; font-weight:700;
}
.avatar-init {
    width:34px; height:34px; border-radius:50%;
    display:inline-flex; align-items:center; justify-content:center;
    background:rgba(139,26,26,.1); color:var(--primary);
    font-weight:700; font-size:.75rem;
}
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
    var TODAY = @json($today);
    var URL_DATA = gridUrl || (adminUrl + '/birthday/get_data');
    var searchTimer = null;

    var table = $('#birthdayTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: URL_DATA,
            type: 'GET',
            data: function (d) {
                d.date = $('#birthdayDate').val() || TODAY;
            },
            dataSrc: function (json) {
                applyMeta(json.meta || {});
                return json.data || [];
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Could not load birthdays.';
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
                            : '<div class="avatar-init" style="flex-shrink:0;">' + esc(row.initials || '?') + '</div>')
                        + '<div><div style="font-size:.875rem;font-weight:600;color:var(--text);">' + esc(val) + '</div>' + sub + '</div>'
                        + '</div>';
                }
            },
            { data: 'phone', defaultContent: '—' },
            { data: 'date_of_birth', defaultContent: '—' },
            {
                data: 'age',
                render: function (val) {
                    return val == null
                        ? '—'
                        : '<span class="age-badge">' + esc(String(val)) + ' yrs</span>';
                }
            },
            { data: 'native_place', defaultContent: '—' },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function (data, type, row) {
                    return '<a href="' + esc(row.edit_url) + '" class="btn btn-light btn-sm" title="Open member">'
                        + '<i class="bi bi-pencil-square"></i></a>';
                }
            }
        ],
        order: [[2, 'asc']],
        pageLength: 25,
        dom: 'rt<"dt-foot"lip>',
        language: {
            emptyTable: 'No birthdays on this date.',
            zeroRecords: 'No matching members found.',
            processing: 'Loading…'
        },
        drawCallback: function () {
            var api = this.api();
            var total = api.data().length;
            var filtered = api.rows({ search: 'applied' }).count();
            $('#rowCountBadge').text(filtered === total ? total + ' total' : filtered + ' / ' + total);
        }
    });

    function applyMeta(meta) {
        var isToday = !!meta.is_today;
        var label = meta.date_label || '';
        $('#listTitle').text(isToday ? "Today's birthdays" : ('Birthdays on ' + label));
        $('#birthdayHint').text(
            isToday
                ? 'Showing members whose birthday is today.'
                : ('Showing members whose birthday falls on ' + label + '.')
        );
    }

    function reloadList() {
        table.ajax.reload();
    }

    function syncSearchClear() {
        $('#ucSearchClear').toggleClass('d-none', !$('#ucSearch').val());
    }

    $('#birthdayDate').on('change', function () {
        if (!this.value) {
            this.value = TODAY;
        }
        reloadList();
    });

    $('#btnToday').on('click', function () {
        $('#birthdayDate').val(TODAY);
        reloadList();
    });

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

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
        });
    }
})();
</script>
@endpush
