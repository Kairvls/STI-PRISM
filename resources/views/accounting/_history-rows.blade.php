@forelse ($records as $row)
    @php
        $mono = match (true) {
            in_array($row->status, ['Approved', 'Completed'], true) => 'bg-blue-50 text-blue-800 ring-blue-300',
            in_array($row->status, ['Funds released', 'Released'], true) => 'bg-emerald-50 text-emerald-700 ring-emerald-300',
            in_array($row->status, ['Rejected'], true) => 'bg-rose-50 text-rose-800 ring-rose-300',
            default => 'bg-slate-100 text-slate-700 ring-slate-200',
        };
        $typeIcon = match ($row->type) {
            'ATP' => 'file-check-2',
            'Request Check' => 'clipboard-list',
            'Liquidation' => 'receipt',
            default => 'file-text',
        };
    @endphp
    <tr class="transition hover:bg-gray-50/70">
        <td class="px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                    <i data-lucide="{{ $typeIcon }}" class="h-4 w-4"></i>
                </div>
                <span class="text-sm font-medium text-gray-700">{{ $row->type }}</span>
            </div>
        </td>
        <td class="px-5 py-4">
            <a href="{{ $row->url }}" class="font-semibold text-gray-900 transition hover:text-[#0025cc]">
                {{ $row->ref }}
            </a>
        </td>
        <td class="px-5 py-4 text-gray-600">{{ $row->related ?? '—' }}</td>
        <td class="whitespace-nowrap px-5 py-4 text-right font-semibold tabular-nums text-gray-900">
            {{ $row->amount !== null ? '₱'.number_format((float) $row->amount, 2) : '—' }}
        </td>
        <td class="px-5 py-4">
            <span class="inline-flex items-center whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-bold leading-tight ring-1 {{ $mono }}">
                {{ $row->status }}
            </span>
        </td>
        <td class="whitespace-nowrap px-5 py-4 text-gray-600">
            {{ $row->when ? \Carbon\Carbon::parse($row->when)->format('M d, Y g:i A') : '—' }}
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6">
            <div class="pur-empty my-2">No processed records yet.</div>
        </td>
    </tr>
@endforelse
