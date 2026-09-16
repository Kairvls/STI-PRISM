@php
    $filters = $filters ?? ['q' => '', 'from' => '', 'to' => ''];
    $action = $action ?? url()->current();
    $placeholder = $placeholder ?? 'Search...';
@endphp

<div class="print-hidden">
    <form method="GET" action="{{ $action }}" class="flex flex-col gap-2 xl:flex-row xl:flex-wrap xl:items-center">
        <div class="relative min-w-[220px] flex-1">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
            </svg>
            <input
                type="search"
                name="q"
                value="{{ $filters['q'] }}"
                placeholder="{{ $placeholder }}"
                class="h-9 w-full rounded-lg border border-gray-200 bg-white pl-10 pr-4 text-sm text-gray-700 outline-none transition focus:border-gray-300"
            >
        </div>
        <input
            type="date"
            name="from"
            value="{{ $filters['from'] }}"
            class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none transition focus:border-gray-300"
        >
        <input
            type="date"
            name="to"
            value="{{ $filters['to'] }}"
            class="h-9 rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none transition focus:border-gray-300"
        >
        <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-[#0025cc] px-4 text-[13px] font-medium text-white transition hover:bg-[#001fa8]">
            Filter
        </button>
        <a
            href="{{ $action }}"
            class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-200 bg-white px-3.5 text-[13px] font-medium text-gray-600 transition hover:bg-gray-50"
        >Clear</a>
        <button
            type="button"
            onclick="window.print()"
            class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-200 bg-white px-3.5 text-[13px] font-medium text-gray-700 transition hover:bg-gray-50 xl:ml-auto"
        >Print</button>
    </form>
</div>
