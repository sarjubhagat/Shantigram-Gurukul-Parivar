<?php $__env->startSection('title', 'Video Library'); ?>
<?php $__env->startSection('page-title', 'Video Library'); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item active">Video Library</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-actions'); ?>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('video-library-create')): ?>
    <button class="btn btn-primary btn-sm" id="btnUploadVideo" type="button">
        <i class="bi bi-cloud-upload me-1"></i> Upload Video
    </button>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>


<div class="uc-card">
    <div class="card-header uc-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <div class="uc-left">
            <i class="bi bi-film" style="font-size:1rem;color:var(--primary);"></i>
            <span class="uc-title">Video Library</span>
            <span class="uc-count" id="vlCountBadge">—</span>
        </div>
        <div class="uc-search-box">
            <i class="bi bi-search uc-search-ico"></i>
            <input type="text" class="uc-search-inp" id="ucSearch" placeholder="Search title or key…">
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="vlTable" class="table mb-0" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:130px;">ID / Key</th>
                        <th style="width:110px;">Thumbnail</th>
                        <th>Title</th>
                        <th>Tags</th>
                        <th class="text-center" style="width:120px;">Status</th>
                        <th class="text-center" style="width:90px;">Duration</th>
                        <th class="text-center" style="width:90px;">Size</th>
                        <th class="text-center" style="width:120px;">Qualities</th>
                        <th class="text-center" style="width:110px;">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>


<div class="modal fade" id="vlUploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:var(--r-lg);overflow:hidden;">

            <div class="modal-header px-4 py-3"
                 style="background:linear-gradient(135deg,var(--primary),var(--primary-mid,#a83232));border-bottom:none;">
                <h5 class="modal-title text-white fw-600">
                    <i class="bi bi-cloud-upload me-2"></i>Upload New Video
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body px-4 pt-4 pb-3">
                <div id="vlUploadAlert" class="alert alert-danger d-none mb-3" role="alert"></div>

                <div class="mb-3">
                    <label class="form-label" for="vlTitle">
                        Title <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="vlTitle"
                           placeholder="e.g. Introduction to React Hooks" maxlength="255">
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        Tags <span class="text-muted" style="font-weight:400;">(Enter or comma to add, Backspace removes last)</span>
                    </label>
                    <div class="al-ti-wrap" id="vlTagsWrap">
                        <input type="text" class="al-ti-inp" id="vlTagsInp" placeholder="Type a tag and press Enter…" autocomplete="off">
                    </div>
                    <input type="hidden" id="vlTags">
                </div>

                <div class="mb-3">
                    <label class="form-label">Video file <span class="text-danger">*</span></label>
                    <label class="al-dropzone" id="vlDropzone" for="vlFile">
                        <div class="empty-state">
                            <i class="bi bi-cloud-upload al-dz-icon"></i>
                            <div class="mt-2 fw-semibold" style="color:var(--primary);">Click or drag a video file here</div>
                            <div class="text-muted small mt-1">MP4 · MOV · MKV · up to 5 GB</div>
                        </div>
                        <div class="selected-state">
                            <div class="al-dz-sel-icon"><i class="bi bi-film"></i></div>
                            <div class="al-dz-sel-body">
                                <div class="al-dz-sel-name" id="vlSelectedName">—</div>
                                <div class="al-dz-sel-size" id="vlSelectedSize">—</div>
                                <span class="al-dz-change">Change file</span>
                            </div>
                        </div>
                    </label>
                    <input type="file" id="vlFile" accept="video/*" style="display:none;">
                </div>

                <div class="al-progress-wrap" id="vlProgressWrap">
                    <div class="al-progress"><div class="al-progress-bar" id="vlProgressBar"></div></div>
                    <div class="al-progress-meta">
                        <span id="vlProgressPct">0 %</span>
                        <span id="vlProgressSpeed">—</span>
                    </div>
                </div>
            </div>

            <div class="modal-footer px-4 py-3" style="border-top:1px solid var(--border-light);">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="vlStartBtn" disabled style="min-width:110px;">
                    <span class="spinner-border spinner-border-sm d-none me-1" id="vlSpinner"></span>
                    <span id="vlBtnText">Upload</span>
                </button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="vlEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:var(--r-lg);overflow:hidden;">

            <div class="modal-header px-4 py-3"
                 style="background:linear-gradient(135deg,var(--primary),var(--primary-mid,#a83232));border-bottom:none;">
                <h5 class="modal-title text-white fw-600">
                    <i class="bi bi-pencil-square me-2"></i>Edit Video
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body px-4 pt-4 pb-3">
                <div id="vlEditAlert" class="alert alert-danger d-none mb-3" role="alert"></div>
                <input type="hidden" id="editVideoId">

                <div class="mb-3">
                    <label class="form-label" for="editVideoTitle">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="editVideoTitle"
                           placeholder="Video title" maxlength="255">
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        Tags <span class="text-muted" style="font-weight:400;">(Enter or comma to add, Backspace removes last)</span>
                    </label>
                    <div class="al-ti-wrap" id="editVideoTagsWrap">
                        <input type="text" class="al-ti-inp" id="editVideoTagsInp" placeholder="Type a tag and press Enter…" autocomplete="off">
                    </div>
                    <input type="hidden" id="editVideoTags">
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        Replace file <span class="text-muted" style="font-weight:400;">(optional)</span>
                    </label>
                    <label class="al-dropzone al-dropzone-sm" id="editVideoDropzone" for="editVideoFile">
                        <div class="empty-state">
                            <i class="bi bi-film" style="font-size:1.6rem;color:var(--primary);"></i>
                            <div class="mt-1 small fw-semibold" style="color:var(--primary);">Click or drag a replacement video</div>
                            <div class="text-muted" style="font-size:.72rem;">Leave empty to keep the current file</div>
                        </div>
                        <div class="selected-state">
                            <div class="al-dz-sel-icon"><i class="bi bi-film"></i></div>
                            <div class="al-dz-sel-body">
                                <div class="al-dz-sel-name" id="editVideoSelectedName">—</div>
                                <div class="al-dz-sel-size" id="editVideoSelectedSize">—</div>
                                <span class="al-dz-change">Change file</span>
                            </div>
                        </div>
                    </label>
                    <input type="file" id="editVideoFile" accept="video/*" style="display:none;">
                </div>

                <div class="al-progress-wrap" id="editVideoProgressWrap">
                    <div class="al-progress"><div class="al-progress-bar" id="editVideoProgressBar"></div></div>
                    <div class="al-progress-meta">
                        <span id="editVideoProgressPct">0 %</span>
                        <span id="editVideoProgressSpeed">—</span>
                    </div>
                </div>
            </div>

            <div class="modal-footer px-4 py-3" style="border-top:1px solid var(--border-light);">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="editVideoSaveBtn" style="min-width:90px;">
                    <span class="spinner-border spinner-border-sm d-none me-1" id="editVideoSpinner"></span>
                    <span id="editVideoBtnText">Save</span>
                </button>
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
/* ── Card shell ──────────────────────────────────────────────── */
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

