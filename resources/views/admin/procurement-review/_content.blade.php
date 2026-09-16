{{-- ===================================================== --}}
{{-- RIS CONTENT PARTIAL --}}
{{-- RENDERED VIA AJAX OR FULL PAGE LOAD --}}
{{-- ===================================================== --}}

<div id="risContent" class="space-y-6">

    @php
        $filter = $filter ?? 'pending';
        $search = $search ?? '';
        $procurementCards = [
            [
                'filter' => 'all',
                'label' => 'All',
                'count' => $allRis ?? ($risRecords->total() ?? 0),
                'amount' => $allRisAmount ?? 0,
                'color' => 'text-gray-950',
                'dot' => 'bg-slate-400',
                'title' => 'Show all RIS records, including completed work',
            ],
            [
                'filter' => 'pending',
                'label' => 'Pending Accept',
                'count' => $pendingRis ?? 0,
                'amount' => $pendingRisAmount ?? 0,
                'color' => 'text-[#0025cc]',
                'dot' => 'bg-[#0025cc]',
                'title' => 'Show procurement requests waiting for Admin accept',
            ],
            [
                'filter' => 'accepted',
                'label' => 'Accepted',
                'count' => $acceptedRis ?? 0,
                'amount' => $acceptedRisAmount ?? 0,
                'color' => 'text-gray-950',
                'dot' => 'bg-violet-400',
                'title' => 'Accepted requests waiting for a Sign RIS decision',
            ],
        ];
    @endphp

    {{-- Metric strip --}}
    <div class="pur-card">
        <div class="grid grid-cols-1 divide-gray-100 sm:grid-cols-3 sm:divide-x">
            @foreach ($procurementCards as $card)
                <button
                    type="button"
                    data-filter="{{ $card['filter'] }}"
                    title="{{ $card['title'] }}"
                    aria-pressed="{{ $filter === $card['filter'] ? 'true' : 'false' }}"
                    class="ris-filter-card block px-5 py-5 text-left transition hover:bg-gray-50/70
                        {{ $filter === $card['filter'] ? 'bg-gray-50/80' : '' }}
                    "
                >
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                        {{ $card['label'] }}
                    </p>
                    <div class="mt-2 flex items-center gap-2">
                        <span class="text-2xl font-semibold tracking-tight {{ $card['color'] }}">
                            {{ $card['count'] }}
                        </span>
                        @if($filter === $card['filter'])
                            <span class="h-1.5 w-1.5 rounded-full {{ $card['dot'] }}"></span>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-gray-400">
                        ₱{{ number_format((float) ($card['amount'] ?? 0), 2) }}
                    </p>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Records card --}}
    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">Procurement Requests</h2>
                        <span
                            id="risTotalCount"
                            class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500"
                            title="Number of RIS records matching the current filter"
                        >{{ $risRecords->total() }} total</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">
                        Accept purchaser submissions to send them to Sign RIS for Forward, Approve Directly, or Return.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @include('admin.partials.view-mode-switcher', [
                        'switcherId' => 'adminPrViewSwitcher',
                        'btnClass' => 'admin-pr-view-btn',
                    ])

                    <div
                        id="risBulkAcceptBar"
                        class="hidden items-center gap-2 rounded-lg border border-[#0025cc]/20 bg-[#0025cc]/5 px-2.5 py-1.5"
                    >
                        <span id="risBulkAcceptCount" class="text-xs font-semibold text-[#0025cc]">
                            0 selected
                        </span>
                        <button
                            type="button"
                            id="risBulkAcceptBtn"
                            onclick="typeof window.openSelectedRisAcceptModal === 'function' && window.openSelectedRisAcceptModal()"
                            class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white transition hover:bg-[#001fa8]"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Accept selected
                        </button>
                        <button
                            type="button"
                            onclick="typeof window.clearRisAcceptSelection === 'function' && window.clearRisAcceptSelection()"
                            class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                        >
                            Clear
                        </button>
                    </div>

                    <a
                        href="{{ route('admin.procurement-review.export-pdf', ['filter' => $filter, 'search' => $search]) }}"
                        title="Export the current RIS table to PDF"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export PDF
                    </a>
                </div>
            </div>

            <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div
                    id="risFilterSlider"
                    role="tablist"
                    aria-label="Procurement request filters"
                    class="relative inline-flex max-w-full items-center overflow-x-auto rounded-lg bg-slate-100 p-1"
                >
                    <span
                        class="ris-filter-thumb pointer-events-none absolute top-1 left-0 z-0 h-8 rounded-md bg-white shadow-sm will-change-transform"
                        style="transform: translate3d(0, 0, 0); transition: transform 220ms cubic-bezier(0.22, 1, 0.36, 1), width 220ms cubic-bezier(0.22, 1, 0.36, 1);"
                        aria-hidden="true"
                    ></span>

                    @foreach ($procurementCards as $card)
                        <button
                            type="button"
                            role="tab"
                            data-filter="{{ $card['filter'] }}"
                            title="{{ $card['title'] }}"
                            aria-selected="{{ $filter === $card['filter'] ? 'true' : 'false' }}"
                            class="ris-filter-btn relative z-10 flex h-8 shrink-0 items-center whitespace-nowrap rounded-md px-3.5 text-xs font-semibold transition-colors
                                {{ $filter === $card['filter'] ? 'text-slate-950' : 'text-slate-500 hover:text-slate-900' }}
                            "
                        >
                            {{ $card['label'] }}
                        </button>
                    @endforeach
                </div>

                <div class="relative w-full lg:max-w-sm">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                    </svg>
                    <input
                        id="risLiveSearch"
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search RIS, requester, equipment, status…"
                        autocomplete="off"
                        title="Search RIS records"
                        class="h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white"
                    >
                </div>
            </div>
        </div>

        <div class="overflow-x-auto" id="risTableContainer" data-pr-panel="table">
            @include('admin.procurement-review._table')
        </div>

        <div id="risCardsContainer" class="hidden space-y-3 px-5 py-4" data-pr-panel="cards">
            @forelse($risRecords as $ris)
                @include('admin.partials.ris-info-card', ['ris' => $ris, 'cardMode' => 'procurement'])
            @empty
                <div class="pur-empty">No RIS records found.</div>
            @endforelse

            @include('layouts.partials.table-showing-pager', [
                'pager' => $risRecords,
                'linkClass' => 'ris-pagination-link',
                'noun' => 'records',
            ])
        </div>
    </div>

</div>
