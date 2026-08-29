/**
 * Audio Library — DataTable (server-side), TUS upload, edit modal
 *
 * Requires window.AL set by the blade view:
 *   { tusUrl, completeUrl, audioApiBase, gridUrl, deleteUrl, csrf }
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
            wrap.querySelectorAll('.al-ti-chip').forEach(function (c) { c.remove(); });
            tags.forEach(function (t, i) {
                var chip = document.createElement('span');
                chip.className = 'al-ti-chip';
                chip.innerHTML = '<span>' + esc(t) + '</span><span class="al-ti-chip-rm" title="Remove">×</span>';
                chip.querySelector('.al-ti-chip-rm').addEventListener('click', function () {
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
            if (!e.target.classList.contains('al-ti-chip-rm')) {
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
        uploadTagInput = TagInput('alTagsWrap',   'alTagsInp',   'alTags');
        editTagInput   = TagInput('editTagsWrap', 'editTagsInp', 'editTags');

        dt = $('#alTable').DataTable({
            processing : true,
            serverSide : true,
            scrollX    : true,
            dom        : 'rt<"dt-foot"lip>',
            pageLength : 10,
            ajax: {
                url  : AL.gridUrl,
                type : 'POST',
                data : function (d) {
                    d.searchValue    = $('#ucSearch').val();
                    d.with_deleted   = $('#showDeletedToggle').is(':checked') ? 1 : 0;
                    d._token         = AL.csrf;
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
            ],
            language: {
                emptyTable    : '<div class="py-4 text-center text-muted"><i class="bi bi-inbox d-block mb-2" style="font-size:2rem;opacity:.4;"></i>No audio tracks found</div>',
                zeroRecords   : '<div class="py-4 text-center text-muted"><i class="bi bi-search d-block mb-2" style="font-size:1.6rem;opacity:.35;"></i>No matching tracks</div>',
                processing    : '<span style="color:var(--primary);">Loading…</span>',
                lengthMenu    : 'Show _MENU_',
                info          : 'Showing _START_–_END_ of _TOTAL_',
                infoEmpty     : 'No tracks',
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
                $('#alCountBadge').text(
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
        var pending = $('[data-role="status"][data-audio-id]').filter(function () {
            var s = $(this).text().trim().toLowerCase();
            return s === 'pending' || s === 'processing';
        });
        if (!pending.length) return;

        pollingTimer = setInterval(function () {
            var found = false;
            $('[data-role="status"][data-audio-id]').each(function () {
                var s = $(this).text().trim().toLowerCase();
                if (s !== 'pending' && s !== 'processing') return;
                found = true;
                var id   = $(this).data('audio-id');
                var cell = $(this);
                $.get(AL.audioApiBase + '/' + id + '/status', function (r) {
                    var map = {
                        pending    : { label: 'Pending',    cls: 'al-pending' },
                        processing : { label: 'Processing', cls: 'al-processing' },
                        active     : { label: 'Active',     cls: 'al-active' },
                        failed     : { label: 'Failed',     cls: 'al-failed' },
                    };
                    var m = map[r.status] || { label: r.status, cls: '' };
                    cell.attr('class', 'al-status ' + m.cls)
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

    $(document).on('click', '.al-btn-edit', function () {
        var id = $(this).data('id');
        $.get(AL.audioApiBase + '/' + id, function (res) {
            if (!res.status) {
                Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Failed to load track.' });
                return;
            }
            var d = res.data;
            $('#editAudioId').val(d.id);
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
            clearError('#alEditAlert');
            $('#alEditModal').modal('show');
        });
    });

    /* ── Delete ──────────────────────────────────────────────── */

    $(document).on('click', '.al-btn-del', function () {
        var id    = $(this).data('id');
        var title = $(this).data('title');
        Swal.fire({
            title             : 'Delete "' + title + '"?',
            text              : 'This will soft-delete the track. Use "Show deleted" to view it again.',
            icon              : 'warning',
            showCancelButton  : true,
            confirmButtonColor: '#8B1A1A',
            cancelButtonColor : '#9B7575',
            confirmButtonText : 'Yes, delete',
        }).then(function (res) {
            if (!res.isConfirmed) return;
            $.ajax({
                url    : AL.deleteUrl,
                type   : 'POST',
                data   : { _token: AL.csrf, ids: [id] },
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
            endpoint    : AL.tusUrl,
            chunkSize   : 8 * 1024 * 1024,
            retryDelays : [0, 1000, 3000, 5000],
            metadata: {
                filename : opts.file.name,
                filetype : opts.file.type || 'audio/mpeg',
            },
            onBeforeRequest: function (req) {
                req.setHeader('X-CSRF-TOKEN', AL.csrf);
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

    $('#btnUploadAudio').on('click', function () {
        uploadFile      = null;
        uploadTusKey    = null;
        uploadUploading = false;
        $('#alTitle').val('');
        uploadTagInput.clear();
        $('#alDropzone').removeClass('has-file');
        $('#alProgressWrap').removeClass('show');
        $('#alProgressBar').css('width', '0');
        $('#alStartBtn').prop('disabled', true);
        $('#alBtnText').text('Upload');
        $('#alSpinner').addClass('d-none');
        $('#alFile').val('');
        clearError('#alUploadAlert');
        $('#alUploadModal').modal('show');
    });

    /* Drag-and-drop (upload dropzone) */
    ['dragover', 'dragenter'].forEach(function (ev) {
        document.getElementById('alDropzone').addEventListener(ev, function (e) {
            e.preventDefault(); $(this).addClass('drag');
        });
    });
    document.getElementById('alDropzone').addEventListener('dragleave', function () { $(this).removeClass('drag'); });
    document.getElementById('alDropzone').addEventListener('drop', function (e) {
        e.preventDefault(); $(this).removeClass('drag');
        var f = e.dataTransfer.files[0];
        if (f) setUploadFile(f);
    });
    $('#alFile').on('change', function () { if (this.files[0]) setUploadFile(this.files[0]); });

    function setUploadFile(f) {
        uploadFile = f;
        $('#alSelectedName').text(f.name);
        $('#alSelectedSize').text(fmtBytes(f.size));
        $('#alDropzone').addClass('has-file');
        $('#alStartBtn').prop('disabled', false);
        clearError('#alUploadAlert');
    }

    $('#alStartBtn').on('click', function () {
        if (uploadUploading) return;
        var title = $.trim($('#alTitle').val());
        if (!title)      { showError('#alUploadAlert', 'Please enter a title.'); return; }
        if (!uploadFile) { showError('#alUploadAlert', 'Please select an audio file.'); return; }
        clearError('#alUploadAlert');

        uploadUploading = true;
        uploadTusKey    = null;
        $('#alSpinner').removeClass('d-none');
        $('#alBtnText').text('Uploading…');
        $('#alStartBtn').prop('disabled', true);
        $('#alProgressWrap').addClass('show');

        TusUpload({
            file          : uploadFile,
            progressBar   : '#alProgressBar',
            progressPct   : '#alProgressPct',
            progressSpeed : '#alProgressSpeed',
            onComplete: function (key) {
                uploadTusKey = key;
                $('#alBtnText').text('Saving…');
                $.ajax({
                    url  : AL.completeUrl,
                    type : 'POST',
                    data : {
                        _token    : AL.csrf,
                        tus_key   : key,
                        title     : title,
                        tags      : uploadTagInput.getTagsString(),
                        file_name : uploadFile.name,
                        file_size : uploadFile.size,
                    },
                    success: function () {
                        uploadUploading = false;
                        $('#alUploadModal').modal('hide');
                        dt.draw(false);
                        startPolling();
                        Swal.fire({ icon: 'success', title: 'Uploaded!', text: 'Track is queued for processing.', timer: 2200, showConfirmButton: false });
                    },
                    error: function (xhr) {
                        uploadUploading = false;
                        showError('#alUploadAlert', (xhr.responseJSON || {}).message || 'Server error saving the track.');
                        $('#alSpinner').addClass('d-none');
                        $('#alBtnText').text('Retry');
                        $('#alStartBtn').prop('disabled', false);
                    },
                });
            },
            onError: function (msg) {
                uploadUploading = false;
                showError('#alUploadAlert', msg);
                $('#alSpinner').addClass('d-none');
                $('#alBtnText').text('Retry');
                $('#alStartBtn').prop('disabled', false);
            },
        });
    });

    /* Reset upload state when modal is dismissed */
    $('#alUploadModal').on('hidden.bs.modal', function () {
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
        editFile = f;
        $('#editSelectedName').text(f.name);
        $('#editSelectedSize').text(fmtBytes(f.size));
        $('#editDropzone').addClass('has-file');
        clearError('#alEditAlert');
    }

    $('#editSaveBtn').on('click', function () {
        if (editUploading) return;
        var id    = $('#editAudioId').val();
        var title = $.trim($('#editTitle').val());
        if (!title) { showError('#alEditAlert', 'Title is required.'); return; }
        clearError('#alEditAlert');

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
                showError('#alEditAlert', msg);
                $('#editSpinner').addClass('d-none');
                $('#editBtnText').text('Save');
                $('#editSaveBtn').prop('disabled', false);
            },
        });
    });

    function doEditSave(id, title, tusKey) {
        var payload = { _token: AL.csrf, title: title, tags: editTagInput.getTagsString() };
        if (tusKey) {
            payload.tus_key   = tusKey;
            payload.file_name = editFile ? editFile.name : '';
        }

        $.ajax({
            url    : AL.audioApiBase + '/' + id + '/update',
            type   : 'POST',
            data   : payload,
            success: function (r) {
                editUploading = false;
                $('#alEditModal').modal('hide');
                dt.draw(false);
                startPolling();
                Swal.fire({ icon: 'success', title: 'Saved!', text: r.message || 'Track updated.', timer: 2000, showConfirmButton: false });
            },
            error: function (xhr) {
                editUploading = false;
                showError('#alEditAlert', (xhr.responseJSON || {}).message || 'Server error updating the track.');
                $('#editSpinner').addClass('d-none');
                $('#editBtnText').text('Save');
                $('#editSaveBtn').prop('disabled', false);
            },
        });
    }

    $('#alEditModal').on('hidden.bs.modal', function () {
        editUploading = false;
    });

})();
