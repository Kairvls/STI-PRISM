{{-- Soft Cards | Table switcher (matches ATP/RFC status-filter style) --}}
@php
    $switcherId = $switcherId ?? 'adminViewSwitcher';
    $btnClass = $btnClass ?? 'admin-view-mode-btn';
@endphp
<div
    id="{{ $switcherId }}"
    class="admin-view-switcher relative inline-flex items-center rounded-lg bg-slate-100 p-1"
    role="group"
    aria-label="View mode"
>
    <span
        class="admin-view-switcher-thumb pointer-events-none absolute top-1 left-1 z-0 h-8 rounded-md bg-white shadow-sm"
        aria-hidden="true"
    ></span>
    <button
        type="button"
        data-view-mode="cards"
        class="{{ $btnClass }} relative z-10 inline-flex h-8 items-center gap-1.5 rounded-md px-3.5 text-xs font-semibold text-slate-500 transition-colors duration-200"
        title="Cards view"
    >
        Cards
    </button>
    <button
        type="button"
        data-view-mode="table"
        class="{{ $btnClass }} relative z-10 inline-flex h-8 items-center gap-1.5 rounded-md px-3.5 text-xs font-semibold text-slate-500 transition-colors duration-200"
        title="Table view"
    >
        Table
    </button>
</div>
@once
<style>
    .admin-view-switcher button.is-active {
        color: #020617;
    }
    .admin-view-switcher-thumb {
        transition:
            transform 220ms cubic-bezier(0.22, 1, 0.36, 1),
            width 220ms cubic-bezier(0.22, 1, 0.36, 1);
        will-change: transform, width;
    }
    @media (prefers-reduced-motion: reduce) {
        .admin-view-switcher-thumb {
            transition: none !important;
        }
    }
</style>
<script>
(function () {
    if (window.__adminViewSwitcherThumbInit) return;
    window.__adminViewSwitcherThumbInit = true;

    window.syncAdminViewSwitcherThumb = function (switcher, animate) {
        if (!switcher) return;
        var thumb = switcher.querySelector('.admin-view-switcher-thumb');
        var active = switcher.querySelector('button.is-active')
            || switcher.querySelector('[data-view-mode="table"]')
            || switcher.querySelector('button');
        if (!thumb || !active) return;

        var pad = 4;
        var x = active.offsetLeft - pad;
        var w = active.offsetWidth;

        if (animate === false) {
            var prev = thumb.style.transition;
            thumb.style.transition = 'none';
            thumb.style.width = w + 'px';
            thumb.style.transform = 'translate3d(' + x + 'px, 0, 0)';
            void thumb.offsetWidth;
            thumb.style.transition = prev || '';
            return;
        }

        thumb.style.width = w + 'px';
        thumb.style.transform = 'translate3d(' + x + 'px, 0, 0)';
    };

    window.addEventListener('resize', function () {
        document.querySelectorAll('.admin-view-switcher').forEach(function (el) {
            window.syncAdminViewSwitcherThumb(el, false);
        });
    });
})();
</script>
@endonce
