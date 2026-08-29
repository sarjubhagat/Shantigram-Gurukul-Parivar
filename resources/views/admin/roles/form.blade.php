@extends('admin.layouts.app')

@section('title', isset($role) ? 'Edit Role: ' . $role->name : 'Create Role')
@section('page-title', isset($role) ? 'Edit Role' : 'Create Role')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ url('/admin/roles') }}">Roles</a></li>
    <li class="breadcrumb-item active">{{ isset($role) ? 'Edit: ' . $role->name : 'Create Role' }}</li>
@endsection

@section('page-actions')
    <a href="{{ url('/admin/roles') }}" class="btn btn-light btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Roles
    </a>
@endsection

@section('content')

@php
    $checkedPerms = old('permissions', $rolePermissions ?? []);
    $isEdit       = isset($role);
    $formAction   = $isEdit
                        ? route('admin.roles.edit.store', $role->id)
                        : route('admin.roles.add.store');
@endphp

<form method="POST" action="{{ $formAction }}" id="rolePageForm" novalidate autocomplete="off">
    @csrf

    <div class="row g-4 align-items-start">

        {{-- ══════════════════════════════════════════════════════
             LEFT — sticky panel: name, stats, save
             ══════════════════════════════════════════════════════ --}}
        <div class="col-xl-3 col-lg-4">
            <div class="rp-sticky">

                <div class="rp-card">
                    {{-- card head --}}
                    <div class="rp-card-head">
                        <i class="bi bi-shield-lock"></i>
                        <span>{{ $isEdit ? 'Edit Role' : 'New Role' }}</span>
                    </div>

                    <div class="card-body p-3">

                        {{-- Role name --}}
                        <div class="mb-3">
                            <label for="roleName" class="rp-label">
                                Role Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control rp-input @error('name') is-invalid @enderror"
                                   id="roleName" name="name"
                                   value="{{ old('name', $role->name ?? '') }}"
                                   placeholder="e.g. Editor, Manager…"
                                   autocomplete="off" autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Unique key --}}
                        <div class="mb-3">
                            <label for="roleKey" class="rp-label">
                                Unique Key
                                <span style="font-weight:400;color:var(--text-muted);font-size:.74rem;margin-left:.3rem;">auto-generated, editable</span>
                            </label>
                            <div class="rp-key-wrap">
                                <input type="text"
                                       class="form-control rp-input rp-key-inp @error('unique_key') is-invalid @enderror"
                                       id="roleKey" name="unique_key"
                                       value="{{ old('unique_key', $role->unique_key ?? '') }}"
                                       placeholder="e.g. super-admin"
                                       autocomplete="off"
                                       spellcheck="false">
                                <button type="button" id="rp-key-reset" class="rp-key-reset-btn" title="Re-generate from name">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                            <div class="rp-key-hint">Lowercase letters, numbers and hyphens only. Used in code to identify this role.</div>
                            @error('unique_key')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Selected count + progress --}}
                        <div class="rp-stats mb-3">
                            <div class="rp-stats-row">
                                <span class="rp-stats-label">Permissions selected</span>
                                <span id="selCount" class="rp-stats-val">0 / 0</span>
                            </div>
                            <div class="rp-bar-track">
                                <div id="selBar" class="rp-bar-fill"></div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <button type="submit" id="saveBtn" class="btn btn-primary w-100 mb-2 rp-save-btn">
                            <span id="saveBtnText">
                                <i class="bi bi-check-lg me-1"></i>
                                {{ $isEdit ? 'Update Role' : 'Create Role' }}
                            </span>
                            <span id="saveBtnLoad" class="d-none">
                                <span class="spinner-border spinner-border-sm me-1"></span>Saving…
                            </span>
                        </button>
                        <a href="{{ url('/admin/roles') }}" class="btn btn-light w-100 rp-cancel-btn">
                            <i class="bi bi-x me-1"></i> Cancel
                        </a>

                    </div>
                </div>

            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════
             RIGHT — permissions with search + section/module toggles
             ══════════════════════════════════════════════════════ --}}
        <div class="col-xl-9 col-lg-8">
            <div class="rp-card">

                {{-- permissions header --}}
                <div class="rp-perm-head">
                    <span class="rp-hl">
                        <span class="rp-head-icon"><i class="bi bi-key"></i></span>
                        <span class="rp-head-title">Assign Permissions</span>
                        <span class="rp-head-badge" id="permCountBadge">—</span>
                    </span>
                    <div class="rp-head-actions">
                        @if($permissions->isNotEmpty())
                            <button type="button" id="btnGlobalToggle" class="btn-tgl btn-tgl-lg">
                                Select All
                            </button>
                        @endif
                        <div class="rp-srch-wrap">
                            <i class="bi bi-search rp-srch-ico"></i>
                            <input type="text" id="permSearch" class="rp-srch-inp"
                                   placeholder="Filter permissions…" autocomplete="off">
                        </div>
                    </div>
                </div>

                {{-- permissions body --}}
                <div class="card-body p-4" id="permArea">

                    @forelse($permissions as $sectionName => $moduleGroups)
                        @php $secKey = $sectionName ?: '__general__'; @endphp

                        <div class="perm-sec-blk mb-4" data-sec="{{ $secKey }}">

                            {{-- section header --}}
                            <div class="perm-sec-hdr">
                                <span>
                                    <i class="bi bi-collection"></i>
                                    {{ $sectionName ?: 'General' }}
                                </span>
                                <button type="button" class="btn-tgl btn-tgl-sec"
                                        data-sec="{{ $secKey }}">Select Section</button>
                            </div>

                            {{-- module groups in responsive grid --}}
                            <div class="row g-3 mt-0">
                                @foreach($moduleGroups as $moduleTitle => $perms)
                                    @php
                                        $first  = $perms->first();
                                        $lbl    = $moduleTitle ?: 'Other';
                                        $icon   = $first->module_icon ?: 'bi-key';
                                        $slug   = $first->module_slug ?: 'other-' . $loop->index;
                                    @endphp

                                    <div class="col-sm-6 col-xl-4 perm-mod-col" data-mod="{{ $slug }}">
                                        <div class="perm-group h-100">

                                            <div class="perm-group-hdr">
                                                <span>
                                                    <i class="bi {{ $icon }}"></i> {{ $lbl }}
                                                </span>
                                                <button type="button" class="btn-tgl btn-tgl-mod"
                                                        data-group="{{ $slug }}" data-sec="{{ $secKey }}">
                                                    All
                                                </button>
                                            </div>

                                            <div class="perm-items">
                                                @foreach($perms as $perm)
                                                    @php
                                                        $pfx    = $first->module_slug;
                                                        $action = ($pfx && str_starts_with($perm->name, $pfx . '-'))
                                                                    ? ucfirst(substr($perm->name, strlen($pfx) + 1))
                                                                    : ucfirst($perm->name);
                                                    @endphp
                                                    <label class="perm-chip"
                                                           data-label="{{ strtolower($action) }}"
                                                           data-mod="{{ strtolower($slug) }}">
                                                        <input type="checkbox"
                                                               class="perm-chk"
                                                               name="permissions[]"
                                                               value="{{ $perm->name }}"
                                                               data-group="{{ $slug }}"
                                                               data-sec="{{ $secKey }}"
                                                               {{ in_array($perm->name, $checkedPerms) ? 'checked' : '' }}>
                                                        {{ $action }}
                                                    </label>
                                                @endforeach
                                            </div>

                                        </div>
                                    </div>
                                @endforeach
                            </div>

                        </div>
                    @empty
                        <div class="text-center py-5 text-muted" style="font-size:.86rem;">
                            <i class="bi bi-key d-block mb-2" style="font-size:2rem;opacity:.3;"></i>
                            No permissions found. Create permissions first.
                        </div>
                    @endforelse

                    {{-- empty search state --}}
                    <div id="noPermResults" class="d-none text-center py-4 text-muted" style="font-size:.84rem;">
                        <i class="bi bi-search d-block mb-2" style="font-size:1.5rem;opacity:.35;"></i>
                        No permissions match "<span id="noResultTerm" style="font-weight:600;"></span>".
                    </div>

                </div>
            </div>
        </div>

    </div>
