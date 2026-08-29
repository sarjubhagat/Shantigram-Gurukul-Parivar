document.addEventListener('DOMContentLoaded', function () {
    if (typeof $.fn.DataTable !== 'undefined' && document.getElementById('permissionsTable')) {
        $('#permissionsTable').DataTable({
            pageLength: 20,
            order: [[0, 'asc']],
            columnDefs: [{ orderable: false, targets: [-1] }],
            language: { search: '', searchPlaceholder: 'Search permissions...' },
        });
    }
});
