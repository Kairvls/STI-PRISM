<div class="space-y-3 p-1">
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">Approved ATP waiting for inspection.</p>
        <a href="/receiving/reports" class="text-xs font-semibold text-[#0037c7]">Open full page</a>
    </div>
    <div class="overflow-hidden rounded-xl border border-gray-200">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left">
                <thead class="border-b bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">RIS / ATP</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Items</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Supplier</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Value</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse(($rows ?? collect()) as $row)
                        <tr>
                            <td class="px-4 py-3 text-sm font-semibold">{{ $row->ris_form_number ?: ($row->authority_purchase_form_number ?: 'ATP-'.$row->authority_purchase_id) }}</td>
                            <td class="px-4 py-3 text-sm">{{ \Illuminate\Support\Str::limit($row->item_names ?: '—', 48) }}</td>
                            <td class="px-4 py-3 text-sm">{{ $row->supplier_name }}</td>
                            <td class="px-4 py-3 text-sm">₱{{ number_format((float) ($row->total_amount ?? 0), 2) }}</td>
                            <td class="px-4 py-3 text-sm">{{ ($row->receiving_report_status ?? null) === 'Returned' ? 'Returned' : 'Pending' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-gray-400">No pending receiving reports.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
