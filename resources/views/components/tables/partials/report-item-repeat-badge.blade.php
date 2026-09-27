@php
    $repeatState = $item->repeat_state ?? null;
    $repeatEarlier = $item->repeat_earlier ?? null;
    $repeatLatest = $item->repeat_latest ?? null;
    $repeatTimes = (int) ($item->repeat_times_reported ?? 0);
    $repeatSince = $repeatEarlier && ! empty($repeatEarlier->submitted_at)
        ? \Carbon\Carbon::parse($repeatEarlier->submitted_at)
        : null;
@endphp

@if ($repeatState === 'not_actioned' || $repeatState === 'in_progress')
    <div class="mt-2 rounded-lg px-3 py-2 text-xs ring-1 {{ $repeatState === 'not_actioned' ? 'bg-rose-50 text-rose-800 ring-rose-200' : 'bg-sky-50 text-sky-800 ring-sky-200' }}">
        <p class="flex flex-wrap items-center gap-1.5 font-semibold">
            <i data-lucide="{{ $repeatState === 'not_actioned' ? 'alert-triangle' : 'loader' }}" class="h-3.5 w-3.5 shrink-0"></i>
            {{ $repeatState === 'not_actioned' ? 'Reported before · not actioned yet' : 'Reported before · already in progress' }}
            @if ($repeatState === 'not_actioned')
                <span class="rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">Prioritize</span>
            @endif
        </p>
        <p class="mt-1 leading-5 {{ $repeatState === 'not_actioned' ? 'text-rose-700' : 'text-sky-700' }}">
            First filed under {{ $repeatEarlier->ticket_code ?? 'an earlier ticket' }}
            @if ($repeatSince)
                on {{ $repeatSince->format('M d, Y g:i A') }} ({{ $repeatSince->diffForHumans(null, true) }} waiting)
            @endif
            @if ((int) ($item->repeat_earlier_count ?? 0) > 1)
                · {{ (int) $item->repeat_earlier_count }} earlier open tickets
            @endif
            @if ($repeatTimes > 1)
                · reported {{ $repeatTimes }} times in total
            @endif
        </p>
    </div>
@elseif ($repeatState === 'reported_again')
    <div class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 ring-1 ring-amber-200">
        <p class="flex items-center gap-1.5 font-semibold">
            <i data-lucide="repeat" class="h-3.5 w-3.5 shrink-0"></i>
            Reported again {{ (int) ($item->repeat_later_count ?? 0) }}× while this ticket is open
        </p>
        <p class="mt-1 leading-5 text-amber-700">
            Latest: {{ $repeatLatest->ticket_code ?? 'a newer ticket' }}
            @if (! empty($repeatLatest->submitted_at))
                on {{ \Carbon\Carbon::parse($repeatLatest->submitted_at)->format('M d, Y g:i A') }}
            @endif
            @if ($repeatTimes > 1)
                · reported {{ $repeatTimes }} times in total
            @endif
        </p>
    </div>
@endif
