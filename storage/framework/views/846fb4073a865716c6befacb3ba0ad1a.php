<?php $__env->startSection('title', 'I-Card Print'); ?>
<?php $__env->startSection('page-title', 'I-Card Print'); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item active">I-Card Print</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-actions'); ?>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('icard-print-edit')): ?>
        <a href="<?php echo e(route('admin.icard-print.settings')); ?>" class="btn btn-light btn-sm">
            <i class="bi bi-sliders me-1"></i> Settings
        </a>
    <?php endif; ?>
    <button type="button" class="btn btn-primary btn-sm" id="btnPrint" disabled>
        <i class="bi bi-printer me-1"></i> Print selected
    </button>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="card uc-card mb-3">
    <div class="card-body py-3 px-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Start filling from row</label>
                <select id="startRow" class="form-select form-select-sm">
                    <?php for($r = 1; $r <= 5; $r++): ?>
                        <option value="<?php echo e($r); ?>" <?php if($startRow === $r): echo 'selected'; endif; ?>>Row <?php echo e($r); ?> (top is 1)</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Start filling from side</label>
                <select id="startCol" class="form-select form-select-sm">
                    <option value="1" <?php if($startColumn === 1): echo 'selected'; endif; ?>>Left card</option>
                    <option value="2" <?php if($startColumn === 2): echo 'selected'; endif; ?>>Right card</option>
                </select>
            </div>
            <div class="col-md-6">
                <div class="form-text mb-0" id="slotHint">
                    Select members, then print. Empty tray slots before the start position stay blank.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card uc-card">
    <div class="card-header uc-header">
        <span class="uc-left">
            <span class="card-header-icon"><i class="bi bi-credit-card-2-front"></i></span>
            <span class="uc-title">Members</span>
            <span class="uc-count" id="selBadge">0 selected</span>
        </span>
        <div class="uc-search-box">
            <i class="bi bi-search uc-search-ico"></i>
            <input type="text" id="ucSearch" class="uc-search-inp" placeholder="Search members…" autocomplete="off">
        </div>
    </div>
    <table id="icardTable" class="table dt-table mb-0" style="width:100%;">
        <thead>
            <tr>
                <th style="width:40px;"></th>
                <th style="width:90px;">ID</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Native place</th>
                <th>Photo</th>
            </tr>
        </thead>
    </table>
</div>

<form id="printForm" method="GET" action="<?php echo e(route('admin.icard-print.print')); ?>" target="_blank">
    <input type="hidden" name="start_row" id="formStartRow" value="<?php echo e($startRow); ?>">
    <input type="hidden" name="start_column" id="formStartCol" value="<?php echo e($startColumn); ?>">
    <div id="printIds"></div>
