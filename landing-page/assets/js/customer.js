/**
 * Eduelevate - Customer Dashboard JavaScript
 */
document.addEventListener('DOMContentLoaded', function() {
    // --- Auto-hide flash messages ---
    document.querySelectorAll('[data-flash]').forEach(function(el) {
        setTimeout(function() {
            el.style.transition = 'opacity 0.3s ease';
            el.style.opacity = '0';
            setTimeout(function() { el.remove(); }, 300);
        }, 4000);
    });

    // --- File input preview ---
    document.querySelectorAll('input[type="file"]').forEach(function(input) {
        input.addEventListener('change', function() {
            var fileName = input.files.length > 0 ? input.files[0].name : '';
            var label = input.closest('form')?.querySelector('.file-name');
            if (label) label.textContent = fileName;
        });
    });

    // --- Form validation feedback ---
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            var btn = form.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                var orig = btn.innerHTML;
                btn.innerHTML = '<span class="spinner"></span> Processing...';
                setTimeout(function() { btn.disabled = false; btn.innerHTML = orig; }, 5000);
            }
        });
    });
});
