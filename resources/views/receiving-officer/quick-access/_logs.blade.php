<div class="space-y-3 p-1">
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">Inspection, accept, and return audit trail.</p>
        <a href="/receiving/logs" class="text-xs font-semibold text-[#0037c7]">Open full page</a>
    </div>
    <div class="overflow-hidden rounded-xl border border-gray-200">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px] text-left">
                <thead class="border-b bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Timestamp</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Action</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Reference</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Officer</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse(($rows ?? collect()) as $log)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $log->receiving_log_created_at ? \Carbon\Carbon::parse($log->receiving_log_created_at)->format('M d, Y g:i A') : '—' }}</td>
                            <td class="px-4 py-3 text-sm font-semibold">{{ $log->receiving_log_action }}</td>
                            <td class="px-4 py-3 text-sm">{{ $log->ris_form_number ?: ($log->authority_purchase_form_number ?: '—') }}</td>
                            <td class="px-4 py-3 text-sm">{{ $log->officer_name ?: 'Receiving Officer' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-12 text-center text-sm text-gray-400">No receiving logs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
