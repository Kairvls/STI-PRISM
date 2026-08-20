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
            $cls = 'bg-amber-50 text-amber-800 ring-amber-200';
            if ($raw === 'Pending' && !empty($submitted)) { $label = 'Pending'; }
        }
    }
    if (in_array($raw, ['Approved', 'Completed', 'Released', 'Funds released'], true)) {
        $cls = 'bg-blue-50 text-blue-800 ring-blue-200';
    }
    if (in_array($raw, ['Minor Revision', 'Revision'], true) || ($raw === 'Pending' && empty($submitted) && !empty($revision))) {
        $cls = 'bg-sky-50 text-sky-800 ring-sky-200';
        $label = $raw === 'Pending' ? 'Revision required' : $raw;
    }
    if ($raw === 'Rejected') {
        $cls = 'bg-rose-50 text-rose-800 ring-rose-200';
    }
@endphp
<span class="inline-flex items-center whitespace-nowrap rounded-full px-2 py-0.5 text-[10px] font-semibold leading-tight ring-1 {{ $cls }}">{{ $label }}</span>
