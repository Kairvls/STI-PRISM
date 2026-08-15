@php
    $risStatus = (string) ($ris->ris_status ?? '');
    $presidentSig = trim((string) ($ris->ris_approved_by_signature ?? ''));

    if (in_array($risStatus, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true)) {
        $risStatusLabel = 'Pending';
        $risStatusClass = 'border-amber-200 bg-amber-50 text-amber-700';
        $risStatusTitle = 'This RIS is waiting for review';
    } elseif ($risStatus === 'Directly Approved') {
        $risStatusLabel = 'Admin Approved';
        $risStatusClass = 'border-sky-200 bg-sky-50 text-sky-700';
        $risStatusTitle = 'Approved by Admin and returned to Purchaser';
    } elseif ($risStatus === 'Forwarded to President' || ($risStatus === 'Approved' && $presidentSig === '')) {
        $risStatusLabel = 'Forwarded to President';
        $risStatusClass = 'border-blue-200 bg-blue-50 text-blue-700';
        $risStatusTitle = 'Sent to the President for a decision';
    } elseif ($risStatus === 'Approved by the President' || ($risStatus === 'Approved' && $presidentSig !== '')) {
        $risStatusLabel = 'Approved by the President';
        $risStatusClass = 'border-emerald-200 bg-emerald-50 text-emerald-700';
        $risStatusTitle = 'Approved by the President';
    } elseif (in_array($risStatus, ['Rejected by the President', 'Rejected by President'], true)) {
        $risStatusLabel = 'Rejected by the President';
        $risStatusClass = 'border-rose-200 bg-rose-50 text-rose-700';
        $risStatusTitle = 'Rejected by the President';
    } elseif (in_array($risStatus, ['Minor Revision', 'Rejected'], true)) {
        $risStatusLabel = 'Amend';
        $risStatusClass = 'border-yellow-300 bg-yellow-50 text-amber-600';
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
