@php
    $risStatus = (string) ($ris->ris_status ?? '');
    $presidentSig = trim((string) ($ris->ris_approved_by_signature ?? ''));
    $issuedBy = trim((string) ($ris->ris_issued_by_signature ?? ''));
    $presidentImage = $presidentSig !== '' && str_starts_with($presidentSig, 'data:image');
    $presidentApproved = $risStatus === 'Approved by the President'
        || ($risStatus === 'Approved' && $presidentImage);

    // Soft slate base + light blue (active) / soft yellow (needs attention)
    if (in_array($risStatus, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true)) {
        $risStatusLabel = 'Pending';
        $risStatusClass = 'border-sky-200 bg-sky-50 text-sky-700';
        $risStatusTitle = 'This RIS is waiting for review';
    } elseif ($risStatus === 'Directly Approved') {
        $risStatusLabel = 'Admin Approved';
        $risStatusClass = 'border-slate-200 bg-slate-50 text-slate-600';
        $risStatusTitle = 'Approved by Admin and returned to Purchaser';
    } elseif ($risStatus === 'Forwarded to President' || ($risStatus === 'Approved' && ($presidentSig === '' || !$presidentImage))) {
        $risStatusLabel = 'Forwarded to President';
        $risStatusClass = 'border-blue-200 bg-blue-50 text-blue-700';
        $risStatusTitle = 'Sent to the President for a decision';
    } elseif ($presidentApproved && $issuedBy === '') {
        $risStatusLabel = 'Awaiting Admin';
        $risStatusClass = 'border-amber-200 bg-amber-50 text-amber-800';
        $risStatusTitle = 'President approved. Admin must sign Issued by';
    } elseif ($presidentApproved) {
        $risStatusLabel = 'Approved by the President';
        $risStatusClass = 'border-slate-200 bg-white text-slate-600';
        $risStatusTitle = 'Approved by the President and signed by Admin';
    } elseif (in_array($risStatus, ['Rejected by the President', 'Rejected by President'], true)) {
        $risStatusLabel = 'Rejected by the President';
        $risStatusClass = 'border-slate-500 bg-slate-800 text-slate-100';
        $risStatusTitle = 'Rejected by the President';
    } elseif (in_array($risStatus, ['Minor Revision', 'Rejected'], true)) {
        $risStatusLabel = 'Amend';
        $risStatusClass = 'border-amber-300 bg-amber-50 text-amber-800';
        $risStatusTitle = 'Returned to Purchaser for amendment';
    } else {
        $risStatusLabel = $risStatus !== '' ? $risStatus : 'N/A';
        $risStatusClass = 'border-gray-200 bg-gray-50 text-gray-600';
        $risStatusTitle = 'Current RIS status';
    }
@endphp

<span
    class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold {{ $risStatusClass }}"
    title="{{ $risStatusTitle }}"
>
    {{ $risStatusLabel }}
</span>
