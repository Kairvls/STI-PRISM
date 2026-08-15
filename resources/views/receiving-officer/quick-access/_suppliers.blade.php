<div class="space-y-3 p-1">
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">Suppliers linked to purchases and accepted deliveries.</p>
        <a href="/receiving/supplier-records" class="text-xs font-semibold text-[#0037c7]">Open full page</a>
    </div>
    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
        @forelse(($rows ?? collect()) as $supplier)
            <div class="rounded-xl border border-gray-200 px-4 py-4">
                <p class="text-sm font-semibold text-gray-900">{{ $supplier->supplier_name }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ $supplier->supplier_store_type }} · {{ $supplier->delivery_count }} accepted</p>
                <p class="mt-2 text-xs text-gray-600">{{ $supplier->contact_person ?: 'No contact' }} {{ $supplier->contact_number ? '· '.$supplier->contact_number : '' }}</p>
            </div>
        @empty
            <p class="col-span-2 py-12 text-center text-sm text-gray-400">No supplier records yet.</p>
        @endforelse
    </div>
</div>
