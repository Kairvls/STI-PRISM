{{-- ===================================================== --}}
{{-- RIS CONTENT PARTIAL --}}
{{-- RENDERED VIA AJAX OR FULL PAGE LOAD --}}
{{-- ===================================================== --}}

<div id="risContent" class="space-y-6">

    @php
        $filter = $filter ?? 'pending';
        $search = $search ?? '';
        $risCanAct = \App\Support\AdminPortal::canActOnRis();
        $procurementCards = [
            [
                'label' => 'All',
                'hint' => '₱'.number_format((float) ($allRisAmount ?? 0), 2),
                'value' => number_format($allRis ?? ($risRecords->total() ?? 0)),
                'tag' => 'button',
                'filterKey' => 'all',
                'extraClass' => 'ris-filter-card',
                'active' => $filter === 'all',
                'title' => 'Show all RIS records, including completed work',
            ],
            [
                'label' => 'Pending Accept',
                'hint' => '₱'.number_format((float) ($pendingRisAmount ?? 0), 2),
                'value' => number_format($pendingRis ?? 0),
                'tag' => 'button',
                'filterKey' => 'pending',
                'extraClass' => 'ris-filter-card',
                'active' => $filter === 'pending',
                'title' => 'Show procurement requests waiting for School Administrator accept',
            ],
            [
                'label' => 'Accepted',
                'hint' => '₱'.number_format((float) ($acceptedRisAmount ?? 0), 2),
                'value' => number_format($acceptedRis ?? 0),
                'tag' => 'button',
                'filterKey' => 'accepted',
                'extraClass' => 'ris-filter-card',
                'active' => $filter === 'accepted',
                'title' => 'Accepted requests waiting on Ready to Sign',
            ],
            [
                'label' => 'Returned for Revision',
                'hint' => '₱'.number_format((float) ($revisionRisAmount ?? 0), 2),
                'value' => number_format($revisionRis ?? 0),
                'tag' => 'button',
                'filterKey' => 'revision',
                'extraClass' => 'ris-filter-card',
                'active' => $filter === 'revision',
                'title' => 'RIS returned to the Purchaser for revision',
            ],
        ];

        $sectionTitle = match ($filter) {
            'all' => 'All RIS',
            'accepted' => 'Accepted',
            'revision' => 'Returned for Revision',
            default => 'Pending Accept',
        };
    @endphp


    {{-- Records card --}}
    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            @include('partials.attention-focus-chip', ['focus' => $attentionFocus ?? null, 'total' => $risRecords->total()])

            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">{{ $sectionTitle }}</h2>
                        <span
                            id="risTotalCount"
                            class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500"
                            title="Number of RIS records matching the current filter"
                        >{{ $risRecords->total() }} total</span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @include('admin.partials.view-mode-switcher', [
                        'switcherId' => 'adminPrViewSwitcher',
                        'btnClass' => 'admin-pr-view-btn',
                    ])

                    @if ($risCanAct)
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
                    @endif

                    <a
                        href="{{ \App\Support\AdminPortal::route('procurement-review.export-pdf', array_filter(['filter' => $filter, 'search' => $search, 'submitted' => $submittedDate ?? ''])) }}"
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
                <nav
                    id="risFilterSlider"
                    role="tablist"
                    aria-label="Procurement request filters"
                    class="flex min-w-0 flex-1 items-center gap-1 overflow-x-auto whitespace-nowrap [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    @foreach ($procurementCards as $card)
                        <button
                            type="button"
                            role="tab"
                            data-filter="{{ $card['filterKey'] }}"
                            title="{{ $card['title'] }}"
                            aria-selected="{{ $filter === $card['filterKey'] ? 'true' : 'false' }}"
                            class="ris-filter-btn shrink-0 rounded-lg px-3 py-2 text-sm transition
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
                    'searchInputId' => 'risLiveSearch',
                    'dateInputId' => 'risSubmittedDate',
                    'dateBtnId' => 'risSubmittedDateBtn',
                    'dateClearId' => 'risSubmittedDateClear',
                ])
            </div>
        </div>

        <div class="overflow-x-auto" id="risTableContainer" data-pr-panel="table">
            @include('admin.procurement-review._table')
        </div>

        <div id="risCardsContainer" class="hidden space-y-3 px-5 py-4" data-pr-panel="cards">
            @forelse($risRecords as $ris)
                @include('admin.partials.ris-info-card', ['ris' => $ris, 'cardMode' => 'procurement'])
            @empty
                <div class="pur-empty">
                    @if ($filter === 'pending')
                        No pending procurement requests.
                    @elseif ($filter === 'accepted')
                        No accepted requests waiting on Ready to Sign.
                    @elseif ($filter === 'revision')
                        No RIS returned for revision.
                    @else
                        No RIS records found.
                    @endif
                </div>
            @endforelse

            @include('layouts.partials.table-showing-pager', [
                'pager' => $risRecords,
                'linkClass' => 'ris-pagination-link',
                'noun' => 'records',
            ])
        </div>
    </div>

</div>
