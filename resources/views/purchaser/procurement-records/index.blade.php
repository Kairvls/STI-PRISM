@extends($procurementLayout ?? 'layouts.purchaser-layout')

@section('page-title', 'Compiled Records')
@section('page-subtitle', 'Submit completed workflow document checklists to Accounting.')

@section('content')
@php
    use App\Support\ProcurementPaymentPath;
    use App\Support\ProcurementRecordCompiler;

    $compileRows = [];
    foreach ($eligibleAtps as $atp) {
        $atpId = (int) $atp->authority_purchase_id;
        $alreadySubmitted = in_array($atpId, $existingAtpIds ?? [], true);
        if ($alreadySubmitted) {
            continue; // submitted packages live under Submitted logs
        }

        $checklist = $checklists[$atpId] ?? [];
        $items = $checklist['items'] ?? [];
        $complete = (bool) ($checklist['complete'] ?? false);
        $readyCount = collect($items)->where('ready', true)->count();
        $totalCount = count($items);
        $compileRef = trim((string) ($atp->authority_purchase_request_no ?? $atp->ris_request_no ?? ''));
        if ($compileRef === '') {
            $compileRef = preg_replace('/^ATP\b/i', 'PR', (string) ($atp->authority_purchase_form_number ?? '—')) ?: '—';
        }
        $pathLabel = ProcurementPaymentPath::label($atp->authority_purchase_payment_path);
        $risNo = (string) ($atp->ris_form_number ?? '');
        $statusKey = $complete ? 'completed' : 'incomplete';
        $compileRows[] = [
            'atp' => $atp,
            'atpId' => $atpId,
            'items' => $items,
            'complete' => $complete,
            'readyCount' => $readyCount,
            'totalCount' => $totalCount,
            'progress' => $totalCount > 0 ? round(($readyCount / $totalCount) * 100) : 0,
            'compileRef' => $compileRef,
            'pathLabel' => $pathLabel,
            'risNo' => $risNo,
            'statusKey' => $statusKey,
            'searchText' => strtolower(trim($compileRef.' '.$risNo.' '.$pathLabel.' '.(string) ($atp->authority_purchase_form_number ?? ''))),
            'openByDefault' => $complete || ((int) ($viewAtpId ?? 0) === $atpId),
        ];
    }

    $logRows = [];
    foreach ($packages as $pkg) {
        $pkgRef = trim((string) ($pkg->authority_purchase_request_no ?? $pkg->ris_request_no ?? ''));
        if ($pkgRef === '') {
            $pkgRef = preg_replace('/^ATP\b/i', 'PR', (string) ($pkg->authority_purchase_form_number ?? '—')) ?: '—';
        }
        $status = str_replace('_', ' ', ucfirst((string) $pkg->package_status));
        $pathLabel = ProcurementPaymentPath::label($pkg->package_payment_path);
        $risNo = (string) ($pkg->ris_form_number ?? '');
        $logRows[] = [
            'pkg' => $pkg,
            'pkgRef' => $pkgRef,
            'status' => $status,
            'statusClass' => match ($pkg->package_status) {
                'submitted_to_accounting' => 'pr-badge--amber',
                'forwarded_to_president' => 'pr-badge--green',
                default => '',
            },
            'pathLabel' => $pathLabel,
            'risNo' => $risNo,
            'submittedAt' => optional(\Carbon\Carbon::parse($pkg->package_submitted_to_accounting_at))->format('M d, Y') ?? '—',
            'searchText' => strtolower(trim($pkgRef.' '.$risNo.' '.$pathLabel.' '.$status.' '.(string) ($pkg->authority_purchase_form_number ?? ''))),
        ];
    }

    $counts = [
        'incomplete' => collect($compileRows)->where('statusKey', 'incomplete')->count(),
        'completed' => collect($compileRows)->where('statusKey', 'completed')->count(),
        'submitted' => count($logRows),
    ];
    $counts['all'] = $counts['incomplete'] + $counts['completed'] + $counts['submitted'];

    $defaultFilter = ! empty($viewAtpId)
        ? 'all'
        : ($counts['completed'] > 0 ? 'completed' : ($counts['incomplete'] > 0 ? 'incomplete' : ($counts['submitted'] > 0 ? 'submitted' : 'all')));
@endphp

@include('partials.procurement-records-ui')

