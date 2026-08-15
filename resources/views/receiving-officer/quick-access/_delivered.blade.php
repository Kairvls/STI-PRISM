<div class="space-y-3 p-1">
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">Accepted deliveries added to inventory.</p>
        <a href="/receiving/delivered-items" class="text-xs font-semibold text-[#0037c7]">Open full page</a>
    </div>
    <div class="overflow-hidden rounded-xl border border-gray-200">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left">
                <thead class="border-b bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">RIS / ATP</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Items</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Supplier</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Received</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Officer</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse(($rows ?? collect()) as $row)
                        <tr>
                            <td class="px-4 py-3 text-sm font-semibold">{{ $row->ris_form_number ?: $row->authority_purchase_form_number }}</td>
                            <td class="px-4 py-3 text-sm">{{ \Illuminate\Support\Str::limit($row->item_names ?: '—', 48) }}</td>
                            <td class="px-4 py-3 text-sm">{{ $row->supplier_name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $row->received_at ? \Carbon\Carbon::parse($row->received_at)->format('M d, Y') : '—' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $row->officer_name ?: 'Receiving Officer' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-gray-400">No delivered items yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
