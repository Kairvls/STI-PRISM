@forelse ($records as $row)
    @php
        // Status badges for History: Approved = blue, Funds released = green, Rejected = rose
        $mono = match (true) {
            in_array($row->status, ['Approved', 'Completed'], true) => 'bg-blue-50 text-blue-800 ring-blue-300',
            in_array($row->status, ['Funds released', 'Released'], true) => 'bg-emerald-50 text-emerald-700 ring-emerald-300',
            in_array($row->status, ['Rejected'], true) => 'bg-rose-50 text-rose-800 ring-rose-300',
            default => 'bg-slate-100 text-slate-700 ring-slate-200',
        };
    @endphp
    <tr>
        <td class="acc-muted">{{ $row->type }}</td>
        <td><a class="acc-ref acc-link" href="{{ $row->url }}">{{ $row->ref }}</a></td>
        <td class="acc-muted">{{ $row->related ?? '—' }}</td>
        <td class="acc-money">{{ $row->amount !== null ? '₱'.number_format((float)$row->amount, 2) : '—' }}</td>
        <td>
            <span class="acc-status-badge inline-flex items-center whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-bold leading-tight ring-1 {{ $mono }}">{{ $row->status }}</span>
        </td>
        <td class="acc-muted">{{ $row->when ? \Carbon\Carbon::parse($row->when)->format('M d, Y g:i A') : '—' }}</td>
    </tr>
@empty
    <tr><td colspan="6"><div class="acc-empty my-2">No processed records yet.</div></td></tr>
@endforelse