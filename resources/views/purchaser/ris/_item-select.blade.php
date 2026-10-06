{{--
  RIS item cell: searchable dropdown fed by File Maintenance > Items (itemOptions),
  with an inline box that adds a new item to the catalog and selects it.
  The RIS still stores the item name as text (ris_items[i][name_description]).
  Rows flagged item._sourceLocked come from the linked replacement request and render read-only.
  Expects Alpine parent scope with: item, index, itemOptions, the list named by $listVar,
  and helpers: toggleRisSelect, isRisSelectOpen, closeRisSelect, filteredRisOptions,
  risSelectQuery, pickRisItem, addRisCatalogItem, risNewItemName, risNewItemSaving, risNewItemError
--}}
@php
    $listVar = $listVar ?? 'createItems';
    $namePrefix = $namePrefix ?? 'ris_items';
    $placeholder = $placeholder ?? 'Select item';
    $triggerClass = $triggerClass ?? 'w-full min-w-0 border-0 bg-transparent px-1 py-1.5 text-[11px] outline-none focus:ring-0 sm:px-2 sm:text-sm';
@endphp
<div class="relative min-w-0">
    <input
        type="hidden"
        x-bind:name="`{{ $namePrefix }}[${index}][name_description]`"
        x-bind:value="item.name_description"
    >
    <div
        x-show="item._sourceLocked"
        class="{{ $triggerClass }} flex cursor-not-allowed items-center gap-1 text-left text-gray-800"
        x-bind:title="'From replacement request: ' + String(item.name_description || '').trim()"
    >
        <svg class="h-3 w-3 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="5" y="11" width="14" height="10" rx="2" stroke-width="2"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 0 1 8 0v4"/>
        </svg>
        <span class="min-w-0 flex-1 truncate" x-text="String(item.name_description || '').trim()"></span>
    </div>
    <button
        x-show="!item._sourceLocked"
        type="button"
        x-on:click.stop="toggleRisSelect('itemOptions-' + index)"
        class="{{ $triggerClass }} flex items-center justify-between gap-0.5 text-left"
        x-bind:class="String(item.name_description || '').trim() ? 'text-gray-800' : 'text-gray-400'"
        x-bind:title="String(item.name_description || '').trim()"
        x-bind:aria-expanded="isRisSelectOpen('itemOptions-' + index).toString()"
    >
        <span class="min-w-0 flex-1 truncate" x-text="String(item.name_description || '').trim() || @js($placeholder)"></span>
        <svg class="h-3 w-3 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
        </svg>
    </button>

    <div
        x-show="isRisSelectOpen('itemOptions-' + index)"
        x-cloak
        x-transition
        x-on:click.outside="closeRisSelect()"
        class="absolute left-0 top-full z-[80] mt-0.5 w-64 max-w-[18rem] overflow-hidden rounded-lg border border-gray-200 bg-white text-left shadow-lg"
    >
        <div class="border-b border-gray-100 p-1.5">
            <input
                type="text"
                x-bind:data-ris-select-search="'itemOptions-' + index"
                x-model="risSelectQuery"
                x-on:click.stop
                x-on:keydown.escape.stop="closeRisSelect()"
                x-on:keydown.enter.prevent="const first = filteredRisOptions('itemOptions')[0]; if (first) pickRisItem({{ $listVar }}, index, first.label)"
                placeholder="Search items..."
                class="w-full rounded-md border border-gray-200 bg-gray-50 px-2 py-1.5 text-[11px] text-gray-800 outline-none focus:border-gray-300 focus:bg-white"
            >
        </div>
        <ul class="max-h-[10.5rem] overflow-y-auto py-1">
            <li x-show="String(item.name_description || '').trim()">
                <button
                    type="button"
                    x-on:click="pickRisItem({{ $listVar }}, index, '')"
                    class="flex w-full px-2.5 py-1.5 text-left text-[11px] text-gray-500 hover:bg-gray-50"
                >
                    Clear item
                </button>
            </li>
            <template x-for="opt in filteredRisOptions('itemOptions')" :key="'itemOptions-' + opt.id">
                <li>
                    <button
                        type="button"
                        x-on:click="pickRisItem({{ $listVar }}, index, opt.label)"
                        class="flex w-full px-2.5 py-1.5 text-left text-[11px] text-gray-800 hover:bg-gray-50"
                        x-bind:class="String(item.name_description || '').trim().toLowerCase() === String(opt.label).toLowerCase() ? 'bg-slate-50 font-medium' : ''"
                        x-text="opt.label"
                    ></button>
                </li>
            </template>
            <li
                x-show="filteredRisOptions('itemOptions').length === 0"
                class="px-2.5 py-2 text-[11px] text-gray-400"
            >
                No matching items — add it below.
            </li>
        </ul>
        <div class="border-t border-gray-100 bg-gray-50 p-1.5" x-on:click.stop>
            <p class="mb-1 px-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Add new item</p>
            <div class="flex items-center gap-1">
                <input
                    type="text"
                    maxlength="255"
                    x-model="risNewItemName"
                    x-on:keydown.enter.prevent.stop="addRisCatalogItem({{ $listVar }}, index)"
                    x-on:keydown.escape.stop="closeRisSelect()"
                    x-bind:placeholder="String(risSelectQuery || '').trim() || 'New item name'"
                    class="min-w-0 flex-1 rounded-md border border-gray-200 bg-white px-2 py-1.5 text-[11px] text-gray-800 outline-none focus:border-gray-300"
                >
                <button
                    type="button"
                    x-on:click="addRisCatalogItem({{ $listVar }}, index)"
                    x-bind:disabled="risNewItemSaving"
                    class="shrink-0 rounded-md bg-[#0025cc] px-2.5 py-1.5 text-[11px] font-semibold text-white transition hover:bg-blue-800 disabled:opacity-60"
                    x-text="risNewItemSaving ? 'Adding…' : 'Add'"
                ></button>
            </div>
            <p class="mt-1 px-0.5 text-[10px] text-red-600" x-show="risNewItemError" x-text="risNewItemError"></p>
        </div>
    </div>
</div>
