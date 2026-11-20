@php
    $badgeLabel = \App\Support\ProcurementStatus::label($status ?? '', $doc ?? null, [
        'submitted' => $submitted ?? null,
        'revision' => $revision ?? null,
        'overdue' => ! empty($overdue),
    ]);
    $badgeStyle = \App\Support\ProcurementStatus::style($badgeLabel);
    $wrapLabel = \App\Support\ProcurementStatus::wraps($badgeLabel);
@endphp
<span
    class="acc-status-badge max-w-full rounded-full px-2.5 py-1 text-xs font-semibold {{ $wrapLabel ? 'inline-block max-w-[9.5rem] whitespace-normal break-words text-left leading-snug' : 'inline-flex items-center whitespace-nowrap leading-4' }}"
    style="{{ $badgeStyle }}"
    data-tip="{{ $badgeLabel }}"
>{{ $badgeLabel }}</span>
