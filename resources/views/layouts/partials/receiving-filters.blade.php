@php
    $searchId = $searchId ?? 'receivingLiveSearch';
    $placeholder = $placeholder ?? 'Search RIS, ATP, supplier, OR...';
@endphp

<div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center sm:justify-end">
    <div class="relative w-full max-w-md">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"></path>
            </svg>
        </div>
        <input
            id="{{ $searchId }}"
            type="search"
            class="receiving-live-search h-10 w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-300 focus:ring-2 focus:ring-slate-100"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            title="{{ $placeholder }}"
        >
    </div>
    <button type="button" class="receiving-search-btn inline-flex h-10 items-center justify-center gap-1.5 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800">
        <i data-lucide="search" class="h-3.5 w-3.5"></i>
        Search
    </button>
    <button type="button" class="receiving-reset-btn hidden h-10 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
        <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
        Reset
    </button>
</div>