</form>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
.uc-card { border-radius: var(--r-lg); box-shadow: 0 2px 12px rgba(139,26,26,.07), 0 1px 3px rgba(0,0,0,.05); overflow: hidden; }
.uc-header { padding: .82rem 1.25rem !important; border-bottom: 1.5px solid var(--border-light) !important; }
.uc-left { display: flex; align-items: center; gap: .55rem; }
.uc-title { font-size: .9rem; font-weight: 700; }
.uc-count { background: rgba(139,26,26,.09); color: var(--primary); border-radius: 2rem; padding: .18em .72em; font-size: .71rem; font-weight: 700; }
.uc-search-box { position: relative; }
.uc-search-ico { position: absolute; left: .75rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: .78rem; pointer-events: none; }
.uc-search-inp { border: 1.5px solid var(--border); border-radius: 2rem; padding: .38rem 1rem .38rem 2.1rem; font-size: .82rem; background: var(--bg); outline: none; width: 210px; }
.uc-search-inp:focus { border-color: var(--primary); background: #fff; width: 255px; }
.dt-foot { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .5rem; padding: .7rem 1.25rem; background: #FAFAFA; border-top: 1.5px solid var(--border-light); }
.uid-badge { font-family: ui-monospace, monospace; font-size: .78rem; font-weight: 700; color: var(--primary); background: rgba(139,26,26,.08); border-radius: .4rem; padding: .2em .55em; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    var URL_DATA = '<?php echo e(route("admin.icard-print.get_data")); ?>';
    var selected = {};
    var searchTimer;

    function availableSlots() {
        var row = parseInt($('#startRow').val(), 10) || 1;
        var col = parseInt($('#startCol').val(), 10) || 1;
        return 10 - ((row - 1) * 2 + (col - 1));
    }

    function selCount() { return Object.keys(selected).length; }

    function syncHint() {
        var max = availableSlots();
        var n = selCount();
        $('#selBadge').text(n + ' / ' + max + ' selected');
        $('#slotHint').text('Start slot leaves earlier tray positions blank. You can pick up to ' + max + ' member(s).');
        $('#btnPrint').prop('disabled', n < 1);
        $('#icardTable .icard-chk').each(function () {
            var id = String($(this).val());
            var checked = !!selected[id];
            $(this).prop('checked', checked);
            $(this).prop('disabled', !checked && n >= max);
        });
    }

    var dt = $('#icardTable').DataTable({
        processing: true, serverSide: false,
        ajax: { url: URL_DATA, type: 'GET' },
        order: [[1, 'desc']], pageLength: 25, scrollX: true,
        dom: 'rt<"dt-foot"lip>',
        columns: [
            { data: null, orderable: false, searchable: false,
              render: function (d) {
                  return '<input type="checkbox" class="form-check-input icard-chk" value="' + d.id + '">';
              }},
            { data: 'unique_id', render: function (v) { return '<span class="uid-badge">' + esc(String(v)) + '</span>'; }},
            { data: 'full_name', render: function (val, t, row) {
                var sub = row.gu_full_name ? '<div style="font-size:.75rem;color:var(--text-muted);">' + esc(row.gu_full_name) + '</div>' : '';
                var pic = row.photo_url
                    ? '<img src="' + esc(row.photo_url) + '" style="width:28px;height:34px;object-fit:cover;border-radius:5px;border:1px solid var(--border);">'
                    : '<span class="avatar-init" style="width:28px;height:28px;font-size:.7rem;">' + esc(row.initials || '?') + '</span>';
                return '<div class="d-flex align-items-center gap-2">' + pic + '<div><strong>' + esc(val) + '</strong>' + sub + '</div></div>';
            }},
            { data: 'phone', render: function (v) { return v ? esc(v) : '<span class="text-muted">—</span>'; }},
            { data: 'native_place', render: function (v) { return v ? esc(v) : '<span class="text-muted">—</span>'; }},
            { data: 'has_photo', render: function (v) {
                return v
                    ? '<span class="badge" style="background:rgba(25,135,84,.12);color:#198754;">Yes</span>'
                    : '<span class="badge" style="background:rgba(108,117,125,.12);color:#6c757d;">No</span>';
            }},
        ],
        drawCallback: syncHint,
        language: { emptyTable: 'No members yet', lengthMenu: 'Show _MENU_', info: 'Showing _START_–_END_ of _TOTAL_' },
    });

    $('#ucSearch').on('input', function () {
        clearTimeout(searchTimer);
        var val = this.value;
        searchTimer = setTimeout(function () { dt.search(val).draw(); }, 220);
    });

    $('#icardTable').on('change', '.icard-chk', function () {
        var id = String(this.value);
        if (this.checked) {
            if (selCount() >= availableSlots()) {
                this.checked = false;
                return;
            }
            selected[id] = true;
        } else {
            delete selected[id];
        }
        syncHint();
    });

    $('#startRow, #startCol').on('change', function () {
        var max = availableSlots();
        var ids = Object.keys(selected);
        if (ids.length > max) {
            ids.slice(max).forEach(function (id) { delete selected[id]; });
        }
        $('#formStartRow').val($('#startRow').val());
        $('#formStartCol').val($('#startCol').val());
        syncHint();
    });

    $('#btnPrint').on('click', function () {
        var ids = Object.keys(selected);
        if (!ids.length) return;
        var $box = $('#printIds').empty();
        ids.forEach(function (id) {
            $box.append('<input type="hidden" name="member_ids[]" value="' + id + '">');
        });
        $('#formStartRow').val($('#startRow').val());
        $('#formStartCol').val($('#startCol').val());
        $('#printForm')[0].submit();
    });

    function esc(str) { return $('<span>').text(str == null ? '' : String(str)).html(); }
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\projects\Gurukul Parivar\resources\views/admin/icard-print/index.blade.php ENDPATH**/ ?>