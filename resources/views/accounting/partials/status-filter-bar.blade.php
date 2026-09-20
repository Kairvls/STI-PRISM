{{-- Soft status tabs + search (equipment-inventory style) --}}
@php
    $filters = $filters ?? [];
    $activeFilter = $activeFilter ?? '';
    $baseUrl = $baseUrl ?? '';
    $queryKey = $queryKey ?? 'status';
    $searchName = $searchName ?? 'search';
    $searchValue = $searchValue ?? request('search');
    $searchPlaceholder = $searchPlaceholder ?? 'Search...';
    $formId = $formId ?? null;
    $searchId = $searchId ?? null;
    $extraHidden = $extraHidden ?? [];
@endphp
<div class="border-b border-gray-100 px-5 py-4">
    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <nav
            class="flex min-w-0 flex-1 items-center gap-1 overflow-x-auto whitespace-nowrap [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
            role="tablist"
            aria-label="Status filters"
        >
            @foreach ($filters as $key => $label)
                <a
                    href="{{ $baseUrl }}?{{ $queryKey }}={{ $key }}{{ $searchValue ? '&search='.urlencode($searchValue) : '' }}@foreach($extraHidden as $hk => $hv){{ $hv !== null && $hv !== '' ? '&'.$hk.'='.urlencode($hv) : '' }}@endforeach"
                    role="tab"
                    class="status-filter-tab shrink-0 rounded-lg px-3 py-2 text-sm transition {{ $activeFilter === $key ? 'bg-gray-100/80 font-medium text-black' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800' }}"
                    data-filter="{{ $key }}"
                    aria-selected="{{ $activeFilter === $key ? 'true' : 'false' }}"
                >{{ $label }}</a>
            @endforeach
        </nav>

        <form
            method="GET"
            class="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center"
            @if($formId) id="{{ $formId }}" @endif
        >
            <input type="hidden" name="{{ $queryKey }}" value="{{ $activeFilter }}">
            @foreach ($extraHidden as $hk => $hv)
                @if ($hv !== null && $hv !== '')
                    <input type="hidden" name="{{ $hk }}" value="{{ $hv }}">
                @endif
            @endforeach
            <div class="relative">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                </svg>
                <input
                    type="search"
                    name="{{ $searchName }}"
                    @if($searchId) id="{{ $searchId }}" @endif
                    value="{{ $searchValue }}"
                    placeholder="{{ $searchPlaceholder }}"
                    class="box-border h-9 w-full rounded-lg border border-gray-200 bg-white pl-10 pr-4 text-sm leading-none text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-64"
                >
            </div>
        </form>
    </div>
</div>
