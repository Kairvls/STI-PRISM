@extends("layouts.purchaser-layout")

@section("page-title", "Dashboard")

@section("content")
@php
    use App\Support\PurchaserAttentionSummary as Focus;

    $d = $dashboard;
    $attention = $d['attention'];

    $peso = fn ($amount) => '₱'.number_format((float) $amount, 2);
    $docIcons = ['ris' => 'package-open', 'atp' => 'file-check-2', 'po' => 'shopping-bag', 'rfc' => 'wallet', 'rr' => 'package-check', 'liq' => 'receipt-text'];

    $nextSteps = collect([
        ['count' => $attention['pendingReplacementRequests'], 'label' => 'Replacement requests to review', 'hint' => 'Sent by Maintenance — approve or reject', 'icon' => 'inbox', 'scope' => 'Shared', 'url' => route('purchaser.procurement.replacement-requests', ['focus' => Focus::FOCUS_REPLACEMENT_PENDING])],
        ['count' => $attention['availableUrgentReports'], 'label' => 'Unclaimed urgent reports', 'hint' => 'Nobody has taken these yet', 'icon' => 'siren', 'scope' => 'Shared', 'url' => route('purchaser.reports.urgent', ['focus' => Focus::FOCUS_URGENT_UNCLAIMED])],
        ['count' => $d['handovers']['total'], 'label' => 'Drafts passed to you', 'hint' => 'A co-worker handed over a draft', 'icon' => 'hand-helping', 'scope' => 'Yours', 'url' => route($d['handovers']['atp'] > 0 ? 'purchaser.atp.index' : ($d['handovers']['po'] > 0 ? 'purchaser.purchase-orders.index' : 'purchaser.ris.index'))],
        ['count' => $attention['risReadyForAtp'], 'label' => 'Approved RIS ready for ATP', 'hint' => 'Create the Authority to Purchase', 'icon' => 'file-plus-2', 'scope' => 'Yours', 'url' => route('purchaser.ris.index', ['focus' => Focus::FOCUS_RIS_READY_FOR_ATP])],
        ['count' => $attention['atpReadyForRfc'], 'label' => 'Approved ATP ready for funding', 'hint' => 'Request for Check or Cash Advance', 'icon' => 'wallet', 'scope' => 'Yours', 'url' => route('purchaser.atp.index', ['focus' => Focus::FOCUS_ATP_READY_FOR_RFC])],
        ['count' => $attention['rfcReadyForRr'], 'label' => 'Funds released — create Receiving Report', 'hint' => 'Collect funds, buy, then record delivery', 'icon' => 'banknote', 'scope' => 'Yours', 'url' => route('purchaser.rfc.index', ['focus' => Focus::FOCUS_RFC_READY_FOR_RR])],
        ['count' => $d['openBackOrders'], 'label' => 'Open back orders', 'hint' => 'Short or damaged lines to replace or refund', 'icon' => 'package-x', 'scope' => 'Yours', 'url' => route('purchaser.bo.index')],
        ['count' => $attention['rrReadyForLiq'], 'label' => 'Receiving Reports ready for liquidation', 'hint' => 'Liquidate your Cash Advance', 'icon' => 'receipt-text', 'scope' => 'Yours', 'url' => route('purchaser.rr.index', ['focus' => Focus::FOCUS_RR_READY_FOR_LIQ])],
    ])->sortBy(fn ($step) => $step['count'] > 0 ? 0 : 1)->values();

    $recentRows = collect($d['recent'] ?? [])->take(5);
    $recentTotal = (int) ($d['recentTotal'] ?? $recentRows->count());
    $showing = function (int $total, int $shown): string {
        if ($shown <= 0) {
            return '';
        }

        return $total > $shown
            ? $shown.' latest'
            : 'Showing '.$shown.' of '.$total;
    };
    $previewFor = function (object $doc): string {
        $type = (string) ($doc->type ?? '');
        $id = (int) ($doc->id ?? 0);
        if ($id <= 0 || ! in_array($type, ['ris', 'atp', 'rfc', 'rr', 'liq'], true)) {
            return '';
        }

        return route('purchaser.dashboard.preview', ['type' => $type, 'id' => $id]);
    };
