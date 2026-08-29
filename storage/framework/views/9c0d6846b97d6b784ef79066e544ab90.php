<?php $__env->startSection('title', 'PDF Library'); ?>
<?php $__env->startSection('page-title', 'PDF Library'); ?>

<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item active">PDF Library</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-actions'); ?>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('pdf-library-create')): ?>
        <button class="btn btn-primary btn-sm" id="btnUploadPdf">
            <i class="bi bi-cloud-upload me-1"></i> Upload PDF
        </button>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>


<div class="card uc-card">

    <div class="card-header uc-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="uc-left">
            <span class="card-header-icon"><i class="bi bi-file-earmark-pdf"></i></span>
            <span class="uc-title">All PDFs</span>
            <span class="uc-count" id="plCountBadge">—</span>
        </span>
        <div class="d-flex align-items-center gap-3">
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="showDeletedToggle" role="switch">
                <label class="form-check-label small" for="showDeletedToggle"
                       style="color:var(--text-muted);font-size:.8rem;">Show deleted</label>
            </div>
            <div class="uc-search-box">
                <i class="bi bi-search uc-search-ico"></i>
                <input type="text" id="ucSearch" class="uc-search-inp"
                       placeholder="Search title / key…" autocomplete="off">
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table id="plTable" class="table dt-table mb-0" style="width:100%;">
            <thead>
                <tr>
                    <th style="width:150px;">ID / Key</th>
                    <th class="text-center" style="width:70px;">Preview</th>
                    <th>Title</th>
                    <th>Tags</th>
                    <th class="text-center" style="width:120px;">Status</th>
                    <th class="text-center" style="width:80px;">Pages</th>
                    <th class="text-center" style="width:85px;">Size</th>
                    <th class="text-center" style="width:110px;">Actions</th>
                </tr>
            </thead>
        </table>
    </div>

</div>


