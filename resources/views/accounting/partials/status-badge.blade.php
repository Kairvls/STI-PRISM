@php
    $raw = (string) ($status ?? '—');
    $label = $raw;
    $cls = 'bg-gray-100 text-gray-700 ring-gray-200';
    if (in_array($raw, ['Pending', 'Submitted', 'Under Review', 'Resubmitted'], true) || !empty($submitted)) {
        if (in_array($raw, ['Approved', 'Completed', 'Released', 'Funds released'], true)) {
            // keep approved
        } elseif (!in_array($raw, ['Rejected', 'Minor Revision'], true)) {
            $cls = 'bg-amber-50 text-amber-800 ring-amber-100';
            if ($raw === 'Pending' && !empty($submitted)) { $label = 'Pending'; }
        }
    }
    if (in_array($raw, ['Approved', 'Completed', 'Released', 'Funds released'], true)) {
        $cls = 'bg-emerald-50 text-emerald-800 ring-emerald-100';
    }
    if (in_array($raw, ['Minor Revision', 'Revision'], true) || ($raw === 'Pending' && empty($submitted) && !empty($revision))) {
        $cls = 'bg-sky-50 text-sky-800 ring-sky-100';
        $label = $raw === 'Pending' ? 'Revision required' : $raw;
    }
    if ($raw === 'Rejected') {
        $cls = 'bg-rose-50 text-rose-800 ring-rose-100';
    }
@endphp
<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 {{ $cls }}">{{ $label }}</span>
