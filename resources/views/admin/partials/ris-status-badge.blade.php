@php
    $risStatusLabel = \App\Support\ProcurementStatus::forRis($ris);
    $risStatusStyle = \App\Support\ProcurementStatus::style($risStatusLabel);
    $wrapLabel = ! empty($wrap) || \App\Support\ProcurementStatus::wraps($risStatusLabel);
@endphp

<span
    class="max-w-full rounded-full px-2.5 py-1 text-xs font-semibold {{ $wrapLabel ? 'inline-block max-w-[9.5rem] whitespace-normal break-words text-left leading-snug' : 'inline-flex items-center whitespace-nowrap leading-4' }}"
    style="{{ $risStatusStyle }}"
    data-tip="{{ $risStatusLabel }}"
>
    {{ $risStatusLabel }}
</span>
