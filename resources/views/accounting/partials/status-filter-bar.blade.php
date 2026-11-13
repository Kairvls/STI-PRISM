{{-- Soft status tabs + optional calendar date + search (equipment-inventory style) --}}
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
    $counts = $counts ?? null;
    $listPanel = $listPanel ?? null;
    $dateName = $dateName ?? null;
    $dateValue = $dateValue ?? '';
    $dateInputId = $dateInputId ?? 'statusFilterDate';
    $dateBtnId = $dateBtnId ?? 'statusFilterDateBtn';
    $dateClearId = $dateClearId ?? 'statusFilterDateClear';
    $dateTitle = $dateTitle ?? 'Filter by submitted date';
    $hasDate = $dateName && $dateValue !== '';
    $exportUrl = $exportUrl ?? null;
@endphp
<div class="border-b border-gray-100 px-5 py-4">
    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <nav
            class="flex min-w-0 flex-1 items-center gap-1 overflow-x-auto whitespace-nowrap [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
            role="tablist"
            aria-label="Status filters"
        >
            @foreach ($filters as $key => $label)
                @php
                    $tabParams = array_filter(array_merge(
                        [$queryKey => $key, $searchName => $searchValue ?: null],
                        $extraHidden,
                        ($dateName && $dateValue !== '') ? [$dateName => $dateValue] : []
                    ), fn ($v) => $v !== null && $v !== '');
                    $tabHref = $baseUrl.(str_contains($baseUrl, '?') ? '&' : '?').http_build_query($tabParams);
                @endphp
                <a
                    href="{{ $tabHref }}"
                    role="tab"
                    class="status-filter-tab shrink-0 rounded-lg px-3 py-2 text-sm transition {{ $activeFilter === $key ? 'bg-gray-100/80 font-medium text-black' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800' }}"
                    data-filter="{{ $key }}"
                    @if($listPanel) data-list-panel="{{ $listPanel }}" @endif
                    aria-selected="{{ $activeFilter === $key ? 'true' : 'false' }}"
                >
                    {{ $label }}
                    @if(is_array($counts) && array_key_exists($key, $counts))
                        <span class="ml-1 tabular-nums" data-count="{{ $key }}">{{ $counts[$key] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center">
            @if($exportUrl)
                <a
                    href="{{ $exportUrl }}"
                    title="Export the current list to PDF"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export PDF
                </a>
            @endif
        <form
            method="GET"
            class="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center"
            @if($formId) id="{{ $formId }}" @endif
            @if($listPanel) data-auto-filter data-list-panel="{{ $listPanel }}" @endif
        >
            <input type="hidden" name="{{ $queryKey }}" value="{{ $activeFilter }}">
            @foreach ($extraHidden as $hk => $hv)
                @if ($hv !== null && $hv !== '')
                    <input type="hidden" name="{{ $hk }}" value="{{ $hv }}">
                @endif
            @endforeach

            @if($dateName)
                <div class="relative shrink-0" data-status-date-filter>
                    <button
                        type="button"
                        id="{{ $dateBtnId }}"
                        data-date-open
                        data-date-input="{{ $dateInputId }}"
                        data-date-title="{{ $dateTitle }}"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border transition
                            {{ $hasDate
                                ? 'border-[#0025cc] bg-blue-50 text-[#0025cc]'
                                : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                        title="{{ $hasDate ? 'Submitted '.$dateValue.' — click to change' : $dateTitle }}"
                        aria-label="{{ $dateTitle }}"
                    >
                        <i data-lucide="calendar" class="h-4 w-4"></i>
                    </button>
                    <input
                        id="{{ $dateInputId }}"
                        type="date"
                        name="{{ $dateName }}"
                        value="{{ $dateValue }}"
                        data-date-field
                        class="sr-only"
                        tabindex="-1"
                    >
                    <button
                        type="button"
                        id="{{ $dateClearId }}"
                        data-date-clear
                        data-date-input="{{ $dateInputId }}"
                        data-date-title="{{ $dateTitle }}"
                        class="absolute -right-1.5 -top-1.5 inline-flex h-4 w-4 items-center justify-center rounded-full bg-slate-700 text-[10px] font-bold leading-none text-white shadow {{ $hasDate ? '' : 'hidden' }}"
                        title="Clear date filter"
                        aria-label="Clear date filter"
                    >
                        ×
                    </button>
                </div>
            @endif

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
</div>
@once
<script>
    (function () {
        if (window.PrismStatusDateFilter) return;
        window.PrismStatusDateFilter = true;

        function syncDateUi(input) {
            if (!input) return;
            const wrap = input.closest('[data-status-date-filter]');
            if (!wrap) return;
            const btn = wrap.querySelector('[data-date-open]');
            const clear = wrap.querySelector('[data-date-clear]');
            const title = (btn && btn.getAttribute('data-date-title')) || 'Filter by submitted date';
            const has = input.value !== '';
            if (clear) clear.classList.toggle('hidden', !has);
            if (!btn) return;
            btn.classList.toggle('border-[#0025cc]', has);
            btn.classList.toggle('bg-blue-50', has);
            btn.classList.toggle('text-[#0025cc]', has);
            btn.classList.toggle('border-gray-200', !has);
            btn.classList.toggle('bg-white', !has);
            btn.classList.toggle('text-gray-600', !has);
            btn.title = has ? ('Submitted ' + input.value + ' — click to change') : title;
        }

        document.addEventListener('click', function (event) {
            const openBtn = event.target.closest('[data-date-open]');
            if (openBtn) {
                event.preventDefault();
                const input = document.getElementById(openBtn.getAttribute('data-date-input') || '');
                if (!input) return;
                try {
                    if (typeof input.showPicker === 'function') input.showPicker();
                    else { input.focus(); input.click(); }
                } catch (e) {
                    input.focus();
                }
                return;
            }

            const clearBtn = event.target.closest('[data-date-clear]');
            if (!clearBtn) return;
            event.preventDefault();
            event.stopPropagation();
            const input = document.getElementById(clearBtn.getAttribute('data-date-input') || '');
            if (!input) return;
            input.value = '';
            syncDateUi(input);
            if (typeof input.form?.requestSubmit === 'function') input.form.requestSubmit();
            else if (input.form) input.form.submit();
        });

        document.addEventListener('change', function (event) {
            const input = event.target;
            if (!input || !input.matches || !input.matches('[data-date-field]')) return;
            syncDateUi(input);
        });
    })();
</script>
@endonce
