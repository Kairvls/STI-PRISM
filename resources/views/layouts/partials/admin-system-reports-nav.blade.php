@php
    $current = $current ?? '';
    $filters = $filters ?? ['q' => '', 'from' => '', 'to' => ''];
@endphp

<style>
@media print {
    #sidebar, .sidebar, [id="sidebar"], header, .print-hidden, .admin-reports-nav { display: none !important; }
    .print-only { display: block !important; }
    main, .content-wrapper { overflow: visible !important; height: auto !important; }
    body { overflow: visible !important; background: #fff !important; }
}
.print-only { display: none; }
</style>

<nav class="admin-reports-nav print-hidden mb-6 flex flex-wrap gap-2">
    <a href="{{ url('/admin/reports/maintenance-history') }}" class="rounded-xl border px-3 py-2 text-sm font-semibold {{ $current === 'maintenance' ? 'border-[#0037c7] bg-[#0037c7] text-white' : 'border-gray-200 bg-white text-gray-700' }}">Maintenance</a>
    <a href="{{ url('/admin/reports/receiving') }}" class="rounded-xl border px-3 py-2 text-sm font-semibold {{ $current === 'receiving' ? 'border-[#0037c7] bg-[#0037c7] text-white' : 'border-gray-200 bg-white text-gray-700' }}">Receiving</a>
    <a href="{{ url('/admin/reports/approval-logs') }}" class="rounded-xl border px-3 py-2 text-sm font-semibold {{ $current === 'approvals' ? 'border-[#0037c7] bg-[#0037c7] text-white' : 'border-gray-200 bg-white text-gray-700' }}">Approvals</a>
    <a href="{{ url('/admin/reports/user-login-logs') }}" class="rounded-xl border px-3 py-2 text-sm font-semibold {{ $current === 'access' ? 'border-[#0037c7] bg-[#0037c7] text-white' : 'border-gray-200 bg-white text-gray-700' }}">User access</a>
</nav>
