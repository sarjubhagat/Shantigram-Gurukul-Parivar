(function () {
    'use strict';

    var cfg = window.IL_CONFIG || {};
    var galleryUrl     = cfg.galleryUrl;
    var galleryIdsUrl  = cfg.galleryIdsUrl;
    var bulkUrl        = cfg.bulkUrl;
    var bulkDestroyUrl = cfg.bulkDestroyUrl;
    var bulkDownloadUrl = cfg.bulkDownloadUrl;
    var apiBase        = cfg.apiBase || '';
    var csrf           = cfg.csrf || '';
    var canEdit        = !!cfg.canEdit;
    var canDelete      = !!cfg.canDelete;
    var canDownload    = !!cfg.canDownload;
    var canSelect      = canDelete || canDownload;

    var galleryEl      = document.getElementById('ilGallery');
    var loadingEl      = document.getElementById('ilLoading');
    var emptyEl        = document.getElementById('ilEmpty');
    var sentinel       = document.getElementById('ilSentinel');
    var searchInput    = document.getElementById('ilSearch');
    var showDeletedEl  = document.getElementById('ilShowDeleted');
    var totalBadge     = document.getElementById('ilTotalBadge');
    var bulkBar        = document.getElementById('ilBulkBar');
    var selectedCountEl = document.getElementById('ilSelectedCount');
    var bulkDeleteBtn  = document.getElementById('ilBulkDelete');
    var bulkDownloadBtn = document.getElementById('ilBulkDownload');
    var clearSelBtn    = document.getElementById('ilClearSelection');
    var selectAllEl    = document.getElementById('ilSelectAll');
    var selectAllText  = document.getElementById('ilSelectAllText');
    var selectAllHint  = document.getElementById('ilSelectAllHint');

    var page            = 1;
    var lastPage        = 1;
    var loading         = false;
    var q               = '';
    var selected        = {};
    var selectAllMode   = false;
    var galleryActiveTotal = 0;
    var selectCap       = 100;

    function esc(s) {
        if (s == null) return '';
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/"/g, '&quot;');
    }

    function setLoading(on) {
        loading = on;
        if (loadingEl) loadingEl.classList.toggle('d-none', !on);
    }

    function updateBulkBar() {
        var n = Object.keys(selected).length;
        if (selectedCountEl) selectedCountEl.textContent = String(n);
        if (bulkBar) bulkBar.classList.toggle('show', n > 0);
        syncSelectAllUi();
    }

    function syncSelectAllUi() {
        if (!selectAllEl) return;

        var selCount = Object.keys(selected).length;
        var target   = Math.min(galleryActiveTotal, selectCap);

        if (selCount === 0) {
            selectAllEl.checked = false;
            selectAllEl.indeterminate = false;
            selectAllMode = false;
        } else if (target > 0 && selCount >= target) {
            selectAllEl.checked = true;
            selectAllEl.indeterminate = false;
            selectAllMode = true;
        } else {
            selectAllEl.checked = false;
            selectAllEl.indeterminate = true;
            selectAllMode = false;
        }

        if (selectAllText) {
            if (galleryActiveTotal > 0) {
                selectAllText.textContent = 'Select all (' + galleryActiveTotal + ')';
            } else {
                selectAllText.textContent = 'Select all';
            }
        }

        if (selectAllHint) {
            if (galleryActiveTotal > selectCap) {
                selectAllHint.textContent = 'Up to ' + selectCap + ' images can be selected at once.';
            } else if (galleryActiveTotal > 0) {
                selectAllHint.textContent = selCount > 0
                    ? selCount + ' of ' + galleryActiveTotal + ' selected'
                    : galleryActiveTotal + ' image' + (galleryActiveTotal === 1 ? '' : 's') + ' match current filter';
            } else {
                selectAllHint.textContent = '';
            }
        }
    }

    function applySelectionToDom() {
        if (!galleryEl) return;
        galleryEl.querySelectorAll('.il-card-select').forEach(function (cb) {
            var id = cb.getAttribute('data-id');
            var on = !!selected[id];
            cb.checked = on;
            var card = cb.closest('.il-card');
            if (card) card.classList.toggle('is-selected', on);
        });
    }

    function toggleSelect(id, on) {
        id = String(id);
        if (on) selected[id] = true;
        else delete selected[id];
        updateBulkBar();
        var cb = galleryEl && galleryEl.querySelector('.il-card-select[data-id="' + id + '"]');
        if (cb) cb.checked = !!on;
        var card = galleryEl && galleryEl.querySelector('.il-card[data-id="' + id + '"]');
        if (card) card.classList.toggle('is-selected', !!on);
    }

    function clearSelection() {
        selected = {};
        selectAllMode = false;
        if (galleryEl) {
            galleryEl.querySelectorAll('.il-card-select').forEach(function (c) {
                c.checked = false;
            });
            galleryEl.querySelectorAll('.il-card.is-selected').forEach(function (c) {
                c.classList.remove('is-selected');
            });
        }
        updateBulkBar();
    }

    function fetchGalleryMeta() {
        if (!galleryIdsUrl) return Promise.resolve();

        var u = new URL(galleryIdsUrl, window.location.origin);
        if (q) u.searchParams.set('q', q);

        return fetch(u.toString(), { headers: { Accept: 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                galleryActiveTotal = data.total || 0;
                selectCap = data.cap || 100;
                syncSelectAllUi();
                return data;
            })
            .catch(function () {
                galleryActiveTotal = 0;
                syncSelectAllUi();
                return null;
            });
    }

    function selectAllMatching() {
        if (!galleryIdsUrl) return Promise.resolve();

        var u = new URL(galleryIdsUrl, window.location.origin);
        if (q) u.searchParams.set('q', q);

        return fetch(u.toString(), { headers: { Accept: 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                galleryActiveTotal = data.total || 0;
                selectCap = data.cap || 100;
                (data.ids || []).forEach(function (id) {
                    selected[String(id)] = true;
                });
                selectAllMode = true;
                applySelectionToDom();
                updateBulkBar();
            });
    }

    function renderItem(row) {
        var id      = row.id;
        var url     = row.url || '';
        var title   = esc(row.title);
        var key     = esc(row.unique_key);
        var dims    = row.width && row.height ? row.width + '×' + row.height : '—';
        var fn      = esc(row.original_filename || '—');
        var tags    = Array.isArray(row.tags) && row.tags.length ? esc(row.tags.join(', ')) : '—';
        var upd     = row.updated_at ? esc(row.updated_at) : '—';
        var checked = selected[String(id)] ? ' checked' : '';
        var deleted = row.is_deleted;
        var cardCls = 'il-card' + (deleted ? ' il-deleted' : '') + (selected[String(id)] ? ' is-selected' : '');
        var badge   = deleted ? '<span class="il-card-badge">Deleted</span>' : '';
        var actions = '';

        if (!deleted) {
            actions += '<button type="button" class="il-act-btn il-btn-view" data-id="' + id + '"><i class="bi bi-arrows-fullscreen"></i> View</button>';
            if (canEdit) {
                actions += '<button type="button" class="il-act-btn il-btn-edit" data-id="' + id + '"><i class="bi bi-pencil-square"></i> Edit</button>';
            }
            if (canDownload) {
                actions += '<a href="' + esc(apiBase + '/' + id + '/download') + '" class="il-act-btn il-btn-dl" data-id="' + id + '" target="_blank" rel="noopener"><i class="bi bi-download"></i> Download</a>';
            }
            if (canDelete) {
                actions += '<button type="button" class="il-act-btn il-act-del il-btn-del" data-id="' + id + '"><i class="bi bi-trash"></i> Remove</button>';
            }
        }

        var selectBox = canSelect && !deleted
            ? '<label class="il-check-wrap" title="Select">' +
              '<input type="checkbox" class="il-card-select" data-id="' + id + '"' + checked + '>' +
              '<span class="il-check-ui" aria-hidden="true"></span>' +
              '</label>'
            : '';

        return (
            '<div class="' + cardCls + '" data-id="' + id + '">' +
            selectBox +
            badge +
            '<div class="il-card-media"><img src="' + esc(url) + '" alt="" loading="lazy" decoding="async"></div>' +
            '<div class="il-card-overlay">' +
            '<div class="il-card-overlay-inner">' +
            '<div class="il-card-title">' + title + '</div>' +
            '<div class="il-card-meta">' + key + '<br>' + dims + ' · ' + esc(row.size_human || '') + '<br>Tags: ' + tags + '</div>' +
            (actions ? '<div class="il-card-actions">' + actions + '</div>' : '') +
            '</div></div></div>'
        );
    }

    function bindOneCard(card) {
        if (!card || card.getAttribute('data-il-bound')) return;
        card.setAttribute('data-il-bound', '1');

        var cb = card.querySelector('.il-card-select');
        var checkWrap = card.querySelector('.il-check-wrap');
        if (checkWrap) {
            checkWrap.addEventListener('click', function (e) { e.stopPropagation(); });
        }
        if (cb) {
            cb.addEventListener('change', function () {
                toggleSelect(cb.getAttribute('data-id'), cb.checked);
            });
        }

        var editBtn = card.querySelector('.il-btn-edit');
        if (editBtn) {
            editBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                openEdit(parseInt(editBtn.getAttribute('data-id'), 10));
            });
        }

        var delBtn = card.querySelector('.il-btn-del');
        if (delBtn) {
            delBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var id = parseInt(delBtn.getAttribute('data-id'), 10);
                confirmDeleteOne(id, card);
            });
        }

        var viewBtn = card.querySelector('.il-btn-view');
        if (viewBtn) {
            viewBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                openView(parseInt(viewBtn.getAttribute('data-id'), 10));
            });
        }

        var media = card.querySelector('.il-card-media');
        if (media) {
            media.addEventListener('click', function (e) {
                e.stopPropagation();
                openView(parseInt(card.getAttribute('data-id'), 10));
            });
        }

        var dlLink = card.querySelector('.il-btn-dl');
        if (dlLink) {
            dlLink.addEventListener('click', function (e) { e.stopPropagation(); });
        }
    }

    function openView(id) {
        var modalEl = document.getElementById('ilModalView');
        if (!modalEl) return;

        var imgEl   = document.getElementById('ilViewImg');
        var titleEl = document.getElementById('ilViewTitle');
        var metaEl  = document.getElementById('ilViewMeta');
        var dlEl    = document.getElementById('ilViewDownload');

        if (imgEl) imgEl.src = '';
        if (titleEl) titleEl.textContent = 'Loading…';
        if (metaEl) metaEl.textContent = '';

        bootstrap.Modal.getOrCreateInstance(modalEl).show();

        fetch(apiBase + '/' + id, { headers: { Accept: 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                if (!j.status || !j.data) {
                    bootstrap.Modal.getInstance(modalEl)?.hide();
                    Swal.fire({ icon: 'error', title: 'Error', text: j.message || 'Not found' });
                    return;
                }
                var d = j.data;
                if (titleEl) titleEl.textContent = d.title || '—';
                if (metaEl) {
                    var parts = [
                        d.unique_key || '',
                        (d.width && d.height) ? (d.width + '×' + d.height) : '',
                        d.size_human || '',
                        d.original_filename || '',
                    ].filter(Boolean);
                    metaEl.textContent = parts.join(' · ');
                }
                if (imgEl) {
                    imgEl.src = d.url || '';
                    imgEl.alt = d.alt_text || d.title || '';
                }
                if (dlEl && canDownload && d.download_url) {
                    dlEl.href = d.download_url;
                    dlEl.classList.remove('d-none');
                } else if (dlEl) {
                    dlEl.classList.add('d-none');
                }
            })
            .catch(function () {
                bootstrap.Modal.getInstance(modalEl)?.hide();
                Swal.fire({ icon: 'error', title: 'Error', text: 'Could not load image' });
            });
    }

    function triggerBulkDownload(ids) {
        if (!bulkDownloadUrl || !ids.length) return;

        Swal.fire({
            title             : 'Preparing download…',
            text              : 'Packaging ' + ids.length + ' image(s) into a ZIP file.',
            allowOutsideClick : false,
            didOpen           : function () { Swal.showLoading(); },
        });

        fetch(bulkDownloadUrl, {
            method : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                Accept          : 'application/json',
            },
            body: JSON.stringify({ ids: ids }),
        })
            .then(function (r) {
                if (!r.ok) {
                    return r.json().then(function (j) {
                        throw new Error(j.message || 'Download failed');
                    }).catch(function () {
                        throw new Error('Download failed');
                    });
                }
                return r.blob();
            })
            .then(function (blob) {
                Swal.close();
                var url = URL.createObjectURL(blob);
                var a   = document.createElement('a');
                a.href  = url;
                a.download = 'images-' + new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-') + '.zip';
                document.body.appendChild(a);
                a.click();
                a.remove();
                URL.revokeObjectURL(url);
            })
            .catch(function (err) {
                Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'Download failed' });
            });
    }

    function confirmDeleteOne(id, card) {
        Swal.fire({
            title             : 'Remove this image?',
            text              : 'This will soft-delete the image. Use "Show deleted" to view it again.',
            icon              : 'warning',
            showCancelButton  : true,
            confirmButtonColor: '#8B1A1A',
            cancelButtonColor : '#9B7575',
            confirmButtonText : 'Yes, remove',
        }).then(function (res) {
            if (!res.isConfirmed) return;
            fetch(apiBase + '/' + id, {
                method : 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    if (j.ok) {
                        if (showDeletedEl && showDeletedEl.checked) {
                            fetchPage(true);
                        } else if (card) {
                            card.remove();
                        }
                        delete selected[String(id)];
                        updateBulkBar();
                        Swal.fire({ icon: 'success', title: 'Removed', timer: 1500, showConfirmButton: false });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: j.message || 'Delete failed' });
                    }
                })
                .catch(function () {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Delete failed' });
                });
        });
    }

    function appendCardsFromHtml(html) {
        if (!galleryEl || !html) return;
        var temp = document.createElement('div');
        temp.innerHTML = html;
        temp.querySelectorAll('.il-card').forEach(function (card) {
            bindOneCard(card);
            galleryEl.appendChild(card);
        });
        if (selectAllMode) {
            applySelectionToDom();
        }
    }

    function fetchPage(reset) {
        if (loading) return;
        if (!reset && page > lastPage) return;
        if (reset) {
            page = 1;
            lastPage = 1;
            if (galleryEl) galleryEl.innerHTML = '';
            if (reset) {
                clearSelection();
            }
        }
        setLoading(true);

        var u = new URL(galleryUrl, window.location.origin);
        u.searchParams.set('page', String(page));
        u.searchParams.set('per_page', '24');
        if (q) u.searchParams.set('q', q);
        if (showDeletedEl && showDeletedEl.checked) u.searchParams.set('with_deleted', '1');

        var metaPromise = reset ? fetchGalleryMeta() : Promise.resolve();

        metaPromise.then(function () {
            return fetch(u.toString(), { headers: { Accept: 'application/json' } });
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                lastPage = data.last_page || 1;
                var rows = data.data || [];
                if (totalBadge) totalBadge.textContent = String(data.total ?? '—');
                if (emptyEl) {
                    emptyEl.classList.toggle('d-none', !(reset && rows.length === 0));
                }
                if (galleryEl && rows.length) {
                    appendCardsFromHtml(rows.map(renderItem).join(''));
                }
                page += 1;
                syncSelectAllUi();
            })
            .catch(function () {
                if (galleryEl && reset) {
                    galleryEl.innerHTML = '<div class="il-empty text-danger">Could not load gallery.</div>';
                }
            })
            .finally(function () {
                setLoading(false);
            });
    }

    var io = null;
    function setupInfinite() {
        if (!sentinel || !('IntersectionObserver' in window)) return;
        if (io) io.disconnect();
        io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting && !loading && page <= lastPage) {
                    fetchPage(false);
                }
            });
        }, { root: null, rootMargin: '200px', threshold: 0 });
        io.observe(sentinel);
    }

    function openEdit(id) {
        var modalEl = document.getElementById('ilModalEdit');
        if (!modalEl) return;
        fetch(apiBase + '/' + id, { headers: { Accept: 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                if (!j.status || !j.data) {
                    Swal.fire({ icon: 'error', title: 'Error', text: j.message || 'Not found' });
                    return;
                }
                var d = j.data;
                document.getElementById('ilEditId').value = String(d.id);
                document.getElementById('ilEditTitle').value = d.title || '';
                document.getElementById('ilEditTags').value = d.tags_string || '';
                document.getElementById('ilEditAlt').value = d.alt_text || '';
                document.getElementById('ilEditSort').value = d.sort_order != null ? String(d.sort_order) : '0';
                var img = document.getElementById('ilEditPreview');
                if (img) {
                    img.src = d.url || '';
                    img.alt = d.title || '';
                }
                document.getElementById('ilEditError').classList.add('d-none');
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            })
            .catch(function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Could not load image' });
            });
    }

    var searchTimer;
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                q = searchInput.value.trim();
                page = 1;
                lastPage = 1;
                clearSelection();
                if (galleryEl) galleryEl.innerHTML = '';
                fetchPage(true);
            }, 350);
        });
    }

    if (showDeletedEl) {
        showDeletedEl.addEventListener('change', function () {
            page = 1;
            lastPage = 1;
            clearSelection();
            if (galleryEl) galleryEl.innerHTML = '';
            fetchPage(true);
        });
    }

    if (selectAllEl) {
        selectAllEl.addEventListener('change', function () {
            if (selectAllEl.checked) {
                selectAllMatching();
            } else {
                clearSelection();
            }
        });
    }

    if (bulkDownloadBtn) {
        bulkDownloadBtn.addEventListener('click', function () {
            var ids = Object.keys(selected).map(function (x) { return parseInt(x, 10); });
            if (!ids.length) return;
            triggerBulkDownload(ids);
        });
    }

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function () {
            var ids = Object.keys(selected).map(function (x) { return parseInt(x, 10); });
            if (!ids.length) return;
            Swal.fire({
                title             : 'Remove ' + ids.length + ' image(s)?',
                text              : 'This will soft-delete selected images. Use "Show deleted" to view them again.',
                icon              : 'warning',
                showCancelButton  : true,
                confirmButtonColor: '#8B1A1A',
                cancelButtonColor : '#9B7575',
                confirmButtonText : 'Yes, remove',
            }).then(function (res) {
                if (!res.isConfirmed) return;
                fetch(bulkDestroyUrl, {
                    method : 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        Accept          : 'application/json',
                    },
                    body: JSON.stringify({ ids: ids }),
                })
                    .then(function (r) { return r.json(); })
                    .then(function (j) {
                        if (j.ok) {
                            clearSelection();
                            page = 1;
                            lastPage = 1;
                            if (galleryEl) galleryEl.innerHTML = '';
                            fetchPage(true);
                            Swal.fire({ icon: 'success', title: 'Removed', text: j.message, timer: 1800, showConfirmButton: false });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: j.message || 'Bulk delete failed' });
                        }
                    })
                    .catch(function () {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Bulk delete failed' });
                    });
            });
        });
    }

    if (clearSelBtn) {
        clearSelBtn.addEventListener('click', clearSelection);
    }

    var bulkForm = document.getElementById('ilBulkForm');
    if (bulkForm) {
        bulkForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var err = document.getElementById('ilBulkError');
            if (err) err.classList.add('d-none');
            var fd  = new FormData(bulkForm);
            var btn = document.getElementById('ilBulkSubmit');
            if (btn) btn.disabled = true;
            fetch(bulkUrl, {
                method : 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body   : fd,
            })
                .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
                .then(function (x) {
                    if (x.j.errors && x.j.errors.length && err) {
                        err.textContent = x.j.message || 'Some files failed.';
                        err.classList.remove('d-none');
                    }
                    if (x.j.ok && x.j.created && x.j.created.length) {
                        var modalEl = document.getElementById('ilModalBulk');
                        if (modalEl) {
                            var bm = bootstrap.Modal.getInstance(modalEl);
                            if (bm) bm.hide();
                        }
                        bulkForm.reset();
                        q = searchInput ? searchInput.value.trim() : '';
                        page = 1;
                        lastPage = 1;
                        if (galleryEl) galleryEl.innerHTML = '';
                        fetchPage(true);
                    } else if (!x.j.ok && (!x.j.created || !x.j.created.length) && err) {
                        err.textContent = x.j.message || 'Upload failed.';
                        err.classList.remove('d-none');
                    }
                })
                .catch(function () {
                    if (err) {
                        err.textContent = 'Network error.';
                        err.classList.remove('d-none');
                    }
                })
                .finally(function () {
                    if (btn) btn.disabled = false;
                });
        });
    }

    var editForm = document.getElementById('ilEditForm');
    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var id  = document.getElementById('ilEditId').value;
            var err = document.getElementById('ilEditError');
            if (err) err.classList.add('d-none');
            var fd = new FormData(editForm);
            fetch(apiBase + '/' + id + '/update', {
                method : 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body   : fd,
            })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    if (j.status) {
                        var modalEl = document.getElementById('ilModalEdit');
                        if (modalEl) {
                            var em = bootstrap.Modal.getInstance(modalEl);
                            if (em) em.hide();
                        }
                        q = searchInput ? searchInput.value.trim() : '';
                        page = 1;
                        lastPage = 1;
                        if (galleryEl) galleryEl.innerHTML = '';
                        fetchPage(true);
                    } else if (err) {
                        err.textContent = j.message || 'Save failed';
                        err.classList.remove('d-none');
                    }
                })
                .catch(function () {
                    if (err) {
                        err.textContent = 'Save failed';
                        err.classList.remove('d-none');
                    }
                });
        });
    }

    fetchPage(true);
    setupInfinite();
})();
