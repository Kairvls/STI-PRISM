{{-- ===================================================== --}}
{{-- SIGN RIS CONTENT PARTIAL --}}
{{-- RENDERED VIA AJAX OR FULL PAGE LOAD --}}
{{-- ===================================================== --}}

<div id="signRisContent" class="space-y-6">

    @php
        $filter = $filter ?? 'pending';
        $search = $search ?? '';
        $signRisCards = [
            [
                'label' => 'Pending',
                'hint' => '₱'.number_format((float) ($pendingActionAmount ?? 0), 2),
                'value' => number_format($pendingActionCount ?? (($forDecisionCount ?? 0) + ($forCosignCount ?? 0))),
                'tag' => 'button',
                'filterKey' => 'pending',
                'extraClass' => 'sign-ris-filter-card',
                'active' => $filter === 'pending',
                'title' => 'Accepted decisions plus Issued by awaiting signature',
            ],
            [
                'label' => 'For Decision',
                'hint' => '₱'.number_format((float) ($forDecisionAmount ?? 0), 2),
                'value' => number_format($forDecisionCount ?? 0),
                'tag' => 'button',
                'filterKey' => 'for_decision',
                'extraClass' => 'sign-ris-filter-card',
                'active' => $filter === 'for_decision',
                'title' => 'Accepted RIS ready to Forward, Approve Directly, or Return',
            ],
            [
                'label' => 'Awaiting Issued by',
                'hint' => '₱'.number_format((float) ($forCosignAmount ?? 0), 2),
                'value' => number_format($forCosignCount ?? 0),
                'tag' => 'button',
                'filterKey' => 'for_cosign',
                'extraClass' => 'sign-ris-filter-card',
                'active' => $filter === 'for_cosign',
                'title' => 'President-approved RIS awaiting Issued by',
            ],
        ];
    @endphp

    {{-- Metric strip --}}
    @include('layouts.partials.maintenance-stat-cards', ['cards' => $signRisCards])

    {{-- Records card --}}
    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">Sign RIS</h2>
                        <span
                            id="signRisTotalCount"
                            class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500"
                            title="Number of RIS records matching the current filter"
                        >{{ $signableRisRecords->total() }} total</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">
                        Forward to President, approve directly, return for revision, or sign Issued by after the President approves.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @include('admin.partials.view-mode-switcher', [
                        'switcherId' => 'adminSignViewSwitcher',
                        'btnClass' => 'admin-sign-view-btn',
                    ])

                    <a
                        href="{{ route('admin.digital-signatures.sign-ris.export-pdf', ['filter' => $filter, 'search' => $search]) }}"
                        title="Export the current Sign RIS table to PDF"
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
                    id="signRisFilterSlider"
                    role="tablist"
                    aria-label="Sign RIS filters"
                    class="relative inline-flex max-w-full items-center overflow-x-auto rounded-lg bg-slate-100 p-1"
                >
                    <span
                        class="sign-ris-filter-thumb pointer-events-none absolute top-1 left-0 z-0 h-8 rounded-md bg-white shadow-sm will-change-transform"
                        style="transform: translate3d(0, 0, 0); transition: transform 220ms cubic-bezier(0.22, 1, 0.36, 1), width 220ms cubic-bezier(0.22, 1, 0.36, 1);"
                        aria-hidden="true"
                    ></span>

                    @foreach ($signRisCards as $card)
                        <button
                            type="button"
                            role="tab"
                            data-filter="{{ $card['filterKey'] }}"
                            title="{{ $card['title'] }}"
                            aria-selected="{{ $filter === $card['filterKey'] ? 'true' : 'false' }}"
                            class="sign-ris-filter-btn relative z-10 flex h-8 shrink-0 items-center whitespace-nowrap rounded-md px-3.5 text-xs font-semibold transition-colors
                                {{ $filter === $card['filterKey'] ? 'text-slate-950' : 'text-slate-500 hover:text-slate-900' }}
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
                        id="signRisLiveSearch"
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search RIS, requester, equipment…"
                        autocomplete="off"
                        title="Search RIS records"
                        class="h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:bg-white"
                    >
                </div>
            </div>
        </div>

        <div class="overflow-x-auto" id="signRisTableContainer" data-sign-panel="table">
            @include('admin.digital-signatures._sign-ris-table')
        </div>

        <div id="signRisCardsContainer" class="hidden space-y-3 px-5 py-4" data-sign-panel="cards">
            @forelse($signableRisRecords as $ris)
                @include('admin.partials.ris-info-card', ['ris' => $ris, 'cardMode' => 'sign'])
            @empty
                <div class="pur-empty">No RIS records found.</div>
            @endforelse

            @include('layouts.partials.table-showing-pager', [
                'pager' => $signableRisRecords,
                'linkClass' => 'sign-ris-pagination-link',
                'noun' => 'records',
            ])
        </div>
    </div>

</div>
