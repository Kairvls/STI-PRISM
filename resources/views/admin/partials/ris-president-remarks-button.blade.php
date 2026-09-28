{{--
  Opens the President remarks modal. Renders only when the President has decided.
  Expects: $ris (with ->presidentDecision)
  Optional: $btnClass
--}}
@php
    $presidentDecision = $ris->presidentDecision ?? null;
    $hasPresidentRemarks = $presidentDecision && $presidentDecision['remarks'] !== '';
@endphp

@if ($presidentDecision)
    <button
        type="button"
        onclick="window.openPresidentRemarksModal(this)"
        data-ref="{{ \App\Support\RisWorkflow::formNumber($ris) }}"
        data-decision="{{ $presidentDecision['decision'] }}"
        data-remarks="{{ $presidentDecision['remarks'] }}"
        data-by="{{ $presidentDecision['by'] }}"
        data-at="{{ $presidentDecision['at'] }}"
        title="{{ $hasPresidentRemarks ? 'Read remarks from the President' : 'The President left no remarks' }}"
        aria-label="President remarks"
        class="relative {{ $btnClass ?? 'inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 hover:text-gray-900' }}"
    >
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8M8 14h5m-9 6 2.6-2.6A2 2 0 0 1 8 17h10a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v14z"></path>
        </svg>
        @if ($hasPresidentRemarks)
            <span class="absolute -right-0.5 -top-0.5 h-2 w-2 rounded-full bg-[#0025cc] ring-2 ring-white" aria-hidden="true"></span>
        @endif
    </button>
@endif
