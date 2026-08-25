@php
    $lineage = $lineage ?? [];
    $steps = array_values(array_filter([
        $lineage['ris'] ?? null,
        $lineage['atp'] ?? null,
        $lineage['rfc'] ?? null,
        $lineage['rr'] ?? null,
        $lineage['liq'] ?? null,
    ]));
    $currentType = $currentType ?? null;
    $statusHint = $statusHint ?? null;
    $pipelineOrder = ['RIS', 'ATP', 'RFC', 'RR', 'LIQ'];
    $lastPresent = $steps === [] ? null : strtoupper((string) ($steps[array_key_last($steps)]['type'] ?? ''));
    $nextExpected = null;
    $readyForNext = $statusHint === 'Completed'
        || $statusHint === 'Approved'
        || $statusHint === 'Approved — ready for ATP'
        || str_starts_with((string) $statusHint, 'Approved — ready');
    if ($lastPresent && $readyForNext) {
        $idx = array_search($lastPresent, $pipelineOrder, true);
        if ($idx !== false && isset($pipelineOrder[$idx + 1])) {
            $nextExpected = $pipelineOrder[$idx + 1];
        }
    }
@endphp

@if(count($steps) > 0 || $statusHint)
    <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3" role="region" aria-label="Document pipeline">
        @if(count($steps) > 0)
            <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Document pipeline</p>
            <div class="flex flex-wrap items-center gap-1.5 text-xs">
                @foreach($steps as $index => $step)
                    @if($index > 0)
                        <span class="text-slate-300" aria-hidden="true">→</span>
                    @endif
                    @php $isCurrent = $currentType && strtoupper($step['type']) === strtoupper($currentType); @endphp
                    <a
                        href="{{ $step['url'] }}"
                        class="inline-flex items-center gap-1 rounded-lg px-2 py-1 font-medium transition {{ $isCurrent ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100' }}"
                        @if($isCurrent) aria-current="page" @endif
                        @if(!empty($step['hint'])) title="{{ $step['hint'] }}" @endif
                    >
                        <span>{{ $step['type'] }}</span>
                        <span class="opacity-80">{{ $step['label'] }}</span>
                    </a>
                @endforeach
                @if($nextExpected && strtoupper((string) $currentType) === $lastPresent)
                    <span class="text-slate-300" aria-hidden="true">→</span>
                    <span class="inline-flex items-center rounded-lg px-2 py-1 font-medium text-slate-400 ring-1 ring-dashed ring-slate-300">
                        {{ $nextExpected }} <span class="opacity-80">next</span>
                    </span>
                @endif
            </div>
        @endif
        @if($statusHint)
            <p class="mt-2 text-xs text-slate-600">
                <span class="font-semibold text-slate-700">Review stage:</span>
                {{ $statusHint }}
            </p>
        @endif
    </div>
@endif
