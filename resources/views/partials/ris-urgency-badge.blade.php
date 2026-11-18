{{--
  RIS urgency pill. Expects: $urgent (bool). Optional: $size = sm|md, $title (tooltip)
  ATP / PO / RFC / RR / LR show it for the urgency inherited from their RIS (see App\Support\DocumentUrgency).
  Inline colours: admin/purchaser grayscale theme remaps rose/red (and some slate) utilities.
--}}
@php
    $badgeSize = ($size ?? 'md') === 'sm' ? 'rounded px-1.5 py-0.5 text-[10px]' : 'rounded-md px-2 py-1 text-[11px]';
@endphp
@if(!empty($urgent))
    <span
        class="inline-flex items-center whitespace-nowrap font-semibold {{ $badgeSize }}"
        style="background-color: #dc2626 !important; color: #ffffff !important; box-shadow: none !important;"
        @if(!empty($title)) title="{{ $title }}" @endif
    >Urgent</span>
@else
    <span
        class="inline-flex items-center whitespace-nowrap font-semibold {{ $badgeSize }}"
        style="background-color: #ecfdf5 !important; color: #047857 !important; box-shadow: inset 0 0 0 1px #a7f3d0 !important;"
        @if(!empty($title)) title="{{ $title }}" @endif
    >Non-Urgent</span>
@endif
