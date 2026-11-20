{{--
  Multi-brand RIS cell: checkbox list + chips with +N hover tooltip (rooms departments pattern).
  Expects Alpine parent: item, index, brandOptions, toggleRisSelect, isRisSelectOpen, closeRisSelect,
  risFieldOptions, risSelectQuery, toggleRisBrand, risBrandSelected, risBrandLabels, risBrandVisibleLabels,
  risBrandOverflowCount, addRisCatalogOption, risNewItemName, risNewItemSaving, risNewItemError
--}}
@php
    $namePrefix = $namePrefix ?? 'ris_items';
    $placeholder = $placeholder ?? 'Brand';
    $triggerClass = $triggerClass ?? 'w-full min-w-0 border-0 bg-transparent px-0.5 py-1.5 text-[10px] outline-none focus:ring-0 sm:px-1 sm:text-xs';
    $panelMinWidth = $panelMinWidth ?? 'min-w-[14rem]';
    $visibleChipLimit = 1;
@endphp
@once
<style>
    .ris-brand-more-panel {
        position: fixed;
        z-index: 12060;
        min-width: 160px;
        max-width: 240px;
        max-height: 220px;
        overflow-y: auto;
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
    .ris-brand-more-panel.is-visible {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transform: translateY(0);
    }
</style>
<script>
    (function () {
        if (window.__risBrandMoreInit) return;
        window.__risBrandMoreInit = true;

        var activeWrap = null;
        var hideTimer = null;
        var panel = document.createElement('div');
        panel.className = 'ris-brand-more-panel';
        panel.setAttribute('role', 'tooltip');
        panel.hidden = true;
        document.body.appendChild(panel);

        function parseLabels(wrap) {
            if (!wrap) return [];
            try {
                var raw = wrap.getAttribute('data-brand-labels') || '[]';
                var parsed = JSON.parse(raw);
                return Array.isArray(parsed) ? parsed.map(String) : [];
            } catch (e) {
                return [];
            }
        }

        function fillPanel(labels) {
            var html = '<span class="block px-3 pb-1.5 pt-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400">'
                + 'All brands · ' + labels.length + '</span>';
            labels.forEach(function (label) {
                html += '<span class="block truncate px-3 py-1.5 text-xs font-medium text-slate-700"></span>';
            });
            panel.innerHTML = html;
            var rows = panel.querySelectorAll('.text-slate-700');
            labels.forEach(function (label, i) {
                if (rows[i]) rows[i].textContent = label;
            });
        }

        function placePanel(wrap) {
            var btn = wrap && wrap.querySelector('[data-ris-brand-more-btn]');
            if (!btn) return;
            panel.hidden = false;
            panel.style.left = '8px';
            panel.style.top = '8px';
            var rect = btn.getBoundingClientRect();
            var gap = 6;
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
            var labels = parseLabels(wrap);
            if (!labels.length) {
                hideWrap();
                return;
            }
            activeWrap = wrap;
            fillPanel(labels);
            placePanel(wrap);
            panel.classList.add('is-visible');
            wrap.querySelector('[data-ris-brand-more-btn]')?.setAttribute('aria-expanded', 'true');
        }

        function hideWrap() {
            clearTimeout(hideTimer);
            panel.classList.remove('is-visible');
            panel.hidden = true;
            if (activeWrap) {
                activeWrap.querySelector('[data-ris-brand-more-btn]')?.setAttribute('aria-expanded', 'false');
            }
            activeWrap = null;
        }

        function scheduleHide() {
            clearTimeout(hideTimer);
            hideTimer = setTimeout(hideWrap, 120);
        }

        window.toggleRisBrandMore = function (button, event) {
            event.preventDefault();
            event.stopPropagation();
            var wrap = button.closest('[data-ris-brand-more]');
            if (!wrap) return;
            if (activeWrap === wrap && panel.classList.contains('is-visible')) {
                hideWrap();
            } else {
                showWrap(wrap);
            }
        };

        document.addEventListener('mouseover', function (event) {
            var wrap = event.target.closest && event.target.closest('[data-ris-brand-more]');
            if (wrap) {
                clearTimeout(hideTimer);
                showWrap(wrap);
                return;
            }
            if (event.target.closest && event.target.closest('.ris-brand-more-panel')) {
                clearTimeout(hideTimer);
            }
        });

        document.addEventListener('mouseout', function (event) {
            var fromWrap = event.target.closest && event.target.closest('[data-ris-brand-more]');
            var fromPanel = event.target.closest && event.target.closest('.ris-brand-more-panel');
            var next = event.relatedTarget;
            if (fromWrap) {
                if (next && (fromWrap.contains(next) || (next.closest && next.closest('.ris-brand-more-panel')))) return;
                scheduleHide();
                return;
            }
            if (fromPanel) {
                if (next && ((activeWrap && activeWrap.contains(next)) || (next.closest && next.closest('.ris-brand-more-panel')))) return;
                scheduleHide();
            }
        });

        document.addEventListener('click', function (event) {
            if (event.target.closest('[data-ris-brand-more]') || event.target.closest('.ris-brand-more-panel')) return;
            hideWrap();
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
<div class="relative min-w-0">
    {{-- Keep primary brand_id for older readers / duplicate checks --}}
    <input
        type="hidden"
        x-bind:name="`{{ $namePrefix }}[${index}][brand_id]`"
        x-bind:value="(item.brand_ids && item.brand_ids[0]) || item.brand_id || ''"
    >
    <template x-for="bid in (item.brand_ids || [])" :key="'brand-hid-' + index + '-' + bid">
        <input type="hidden" x-bind:name="`{{ $namePrefix }}[${index}][brand_ids][]`" x-bind:value="bid">
    </template>
    <template x-for="bid in (item.brand_ids || [])" :key="'brand-qty-hid-' + index + '-' + bid">
        <input
            type="hidden"
            x-bind:name="`{{ $namePrefix }}[${index}][brand_qtys][${bid}]`"
            x-bind:value="risBrandQty(item, bid)"
        >
    </template>

    <div class="flex min-w-0 items-center gap-0.5">
        <button
            type="button"
            x-on:click.stop="toggleRisSelect('brandOptions-' + index)"
            class="{{ $triggerClass }} flex min-w-0 flex-1 items-center justify-between gap-0.5 text-center text-gray-800"
            x-bind:aria-expanded="isRisSelectOpen('brandOptions-' + index).toString()"
        >
            <span class="min-w-0 flex-1">
                <span
                    x-show="!(item.brand_ids && item.brand_ids.length)"
                    class="block truncate text-gray-400"
                >{{ $placeholder }}</span>
                <span
                    x-show="item.brand_ids && item.brand_ids.length"
                    class="flex max-w-full flex-wrap items-center justify-center gap-0.5"
                    x-cloak
                >
                    <template x-for="(label, bi) in risBrandVisibleLabels(item, {{ $visibleChipLimit }})" :key="'chip-' + index + '-' + bi">
                        <span
                            class="inline-flex max-w-[5.5rem] truncate rounded bg-slate-100 px-1.5 py-0.5 text-[9px] font-medium leading-tight text-slate-700 sm:max-w-[6.5rem] sm:text-[10px]"
                            x-text="label"
                            x-bind:title="label"
                        ></span>
                    </template>
                </span>
            </span>
            <svg class="h-3 w-3 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
            </svg>
        </button>
        <span
            x-show="risBrandOverflowCount(item, {{ $visibleChipLimit }}) > 0"
            class="ris-brand-more relative inline-flex shrink-0"
            data-ris-brand-more
            x-cloak
            x-bind:data-brand-labels="JSON.stringify(risBrandLabels(item))"
            style="z-index: 30;"
        >
            <button
                type="button"
                data-ris-brand-more-btn
                aria-expanded="false"
                x-on:click.stop="window.toggleRisBrandMore($el, $event)"
                class="inline-flex items-center rounded-md bg-[#0037C7]/10 px-1.5 py-0.5 text-[10px] font-bold leading-4 text-[#0037C7] ring-1 ring-[#0037C7]/20 transition hover:bg-[#0037C7] hover:text-white"
                x-text="'+' + risBrandOverflowCount(item, {{ $visibleChipLimit }})"
            ></button>
        </span>
    </div>

    <div
        x-show="isRisSelectOpen('brandOptions-' + index)"
        x-cloak
        x-transition
        x-on:click.outside="closeRisSelect()"
        class="absolute left-0 top-full z-[80] mt-0.5 {{ $panelMinWidth }} w-max max-w-[18rem] overflow-hidden rounded-lg border border-gray-200 bg-white text-left shadow-lg"
    >
        <div class="border-b border-gray-100 p-1.5">
            <input
                type="text"
                x-bind:data-ris-select-search="'brandOptions-' + index"
                x-model="risSelectQuery"
                x-on:click.stop
                x-on:keydown.escape.stop="closeRisSelect()"
                placeholder="Search..."
                maxlength="{{ \App\Support\InputLimits::SHORT }}"
                class="w-full rounded-md border border-gray-200 bg-gray-50 px-2 py-1.5 text-[11px] text-gray-800 outline-none focus:border-gray-300 focus:bg-white"
            >
        </div>
        <ul class="max-h-[12rem] overflow-y-auto py-1" role="listbox" aria-multiselectable="true">
            <template x-for="opt in risFieldOptions('brandOptions', item, 'brand')" :key="'brand-opt-' + opt.id">
                <li class="border-b border-slate-50 last:border-0">
                    <div
                        class="flex items-start gap-2 px-2.5 py-1.5 text-left text-[11px] text-gray-800 hover:bg-gray-50"
                        x-bind:class="risBrandSelected(item, opt.id) ? 'bg-slate-50' : ''"
                        x-on:click.stop
                    >
                        <label class="flex min-w-0 flex-1 cursor-pointer items-start gap-2">
                            <input
                                type="checkbox"
                                class="mt-0.5 h-3.5 w-3.5 shrink-0 rounded border-slate-300 text-[#0025cc] focus:ring-[#0025cc]/30"
                                x-bind:checked="risBrandSelected(item, opt.id)"
                                x-on:change="toggleRisBrand(item, opt.id)"
                            >
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-1">
                                    <span class="min-w-0 truncate font-medium" x-text="opt.name || opt.label"></span>
                                    <span
                                        x-show="!!opt.caution"
                                        class="shrink-0 rounded bg-rose-50 px-1 py-px text-[9px] font-semibold uppercase tracking-wide text-rose-700"
                                        x-text="opt.cautionLabel || 'Avoid'"
                                    ></span>
                                </span>
                                <span
                                    x-show="!!opt.detail"
                                    class="mt-0.5 block text-[10px] font-normal leading-snug text-amber-800"
                                    x-text="opt.detail"
                                ></span>
                            </span>
                        </label>
                        <div
                            x-show="risBrandSelected(item, opt.id)"
                            x-cloak
                            class="flex shrink-0 items-center gap-1"
                            x-on:click.stop
                        >
                            <span class="text-[9px] font-semibold uppercase tracking-wide text-slate-400">Qty</span>
                            <input
                                type="number"
                                min="1"
                                max="{{ \App\Support\InputLimits::QTY }}"
                                inputmode="numeric"
                                x-bind:value="risBrandQty(item, opt.id)"
                                x-on:input.stop="setRisBrandQty(item, opt.id, $event.target.value)"
                                x-on:click.stop
                                x-on:keydown.escape.stop="closeRisSelect()"
                                class="h-7 w-14 rounded-md border border-slate-200 bg-white px-1.5 text-center text-[11px] font-semibold text-slate-800 outline-none focus:border-[#0025cc] focus:ring-1 focus:ring-[#0025cc]/20"
                            >
                        </div>
                    </div>
                </li>
            </template>
            <li
                x-show="risFieldOptions('brandOptions', item, 'brand').length === 0"
                class="px-2.5 py-2 text-[11px] text-gray-400"
            >
                No matches — add it below.
            </li>
            <li
                x-show="risKnownLimited(item, 'brand')"
                class="border-t border-gray-100 px-2.5 pt-1.5 text-[10px] font-medium uppercase tracking-wide text-slate-400"
                x-cloak
            >
                Suggested for this item
            </li>
            <li x-show="risKnownLimited(item, 'brand')" class="border-t border-gray-100">
                <button
                    type="button"
                    x-on:click.stop="item._showAllBrands = true"
                    class="flex w-full px-2.5 py-1.5 text-left text-[11px] font-medium text-[#0025cc] hover:bg-gray-50"
                >
                    Show all brands
                </button>
            </li>
        </ul>
        <div class="border-t border-gray-100 bg-gray-50 p-1.5" x-on:click.stop>
            <p class="mb-1 px-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Add new brand</p>
            <div class="flex items-center gap-1">
                <input
                    type="text"
                    maxlength="{{ \App\Support\EquipmentBrands::maxLength() }}"
                    x-model="risNewItemName"
                    x-on:keydown.enter.prevent.stop="addRisCatalogOption('brand', item)"
                    x-on:keydown.escape.stop="closeRisSelect()"
                    x-bind:placeholder="String(risSelectQuery || '').trim() || 'New brand name'"
                    class="min-w-0 flex-1 rounded-md border border-gray-200 bg-white px-2 py-1.5 text-[11px] text-gray-800 outline-none focus:border-gray-300"
                >
                <button
                    type="button"
                    x-on:click="addRisCatalogOption('brand', item)"
                    x-bind:disabled="risNewItemSaving"
                    class="shrink-0 rounded-md bg-[#0025cc] px-2.5 py-1.5 text-[11px] font-semibold text-white transition hover:bg-blue-800 disabled:opacity-60"
                    x-text="risNewItemSaving ? 'Adding…' : 'Add'"
                ></button>
            </div>
            <p class="mt-1 px-0.5 text-[10px] text-red-600" x-show="risNewItemError" x-text="risNewItemError"></p>
        </div>
    </div>
</div>
