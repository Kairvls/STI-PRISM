{{-- Calendar date filter + live search (RIS lists) --}}
@php
    $searchInputId = $searchInputId ?? 'risLiveSearch';
    $dateInputId = $dateInputId ?? 'risSubmittedDate';
    $dateBtnId = $dateBtnId ?? 'risSubmittedDateBtn';
    $dateClearId = $dateClearId ?? 'risSubmittedDateClear';
    $search = $search ?? '';
    $submittedDate = $submittedDate ?? '';
    $searchPlaceholder = $searchPlaceholder ?? 'Search RIS, Request No., requester, equipment…';
    $hasDate = $submittedDate !== '';
@endphp

<div class="flex w-full items-center gap-2 lg:max-w-md">
    <div class="relative shrink-0">
        <button
            type="button"
            id="{{ $dateBtnId }}"
            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border transition
                {{ $hasDate
                    ? 'border-[#0025cc] bg-blue-50 text-[#0025cc]'
                    : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
            title="{{ $hasDate ? 'Submitted '.$submittedDate.' — click to change' : 'Filter by submitted date' }}"
            aria-label="Filter by submitted date"
        >
            <i data-lucide="calendar" class="h-4 w-4"></i>
        </button>
        <input
            id="{{ $dateInputId }}"
            type="date"
            name="submitted"
            value="{{ $submittedDate }}"
            class="sr-only"
            tabindex="-1"
        >
        <button
            type="button"
            id="{{ $dateClearId }}"
            class="absolute -right-1.5 -top-1.5 inline-flex h-4 w-4 items-center justify-center rounded-full bg-slate-700 text-[10px] font-bold leading-none text-white shadow {{ $hasDate ? '' : 'hidden' }}"
            title="Clear submitted date filter"
            aria-label="Clear submitted date filter"
        >
            ×
        </button>
    </div>

    <div class="relative min-w-0 flex-1">
        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
        </svg>
        <input
            id="{{ $searchInputId }}"
            type="search"
            name="search"
            value="{{ $search }}"
            placeholder="{{ $searchPlaceholder }}"
            autocomplete="off"
            title="Search records"
            class="h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white"
        >
    </div>
</div>
