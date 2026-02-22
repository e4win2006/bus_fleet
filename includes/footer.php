<?php if (!isset($show_sidebar)) $show_sidebar = true; ?>
<?php if ($show_sidebar): ?>
        </main>
    </div>
<?php endif; ?>

<!-- Shared scripts: dynamic date and small animations -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Set dynamic date if an element exists
        const dateEl = document.querySelector('.date-display');
        if (dateEl) {
            const now = new Date();
            const opts = { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric' };
            dateEl.textContent = '📅 ' + now.toLocaleDateString(undefined, opts);
        }

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
