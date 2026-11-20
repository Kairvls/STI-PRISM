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

    $pipelineLabel = static function (?string $label): string {
        $label = trim((string) $label);
        if ($label === '' || preg_match('/^(RIS|ATP|RFC|RR|LR|LIQ|CA|DV)\s*#\d+$/i', $label)) {
            return '-';
        }

        return $label;
    };
@endphp

@if($hasAnyStep || $statusHint)
    <div class="w-full border-b border-slate-200 bg-slate-50" role="region" aria-label="Document pipeline">
        <div class="w-full px-6 py-3">
            @if($hasAnyStep)
                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Document pipeline</p>
                <div class="flex w-full items-end gap-1 text-xs sm:gap-1.5">
                    @foreach($pipelineStages as $index => $stage)
                        @if($index > 0)
                            <span class="mb-2 flex shrink-0 items-center text-slate-300" aria-hidden="true">→</span>
                        @endif

                        @php
                            $step = $lineage[$stage['key']] ?? null;
                            $isCurrent = $currentType && strtoupper((string) $currentType) === $stage['type'];
                            $isStart = $index === 0;
                            $isFinish = $index === count($pipelineStages) - 1;
                            $stepLabel = $step ? $pipelineLabel($step['label'] ?? null) : '-';
                            $stepTitle = $stage['display'].' '.$stepLabel;
                            if ($step && ! empty($step['hint'])) {
                                $stepTitle = $step['hint'];
                            }
                        @endphp

                        <div class="flex min-w-0 flex-1 flex-col items-center gap-1">
                            <span class="text-[10px] font-bold uppercase tracking-wide {{ $isStart ? 'text-emerald-700' : ($isFinish ? 'text-slate-500' : 'invisible') }}">
                                {{ $isStart ? 'Start' : ($isFinish ? 'Finish' : 'Step') }}
                            </span>
                        @if(!empty($switchable) && $step)
                            <button
                                type="button"
                                data-switch-doc="{{ $stage['key'] }}"
                                class="inline-flex w-full min-w-0 items-center justify-center gap-1 truncate rounded-lg px-2 py-1.5 text-center font-medium ring-1 ring-slate-200 transition hover:bg-slate-100 {{ $isCurrent ? 'bg-[#0025cc] text-white ring-[#0025cc]' : 'bg-white text-slate-700' }}"
                                @if($isCurrent) aria-current="page" @endif
                                title="Show {{ $stage['display'] }}"
                            >
                                <span class="shrink-0">{{ $stage['display'] }}</span>
                                <span class="min-w-0 truncate opacity-80">{{ $stepLabel }}</span>
                            </button>
                        @elseif($step && $isCurrent)
                            <span
                                class="inline-flex w-full min-w-0 cursor-default items-center justify-center gap-1 truncate rounded-lg bg-[#0025cc] px-2 py-1.5 text-center font-medium text-white"
                                aria-current="page"
                                title="{{ $stepTitle }}"
                            >
                                <span class="shrink-0">{{ $stage['display'] }}</span>
                                <span class="min-w-0 truncate opacity-80">{{ $stepLabel }}</span>
                            </span>
                        @elseif($step)
                            <a
                                href="{{ $step['url'] }}"
                                class="inline-flex w-full min-w-0 items-center justify-center gap-1 truncate rounded-lg bg-white px-2 py-1.5 text-center font-medium text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-100"
                                title="Open {{ $stepTitle }}"
                            >
                                <span class="shrink-0">{{ $stage['display'] }}</span>
                                <span class="min-w-0 truncate opacity-80">{{ $stepLabel }}</span>
                            </a>
                        @else
                            <span
                                class="inline-flex w-full min-w-0 cursor-default items-center justify-center gap-1 truncate rounded-lg px-2 py-1.5 text-center font-medium text-slate-400 ring-1 ring-dashed ring-slate-300"
                                title="{{ $stage['display'] }} not created yet"
                                aria-disabled="true"
                            >
                                <span class="shrink-0">{{ $stage['display'] }}</span>
                                <span class="opacity-80">-</span>
                            </span>
                        @endif
                        </div>
                    @endforeach
                </div>
                @if(!empty($lineage['atp_siblings']))
                    <p class="mt-2 flex flex-wrap items-center gap-1.5 text-xs text-slate-600">
                        <span class="font-semibold text-slate-700">Other ATPs for this RIS:</span>
                        @foreach($lineage['atp_siblings'] as $sibling)
                            @php $siblingLabel = $pipelineLabel($sibling['label'] ?? null); @endphp
                            <a
                                href="{{ $sibling['url'] }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="rounded-md bg-white px-1.5 py-0.5 font-medium text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100"
                                title="{{ !empty($sibling['hint']) ? $sibling['hint'] : $siblingLabel }} (opens in new tab)"
                            >{{ $siblingLabel }}</a>
                        @endforeach
                    </p>
                @endif
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