<div class="modal fade" id="plUploadModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:520px;">
        <div class="modal-content" style="border:none;border-radius:var(--r-lg);overflow:hidden;box-shadow:var(--sh-lg);">

            <div class="modal-header px-4 py-3"
                 style="background:linear-gradient(135deg,var(--primary) 0%,var(--primary-mid) 100%);border:none;">
                <h5 class="modal-title text-white mb-0" style="font-weight:700;font-size:.97rem;">
                    <i class="bi bi-cloud-upload me-2"></i>Upload PDF
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body px-4 py-3">
                <div id="plUploadAlert" class="alert alert-danger mb-3 d-none" role="alert"
                     style="border-radius:var(--r-sm);font-size:.84rem;padding:.65rem .9rem;"></div>

                <div class="mb-3">
                    <label class="form-label" for="plTitle">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="plTitle"
                           placeholder="e.g. Chapter 1 Narration" maxlength="255">
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        Tags <span class="text-muted" style="font-weight:400;">(Enter or comma to add, Backspace removes last)</span>
                    </label>
                    <div class="pl-ti-wrap" id="plTagsWrap">
                        <input type="text" class="pl-ti-inp" id="plTagsInp" placeholder="Type a tag and press Enter…" autocomplete="off">
                    </div>
                    <input type="hidden" id="plTags">
                </div>

                <div class="mb-3">
                    <label class="form-label">PDF file <span class="text-danger">*</span></label>
                    <label class="pl-dropzone" id="plDropzone" for="plFile">
                        <div class="empty-state">
                            <i class="bi bi-cloud-upload pl-dz-icon"></i>
                            <div class="mt-2 fw-semibold" style="color:var(--primary);">Click or drag a PDF here</div>
                            <div class="text-muted small mt-1">PDF only · up to 2 GB · linearized for fast viewing</div>
                        </div>
                        <div class="selected-state">
                            <div class="pl-dz-sel-icon"><i class="bi bi-file-earmark-pdf"></i></div>
                            <div class="pl-dz-sel-body">
                                <div class="pl-dz-sel-name" id="plSelectedName">—</div>
                                <div class="pl-dz-sel-size" id="plSelectedSize">—</div>
                                <span class="pl-dz-change">Change file</span>
                            </div>
                        </div>
                    </label>
                    <input type="file" id="plFile" accept="application/pdf,.pdf" style="display:none;">
                </div>

                <div class="pl-progress-wrap" id="plProgressWrap">
                    <div class="pl-progress"><div class="pl-progress-bar" id="plProgressBar"></div></div>
                    <div class="pl-progress-meta">
                        <span id="plProgressPct">0 %</span>
                        <span id="plProgressSpeed">—</span>
                    </div>
                </div>
            </div>

            <div class="modal-footer px-4 py-3" style="border-top:1px solid var(--border-light);">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="plStartBtn" disabled style="min-width:110px;">
                    <span class="spinner-border spinner-border-sm d-none me-1" id="plSpinner"></span>
                    <span id="plBtnText">Upload</span>
                </button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="plEditModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:520px;">
        <div class="modal-content" style="border:none;border-radius:var(--r-lg);overflow:hidden;box-shadow:var(--sh-lg);">

            <div class="modal-header px-4 py-3"
                 style="background:linear-gradient(135deg,var(--primary) 0%,var(--primary-mid) 100%);border:none;">
                <h5 class="modal-title text-white mb-0" style="font-weight:700;font-size:.97rem;">
                    <i class="bi bi-pencil-square me-2"></i>Edit PDF
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body px-4 py-3">
                <div id="plEditAlert" class="alert alert-danger mb-3 d-none" role="alert"
                     style="border-radius:var(--r-sm);font-size:.84rem;padding:.65rem .9rem;"></div>

                <input type="hidden" id="editPdfId">

                <div class="mb-3">
                    <label class="form-label" for="editTitle">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="editTitle"
                           placeholder="Track title" maxlength="255">
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        Tags <span class="text-muted" style="font-weight:400;">(Enter or comma to add, Backspace removes last)</span>
                    </label>
                    <div class="pl-ti-wrap" id="editTagsWrap">
                        <input type="text" class="pl-ti-inp" id="editTagsInp" placeholder="Type a tag and press Enter…" autocomplete="off">
                    </div>
                    <input type="hidden" id="editTags">
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        Replace file <span class="text-muted" style="font-weight:400;">(optional)</span>
                    </label>
                    <label class="pl-dropzone pl-dropzone-sm" id="editDropzone" for="editFile">
                        <div class="empty-state">
                            <i class="bi bi-file-earmark-pdf" style="font-size:1.6rem;color:var(--primary);"></i>
                            <div class="mt-1 small fw-semibold" style="color:var(--primary);">Click or drag a replacement PDF</div>
                            <div class="text-muted" style="font-size:.72rem;">Leave empty to keep the current file</div>
                        </div>
                        <div class="selected-state">
                            <div class="pl-dz-sel-icon"><i class="bi bi-file-earmark-pdf"></i></div>
                            <div class="pl-dz-sel-body">
                                <div class="pl-dz-sel-name" id="editSelectedName">—</div>
                                <div class="pl-dz-sel-size" id="editSelectedSize">—</div>
                                <span class="pl-dz-change">Change file</span>
                            </div>
                        </div>
                    </label>
                    <input type="file" id="editFile" accept="application/pdf,.pdf" style="display:none;">
                </div>

                <div class="pl-progress-wrap" id="editProgressWrap">
                    <div class="pl-progress"><div class="pl-progress-bar" id="editProgressBar"></div></div>
                    <div class="pl-progress-meta">
                        <span id="editProgressPct">0 %</span>
                        <span id="editProgressSpeed">—</span>
                    </div>
                </div>
            </div>

            <div class="modal-footer px-4 py-3" style="border-top:1px solid var(--border-light);">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="editSaveBtn" style="min-width:90px;">
                    <span class="spinner-border spinner-border-sm d-none me-1" id="editSpinner"></span>
                    <span id="editBtnText">Save</span>
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
#plTable_paginate .paginate_button.first,
#plTable_paginate .paginate_button.previous,
#plTable_paginate .paginate_button.next,
#plTable_paginate .paginate_button.last { width:32px !important; height:32px !important; min-width:32px !important; padding:0 !important; display:inline-flex !important; align-items:center !important; justify-content:center !important; font-size:.85rem !important; line-height:1 !important; }
#plTable_paginate .paginate_button { vertical-align:middle !important; }

