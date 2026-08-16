@php
    $risId = $risId ?? null;
    $hasRis = !empty($risId);
@endphp
<button
    type="button"
    class="ro-preview-btn"
    @if($hasRis)
        onclick="openReceivingRisPreview('{{ $risId }}')"
        title="Preview RIS"
    @else
        disabled
        title="No RIS is attached to this ATP. Preview is available only when an RIS exists."
        aria-label="RIS preview unavailable. No RIS is attached to this ATP."
    @endif
>
    <i data-lucide="eye" class="h-4 w-4"></i>
</button>
