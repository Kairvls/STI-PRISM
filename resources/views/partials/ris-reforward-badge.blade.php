{{--
  Indicator for Accepted RIS that must be forwarded back to the President
  (already returned by President at least once — Direct Approve is blocked).
  Optional: $size = sm|md
--}}
@php
    $badgeSize = ($size ?? 'md') === 'sm' ? 'rounded px-1.5 py-0.5 text-[10px]' : 'rounded-md px-2 py-1 text-[11px]';
@endphp
<span
    class="inline-flex max-w-full items-center whitespace-normal break-words bg-orange-50 font-semibold leading-snug text-orange-800 ring-1 ring-inset ring-orange-200 {{ $badgeSize }}"
    data-tip="Previously returned by the President. Forward it back — Direct Approve is not available."
    title="Previously returned by the President. Forward it back — Direct Approve is not available."
>Re-forward to President</span>
