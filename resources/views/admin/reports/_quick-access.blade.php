@php
    $m = $maintenance ?? [];
    $r = $receiving ?? [];
    $a = $approvals ?? [];
    $u = $access ?? [];
@endphp

<div class="space-y-4 p-1">
    <div class="flex flex-wrap gap-2">
        <button type="button" class="qa-report-tab rounded-xl border border-slate-700 bg-slate-700 px-3 py-2 text-sm font-semibold text-white" data-pane="qa-pane-maintenance" onclick="window.switchQaReportTab(this)">Maintenance</button>
        <button type="button" class="qa-report-tab rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700" data-pane="qa-pane-receiving" onclick="window.switchQaReportTab(this)">Receiving</button>
        <button type="button" class="qa-report-tab rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700" data-pane="qa-pane-approvals" onclick="window.switchQaReportTab(this)">Approvals</button>
        <button type="button" class="qa-report-tab rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700" data-pane="qa-pane-access" onclick="window.switchQaReportTab(this)">User access</button>
    </div>

    <div id="qa-pane-maintenance" class="qa-report-pane space-y-4">
        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            <div class="rounded-xl border border-gray-200 px-4 py-3"><p class="text-xs font-semibold uppercase text-gray-500">Filed</p><p class="mt-1 font-['Outfit'] text-2xl font-bold">{{ $m['filed'] ?? 0 }}</p></div>
            <div class="rounded-xl border border-gray-200 px-4 py-3"><p class="text-xs font-semibold uppercase text-gray-500">Resolved</p><p class="mt-1 font-['Outfit'] text-2xl font-bold text-slate-700">{{ $m['resolved'] ?? 0 }}</p></div>
            <div class="rounded-xl border border-gray-200 px-4 py-3"><p class="text-xs font-semibold uppercase text-gray-500">Rejected</p><p class="mt-1 font-['Outfit'] text-2xl font-bold text-slate-600">{{ $m['rejected'] ?? 0 }}</p></div>
            <div class="rounded-xl border border-gray-200 px-4 py-3"><p class="text-xs font-semibold uppercase text-gray-500">Replacement</p><p class="mt-1 font-['Outfit'] text-2xl font-bold text-slate-600">{{ $m['replacement'] ?? 0 }}</p></div>
        </div>
        <div class="overflow-hidden rounded-xl border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left">
                    <thead class="border-b bg-gray-50"><tr>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">#</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Equipment</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Location</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Urgency</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Status</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Technician</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse(($m['rows'] ?? collect()) as $row)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $row->report_id }}</td>
                                <td class="px-4 py-3 text-sm font-semibold">{{ $row->equipment_name ?: ($row->report_unlisted_equipment_name ?: ($row->report_suggested_issue ?: '—')) }}</td>
                                <td class="px-4 py-3 text-sm">{{ trim(($row->building_name ? $row->building_name.' · ' : '').($row->room_name ?: '')) ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm">{{ $row->report_urgency_level }}</td>
                                <td class="px-4 py-3 text-sm">{{ $row->report_current_status }}</td>
                                <td class="px-4 py-3 text-sm">{{ $row->technician_name ?: 'Unassigned' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-gray-400">No records in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="qa-pane-receiving" class="qa-report-pane space-y-4 hidden">
        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            <div class="rounded-xl border border-gray-200 px-4 py-3"><p class="text-xs font-semibold uppercase text-gray-500">Delivered</p><p class="mt-1 font-['Outfit'] text-2xl font-bold text-slate-700">{{ $r['accepted'] ?? 0 }}</p></div>
            <div class="rounded-xl border border-gray-200 px-4 py-3"><p class="text-xs font-semibold uppercase text-gray-500">Returned</p><p class="mt-1 font-['Outfit'] text-2xl font-bold text-slate-600">{{ $r['returned'] ?? 0 }}</p></div>
            <div class="rounded-xl border border-gray-200 px-4 py-3"><p class="text-xs font-semibold uppercase text-gray-500">With OR</p><p class="mt-1 font-['Outfit'] text-2xl font-bold">{{ $r['withOr'] ?? 0 }}</p></div>
            <div class="rounded-xl border border-gray-200 px-4 py-3"><p class="text-xs font-semibold uppercase text-gray-500">Inventory lines</p><p class="mt-1 font-['Outfit'] text-2xl font-bold">{{ $r['inventoryLines'] ?? 0 }}</p></div>
        </div>
        <div class="overflow-hidden rounded-xl border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px] text-left">
                    <thead class="border-b bg-gray-50"><tr>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">ID</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Supplier</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">OR</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Status</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Date</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse(($r['rows'] ?? collect()) as $row)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $row->receiving_report_id }}</td>
                                <td class="px-4 py-3 text-sm font-semibold">{{ $row->supplier_name }}</td>
                                <td class="px-4 py-3 text-sm">{{ $row->receiving_report_invoice_no ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm">{{ $row->receiving_report_status }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ \Carbon\Carbon::parse($row->receiving_report_date ?: $row->receiving_report_created_at)->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-gray-400">No records in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="qa-pane-approvals" class="qa-report-pane hidden">
        <div class="overflow-hidden rounded-xl border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left">
                    <thead class="border-b bg-gray-50"><tr>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">When</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Officer</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Record</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Decision</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Remarks</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse(($a['rows'] ?? collect()) as $row)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $row->approval_log_approved_at ? \Carbon\Carbon::parse($row->approval_log_approved_at)->format('M d, Y g:i A') : '—' }}</td>
                                <td class="px-4 py-3 text-sm font-semibold">{{ $row->officer_name ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm">{{ $row->approval_log_reference_type }} #{{ $row->approval_log_reference_id }}</td>
                                <td class="px-4 py-3 text-sm">{{ $row->approval_log_approval_status }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ \Illuminate\Support\Str::limit($row->approval_log_approval_remarks ?: '—', 80) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-gray-400">No records in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="qa-pane-access" class="qa-report-pane hidden">
        <div class="overflow-hidden rounded-xl border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px] text-left">
                    <thead class="border-b bg-gray-50"><tr>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">User</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Username</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Role</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Last seen</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse(($u['users'] ?? collect()) as $user)
                            <tr>
                                <td class="px-4 py-3 text-sm font-semibold">{{ $user->user_full_name }}</td>
                                <td class="px-4 py-3 text-sm">{{ $user->user_username }}</td>
                                <td class="px-4 py-3 text-sm">{{ $user->role_name ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    @if(!empty($user->last_activity))
                                        {{ \Carbon\Carbon::createFromTimestamp((int) $user->last_activity)->format('M d, Y g:i A') }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-12 text-center text-sm text-gray-400">No records in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