</form>

@endsection

@push('styles')
<style>
/* ── Shared card ─────────────────────────────────────────── */
.rp-card {
    border-radius: var(--r-lg);
    box-shadow: 0 2px 12px rgba(139,26,26,.07), 0 1px 3px rgba(0,0,0,.05);
    overflow: hidden;
    border: 1.5px solid var(--border-light);
    background: #fff;
}

/* ── Left sticky panel ───────────────────────────────────── */
.rp-sticky { position: sticky; top: 1.25rem; }

.rp-card-head {
    display: flex; align-items: center; gap: .6rem;
    padding: .9rem 1.1rem;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-mid) 100%);
    color: #fff; font-weight: 700; font-size: .9rem;
}

.rp-label {
    display: block;
    font-size: .8rem; font-weight: 700; color: var(--text); margin-bottom: .35rem;
}
.rp-input {
    font-size: .875rem;
    border: 1.5px solid var(--border);
    border-radius: var(--r-sm);
    transition: border-color .2s, box-shadow .2s;
}
.rp-input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(139,26,26,.1);
    outline: none;
}
.rp-input.is-invalid { border-color: #dc3545; }

.rp-key-wrap {
    display: flex; align-items: center; gap: .4rem;
}
.rp-key-inp {
    font-family: ui-monospace, monospace;
    font-size: .82rem;
    letter-spacing: .02em;
    flex: 1;
}
.rp-key-reset-btn {
    flex-shrink: 0;
    width: 32px; height: 32px;
    border: 1.5px solid var(--border); border-radius: var(--r-sm);
    background: var(--bg); color: var(--text-muted);
    cursor: pointer; transition: all .15s;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .85rem;
}
.rp-key-reset-btn:hover { border-color: var(--primary); color: var(--primary); background: rgba(139,26,26,.05); }
.rp-key-hint { font-size: .73rem; color: var(--text-muted); margin-top: .28rem; }

.rp-stats {
    padding: .6rem .85rem;
    background: var(--bg); border-radius: var(--r-sm);
    border: 1.5px solid var(--border-light);
}
.rp-stats-row {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: .38rem;
}
.rp-stats-label { font-size: .76rem; color: var(--text-muted); }
.rp-stats-val   { font-size: .84rem; font-weight: 700; color: var(--primary); }
.rp-bar-track   {
    background: var(--border-light); border-radius: 2rem; overflow: hidden; height: 5px;
}
.rp-bar-fill {
    background: linear-gradient(90deg, var(--primary), var(--primary-mid));
    height: 5px; width: 0%; border-radius: 2rem; transition: width .3s ease;
}

.rp-save-btn   { font-size: .875rem; font-weight: 600; }
.rp-cancel-btn { font-size: .875rem; color: var(--text-2); }

/* ── Permissions header ──────────────────────────────────── */
.rp-perm-head {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: .5rem;
    padding: .82rem 1.25rem;
    border-bottom: 1.5px solid var(--border-light);
}
.rp-hl { display: flex; align-items: center; gap: .55rem; }
.rp-head-icon {
    width: 28px; height: 28px;
    background: rgba(139,26,26,.09); border-radius: var(--r-sm);
    display: inline-flex; align-items: center; justify-content: center;
    color: var(--primary); font-size: .88rem; flex-shrink: 0;
}
.rp-head-title { font-size: .9rem; font-weight: 700; color: var(--text); }
.rp-head-badge {
    background: rgba(139,26,26,.09); color: var(--primary);
    border-radius: 2rem; padding: .18em .72em;
    font-size: .71rem; font-weight: 700; letter-spacing: .2px;
}
.rp-head-actions { display: flex; align-items: center; gap: .45rem; flex-wrap: wrap; }

/* search */
.rp-srch-wrap { position: relative; }
.rp-srch-ico {
    position: absolute; left: .75rem; top: 50%; transform: translateY(-50%);
    color: var(--text-muted); font-size: .78rem; pointer-events: none;
}
.rp-srch-inp {
    border: 1.5px solid var(--border); border-radius: 2rem;
    padding: .38rem 1rem .38rem 2.1rem;
    font-size: .82rem; font-family: inherit;
    color: var(--text); background: var(--bg);
    outline: none; width: 195px;
    transition: border-color .2s, box-shadow .2s, width .25s;
}
.rp-srch-inp:focus {
    border-color: var(--primary); background: #fff;
    box-shadow: 0 0 0 3px rgba(139,26,26,.09); width: 235px;
}
.rp-srch-inp::placeholder { color: var(--text-muted); }

/* ── Section header ──────────────────────────────────────── */
.perm-sec-hdr {
    display: flex; align-items: center; justify-content: space-between;
    font-size: .73rem; font-weight: 700; letter-spacing: .5px; text-transform: uppercase;
    color: var(--primary);
    padding: .5rem .05rem .35rem;
    border-bottom: 1.5px solid rgba(139,26,26,.13);
    margin-bottom: .75rem;
}
.perm-sec-hdr i { font-size: .85rem; }

/* ── Module group card ───────────────────────────────────── */
.perm-group {
    border: 1.5px solid var(--border); border-radius: var(--r-sm); overflow: hidden;
}
.perm-group-hdr {
    display: flex; align-items: center; justify-content: space-between;
    padding: .44rem .85rem;
    background: var(--bg); border-bottom: 1.5px solid var(--border);
    font-size: .82rem; font-weight: 700; color: var(--text);
}
.perm-group-hdr i { color: var(--primary); margin-right: .2rem; }
.perm-items {
    padding: .6rem .85rem; display: flex; flex-wrap: wrap; gap: .3rem;
}

/* ── Permission chip ─────────────────────────────────────── */
.perm-chip {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .28em .75em;
    border: 1.5px solid var(--border); border-radius: 2rem;
    cursor: pointer; user-select: none;
    font-size: .775rem; color: var(--text-2); background: #fff;
    transition: border-color .15s, background .15s, color .15s; margin: 0;
}
.perm-chip:hover {
    border-color: var(--primary); color: var(--primary);
    background: rgba(139,26,26,.04);
}
.perm-chip:has(input:checked) {
    border-color: var(--primary);
    background: rgba(139,26,26,.09);
    color: var(--primary); font-weight: 600;
}
.perm-chip input[type="checkbox"] {
    width: 12px; height: 12px; margin: 0; flex-shrink: 0;
    cursor: pointer; accent-color: var(--primary);
}

/* ── Toggle buttons ──────────────────────────────────────── */
.btn-tgl {
    font-size: .71rem; padding: .2em .7em; line-height: 1.6;
    border: 1.5px solid var(--border); border-radius: 2rem;
    background: #fff; color: var(--text-muted);
    cursor: pointer; transition: all .15s; white-space: nowrap;
}
.btn-tgl:hover     { border-color: var(--primary); color: var(--primary); }
.btn-tgl.is-all    { border-color: var(--primary); color: var(--primary); background: rgba(139,26,26,.07); }
.btn-tgl-lg        { font-size: .76rem; padding: .25em .9em; }

/* ── Responsive ──────────────────────────────────────────── */
@media (max-width: 991.98px) {
    .rp-sticky { position: static; }
    .rp-perm-head { flex-direction: column; align-items: flex-start; }
    .rp-head-actions { width: 100%; }
    .rp-srch-inp { width: 100% !important; }
}
</style>
@endpush

@push('scripts')
<script>
(function () {

    var totalChk = $('.perm-chk').length;

    // ── Initialise ──────────────────────────────────────────
    syncAll();
    $('#permCountBadge').text(totalChk + ' total');

    // ── Master sync: count, bar, button labels ──────────────
    function syncAll() {
        syncCount();
        syncModBtns();
        syncSecBtns();
        syncGlobalBtn();
    }

    function syncCount() {
        var n = $('.perm-chk:checked').length;
        $('#selCount').text(n + ' / ' + totalChk);
        $('#selBar').css('width', totalChk ? (n / totalChk * 100) + '%' : '0%');
    }

    function syncModBtns() {
        $('.btn-tgl-mod').each(function () {
            var all  = $('.perm-chk[data-group="' + $(this).data('group') + '"]');
            var nAll = all.length > 0 && all.filter(':checked').length === all.length;
            $(this).text(nAll ? 'None' : 'All').toggleClass('is-all', nAll);
        });
    }

    function syncSecBtns() {
        $('.btn-tgl-sec').each(function () {
            var all  = $('.perm-chk[data-sec="' + $(this).data('sec') + '"]');
            var nAll = all.length > 0 && all.filter(':checked').length === all.length;
            $(this).text(nAll ? 'Deselect Section' : 'Select Section').toggleClass('is-all', nAll);
        });
    }

    function syncGlobalBtn() {
        var all  = $('.perm-chk');
        if (!all.length) return;
        var nAll = all.filter(':checked').length === all.length;
        $('#btnGlobalToggle').text(nAll ? 'Deselect All' : 'Select All').toggleClass('is-all', nAll);
    }

    // ── Checkbox interaction ────────────────────────────────
    $(document).on('change', '.perm-chk', function () { syncAll(); });

    // ── Module toggle ───────────────────────────────────────
    $(document).on('click', '.btn-tgl-mod', function () {
        var all  = $('.perm-chk[data-group="' + $(this).data('group') + '"]');
        var nAll = all.length > 0 && all.filter(':checked').length === all.length;
        all.prop('checked', !nAll);
        syncAll();
    });

    // ── Section toggle ──────────────────────────────────────
    $(document).on('click', '.btn-tgl-sec', function () {
        var all  = $('.perm-chk[data-sec="' + $(this).data('sec') + '"]');
        var nAll = all.length > 0 && all.filter(':checked').length === all.length;
        all.prop('checked', !nAll);
        syncAll();
    });

    // ── Global toggle ───────────────────────────────────────
    $('#btnGlobalToggle').on('click', function () {
        var all  = $('.perm-chk');
        var nAll = all.length > 0 && all.filter(':checked').length === all.length;
        all.prop('checked', !nAll);
        syncAll();
    });

    // ── Permission search / filter ──────────────────────────
    var srchTimer;
    $('#permSearch').on('input', function () {
        clearTimeout(srchTimer);
        var val = $.trim(this.value).toLowerCase();
        srchTimer = setTimeout(function () { applyFilter(val); }, 160);
    });

    function applyFilter(q) {
        if (!q) {
            $('.perm-chip, .perm-mod-col, .perm-sec-blk').show();
            $('#noPermResults').addClass('d-none');
            return;
        }

        // chips
        $('.perm-chip').each(function () {
            var match = ($(this).data('label') || '').indexOf(q) !== -1
                     || ($(this).data('mod')   || '').indexOf(q) !== -1;
            $(this).toggle(match);
        });

        // module columns — hide if all chips hidden
        $('.perm-mod-col').each(function () {
            $(this).toggle($(this).find('.perm-chip:visible').length > 0);
        });

        // section blocks — hide if all module columns hidden; track any visible
        var anyVisible = false;
        $('.perm-sec-blk').each(function () {
            var vis = $(this).find('.perm-mod-col:visible').length > 0;
            $(this).toggle(vis);
            if (vis) anyVisible = true;
        });

        $('#noPermResults').toggleClass('d-none', anyVisible);
        $('#noResultTerm').text(q);
    }

    // ── Remove is-invalid on typing ─────────────────────────
    $('#roleName').on('input', function () { $(this).removeClass('is-invalid'); });
    $('#roleKey').on('input', function () { $(this).removeClass('is-invalid'); });

    // ── Unique-key slug generation ───────────────────────────
    var keyManuallyEdited = $('#roleKey').val() !== '';

    function toSlug(str) {
        return str.toLowerCase()
                  .replace(/[^a-z0-9]+/g, '-')
                  .replace(/^-+|-+$/g, '');
    }

    $('#roleName').on('input', function () {
        if (!keyManuallyEdited) {
            $('#roleKey').val(toSlug(this.value));
        }
    });

    $('#roleKey').on('input', function () {
        // Sanitise in real-time: keep only a-z 0-9 -
        var clean = this.value.toLowerCase().replace(/[^a-z0-9\-]/g, '');
        if (this.value !== clean) { this.value = clean; }
        keyManuallyEdited = clean !== '';
    });

    $('#rp-key-reset').on('click', function () {
        $('#roleKey').val(toSlug($('#roleName').val()));
        keyManuallyEdited = false;
    });

    // ── Form submit guard ───────────────────────────────────
    $('#rolePageForm').on('submit', function () {
        if (!$.trim($('#roleName').val())) {
            $('#roleName').addClass('is-invalid').focus();
            return false;
        }
        $('#saveBtnText').addClass('d-none');
        $('#saveBtnLoad').removeClass('d-none');
        $('#saveBtn').prop('disabled', true);
    });

})();
</script>
@endpush
