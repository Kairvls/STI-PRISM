@php
    $lineage = $lineage ?? [];
    $currentType = $currentType ?? null;
    $statusHint = $statusHint ?? null;

    $pipelineStages = [
        ['key' => 'ris', 'type' => 'RIS', 'display' => 'RIS'],
        ['key' => 'atp', 'type' => 'ATP', 'display' => 'ATP'],
        ['key' => 'rfc', 'type' => 'RFC', 'display' => 'RFC'],
        ['key' => 'rr', 'type' => 'RR', 'display' => 'RR'],
        ['key' => 'liq', 'type' => 'LIQ', 'display' => 'LR'],
    ];

    $hasAnyStep = false;
    foreach ($pipelineStages as $stage) {
        if (! empty($lineage[$stage['key']] ?? null)) {
            $hasAnyStep = true;
            break;
        }
    }
@endphp

@if($hasAnyStep || $statusHint)
    <div class="w-full border-b border-slate-200 bg-slate-50" role="region" aria-label="Document pipeline">
        <div class="w-full px-6 py-3">
            @if($hasAnyStep)
                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Document pipeline</p>
                <div class="flex w-full items-stretch gap-1 text-xs sm:gap-1.5">
                    @foreach($pipelineStages as $index => $stage)
                        @if($index > 0)
                            <span class="flex shrink-0 items-center text-slate-300" aria-hidden="true">→</span>
                        @endif

                        @php
                            $step = $lineage[$stage['key']] ?? null;
                            $isCurrent = $currentType && strtoupper((string) $currentType) === $stage['type'];
                        @endphp

                        @if($step)
                            <a
                                href="{{ $step['url'] }}"
                                class="inline-flex min-w-0 flex-1 items-center justify-center gap-1 truncate rounded-lg px-2 py-1.5 text-center font-medium transition {{ $isCurrent ? 'bg-[#0025cc] text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100' }}"
                                @if($isCurrent) aria-current="page" @endif
                                @if(!empty($step['hint'])) title="{{ $step['hint'] }}" @endif
                                title="{{ $stage['display'] }} {{ $step['label'] }}"
                            >
                                <span class="shrink-0">{{ $stage['display'] }}</span>
                                <span class="min-w-0 truncate opacity-80">{{ $step['label'] }}</span>
                            </a>
                        @else
                            <span
                                class="inline-flex min-w-0 flex-1 cursor-not-allowed items-center justify-center gap-1 truncate rounded-lg px-2 py-1.5 text-center font-medium text-slate-400 ring-1 ring-dashed ring-slate-300"
                                title="{{ $stage['display'] }} not created yet"
                                aria-disabled="true"
                            >
                                <span class="shrink-0">{{ $stage['display'] }}</span>
                                <span class="opacity-80">pending</span>
                            </span>
                        @endif
                    @endforeach
                </div>
            @endif
            @if($statusHint)
                <p class="mt-2 text-xs text-slate-600">
                    <span class="font-semibold text-slate-700">Review stage:</span>
                    {{ $statusHint }}
                </p>
            @endif
        </div>
    </div>
@endif
