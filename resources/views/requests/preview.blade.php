<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $requestNo }}</title>
    <style>
        html, body { margin: 0; background: #fff; color: #0f172a; font-family: ui-sans-serif, system-ui, sans-serif; }
        .wrap { padding: 20px; }
        .kicker { margin: 0; font-size: 13px; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: #64748b; }
        h1 { margin: 4px 0 0; font-size: 26px; line-height: 1.2; font-weight: 600; letter-spacing: -0.02em; color: #020617; }
        .empty { margin-top: 24px; font-size: 14px; color: #92400e; }
        .card { margin-top: 20px; border: 1px solid #e5e7eb; border-radius: 12px; background: #fff; padding: 20px; }
        .head { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 12px; }
        .now { margin: 4px 0 0; font-size: 22px; font-weight: 700; color: #020617; }
        .hint { margin: 4px 0 0; font-size: 15px; color: #475569; }
        .meta { text-align: right; font-size: 15px; color: #475569; }
        .meta p { margin: 0; }
        .meta p + p { margin-top: 4px; }
        .meta strong { font-weight: 500; color: #334155; }
        .steps { display: flex; align-items: flex-start; min-width: 640px; margin-top: 24px; }
        .step { flex: 1; display: flex; flex-direction: column; align-items: center; }
        .rail { display: flex; align-items: center; width: 100%; }
        .line { flex: 1; height: 2px; }
        .line.on { background: #0025cc; }
        .line.off { background: #e5e7eb; }
        .line.none { background: transparent; }
        .dot { flex-shrink: 0; width: 42px; height: 42px; border-radius: 999px; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; }
        .dot.current { background: #0025cc; color: #fff; box-shadow: 0 0 0 4px #dbeafe; }
        .dot.done { background: rgba(0, 37, 204, 0.1); color: #0025cc; }
        .dot.wait { background: #f3f4f6; color: #9ca3af; }
        .name { margin: 8px 0 0; text-align: center; font-size: 14px; font-weight: 600; color: #334155; }
        .name.current { color: #0025cc; }
        .fid { margin: 4px 0 0; max-width: 11rem; text-align: center; font-size: 14px; font-weight: 700; font-variant-numeric: tabular-nums; color: #0f172a; overflow-wrap: anywhere; }
    </style>
</head>
<body>
<div class="wrap">
    <p class="kicker">Request No.</p>
    <h1>{{ $requestNo }}</h1>

    @if(! $explorer)
        <p class="empty">No procurement documents were found for this request.</p>
    @else
        @php
            $stages = $explorer['stages'] ?? [];
            $stageOrder = $explorer['stage_order']
                ?? \App\Support\RequestExplorer::visibleStageKeys($stages, $explorer['payment_path'] ?? null);
            $current = $explorer['current_stage'] ?? 'ris';
            $currentIndex = array_search($current, $stageOrder, true);
            if ($currentIndex === false) {
                $currentIndex = 0;
            }
        @endphp
        <div class="card">
            <div class="head">
                <div>
                    <p class="kicker">Now at</p>
                    <p class="now">{{ $stageLabels[$current] ?? strtoupper($current) }}</p>
                    @if(! empty($explorer['current_hint']))
                        <p class="hint">{{ $explorer['current_hint'] }}</p>
                    @endif
                </div>
                <div class="meta">
                    @if(! empty($explorer['payment_path_label']))
                        <p>Payment: <strong>{{ $explorer['payment_path_label'] }}</strong></p>
                    @endif
                    @if(! empty($explorer['funds']['label']))
                        <p>Funds: <strong>{{ $explorer['funds']['label'] }}</strong></p>
                    @endif
                </div>
            </div>
            <div class="steps">
                @foreach($stageOrder as $index => $key)
                    @php
                        $stage = $stages[$key] ?? null;
                        $exists = ! empty($stage['exists']);
                        $isCurrent = $key === $current;
                        $isPast = $index < $currentIndex;
                        $left = $index === 0 ? 'none' : (($isPast || $isCurrent) ? 'on' : 'off');
                        $right = $index === count($stageOrder) - 1 ? 'none' : ($isPast ? 'on' : 'off');
                    @endphp
                    <div class="step">
                        <div class="rail">
                            <div class="line {{ $left }}"></div>
                            <div class="dot {{ $isCurrent ? 'current' : ($exists ? 'done' : 'wait') }}">{{ strtoupper($key) }}</div>
                            <div class="line {{ $right }}"></div>
                        </div>
                        <p class="name {{ $isCurrent ? 'current' : '' }}">{{ $stageLabels[$key] ?? strtoupper($key) }}</p>
                        @if($exists && ! empty($stage['label']))
                            <p class="fid" title="{{ $stage['label'] }}">{{ $stage['label'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
</body>
</html>
