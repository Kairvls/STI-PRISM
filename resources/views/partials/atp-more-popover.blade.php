@php
    $atpList = array_values($atps ?? []);
    $firstAtp = $atpList[0] ?? null;
    $extraCount = max(0, count($atpList) - 1);
    $muted = ! empty($muted);
@endphp

@if ($firstAtp)
    <div class="mt-0.5 flex min-w-0 items-center gap-1.5">
        <span class="truncate text-[11px] font-medium {{ $muted ? 'text-gray-400' : 'text-slate-500' }}">
            {{ $firstAtp['number'] }}
        </span>
        @if ($extraCount > 0)
            <button
                type="button"
                class="atp-more-trigger shrink-0 rounded-md border px-1.5 py-0.5 text-[10px] font-semibold leading-none transition hover:opacity-90"
                style="background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8;"
                data-atp-items="{{ json_encode($atpList) }}"
                onclick="event.stopPropagation(); window.openAtpMorePopover && window.openAtpMorePopover(this, true)"
                onmouseenter="window.openAtpMorePopover && window.openAtpMorePopover(this, false)"
                onmouseleave="window.scheduleCloseAtpMorePopover && window.scheduleCloseAtpMorePopover()"
            >+{{ $extraCount }} more</button>
        @endif
    </div>
@endif

@once
    <div
        id="atpMorePopover"
        class="fixed hidden w-72 overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xl shadow-slate-900/10"
        style="z-index: 99999;"
        onmouseenter="window.cancelCloseAtpMorePopover && window.cancelCloseAtpMorePopover()"
        onmouseleave="window.scheduleCloseAtpMorePopover && window.scheduleCloseAtpMorePopover()"
        onclick="event.stopPropagation()"
    >
        <div class="flex items-center justify-between gap-2 px-4 pb-2 pt-3.5">
            <span id="atpMorePopoverHeading" class="text-[11px] font-bold uppercase tracking-wider text-slate-400">LINKED ATPS</span>
            <button
                type="button"
                onclick="window.closeAtpMorePopover && window.closeAtpMorePopover()"
                class="inline-flex h-6 w-6 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                aria-label="Close"
            >&times;</button>
        </div>
        <div id="atpMorePopoverList" class="max-h-60 space-y-2.5 overflow-y-auto px-4 pb-4 pt-1"></div>
    </div>

    <script>
        (function () {
            if (window._atpMorePopoverBound) return;
            window._atpMorePopoverBound = true;
            var closeTimer = null;
            var pinned = false;

            function getPop() {
                var el = document.getElementById('atpMorePopover');
                if (el && el.parentElement !== document.body) {
                    document.body.appendChild(el);
                }
                return el;
            }

            function esc(s) {
                return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }

            window.cancelCloseAtpMorePopover = function () {
                if (closeTimer) {
                    clearTimeout(closeTimer);
                    closeTimer = null;
                }
            };

            window.closeAtpMorePopover = function () {
                window.cancelCloseAtpMorePopover();
                pinned = false;
                var pop = getPop();
                if (pop) pop.classList.add('hidden');
            };

            window.scheduleCloseAtpMorePopover = function () {
                if (pinned) return;
                window.cancelCloseAtpMorePopover();
                closeTimer = setTimeout(function () {
                    var pop = getPop();
                    if (pop) pop.classList.add('hidden');
                }, 160);
            };

            window.openAtpMorePopover = function (trigger, isClick) {
                var pop = getPop();
                if (!pop || !trigger) return;
                window.cancelCloseAtpMorePopover();
                if (isClick) pinned = true;

                var items = [];
                try {
                    items = JSON.parse(trigger.getAttribute('data-atp-items') || '[]');
                } catch (e) {
                    return;
                }
                if (!items.length) return;

                var heading = document.getElementById('atpMorePopoverHeading');
                var list = document.getElementById('atpMorePopoverList');
                if (heading) heading.textContent = 'LINKED ATPS \u00b7 ' + items.length;
                if (list) {
                    list.innerHTML = items.map(function (it) {
                        var sub = it.supplier ? '<div class="mt-0.5 text-xs text-slate-500">' + esc(it.supplier) + '</div>' : '';
                        return '<div>' +
                            '<div class="text-sm font-semibold text-slate-900">' + esc(it.number) + '</div>' +
                            sub +
                        '</div>';
                    }).join('');
                }

                pop.classList.remove('hidden');
                var rect = trigger.getBoundingClientRect();
                var popW = pop.offsetWidth || 288;
                var popH = pop.offsetHeight || 160;
                var left = Math.max(12, Math.min(rect.left, window.innerWidth - popW - 12));
                var top = rect.bottom + 8;
                if (top + popH > window.innerHeight - 12 && rect.top - popH - 8 > 12) {
                    top = rect.top - popH - 8;
                }
                pop.style.left = left + 'px';
                pop.style.top = top + 'px';
            };

            document.addEventListener('click', function (e) {
                var pop = document.getElementById('atpMorePopover');
                if (!pop || pop.classList.contains('hidden')) return;
                if (pop.contains(e.target) || (e.target && e.target.closest && e.target.closest('.atp-more-trigger'))) return;
                window.closeAtpMorePopover();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') window.closeAtpMorePopover();
            });
            window.addEventListener('scroll', function () {
                if (!pinned) window.closeAtpMorePopover();
            }, true);
        })();
    </script>
@endonce
