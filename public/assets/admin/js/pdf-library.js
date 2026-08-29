/**
 * PDF Library — DataTable (server-side), TUS upload, edit modal
 *
 * Requires window.PL set by the blade view:
 *   { tusUrl, completeUrl, pdfApiBase, gridUrl, deleteUrl, csrf }
 *
 * Also requires: jQuery, Bootstrap 5, SweetAlert2, tus-js-client, DataTables
 */
(function () {
    'use strict';

    /* ── Helpers ─────────────────────────────────────────────── */

    function fmtBytes(b) {
        if (!b || b <= 0) return '';
        var u = ['B', 'KB', 'MB', 'GB'], i = 0;
        while (b >= 1024 && i < u.length - 1) { b /= 1024; i++; }
        return (i >= 2 ? b.toFixed(1) : Math.round(b)) + ' ' + u[i];
    }

    function fmtSpeed(bps) {
        if (bps == null || isNaN(bps) || bps <= 0) return bps == null || isNaN(bps) ? '—' : '0 B/s';
        var u = ['B', 'KB', 'MB', 'GB'], i = 0, b = bps;
        while (b >= 1024 && i < u.length - 1) { b /= 1024; i++; }
        return (i >= 2 ? b.toFixed(2) : i === 1 ? b.toFixed(1) : Math.round(b)) + ' ' + u[i] + '/s';
    }

    function showError(elId, msg) { $(elId).text(msg).removeClass('d-none'); }
    function clearError(elId)     { $(elId).text('').addClass('d-none'); }

    function esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ── TagInput ────────────────────────────────────────────── */

    function TagInput(wrapId, inpId, hiddenId) {
        var wrap   = document.getElementById(wrapId);
        var inp    = document.getElementById(inpId);
        var hidden = document.getElementById(hiddenId);
        var tags   = [];

        function render() {
            wrap.querySelectorAll('.pl-ti-chip').forEach(function (c) { c.remove(); });
            tags.forEach(function (t, i) {
                var chip = document.createElement('span');
                chip.className = 'pl-ti-chip';
                chip.innerHTML = '<span>' + esc(t) + '</span><span class="pl-ti-chip-rm" title="Remove">×</span>';
                chip.querySelector('.pl-ti-chip-rm').addEventListener('click', function () {
                    tags.splice(i, 1);
                    render();
                    inp.focus();
                });
                wrap.insertBefore(chip, inp);
            });
            hidden.value = tags.join(', ');
        }

        function addTag(raw) {
            var val = raw.trim().toLowerCase();
            if (val && tags.indexOf(val) === -1) {
                tags.push(val);
                render();
            }
            inp.value = '';
        }

        inp.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addTag(inp.value);
            } else if (e.key === 'Backspace' && inp.value === '' && tags.length) {
                tags.pop();
                render();
            }
        });

        inp.addEventListener('input', function () {
            if (inp.value.indexOf(',') !== -1) {
                var parts = inp.value.split(',');
                parts.slice(0, -1).forEach(function (p) { addTag(p); });
                inp.value = parts[parts.length - 1].trimStart();
            }
        });

        wrap.addEventListener('click', function (e) {
            if (!e.target.classList.contains('pl-ti-chip-rm')) {
                inp.focus();
            }
        });

        return {
            setTags: function (str) {
                tags = [];
                if (str) {
                    str.split(',').forEach(function (t) {
                        t = t.trim().toLowerCase();
                        if (t) tags.push(t);
                    });
                }
                render();
            },
            getTagsString: function () {
                if (inp.value.trim()) { addTag(inp.value); }
                return hidden.value;
            },
            clear: function () {
                tags = [];
                inp.value = '';
                render();
            },
        };
    }

    /* ── TagInput instances (initialised on DOM ready) ───────── */
    var uploadTagInput;
    var editTagInput;

    /* ── DataTable ───────────────────────────────────────────── */

    var dt;
    var searchTimer;

    $(function () {
        uploadTagInput = TagInput('plTagsWrap',   'plTagsInp',   'plTags');
        editTagInput   = TagInput('editTagsWrap', 'editTagsInp', 'editTags');

        dt = $('#plTable').DataTable({
            processing : true,
            serverSide : true,
            scrollX    : true,
            dom        : 'rt<"dt-foot"lip>',
            pageLength : 10,
            ajax: {
                url  : PL.gridUrl,
                type : 'POST',
                data : function (d) {
                    d.searchValue    = $('#ucSearch').val();
                    d.with_deleted   = $('#showDeletedToggle').is(':checked') ? 1 : 0;
                    d._token         = PL.csrf;
                },
            },
            columns: [
                { data: 0, orderable: false },
                { data: 1, orderable: false },
                { data: 2, orderable: false },
                { data: 3, orderable: false },
                { data: 4, orderable: false, className: 'text-center' },
                { data: 5, orderable: false, className: 'text-center' },
                { data: 6, orderable: false, className: 'text-center' },
                { data: 7, orderable: false, className: 'text-center' },
            ],
            language: {
                emptyTable    : '<div class="py-4 text-center text-muted"><i class="bi bi-inbox d-block mb-2" style="font-size:2rem;opacity:.4;"></i>No PDFs found</div>',
                zeroRecords   : '<div class="py-4 text-center text-muted"><i class="bi bi-search d-block mb-2" style="font-size:1.6rem;opacity:.35;"></i>No matching PDFs</div>',
                processing    : '<span style="color:var(--primary);">Loading…</span>',
                lengthMenu    : 'Show _MENU_',
                info          : 'Showing _START_–_END_ of _TOTAL_',
                infoEmpty     : 'No PDFs',
                infoFiltered  : '(of _MAX_)',
                paginate: {
                    first    : '<i class="bi bi-chevron-double-left"></i>',
                    previous : '<i class="bi bi-chevron-left"></i>',
                    next     : '<i class="bi bi-chevron-right"></i>',
                    last     : '<i class="bi bi-chevron-double-right"></i>',
                },
            },
            drawCallback: function () {
                var info = this.api().page.info();
                var total    = info.recordsTotal;
                var filtered = info.recordsDisplay;
                $('#plCountBadge').text(
                    filtered === total ? total + ' total' : filtered + ' / ' + total
                );
                startPolling();
            },
        });

        /* Custom search */
        $('#ucSearch').on('input', function () {
            clearTimeout(searchTimer);
            var val = this.value;
            searchTimer = setTimeout(function () { dt.draw(); }, 280);
        });

        /* Show-deleted toggle */
        $('#showDeletedToggle').on('change', function () { dt.draw(); });
    });

    /* ── Status polling ──────────────────────────────────────── */

    var pollingTimer = null;

    function startPolling() {
        stopPolling();
        var pending = $('[data-role="status"][data-pdf-id]').filter(function () {
            var s = $(this).text().trim().toLowerCase();
            return s === 'pending' || s === 'processing';
        });
        if (!pending.length) return;

        pollingTimer = setInterval(function () {
            var found = false;
            $('[data-role="status"][data-pdf-id]').each(function () {
                var s = $(this).text().trim().toLowerCase();
                if (s !== 'pending' && s !== 'processing') return;
                found = true;
                var id   = $(this).data('pdf-id');
                var cell = $(this);
                $.get(PL.pdfApiBase + '/' + id + '/status', function (r) {
                    var map = {
                        pending    : { label: 'Pending',    cls: 'pl-pending' },
                        processing : { label: 'Processing', cls: 'pl-processing' },
                        active     : { label: 'Active',     cls: 'pl-active' },
                        failed     : { label: 'Failed',     cls: 'pl-failed' },
                        deleted    : { label: 'Deleted',    cls: 'pl-deleted' },
                    };
                    var m = map[r.status] || { label: r.status, cls: '' };
                    cell.attr('class', 'pl-status ' + m.cls)
                        .html('<span class="dot"></span>' + m.label);
                    if (r.status === 'active' || r.status === 'failed') {
                        dt.draw(false);
                    }
                });
            });
            if (!found) stopPolling();
        }, 3000);
    }

    function stopPolling() {
        if (pollingTimer) { clearInterval(pollingTimer); pollingTimer = null; }
    }

    /* ── Edit (open modal) ───────────────────────────────────── */

    $(document).on('click', '.pl-btn-edit', function () {
        var id = $(this).data('id');
        $.get(PL.pdfApiBase + '/' + id, function (res) {
            if (!res.status) {
                Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Failed to load PDF.' });
                return;
            }
            var d = res.data;
            $('#editPdfId').val(d.id);
            $('#editTitle').val(d.title);
            editTagInput.setTags(d.tags_string);
            editFile   = null;
            editTusKey = null;
            $('#editDropzone').removeClass('has-file');
            $('#editProgressWrap').removeClass('show');
            $('#editProgressBar').css('width', '0');
            $('#editFile').val('');
            $('#editBtnText').text('Save');
            $('#editSpinner').addClass('d-none');
            $('#editSaveBtn').prop('disabled', false);
            clearError('#plEditAlert');
            $('#plEditModal').modal('show');
        });
    });

    /* ── Delete ──────────────────────────────────────────────── */

    $(document).on('click', '.pl-btn-del', function () {
        var id    = $(this).data('id');
        var title = $(this).data('title');
        Swal.fire({
            title             : 'Delete "' + title + '"?',
            text              : 'This will soft-delete the PDF. Use "Show deleted" to view it again.',
            icon              : 'warning',
            showCancelButton  : true,
            confirmButtonColor: '#8B1A1A',
            cancelButtonColor : '#9B7575',
            confirmButtonText : 'Yes, delete',
        }).then(function (res) {
            if (!res.isConfirmed) return;
            $.ajax({
                url    : PL.deleteUrl,
                type   : 'POST',
                data   : { _token: PL.csrf, ids: [id] },
                success: function (r) {
                    if (r.success) {
                        dt.draw(false);
                        Swal.fire({ icon: 'success', title: 'Deleted!', text: r.message, timer: 1800, showConfirmButton: false });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: r.message });
                    }
                },
                error: function (xhr) {
                    Swal.fire({ icon: 'error', title: 'Error', text: (xhr.responseJSON || {}).message || 'Delete failed.' });
                },
            });
        });
    });

    /* ══════════════════════════════════════════════════════════
       TUS upload helper
       ══════════════════════════════════════════════════════════ */

    function TusUpload(opts) {
        var startedAt = Date.now();
        var upload = new tus.Upload(opts.file, {
            endpoint    : PL.tusUrl,
            chunkSize   : 8 * 1024 * 1024,
            retryDelays : [0, 1000, 3000, 5000],
            metadata: {
                filename : opts.file.name,
                filetype : opts.file.type || 'application/pdf',
            },
            onBeforeRequest: function (req) {
                req.setHeader('X-CSRF-TOKEN', PL.csrf);
            },
            onProgress: function (uploaded, total) {
                var pct = total > 0 ? Math.round((uploaded / total) * 100) : 0;
                $(opts.progressBar).css('width', pct + '%');
                $(opts.progressPct).text(pct + ' %');
                var elapsedSec = (Date.now() - startedAt) / 1000;
                $(opts.progressSpeed).text(fmtSpeed(elapsedSec > 0 ? uploaded / elapsedSec : 0));
            },
            onSuccess: function () {
                $(opts.progressBar).css('width', '100%');
                $(opts.progressPct).text('100 %');
                $(opts.progressSpeed).text('Done');
                var key = upload.url ? upload.url.split('/').pop() : null;
                opts.onComplete(key);
            },
            onError: function (err) {
                opts.onError('Upload failed: ' + err.message);
            },
        });
        upload.start();
        return upload;
    }

    /* ══════════════════════════════════════════════════════════
       Upload modal
       ══════════════════════════════════════════════════════════ */

    var uploadFile      = null;
    var uploadTusKey    = null;
    var uploadUploading = false;

    $('#btnUploadPdf').on('click', function () {
        uploadFile      = null;
        uploadTusKey    = null;
        uploadUploading = false;
        $('#plTitle').val('');
        uploadTagInput.clear();
        $('#plDropzone').removeClass('has-file');
        $('#plProgressWrap').removeClass('show');
        $('#plProgressBar').css('width', '0');
        $('#plStartBtn').prop('disabled', true);
        $('#plBtnText').text('Upload');
        $('#plSpinner').addClass('d-none');
        $('#plFile').val('');
        clearError('#plUploadAlert');
        $('#plUploadModal').modal('show');
    });

    /* Drag-and-drop (upload dropzone) */
    ['dragover', 'dragenter'].forEach(function (ev) {
        document.getElementById('plDropzone').addEventListener(ev, function (e) {
            e.preventDefault(); $(this).addClass('drag');
        });
    });
    document.getElementById('plDropzone').addEventListener('dragleave', function () { $(this).removeClass('drag'); });
    document.getElementById('plDropzone').addEventListener('drop', function (e) {
        e.preventDefault(); $(this).removeClass('drag');
        var f = e.dataTransfer.files[0];
        if (f) setUploadFile(f);
    });
    $('#plFile').on('change', function () { if (this.files[0]) setUploadFile(this.files[0]); });

    function isPdfFile(file) {
        if (!file) return false;
        if (file.type === 'application/pdf') return true;
        return /\.pdf$/i.test(file.name || '');
    }

    function setUploadFile(f) {
        if (!isPdfFile(f)) {
            showError('#plUploadAlert', 'Please select a PDF file.');
            return;
        }
        uploadFile = f;
        $('#plSelectedName').text(f.name);
        $('#plSelectedSize').text(fmtBytes(f.size));
        $('#plDropzone').addClass('has-file');
        $('#plStartBtn').prop('disabled', false);
        clearError('#plUploadAlert');
    }

    $('#plStartBtn').on('click', function () {
        if (uploadUploading) return;
        var title = $.trim($('#plTitle').val());
        if (!title)      { showError('#plUploadAlert', 'Please enter a title.'); return; }
        if (!uploadFile) { showError('#plUploadAlert', 'Please select a PDF file.'); return; }
        clearError('#plUploadAlert');

        uploadUploading = true;
        uploadTusKey    = null;
        $('#plSpinner').removeClass('d-none');
        $('#plBtnText').text('Uploading…');
        $('#plStartBtn').prop('disabled', true);
        $('#plProgressWrap').addClass('show');

        TusUpload({
            file          : uploadFile,
            progressBar   : '#plProgressBar',
            progressPct   : '#plProgressPct',
            progressSpeed : '#plProgressSpeed',
            onComplete: function (key) {
                uploadTusKey = key;
                $('#plBtnText').text('Saving…');
                $.ajax({
                    url  : PL.completeUrl,
                    type : 'POST',
                    data : {
                        _token    : PL.csrf,
                        tus_key   : key,
                        title     : title,
                        tags      : uploadTagInput.getTagsString(),
                        file_name : uploadFile.name,
                        file_size : uploadFile.size,
                    },
                    success: function () {
                        uploadUploading = false;
                        $('#plUploadModal').modal('hide');
                        dt.draw(false);
                        startPolling();
                        Swal.fire({ icon: 'success', title: 'Uploaded!', text: 'PDF is queued for linearization.', timer: 2200, showConfirmButton: false });
                    },
                    error: function (xhr) {
                        uploadUploading = false;
                        showError('#plUploadAlert', (xhr.responseJSON || {}).message || 'Server error saving the PDF.');
                        $('#plSpinner').addClass('d-none');
                        $('#plBtnText').text('Retry');
                        $('#plStartBtn').prop('disabled', false);
                    },
                });
            },
            onError: function (msg) {
                uploadUploading = false;
                showError('#plUploadAlert', msg);
                $('#plSpinner').addClass('d-none');
                $('#plBtnText').text('Retry');
                $('#plStartBtn').prop('disabled', false);
            },
        });
    });

    /* Reset upload state when modal is dismissed */
    $('#plUploadModal').on('hidden.bs.modal', function () {
        uploadUploading = false;
    });

    /* ══════════════════════════════════════════════════════════
       Edit modal
       ══════════════════════════════════════════════════════════ */

    var editFile      = null;
    var editTusKey    = null;
    var editUploading = false;

    /* Drag-and-drop (edit dropzone) */
    ['dragover', 'dragenter'].forEach(function (ev) {
        document.getElementById('editDropzone').addEventListener(ev, function (e) {
            e.preventDefault(); $(this).addClass('drag');
        });
    });
    document.getElementById('editDropzone').addEventListener('dragleave', function () { $(this).removeClass('drag'); });
    document.getElementById('editDropzone').addEventListener('drop', function (e) {
        e.preventDefault(); $(this).removeClass('drag');
        var f = e.dataTransfer.files[0];
        if (f) setEditFile(f);
    });
    $('#editFile').on('change', function () { if (this.files[0]) setEditFile(this.files[0]); });

    function setEditFile(f) {
        if (!isPdfFile(f)) {
            showError('#plEditAlert', 'Please select a PDF file.');
            return;
        }
        editFile = f;
        $('#editSelectedName').text(f.name);
        $('#editSelectedSize').text(fmtBytes(f.size));
        $('#editDropzone').addClass('has-file');
        clearError('#plEditAlert');
    }

    $('#editSaveBtn').on('click', function () {
        if (editUploading) return;
        var id    = $('#editPdfId').val();
        var title = $.trim($('#editTitle').val());
        if (!title) { showError('#plEditAlert', 'Title is required.'); return; }
        clearError('#plEditAlert');

        if (!editFile) {
            doEditSave(id, title, null);
            return;
        }

        editUploading = true;
        editTusKey    = null;
        $('#editSpinner').removeClass('d-none');
        $('#editBtnText').text('Uploading…');
        $('#editSaveBtn').prop('disabled', true);
        $('#editProgressWrap').addClass('show');

        TusUpload({
            file          : editFile,
            progressBar   : '#editProgressBar',
            progressPct   : '#editProgressPct',
            progressSpeed : '#editProgressSpeed',
            onComplete: function (key) {
                editTusKey = key;
                $('#editBtnText').text('Saving…');
                doEditSave(id, title, key);
            },
            onError: function (msg) {
                editUploading = false;
                showError('#plEditAlert', msg);
                $('#editSpinner').addClass('d-none');
                $('#editBtnText').text('Save');
                $('#editSaveBtn').prop('disabled', false);
            },
        });
    });

    function doEditSave(id, title, tusKey) {
        var payload = { _token: PL.csrf, title: title, tags: editTagInput.getTagsString() };
        if (tusKey) {
            payload.tus_key   = tusKey;
            payload.file_name = editFile ? editFile.name : '';
        }

        $.ajax({
            url    : PL.pdfApiBase + '/' + id + '/update',
            type   : 'POST',
            data   : payload,
            success: function (r) {
                editUploading = false;
                $('#plEditModal').modal('hide');
                dt.draw(false);
                startPolling();
                Swal.fire({ icon: 'success', title: 'Saved!', text: r.message || 'Track updated.', timer: 2000, showConfirmButton: false });
            },
            error: function (xhr) {
                editUploading = false;
                showError('#plEditAlert', (xhr.responseJSON || {}).message || 'Server error updating the PDF.');
                $('#editSpinner').addClass('d-none');
                $('#editBtnText').text('Save');
                $('#editSaveBtn').prop('disabled', false);
            },
        });
    }

    $('#plEditModal').on('hidden.bs.modal', function () {
        editUploading = false;
    });

})();
