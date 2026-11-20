{{--
  Searchable Unit (UOM) cell with inline Add — same pattern as RIS Brand/Unit.
  Used on ATP and Receiving Report line items.
--}}
@php
    $unit = (string) ($unit ?? '');
    $inputName = $inputName ?? 'items[0][unit]';
    $lockRow = ! empty($lockRow);
    $placeholder = $placeholder ?? 'Unit';
    $buttonClass = $buttonClass ?? 'flex w-full min-w-0 items-center justify-between gap-0.5 border-0 bg-transparent px-1 py-1.5 text-center text-sm outline-none focus:ring-0';
    $uomNames = collect($uomNames ?? [])->map(fn ($name) => (string) $name)->filter()->values();
    $portal = $pp ?? \App\Support\ProcurementPortal::prefix();
    $uomQuickStoreUrl = $uomQuickStoreUrl
        ?? (\Illuminate\Support\Facades\Route::has($portal.'.uom.quick-store')
            ? route($portal.'.uom.quick-store')
            : route('purchaser.uom.quick-store'));
@endphp
<div
    class="relative min-w-0"
    x-data="{
        value: @js($unit),
        options: @js($uomNames->all()),
        open: false,
        query: '',
        newName: '',
        saving: false,
        error: '',
        storeUrl: @js($uomQuickStoreUrl),
        get filtered() {
            const list = this.value && !this.options.includes(this.value)
                ? [this.value, ...this.options]
                : this.options.slice();
            list.sort((a, b) => String(a).localeCompare(String(b), undefined, { sensitivity: 'base' }));
            const q = this.query.trim().toLowerCase();
            return q ? list.filter((opt) => opt.toLowerCase().includes(q)) : list;
        },
        init() {
            this._onUomAdded = (event) => {
                const name = String(event.detail?.name || '').trim();
                if (name && !this.options.includes(name)) {
                    this.options.push(name);
                    this.options.sort((a, b) => String(a).localeCompare(String(b), undefined, { sensitivity: 'base' }));
                }
            };
            window.addEventListener('prism-uom-added', this._onUomAdded);
        },
        destroy() {
            window.removeEventListener('prism-uom-added', this._onUomAdded);
        },
        csrfToken() {
            const meta = document.querySelector('meta[name=csrf-token]');
            return (meta && meta.getAttribute('content')) || '';
        },
        toggle() {
            if (@js($lockRow)) return;
            this.open = !this.open;
            this.query = '';
            this.newName = '';
            this.error = '';
            if (this.open) this.$nextTick(() => this.$refs.search?.focus({ preventScroll: true }));
        },
        pick(opt) {
            this.value = opt;
            this.open = false;
            this.query = '';
            this.newName = '';
            this.error = '';
        },
        async addUnit() {
            if (this.saving) return;
            const name = String(this.newName || this.query || '').replace(/\s+/g, ' ').trim();
            if (!name) {
                this.error = 'Type the new unit name first.';
                return;
            }
            this.saving = true;
            this.error = '';
            try {
                const res = await fetch(this.storeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    body: JSON.stringify({ uom_name: name }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.label) {
                    this.error = (data.errors && data.errors.uom_name && data.errors.uom_name[0])
                        || data.message
                        || 'Could not add the unit.';
                    return;
                }
                const label = String(data.label);
                if (!this.options.includes(label)) {
                    this.options.push(label);
                    this.options.sort((a, b) => String(a).localeCompare(String(b), undefined, { sensitivity: 'base' }));
                }
                window.dispatchEvent(new CustomEvent('prism-uom-added', { detail: { name: label } }));
                this.pick(label);
            } catch (e) {
                this.error = 'Could not add the unit. Check your connection and try again.';
            } finally {
                this.saving = false;
            }
        },
    }"
    x-on:keydown.escape.stop="open = false"
>
    <input
        type="hidden"
        name="{{ $inputName }}"
        x-bind:value="value"
        x-on:input="value = $event.target.value"
    >
    <button
        type="button"
        x-on:click.stop="toggle()"
        @if($lockRow) disabled @endif
        title="{{ $lockRow ? 'Taken from the RIS' : '' }}"
        class="{{ $buttonClass }} {{ $lockRow ? 'cursor-not-allowed text-gray-600' : 'text-gray-800' }}"
        x-bind:aria-expanded="open.toString()"
    >
        <span class="min-w-0 flex-1 truncate" x-bind:class="value ? '' : 'text-gray-400'" x-text="value || @js($placeholder)"></span>
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
                maxlength="{{ \App\Support\InputLimits::SHORT }}"
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
                No matches — add it below.
            </li>
        </ul>
        <div class="border-t border-gray-100 bg-gray-50 p-1.5" x-on:click.stop>
            <p class="mb-1 px-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Add new unit</p>
            <div class="flex items-center gap-1">
                <input
                    type="text"
                    maxlength="50"
                    x-model="newName"
                    x-on:keydown.enter.prevent.stop="addUnit()"
                    x-on:keydown.escape.stop="open = false"
                    x-bind:placeholder="String(query || '').trim() || 'New unit name'"
                    class="min-w-0 flex-1 rounded-md border border-gray-200 bg-white px-2 py-1.5 text-[11px] text-gray-800 outline-none focus:border-gray-300"
                >
                <button
                    type="button"
                    x-on:click="addUnit()"
                    x-bind:disabled="saving"
                    class="shrink-0 rounded-md bg-[#0025cc] px-2.5 py-1.5 text-[11px] font-semibold text-white transition hover:bg-blue-800 disabled:opacity-60"
                    x-text="saving ? 'Adding…' : 'Add'"
                ></button>
            </div>
            <p class="mt-1 px-0.5 text-[10px] text-red-600" x-show="error" x-text="error"></p>
        </div>
    </div>
</div>
