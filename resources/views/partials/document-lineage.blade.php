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
                <div class="flex w-full items-stretch gap-1 text-xs sm:gap-1.5">
                    @foreach($pipelineStages as $index => $stage)
                        @if($index > 0)
                            <span class="flex shrink-0 items-center text-slate-300" aria-hidden="true">→</span>
                        @endif

                        @php
                            $step = $lineage[$stage['key']] ?? null;
                            $isCurrent = $currentType && strtoupper((string) $currentType) === $stage['type'];
                            $stepLabel = $step ? $pipelineLabel($step['label'] ?? null) : '-';
                            $stepTitle = $stage['display'].' '.$stepLabel;
                            if ($step && ! empty($step['hint'])) {
                                $stepTitle = $step['hint'];
                            }
                        @endphp

                        @if($step && $isCurrent)
                            <span
                                class="inline-flex min-w-0 flex-1 cursor-default items-center justify-center gap-1 truncate rounded-lg bg-[#0025cc] px-2 py-1.5 text-center font-medium text-white"
                                aria-current="page"
                                title="{{ $stepTitle }}"
                            >
                                <span class="shrink-0">{{ $stage['display'] }}</span>
                                <span class="min-w-0 truncate opacity-80">{{ $stepLabel }}</span>
                            </span>
                        @elseif($step)
                            <a
                                href="{{ $step['url'] }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex min-w-0 flex-1 items-center justify-center gap-1 truncate rounded-lg bg-white px-2 py-1.5 text-center font-medium text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-100"
                                title="{{ $stepTitle }} (opens in new tab)"
                            >
                                <span class="shrink-0">{{ $stage['display'] }}</span>
                                <span class="min-w-0 truncate opacity-80">{{ $stepLabel }}</span>
                            </a>
                        @else
                            <span
                                class="inline-flex min-w-0 flex-1 cursor-default items-center justify-center gap-1 truncate rounded-lg px-2 py-1.5 text-center font-medium text-slate-400 ring-1 ring-dashed ring-slate-300"
                                title="{{ $stage['display'] }} not created yet"
                                aria-disabled="true"
                            >
                                <span class="shrink-0">{{ $stage['display'] }}</span>
                                <span class="opacity-80">-</span>
                            </span>
                        @endif
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
