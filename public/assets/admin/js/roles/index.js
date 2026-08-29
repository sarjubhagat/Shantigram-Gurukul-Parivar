document.addEventListener('DOMContentLoaded', function () {
    if (typeof $.fn.DataTable !== 'undefined' && document.getElementById('rolesTable')) {
        $('#rolesTable').DataTable({
            pageLength: 15,
            order: [[0, 'asc']],
            columnDefs: [{ orderable: false, targets: [-1] }],
            language: { search: '', searchPlaceholder: 'Search roles...' },
        });
    }

    // Toggle all permissions in a group
    document.querySelectorAll('.toggle-group').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const group = this.dataset.group;
            const checkboxes = document.querySelectorAll('[data-group="' + group + '"] input[type="checkbox"]');
            const allChecked = [...checkboxes].every(c => c.checked);
            checkboxes.forEach(c => c.checked = !allChecked);
        });
    });
});
