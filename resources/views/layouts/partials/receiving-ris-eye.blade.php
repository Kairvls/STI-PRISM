@php
    $risId = $risId ?? null;
    $reportId = $reportId ?? null;
    $hasReport = ! empty($reportId);
    $hasRis = ! empty($risId);
@endphp
<button
    type="button"
    class="ro-preview-btn"
    @if($hasReport)
        onclick="openReceivingReportPreview('{{ (int) $reportId }}')"
        title="View Receiving Report"
        aria-label="View Receiving Report"
    @elseif($hasRis)
        onclick="openReceivingRisPreview('{{ $risId }}')"
        title="Preview RIS"
        aria-label="Preview RIS"
    @else
        disabled
        title="No report available to preview"
        aria-label="Preview unavailable"
    @endif
>
    <i data-lucide="eye" class="h-4 w-4"></i>
</button>
