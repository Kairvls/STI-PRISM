{{-- ===================================================== --}}
{{-- RIS CONTENT PARTIAL --}}
{{-- RENDERED VIA AJAX OR FULL PAGE LOAD --}}
{{-- ===================================================== --}}

<div id="risContent">


    {{-- ===================================================== --}}
    {{-- CURRENT FILTER & SEARCH --}}
    {{-- ===================================================== --}}

    @php
        $filter = $filter ?? 'pending';
        $search = $search ?? '';
        $procurementCards = [
            [
                'filter' => 'all',
                'label' => 'All',
                'count' => $allRis ?? ($risRecords->total() ?? 0),
                'amount' => $allRisAmount ?? 0,
                'color' => 'text-slate-900',
                'title' => 'Show all RIS records, including completed work',
            ],
            [
                'filter' => 'pending',
                'label' => 'Pending',
                'count' => $pendingRis ?? 0,
                'amount' => $pendingRisAmount ?? 0,
                'color' => 'text-sky-600',
                'title' => 'Show RIS forms that still need admin review',
            ],
            [
                'filter' => 'forwarded',
                'label' => 'Forwarded to President',
                'count' => $forwardedRis ?? 0,
                'amount' => $forwardedRisAmount ?? 0,
                'color' => 'text-amber-600',
                'title' => 'Show RIS forms currently waiting on the President',
            ],
        ];
    @endphp


    {{-- ===================================================== --}}
    {{-- RIS STATISTIC CARDS --}}
    {{-- ===================================================== --}}

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($procurementCards as $card)
            <button
                type="button"
                data-filter="{{ $card['filter'] }}"
                title="{{ $card['title'] }}"
                aria-pressed="{{ $filter === $card['filter'] ? 'true' : 'false' }}"
                class="ris-filter-card rounded-[18px] border bg-white px-5 py-5 text-left shadow-[0_1px_2px_rgba(15,23,42,0.03)] transition
                    {{ $filter === $card['filter']
                        ? 'border-slate-900/20 ring-2 ring-slate-900/10'
                        : 'border-gray-200 hover:border-gray-300' }}
                "
            >
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {{ $card['label'] }}
                </p>
                <div class="mt-3">
                    <span class="font-['Outfit'] text-3xl font-bold {{ $card['color'] }}">
                        {{ $card['count'] }}
                    </span>
                </div>
                <div class="mt-1 text-xs text-gray-400">
                    ₱{{ number_format((float) ($card['amount'] ?? 0), 2) }}
                </div>
            </button>
        @endforeach
    </div>


    {{-- ===================================================== --}}
    {{-- PROCUREMENT REQUEST TABLE CARD --}}
    {{-- ===================================================== --}}

    <div class="mt-6 overflow-hidden rounded-[18px] border border-gray-200 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.03)]">


        {{-- ================================================= --}}
        {{-- TABLE CARD HEADER --}}
        {{-- ================================================= --}}

        <div class="border-b border-gray-100 px-5 py-4">

            <div class="flex flex-col gap-4">


                {{-- ================================================= --}}
                {{-- TABLE TITLE --}}
                {{-- ================================================= --}}

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                    <div>

                        <h2 class="text-sm font-semibold text-gray-900">
                            RIS Records
                        </h2>

                        <p class="mt-1 text-xs text-gray-500">
                            RIS forms that still need attention. Completed, amended, and admin-approved forms leave this queue.
                        </p>

                    </div>


                    <div class="flex flex-wrap items-center justify-end gap-2 shrink-0">

                        @include('admin.partials.view-mode-switcher', [
                            'switcherId' => 'adminPrViewSwitcher',
                            'btnClass' => 'admin-pr-view-btn',
                        ])

                        <a
                            href="{{ route('admin.procurement-review.export-pdf', ['filter' => $filter, 'search' => $search]) }}"
                            title="Export the current RIS table to PDF"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Export Table to PDF
                        </a>

                        <div
                            id="risTotalCount"
                            class="rounded-lg border border-slate-200 bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700"
                            title="Number of RIS records matching the current filter"
                        >

                            {{ $risRecords->total() }} total

                        </div>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- FILTERS + LIVE SEARCH --}}
                {{-- ================================================= --}}

                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">


                    {{-- ================================================= --}}
                    {{-- STATUS FILTER SLIDER --}}
                    {{-- ================================================= --}}

                    <div
                        id="risFilterSlider"
                        role="tablist"
                        aria-label="Procurement request filters"
                        class="relative inline-flex max-w-full items-center overflow-x-auto rounded-xl bg-slate-200/70 p-1"
                    >
                        <span
                            class="ris-filter-thumb pointer-events-none absolute top-1 left-0 z-0 h-9 rounded-lg bg-white shadow-sm will-change-transform"
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
                                class="ris-filter-btn relative z-10 flex h-9 shrink-0 items-center whitespace-nowrap rounded-lg px-4 text-xs font-semibold transition-colors
                                    {{ $filter === $card['filter'] ? 'text-slate-950' : 'text-slate-500 hover:text-slate-900' }}
                                "
                            >
                                {{ $card['label'] }}
                            </button>
                        @endforeach
                    </div>


                    {{-- ================================================= --}}
                    {{-- LIVE SEARCH --}}
                    {{-- ================================================= --}}

                    <div class="relative w-full lg:max-w-md">

                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">

                            <svg
                                class="h-4 w-4 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"
                                ></path>

                            </svg>

                        </div>


                        <input
                            id="risLiveSearch"
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Search RIS, requester, equipment, status..."
                            autocomplete="off"
                            title="Search RIS records"
                            class="w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:ring-2 focus:ring-gray-100"
                        >

                    </div>

                </div>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- RIS TABLE / CARDS --}}
        {{-- ===================================================== --}}

        <div class="overflow-x-auto" id="risTableContainer" data-pr-panel="table">
            @include('admin.procurement-review._table')
        </div>

        <div id="risCardsContainer" class="hidden space-y-3 px-5 py-4" data-pr-panel="cards">
            @forelse($risRecords as $ris)
                @include('admin.partials.ris-info-card', ['ris' => $ris, 'cardMode' => 'procurement'])
            @empty
                <div class="px-2 py-10 text-center text-sm text-gray-400">No RIS records found.</div>
            @endforelse

            @include('layouts.partials.table-showing-pager', [
                'pager' => $risRecords,
                'linkClass' => 'ris-pagination-link',
                'noun' => 'records',
            ])
        </div>

    </div>


</div>

