// assets/js/main.js

// Auto-hide alerts after 4 seconds
document.addEventListener('DOMContentLoaded', function () {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(function () { alert.remove(); }, 500);
        }, 4000);
    });

    // Confirm delete buttons
    document.querySelectorAll('[data-confirm]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm)) e.preventDefault();
        });
    });

    // Active nav highlight
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-item').forEach(function (link) {
        if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href').replace('../', ''))) {
            link.classList.add('active');
        }
    });

    // Print button support
    document.querySelectorAll('.btn-print').forEach(function (btn) {
        btn.addEventListener('click', function () { window.print(); });
    });

    // Table row search filter
    const searchInputs = document.querySelectorAll('[data-table-search]');
    searchInputs.forEach(function (input) {
        input.addEventListener('keyup', function () {
            const query = this.value.toLowerCase();
            const tableId = this.dataset.tableSearch;
            const rows = document.querySelectorAll('#' + tableId + ' tbody tr');
            rows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    });
});
