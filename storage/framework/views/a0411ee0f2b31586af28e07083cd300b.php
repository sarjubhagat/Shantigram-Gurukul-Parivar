<?php $__env->startSection('title', 'Image Library'); ?>
<?php $__env->startSection('page-title', 'Image Library'); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item active">Image Library</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-actions'); ?>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('image-library-create')): ?>
        <button type="button" class="btn btn-primary btn-sm" id="ilOpenBulkUpload" data-bs-toggle="modal" data-bs-target="#ilModalBulk">
            <i class="bi bi-images me-1"></i> Bulk upload
        </button>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php
    $ilConfig = [
        'galleryUrl'       => route('admin.' . config('module.name') . '.gallery'),
        'galleryIdsUrl'    => route('admin.' . config('module.name') . '.gallery.ids'),
        'bulkUrl'          => route('admin.' . config('module.name') . '.bulk'),
        'bulkDestroyUrl'   => route('admin.' . config('module.name') . '.bulk_destroy'),
        'bulkDownloadUrl'  => route('admin.' . config('module.name') . '.bulk_download'),
        'apiBase'          => rtrim(url('/admin/' . config('module.name')), '/'),
        'csrf'             => csrf_token(),
        'canCreate'        => auth()->user()->can('image-library-create'),
        'canEdit'          => auth()->user()->can('image-library-edit'),
        'canDelete'        => auth()->user()->can('image-library-delete'),
        'canDownload'      => auth()->user()->can('image-library-download'),
    ];
?>

<div class="card uc-card mb-3">
    <div class="card-header uc-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="uc-left">
            <span class="card-header-icon"><i class="bi bi-images"></i></span>
            <span class="uc-title">Gallery</span>
            <span class="uc-count" id="ilTotalBadge">—</span>
        </span>
        <div class="d-flex align-items-center gap-3 flex-wrap il-header-tools">
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="ilShowDeleted" role="switch">
                <label class="form-check-label small" for="ilShowDeleted">Show deleted</label>
            </div>
            <div class="uc-search-box">
                <i class="bi bi-search uc-search-ico"></i>
                <input type="search" id="ilSearch" class="uc-search-inp"
                       placeholder="Title, key, file name…" autocomplete="off">
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="il-toolbar-bulk mb-2" id="ilBulkBar">
            <span class="small text-muted"><span id="ilSelectedCount">0</span> selected</span>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('image-library-download')): ?>
                <button type="button" class="btn btn-sm btn-outline-primary" id="ilBulkDownload">
                    <i class="bi bi-download me-1"></i> Download selected
                </button>
            <?php endif; ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('image-library-delete')): ?>
                <button type="button" class="btn btn-sm btn-outline-danger" id="ilBulkDelete">Remove selected</button>
            <?php endif; ?>
            <button type="button" class="btn btn-sm btn-light" id="ilClearSelection">Clear</button>
        </div>
        <?php if(auth()->user()->can('image-library-download') || auth()->user()->can('image-library-delete')): ?>
        <div class="il-select-bar mb-3" id="ilSelectBar">
            <label class="il-select-all-label" for="ilSelectAll">
                <input type="checkbox" id="ilSelectAll" class="il-select-all-input">
                <span class="il-select-all-box" aria-hidden="true"></span>
                <span id="ilSelectAllText">Select all</span>
            </label>
            <span class="il-select-all-hint small text-muted" id="ilSelectAllHint"></span>
        </div>
        <?php endif; ?>
        <div id="ilGallery" class="il-gallery"></div>
        <div class="il-loading d-none" id="ilLoading"><i class="bi bi-arrow-repeat spin"></i> Loading…</div>
        <div class="il-empty d-none" id="ilEmpty">
            <i class="bi bi-image"></i>
            <p class="mb-0">No images match your search.</p>
        </div>
        <div class="il-sentinel" id="ilSentinel" aria-hidden="true"></div>
    </div>
</div>


