@forelse ($records as $row)
    @php
        $st = $row->request_check_status;
        if (!empty($row->request_check_funds_released_at)) { $st = 'Released'; }
        $when = $row->request_check_submitted_at ?? $row->request_check_date ?? $row->request_check_created_at;
    @endphp
    <tr>
        <td class="acc-ref">{{ $row->request_check_form_number ?? ('RFC-'.$row->request_check_id) }}</td>
        <td class="acc-muted">{{ $row->authority_purchase_form_number ?? '—' }}</td>
        <td class="acc-muted">{{ $row->ris_form_number ?? '—' }}</td>
        <td class="acc-muted">{{ $row->request_check_payee }}</td>
        <td class="acc-money">{{ $row->request_check_amount_figures !== null ? '₱'.number_format((float)$row->request_check_amount_figures, 2) : '—' }}</td>
        <td class="acc-muted">{{ $when ? \Carbon\Carbon::parse($when)->format('M d, Y') : '—' }}</td>
        <td>@include('accounting.partials.status-badge', ['status' => $st])</td>
        <td class="text-right">
            @php
                $reviewTip = ($st === 'Released' || $st === 'Approved') ? 'View request check' : 'Review request check';
            @endphp
            <a
                href="/accounting/request-check/{{ $row->request_check_id }}?return_status={{ urlencode($filter ?? 'incoming') }}"
                class="icon-btn"
                data-tip="{{ $reviewTip }}"
                aria-label="{{ $reviewTip }}"
            >
                <i data-lucide="eye" class="h-4 w-4"></i>
            </a>
        </td>
    </tr>
@empty
    <tr><td colspan="8"><div class="acc-empty my-2">No Request Check records in this queue.</div></td></tr>
@endforelse
