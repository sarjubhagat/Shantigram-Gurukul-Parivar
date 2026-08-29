document.addEventListener('DOMContentLoaded', function () {
    if (typeof $.fn.DataTable !== 'undefined' && document.getElementById('usersTable')) {
        $('#usersTable').DataTable({
            pageLength: 15,
            order: [[0, 'desc']],
            columnDefs: [{ orderable: false, targets: [-1] }],
            language: { search: '', searchPlaceholder: 'Search users...' },
        });
    }
});
