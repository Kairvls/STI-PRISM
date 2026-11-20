{{--
  Searchable RIS cell select (Brand / Unit / Supplier).
  Brand and Unit include an inline Add box; Supplier opens the Add Supplier modal.
  Brand/Supplier show the full alphabetical list (no per-item suggestions).
  Expects Alpine parent scope with: item, index, brandOptions|uomOptions|supplierOptions,
  and helpers: toggleRisSelect, isRisSelectOpen, closeRisSelect, risSelectLabel, risFieldOptions,
  risSelectQuery, selectRisField, addRisCatalogOption, openRisAddSupplierModal,
  risNewItemName, risNewItemSaving, risNewItemError
--}}
@php
    $field = $field ?? 'brand_id';
    $optionsKey = $optionsKey ?? 'brandOptions';
    $limitKind = $limitKind ?? '';
    $allowQuickAdd = in_array($limitKind, ['brand', 'uom'], true);
    $allowSupplierModal = $limitKind === 'supplier';
    $hideSuggestions = in_array($limitKind, ['brand', 'supplier'], true);
    $quickAddKind = $limitKind;
    $quickAddTitle = match ($limitKind) {
        'brand' => 'Add new brand',
        'uom' => 'Add new unit',
        default => 'Add new',
    };
    $quickAddPlaceholder = match ($limitKind) {
        'brand' => 'New brand name',
        'uom' => 'New unit name',
        default => 'New name',
    };
    $quickAddMax = match ($limitKind) {
        'brand' => \App\Support\EquipmentBrands::maxLength(),
        'uom' => 50,
        default => \App\Support\InputLimits::SHORT,
    };
    $showAllFlag = match ($limitKind) {
        'brand' => '_showAllBrands',
        'uom' => '_showAllUnits',
        'supplier' => '_showAllSuppliers',
        default => '',
    };
    $showAllLabel = match ($limitKind) {
        'brand' => 'Show all brands',
        'uom' => 'Show all units',
        'supplier' => 'Show all suppliers',
        default => '',
    };
    $limitedHint = match ($limitKind) {
        'brand' => 'Suggested for this item',
        'supplier' => 'Suggested for this item',
        'uom' => 'Suggested for this item',
        default => '',
    };
    $placeholder = $placeholder ?? 'Select';
    $namePrefix = $namePrefix ?? 'ris_items';
    $textAlign = $textAlign ?? 'center';
    $triggerClass = $triggerClass ?? 'w-full min-w-0 border-0 bg-transparent px-0.5 py-1.5 text-[10px] outline-none focus:ring-0 sm:px-1 sm:text-xs';
    $panelMinWidth = $panelMinWidth ?? 'min-w-[10rem]';
