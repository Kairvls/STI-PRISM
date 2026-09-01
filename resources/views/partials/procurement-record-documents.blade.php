@php
    $documentRows = $documentRows ?? [];
    $readyCount = collect($documentRows)->where('ready', true)->count();
    $totalCount = count($documentRows);
    $progress = $totalCount > 0 ? round(($readyCount / $totalCount) * 100) : 0;

    $docIcon = fn (string $key) => match ($key) {
        'supporting_docs' => 'paperclip',
        'ris' => 'clipboard-list',
        'atp' => 'file-text',
        'funding' => 'banknote',
        'rr' => 'package',
        'liq' => 'receipt',
        default => 'file',
    };

    $actionIcon = fn (string $label) => match (strtolower($label)) {
        'view' => 'eye',
        'excel' => 'table',
        'word' => 'file-text',
        default => 'download',
    };
@endphp

@include('partials.procurement-records-ui')

<div class="pr-surface">
    <div class="pr-checklist">
        <div class="pr-checklist-head">
            <div>
                <h2 class="pr-checklist-title">Document checklist</h2>
                <p class="pr-checklist-sub">Open or download each form for review and proof of transaction.</p>
            </div>
            <span class="pr-checklist-count">{{ $readyCount }} of {{ $totalCount }} complete</span>
        </div>

        <div class="pr-progress" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
            <div class="pr-progress-bar" style="width: {{ $progress }}%"></div>
        </div>

        <div class="pr-doc-list mt-3">
            @foreach($documentRows as $row)
                @php
                    $key = $row['key'] ?? '';
                    $isReady = !empty($row['ready']);
                @endphp
                <div class="pr-doc-row">
                    <div class="pr-doc-info">
                        <span class="pr-doc-icon {{ $isReady ? 'is-ready' : '' }}" aria-hidden="true">
                            <i data-lucide="{{ $docIcon($key) }}"></i>
                        </span>
                        <div class="min-w-0">
                            <span class="pr-doc-label">{{ $row['label'] }}</span>
                            @if(!empty($row['form_number']))
                                <span class="pr-doc-number">{{ $row['form_number'] }}</span>
                            @endif
                        </div>
                    </div>

                    @if(!empty($row['links']))
                        <div class="pr-doc-actions">
                            @foreach($row['links'] as $link)
                                @php $isView = strtolower($link['label']) === 'view'; @endphp
                                <a
                                    href="{{ $link['url'] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="pr-doc-btn {{ $isView ? 'pr-doc-btn--primary' : '' }}"
                                >
                                    <i data-lucide="{{ $actionIcon($link['label']) }}"></i>
                                    {{ $link['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @else
                        <span class="pr-doc-empty">Not available</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
