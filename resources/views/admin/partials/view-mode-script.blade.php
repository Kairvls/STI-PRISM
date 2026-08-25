{{-- Shared Cards/Table view mode helper. Include once per page. --}}
<style>
    .admin-view-panel {
        opacity: 1;
        transform: translateY(0);
        transition:
            opacity 220ms cubic-bezier(0.22, 1, 0.36, 1),
            transform 220ms cubic-bezier(0.22, 1, 0.36, 1);
        will-change: opacity, transform;
    }
    .admin-view-panel.is-leaving {
        opacity: 0;
        transform: translateY(6px);
        pointer-events: none;
    }
    .admin-view-panel.is-entering {
        opacity: 0;
        transform: translateY(6px);
    }
    .admin-view-panel.is-visible {
        opacity: 1;
        transform: translateY(0);
    }
    @media (prefers-reduced-motion: reduce) {
        .admin-view-panel,
        .admin-view-panel.is-leaving,
        .admin-view-panel.is-entering,
        .admin-view-panel.is-visible {
            transition: none !important;
            transform: none !important;
        }
    }
</style>
<script>
(function () {
    if (window.__adminViewModeInit) return;
    window.__adminViewModeInit = true;

    var PANEL_MS = 220;
    var reduceMotion = false;
    try {
        reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    } catch (e) {}

    function preparePanel(el) {
        if (!el) return;
        el.classList.add('admin-view-panel');
    }

    function setButtons(buttons, mode, animateThumb) {
        buttons.forEach(function (btn) {
            var active = btn.getAttribute('data-view-mode') === mode;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        var switcher = buttons.length ? buttons[0].closest('.admin-view-switcher') : null;
        if (switcher && typeof window.syncAdminViewSwitcherThumb === 'function') {
            window.syncAdminViewSwitcherThumb(switcher, animateThumb !== false);
        }
    }

    window.applyAdminViewMode = function (opts) {
        var mode = opts.mode === 'cards' ? 'cards' : 'table';
        var table = document.getElementById(opts.tableId);
        var cards = document.getElementById(opts.cardsId);
        var buttons = document.querySelectorAll(opts.buttonSelector || '.admin-view-mode-btn');
        var useCards = mode === 'cards';
        var animate = opts.animate !== false && !reduceMotion;

        preparePanel(table);
        preparePanel(cards);
        setButtons(buttons, mode, animate);

        var showEl = useCards ? cards : table;
        var hideEl = useCards ? table : cards;

        if (!animate) {
            if (hideEl) {
                hideEl.classList.add('hidden');
                hideEl.classList.remove('is-visible', 'is-entering', 'is-leaving');
            }
            if (showEl) {
                showEl.classList.remove('hidden', 'is-entering', 'is-leaving');
                showEl.classList.add('is-visible');
            }
            return;
        }

        if (hideEl && !hideEl.classList.contains('hidden')) {
            hideEl.classList.remove('is-visible', 'is-entering');
            hideEl.classList.add('is-leaving');
            window.setTimeout(function () {
                hideEl.classList.add('hidden');
                hideEl.classList.remove('is-leaving');
            }, PANEL_MS);
        } else if (hideEl) {
            hideEl.classList.add('hidden');
            hideEl.classList.remove('is-visible', 'is-entering', 'is-leaving');
        }

        if (showEl) {
            showEl.classList.remove('hidden', 'is-leaving', 'is-visible');
            showEl.classList.add('is-entering');
            void showEl.offsetWidth;
            requestAnimationFrame(function () {
                showEl.classList.remove('is-entering');
                showEl.classList.add('is-visible');
            });
        }
    };

    window.bindAdminViewMode = function (opts) {
        document.querySelectorAll(opts.buttonSelector || '.admin-view-mode-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                window.applyAdminViewMode({
                    mode: btn.getAttribute('data-view-mode') || 'table',
                    tableId: opts.tableId,
                    cardsId: opts.cardsId,
                    buttonSelector: opts.buttonSelector,
                    storageKey: opts.storageKey,
                    animate: true,
                });
            });
        });

        // Always open in table view when navigating to the page.
        window.applyAdminViewMode({
            mode: 'table',
            tableId: opts.tableId,
            cardsId: opts.cardsId,
            buttonSelector: opts.buttonSelector,
            storageKey: opts.storageKey,
            animate: false,
        });
    };
})();
</script>
