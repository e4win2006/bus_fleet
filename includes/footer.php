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
</body>
</html>
