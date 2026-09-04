{{--
  Diagonal APPROVED watermark for on-screen RIS preview only.
  Exactly 3 stamps, horizontally centered and split vertically.
  Hidden when printing (@media print).
  Optional: $watermarkLabel (default: APPROVED)
--}}
@php
    $watermarkLabel = strtoupper((string) ($watermarkLabel ?? 'APPROVED'));
@endphp

@once
<style>
    .approval-watermark {
        position: absolute;
        inset: 0;
        z-index: 40;
        overflow: hidden;
        pointer-events: none;
        user-select: none;
        -webkit-user-select: none;
    }
    .approval-watermark-pattern {
        position: absolute;
        inset: 0;
    }
    .approval-watermark-stamp {
        position: absolute;
        top: 48%;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 72px;
        font-weight: 800;
        letter-spacing: 0.14em;
        line-height: 1;
        color: rgba(15, 23, 42, 0.03);
        text-transform: uppercase;
        white-space: nowrap;
        transform: translate(-50%, -50%) rotate(-32deg);
    }
    /* Horizontally aligned (same row), split across the page */
    .approval-watermark-stamp:nth-child(1) { left: 22%; }
    .approval-watermark-stamp:nth-child(2) { left: 50%; }
    .approval-watermark-stamp:nth-child(3) { left: 78%; }

    html.screen-preview .approval-watermark {
        animation: approvalWatermarkIn 0.55s ease both;
    }
    @keyframes approvalWatermarkIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @media print {
        .approval-watermark {
            display: none !important;
        }
    }
</style>
@endonce

<div class="approval-watermark" aria-hidden="true">
    <div class="approval-watermark-pattern">
        <span class="approval-watermark-stamp">{{ $watermarkLabel }}</span>
        <span class="approval-watermark-stamp">{{ $watermarkLabel }}</span>
        <span class="approval-watermark-stamp">{{ $watermarkLabel }}</span>
    </div>
</div>
