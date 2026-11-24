@php
    $risStatusLabel = \App\Support\ProcurementStatus::forRis($ris);
    $risStatusStyle = \App\Support\ProcurementStatus::style($risStatusLabel);
    $wrapLabel = ! empty($wrap) || \App\Support\ProcurementStatus::wraps($risStatusLabel);

    $structuredBadge = match ($risStatusLabel) {
        \App\Support\ProcurementStatus::REVIEWED => [
            'pill' => 'Reviewed',
            'sub' => 'Awaiting for approval',
            'bg' => '#f3e8ff',
            'fg' => '#7e22ce',
            'border' => '#d8b4fe',
            'dot' => '#7e22ce',
        ],
        \App\Support\ProcurementStatus::TO_REVIEW,
        \App\Support\ProcurementStatus::WITH_SCHOOL_ADMINISTRATOR,
        \App\Support\ProcurementStatus::SENT_TO_SCHOOL_ADMINISTRATOR => [
            'pill' => 'Pending',
            'sub' => 'For School Admin review',
            'bg' => '#fffbeb',
            'fg' => '#b45309',
            'border' => '#fde68a',
            'dot' => '#d97706',
        ],
        \App\Support\ProcurementStatus::APPROVED_BY_SA_READY_FOR_ATP => [
            'pill' => 'Approved',
            'sub' => 'By School Administrator',
            'bg' => '#ecfdf5',
            'fg' => '#047857',
            'border' => '#a7f3d0',
            'dot' => '#059669',
        ],
        \App\Support\ProcurementStatus::APPROVED_BY_PRESIDENT_READY_FOR_ATP,
        \App\Support\ProcurementStatus::APPROVED_BY_PRESIDENT_TO_PURCHASER => [
            'pill' => 'Approved',
            'sub' => 'By President',
            'bg' => '#ecfdf5',
            'fg' => '#047857',
            'border' => '#a7f3d0',
            'dot' => '#059669',
        ],
        \App\Support\ProcurementStatus::APPROVED_BY_PRESIDENT_NEED_ISSUED_BY => [
            'pill' => 'Approved',
            'sub' => 'Awaiting Issued by',
            'bg' => '#ecfdf5',
            'fg' => '#047857',
            'border' => '#a7f3d0',
            'dot' => '#059669',
        ],
        \App\Support\ProcurementStatus::SA_FORWARDED_TO_PRESIDENT,
        \App\Support\ProcurementStatus::WITH_PRESIDENT => [
            'pill' => 'Forwarded',
            'sub' => 'To President for approval',
            'bg' => '#eef2ff',
            'fg' => '#4338ca',
            'border' => '#c7d2fe',
            'dot' => '#4f46e5',
        ],
        \App\Support\ProcurementStatus::SA_RETURN_RIS_FOR_REVISION,
        \App\Support\ProcurementStatus::SA_FORWARD_RETURNED_RIS_TO_PURCHASER,
        \App\Support\ProcurementStatus::FOR_REVISION => [
            'pill' => 'Returned',
            'sub' => 'For revision',
            'bg' => '#fff7ed',
            'fg' => '#c2410c',
            'border' => '#fed7aa',
            'dot' => '#ea580c',
        ],
        \App\Support\ProcurementStatus::PRESIDENT_RETURN_RIS_FOR_REVISION => [
            'pill' => 'Returned',
            'sub' => 'By President for revision',
            'bg' => '#fff7ed',
            'fg' => '#c2410c',
            'border' => '#fed7aa',
            'dot' => '#ea580c',
        ],
        \App\Support\ProcurementStatus::REJECTED_BY_SA => [
            'pill' => 'Rejected',
            'sub' => 'By School Administrator',
            'bg' => '#fef2f2',
            'fg' => '#dc2626',
            'border' => '#fecaca',
            'dot' => '#dc2626',
        ],
        \App\Support\ProcurementStatus::REJECTED_BY_PRESIDENT => [
            'pill' => 'Rejected',
            'sub' => 'By President',
            'bg' => '#fef2f2',
            'fg' => '#dc2626',
            'border' => '#fecaca',
            'dot' => '#dc2626',
        ],
        default => null,
    };
@endphp

@if ($structuredBadge)
    <div class="inline-flex flex-col items-start">
        <span
            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
            style="background:{{ $structuredBadge['bg'] }};color:{{ $structuredBadge['fg'] }};border-color:{{ $structuredBadge['border'] }};"
            data-tip="{{ $structuredBadge['pill'] }} — {{ $structuredBadge['sub'] }}"
        >
            <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background:{{ $structuredBadge['dot'] }};"></span>
            <span>{{ $structuredBadge['pill'] }}</span>
        </span>
        <div class="mt-1 text-[11px] font-medium leading-tight text-slate-600">{{ $structuredBadge['sub'] }}</div>
    </div>
@else
    <span
        class="max-w-full rounded-full px-2.5 py-1 text-xs font-semibold {{ $wrapLabel ? 'inline-block max-w-[9.5rem] whitespace-normal break-words text-left leading-snug' : 'inline-flex items-center whitespace-nowrap leading-4' }}"
        style="{{ $risStatusStyle }}"
        data-tip="{{ $risStatusLabel }}"
    >
        {{ $risStatusLabel }}
    </span>
@endif