<div class="modal fade" id="ilModalView" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl il-view-dialog">
        <div class="modal-content il-view-content">
            <div class="modal-header il-view-header">
                <div class="il-view-head-text">
                    <h5 class="modal-title mb-0" id="ilViewTitle">—</h5>
                    <div class="il-view-sub" id="ilViewMeta">—</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('image-library-download')): ?>
                        <a href="#" class="btn btn-sm btn-light" id="ilViewDownload" target="_blank" rel="noopener">
                            <i class="bi bi-download me-1"></i> Download
                        </a>
                    <?php endif; ?>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body il-view-body p-0">
                <div class="il-view-stage">
                    <img src="" alt="" id="ilViewImg" class="il-view-img">
                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="ilModalBulk" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border:none;border-radius:var(--r-lg);overflow:hidden;box-shadow:var(--sh-lg);">
            <form id="ilBulkForm" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="modal-header px-4 py-3"
                     style="background:linear-gradient(135deg,var(--primary) 0%,var(--primary-mid) 100%);border:none;">
                    <h5 class="modal-title text-white mb-0" style="font-weight:700;font-size:.97rem;">
                        <i class="bi bi-cloud-upload me-2"></i>Bulk upload
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <p class="small text-muted">All files in this batch share the same <strong>title</strong> and <strong>tags</strong> (good for one gallery / album).</p>
                    <div class="mb-3">
                        <label class="form-label">Gallery title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required maxlength="255" placeholder="e.g. Workshop April 2026">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tags (optional)</label>
                        <input type="text" name="tags" class="form-control" maxlength="5000" placeholder="comma separated">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Images <span class="text-danger">*</span></label>
                        <input type="file" name="files[]" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp" multiple required>
                        <div class="form-text">JPEG, PNG, GIF, WebP — up to <?php echo e((int) config('image.bulk.max_files', 40)); ?> files, 10 MB each.</div>
                    </div>
                    <div class="alert alert-danger py-2 d-none mt-3" id="ilBulkError"></div>
                </div>
                <div class="modal-footer px-4 py-3" style="border-top:1px solid var(--border-light);">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="ilBulkSubmit">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="ilModalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:none;border-radius:var(--r-lg);overflow:hidden;box-shadow:var(--sh-lg);">
            <form id="ilEditForm" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" id="ilEditId">
                <div class="modal-header px-4 py-3"
                     style="background:linear-gradient(135deg,var(--primary) 0%,var(--primary-mid) 100%);border:none;">
                    <h5 class="modal-title text-white mb-0" style="font-weight:700;font-size:.97rem;">
                        <i class="bi bi-pencil-square me-2"></i>Edit image
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div class="text-center mb-3">
                        <img src="" alt="" id="ilEditPreview" class="rounded border" style="max-height:180px;max-width:100%;object-fit:contain;">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" id="ilEditTitle" class="form-control" required maxlength="255">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Tags</label>
                        <input type="text" name="tags" id="ilEditTags" class="form-control" maxlength="5000" placeholder="comma separated">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Alt text</label>
                        <input type="text" name="alt_text" id="ilEditAlt" class="form-control" maxlength="500">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Sort order</label>
                        <input type="number" name="sort_order" id="ilEditSort" class="form-control" step="any" value="0">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Replace file (optional)</label>
                        <input type="file" name="file" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp">
                    </div>
                    <div class="alert alert-danger py-2 d-none mt-3" id="ilEditError"></div>
                </div>
                <div class="modal-footer px-4 py-3" style="border-top:1px solid var(--border-light);">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
/* ── Card shell (match PDF / audio library) ─────────────────── */
.uc-card { border-radius:var(--r-lg); box-shadow:0 2px 12px rgba(139,26,26,.07),0 1px 3px rgba(0,0,0,.05); overflow:hidden; }
.uc-header { padding:.82rem 1.25rem !important; border-bottom:1.5px solid var(--border-light) !important; }
.uc-left { display:flex; align-items:center; gap:.55rem; }
.uc-title { font-size:.9rem; font-weight:700; color:var(--text); }
.uc-count { background:rgba(139,26,26,.09); color:var(--primary); border-radius:2rem; padding:.18em .72em; font-size:.71rem; font-weight:700; letter-spacing:.2px; }

