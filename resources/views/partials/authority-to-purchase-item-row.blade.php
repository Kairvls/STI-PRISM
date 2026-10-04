@php
    $i = $i ?? '__INDEX__';
    $qty = $qty ?? '';
    $unit = $unit ?? '';
    $desc = $desc ?? '';
    $price = $price ?? '';
    $amount = $amount ?? '';
@endphp
<tr data-doc-row>
    <td class="border border-black p-1">
        <input
            type="number"
            name="items[{{ $i }}][quantity]"
            value="{{ $qty }}"
            min="1"
            max="9999999"
            step="1"
            inputmode="numeric"
            onkeydown="if (['e', 'E', '+', '-', '.'].includes(event.key)) event.preventDefault();"
            oninput="if (this.value.length > 7) this.value = this.value.replace(/\D/g, '').slice(0, 7);"
            class="{{ $atpCellClass }} text-center"
        >
    </td>
    <td class="border border-black p-1">
        <div
            class="relative min-w-0"
            x-data="{
                value: @js((string) $unit),
                options: @js($uomNames->all()),
                open: false,
                query: '',
                get filtered() {
                    const list = this.value && !this.options.includes(this.value)
                        ? [this.value, ...this.options]
                        : this.options;
                    const q = this.query.trim().toLowerCase();
                    return q ? list.filter((opt) => opt.toLowerCase().includes(q)) : list;
                },
                toggle() {
                    this.open = !this.open;
                    this.query = '';
                    if (this.open) this.$nextTick(() => this.$refs.search.focus({ preventScroll: true }));
                },
                pick(opt) {
                    this.value = opt;
                    this.open = false;
                    this.query = '';
                }
            }"
            x-on:keydown.escape.stop="open = false"
        >
            <input
                type="hidden"
                name="items[{{ $i }}][unit]"
                x-bind:value="value"
                x-on:input="value = $event.target.value"
            >
            <button
                type="button"
                x-on:click.stop="toggle()"
                class="flex w-full min-w-0 items-center justify-between gap-0.5 border-0 bg-transparent px-1 py-1.5 text-center text-sm text-gray-800 outline-none focus:ring-0"
                x-bind:aria-expanded="open.toString()"
            >
                <span class="min-w-0 flex-1 truncate" x-bind:class="value ? '' : 'text-gray-400'" x-text="value || 'Unit'"></span>
                <svg class="h-3 w-3 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
                </svg>
            </button>
            <div
                x-show="open"
                x-cloak
                x-transition
                x-on:click.outside="open = false"
                class="absolute left-0 top-full z-[80] mt-0.5 w-max min-w-[7rem] max-w-[16rem] overflow-hidden rounded-lg border border-gray-200 bg-white text-left shadow-lg"
            >
                <div class="border-b border-gray-100 p-1.5">
                    <input
                        type="text"
                        x-ref="search"
                        x-model="query"
                        x-on:click.stop
                        x-on:keydown.enter.prevent="filtered.length && pick(filtered[0])"
                        placeholder="Search..."
                        class="w-full rounded-md border border-gray-200 bg-gray-50 px-2 py-1.5 text-[11px] text-gray-800 outline-none focus:border-gray-300 focus:bg-white"
                    >
                </div>
                <ul class="max-h-[10.5rem] overflow-y-auto py-1">
                    <li>
                        <button
                            type="button"
                            x-on:click="pick('')"
                            class="flex w-full px-2.5 py-1.5 text-left text-[11px] text-gray-500 hover:bg-gray-50"
                            x-bind:class="!value ? 'bg-slate-50 font-medium text-slate-800' : ''"
                        >
                            Select unit
                        </button>
                    </li>
                    <template x-for="opt in filtered" :key="opt">
                        <li>
                            <button
                                type="button"
                                x-on:click="pick(opt)"
                                class="flex w-full px-2.5 py-1.5 text-left text-[11px] text-gray-800 hover:bg-gray-50"
                                x-bind:class="value === opt ? 'bg-slate-50 font-medium' : ''"
                                x-text="opt"
                            ></button>
                        </li>
                    </template>
                    <li x-show="filtered.length === 0" class="px-2.5 py-2 text-[11px] text-gray-400">
                        No matches
                    </li>
                </ul>
            </div>
        </div>
    </td>
    <td class="border border-black p-1">
        <input type="text" name="items[{{ $i }}][description]" value="{{ $desc }}" class="{{ $atpCellClass }}">
    </td>
    <td class="border border-black p-1">
        <input
            type="number"
            step="0.01"
            name="items[{{ $i }}][unit_price]"
            value="{{ $price }}"
            min="0"
            max="9999999.99"
            inputmode="decimal"
            onkeydown="if (['e', 'E', '+', '-'].includes(event.key)) event.preventDefault();"
            oninput="const [whole, decimals] = this.value.split('.'); if (whole.length > 7 || (decimals ?? '').length > 2) this.value = whole.slice(0, 7) + (decimals !== undefined ? '.' + decimals.slice(0, 2) : '');"
            class="{{ $atpCellClass }} text-right"
        >
    </td>
    <td class="border border-black p-1">
        <input
            readonly
            name="items[{{ $i }}][amount_display]"
            value="{{ $amount }}"
            class="{{ $atpCellClass }} cursor-not-allowed bg-gray-50 text-right text-gray-500"
            placeholder="0.00"
        >
    </td>
</tr>
