{{--
  Searchable RIS cell select (Brand / Unit / Supplier).
  Expects Alpine parent scope with: item, index, brandOptions|uomOptions|supplierOptions,
  and helpers: toggleRisSelect, isRisSelectOpen, closeRisSelect, risSelectLabel, filteredRisOptions, risSelectQuery
--}}
@php
    $field = $field ?? 'brand_id';
    $optionsKey = $optionsKey ?? 'brandOptions';
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
    <button
        type="button"
        x-on:click.stop="toggleRisSelect('{{ $optionsKey }}-' + index)"
        class="{{ $triggerClass }} flex items-center justify-between gap-0.5 text-{{ $textAlign }} text-gray-800"
        x-bind:aria-expanded="isRisSelectOpen('{{ $optionsKey }}-' + index).toString()"
    >
        <span
            class="min-w-0 flex-1 truncate"
            x-text="risSelectLabel('{{ $optionsKey }}', item.{{ $field }}, @js($placeholder))"
        ></span>
        <svg class="h-3 w-3 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
        </svg>
    </button>

    <div
        x-show="isRisSelectOpen('{{ $optionsKey }}-' + index)"
        x-cloak
        x-transition
        x-on:click.outside="closeRisSelect()"
        class="absolute left-0 top-full z-[80] mt-0.5 {{ $panelMinWidth }} w-max max-w-[16rem] overflow-hidden rounded-lg border border-gray-200 bg-white text-left shadow-lg"
    >
        <div class="border-b border-gray-100 p-1.5">
            <input
                type="text"
                x-bind:data-ris-select-search="'{{ $optionsKey }}-' + index"
                x-model="risSelectQuery"
                x-on:click.stop
                x-on:keydown.escape.stop="closeRisSelect()"
                placeholder="Search..."
                class="w-full rounded-md border border-gray-200 bg-gray-50 px-2 py-1.5 text-[11px] text-gray-800 outline-none focus:border-gray-300 focus:bg-white"
            >
        </div>
        <ul class="max-h-[10.5rem] overflow-y-auto py-1">
            <li>
                <button
                    type="button"
                    x-on:click="item.{{ $field }} = ''; closeRisSelect()"
                    class="flex w-full px-2.5 py-1.5 text-left text-[11px] text-gray-500 hover:bg-gray-50"
                    x-bind:class="!item.{{ $field }} ? 'bg-slate-50 font-medium text-slate-800' : ''"
                >
                    {{ $placeholder }}
                </button>
            </li>
            <template x-for="opt in filteredRisOptions('{{ $optionsKey }}')" :key="'{{ $optionsKey }}-' + opt.id">
                <li>
                    <button
                        type="button"
                        x-on:click="item.{{ $field }} = String(opt.id); closeRisSelect()"
                        class="flex w-full px-2.5 py-1.5 text-left text-[11px] text-gray-800 hover:bg-gray-50"
                        x-bind:class="String(item.{{ $field }}) === String(opt.id) ? 'bg-slate-50 font-medium' : ''"
                        x-text="opt.label"
                    ></button>
                </li>
            </template>
            <li
                x-show="filteredRisOptions('{{ $optionsKey }}').length === 0"
                class="px-2.5 py-2 text-[11px] text-gray-400"
            >
                No matches
            </li>
        </ul>
    </div>
</div>
