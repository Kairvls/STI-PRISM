<script>
    (function () {
        if (window.__atpPopoverInit) return;
        window.__atpPopoverInit = true;

        let openPop = null;

        function close() {
            if (!openPop) return;
            openPop.panel.hidden = true;
            openPop.trigger.setAttribute('aria-expanded', 'false');
            openPop = null;
        }

        function place(trigger, panel) {
            const rect = trigger.getBoundingClientRect();
            const gap = 6;
            const width = panel.offsetWidth;
            const height = panel.offsetHeight;
            const left = Math.max(8, Math.min(rect.left, window.innerWidth - width - 8));
            const below = rect.bottom + gap;
            const top = below + height > window.innerHeight - 8 ? rect.top - height - gap : below;
            panel.style.left = left + 'px';
            panel.style.top = Math.max(8, top) + 'px';
        }

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-atp-pop-trigger]');
            if (trigger) {
                const panel = trigger.closest('[data-atp-pop]').querySelector('[data-atp-pop-panel]');
                const wasOpen = openPop && openPop.panel === panel;
                close();
                if (!wasOpen) {
                    panel.hidden = false;
                    place(trigger, panel);
                    trigger.setAttribute('aria-expanded', 'true');
                    openPop = { trigger, panel };
                }
                return;
            }
            if (openPop && !openPop.panel.contains(event.target)) close();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') close();
        });
        window.addEventListener('resize', close);
        window.addEventListener('scroll', close, true);
    })();
</script>