/* ── Search box ──────────────────────────────────────────────── */
.uc-search-box { position:relative; }
.uc-search-ico { position:absolute; left:.75rem; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:.78rem; pointer-events:none; }
.uc-search-inp { border:1.5px solid var(--border); border-radius:2rem; padding:.38rem 1rem .38rem 2.1rem; font-size:.82rem; font-family:inherit; color:var(--text); background:var(--bg); outline:none; width:210px; transition:border-color .2s,box-shadow .2s,width .25s; }
.uc-search-inp:focus { border-color:var(--primary); background:#fff; box-shadow:0 0 0 3px rgba(139,26,26,.09); width:255px; }
.uc-search-inp::placeholder { color:var(--text-muted); }

/* ── Show-deleted switch ─────────────────────────────────────── */
#ilShowDeleted:checked { background-color:var(--primary); border-color:var(--primary); }
.il-header-tools { display:flex; align-items:center; gap:1rem; flex-wrap:wrap; }
.il-header-tools .form-check-label { color:var(--text-muted); font-size:.8rem; user-select:none; }

/* ── Bulk toolbar ────────────────────────────────────────────── */
.il-toolbar-bulk {
    display:none; align-items:center; gap:.65rem; flex-wrap:wrap;
    padding:.55rem .85rem; border-radius:var(--r-sm);
    background:rgba(139,26,26,.06); border:1px solid rgba(139,26,26,.12);
}
.il-toolbar-bulk.show { display:flex; }

/* ── Select all bar ──────────────────────────────────────────── */
.il-select-bar {
    display:flex; align-items:center; gap:.85rem; flex-wrap:wrap;
    padding:.55rem .85rem; border-radius:var(--r-sm);
    background:#FAFAFA; border:1.5px solid var(--border-light);
}
.il-select-all-label {
    display:inline-flex; align-items:center; gap:.5rem;
    margin:0; cursor:pointer; user-select:none;
    font-size:.82rem; font-weight:600; color:var(--text);
}
.il-select-all-input {
    position:absolute; opacity:0; width:0; height:0; pointer-events:none;
}
.il-select-all-box {
    display:inline-flex; align-items:center; justify-content:center;
    width:18px; height:18px; border-radius:5px;
    border:2px solid var(--border); background:#fff;
    transition:background .15s, border-color .15s;
    flex-shrink:0;
}
.il-select-all-input:checked + .il-select-all-box,
.il-select-all-input:indeterminate + .il-select-all-box {
    background:var(--primary); border-color:var(--primary);
}
.il-select-all-input:checked + .il-select-all-box::after {
    content:''; display:block;
    width:5px; height:9px;
    border:solid #fff; border-width:0 2px 2px 0;
    transform:rotate(45deg); margin-top:-1px;
}
.il-select-all-input:indeterminate + .il-select-all-box::after {
    content:''; display:block;
    width:10px; height:2px; background:#fff; border-radius:1px;
}
.il-select-all-hint { font-size:.75rem; }

/* ── Gallery grid ────────────────────────────────────────────── */
.il-gallery {
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(168px, 1fr));
    gap:1rem;
}
@media (min-width:1200px) {
    .il-gallery { grid-template-columns:repeat(auto-fill, minmax(190px, 1fr)); gap:1.1rem; }
}

/* ── Image tile ──────────────────────────────────────────────── */
.il-card {
    position:relative;
    border-radius:var(--r-md);
    overflow:hidden;
    border:1.5px solid var(--border-light);
    background:#f3f4f6;
    aspect-ratio:1;
    cursor:pointer;
    transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease;
}
.il-card:hover {
    transform:translateY(-3px);
    box-shadow:0 10px 28px rgba(139,26,26,.12), 0 4px 10px rgba(0,0,0,.08);
    border-color:rgba(139,26,26,.25);
}
.il-card.is-selected {
    border-color:var(--primary);
    box-shadow:0 0 0 3px rgba(139,26,26,.18), 0 8px 22px rgba(139,26,26,.14);
}
.il-card.il-deleted { opacity:.62; filter:grayscale(.35); }
.il-card.il-deleted:hover { transform:none; }

