@extends('layouts.maintenance-layout')

@section('title', $person ? 'Edit '.$person->custodian_full_name : 'Add person')

@section('content')
@php
    $editing = $person !== null;
    $value = fn (string $field, $default = null) => old($field, $editing ? ($person->{$field} ?? $default) : $default);
    $fieldClass = 'h-10 w-full rounded-xl border-0 bg-slate-50 px-3 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10';
    $errorClass = 'ring-rose-300 bg-rose-50/40';
    $selectedRoom = (string) $value('custodian_room_id', $defaultRoomId);
    $selectedReporter = (string) $value('custodian_reporter_id');
    $selectedStatus = $value('custodian_status', \App\Support\Custodians::STATUS_ACTIVE);
    $selectedDepartment = (string) $value('custodian_department_id');
    $nameParts = \App\Support\Custodians::nameParts($person);
@endphp

<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex items-center gap-2 text-sm text-slate-400">
        <a href="{{ \App\Support\PropertyAssignments::url() }}" class="transition hover:text-slate-700">Property Assignment</a>
        <i data-lucide="chevron-right" class="h-4 w-4"></i>
        @if ($editing)
            <a href="{{ \App\Support\PropertyAssignments::route('person', $person->custodian_id) }}" class="transition hover:text-slate-700">{{ $person->custodian_full_name }}</a>
            <i data-lucide="chevron-right" class="h-4 w-4"></i>
            <span class="font-medium text-slate-600">Edit</span>
        @else
            <span class="font-medium text-slate-600">Add person</span>
        @endif
    </div>

    @include('maintenance-personnel.property-assignments._person-form-fields')
</div>
<script>
    // Sync custodian employee id similar to reporters: OMC + 5 digits + suffix (F/S)
    function suffixForCustodianType(type) {
        if (type === 'Faculty') return 'F';
        if (type === 'Staff') return 'S';
        return '';
    }

    let custodianExistsWarningRequest = 0;

    function setCustodianExistsWarning(shouldShow) {
        const hint = document.getElementById('custodianEmployeeExistsHint');
        if (!hint) return;
        hint.classList.toggle('hidden', !shouldShow);
    }

    async function refreshCustodianExistsWarning(employeeId) {
        const requestId = ++custodianExistsWarningRequest;
        if (!employeeId) {
            setCustodianExistsWarning(false);
            return;
        }
        try {
            const res = await fetch(`/get-custodian/${encodeURIComponent(employeeId)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (requestId !== custodianExistsWarningRequest) return;
            if (!res || !res.ok) {
                setCustodianExistsWarning(false);
                return;
            }
            const json = await res.json();
            if (requestId !== custodianExistsWarningRequest) return;
            setCustodianExistsWarning(!!(json && json.exists === true && Number(json.custodian_id) > 0));
        } catch (e) {
            if (requestId === custodianExistsWarningRequest) {
                setCustodianExistsWarning(false);
            }
        }
    }

    function syncCustodianEmployeeId() {
        const typeEl = document.getElementById('custodian-employment-type');
        const digitsEl = document.getElementById('custodianEmployeeDigits');
        const fullEl = document.getElementById('custodian_employee_id');
        const suffixEl = document.getElementById('custodianEmployeeIdSuffix');
        const fieldEl = document.getElementById('custodianEmployeeIdField');
        const hintEl = document.getElementById('custodianEmployeeHint');
        if (!typeEl || !digitsEl || !fullEl || !suffixEl || !fieldEl) return '';

        const type = typeEl.value || '';
        const suffix = suffixForCustodianType(type);
        const digits = String(digitsEl.value || '').replace(/\D/g, '').slice(0,5);
        digitsEl.value = digits;
        suffixEl.textContent = suffix || '?';
        suffixEl.classList.toggle('is-empty', !suffix);

        if (type) {
            fieldEl.classList.remove('is-disabled');
            try {
                digitsEl.readOnly = false;
                digitsEl.disabled = false;
                digitsEl.removeAttribute('readonly');
                digitsEl.removeAttribute('disabled');
                digitsEl.tabIndex = 0;
            } catch (e) { }
            if (hintEl) hintEl.textContent = 'Enter the 5-digit number only. OMC and ' + suffix + ' are fixed.';
        } else {
            fieldEl.classList.add('is-disabled');
            try {
                digitsEl.readOnly = true;
                digitsEl.disabled = true;
                digitsEl.setAttribute('readonly', 'readonly');
                digitsEl.setAttribute('disabled', 'disabled');
                digitsEl.tabIndex = -1;
            } catch (e) { }
            if (hintEl) hintEl.textContent = 'Select type first, then enter the 5-digit number.';
        }

        const fullId = (type && digits.length === 5 && suffix) ? ('OMC' + digits + suffix) : '';
        fullEl.value = fullId;
        setCustodianExistsWarning(false);
        refreshCustodianExistsWarning(fullId);
        return fullId;
    }

    ['custodian-employment-type', 'custodianEmployeeDigits'].forEach((id) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener(id === 'custodian-employment-type' ? 'change' : 'input', () => syncCustodianEmployeeId());
    });

    // Make clicking the whole field focus the digits input (helpful in modals)
    (function () {
        const field = document.getElementById('custodianEmployeeIdField');
        const digits = document.getElementById('custodianEmployeeDigits');
        if (!field || !digits) return;
        field.addEventListener('click', function (ev) {
            try { digits.focus(); } catch (e) { }
        });
    })();

    // Initialize
    syncCustodianEmployeeId();
</script>
@endsection
