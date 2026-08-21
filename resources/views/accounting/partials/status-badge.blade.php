@php
    $raw = (string) ($status ?? '—');
    if (in_array($raw, ['Pending Admin Approval', 'For Admin'], true)) {
        $raw = 'Waiting for Accounting';
    }
    $label = $raw;
    $cls = 'bg-slate-100 text-slate-700 ring-slate-200';
    if (in_array($raw, ['Pending', 'Submitted', 'Under Review', 'Resubmitted', 'Waiting for funds', 'Waiting for Accounting'], true) || !empty($submitted)) {
        if (in_array($raw, ['Approved', 'Completed', 'Released', 'Funds released'], true)) {
            // keep approved
        } elseif (!in_array($raw, ['Rejected', 'Minor Revision'], true)) {
            // Distinct colors per state: Submitted = green, Under Review = gray, Pending = yellow
            if ($raw === 'Under Review') {
                $cls = 'bg-slate-200 text-slate-700 ring-slate-300';
            } elseif ($raw === 'Pending') {
                $cls = 'bg-yellow-50 text-yellow-700 ring-yellow-300';
            } else {
                $cls = 'bg-emerald-50 text-emerald-700 ring-emerald-300';
            }
            if ($raw === 'Pending' && !empty($submitted)) { $label = 'Pending'; }
        }
    }
    if (in_array($raw, ['Approved', 'Completed', 'Released', 'Funds released'], true)) {
        $cls = 'bg-blue-50 text-blue-800 ring-blue-300';
    }
    if (in_array($raw, ['Minor Revision', 'Revision'], true) || ($raw === 'Pending' && empty($submitted) && !empty($revision))) {
        $cls = 'bg-sky-50 text-sky-800 ring-sky-300';
        $label = $raw === 'Pending' ? 'Revision required' : $raw;
    }
    if ($raw === 'Rejected') {
        $cls = 'bg-rose-50 text-rose-800 ring-rose-300';
    }
@endphp
<span class="acc-status-badge inline-flex items-center whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-bold leading-tight ring-1 {{ $cls }}">{{ $label }}</span>