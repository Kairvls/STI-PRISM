{{-- ===================================================== --}}
{{-- SIGNATURE HISTORY CONTENT PARTIAL --}}
{{-- RENDERED VIA AJAX OR FULL PAGE LOAD --}}
{{-- ===================================================== --}}

<div id="signatureHistoryContent">


    {{-- ===================================================== --}}
    {{-- CURRENT SEARCH --}}
    {{-- ===================================================== --}}

    @php
        $search = $search ?? '';
        $filter = $filter ?? 'all';
        $historyCards = [
            [
                'filter' => 'all',
                'label' => 'All',
                'count' => $allCount ?? ($signatureHistory->total() ?? 0),
                'color' => 'text-slate-900',
                'title' => 'Show every identifiable RIS record',
            ],
            [
                'filter' => 'direct_approved',
                'label' => 'Admin Approved',
                'count' => $directApprovedCount ?? 0,
                'color' => 'text-sky-600',
                'title' => 'Show RIS forms approved directly by Admin',
            ],
            [
                'filter' => 'president_approved',
                'label' => 'Approved by the President',
                'count' => $presidentApprovedCount ?? 0,
                'color' => 'text-blue-600',
                'title' => 'Show RIS forms approved by the President',
            ],
            [
                'filter' => 'president_rejected',
                'label' => 'Rejected by the President',
                'count' => $presidentRejectedCount ?? 0,
                'color' => 'text-amber-700',
                'title' => 'Show RIS forms rejected by the President',
            ],
            [
                'filter' => 'amend',
                'label' => 'Amend',
                'count' => $amendedCount ?? 0,
                'color' => 'text-amber-600',
                'title' => 'Show RIS forms returned to Purchaser for amendment',
            ],
        ];
    @endphp


    {{-- ===================================================== --}}
    {{-- RIS STATISTIC CARDS --}}
    {{-- ===================================================== --}}

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($historyCards as $card)
            <button
                type="button"
                data-filter="{{ $card['filter'] }}"
                title="{{ $card['title'] }}"
                aria-pressed="{{ $filter === $card['filter'] ? 'true' : 'false' }}"
                class="signature-history-filter-card rounded-[18px] border bg-white px-5 py-5 text-left shadow-[0_1px_2px_rgba(15,23,42,0.03)] transition
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
            </button>
        @endforeach
    </div>


    {{-- ===================================================== --}}
    {{-- SIGNATURE HISTORY TABLE CARD --}}
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
                            RIS History
                        </h2>

                        <p class="mt-1 text-xs text-gray-500">
                            Complete RIS log, including incomplete and in-progress forms, sorted by latest
                        </p>

                    </div>


                    <div class="flex flex-wrap items-center justify-end gap-2 shrink-0">

                        @include('admin.partials.view-mode-switcher', [
                            'switcherId' => 'adminHistoryViewSwitcher',
                            'btnClass' => 'admin-history-view-btn',
                        ])

                        <a
                            href="{{ route('admin.digital-signatures.history.export-pdf', ['search' => $search, 'filter' => $filter]) }}"
                            title="Export the current Signature History table to PDF"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Export Table to PDF
                        </a>

                        <div
                            id="signatureHistoryTotalCount"
                            class="rounded-lg border border-slate-200 bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700"
                            title="Number of RIS records matching the current filter and search"
                        >

                            {{ $signatureHistory->total() }} total

                        </div>

                    </div>

                </div>


                {{-- FILTERS + LIVE SEARCH --}}

                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">

                    <div
                        id="signatureHistoryFilterSlider"
                        role="tablist"
                        aria-label="Signature history filters"
                        class="relative inline-flex max-w-full items-center overflow-x-auto rounded-xl bg-slate-200/70 p-1"
                    >
                        <span
                            class="signature-history-filter-thumb pointer-events-none absolute top-1 left-0 z-0 h-9 rounded-lg bg-white shadow-sm will-change-transform"
                            style="transform: translate3d(0, 0, 0); transition: transform 220ms cubic-bezier(0.22, 1, 0.36, 1), width 220ms cubic-bezier(0.22, 1, 0.36, 1);"
                            aria-hidden="true"
                        ></span>

                        @foreach ($historyCards as $card)
                            <button
                                type="button"
                                role="tab"
                                data-filter="{{ $card['filter'] }}"
                                title="{{ $card['title'] }}"
                                aria-selected="{{ $filter === $card['filter'] ? 'true' : 'false' }}"
                                class="signature-history-filter-btn relative z-10 flex h-9 shrink-0 items-center whitespace-nowrap rounded-lg px-4 text-xs font-semibold transition-colors
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
                            id="signatureHistoryLiveSearch"
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Search RIS, requester, equipment..."
                            autocomplete="off"
                            title="Search signature history records"
                            class="w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:ring-2 focus:ring-gray-100"
                        >

                    </div>

                </div>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- SIGNATURE HISTORY TABLE --}}
        {{-- ===================================================== --}}

        <div class="overflow-x-auto" id="signatureHistoryTableContainer" data-history-panel="table">
            @include('admin.digital-signatures._signature-history-table')
        </div>

        <div id="signatureHistoryCardsContainer" class="hidden space-y-3 px-5 py-4" data-history-panel="cards">
            @forelse($signatureHistory as $history)
                @include('admin.partials.ris-info-card', ['ris' => $history, 'cardMode' => 'history'])
            @empty
                <div class="px-2 py-10 text-center text-sm text-gray-400">No RIS records found.</div>
            @endforelse

            @include('layouts.partials.table-showing-pager', [
                'pager' => $signatureHistory,
                'linkClass' => 'signature-history-pagination-link',
                'noun' => 'records',
            ])
        </div>

    </div>


</div>

