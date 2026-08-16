@php
    $searchId = $searchId ?? 'receivingLiveSearch';
    $placeholder = $placeholder ?? 'Search RIS, ATP, supplier, OR...';
@endphp

<div class="relative w-full lg:max-w-md">
    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"></path>
        </svg>
    </div>
    <input
        id="{{ $searchId }}"
        type="search"
        class="receiving-live-search w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:ring-2 focus:ring-gray-100"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        title="{{ $placeholder }}"
    >
</div>
