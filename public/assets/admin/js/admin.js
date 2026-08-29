document.addEventListener('DOMContentLoaded', function () {

    // ── Sidebar toggle ────────────────────────────────────
    const toggler = document.getElementById('sidebarToggler');
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    function openSidebar() {
        sidebar?.classList.add('sidebar-open');
        overlay?.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar?.classList.remove('sidebar-open');
        overlay?.classList.remove('active');
        document.body.style.overflow = '';
    }

    toggler?.addEventListener('click', openSidebar);
    overlay?.addEventListener('click', closeSidebar);

    // ── Auto-dismiss flash alerts ────────────────────────
    document.querySelectorAll('.alert-auto-dismiss').forEach(function (el) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            bsAlert?.close();
        }, 4500);
    });

    // ── SweetAlert2 theme ─────────────────────────────────
    if (typeof Swal !== 'undefined') {
        Swal.mixin({
            confirmButtonColor: '#8B1A1A',
            cancelButtonColor:  '#9B7575',
        });
    }
});

// ── Confirm delete (global helper) ───────────────────────
function confirmDelete(formId, name) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Delete "' + name + '"?',
            text:  'This action cannot be undone.',
            icon:  'warning',
            showCancelButton:   true,
            confirmButtonColor: '#8B1A1A',
            cancelButtonColor:  '#9B7575',
            confirmButtonText:  'Yes, delete',
            cancelButtonText:   'Cancel',
            borderRadius:       '12px',
        }).then(function (result) {
            if (result.isConfirmed) {
                document.getElementById(formId)?.submit();
            }
        });
    } else {
        if (confirm('Delete "' + name + '"? This cannot be undone.')) {
            document.getElementById(formId)?.submit();
        }
    }
}