/* ── Status badges ───────────────────────────────────────────── */
.pl-status { display:inline-flex; align-items:center; gap:6px; padding:3px 10px; border-radius:999px; font-size:.72rem; font-weight:500; }
.pl-status .dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.pl-status.pl-pending    { background:#fef3c7; color:#92400e; } .pl-status.pl-pending .dot    { background:#f59e0b; }
.pl-status.pl-processing { background:#dbeafe; color:#1e40af; } .pl-status.pl-processing .dot { background:#3b82f6; animation:plPulse 1.2s infinite; }
.pl-status.pl-active     { background:#dcfce7; color:#166534; } .pl-status.pl-active .dot     { background:#22c55e; }
.pl-status.pl-failed     { background:#fee2e2; color:#991b1b; } .pl-status.pl-failed .dot     { background:#ef4444; }
.pl-status.pl-deleted    { background:#f3f4f6; color:#4b5563; } .pl-status.pl-deleted .dot    { background:#9ca3af; }
@keyframes plPulse { 0%,100%{opacity:1;} 50%{opacity:.3;} }

/* ── Tag chips ───────────────────────────────────────────────── */
.pl-tag { display:inline-block; font-size:.68rem; padding:2px 8px; border-radius:6px; background:rgba(139,26,26,.08); color:var(--primary); margin:1px 2px 1px 0; }

/* ── Action buttons ──────────────────────────────────────────── */
.pl-btn-view  { border:1.5px solid var(--border); background:#fff; border-radius:var(--r-sm); color:#198754; transition:all .18s; }
.pl-btn-edit  { border:1.5px solid var(--border); background:#fff; border-radius:var(--r-sm); color:var(--primary); transition:all .18s; }
.pl-btn-dl    { border:1.5px solid var(--border); background:#fff; border-radius:var(--r-sm); color:#0d6efd; transition:all .18s; }
.pl-btn-del   { border:1.5px solid var(--border); background:#fff; border-radius:var(--r-sm); color:var(--accent); transition:all .18s; }
.pl-btn-view:hover { background:rgba(25,135,84,.07); border-color:#198754; }
.pl-btn-edit:hover { background:rgba(139,26,26,.07); border-color:var(--primary); }
.pl-btn-dl:hover   { background:rgba(13,110,253,.07); border-color:#0d6efd; }
.pl-btn-del:hover  { background:rgba(192,57,43,.07); border-color:var(--accent); }

/* ── TUS dropzone ────────────────────────────────────────────── */
.pl-dropzone { position:relative; border:2px dashed var(--border); border-radius:var(--r); padding:24px; text-align:center; cursor:pointer; transition:all .2s; display:block; }
.pl-dropzone:hover,.pl-dropzone.drag { border-color:var(--primary); background:rgba(139,26,26,.04); }
.pl-dz-icon { font-size:2.2rem; color:var(--primary); }
.pl-dropzone.has-file { border-style:solid; border-color:#22c55e; background:#f0fdf4; text-align:left; padding:14px 16px; }
.pl-dropzone.has-file .empty-state { display:none; }
.pl-dropzone .selected-state { display:none; align-items:center; gap:12px; }
.pl-dropzone.has-file .selected-state { display:flex; }
.pl-dz-sel-icon { width:40px; height:40px; border-radius:var(--r-sm); background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
.pl-dz-sel-body { flex:1; min-width:0; }
.pl-dz-sel-name { font-weight:600; color:#064e3b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.pl-dz-sel-size { font-size:.75rem; color:#15803d; }
.pl-dz-change   { font-size:.72rem; color:#15803d; text-decoration:underline; }
.pl-dropzone-sm { padding:14px 16px; }

/* ── Upload progress ─────────────────────────────────────────── */
.pl-progress-wrap     { display:none; margin-top:14px; }
.pl-progress-wrap.show{ display:block; }
.pl-progress          { height:10px; border-radius:999px; background:var(--border); overflow:hidden; }
.pl-progress-bar      { height:100%; background:linear-gradient(90deg,var(--primary),var(--primary-mid)); transition:width .3s; width:0; }
.pl-progress-meta     { font-size:.75rem; color:var(--text-muted); margin-top:6px; display:flex; justify-content:space-between; }

/* ── Show-deleted switch ─────────────────────────────────────── */
#showDeletedToggle:checked { background-color:var(--primary); border-color:var(--primary); }

/* ── Tag chip input ──────────────────────────────────────────── */
.pl-ti-wrap {
    display:flex; flex-wrap:wrap; gap:5px; align-items:center;
    border:1.5px solid var(--border); border-radius:var(--r-sm,6px);
    padding:6px 10px; background:#fff; cursor:text; min-height:42px;
    transition:border-color .2s,box-shadow .2s;
}
.pl-ti-wrap:focus-within {
    border-color:var(--primary);
    box-shadow:0 0 0 3px rgba(139,26,26,.09);
}
.pl-ti-chip {
    display:inline-flex; align-items:center; gap:3px;
    background:rgba(139,26,26,.1); color:var(--primary);
    border-radius:4px; font-size:.74rem; padding:3px 7px 3px 9px;
    font-weight:500; line-height:1;
}
.pl-ti-chip-rm {
    cursor:pointer; opacity:.55; font-size:.78rem; line-height:1;
    padding:0 1px; font-weight:700;
}
.pl-ti-chip-rm:hover { opacity:1; }
.pl-ti-inp {
    border:none; outline:none; font-size:.84rem;
    flex:1; min-width:90px; padding:2px 0;
    background:transparent; color:var(--text);
}
.pl-ti-inp::placeholder { color:var(--text-muted); }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<?php
    $pdfLibraryConfig = [
        'tusUrl'      => url('/admin/pdf-library/tus'),
        'completeUrl' => route('admin.pdf-library.complete'),
        'pdfApiBase'  => rtrim(url('/admin/pdf-library'), '/'),
        'gridUrl'     => route('admin.pdf-library.get_data'),
        'deleteUrl'   => route('admin.pdf-library.delete'),
        'csrf'        => csrf_token(),
    ];
?>
<script>
window.PL = <?php echo json_encode($pdfLibraryConfig, 15, 512) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/tus-js-client@4.1.0/dist/tus.min.js"></script>
<script src="<?php echo e(asset('assets/admin/js/pdf-library.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\projects\serve\resources\views/admin/pdf-library/index.blade.php ENDPATH**/ ?>