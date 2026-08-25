{{-- Shared print icon button. Expects $risId; optional $btnClass --}}
@php
    $risId = $risId ?? null;
    $btnClass = $btnClass ?? 'inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900';
@endphp
@if ($risId)
<button
    type="button"
    onclick="window.printAdminRis('{{ $risId }}')"
    title="Print RIS"
    aria-label="Print RIS"
    class="{{ $btnClass }}"
>
    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
    </svg>
</button>
@endif
