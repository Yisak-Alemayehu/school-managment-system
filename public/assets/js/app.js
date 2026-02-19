/**
 * Urjiberi School ERP — Core JavaScript
 * Minimal vanilla JS for UI interactions
 */

// ── Sidebar Toggle ───────────────────────────────────────────
function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    if (!sidebar) return;

    var isOpen = !sidebar.classList.contains('-translate-x-full');
    if (isOpen) {
        sidebar.classList.add('-translate-x-full');
        if (overlay) overlay.classList.add('hidden');
        document.body.style.overflow = '';
    } else {
        sidebar.classList.remove('-translate-x-full');
        if (overlay) overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

// ── Profile Dropdown ─────────────────────────────────────────
function toggleProfileMenu() {
    var menu = document.getElementById('profile-menu');
    if (menu) {
        menu.classList.toggle('hidden');
    }
}

// Close profile menu on outside click
document.addEventListener('click', function(e) {
    var dropdown = document.getElementById('profile-dropdown');
    var menu = document.getElementById('profile-menu');
    if (dropdown && menu && !dropdown.contains(e.target)) {
        menu.classList.add('hidden');
    }
});

// ── Flash Message Auto-dismiss ───────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    var flashes = document.querySelectorAll('.flash-message');
    flashes.forEach(function(el) {
        setTimeout(function() {
            el.style.transition = 'opacity 0.3s';
            el.style.opacity = '0';
            setTimeout(function() { el.remove(); }, 300);
        }, 5000);
    });
});

// ── Confirm Delete ───────────────────────────────────────────
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

// ── Select All Checkbox ──────────────────────────────────────
function toggleSelectAll(masterCheckbox, className) {
    var checkboxes = document.querySelectorAll('.' + className);
    checkboxes.forEach(function(cb) {
        cb.checked = masterCheckbox.checked;
    });
}

// ── Dynamic Search Filter ────────────────────────────────────
function filterTable(inputId, tableId) {
    var input = document.getElementById(inputId);
    var table = document.getElementById(tableId);
    if (!input || !table) return;

    var filter = input.value.toLowerCase();
    var rows = table.querySelectorAll('tbody tr');
    rows.forEach(function(row) {
        var text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}

// ── Form Validation Highlight ────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    // Add error styling to inputs with errors
    var errorFields = document.querySelectorAll('[data-has-error]');
    errorFields.forEach(function(el) {
        el.classList.add('border-red-500', 'focus:ring-red-500');
    });
});

// ── AJAX Helper ──────────────────────────────────────────────
function ajaxRequest(url, options) {
    options = options || {};
    var method = options.method || 'GET';
    var data = options.data || null;
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');

    var headers = {
        'X-Requested-With': 'XMLHttpRequest'
    };
    if (csrfMeta) {
        headers['X-CSRF-TOKEN'] = csrfMeta.content;
    }
    if (data && !(data instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
        data = JSON.stringify(data);
    }

    return fetch(url, {
        method: method,
        headers: headers,
        body: data,
        credentials: 'same-origin'
    }).then(function(response) {
        return response.json();
    });
}

// ── Number Formatting ────────────────────────────────────────
function formatMoney(amount) {
    return parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// ── Print Helper ─────────────────────────────────────────────
function printContent(elementId) {
    var content = document.getElementById(elementId);
    if (!content) return;

    var win = window.open('', '_blank');
    win.document.write('<html><head><title>Print</title>');
    win.document.write('<link href="https://cdn.tailwindcss.com" rel="stylesheet">');
    win.document.write('</head><body class="p-4">');
    win.document.write(content.innerHTML);
    win.document.write('</body></html>');
    win.document.close();
    win.onload = function() { win.print(); win.close(); };
}

// ── Loading Spinner ──────────────────────────────────────────
function showLoading(button) {
    if (!button) return;
    button.disabled = true;
    button.dataset.originalText = button.innerHTML;
    button.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2 inline" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Processing...';
}

function hideLoading(button) {
    if (!button || !button.dataset.originalText) return;
    button.disabled = false;
    button.innerHTML = button.dataset.originalText;
}

// ── Service Worker Update Check ──────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.ready.then(function(reg) {
            // Check for updates every 60 minutes
            setInterval(function() {
                reg.update();
            }, 60 * 60 * 1000);
        });

        // Notify user of new version
        navigator.serviceWorker.addEventListener('controllerchange', function() {
            if (confirm('A new version of Urjiberi School ERP is available. Reload now?')) {
                window.location.reload();
            }
        });
    }
});

// ── Touch-friendly dropdown helper ───────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    // Close all dropdowns when tapping outside
    document.addEventListener('touchstart', function(e) {
        var openDropdowns = document.querySelectorAll('.dropdown-menu:not(.hidden)');
        openDropdowns.forEach(function(dropdown) {
            if (!dropdown.parentElement.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    });
});

// ── Debounce helper for search ───────────────────────────────
function debounce(func, wait) {
    var timeout;
    return function() {
        var context = this;
        var args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            func.apply(context, args);
        }, wait);
    };
}

// ── Back to top button ───────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('back-to-top');
    if (!btn) return;
    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            btn.classList.remove('hidden');
        } else {
            btn.classList.add('hidden');
        }
    });
    btn.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});
