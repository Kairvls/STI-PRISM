{{--
    Status / condition badge in the Maintenance colours.
    @include('partials.status-pill', ['status' => $value])
--}}
@php
    $statusText = trim((string) ($status ?? ''));
    $statusKey = strtolower($statusText);
    $statusTone = match (true) {
        $statusKey === '' => 'bg-slate-100 text-slate-500 ring-slate-200',
        str_contains($statusKey, 'reject') || str_contains($statusKey, 'cancel') || str_contains($statusKey, 'overdue')
            || str_contains($statusKey, 'damage') || str_contains($statusKey, 'defect') || str_contains($statusKey, 'critical')
            || str_contains($statusKey, 'dispos') || str_contains($statusKey, 'lost') || str_contains($statusKey, 'fail')
            || $statusKey === 'urgent' => 'bg-rose-50 text-rose-700 ring-rose-200',
        str_contains($statusKey, 'replacement') || str_contains($statusKey, 'revision') || str_contains($statusKey, 'return') => 'bg-orange-50 text-orange-700 ring-orange-200',
        str_contains($statusKey, 'pending') || str_contains($statusKey, 'review') || str_contains($statusKey, 'submitted')
            || str_contains($statusKey, 'awaiting') || str_contains($statusKey, 'fair') || str_contains($statusKey, 'partial')
            || str_contains($statusKey, 'maintenance') => 'bg-amber-50 text-amber-700 ring-amber-200',
        str_contains($statusKey, 'process') || str_contains($statusKey, 'progress') || str_contains($statusKey, 'forward')
            || str_contains($statusKey, 'borrow') || str_contains($statusKey, 'draft') => 'bg-sky-50 text-sky-700 ring-sky-200',
        str_contains($statusKey, 'resolv') || str_contains($statusKey, 'approv') || str_contains($statusKey, 'accept')
            || str_contains($statusKey, 'deliver') || str_contains($statusKey, 'complet') || str_contains($statusKey, 'receiv')
            || str_contains($statusKey, 'signed') || str_contains($statusKey, 'paid') || str_contains($statusKey, 'released')
            || in_array($statusKey, ['good', 'excellent', 'active', 'working', 'available'], true) => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        default => 'bg-slate-100 text-slate-600 ring-slate-200',
    };
@endphp
<span class="inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 {{ $statusTone }}">{{ $statusText !== '' ? $statusText : '—' }}</span>
