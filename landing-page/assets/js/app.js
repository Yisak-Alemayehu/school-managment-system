/**
 * Eduelevate - Landing Page JavaScript
 */
document.addEventListener('DOMContentLoaded', function() {
    // --- Scroll Animations ---
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('.animate-on-scroll').forEach(function(el) { observer.observe(el); });

    // --- Navbar Scroll Effect ---
    const navbar = document.getElementById('navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('navbar-scrolled');
            } else {
                navbar.classList.remove('navbar-scrolled');
            }
        });
    }

    // --- Mobile Menu Toggle ---
    const mobileToggle = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    if (mobileToggle && mobileMenu) {
        mobileToggle.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });
        // Close on link click
        mobileMenu.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function() { mobileMenu.classList.add('hidden'); });
        });
    }

    // --- FAQ Accordion ---
    document.querySelectorAll('.faq-item .faq-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var item = btn.closest('.faq-item');
            var wasOpen = item.classList.contains('faq-open');
            // Close all first
            document.querySelectorAll('.faq-item.faq-open').forEach(function(openItem) {
                openItem.classList.remove('faq-open');
            });
            // Toggle current
            if (!wasOpen) {
                item.classList.add('faq-open');
            }
        });
    });

    // --- Contact Form AJAX ---
    var contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var btn = contactForm.querySelector('button[type="submit"]');
            var originalText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner"></span> Sending...';
            btn.disabled = true;

            fetch(contactForm.getAttribute('action') || window.location.origin + '/contact', {
                method: 'POST',
                body: new FormData(contactForm)
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.innerHTML = originalText;
                btn.disabled = false;
                var msgEl = document.getElementById('contactFormMessage');
                if (data.success) {
                    msgEl.className = 'mt-3 p-3 bg-green-50 text-green-700 rounded-xl text-sm';
                    msgEl.textContent = data.message || 'Message sent successfully!';
                    contactForm.reset();
                } else {
                    msgEl.className = 'mt-3 p-3 bg-red-50 text-red-700 rounded-xl text-sm';
                    msgEl.textContent = data.message || 'Something went wrong. Please try again.';
                }
                msgEl.classList.remove('hidden');
                setTimeout(function() { msgEl.classList.add('hidden'); }, 5000);
            })
            .catch(function() {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        });
    }

    // --- Showcase Tabs ---
    document.querySelectorAll('.showcase-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            // Update active tab
            document.querySelectorAll('.showcase-tab').forEach(function(t) { t.classList.remove('active'); });
            tab.classList.add('active');
            // Show matching panel
            var targetPanel = tab.dataset.tab;
            document.querySelectorAll('.showcase-panel').forEach(function(panel) {
                if (panel.dataset.panel === targetPanel) {
                    panel.style.display = '';
                    panel.classList.add('active');
                } else {
                    panel.style.display = 'none';
                    panel.classList.remove('active');
                }
            });
            // Update URL in browser chrome
            var urlPath = document.getElementById('showcase-url-path');
            if (urlPath) urlPath.textContent = targetPanel;
            // Swap floating badges
            document.querySelectorAll('.showcase-badge').forEach(function(badge) {
                if (badge.dataset.badgeFor === targetPanel) {
                    badge.classList.remove('lg:hidden');
                    badge.classList.add('lg:block');
                } else {
                    badge.classList.remove('lg:block');
                    badge.classList.add('lg:hidden');
                }
            });
        });
    });

    // --- PWA Mobile Showcase ---
    // Role tab switching
    document.querySelectorAll('.pwa-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.pwa-tab').forEach(function(t) { t.classList.remove('active'); });
            tab.classList.add('active');
            var role = tab.dataset.pwaTab;
            document.querySelectorAll('.pwa-role-panel').forEach(function(panel) {
                if (panel.dataset.pwaPanel === role) {
                    panel.style.display = '';
                } else {
                    panel.style.display = 'none';
                }
            });
        });
    });

    // Phone slider: click a phone to make it active
    function activatePhone(slider, index) {
        var phones = slider.querySelectorAll('.pwa-phone-frame');
        var sliderName = slider.dataset.pwaSlider;
        phones.forEach(function(phone, i) {
            if (i === index) {
                phone.classList.remove('pwa-phone-side');
                phone.classList.add('pwa-phone-active');
            } else {
                phone.classList.remove('pwa-phone-active');
                phone.classList.add('pwa-phone-side');
            }
        });
        // Update dots
        document.querySelectorAll('.pwa-dot[data-slider="' + sliderName + '"]').forEach(function(dot) {
            if (parseInt(dot.dataset.dot, 10) === index) {
                dot.classList.add('active');
            } else {
                dot.classList.remove('active');
            }
        });
    }

    document.querySelectorAll('.pwa-phone-slider').forEach(function(slider) {
        slider.querySelectorAll('.pwa-phone-frame').forEach(function(phone) {
            phone.addEventListener('click', function() {
                var idx = parseInt(phone.dataset.screenIndex, 10);
                activatePhone(slider, idx);
            });
        });
    });

    // Dots click
    document.querySelectorAll('.pwa-dot').forEach(function(dot) {
        dot.addEventListener('click', function() {
            var sliderName = dot.dataset.slider;
            var idx = parseInt(dot.dataset.dot, 10);
            var slider = document.querySelector('.pwa-phone-slider[data-pwa-slider="' + sliderName + '"]');
            if (slider) activatePhone(slider, idx);
        });
    });

    // Auto-rotate phones every 3s
    document.querySelectorAll('.pwa-phone-slider').forEach(function(slider) {
        var count = slider.querySelectorAll('.pwa-phone-frame').length;
        var current = 0;
        setInterval(function() {
            // Only auto-rotate if section is visible
            var rect = slider.getBoundingClientRect();
            if (rect.top < window.innerHeight && rect.bottom > 0) {
                current = (current + 1) % count;
                activatePhone(slider, current);
            }
        }, 3500);
    });

    // --- Smooth scroll for anchor links ---
    document.querySelectorAll('a[href^="#"]').forEach(function(link) {
        link.addEventListener('click', function(e) {
            var target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // --- Counter Animation ---
    function animateCounter(el) {
        var target = parseInt(el.dataset.count, 10);
        var duration = 2000;
        var start = 0;
        var startTime = null;
        function step(timestamp) {
            if (!startTime) startTime = timestamp;
            var progress = Math.min((timestamp - startTime) / duration, 1);
            el.textContent = Math.floor(progress * target).toLocaleString();
            if (progress < 1) requestAnimationFrame(step);
            else el.textContent = target.toLocaleString();
        }
        requestAnimationFrame(step);
    }
    var counterObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                counterObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });
    document.querySelectorAll('[data-count]').forEach(function(el) { counterObserver.observe(el); });

    // --- Password toggle ---
    document.querySelectorAll('.password-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var input = btn.parentElement.querySelector('input');
            var icon = btn.querySelector('svg');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
            }
        });
    });
});