/* ── DataTable footer ────────────────────────────────────────── */
.dt-foot { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.5rem; padding:.7rem 1.25rem; background:#FAFAFA; border-top:1.5px solid var(--border-light); border-radius:0 0 var(--r-lg) var(--r-lg); }
.dt-foot .dataTables_length { flex-shrink:0; }
.dt-foot .dataTables_info { flex:1; text-align:center; }
.dt-foot .dataTables_paginate { flex-shrink:0; }

/* ── Pagination icons ────────────────────────────────────────── */
#vlTable_paginate .paginate_button.first,
#vlTable_paginate .paginate_button.previous,
#vlTable_paginate .paginate_button.next,
#vlTable_paginate .paginate_button.last { width:32px !important; height:32px !important; min-width:32px !important; padding:0 !important; display:inline-flex !important; align-items:center !important; justify-content:center !important; font-size:.85rem !important; line-height:1 !important; }
#vlTable_paginate .paginate_button { vertical-align:middle !important; }

/* ── Status badges ───────────────────────────────────────────── */
.vl-status { display:inline-flex; align-items:center; gap:6px; padding:3px 10px; border-radius:999px; font-size:.72rem; font-weight:500; }
.vl-status .dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.vl-status.vl-pending    { background:#fef3c7; color:#92400e; } .vl-status.vl-pending .dot    { background:#f59e0b; }
.vl-status.vl-processing { background:#dbeafe; color:#1e40af; } .vl-status.vl-processing .dot { background:#3b82f6; animation:vlPulse 1.2s infinite; }
.vl-status.vl-active     { background:#dcfce7; color:#166534; } .vl-status.vl-active .dot     { background:#22c55e; }
.vl-status.vl-failed     { background:#fee2e2; color:#991b1b; } .vl-status.vl-failed .dot     { background:#ef4444; }
@keyframes vlPulse { 0%,100%{opacity:1;} 50%{opacity:.3;} }

/* ── Tag chips ───────────────────────────────────────────────── */
.vl-tag { display:inline-block; font-size:.68rem; padding:2px 8px; border-radius:6px; background:rgba(139,26,26,.08); color:var(--primary); margin:1px 2px 1px 0; }

/* ── Action buttons ──────────────────────────────────────────── */
.vl-btn-play  { border:1.5px solid var(--border); background:#fff; border-radius:var(--r-sm); color:#198754; transition:all .18s; }
.vl-btn-edit  { border:1.5px solid var(--border); background:#fff; border-radius:var(--r-sm); color:var(--primary); transition:all .18s; }
.vl-btn-dl    { border:1.5px solid var(--border); background:#fff; border-radius:var(--r-sm); color:#0d6efd; transition:all .18s; }
.vl-btn-del   { border:1.5px solid var(--border); background:#fff; border-radius:var(--r-sm); color:var(--accent); transition:all .18s; }
.vl-btn-play:hover { background:rgba(25,135,84,.07); border-color:#198754; }
.vl-btn-edit:hover { background:rgba(139,26,26,.07); border-color:var(--primary); }
.vl-btn-dl:hover   { background:rgba(13,110,253,.07); border-color:#0d6efd; }
.vl-btn-del:hover  { background:rgba(192,57,43,.07); border-color:var(--accent); }

/* ── TUS dropzone ────────────────────────────────────────────── */
.al-dropzone { position:relative; border:2px dashed var(--border); border-radius:var(--r); padding:24px; text-align:center; cursor:pointer; transition:all .2s; display:block; }
.al-dropzone:hover,.al-dropzone.drag { border-color:var(--primary); background:rgba(139,26,26,.04); }
.al-dz-icon { font-size:2.2rem; color:var(--primary); }
.al-dropzone.has-file { border-style:solid; border-color:#22c55e; background:#f0fdf4; text-align:left; padding:14px 16px; }
.al-dropzone.has-file .empty-state { display:none; }
.al-dropzone .selected-state { display:none; align-items:center; gap:12px; }
.al-dropzone.has-file .selected-state { display:flex; }
.al-dz-sel-icon { width:40px; height:40px; border-radius:var(--r-sm); background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
.al-dz-sel-body { flex:1; min-width:0; }
.al-dz-sel-name { font-weight:600; color:#064e3b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.al-dz-sel-size { font-size:.75rem; color:#15803d; }
.al-dz-change   { font-size:.72rem; color:#15803d; text-decoration:underline; }
.al-dropzone-sm { padding:14px 16px; }

/* ── Upload progress ─────────────────────────────────────────── */
.al-progress-wrap      { display:none; margin-top:14px; }
.al-progress-wrap.show { display:block; }
.al-progress           { height:10px; border-radius:999px; background:var(--border); overflow:hidden; }
.al-progress-bar       { height:100%; background:linear-gradient(90deg,var(--primary),var(--primary-mid,#a83232)); transition:width .3s; width:0; }
.al-progress-meta      { font-size:.75rem; color:var(--text-muted); margin-top:6px; display:flex; justify-content:space-between; }

/* ── Tag chip input ──────────────────────────────────────────── */
.al-ti-wrap { display:flex; flex-wrap:wrap; gap:5px; align-items:center; border:1.5px solid var(--border); border-radius:var(--r-sm,6px); padding:6px 10px; background:#fff; cursor:text; min-height:42px; transition:border-color .2s,box-shadow .2s; }
.al-ti-wrap:focus-within { border-color:var(--primary); box-shadow:0 0 0 3px rgba(139,26,26,.09); }
.al-ti-chip { display:inline-flex; align-items:center; gap:3px; background:rgba(139,26,26,.1); color:var(--primary); border-radius:4px; font-size:.74rem; padding:3px 7px 3px 9px; font-weight:500; line-height:1; }
.al-ti-chip-rm { cursor:pointer; opacity:.55; font-size:.78rem; line-height:1; padding:0 1px; font-weight:700; }
.al-ti-chip-rm:hover { opacity:1; }
.al-ti-inp { border:none; outline:none; font-size:.84rem; flex:1; min-width:90px; padding:2px 0; background:transparent; color:var(--text); }
.al-ti-inp::placeholder { color:var(--text-muted); }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<?php
    $videoLibraryConfig = [
        'tusUrl'       => url('/admin/video-library/tus'),
        'completeUrl'  => route('admin.video-library.complete'),
        'videoApiBase' => rtrim(url('/admin/video-library'), '/'),
        'gridUrl'      => route('admin.video-library.get_data'),
        'deleteUrl'    => route('admin.video-library.delete'),
        'csrf'         => csrf_token(),
    ];
?>
<script>
window.VL = <?php echo json_encode($videoLibraryConfig, 15, 512) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/tus-js-client@4.1.0/dist/tus.min.js"></script>
<script src="<?php echo e(asset('assets/admin/js/video-library.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\projects\serve\resources\views/admin/video-library/index.blade.php ENDPATH**/ ?>