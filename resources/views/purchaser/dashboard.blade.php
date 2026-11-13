@extends("layouts.purchaser-layout")

@section("page-title", "Dashboard")

@section("content")
@php
    $d = $dashboard;

    $peso = fn ($amount) => '₱'.number_format((float) $amount, 2);
    $docIcons = ['ris' => 'package-open', 'atp' => 'file-check-2', 'po' => 'shopping-bag', 'rfc' => 'wallet', 'rr' => 'package-check', 'liq' => 'receipt-text'];

    $latestReplacements = collect($d['latestReplacementRequests'] ?? []);
    $delayedFlows = collect($d['delayedUrgentFlows'] ?? []);

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
            <div class="grid grid-cols-1 md:grid-cols-2 md:divide-x md:divide-gray-100">
                <div>
                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                        <h2 class="text-base font-semibold tracking-tight text-gray-950">3 latest replacement requests</h2>
                        <a href="{{ route('purchaser.procurement.replacement-requests') }}" class="shrink-0 text-xs font-semibold text-[#0025cc] hover:underline">View all</a>
                    </div>
                    @forelse($latestReplacements as $request)
                        <a href="{{ route('purchaser.procurement.replacement-requests') }}" class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-3 transition last:border-b-0 hover:bg-gray-50/80">
                            <span class="min-w-0">
                                <span class="flex items-center gap-2">
                                    <span class="truncate text-sm font-semibold text-gray-900">{{ $request->code }}</span>
                                    @if($request->urgent)
                                        <span class="shrink-0 rounded bg-amber-50 px-1.5 py-px text-[10px] font-semibold uppercase tracking-wide text-amber-700">Urgent</span>
                                    @endif
                                </span>
                                <span class="mt-0.5 block truncate text-xs text-gray-500">{{ $request->equipment }}@if($request->room !== '') · {{ $request->room }}@endif · {{ $request->status }}</span>
                            </span>
                            <span class="shrink-0 text-xs font-medium text-gray-500">{{ $request->date }}</span>
                        </a>
                    @empty
                        <p class="px-5 py-8 text-center text-xs font-medium text-gray-500">No replacement requests yet</p>
                    @endforelse
                </div>
                <div class="border-t border-gray-100 md:border-t-0">
                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                        <h2 class="text-base font-semibold tracking-tight text-gray-950">3 latest delayed urgent processes</h2>
                    </div>
                    @forelse($delayedFlows as $flow)
                        <button type="button" class="flex w-full items-center justify-between gap-3 border-b border-gray-100 px-5 py-3 text-left transition last:border-b-0 hover:bg-gray-50/80" data-dashboard-preview data-preview-kind="pipeline" data-preview-url="{{ $flow->preview_url }}" data-continue-url="{{ $flow->continue_url }}" data-continue-label="Open" data-preview-title="Delayed urgent process">
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-blue-700">{{ $flow->request_no }}</span>
                                <span class="mt-0.5 block truncate text-xs text-gray-700">{{ $flow->atp }} · {{ $flow->ris }}</span>
                                <span class="mt-0.5 block truncate text-xs text-gray-500">Last stop: {{ $flow->stage }} · {{ $flow->detail }}</span>
                            </span>
                            <span class="shrink-0 text-right text-xs font-semibold text-amber-700">{{ $flow->days === 1 ? '1 day' : $flow->days.' days' }}</span>
                        </button>
                    @empty
                        <p class="px-5 py-8 text-center text-xs font-medium text-gray-500">No delayed urgent process</p>
                    @endforelse
                </div>
            </div>
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
        <div id="purchaserDashboardPreviewStage" class="relative h-[70vh] bg-slate-100">
            <iframe id="purchaserDashboardPreviewFrame" title="Form preview" class="h-full w-full border-0 bg-white" src="about:blank"></iframe>
            <div id="purchaserDashboardPreviewSkeleton" class="absolute inset-0 z-10 hidden overflow-auto bg-slate-100 py-6">
                <div data-preview-skeleton-document>
                    @include('partials.skeleton', ['skeletonType' => 'document', 'skeletonLabel' => 'Loading form'])
                </div>
                <div data-preview-skeleton-pipeline class="hidden bg-white p-5" role="status" aria-live="polite" aria-busy="true">
                    <span class="sr-only">Loading procurement flow…</span>
                    <div aria-hidden="true">
                        <div class="prism-skel" style="width:72px;height:9px"></div>
                        <div class="prism-skel mt-2" style="width:220px;height:22px"></div>
                        <div class="mt-5 rounded-xl border border-gray-200 p-5">
                            <div class="flex items-start justify-between gap-6">
                                <div>
                                    <div class="prism-skel" style="width:48px;height:9px"></div>
                                    <div class="prism-skel mt-2" style="width:168px;height:16px"></div>
                                    <div class="prism-skel mt-2" style="width:72px;height:10px"></div>
                                </div>
                                <div class="shrink-0">
                                    <div class="prism-skel ml-auto" style="width:148px;height:10px"></div>
                                    <div class="prism-skel ml-auto mt-2" style="width:112px;height:10px"></div>
                                </div>
                            </div>
                            <div class="mt-8 flex items-start">
                                @for ($step = 0; $step < 5; $step++)
                                    <div class="flex flex-1 flex-col items-center">
                                        <div class="flex w-full items-center">
                                            <div class="h-0.5 flex-1 {{ $step === 0 ? 'bg-transparent' : 'bg-slate-200' }}"></div>
                                            <div class="prism-skel prism-skel-round shrink-0" style="width:36px;height:36px"></div>
                                            <div class="h-0.5 flex-1 {{ $step === 4 ? 'bg-transparent' : 'bg-slate-200' }}"></div>
                                        </div>
                                        <div class="prism-skel mt-2" style="width:64px;height:9px"></div>
                                        <div class="prism-skel mt-1.5" style="width:92px;height:10px"></div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>
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
        const stage = document.getElementById('purchaserDashboardPreviewStage');
        const skeleton = document.getElementById('purchaserDashboardPreviewSkeleton');
        const title = document.getElementById('purchaserDashboardPreviewTitle');
        const continueLink = document.getElementById('purchaserDashboardPreviewContinue');
        if (!modal || !frame || !skeleton || !title || !continueLink) return;
        document.body.appendChild(modal);

        var previewCache = Object.create(null);

        function showSkeleton(pipeline) {
            var pipe = skeleton.querySelector('[data-preview-skeleton-pipeline]');
            var documentSkeleton = skeleton.querySelector('[data-preview-skeleton-document]');
            if (pipe) pipe.classList.toggle('hidden', !pipeline);
            if (documentSkeleton) documentSkeleton.classList.toggle('hidden', pipeline);
            skeleton.classList.toggle('bg-white', pipeline);
            skeleton.classList.toggle('bg-slate-100', !pipeline);
            skeleton.classList.toggle('py-6', !pipeline);
            skeleton.classList.toggle('absolute', !pipeline);
            skeleton.classList.toggle('inset-0', !pipeline);
            skeleton.classList.remove('hidden');
        }

        function hideSkeleton() {
            skeleton.classList.add('hidden');
        }

        function fitPipelineFrame() {
            if (!stage || !stage.classList.contains('h-auto')) return;
            var doc;
            try { doc = frame.contentDocument; } catch (e) { return; }
            if (!doc || !doc.body) return;
            frame.style.height = '40px';
            var total = 0;
            var children = doc.body.children || [];
            for (var i = 0; i < children.length; i++) {
                total += children[i].offsetHeight;
            }
            if (total < 40) total = doc.body.scrollHeight || 0;
            if (total < 40) return;
            frame.style.height = Math.ceil(total) + 'px';
        }

        function revealPreview() {
            fitPipelineFrame();
            if (!stage || !stage.classList.contains('h-auto') || (frame.style.height && frame.style.height !== '40px' && frame.style.height !== '0px')) {
                hideSkeleton();
            }
        }

        frame.addEventListener('load', function () {
            const src = frame.getAttribute('src') || '';
            const inline = frame.srcdoc || '';
            if ((!src || src === 'about:blank') && !inline) return;
            revealPreview();
            setTimeout(revealPreview, 80);
        });

        function closePreview() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            hideSkeleton();
            if (stage) {
                stage.classList.add('h-[70vh]');
                stage.classList.remove('h-auto');
            }
            frame.classList.add('h-full');
            frame.style.height = '';
            skeleton.classList.add('absolute', 'inset-0');
            frame.srcdoc = '';
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
            var pipeline = trigger.getAttribute('data-preview-kind') === 'pipeline';
            title.textContent = trigger.getAttribute('data-preview-title') || 'Form';
            continueLink.href = continueUrl || '#';
            continueLink.textContent = trigger.getAttribute('data-continue-label') || 'Open';
            if (stage) {
                stage.classList.toggle('h-[70vh]', !pipeline);
                stage.classList.toggle('h-auto', pipeline);
            }
            frame.classList.toggle('h-full', !pipeline);
            frame.style.height = pipeline ? '0px' : '';
            var cached = pipeline ? (previewCache[previewUrl] || '') : '';
            showSkeleton(pipeline);
            if (cached) {
                frame.removeAttribute('src');
                frame.srcdoc = cached;
                requestAnimationFrame(revealPreview);
            } else {
                frame.srcdoc = '';
                frame.src = previewUrl;
            }
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

        function rememberPreview(url) {
            if (!url || Object.prototype.hasOwnProperty.call(previewCache, url)) return;
            previewCache[url] = '';
            fetch(url, { credentials: 'same-origin' })
                .then(function (response) { return response.ok ? response.text() : ''; })
                .then(function (html) { previewCache[url] = html || ''; })
                .catch(function () { delete previewCache[url]; });
        }

        var warmNodes = Array.prototype.slice.call(document.querySelectorAll('[data-preview-kind="pipeline"][data-preview-url]'));
        document.querySelectorAll('[data-preview-url]').forEach(function (el) {
            if (el.getAttribute('data-preview-kind') === 'pipeline') return;
            warmNodes.push(el);
        });
        warmNodes.slice(0, 12).forEach(function (el) {
            rememberPreview(el.getAttribute('data-preview-url'));
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) closePreview();
        });
    })();
</script>
@endsection
