@forelse ($records as $row)
    @php
        $when = $row->liquidation_report_submitted_at ?? $row->liquidation_report_date_submitted ?? $row->liquidation_report_created_at;
        $deadline = $row->liquidation_report_submission_deadline ?? null;
        $deadlineQs = !empty($deadlineFilter ?? null) ? '&deadline=' . urlencode($deadlineFilter) : '';
    @endphp
    <tr class="transition hover:bg-gray-50/70">
        <td class="px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                    <i data-lucide="receipt" class="h-4 w-4"></i>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-semibold text-gray-900">
                            {{ $row->liquidation_report_form_number ?? ('LIQ-'.$row->liquidation_report_id) }}
                        </p>
                        @include('accounting.partials.deadline-badge', ['deadline' => $deadline])
                    </div>
                    <p class="mt-0.5 text-xs text-gray-400">Record #{{ $row->liquidation_report_id }}</p>
                </div>
            </div>
        </td>
        <td class="px-5 py-4 text-gray-700">{{ $row->receiving_report_form_number ?? '—' }}</td>
        <td class="px-5 py-4 text-gray-600">{{ $row->liquidation_report_employee_name ?? '—' }}</td>
        <td class="whitespace-nowrap px-5 py-4 text-right font-semibold tabular-nums text-gray-900">
            {{ $row->liquidation_report_amount_advance !== null ? '₱'.number_format((float) $row->liquidation_report_amount_advance, 2) : '—' }}
        </td>
        <td class="whitespace-nowrap px-5 py-4 text-gray-600">
            <div>{{ $when ? \Carbon\Carbon::parse($when)->format('M d, Y') : '—' }}</div>
            @if ($deadline)
                <div class="mt-0.5 text-[10px] font-medium text-gray-400">
                    Deadline {{ \Carbon\Carbon::parse($deadline)->format('M d, Y') }}
                </div>
            @endif
        </td>
        <td class="px-5 py-4">
            @include('accounting.partials.status-badge', ['status' => $row->liquidation_report_status])
        </td>
        <td class="px-5 py-4 text-right">
            <x-view-action-button
                :href="'/accounting/liquidation-reports/'.$row->liquidation_report_id.'?return_status='.urlencode($filter ?? 'incoming').$deadlineQs"
                label="View"
                title="Review liquidation"
                aria-label="Review liquidation"
                data-tip="Review liquidation"
            />
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7">
            <div class="pur-empty my-2">No liquidation reports in this queue.</div>
        </td>
    </tr>
@endforelse
