{{--
  Equipment column: first item + "+N more" hover list (borrowing-style).
  Expects: $ris
  Optional: $muted (bool) for dimmed rows
--}}
@php
    $muted = ! empty($muted);
    $itemRows = collect($ris->risEquipmentItems ?? []);
    $names = \App\Support\RisWorkflow::equipmentItemNames($ris);
    if ($names === [] && $itemRows->isNotEmpty()) {
        $names = $itemRows
            ->map(fn ($item) => trim((string) ($item->ris_item_name_description ?? '')))
            ->filter()
            ->values()
            ->all();
    }
    $primaryName = $names[0] ?? \App\Support\RisWorkflow::sourceLabel($ris);
    $moreCount = max(0, count($names) - 1);
    $requestTypeLabel = ! empty($ris->ris_request_type)
        ? \App\Support\RisWorkflow::requestTypeLabel($ris)
        : '';
    $nameClass = $muted ? 'text-gray-500' : 'text-gray-700';
    $metaClass = $muted ? 'text-gray-400' : 'text-gray-400';
    $popoverId = 'ris-eq-pop-'.(int) ($ris->ris_id ?? 0).'-'.substr(md5(($primaryName ?: '').'|'.$moreCount), 0, 6);
@endphp

@once
<style>
    .ris-eq-more-panel {
        position: fixed;
        z-index: 12050;
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
    .ris-eq-more-panel.is-visible {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transform: translateY(0);
    }
</style>
<script>
    (function () {
        if (window.__risEqMoreInit) return;
        window.__risEqMoreInit = true;

        var activeWrap = null;
        var hideTimer = null;

        function panelFor(wrap) {
            if (!wrap) return null;
            var id = wrap.getAttribute('data-ris-eq-panel-id');
            return id ? document.getElementById(id) : wrap.querySelector('.ris-eq-more-panel');
        }

        function placePanel(wrap) {
            var panel = panelFor(wrap);
            var btn = wrap.querySelector('[data-ris-eq-btn]');
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

        function showWrap(wrap) {
            if (!wrap) return;
            clearTimeout(hideTimer);
            if (activeWrap && activeWrap !== wrap) hideWrap(activeWrap);
            activeWrap = wrap;
            placePanel(wrap);
            var panel = panelFor(wrap);
            if (panel) panel.classList.add('is-visible');
            wrap.querySelector('[data-ris-eq-btn]')?.setAttribute('aria-expanded', 'true');
        }

        function hideWrap(wrap) {
            if (!wrap) return;
            var panel = panelFor(wrap);
            if (panel) panel.classList.remove('is-visible');
            wrap.querySelector('[data-ris-eq-btn]')?.setAttribute('aria-expanded', 'false');
            if (activeWrap === wrap) activeWrap = null;
        }

        function scheduleHide(wrap) {
            clearTimeout(hideTimer);
            hideTimer = setTimeout(function () {
                hideWrap(wrap);
            }, 120);
        }

        window.toggleRisEqMorePopover = function (button, event) {
            event.preventDefault();
            event.stopPropagation();
            var wrap = button.closest('[data-ris-eq-more]');
            if (!wrap) return;
            if (activeWrap === wrap) {
                hideWrap(wrap);
            } else {
                showWrap(wrap);
            }
        };

        document.addEventListener('mouseover', function (event) {
            var wrap = event.target.closest && event.target.closest('[data-ris-eq-more]');
            var panel = event.target.closest && event.target.closest('.ris-eq-more-panel');
            if (panel && activeWrap) {
                clearTimeout(hideTimer);
                return;
            }
            if (wrap) showWrap(wrap);
        });

        document.addEventListener('mouseout', function (event) {
            var fromWrap = event.target.closest && event.target.closest('[data-ris-eq-more]');
            var fromPanel = event.target.closest && event.target.closest('.ris-eq-more-panel');
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
            if (event.target.closest('[data-ris-eq-more]') || event.target.closest('.ris-eq-more-panel')) return;
            if (activeWrap) hideWrap(activeWrap);
        }, true);

        window.addEventListener('scroll', function () {
            if (activeWrap) placePanel(activeWrap);
        }, true);

        window.addEventListener('resize', function () {
            if (activeWrap) placePanel(activeWrap);
        });
    })();
</script>
@endonce

<div class="min-w-0">
    <div class="flex min-w-0 items-center gap-1.5">
        <span
            class="min-w-0 truncate text-sm font-medium {{ $nameClass }}"
            data-tip="{{ $primaryName }}"
            data-truncate-tip="{{ $primaryName }}"
            title="{{ $primaryName }}"
        >{{ $primaryName !== '' ? $primaryName : '—' }}</span>

        @if ($moreCount > 0)
            <span
                class="ris-eq-more-pop relative inline-flex shrink-0"
                data-ris-eq-more
                data-ris-eq-panel-id="{{ $popoverId }}"
            >
                <button
                    type="button"
                    data-ris-eq-btn
                    aria-expanded="false"
                    aria-controls="{{ $popoverId }}"
                    onclick="toggleRisEqMorePopover(this, event)"
                    class="inline-flex items-center rounded-md bg-[#0037C7]/10 px-1.5 py-0.5 text-[10px] font-bold leading-4 text-[#0037C7] ring-1 ring-[#0037C7]/20 transition hover:bg-[#0037C7] hover:text-white"
                >
                    +{{ $moreCount }} more
                </button>
                <span id="{{ $popoverId }}" class="ris-eq-more-panel" role="tooltip">
                    <span class="block px-3 pb-1.5 pt-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                        RIS equipment · {{ count($names) }}
                    </span>
                    @foreach ($names as $index => $itemName)
                        @php
                            $row = $itemRows->get($index);
                            $qty = $row->ris_quantity_requested ?? null;
                            $amount = isset($row->ris_total_amount) ? (float) $row->ris_total_amount : null;
                        @endphp
                        <span class="flex items-start justify-between gap-3 px-3 py-1.5">
                            <span class="min-w-0">
                                <span class="block truncate text-xs font-semibold text-slate-800">{{ $itemName }}</span>
                                @if ($qty !== null && $qty !== '')
                                    <span class="block truncate text-[10.5px] text-slate-400">Qty {{ $qty }}</span>
                                @endif
                            </span>
                        </span>
                    @endforeach
                </span>
            </span>
        @endif
    </div>

    @if ($requestTypeLabel !== '')
        <div class="mt-0.5 truncate text-[11px] {{ $metaClass }}">{{ $requestTypeLabel }}</div>
    @elseif ($moreCount > 0)
        <div class="mt-0.5 truncate text-[11px] {{ $metaClass }}">{{ count($names) }} items</div>
    @endif
</div>
