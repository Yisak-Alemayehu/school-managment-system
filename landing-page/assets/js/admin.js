/**
 * Eduelevate - Admin Dashboard JavaScript
 */
document.addEventListener('DOMContentLoaded', function() {
    // --- Sidebar toggle for mobile ---
    var sidebarToggle = document.getElementById('sidebar-toggle');
    var sidebar = document.getElementById('admin-sidebar');
    var sidebarOverlay = document.getElementById('sidebar-overlay');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('-translate-x-full');
            if (sidebarOverlay) sidebarOverlay.classList.toggle('hidden');
        });
    }
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
        });
    }

    // --- Auto-hide flash messages ---
    document.querySelectorAll('[data-flash]').forEach(function(el) {
        setTimeout(function() {
            el.style.transition = 'opacity 0.3s ease';
            el.style.opacity = '0';
            setTimeout(function() { el.remove(); }, 300);
        }, 4000);
    });

    // --- Confirm on delete ---
    document.querySelectorAll('[data-confirm]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm(btn.dataset.confirm || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });

    // --- Tab switching ---
    document.querySelectorAll('[data-tab-target]').forEach(function(tab) {
        tab.addEventListener('click', function() {
            var target = tab.dataset.tabTarget;
            var group = tab.closest('[data-tab-group]');
            if (group) {
                group.querySelectorAll('[data-tab-target]').forEach(function(t) {
                    t.classList.remove('bg-primary-600', 'text-white');
                    t.classList.add('bg-gray-100', 'text-gray-600');
                });
                tab.classList.add('bg-primary-600', 'text-white');
                tab.classList.remove('bg-gray-100', 'text-gray-600');
            }
            document.querySelectorAll('[data-tab-content]').forEach(function(content) {
                content.classList.add('hidden');
            });
            var targetEl = document.querySelector('[data-tab-content="' + target + '"]');
            if (targetEl) targetEl.classList.remove('hidden');
        });
    });
});
