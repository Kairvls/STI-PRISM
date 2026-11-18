@php
    $risStatus = (string) ($ris->ris_status ?? '');
    $presidentSig = trim((string) ($ris->ris_approved_by_signature ?? ''));
    $issuedBy = trim((string) ($ris->ris_issued_by_signature ?? ''));
    $presidentImage = isset($ris->ris_president_sig_image)
        ? ((int) $ris->ris_president_sig_image === 1)
        : ($presidentSig !== '' && str_starts_with($presidentSig, 'data:image'));
    if (isset($ris->ris_has_issued_by)) {
        $issuedBy = ((int) $ris->ris_has_issued_by === 1) ? '1' : '';
    }
    $presidentApproved = $risStatus === 'Approved by the President'
        || ($risStatus === 'Approved' && $presidentImage);

    // Soft slate base + light blue (active) / soft yellow (needs attention)
    if (in_array($risStatus, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true)) {
        $risStatusLabel = 'Pending';
        $risStatusClass = 'border-yellow-300 bg-yellow-50 text-yellow-700';
        $risStatusTitle = 'Waiting for Administrator to accept this procurement request';
    } elseif ($risStatus === 'Accepted') {
        $risStatusLabel = 'Accepted';
        $risStatusClass = 'border-violet-200 bg-violet-50 text-violet-700';
        $risStatusTitle = 'Accepted — ready for Direct Approve or Pass to President on Ready to Sign';
    } elseif ($risStatus === 'Directly Approved') {
        $risStatusLabel = 'Directly approved';
        $risStatusClass = 'border-slate-200 bg-slate-50 text-slate-600';
        $risStatusTitle = 'Directly approved by the Administrator and returned to Purchaser';
    } elseif ($risStatus === 'Forwarded to President' || ($risStatus === 'Approved' && ($presidentSig === '' || !$presidentImage))) {
        $risStatusLabel = 'Forwarded to President';
        $risStatusClass = 'border-blue-200 bg-blue-50 text-blue-700';
        $risStatusTitle = 'Sent to the President for a decision';
    } elseif ($presidentApproved && $issuedBy === '') {
        $risStatusLabel = 'Sign Issued by';
        $risStatusClass = 'border-amber-200 bg-amber-50 text-amber-800';
        $risStatusTitle = 'The President approved this RIS. Sign the Issued by field to send it back to the Purchaser.';
    } elseif ($presidentApproved) {
        $risStatusLabel = 'President approved';
        $risStatusClass = 'border-slate-200 bg-white text-slate-600';
        $risStatusTitle = 'Approved by the President and signed by Administrator';
    } elseif (in_array($risStatus, ['Rejected by the President', 'Rejected by President'], true)) {
        $risStatusLabel = 'President rejected';
        $risStatusClass = 'border-slate-500 bg-slate-800 text-slate-100';
        $risStatusTitle = 'Rejected by the President';
    } elseif ($risStatus === 'Returned by the President') {
        $risStatusLabel = 'For revision by President';
        $risStatusClass = 'border-amber-300 bg-amber-50 text-amber-800';
        $risStatusTitle = 'President returned this RIS for revision. School Administrator must return it to the Purchaser.';
    } elseif ($risStatus === 'President Revision') {
        $risStatusLabel = 'For revision by President';
        $risStatusClass = 'border-amber-300 bg-amber-50 text-amber-800';
        $risStatusTitle = 'President returned this RIS for revision — Purchaser can edit and resubmit';
    } elseif ($risStatus === 'Minor Revision') {
        $risStatusLabel = 'For revision by School Administrator';
        $risStatusClass = 'border-amber-300 bg-amber-50 text-amber-800';
        $risStatusTitle = 'Returned by School Administrator for revision — update and resubmit';
    } elseif ($risStatus === 'Rejected') {
        $risStatusLabel = 'Rejected';
        $risStatusClass = 'border-slate-500 bg-slate-800 text-slate-100';
        $risStatusTitle = 'Hard rejected by School Administrator';
    } elseif (\App\Support\ProcurementDraftLabel::isDraftStatus($risStatus)) {
        $risStatusLabel = \App\Support\ProcurementDraftLabel::STATUS;
        $risStatusClass = 'border-gray-200 bg-gray-50 text-gray-600';
        $risStatusTitle = \App\Support\ProcurementDraftLabel::STATUS_LONG;
    } else {
        $risStatusLabel = $risStatus !== '' ? $risStatus : 'N/A';
        $risStatusClass = 'border-gray-200 bg-gray-50 text-gray-600';
        $risStatusTitle = 'Current RIS status';
    }
@endphp

<span
    class="max-w-full rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ !empty($wrap) ? 'inline-block whitespace-normal break-words text-left leading-snug' : 'inline-flex items-center whitespace-nowrap leading-4' }} {{ $risStatusClass }}"
    data-tip="{{ $risStatusTitle }}"
>
    {{ $risStatusLabel }}
</span>
