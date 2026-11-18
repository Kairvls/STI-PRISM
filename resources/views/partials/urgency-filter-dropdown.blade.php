{{-- Compact All urgency / Urgent / Non-Urgent dropdown --}}
@php
    $urgencyName = $urgencyName ?? 'urgency';
    $urgencyValue = (string) ($urgencyValue ?? request($urgencyName, ''));
    $urgencyOptions = [
        '' => 'All urgency',
        \App\Support\RisWorkflow::URGENCY_URGENT => 'Urgent',
        \App\Support\RisWorkflow::URGENCY_NON_URGENT => 'Non-Urgent',
    ];
    if ($urgencyValue !== '' && ! array_key_exists($urgencyValue, $urgencyOptions)) {
        $urgencyValue = '';
    }
    $urgencyInputId = $urgencyInputId ?? null;
    $urgencyOnChange = $urgencyOnChange ?? null; // optional JS callback name
@endphp

<div
    class="relative shrink-0"
    x-data="{
        open: false,
        value: @js($urgencyValue),
        labels: @js($urgencyOptions),
        onChange: @js($urgencyOnChange)
    }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
>
    <input
        type="hidden"
        name="{{ $urgencyName }}"
        x-bind:value="value"
        @if($urgencyInputId) id="{{ $urgencyInputId }}" @endif
        data-urgency-field
    >

    <button
        type="button"
        x-on:click="open = !open"
        class="box-border inline-flex h-9 min-w-[9.5rem] items-center justify-between gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 text-left text-sm leading-none text-gray-600 outline-none transition hover:border-gray-300 hover:bg-white focus:border-gray-300 focus:bg-white"
        :aria-expanded="open.toString()"
        aria-haspopup="listbox"
        aria-label="Filter by urgency"
        data-tip="Filter by Urgent or Non-Urgent"
    >
        <span class="truncate" x-text="labels[value] || 'All urgency'"></span>
        <svg class="h-4 w-4 shrink-0 text-gray-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        class="absolute right-0 top-[calc(100%+6px)] z-30 w-48 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-[0_12px_40px_rgba(15,23,42,0.12)]"
        role="listbox"
    >
        <div class="py-1">
            <template x-for="(label, key) in labels" :key="'urgency-' + String(key)">
                <button
                    type="button"
                    role="option"
                    x-on:click="
                        value = key;
                        open = false;
                        $nextTick(() => {
                            if (onChange && typeof window[onChange] === 'function') {
                                window[onChange](value);
                                return;
                            }
                            const form = $el.closest('form');
                            if (form && typeof form.requestSubmit === 'function') form.requestSubmit();
                            else if (form) form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                        });
                    "
                    class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm transition hover:bg-slate-50"
                    :class="value === key ? 'bg-slate-50 font-medium text-slate-950' : 'text-slate-600'"
                    :aria-selected="(value === key).toString()"
                >
                    <span class="truncate" x-text="label"></span>
                    <svg
                        x-show="value === key"
                        class="h-4 w-4 shrink-0 text-[#0f172a]"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="m5 13 4 4L19 7" />
                    </svg>
                </button>
            </template>
        </div>
    </div>
</div>