.il-card-media {
    position:absolute; inset:0; z-index:0;
    background:linear-gradient(145deg, #ececec, #f8f8f8);
    cursor:zoom-in;
}
.il-card-media img {
    width:100%; height:100%; object-fit:cover; display:block;
    transition:transform .35s ease;
}
.il-card:hover .il-card-media img { transform:scale(1.04); }

/* ── Custom checkbox (hidden until hover / selected) ───────────── */
.il-check-wrap {
    position:absolute; top:10px; left:10px; z-index:5;
    margin:0; opacity:0; pointer-events:none;
    transition:opacity .18s ease;
}
.il-card:hover .il-check-wrap,
.il-card.is-selected .il-check-wrap {
    opacity:1; pointer-events:auto;
}
.il-card-select {
    position:absolute; opacity:0; width:0; height:0; margin:0; pointer-events:none;
}
.il-check-ui {
    display:flex; align-items:center; justify-content:center;
    width:24px; height:24px; border-radius:7px;
    background:rgba(255,255,255,.94);
    border:2px solid rgba(255,255,255,.9);
    box-shadow:0 2px 10px rgba(0,0,0,.22);
    transition:background .15s, border-color .15s, transform .15s;
}
.il-check-wrap:hover .il-check-ui { transform:scale(1.06); }
.il-card-select:checked + .il-check-ui {
    background:var(--primary); border-color:var(--primary);
}
.il-card-select:checked + .il-check-ui::after {
    content:''; display:block;
    width:5px; height:10px;
    border:solid #fff; border-width:0 2.5px 2.5px 0;
    transform:rotate(45deg); margin-top:-2px;
}

.il-card-badge {
    position:absolute; top:10px; right:10px; z-index:5;
    font-size:.62rem; font-weight:700; letter-spacing:.03em; text-transform:uppercase;
    padding:3px 8px; border-radius:999px;
    background:rgba(75,85,99,.88); color:#fff;
    box-shadow:0 2px 8px rgba(0,0,0,.2);
}

/* ── Hover overlay ───────────────────────────────────────────── */
.il-card-overlay {
    position:absolute; inset:0; z-index:2;
    background:linear-gradient(to top,
        rgba(15,23,42,.94) 0%,
        rgba(15,23,42,.55) 38%,
        rgba(15,23,42,.08) 68%,
        transparent 100%);
    color:#fff;
    opacity:0;
    pointer-events:none;
    transition:opacity .22s ease;
    display:flex; flex-direction:column; justify-content:flex-end;
}
.il-card:hover .il-card-overlay {
    opacity:1;
    pointer-events:auto;
}
.il-card-overlay-inner {
    padding:.75rem .8rem .8rem;
    transform:translateY(6px);
    transition:transform .22s ease;
}
.il-card:hover .il-card-overlay-inner { transform:translateY(0); }

.il-card-title {
    font-weight:700; font-size:.8rem; line-height:1.3;
    margin-bottom:.25rem; word-break:break-word;
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
}
.il-card-meta {
    opacity:.88; font-size:.67rem; line-height:1.45;
    font-family:ui-monospace, monospace;
    word-break:break-all;
}
.il-card-actions {
    display:flex; gap:.4rem; margin-top:.55rem; flex-wrap:wrap;
}
.il-act-btn {
    display:inline-flex; align-items:center; gap:.25rem;
    padding:.28rem .55rem; font-size:.68rem; font-weight:600;
    border-radius:6px; border:1px solid rgba(255,255,255,.35);
    background:rgba(255,255,255,.14); color:#fff;
    backdrop-filter:blur(4px); cursor:pointer;
    transition:background .15s, border-color .15s;
}
.il-act-btn:hover { background:rgba(255,255,255,.28); border-color:rgba(255,255,255,.55); color:#fff; text-decoration:none; }
.il-act-btn.il-act-del:hover { background:rgba(220,38,38,.85); border-color:rgba(220,38,38,.95); }
a.il-act-btn { display:inline-flex; }

.il-sentinel { height:1px; margin:1rem 0; }
.il-loading { text-align:center; padding:2.5rem 1rem; color:var(--text-muted); font-size:.85rem; }
.il-empty { text-align:center; padding:3.5rem 1rem; color:var(--text-muted); }
.il-empty i { font-size:2.8rem; display:block; margin-bottom:.65rem; color:var(--border); opacity:.7; }
.spin { animation:il-spin 1s linear infinite; display:inline-block; }
@keyframes il-spin { to { transform:rotate(360deg); } }

/* ── Full image viewer ───────────────────────────────────────── */
.il-view-dialog { max-width:min(96vw, 1100px); }
.il-view-content {
    border:none; border-radius:var(--r-lg); overflow:hidden;
    background:#111; color:#fff; box-shadow:0 20px 60px rgba(0,0,0,.45);
}
.il-view-header {
    background:linear-gradient(135deg, #1a1a24 0%, #2d2d3a 100%);
    border-bottom:1px solid rgba(255,255,255,.08);
    padding:.85rem 1.1rem;
}
.il-view-head-text { min-width:0; flex:1; padding-right:.75rem; }
.il-view-header .modal-title {
    font-size:.92rem; font-weight:700; color:#fff;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.il-view-sub {
    font-size:.72rem; color:rgba(255,255,255,.65); margin-top:.15rem;
    font-family:ui-monospace, monospace;
}
.il-view-body { background:#0a0a0f; }
.il-view-stage {
    display:flex; align-items:center; justify-content:center;
    min-height:50vh; max-height:78vh; padding:1rem;
    overflow:auto;
}
.il-view-img {
    max-width:100%; max-height:76vh; width:auto; height:auto;
    object-fit:contain; display:block;
    border-radius:var(--r-sm);
    box-shadow:0 8px 32px rgba(0,0,0,.5);
}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>window.IL_CONFIG = <?php echo json_encode($ilConfig, 15, 512) ?>;</script>
<script src="<?php echo e(asset('assets/admin/js/image-library.js')); ?>?v=4"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\projects\serve\resources\views/admin/image-library/index.blade.php ENDPATH**/ ?>