{{-- ===================================================== --}}
{{-- SIGN RIS CONTENT PARTIAL --}}
{{-- RENDERED VIA AJAX OR FULL PAGE LOAD --}}
{{-- ===================================================== --}}

<div id="signRisContent" class="space-y-6">

    @php
        $filter = $filter ?? 'for_decision';
        $search = $search ?? '';
        $isIssuedBy = $filter === 'for_cosign';
        $signRisCards = [
            [
                'label' => 'All',
                'hint' => '',
                'value' => number_format($allCount ?? 0),
                'tag' => 'button',
                'filterKey' => 'all',
                'extraClass' => 'sign-ris-filter-card',
                'active' => $filter === 'all',
                'title' => 'All Ready to Sign records, including forwarded to President and directly approved',
            ],
            [
                'label' => 'Needs decision',
                'hint' => '',
                'value' => number_format($forDecisionCount ?? 0),
                'tag' => 'button',
                'filterKey' => 'for_decision',
                'extraClass' => 'sign-ris-filter-card',
                'active' => $filter === 'for_decision',
                'title' => 'Accepted RIS ready to Forward or Approve Directly (fresh only)',
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
                        <h2 class="text-base font-semibold text-gray-950">{{ $isIssuedBy ? 'Awaiting Issued by' : 'Ready to Sign' }}</h2>
                        <span
                            id="signRisTotalCount"
                            class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500"
                            title="Number of RIS records matching the current filter"
                        >{{ $signableRisRecords->total() }} total</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">
                        @if ($isIssuedBy)
                            @if (\App\Support\AdminPortal::canActOnRis())
                                President-approved RIS waiting for the Issued by signature.
                            @else
                                View only. The School Administrator signs Issued by after the President approves.
                            @endif
                        @elseif (\App\Support\AdminPortal::canActOnRis())
                            Direct Approve, Pass to President, or return for revision.
                        @else
                            View only. The School Administrator Direct Approves, Passes to President, or returns for revision.
                        @endif
                    </p>
                </div>

                <div
                    class="flex flex-wrap items-center gap-2"
                    x-data="{
                        budgetModalOpen: false,
                        budgetLimitVal: {{ json_encode((float) \App\Support\RisApprovalBudget::limit()) }},
                        budgetLimitDisplay: {{ json_encode(number_format((float) \App\Support\RisApprovalBudget::limit(), 2, '.', ',')) }},
                        formatBudgetInput(raw) {
                            let cleaned = String(raw || '').replace(/[^0-9.]/g, '');
                            const parts = cleaned.split('.');
                            let intPart = (parts[0] || '').replace(/^0+(?=\d)/, '');
                            let decPart = parts.length > 1 ? parts.slice(1).join('').slice(0, 2) : null;
                            const num = parseFloat((intPart || '0') + (decPart !== null ? '.' + decPart : ''));
                            this.budgetLimitVal = isNaN(num) ? 0 : Math.min(999999999.99, num);
                            const formattedInt = intPart ? intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '';
                            this.budgetLimitDisplay = decPart !== null ? (formattedInt || '0') + '.' + decPart : formattedInt;
                        },
                        normalizeBudgetBlur() {
                            const n = Number(this.budgetLimitVal || 0);
                            this.budgetLimitDisplay = n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                    }"
                >
                    @php
                        $currentBudgetLimit = \App\Support\RisApprovalBudget::limit();
                        $canEditBudgetLimit = \App\Support\AdminPortal::canActOnRis();
                    @endphp
                    @if ($canEditBudgetLimit)
                        <button
                            type="button"
                            @click="budgetModalOpen = true"
                            title="Configure direct-approval vs. forward-to-President budget threshold"
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50/70 px-3 text-xs font-semibold transition hover:bg-blue-100/80"
                            style="color:#1d4ed8;"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"/>
                            </svg>
                            <span>Budget Limit: {{ \App\Support\RisApprovalBudget::formatPeso($currentBudgetLimit) }}</span>
                        </button>

                        {{-- Teleported Approval Budget Maintenance Modal --}}
                        <template x-teleport="body">
                            <div
                                x-cloak
                                x-show="budgetModalOpen"
                                @keydown.escape.window="if (budgetModalOpen) budgetModalOpen = false"
                                @click.self="budgetModalOpen = false"
                                class="fixed inset-0 z-[270] flex items-center justify-center bg-slate-900/45 p-4"
                            >
                                <form
                                    method="POST"
                                    action="{{ \App\Support\AdminPortal::route('digital-signatures.sign-ris.budget-limit') }}"
                                    class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
                                >
                                    @csrf
                                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-6 py-4">
                                        <div>
                                            <h3 class="text-base font-bold text-slate-900">Approval Budget Limit</h3>
                                            <p class="mt-0.5 text-xs text-slate-500">Controls when School Admin can Approve Directly vs. Forward RIS to President.</p>
                                        </div>
                                        <button
                                            type="button"
                                            @click="budgetModalOpen = false"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50"
                                        >✕</button>
                                    </div>
                                    <div class="space-y-4 px-6 py-5">
                                        <div>
                                            <label class="mb-1.5 block text-xs font-semibold text-slate-700">
                                                Direct Approval Budget Limit (PHP) <span style="color:#dc2626;">*</span>
                                            </label>
                                            <div class="relative">
                                                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm font-bold text-slate-400">₱</span>
                                                <input type="hidden" name="direct_approve_limit" :value="budgetLimitVal">
                                                <input
                                                    type="text"
                                                    inputmode="decimal"
                                                    autocomplete="off"
                                                    x-model="budgetLimitDisplay"
                                                    @input="formatBudgetInput($event.target.value)"
                                                    @blur="normalizeBudgetBlur()"
                                                    placeholder="10,000.00"
                                                    required
                                                    class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-8 pr-3 text-sm font-bold tabular-nums text-slate-900 outline-none focus:border-blue-500"
                                                >
                                            </div>
                                        </div>
                                        <div class="space-y-2 rounded-xl border border-slate-200 bg-slate-50 p-3.5 text-xs">
                                            <div class="flex items-start gap-2">
                                                <span class="mt-0.5 h-2 w-2 shrink-0 rounded-full" style="background:#10b981;"></span>
                                                <div class="text-slate-700">
                                                    <span class="font-bold text-slate-900">At or below ₱<span x-text="Number(budgetLimitVal || 0).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2})"></span>:</span>
                                                    <span><strong>Approve Directly</strong> is enabled; Forward to President is disabled.</span>
                                                </div>
                                            </div>
                                            <div class="flex items-start gap-2">
                                                <span class="mt-0.5 h-2 w-2 shrink-0 rounded-full" style="background:#0284c7;"></span>
                                                <div class="text-slate-700">
                                                    <span class="font-bold text-slate-900">Above ₱<span x-text="Number(budgetLimitVal || 0).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2})"></span>:</span>
                                                    <span><strong>Forward RIS to President</strong> is enabled; Approve Directly is disabled.</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-3.5">
                                        <button
                                            type="button"
                                            @click="budgetModalOpen = false"
                                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100"
                                        >Cancel</button>
                                        <button
                                            type="submit"
                                            class="rounded-lg px-4 py-2 text-xs font-semibold text-white transition hover:opacity-90"
                                            style="background:#0025cc;"
                                        >Save Budget Limit</button>
                                    </div>
                                </form>
                            </div>
                        </template>
                    @else
                        <span
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs font-semibold text-slate-600"
                            title="Configured by School Administrator"
                        >
                            Budget Limit: {{ \App\Support\RisApprovalBudget::formatPeso($currentBudgetLimit) }}
                        </span>
                    @endif

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

            <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center {{ $isIssuedBy ? 'lg:justify-end' : 'lg:justify-between' }}">
                @unless ($isIssuedBy)
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
                @endunless

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
