@php
    $sliderId = $sliderId ?? 'receivingFilterSlider';
    $current = $current ?? 'all';
    $options = $options ?? [];
@endphp

@if(count($options))
<div
    id="{{ $sliderId }}"
    role="tablist"
    aria-label="{{ $ariaLabel ?? 'Filters' }}"
    class="relative inline-flex max-w-full items-center overflow-x-auto rounded-xl bg-slate-200/70 p-1"
>
    <span
        class="receiving-filter-thumb pointer-events-none absolute top-1 left-0 z-0 h-9 rounded-lg bg-white shadow-sm will-change-transform"
        style="transform: translate3d(0, 0, 0); transition: transform 220ms cubic-bezier(0.22, 1, 0.36, 1), width 220ms cubic-bezier(0.22, 1, 0.36, 1);"
        aria-hidden="true"
    ></span>

    @foreach ($options as $option)
        <button
            type="button"
            role="tab"
            data-filter="{{ $option['filter'] }}"
            title="{{ $option['title'] ?? $option['label'] }}"
            aria-selected="{{ $current === $option['filter'] ? 'true' : 'false' }}"
            class="receiving-filter-btn relative z-10 flex h-9 shrink-0 items-center whitespace-nowrap rounded-lg px-4 text-xs font-semibold transition-colors {{ $current === $option['filter'] ? 'text-slate-950' : 'text-slate-500 hover:text-slate-900' }}"
        >
            {{ $option['label'] }}
        </button>
    @endforeach
</div>
@endif
