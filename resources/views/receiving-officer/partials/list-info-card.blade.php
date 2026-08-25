{{--
  Generic Receiving list card (Cards view).
  Expects: $title, $roStatus, $roSearch
  Optional: $subtitle, $status, $statusClass, $fields ([['label','value']]), $actionsHtml
--}}
@php
    $title = $title ?? '—';
    $subtitle = $subtitle ?? null;
    $status = $status ?? null;
    $statusClass = $statusClass ?? 'border-slate-200 bg-slate-50 text-slate-700';
    $fields = $fields ?? [];
    $actionsHtml = $actionsHtml ?? '';
    $roStatus = $roStatus ?? 'all';
    $roSearch = $roSearch ?? '';
@endphp
<article
    data-ro-card-item
    data-ro-status="{{ $roStatus }}"
    data-ro-search="{{ $roSearch }}"
    class="rounded-xl border border-gray-200 bg-white px-4 py-3.5 text-black shadow-[0_1px_2px_rgba(15,23,42,0.03)]"
>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h3 class="truncate text-sm font-bold text-black" title="{{ $title }}">{{ $title }}</h3>
            @if($subtitle)
                <p class="mt-0.5 text-[11px] font-medium text-slate-500">{{ $subtitle }}</p>
            @endif
        </div>
        @if($status)
            <span class="shrink-0 rounded-lg border px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $status }}</span>
        @endif
    </div>

    @if(count($fields))
        <div class="mt-3 grid grid-cols-1 gap-x-6 gap-y-1.5 text-xs sm:grid-cols-2">
            @foreach($fields as $field)
                <div class="flex gap-2 {{ !empty($field['full']) ? 'sm:col-span-2' : '' }}">
                    <span class="w-24 shrink-0 font-semibold text-slate-500">{{ $field['label'] ?? '' }}</span>
                    <span class="min-w-0 truncate text-slate-800" title="{{ $field['value'] ?? '' }}">{{ $field['value'] ?? '—' }}</span>
                </div>
            @endforeach
        </div>
    @endif

    @if($actionsHtml !== '')
        <div class="mt-3 flex items-center justify-end gap-1.5">
            {!! $actionsHtml !!}
        </div>
    @endif
</article>