@endphp

<div class="space-y-6">

    {{-- ===================================================== --}}
    {{-- NEXT STEPS + RETURNED --}}
    {{-- ===================================================== --}}

    <section class="grid gap-6 xl:grid-cols-3">
        <div id="next-steps" class="pur-card scroll-mt-24 xl:col-span-2">
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <div>
                    <h2 class="text-base font-semibold tracking-tight text-gray-950">Next steps</h2>
                </div>
                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">{{ $nextSteps->sum('count') }} open</span>
            </div>

            <div class="grid grid-cols-1 divide-y divide-gray-100 md:grid-cols-2 md:divide-y-0">
                @foreach($nextSteps as $i => $step)
                    @php $active = $step['count'] > 0; @endphp
                    <a
                        href="{{ $step['url'] }}"
                        class="group flex items-center gap-3.5 px-5 py-3.5 transition hover:bg-gray-50/80 md:border-b md:border-gray-100 {{ $i % 2 === 0 ? 'md:border-r' : '' }} {{ $active ? '' : 'opacity-60' }}"
                    >
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1 {{ $active ? 'bg-blue-50 text-blue-700 ring-blue-100' : 'bg-slate-50 text-slate-400 ring-slate-100' }}">
                            <i data-lucide="{{ $step['icon'] }}" class="h-[18px] w-[18px]"></i>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold leading-snug text-gray-900">
                                {{ $step['label'] }}
                                @if($step['scope'] === 'Shared')
                                    <span class="ml-1 inline-block rounded bg-amber-50 px-1.5 py-px align-middle text-[10px] font-semibold uppercase tracking-wide text-amber-700">Shared</span>
                                @endif
                            </span>
                            <span class="mt-0.5 block text-xs leading-snug text-gray-500">{{ $step['hint'] }}</span>
                        </span>
                        @if($active)
                            <span class="flex h-7 min-w-[28px] items-center justify-center rounded-full bg-[#0025cc] px-2 text-xs font-bold text-white">{{ $step['count'] }}</span>
                        @else
                            <i data-lucide="check" class="h-4 w-4 text-slate-400"></i>
                        @endif
                    </a>
                @endforeach
            </div>

            @php $rc = $d['replacementCounts']; @endphp
            <a
                href="{{ route('purchaser.procurement.replacement-requests') }}"
                class="flex flex-wrap items-center justify-between gap-3 bg-gray-50/70 px-5 py-3 text-xs text-gray-500 transition hover:bg-gray-100/70"
            >
                <span class="font-semibold text-gray-700">Replacement requests from Maintenance</span>
                <span class="flex items-center gap-4">
                    <span><b class="text-gray-900">{{ $rc['pending'] }}</b> pending</span>
                    <span><b class="text-gray-900">{{ $rc['approved'] }}</b> approved</span>
                    <span><b class="text-gray-900">{{ $rc['completed'] }}</b> completed</span>
                    <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                </span>
            </a>
        </div>

        @php
            $boReports = $d['backOrderReports'];
            $boRrIds = $boReports->map(fn ($row) => (int) $row->doc->id)->all();
            $returnedDocs = $d['needsAction']->reject(fn ($doc) => $doc->type === 'rr' && in_array((int) $doc->id, $boRrIds, true))->values();
            $panelCount = $returnedDocs->count() + $boReports->count();
        @endphp
        <div class="pur-card flex flex-col">
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <div>
                    <h2 class="text-base font-semibold tracking-tight text-gray-950">Returned to you</h2>
                </div>
                @if($panelCount > 0)
                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700">{{ $panelCount }}</span>
                @endif
            </div>

            @if($panelCount === 0)
                <div class="flex flex-1 flex-col items-center justify-center px-6 py-10 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 text-blue-700">
                        <i data-lucide="party-popper" class="h-5 w-5"></i>
                    </span>
                    <p class="mt-3 text-sm font-semibold text-gray-800">Nothing returned</p>
                    <p class="mt-1 text-xs text-gray-500">Documents sent back for revision and receiving reports with back orders will show up here.</p>
                </div>
            @else
                <div class="flex flex-1 flex-col">
                    @if($returnedDocs->isNotEmpty())
                        <p class="px-5 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400">For revision · {{ $returnedDocs->count() }}</p>
                        @foreach($returnedDocs as $doc)
                            <button type="button" class="group flex w-full items-center gap-3 px-5 py-2.5 text-left transition hover:bg-gray-50/80" data-dashboard-preview data-preview-url="{{ $previewFor($doc) }}" data-continue-url="{{ route('purchaser.'.$doc->route, $doc->params) }}" data-continue-label="Open" data-preview-title="{{ $doc->number }}">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700">
                                    <i data-lucide="{{ $docIcons[$doc->type] ?? 'file' }}" class="h-4 w-4"></i>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-gray-900">{{ $doc->number }}</span>
                                    <span class="block truncate text-xs text-gray-500">{{ $doc->type_label }} · {{ $doc->status }}</span>
                                </span>
                                <i data-lucide="chevron-right" class="h-4 w-4 text-gray-300 transition group-hover:translate-x-0.5 group-hover:text-gray-500"></i>
                            </button>
                        @endforeach
                    @endif

                    @if($boReports->isNotEmpty())
                        <div class="flex items-center justify-between px-5 pb-2 pt-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-400">RR with back orders · {{ $boReports->count() }}</p>
                            <a href="{{ route('purchaser.bo.index') }}" class="text-xs font-semibold text-[#0025cc] hover:underline">All</a>
                        </div>
                        <div class="pb-2">
                            @foreach($boReports->take(4) as $row)
                                @php
                                    $boItems = implode(', ', array_slice($row->articles, 0, 2)).(count($row->articles) > 2 ? ' +'.(count($row->articles) - 2) : '');
                                @endphp
                                <button type="button" class="group flex w-full items-center gap-3 px-5 py-2.5 text-left transition hover:bg-gray-50/80" data-dashboard-preview data-preview-url="{{ $previewFor($row->doc) }}" data-continue-url="{{ route('purchaser.bo.index', ['rr' => $row->doc->id]) }}" data-continue-label="Open" data-preview-title="{{ $row->doc->number }}">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700">
                                        <i data-lucide="{{ $docIcons['rr'] }}" class="h-4 w-4"></i>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-gray-900">{{ $row->doc->number }}</span>
                                        <span class="block truncate text-xs text-gray-500">{{ $row->doc->type_label }} · {{ $row->doc->status }}</span>
                                        <span class="mt-0.5 block truncate text-xs text-gray-500">
                                            @if($row->missing > 0)<span class="font-medium text-amber-700">{{ $row->missing }} missing</span>@endif
                                            @if($row->missing > 0 && $row->damaged > 0)<span class="text-gray-300"> · </span>@endif
                                            @if($row->damaged > 0)<span class="font-medium text-amber-700">{{ $row->damaged }} damaged</span>@endif
                                            <span class="text-gray-300"> · </span>{{ $peso($row->amount) }}@if($boItems !== '')<span class="text-gray-300"> · </span>{{ $boItems }}@endif
                                        </span>
                                    </span>
                                    <i data-lucide="chevron-right" class="h-4 w-4 text-gray-300 transition group-hover:translate-x-0.5 group-hover:text-gray-500"></i>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </section>

    <section class="pur-card">
        <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
            <div class="flex min-w-0 items-center gap-2">
                <h2 class="text-base font-semibold tracking-tight text-gray-950">Recent documents</h2>
                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">{{ number_format($recentTotal) }}</span>
            </div>
            <a href="{{ route('purchaser.history.index', ['tab' => 'documents']) }}" class="shrink-0 text-xs font-semibold text-[#0025cc] hover:underline">View all</a>
        </div>
        @forelse($recentRows as $doc)
            <button type="button" class="flex w-full items-center justify-between gap-3 border-b border-gray-100 px-5 py-3 text-left transition last:border-b-0 hover:bg-gray-50/80" data-dashboard-preview data-preview-url="{{ $previewFor($doc) }}" data-continue-url="{{ route('purchaser.'.$doc->route, $doc->params) }}" data-continue-label="Open" data-preview-title="{{ $doc->number }}">
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold text-gray-900">{{ $doc->number }}</span>
                    <span class="block truncate text-xs text-gray-500">{{ $doc->type_label }} · {{ $doc->status ?: '—' }}</span>
                </span>
                @if($doc->amount !== null)
                    <span class="shrink-0 text-sm font-semibold tabular-nums text-gray-900">{{ $peso($doc->amount) }}</span>
                @endif
            </button>
        @empty
            <p class="px-5 py-8 text-center text-sm text-gray-500">None</p>
        @endforelse
        @if($recentRows->isNotEmpty())
            <p class="px-5 py-3 text-xs text-gray-500">{{ $showing($recentTotal, $recentRows->count()) }}</p>
        @endif
    </section>

