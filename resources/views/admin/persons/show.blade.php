@extends('layouts.admin-layout')

@section('title', $person->person_full_name)

@section('content')
@php
    $viewOnly = (bool) ($viewOnly ?? false);
    $fieldClass = 'h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100';
@endphp

<div class="space-y-6">
    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">{{ session('error') }}</div>
    @endif

    <div>
        @php
            $personBack = $personBack ?? ['url' => route('admin.persons.index'), 'label' => 'All faculty & staff'];
        @endphp
        <a href="{{ $personBack['url'] }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#0025cc] hover:underline">
            <i data-lucide="chevron-left" class="h-4 w-4"></i>
            {{ $personBack['label'] }}
        </a>
        <h1 class="mt-2 text-xl font-semibold text-slate-950">{{ $person->person_full_name }}</h1>
        <p class="mt-1 font-mono text-sm text-slate-500">{{ $person->person_employee_id }}</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-5">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-3">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Identity</h2>
                @if ($viewOnly)
                    <p class="mt-0.5 text-xs text-slate-500">View only — the Administrator manages faculty and staff records.</p>
                @endif
            </div>
            @if ($viewOnly)
                @php
                    $na = static fn ($v) => filled($v) ? $v : '—';
                    $contactDisplay = \App\Support\PhoneNumber::formatForDisplay($person->person_contact_number ?? null);
                    $statusActive = ($person->person_status ?? '') === 'Active';
                @endphp
                <div class="space-y-6 p-5">
                    <section>
                        <p class="mb-3 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                            <i data-lucide="briefcase" class="h-3.5 w-3.5"></i> Employment
                        </p>
                        <dl class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Type</dt>
                                <dd class="mt-0.5 text-sm font-semibold text-slate-900">{{ $na($person->person_employment_type) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Employee ID</dt>
                                <dd class="mt-0.5 font-mono text-sm font-semibold tracking-wide text-slate-900">{{ $na($person->person_employee_id) }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section>
                        <p class="mb-3 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                            <i data-lucide="id-card" class="h-3.5 w-3.5"></i> Name
                        </p>
                        <dl class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-3">
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">First name</dt>
                                <dd class="mt-0.5 text-sm font-semibold text-slate-900">{{ $na($person->person_first_name) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Middle name</dt>
                                <dd class="mt-0.5 text-sm font-semibold text-slate-900">{{ $na($person->person_middle_name) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Last name</dt>
                                <dd class="mt-0.5 text-sm font-semibold text-slate-900">{{ $na($person->person_last_name) }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section>
                        <p class="mb-3 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                            <i data-lucide="building-2" class="h-3.5 w-3.5"></i> Office
                        </p>
                        <dl class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Employment basis</dt>
                                <dd class="mt-0.5 text-sm font-semibold text-slate-900">{{ $na($person->person_employment_basis) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Department</dt>
                                <dd class="mt-0.5 text-sm font-semibold text-slate-900">{{ $na($person->department_name ?? null) }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section>
                        <p class="mb-3 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                            <i data-lucide="mail" class="h-3.5 w-3.5"></i> Contact
                        </p>
                        <dl class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Email</dt>
                                <dd class="mt-0.5 break-all text-sm font-semibold text-slate-900">{{ $na($person->person_email_address) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Contact number</dt>
                                <dd class="mt-0.5 text-sm font-semibold text-slate-900">{{ $na($contactDisplay) }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section>
                        <p class="mb-3 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                            <i data-lucide="toggle-left" class="h-3.5 w-3.5"></i> Status
                        </p>
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $statusActive ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-slate-100 text-slate-600 ring-slate-200' }}">
                            {{ $na($person->person_status) }}
                        </span>
                    </section>
                </div>
            @else
                <form method="POST" action="{{ route('admin.persons.update', $person->person_id) }}" class="space-y-6 p-5">
                    @csrf
                    @method('PUT')
                    @if (request()->filled('return'))
                        <input type="hidden" name="return" value="{{ request('return') }}">
                    @endif
                    @include('admin.persons._form-fields', ['person' => $person, 'departments' => $departments, 'fieldClass' => $fieldClass, 'idPrefix' => 'edit'])
                    <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white">Save changes</button>
                </form>
            @endif
        </div>

        <div class="space-y-4 lg:col-span-2">
            @unless ($viewOnly)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-950">Linked modules</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    <li class="flex items-center justify-between gap-2">
                        <span class="text-slate-600">Reporter</span>
                        @if ($reporterUrl)
                            <a href="{{ $reporterUrl }}" class="font-semibold text-[#0025cc] hover:underline">Open</a>
                        @else
                            <span class="text-slate-400">Not linked</span>
                        @endif
                    </li>
                    <li class="flex items-center justify-between gap-2">
                        <span class="text-slate-600">Property person</span>
                        @if ($custodianUrl)
                            <a href="{{ $custodianUrl }}" class="font-semibold text-[#0025cc] hover:underline">Open</a>
                        @else
                            <span class="text-slate-400">Not linked</span>
                        @endif
                    </li>
                    <li class="flex items-center justify-between gap-2">
                        <span class="text-slate-600">System user</span>
                        @if ($userUrl)
                            <a href="{{ $userUrl }}" class="font-semibold text-[#0025cc] hover:underline">Open</a>
                        @else
                            <span class="text-slate-400">Not linked</span>
                        @endif
                    </li>
                </ul>
            </div>
            @endunless

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                @php
                    $latestReports = $reports->take(6);
                    $moreReports = $reports->slice(6)->values();
                @endphp
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">Reports filed</h2>
                        <p class="mt-0.5 text-xs text-slate-500">
                            {{ $reportsTotal }} {{ \Illuminate\Support\Str::plural('report', $reportsTotal) }} on file
                        </p>
                    </div>
                    @if (!empty($reporterHistoryUrl) && $reportsTotal > 0)
                        <a href="{{ $reporterHistoryUrl }}" class="shrink-0 text-xs font-semibold text-[#0025cc] hover:underline">View all</a>
                    @endif
                </div>

                @if ($reports->isEmpty())
                    <p class="mt-4 text-sm text-slate-500">No damage or maintenance reports filed by this person.</p>
                @else
                    @php
                        $renderPersonReport = function ($report) use ($reporterHistoryUrl) {
                            $equipmentLabel = $report->equipment_name
                                ?: ($report->report_unlisted_equipment_name ?: 'No equipment');
                            $statusClass = match ($report->report_current_status) {
                                'Pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                'Processing' => 'bg-blue-50 text-blue-700 ring-blue-200',
                                'Resolved' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                'For Replacement' => 'bg-violet-50 text-violet-700 ring-violet-200',
                                'Rejected' => 'bg-rose-50 text-rose-700 ring-rose-200',
                                default => 'bg-slate-100 text-slate-600 ring-slate-200',
                            };
                            return compact('equipmentLabel', 'statusClass');
                        };
                    @endphp
                    <ul class="mt-4 divide-y divide-slate-100">
                        @foreach ($latestReports as $report)
                            @php extract($renderPersonReport($report)); @endphp
                            <li class="py-3 first:pt-0 last:pb-0">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        @if (!empty($reporterHistoryUrl))
                                            <a href="{{ $reporterHistoryUrl }}" class="text-sm font-semibold text-[#0025cc] hover:underline">
                                                {{ \App\Support\ReportGrouping::ticketCode($report) }} · {{ $equipmentLabel }}
                                            </a>
                                        @else
                                            <p class="text-sm font-semibold text-slate-900">{{ \App\Support\ReportGrouping::ticketCode($report) }} · {{ $equipmentLabel }}</p>
                                        @endif
                                        <p class="mt-0.5 truncate text-xs text-slate-500">
                                            {{ $report->room_name ?: 'No room' }}
                                            @if ($report->report_suggested_issue)
                                                · {{ $report->report_suggested_issue }}
                                            @endif
                                        </p>
                                        <p class="mt-0.5 text-[11px] text-slate-400">
                                            {{ filled($report->report_submitted_at) ? \Carbon\Carbon::parse($report->report_submitted_at)->format('M d, Y') : '—' }}
                                            · {{ $report->report_urgency_level ?: '—' }}
                                        </p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $statusClass }}">
                                        {{ $report->report_current_status }}
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    @if ($moreReports->isNotEmpty())
                        <details class="group mt-2 border-t border-slate-100 pt-2">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 rounded-lg px-1 py-2 text-xs font-semibold text-[#0025cc] hover:bg-slate-50">
                                <span>Show {{ $moreReports->count() }} more on this page</span>
                                <i data-lucide="chevron-down" class="h-3.5 w-3.5 transition group-open:rotate-180"></i>
                            </summary>
                            <ul class="mt-1 divide-y divide-slate-100">
                                @foreach ($moreReports as $report)
                                    @php extract($renderPersonReport($report)); @endphp
                                    <li class="py-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                @if (!empty($reporterHistoryUrl))
                                                    <a href="{{ $reporterHistoryUrl }}" class="text-sm font-semibold text-[#0025cc] hover:underline">
                                                        {{ \App\Support\ReportGrouping::ticketCode($report) }} · {{ $equipmentLabel }}
                                                    </a>
                                                @else
                                                    <p class="text-sm font-semibold text-slate-900">{{ \App\Support\ReportGrouping::ticketCode($report) }} · {{ $equipmentLabel }}</p>
                                                @endif
                                                <p class="mt-0.5 truncate text-xs text-slate-500">
                                                    {{ $report->room_name ?: 'No room' }}
                                                    @if ($report->report_suggested_issue)
                                                        · {{ $report->report_suggested_issue }}
                                                    @endif
                                                </p>
                                                <p class="mt-0.5 text-[11px] text-slate-400">
                                                    {{ filled($report->report_submitted_at) ? \Carbon\Carbon::parse($report->report_submitted_at)->format('M d, Y') : '—' }}
                                                    · {{ $report->report_urgency_level ?: '—' }}
                                                </p>
                                            </div>
                                            <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $statusClass }}">
                                                {{ $report->report_current_status }}
                                            </span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                            @if ($reportsTotal > $reports->count() && !empty($reporterHistoryUrl))
                                <p class="mt-2 px-1 text-xs text-slate-400">
                                    {{ $reportsTotal - $reports->count() }} older
                                    {{ \Illuminate\Support\Str::plural('report', $reportsTotal - $reports->count()) }} —
                                    <a href="{{ $reporterHistoryUrl }}" class="font-semibold text-[#0025cc] hover:underline">open full history</a>
                                </p>
                            @endif
                        </details>
                    @elseif ($reportsTotal > $reports->count() && !empty($reporterHistoryUrl))
                        <p class="mt-3 text-xs text-slate-400">
                            Showing latest {{ $reports->count() }} of {{ $reportsTotal }} —
                            <a href="{{ $reporterHistoryUrl }}" class="font-semibold text-[#0025cc] hover:underline">open full history</a>
                        </p>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="text-base font-semibold text-slate-950">Equipment held</h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    @if ($heldEquipment->isEmpty())
                        No active property assigned to this person.
                    @else
                        {{ $heldEquipment->count() }} {{ \Illuminate\Support\Str::plural('item', $heldEquipment->count()) }} currently assigned
                    @endif
                </p>
            </div>
            @if ($custodianUrl && $heldEquipment->isNotEmpty())
                <a href="{{ $custodianUrl }}" class="text-xs font-semibold text-[#0025cc] hover:underline">Open property record</a>
            @endif
        </div>

        @if ($heldEquipment->isEmpty())
            <p class="px-5 py-10 text-center text-sm text-slate-500">This person is not holding any equipment right now.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Item</th>
                            <th class="px-5 py-3">Asset tag / serial</th>
                            <th class="px-5 py-3">Room</th>
                            <th class="px-5 py-3">Condition</th>
                            <th class="px-5 py-3">Since</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($heldEquipment as $item)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-5 py-3">
                                    @php
                                        $heldEquipmentUrl = $viewOnly
                                            ? route('school-admin.operations.equipment.show', (int) $item->equipment_id)
                                                .(request()->filled('return') ? '?return='.rawurlencode((string) request('return')) : '')
                                            : \App\Support\EquipmentViewReturn::viewUrl((int) $item->equipment_id);
                                    @endphp
                                    <a href="{{ $heldEquipmentUrl }}" class="font-semibold text-[#0025cc] hover:underline">
                                        {{ $item->equipment_name }}
                                    </a>
                                    <p class="text-xs text-slate-500">{{ implode(' · ', array_filter([$item->equipment_category_name ?? null, $item->equipment_brand_name ?? null, $item->equipment_model ?? null])) ?: '—' }}</p>
                                </td>
                                <td class="px-5 py-3 font-mono text-xs text-slate-600">
                                    {{ $item->equipment_asset_tag ?: '—' }}
                                    <br>
                                    <span class="text-slate-400">{{ $item->equipment_serial_number ?: '—' }}</span>
                                </td>
                                <td class="px-5 py-3 text-slate-700">{{ $item->current_room_name ?: '—' }}</td>
                                <td class="px-5 py-3 text-slate-700">{{ $item->equipment_condition_status ?: '—' }}</td>
                                <td class="px-5 py-3 text-slate-700">
                                    {{ filled($item->assignment_issued_at) ? \Carbon\Carbon::parse($item->assignment_issued_at)->format('M d, Y') : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
<script>
function syncPersonEmployeeId(prefix) {
    const typeEl = document.getElementById(prefix + 'Type');
    const digitsEl = document.getElementById(prefix + 'EmployeeDigits');
    const fullEl = document.getElementById(prefix + 'EmployeeId');
    const suffixEl = document.getElementById(prefix + 'EmployeeIdSuffix');
    const fieldEl = document.getElementById(prefix + 'EmployeeIdField');
    const hint = document.getElementById(prefix + 'EmployeeIdHint');
    if (!typeEl || !digitsEl || !fullEl || !suffixEl || !fieldEl) return;

    const type = typeEl.value;
    const suffix = type === 'Staff' ? 'S' : (type === 'Faculty' ? 'F' : '');
    digitsEl.value = String(digitsEl.value || '').replace(/\D/g, '').slice(0, 5);

    if (!suffix) {
        fieldEl.classList.add('is-disabled');
        digitsEl.readOnly = true;
        suffixEl.textContent = '?';
        suffixEl.classList.add('is-empty');
        fullEl.value = '';
        if (hint) hint.textContent = 'Select type first, then enter the 5-digit number.';
        return;
    }

    fieldEl.classList.remove('is-disabled');
    digitsEl.readOnly = false;
    suffixEl.textContent = suffix;
    suffixEl.classList.remove('is-empty');
    fullEl.value = digitsEl.value.length === 5 ? ('OMC' + digitsEl.value + suffix) : '';
    if (hint) hint.textContent = 'Enter the 5-digit number only. OMC and ' + suffix + ' are fixed.';
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-person-emp-sync]').forEach((el) => {
        const prefix = el.getAttribute('data-person-emp-prefix') || 'edit';
        const evt = el.getAttribute('data-person-emp-sync') === 'type' ? 'change' : 'input';
        el.addEventListener(evt, () => syncPersonEmployeeId(prefix));
    });
    syncPersonEmployeeId('edit');
});
</script>
@endsection