@endphp
<div class="relative min-w-0">
    <input
        type="hidden"
        x-bind:name="`{{ $namePrefix }}[${index}][{{ $field }}]`"
        x-model="item.{{ $field }}"
    >
    @if ($field === 'brand_id')
        <template x-if="item.brand_id">
            <input type="hidden" x-bind:name="`{{ $namePrefix }}[${index}][brand_ids][]`" x-bind:value="item.brand_id">
        </template>
    @endif
    @if ($field === 'supplier_id')
        <template x-if="item.supplier_id">
            <input type="hidden" x-bind:name="`{{ $namePrefix }}[${index}][supplier_ids][]`" x-bind:value="item.supplier_id">
        </template>
    @endif
    <button
        type="button"
        x-on:click.stop="!item._revisionLocked && risFieldReady(item, @js($field)) && toggleRisSelect('{{ $optionsKey }}-' + index)"
        x-bind:disabled="!item._revisionLocked && !risFieldReady(item, @js($field))"
        x-bind:title="item._revisionLocked ? risSelectLabel('{{ $optionsKey }}', item.{{ $field }}, '') : (risFieldLockReason(item, @js($field)) || risSelectLabel('{{ $optionsKey }}', item.{{ $field }}, ''))"
        class="{{ $triggerClass }} flex items-center justify-between gap-0.5 text-{{ $textAlign }}"
        x-bind:class="item._revisionLocked ? 'cursor-not-allowed bg-gray-50 text-gray-600' : (risFieldReady(item, @js($field)) ? 'text-gray-800' : 'cursor-not-allowed text-gray-400 opacity-60')"
        x-bind:aria-expanded="(!item._revisionLocked && isRisSelectOpen('{{ $optionsKey }}-' + index)).toString()"
        x-bind:aria-disabled="(!!item._revisionLocked || !risFieldReady(item, @js($field))).toString()"
    >
        <span
            class="min-w-0 flex-1 truncate"
            x-text="risSelectLabel('{{ $optionsKey }}', item.{{ $field }}, @js($placeholder))"
        ></span>
        <svg x-show="!item._revisionLocked" class="h-3 w-3 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
        </svg>
    </button>

    <div
        x-show="risFieldReady(item, @js($field)) && isRisSelectOpen('{{ $optionsKey }}-' + index)"
        x-cloak
        x-transition
        x-on:click.outside="closeRisSelect()"
        class="absolute left-0 top-full z-[80] mt-0.5 {{ $panelMinWidth }} w-max {{ ($limitKind ?? '') === 'supplier' ? 'max-w-[20rem]' : 'max-w-[16rem]' }} overflow-hidden rounded-lg border border-gray-200 bg-white text-left shadow-lg"
    >
        <div class="border-b border-gray-100 p-1.5">
            <input
                type="text"
                x-bind:data-ris-select-search="'{{ $optionsKey }}-' + index"
                x-model="risSelectQuery"
                x-on:click.stop
                x-on:keydown.escape.stop="closeRisSelect()"
                placeholder="Search..."
                maxlength="{{ \App\Support\InputLimits::SHORT }}"
                class="w-full rounded-md border border-gray-200 bg-gray-50 px-2 py-1.5 text-[11px] text-gray-800 outline-none focus:border-gray-300 focus:bg-white"
            >
        </div>
        <ul class="max-h-[10.5rem] overflow-y-auto py-1">
            <li>
                <button
                    type="button"
                    x-on:click="selectRisField(item, @js($field), '')"
                    class="flex w-full px-2.5 py-1.5 text-left text-[11px] text-gray-500 hover:bg-gray-50"
                    x-bind:class="!item.{{ $field }} ? 'bg-slate-50 font-medium text-slate-800' : ''"
                >
                    {{ $placeholder }}
                </button>
            </li>
            <template x-for="opt in risFieldOptions('{{ $optionsKey }}', item, @js($limitKind))" :key="'{{ $optionsKey }}-' + opt.id">
                <li>
                    <button
                        type="button"
                        x-on:click="if (risOptionAllowed(item, @js($field), opt.id)) { selectRisField(item, @js($field), opt.id); }"
                        x-bind:disabled="!risOptionAllowed(item, @js($field), opt.id)"
                        class="flex w-full items-start justify-between gap-2 px-2.5 py-1.5 text-left text-[11px] text-gray-800 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent"
                        x-bind:class="String(item.{{ $field }}) === String(opt.id) ? 'bg-slate-50 font-medium' : ''"
                        x-bind:title="risOptionAllowed(item, @js($field), opt.id) ? '' : (@js($field) === 'supplier_id' ? 'Max 3 suppliers on this RIS, or max 3 brands per supplier' : 'Max 3 brands for this supplier')"
                    >
                        <span class="min-w-0 flex-1">
                            <span class="block truncate" x-text="opt.label"></span>
                            <span
                                x-show="!!opt.detail"
                                class="mt-0.5 block text-[10px] font-normal leading-snug text-amber-800"
                                x-text="opt.detail"
                            ></span>
                        </span>
                        <span
                            x-show="!!opt.caution"
                            class="mt-0.5 shrink-0 rounded bg-rose-50 px-1 py-px text-[9px] font-semibold uppercase tracking-wide text-rose-700"
                            x-text="opt.cautionLabel || 'Avoid'"
                        ></span>
                    </button>
                </li>
            </template>
            <li
                x-show="risFieldOptions('{{ $optionsKey }}', item, @js($limitKind)).length === 0"
                class="px-2.5 py-2 text-[11px] text-gray-400"
            >
                {{ ($allowQuickAdd || $allowSupplierModal) ? 'No matches — add it below.' : 'No matches' }}
            </li>
            @if ($limitKind !== '' && ! $hideSuggestions)
                <li
                    x-show="risKnownLimited(item, @js($limitKind))"
                    class="border-t border-gray-100 px-2.5 pt-1.5 text-[10px] font-medium uppercase tracking-wide text-slate-400"
                    x-cloak
                >
                    {{ $limitedHint }}
                </li>
                <li x-show="risKnownLimited(item, @js($limitKind))" class="border-t border-gray-100">
                    <button
                        type="button"
                        x-on:click.stop="item.{{ $showAllFlag }} = true"
                        class="flex w-full px-2.5 py-1.5 text-left text-[11px] font-medium text-[#0025cc] hover:bg-gray-50"
                    >
                        {{ $showAllLabel }}
                    </button>
                </li>
            @endif
        </ul>
        @if ($allowQuickAdd)
            <div class="border-t border-gray-100 bg-gray-50 p-1.5" x-on:click.stop>
                <p class="mb-1 px-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-400">{{ $quickAddTitle }}</p>
                <div class="flex items-center gap-1">
                    <input
                        type="text"
                        maxlength="{{ $quickAddMax }}"
                        x-model="risNewItemName"
                        x-on:keydown.enter.prevent.stop="addRisCatalogOption(@js($quickAddKind), item)"
                        x-on:keydown.escape.stop="closeRisSelect()"
                        x-bind:placeholder="String(risSelectQuery || '').trim() || @js($quickAddPlaceholder)"
                        class="min-w-0 flex-1 rounded-md border border-gray-200 bg-white px-2 py-1.5 text-[11px] text-gray-800 outline-none focus:border-gray-300"
                    >
                    <button
                        type="button"
                        x-on:click="addRisCatalogOption(@js($quickAddKind), item)"
                        x-bind:disabled="risNewItemSaving"
                        class="shrink-0 rounded-md bg-[#0025cc] px-2.5 py-1.5 text-[11px] font-semibold text-white transition hover:bg-blue-800 disabled:opacity-60"
                        x-text="risNewItemSaving ? 'Adding…' : 'Add'"
                    ></button>
                </div>
                <p class="mt-1 px-0.5 text-[10px] text-red-600" x-show="risNewItemError" x-text="risNewItemError"></p>
            </div>
        @elseif ($allowSupplierModal)
            <div class="border-t border-gray-100 bg-gray-50 p-1.5" x-on:click.stop>
                <button
                    type="button"
                    x-on:click.stop="openRisAddSupplierModal()"
                    class="flex w-full items-center justify-center gap-1.5 rounded-md bg-[#0025cc] px-2.5 py-1.5 text-[11px] font-semibold text-white transition hover:bg-blue-800"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/>
                    </svg>
                    Add supplier
                </button>
            </div>
        @endif
    </div>
</div>
