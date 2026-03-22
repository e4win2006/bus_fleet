<?php if (!isset($show_sidebar)) $show_sidebar = true; ?>
<?php if ($show_sidebar): ?>
        </main>
    </div>
<?php endif; ?>

<!-- Shared scripts: dynamic date and small animations -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
    // Live clock – runs immediately (footer loads after all page HTML)
    const DAYS   = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    const MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    function tickClock() {
        const els = document.querySelectorAll('.date-display');
        if (!els.length) return;
        const now = new Date();
        const day  = DAYS[now.getDay()];
        const date = now.getDate() + ' ' + MONTHS[now.getMonth()] + ' ' + now.getFullYear();
        const h = String(now.getHours()).padStart(2,'0');
        const m = String(now.getMinutes()).padStart(2,'0');
        const s = String(now.getSeconds()).padStart(2,'0');
        const html =
            '<span style="font-size:12px;font-weight:600;opacity:0.7;">' + day + '</span>' +
            '<span style="margin:0 5px;opacity:0.4;">·</span>' +
            '<span style="font-weight:600;">' + date + '</span>' +
            '<span style="margin:0 7px;opacity:0.3;">|</span>' +
            '<span style="font-size:15px;font-weight:700;letter-spacing:0.04em;font-variant-numeric:tabular-nums;">' + h + ':' + m + ':' + s + '</span>';
        els.forEach(function(el) { el.innerHTML = html; });
    }
    tickClock();
    setInterval(tickClock, 1000);

        // Automatic / Manual Dark Theme logic
        const themeToggle = document.getElementById('theme-toggle');
        const iconPlaceholder = themeToggle ? themeToggle.querySelector('i') : null;
        
        function applyTheme(isDark) {
            if (isDark) {
                document.body.classList.add('dark-theme');
                if (iconPlaceholder) iconPlaceholder.setAttribute('data-lucide', 'sun');
            } else {
                document.body.classList.remove('dark-theme');
                if (iconPlaceholder) iconPlaceholder.setAttribute('data-lucide', 'moon');
            }
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        // Check local storage first
        const savedTheme = localStorage.getItem('fleetvision-theme');
        if (savedTheme) {
            applyTheme(savedTheme === 'dark');
        } else {
            // Otherwise apply automatic sun down check
            const hour = new Date().getHours();
            // Assume sun goes down after 6 PM (18:00) and comes up at 6 AM (06:00)
            applyTheme(hour >= 18 || hour < 6);
        }

        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                const isCurrentlyDark = document.body.classList.contains('dark-theme');
                const newTheme = isCurrentlyDark ? 'light' : 'dark';
                localStorage.setItem('fleetvision-theme', newTheme);
                applyTheme(newTheme === 'dark');
            });
        }

        // Initialize modern SVG icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Staggered reveal for KPI cards
        const kpis = Array.from(document.querySelectorAll('.kpi-card'));
        kpis.forEach((el, i) => {
            setTimeout(() => el.classList.add('visible'), i * 100);
        });

        // Reveal chart cards
        const charts = Array.from(document.querySelectorAll('.chart-card'));
        charts.forEach((el, i) => {
            setTimeout(() => el.classList.add('visible'), 300 + i * 150);
        });

        // Animate status bar fills from data-percent
        const fills = Array.from(document.querySelectorAll('.status-bar-fill'));
        fills.forEach((fill, i) => {
            const pct = fill.getAttribute('data-percent') || '0%';
            setTimeout(() => {
                fill.style.width = pct;
            }, 400 + i * 120);
        });
    });
</script>

