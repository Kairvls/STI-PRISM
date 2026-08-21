@forelse ($records as $row)
    @php $when = $row->liquidation_report_submitted_at ?? $row->liquidation_report_date_submitted ?? $row->liquidation_report_created_at; @endphp
    <tr>
        <td class="acc-ref">{{ $row->liquidation_report_form_number ?? ('LIQ-'.$row->liquidation_report_id) }}</td>
        <td class="acc-muted">{{ $row->receiving_report_form_number ?? '—' }}</td>
        <td class="acc-muted">{{ $row->liquidation_report_employee_name }}</td>
        <td class="acc-money">{{ $row->liquidation_report_amount_advance !== null ? '₱'.number_format((float)$row->liquidation_report_amount_advance, 2) : '—' }}</td>
        <td class="acc-muted">{{ $when ? \Carbon\Carbon::parse($when)->format('M d, Y') : '—' }}</td>
        <td>@include('accounting.partials.status-badge', ['status' => $row->liquidation_report_status])</td>
        <td class="text-right">
            <a
                href="/accounting/liquidation-reports/{{ $row->liquidation_report_id }}"
                class="icon-btn"
                data-tip="Review liquidation"
                aria-label="Review liquidation"
            >
                <i data-lucide="eye" class="h-4 w-4"></i>
            </a>
        </td>
    </tr>
@empty
    <tr><td colspan="7"><div class="acc-empty my-2">No liquidation reports in this queue.</div></td></tr>
@endforelse