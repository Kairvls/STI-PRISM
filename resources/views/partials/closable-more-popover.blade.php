{{--
  Reusable "+N more" pill with hover/click teleported popover and close (×) button.
  Expects:
    $moreCount (int)
    $heading (string, e.g. 'Canvass items')
    $rows (array|Collection of ['title' => string, 'meta' => ?string])
    $popoverKey (string unique key)
--}}
@php
    $moreCount = (int) ($moreCount ?? 0);
    $heading = trim((string) ($heading ?? 'Items'));
    $rows = collect($rows ?? [])->values();
    $popoverId = 'more-pop-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', (string) ($popoverKey ?? uniqid()));
@endphp

@once
<style>
    .prism-more-panel {
        position: fixed;
        z-index: 12060;
        min-width: 240px;
        max-width: min(320px, calc(100vw - 16px));
        padding: 4px 0 6px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #ffffff;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.16);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transform: translateY(-3px);
        transition: opacity 0.15s ease, transform 0.15s ease, visibility 0.15s;
    }
    .prism-more-panel.is-visible {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transform: translateY(0);
    }
</style>
<script>
    (function () {
        if (window.__prismMorePopInit) return;
        window.__prismMorePopInit = true;

        var activeWrap = null;
        var hideTimer = null;

        function panelFor(wrap) {
            if (!wrap) return null;
            var id = wrap.getAttribute('data-prism-more-id');
            return id ? document.getElementById(id) : wrap.querySelector('.prism-more-panel');
        }

        function placePanel(wrap) {
            var panel = panelFor(wrap);
            var btn = wrap.querySelector('[data-prism-more-btn]');
            if (!panel || !btn) return;
            if (panel.parentElement !== document.body) {
                document.body.appendChild(panel);
            }
            var rect = btn.getBoundingClientRect();
            var gap = 6;
            panel.hidden = false;
            panel.style.left = '8px';
            panel.style.top = '8px';
            var panelRect = panel.getBoundingClientRect();
            var left = Math.max(8, Math.min(rect.left, window.innerWidth - panelRect.width - 8));
            var top = rect.bottom + gap;
            if (top + panelRect.height > window.innerHeight - 8 && rect.top > panelRect.height + gap) {
                top = rect.top - panelRect.height - gap;
            }
            panel.style.left = left + 'px';
            panel.style.top = Math.max(8, top) + 'px';
        }

        function hideAll() {
            clearTimeout(hideTimer);
            document.querySelectorAll('.prism-more-panel.is-visible').forEach(function (el) {
                el.classList.remove('is-visible');
            });
            if (activeWrap) {
                activeWrap.querySelector('[data-prism-more-btn]')?.setAttribute('aria-expanded', 'false');
                activeWrap = null;
            }
        }

        function showWrap(wrap) {
            if (!wrap) return;
            clearTimeout(hideTimer);
            if (activeWrap && activeWrap !== wrap) hideAll();
            activeWrap = wrap;
            placePanel(wrap);
            var panel = panelFor(wrap);
            if (panel) panel.classList.add('is-visible');
            wrap.querySelector('[data-prism-more-btn]')?.setAttribute('aria-expanded', 'true');
        }

        function scheduleHide(wrap) {
            clearTimeout(hideTimer);
            hideTimer = setTimeout(function () {
                if (activeWrap === wrap) hideAll();
            }, 130);
        }

        window.togglePrismMorePopover = function (btn, event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            var wrap = btn && btn.closest('[data-prism-more]');
            if (!wrap) return;
            if (activeWrap === wrap) {
                hideAll();
            } else {
                showWrap(wrap);
            }
        };

        window.closePrismMorePopover = function (event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            hideAll();
        };

        document.addEventListener('mouseover', function (event) {
            var wrap = event.target.closest && event.target.closest('[data-prism-more]');
            var panel = event.target.closest && event.target.closest('.prism-more-panel');
            if (panel && activeWrap) {
                clearTimeout(hideTimer);
                return;
            }
            if (wrap) showWrap(wrap);
        });

        document.addEventListener('mouseout', function (event) {
            var fromWrap = event.target.closest && event.target.closest('[data-prism-more]');
            var fromPanel = event.target.closest && event.target.closest('.prism-more-panel');
            var next = event.relatedTarget;
            if (fromWrap) {
                if (next && (fromWrap.contains(next) || (panelFor(fromWrap) && panelFor(fromWrap).contains(next)))) return;
                scheduleHide(fromWrap);
                return;
            }
            if (fromPanel && activeWrap) {
                if (next && (activeWrap.contains(next) || fromPanel.contains(next))) return;
                scheduleHide(activeWrap);
            }
        });

        document.addEventListener('click', function (event) {
            if (event.target.closest('[data-prism-more-close]')) {
                event.preventDefault();
                event.stopPropagation();
                hideAll();
                return;
            }
            if (event.target.closest('[data-prism-more]') || event.target.closest('.prism-more-panel')) return;
            hideAll();
        }, true);

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') hideAll();
        });

        window.addEventListener('scroll', function () {
            if (activeWrap) placePanel(activeWrap);
        }, true);

        window.addEventListener('resize', function () {
            if (activeWrap) placePanel(activeWrap);
        });
    })();
</script>
@endonce

@if ($moreCount > 0)
    <span
        class="relative inline-flex shrink-0"
        data-prism-more
        data-prism-more-id="{{ $popoverId }}"
    >
        <button
            type="button"
            data-prism-more-btn
            aria-expanded="false"
            aria-controls="{{ $popoverId }}"
            onclick="window.togglePrismMorePopover(this, event)"
            class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-bold leading-4 transition"
            style="background:rgba(0,55,199,0.1);color:#0037C7;border:1px solid rgba(0,55,199,0.2);"
        >
            +{{ $moreCount }} more
        </button>
        <span id="{{ $popoverId }}" class="prism-more-panel" role="tooltip">
            <span class="flex items-center justify-between gap-2 px-3 pb-1.5 pt-2">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                    {{ $heading }} · {{ $rows->count() }}
                </span>
                <button
                    type="button"
                    data-prism-more-close
                    onclick="window.closePrismMorePopover(event)"
                    class="inline-flex h-5 w-5 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    title="Close"
                    aria-label="Close"
                >
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </span>
            @foreach ($rows as $popRow)
                <span class="flex items-start justify-between gap-3 px-3 py-1.5">
                    <span class="min-w-0">
                        <span class="block truncate text-xs font-semibold text-slate-800">{{ $popRow['title'] ?? '—' }}</span>
                        @if (!empty($popRow['meta']))
                            <span class="block truncate text-[10.5px] text-slate-400">{{ $popRow['meta'] }}</span>
                        @endif
                    </span>
                </span>
            @endforeach
        </span>
    </span>
@endif
