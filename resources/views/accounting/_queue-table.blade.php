<div class="acc-table-wrap acc-dash-flush">
    <table class="acc-table">
        <thead>
            <tr>
                <th>Document</th>
                <th>Reference</th>
                <th class="!text-right">Amount</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="queueTableBody" class="acc-animate">
            @forelse ($queue as $item)
                <tr>
                    <td class="whitespace-nowrap">
                        <span class="acc-muted text-xs font-semibold">{{ $item->type }}</span>
                    </td>
                    <td class="min-w-0">
                        <a href="{{ $item->url }}" class="acc-row-link truncate block max-w-[220px]" title="{{ $item->ref }}">
                            {{ $item->ref }}
                        </a>
                    </td>
                    <td class="acc-money whitespace-nowrap !text-right">
                        {{ $item->amount !== null ? '₱'.number_format((float)$item->amount, 2) : '—' }}
                    </td>
                    <td class="whitespace-nowrap">
                        @include('accounting.partials.status-badge', ['status' => $item->status])
                    </td>
                    <td class="whitespace-nowrap text-xs font-medium text-slate-600">
                        {{ $item->action ?? '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-5">
                        <div class="acc-empty">Nothing waiting for Accounting right now.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
