{{--
  Receiving-style summary / filter cards (Maintenance, Accounting, etc).
  Each card:
    label, hint, value
    optional: href, active, title, filterKey, countKey, extraClass,
              tag ('a'|'button'|'div'), valueId, hintId
--}}
@php
    $cards = array_values($cards ?? []);
    $count = count($cards);
    $colsClass = match (min(6, max(1, $count))) {
        1 => 'xl:grid-cols-1',
        2 => 'xl:grid-cols-2',
        3 => 'xl:grid-cols-3',
        4 => 'xl:grid-cols-4',
        5 => 'xl:grid-cols-5',
        default => 'xl:grid-cols-6',
    };
@endphp

@if ($count)
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 {{ $colsClass }}">
        @foreach ($cards as $card)
            @php
                $href = $card['href'] ?? null;
                $isActive = (bool) ($card['active'] ?? false);
                $filterKey = $card['filterKey'] ?? null;
                $countKey = $card['countKey'] ?? $filterKey;
                $requestedTag = $card['tag'] ?? null;
                if ($requestedTag) {
                    $tag = $requestedTag;
                } elseif ($href) {
                    $tag = 'a';
                } else {
                    $tag = 'div';
                }
                $classes = trim(implode(' ', array_filter([
                    'maintenance-stat-card group rounded-2xl border bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)] transition hover:border-slate-300 hover:shadow-sm',
                    $tag === 'button' ? 'w-full text-left' : '',
                    $isActive ? 'is-active' : '',
                    $filterKey ? 'status-filter-card' : '',
                    $card['extraClass'] ?? '',
                ])));
            @endphp
            <{{ $tag }}
                @if ($tag === 'button') type="button" @endif
                @if ($href) href="{{ $href }}" @endif
                @if (!empty($card['title'])) title="{{ $card['title'] }}" @endif
                @if ($filterKey) data-filter="{{ $filterKey }}" @endif
                @if ($filterKey) aria-pressed="{{ $isActive ? 'true' : 'false' }}" @endif
                class="{{ $classes }}"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-500">{{ $card['label'] ?? '' }}</p>
                        @if (!empty($card['hint']))
                            <p @if (!empty($card['hintId'])) id="{{ $card['hintId'] }}" @endif class="mt-1 text-xs text-slate-400">{{ $card['hint'] }}</p>
                        @endif
                    </div>
                    <span class="stat-active-dot mt-0.5 h-2 w-2 shrink-0 rounded-full bg-[#0025cc]" aria-hidden="true"></span>
                </div>
                <p class="mt-4 text-3xl font-semibold tracking-tight text-slate-950">
                    @if ($countKey)
                        <span @if (!empty($card['valueId'])) id="{{ $card['valueId'] }}" @endif data-count="{{ $countKey }}">{{ $card['value'] ?? 0 }}</span>
                    @elseif (!empty($card['valueId']))
                        <span id="{{ $card['valueId'] }}">{{ $card['value'] ?? 0 }}</span>
                    @else
                        {{ $card['value'] ?? 0 }}
                    @endif
                </p>
            </{{ $tag }}>
        @endforeach
    </div>
@endif

@once
<style>
    .maintenance-stat-card .stat-active-dot {
        opacity: 0;
    }
    .maintenance-stat-card.is-active {
        border-color: rgba(0, 37, 204, 0.6);
        box-shadow: 0 0 0 2px rgba(0, 37, 204, 0.15);
    }
    .maintenance-stat-card.is-active .stat-active-dot {
        opacity: 1;
    }
</style>
@endonce