<div
    class="pr-module space-y-5"
    x-data="{
        q: '',
        filter: @js($defaultFilter),
        compileMeta: @js(collect($compileRows)->map(fn ($r) => ['status' => $r['statusKey'], 'q' => $r['searchText']])->values()),
        logMeta: @js(collect($logRows)->map(fn ($r) => ['q' => $r['searchText']])->values()),
        needle() {
            return (this.q || '').trim().toLowerCase();
        },
        matchText(text) {
            const n = this.needle();
            if (!n) return true;
            return (text || '').includes(n);
        },
        showCompile(statusKey) {
            if (this.filter === 'submitted') return false;
            if (this.filter === 'all') return true;
            return statusKey === this.filter;
        },
        showLogs() {
            return this.filter === 'submitted' || this.filter === 'all';
        },
        hasVisible() {
            const compileHit = this.compileMeta.some((row) => this.showCompile(row.status) && this.matchText(row.q));
            if (compileHit) return true;
            if (!this.showLogs()) return false;
            return this.logMeta.some((row) => this.matchText(row.q));
        }
    }"
>
    <div class="pr-hero">
        <p class="pr-hero-kicker">Purchasing Workflow</p>
        <h1 class="pr-hero-title">Compiled Records</h1>
    </div>

    <section class="pr-section">
        <div class="pr-surface h-full">
            <div class="pr-section-head">
                <h2 class="pr-section-title">Ready to compile</h2>
                <p class="pr-section-sub">Filter incomplete workflows, completed checklists, and submitted logs.</p>
            </div>

            <div class="pr-toolbar">
                <label class="pr-search">
                    <i data-lucide="search"></i>
                    <input
                        type="search"
                        x-model.debounce.150ms="q"
                        placeholder="Search PR, RIS, payment path…"
                        autocomplete="off"
                    >
                </label>
                <div class="pr-filter-pills" role="tablist" aria-label="Compile status filter">
                    <button type="button" class="pr-filter-pill" :class="filter === 'all' && 'is-active'" @click="filter = 'all'">
                        All
                        <span class="pr-filter-count">{{ $counts['all'] }}</span>
                    </button>
                    <button type="button" class="pr-filter-pill" :class="filter === 'incomplete' && 'is-active'" @click="filter = 'incomplete'">
                        Incomplete
                        <span class="pr-filter-count">{{ $counts['incomplete'] }}</span>
                    </button>
                    <button type="button" class="pr-filter-pill" :class="filter === 'completed' && 'is-active'" @click="filter = 'completed'">
                        Completed
                        <span class="pr-filter-count">{{ $counts['completed'] }}</span>
                    </button>
                    <button type="button" class="pr-filter-pill" :class="filter === 'submitted' && 'is-active'" @click="filter = 'submitted'">
                        Submitted logs
                        <span class="pr-filter-count">{{ $counts['submitted'] }}</span>
                    </button>
                </div>
            </div>

            @if(count($compileRows) || count($logRows))
                <div class="pr-compile-list">
                    @foreach($compileRows as $row)
                        @php
                            $atpId = $row['atpId'];
                            $items = $row['items'];
                        @endphp
                        <div
                            x-show="showCompile(@js($row['statusKey'])) && matchText(@js($row['searchText']))"
                            x-cloak
                        >
                            <div
                                class="pr-compile-card"
                                id="atp-record-{{ $atpId }}"
                                x-data="{ open: {{ $row['openByDefault'] ? 'true' : 'false' }} }"
                            >
                                <div class="pr-compile-row">
                                    <button
                                        type="button"
                                        class="pr-compile-toggle"
                                        @click="open = !open"
                                        :aria-expanded="open.toString()"
                                        aria-controls="compile-body-{{ $atpId }}"
                                    >
                                        <i data-lucide="chevron-down" class="pr-compile-chevron" :class="open ? 'is-open' : ''"></i>
                                        <span class="pr-compile-toggle-main">
                                            <span class="pr-compile-ref">{{ $row['compileRef'] }}</span>
                                            <span class="pr-compile-meta">
                                                RIS {{ $row['risNo'] !== '' ? $row['risNo'] : '—' }}
                                                · {{ $row['pathLabel'] }}
                                            </span>
                                        </span>
                                        <span class="pr-compile-toggle-aside">
                                            <span class="pr-compile-mini-progress" role="progressbar" aria-valuenow="{{ $row['progress'] }}" aria-valuemin="0" aria-valuemax="100">
                                                <span class="pr-progress-bar" style="width: {{ $row['progress'] }}%"></span>
                                            </span>
                                            <span class="pr-checklist-count">{{ $row['readyCount'] }}/{{ $row['totalCount'] }}</span>
                                        </span>
                                    </button>

                                    <div class="pr-compile-actions" @click.stop>
                                        @if($row['complete'])
                                            <form method="POST" action="{{ route(($pp ?? 'purchaser').'.procurement-records.store') }}" onsubmit="return confirm('Submit compiled record to Accounting?')">
                                                @csrf
                                                <input type="hidden" name="package_authority_purchase_id" value="{{ $atpId }}">
                                                <button type="submit" class="pr-submit-btn">
                                                    <i data-lucide="send"></i>
                                                    Submit
                                                </button>
                                            </form>
                                        @else
                                            <span class="pr-badge pr-badge--amber">Incomplete</span>
                                        @endif
                                    </div>
                                </div>

                                <div
                                    id="compile-body-{{ $atpId }}"
                                    class="pr-compile-body"
                                    x-show="open"
                                    x-cloak
                                >
                                    <div class="pr-inline-checklist">
                                        @foreach($items as $item)
                                            @php
                                                $itemKey = $item['key'] ?? '';
                                                $itemReady = ! empty($item['ready']);
                                                $valueId = $itemReady ? trim((string) ($item['value_id'] ?? '')) : '';
                                                $url = ProcurementRecordCompiler::purchaserUrlForItem($item, $atpId);
                                                $supportingLinks = $itemKey === 'supporting_docs'
                                                    ? ProcurementRecordCompiler::supportingDocLinksForAtp($atpId)
                                                    : [];
                                            @endphp
                                            @if($itemKey === 'supporting_docs' && $supportingLinks !== [])
                                                <div class="pr-inline-check-block">
                                                    <span class="pr-inline-check {{ $itemReady ? 'is-ready' : '' }}">
                                                        <span class="pr-check-dot" aria-hidden="true">
                                                            <i data-lucide="{{ $itemReady ? 'check' : 'minus' }}"></i>
                                                        </span>
                                                        <span class="pr-inline-check-text">
                                                            <span class="pr-inline-check-label">{{ $item['label'] }}</span>
                                                        </span>
                                                    </span>
                                                    <div class="pr-inline-check-files">
                                                        @foreach($supportingLinks as $link)
                                                            <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" class="pr-doc-btn">
                                                                <i data-lucide="paperclip"></i>
                                                                {{ $link['label'] }}
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @elseif($url)
                                                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="pr-inline-check {{ $itemReady ? 'is-ready' : '' }}" title="Open form in new tab">
                                                    <span class="pr-check-dot" aria-hidden="true">
                                                        <i data-lucide="{{ $itemReady ? 'check' : 'minus' }}"></i>
                                                    </span>
                                                    <span class="pr-inline-check-text">
                                                        <span class="pr-inline-check-label">{{ $item['label'] }}</span>
                                                        @if($valueId !== '')
                                                            <span class="pr-inline-check-id">{{ $valueId }}</span>
                                                        @endif
                                                    </span>
                                                </a>
                                            @else
                                                <span class="pr-inline-check {{ $itemReady ? 'is-ready' : '' }}">
                                                    <span class="pr-check-dot" aria-hidden="true">
                                                        <i data-lucide="{{ $itemReady ? 'check' : 'minus' }}"></i>
                                                    </span>
                                                    <span class="pr-inline-check-text">
                                                        <span class="pr-inline-check-label">{{ $item['label'] }}</span>
                                                        @if($valueId !== '')
                                                            <span class="pr-inline-check-id">{{ $valueId }}</span>
                                                        @endif
                                                    </span>
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @foreach($logRows as $log)
                        <div
                            class="pr-list-row pr-list-row--static pr-log-row"
                            x-show="showLogs() && matchText(@js($log['searchText']))"
                            x-cloak
                        >
                            <div class="pr-list-main">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="pr-list-ref">{{ $log['pkgRef'] }}</span>
                                    <span class="pr-badge {{ $log['statusClass'] }}">{{ $log['status'] }}</span>
                                </div>
                                <div class="pr-list-meta">
                                    <span>
                                        <i data-lucide="clipboard-list" class="h-3 w-3"></i>
                                        RIS {{ $log['risNo'] !== '' ? $log['risNo'] : '—' }}
                                    </span>
                                    <span>
                                        <i data-lucide="git-branch" class="h-3 w-3"></i>
                                        {{ $log['pathLabel'] }}
                                    </span>
                                    <span>
                                        <i data-lucide="calendar" class="h-3 w-3"></i>
                                        {{ $log['submittedAt'] }}
                                    </span>
                                </div>
                            </div>
                            <span class="pr-log-tag">Log</span>
                        </div>
                    @endforeach
                </div>

                <div class="pr-empty pr-empty--inline" x-show="!hasVisible()" x-cloak>
                    <div class="pr-empty-icon">
                        <i data-lucide="search-x"></i>
                    </div>
                    <p class="pr-empty-title">No matching records</p>
                    <p class="pr-empty-sub">Try another search or filter.</p>
                </div>
            @else
                <div class="pr-empty">
                    <div class="pr-empty-icon">
                        <i data-lucide="folder-open"></i>
                    </div>
                    <p class="pr-empty-title">No PRs ready yet</p>
                    <p class="pr-empty-sub">Approved PRs with a payment path will appear here.</p>
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