</div>

<div id="purchaserDashboardPreview" class="fixed inset-0 z-[12000] hidden items-start justify-center overflow-y-auto bg-black/50 p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="purchaserDashboardPreviewTitle">
    <div class="my-4 flex w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
            <h2 id="purchaserDashboardPreviewTitle" class="truncate text-sm font-semibold text-slate-950">Form</h2>
            <button type="button" data-preview-close class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
        <div class="relative h-[70vh] bg-slate-100">
            <iframe id="purchaserDashboardPreviewFrame" title="Form preview" class="h-full w-full border-0 bg-slate-100" src="about:blank"></iframe>
            <div id="purchaserDashboardPreviewSkeleton" class="absolute inset-0 z-10 hidden overflow-auto bg-slate-100 py-6">
                @include('partials.skeleton', ['skeletonType' => 'document', 'skeletonLabel' => 'Loading form'])
            </div>
        </div>
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-4 py-3">
            <button type="button" data-preview-close class="text-sm font-medium text-slate-500 hover:text-slate-900">Close</button>
            <a id="purchaserDashboardPreviewContinue" href="#" class="inline-flex h-9 items-center rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001db3]">Open</a>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('purchaserDashboardPreview');
        const frame = document.getElementById('purchaserDashboardPreviewFrame');
        const skeleton = document.getElementById('purchaserDashboardPreviewSkeleton');
        const title = document.getElementById('purchaserDashboardPreviewTitle');
        const continueLink = document.getElementById('purchaserDashboardPreviewContinue');
        if (!modal || !frame || !skeleton || !title || !continueLink) return;
        document.body.appendChild(modal);

        function showSkeleton() {
            skeleton.classList.remove('hidden');
        }

        function hideSkeleton() {
            skeleton.classList.add('hidden');
        }

        frame.addEventListener('load', function () {
            const src = frame.getAttribute('src') || '';
            if (!src || src === 'about:blank') return;
            hideSkeleton();
        });

        function closePreview() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            hideSkeleton();
            frame.src = 'about:blank';
            document.body.style.overflow = '';
        }

        function openPreview(trigger) {
            const previewUrl = trigger.getAttribute('data-preview-url') || '';
            const continueUrl = trigger.getAttribute('data-continue-url') || '';
            if (!previewUrl) {
                if (continueUrl) window.location = continueUrl;
                return;
            }
            title.textContent = trigger.getAttribute('data-preview-title') || 'Form';
            continueLink.href = continueUrl || '#';
            continueLink.textContent = trigger.getAttribute('data-continue-label') || 'Open';
            showSkeleton();
            const joiner = previewUrl.indexOf('?') === -1 ? '?' : '&';
            frame.src = previewUrl + joiner + 'ts=' + Date.now();
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-dashboard-preview]');
            if (!trigger) return;
            const inner = event.target.closest('a, button');
            if (inner && inner !== trigger) return;
            event.preventDefault();
            openPreview(trigger);
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal || event.target.closest('[data-preview-close]')) closePreview();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) closePreview();
        });
    })();
</script>
@endsection
