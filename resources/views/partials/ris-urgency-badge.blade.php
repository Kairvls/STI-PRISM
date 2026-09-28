{{--
  RIS urgency pill. Expects: $urgent (bool). Optional: $size = sm|md, $title (tooltip)
  ATP / PO / RFC / RR / LR show it for the urgency inherited from their RIS (see App\Support\DocumentUrgency).
  Urgent uses inline colours because the admin/purchaser grayscale theme remaps every rose/red utility class to slate.
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
    <span class="inline-flex items-center whitespace-nowrap bg-slate-50 font-semibold text-slate-600 ring-1 ring-inset ring-slate-200 {{ $badgeSize }}">Non-Urgent</span>
@endif
