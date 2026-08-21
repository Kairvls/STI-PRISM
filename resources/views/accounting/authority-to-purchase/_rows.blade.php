@forelse ($records as $row)
    <tr>
        <td class="acc-ref">{{ $row->authority_purchase_form_number }}</td>
        <td class="acc-muted">{{ $row->ris_form_number ?? '—' }}</td>
        <td class="acc-muted">{{ $row->company_name ?? $row->shop_name ?? '—' }}</td>
        <td class="acc-money">{{ $row->atp_total !== null ? '₱'.number_format((float)$row->atp_total, 2) : '—' }}</td>
        <td class="acc-muted">{{ $row->authority_purchase_submitted_at ? \Carbon\Carbon::parse($row->authority_purchase_submitted_at)->format('M d, Y') : '—' }}</td>
        <td>@include('accounting.partials.status-badge', ['status' => $row->authority_purchase_status, 'submitted' => $row->authority_purchase_submitted_at, 'revision' => $row->authority_purchase_rejection_reason])</td>
        <td class="text-right">
            <a
                href="/accounting/authority-to-purchase/{{ $row->authority_purchase_id }}"
                class="icon-btn"
                data-tip="Review ATP"
                aria-label="Review ATP"
            >
                <i data-lucide="eye" class="h-4 w-4"></i>
            </a>
        </td>
    </tr>
@empty
    <tr><td colspan="7"><div class="acc-empty my-2">No ATP records in this queue.</div></td></tr>
@endforelse