<!-- jQuery CDN -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- FleetVision jQuery Animation Layer -->
<script>
$(function () {

    /* ── 1. Page fade-in ─────────────────────────────────────────── */
    $('body').css('opacity', 0).animate({ opacity: 1 }, 400);

    /* ── 2. Staggered table row slide-in ────────────────────────── */
    $('.data-table tbody tr').each(function (i) {
        $(this).css({ opacity: 0, transform: 'translateX(-12px)' })
            .delay(i * 45)
            .animate({ opacity: 1 }, {
                duration: 280,
                step: function (n) {
                    $(this).css('transform', 'translateX(' + (-12 + 12 * n) + 'px)');
                }
            });
    });

    /* ── 3. Staggered maintenance / action cards ────────────────── */
    $('.maintenance-card, .user-form-card, .kpi-card, .chart-card, .report-card').each(function (i) {
        $(this).css({ opacity: 0, transform: 'translateY(16px)' })
            .delay(i * 60)
            .animate({ opacity: 1 }, {
                duration: 350,
                step: function (n) {
                    $(this).css('transform', 'translateY(' + (16 - 16 * n) + 'px)');
                }
            });
    });

    /* ── 4. KPI number counter animation ────────────────────────── */
    $('.kpi-value').each(function () {
        var $el   = $(this);
        var final = parseInt($el.text(), 10);
        if (isNaN(final) || final === 0) return;
        $el.text('0');
        $({ val: 0 }).animate({ val: final }, {
            duration: 900,
            easing: 'swing',
            step: function () { $el.text(Math.ceil(this.val)); },
            complete: function () { $el.text(final); }
        });
    });

    /* ── 5. Alert auto-dismiss (success/error) ──────────────────── */
    $('.alert-success, .alert-error').each(function () {
        var $alert = $(this);
        $alert.css({ position: 'relative', overflow: 'hidden' });
        // Progress bar
        $('<div>').css({
            position: 'absolute', bottom: 0, left: 0,
            height: '3px', width: '100%',
            background: $alert.hasClass('alert-success') ? '#10b981' : '#ef4444',
            borderRadius: '0 0 6px 6px'
        }).appendTo($alert).animate({ width: '0%' }, 4000);

        setTimeout(function () {
            $alert.slideUp(400, function () { $(this).remove(); });
        }, 4200);
    });

    /* ── 6. Button ripple effect ────────────────────────────────── */
    $(document).on('click', '.btn-submit, .m-btn, button[type="submit"]', function (e) {
        var $btn  = $(this);
        var offset = $btn.offset();
        var x = e.pageX - offset.left;
        var y = e.pageY - offset.top;
        var $ripple = $('<span>').css({
            position: 'absolute', borderRadius: '50%',
            background: 'rgba(255,255,255,0.35)',
            width: 0, height: 0,
            left: x, top: y,
            transform: 'translate(-50%,-50%)',
            pointerEvents: 'none'
        });
        if ($btn.css('position') === 'static') $btn.css('position', 'relative');
        $btn.css('overflow', 'hidden').append($ripple);
        $ripple.animate({ width: 200, height: 200, opacity: 0 }, {
            duration: 500,
            complete: function () { $(this).remove(); }
        });
    });

    /* ── 7. Nav item hover micro-bounce ─────────────────────────── */
    $('.nav-item').on('mouseenter', function () {
        $(this).stop(true).animate({ paddingLeft: '26px' }, 120);
    }).on('mouseleave', function () {
        $(this).stop(true).animate({ paddingLeft: '20px' }, 120);
    });

    /* ── 8. Form input focus glow ───────────────────────────────── */
    $(document).on('focus', '.form-input, .form-select, .form-textarea', function () {
        $(this).stop(true).animate({ borderWidth: '2px' }, 150);
    }).on('blur', '.form-input, .form-select, .form-textarea', function () {
        $(this).stop(true).animate({ borderWidth: '1px' }, 150);
    });

    /* ── 9. Status badge pulse on load ─────────────────────────── */
    $('.status-badge.active').each(function () {
        var $b = $(this);
        (function pulse() {
            $b.animate({ opacity: 0.6 }, 800).animate({ opacity: 1 }, 800, pulse);
        })();
    });

    /* ── 10. Action card (quick action) lift on hover ───────────── */
    $(document).on('mouseenter', '.action-card', function () {
        $(this).stop(true).animate({ marginTop: '-4px', marginBottom: '4px' }, 150);
    }).on('mouseleave', '.action-card', function () {
        $(this).stop(true).animate({ marginTop: '0px', marginBottom: '0px' }, 150);
    });

    /* ── 11. Table row highlight on click ───────────────────────── */
    $(document).on('click', '.data-table tbody tr', function () {
        $('.data-table tbody tr').css('background', '');
        $(this).css('background', '#eff6ff');
        setTimeout(() => $(this).css('background', ''), 1200);
    });

    /* ── 12. Search input live table filter ─────────────────────── */
    $(document).on('keyup', '.search-input', function () {
        var q = $(this).val().toLowerCase();
        $(this).closest('section, .content-section').find('.data-table tbody tr').each(function () {
            var text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(q));
        });
    });

    /* ── 13. Sidebar active item slide-in indicator ─────────────── */
    $('.nav-item.active').css({ borderLeftWidth: 0 })
        .animate({ borderLeftWidth: 3 }, 400);

});
</script>
</body>
</html>
