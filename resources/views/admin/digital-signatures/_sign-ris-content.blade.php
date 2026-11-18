{{-- ===================================================== --}}
{{-- SIGN RIS CONTENT PARTIAL --}}
{{-- RENDERED VIA AJAX OR FULL PAGE LOAD --}}
{{-- ===================================================== --}}

<div id="signRisContent" class="space-y-6">

    @php
        $filter = $filter ?? 'for_decision';
        $search = $search ?? '';
        $signRisCards = [
            [
                'label' => 'All',
                'hint' => '₱'.number_format((float) ($allAmount ?? 0), 2),
                'value' => number_format($allCount ?? 0),
                'tag' => 'button',
                'filterKey' => 'all',
                'extraClass' => 'sign-ris-filter-card',
                'active' => $filter === 'all',
                'title' => 'All Ready to Sign records, including forwarded to President and directly approved',
            ],
            [
                'label' => 'Needs decision',
                'hint' => '₱'.number_format((float) ($forDecisionAmount ?? 0), 2),
                'value' => number_format($forDecisionCount ?? 0),
                'tag' => 'button',
                'filterKey' => 'for_decision',
                'extraClass' => 'sign-ris-filter-card',
                'active' => $filter === 'for_decision',
                'title' => 'Accepted RIS ready to Forward or Approve Directly (fresh only)',
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


    {{-- Records card --}}
    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            @include('partials.attention-focus-chip', ['focus' => $attentionFocus ?? null, 'total' => $signableRisRecords->total()])

            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">Ready to Sign</h2>
                        <span
                            id="signRisTotalCount"
                            class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500"
                            title="Number of RIS records matching the current filter"
                        >{{ $signableRisRecords->total() }} total</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">
                        @if (\App\Support\AdminPortal::canActOnRis())
                            Direct Approve, Pass to President, return for revision, or sign Issued by after the President approves.
                        @else
                            View only. The School Administrator Direct Approves, Passes to President, returns, and signs Issued by.
                        @endif
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @include('admin.partials.view-mode-switcher', [
                        'switcherId' => 'adminSignViewSwitcher',
                        'btnClass' => 'admin-sign-view-btn',
                    ])

                    <a
                        href="{{ \App\Support\AdminPortal::route('digital-signatures.sign-ris.export-pdf', array_filter(['filter' => $filter, 'search' => $search, 'submitted' => $submittedDate ?? '', 'urgency' => $urgencyFilter ?? ''])) }}"
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
                <nav
                    id="signRisFilterSlider"
                    role="tablist"
                    aria-label="Sign RIS filters"
                    class="flex min-w-0 flex-1 items-center gap-1 overflow-x-auto whitespace-nowrap [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    @foreach ($signRisCards as $card)
                        <button
                            type="button"
                            role="tab"
                            data-filter="{{ $card['filterKey'] }}"
                            title="{{ $card['title'] }}"
                            aria-selected="{{ $filter === $card['filterKey'] ? 'true' : 'false' }}"
                            class="sign-ris-filter-btn shrink-0 rounded-lg px-3 py-2 text-sm transition
                                {{ $filter === $card['filterKey']
                                    ? 'bg-gray-100/80 font-medium text-black'
                                    : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800' }}
                            "
                        >
                            {{ $card['label'] }}
                            <span class="ml-1 tabular-nums" data-count="{{ $card['filterKey'] }}">{{ $card['value'] }}</span>
                        </button>
                    @endforeach
                </nav>

                @include('admin.partials.ris-submitted-date-search', [
                    'search' => $search,
                    'submittedDate' => $submittedDate ?? '',
                    'urgencyFilter' => $urgencyFilter ?? '',
                    'urgencyOnChange' => 'applySignRisUrgencyFilter',
                    'searchInputId' => 'signRisLiveSearch',
                    'dateInputId' => 'signRisSubmittedDate',
                    'dateBtnId' => 'signRisSubmittedDateBtn',
                    'dateClearId' => 'signRisSubmittedDateClear',
                    'urgencyInputId' => 'signRisUrgencyFilter',
                ])
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
