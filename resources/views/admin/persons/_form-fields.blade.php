@php
    $fieldClass = $fieldClass ?? 'h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10';
    $labelClass = $labelClass ?? 'mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500';
    $sectionClass = $sectionClass ?? 'mb-3 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400';
    $person = $person ?? null;
    $prefix = $idPrefix ?? ($person ? 'edit' : 'create');
    $fullEmp = old('employee_id', $person->person_employee_id ?? '');
    $empDigits = '';
    $empSuffix = '';
    $selectedType = old('type', $person->person_employment_type ?? '');
    if (preg_match('/^OMC(\d{1,5})([FS])$/i', (string) $fullEmp, $m)) {
        $empDigits = $m[1];
        $empSuffix = strtoupper($m[2]);
    } elseif ($selectedType === 'Faculty') {
        $empSuffix = 'F';
    } elseif ($selectedType === 'Staff') {
        $empSuffix = 'S';
    }
@endphp

<style>
    .person-employee-id-field {
        display: flex;
        align-items: stretch;
        height: 2.75rem;
        overflow: hidden;
        border-radius: 0.75rem;
        background: #f8fafc;
        box-shadow: inset 0 0 0 1px rgba(226, 232, 240, 0.9);
        transition: background 0.15s ease, box-shadow 0.15s ease;
    }
    .person-employee-id-field:focus-within {
        background: #fff;
        box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.1);
    }
    .person-employee-id-field.is-disabled { opacity: 0.85; }
    .person-employee-id-prefix,
    .person-employee-id-suffix {
        display: inline-flex;
        align-items: center;
        font-size: 0.875rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        color: #64748b;
        user-select: none;
    }
    .person-employee-id-prefix { padding-left: 0.875rem; }
    .person-employee-id-suffix {
        min-width: 2rem;
        justify-content: center;
        padding-right: 0.875rem;
    }
    .person-employee-id-suffix.is-empty { color: #94a3b8; }
    .person-employee-id-field input {
        flex: 1;
        min-width: 0;
        border: 0;
        background: transparent;
        padding: 0 0.5rem;
        text-align: center;
        font-size: 0.875rem;
        font-weight: 600;
        letter-spacing: 0.12em;
        color: #0f172a;
        outline: none;
    }
    .person-employee-id-field.is-disabled input {
        pointer-events: none;
        color: #94a3b8;
    }
</style>

<section>
    <p class="{{ $sectionClass }}"><i data-lucide="briefcase" class="h-3.5 w-3.5"></i> Employment</p>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="{{ $prefix }}Type" class="{{ $labelClass }}">Type <span class="text-red-500">*</span></label>
            <select
                id="{{ $prefix }}Type"
                name="type"
                required
                class="{{ $fieldClass }}"
                data-person-emp-sync="type"
                data-person-emp-prefix="{{ $prefix }}"
            >
                <option value="">Select type</option>
                <option value="Faculty" @selected($selectedType === 'Faculty')>Faculty</option>
                <option value="Staff" @selected($selectedType === 'Staff')>Staff</option>
            </select>
        </div>
        <div>
            <label for="{{ $prefix }}EmployeeDigits" class="{{ $labelClass }}">Employee ID <span class="text-red-500">*</span></label>
            <div
                id="{{ $prefix }}EmployeeIdField"
                class="person-employee-id-field {{ $empSuffix ? '' : 'is-disabled' }}"
            >
                <span class="person-employee-id-prefix">OMC</span>
                <input
                    id="{{ $prefix }}EmployeeDigits"
                    type="text"
                    inputmode="numeric"
                    maxlength="5"
                    placeholder="00127"
                    autocomplete="off"
                    value="{{ $empDigits }}"
                    data-person-emp-sync="digits"
                    data-person-emp-prefix="{{ $prefix }}"
                    {{ $empSuffix ? '' : 'readonly' }}
                >
                <span
                    id="{{ $prefix }}EmployeeIdSuffix"
                    class="person-employee-id-suffix {{ $empSuffix ? '' : 'is-empty' }}"
                >{{ $empSuffix ?: '?' }}</span>
            </div>
            <input type="hidden" id="{{ $prefix }}EmployeeId" name="employee_id" value="{{ $fullEmp }}" required>
            <p id="{{ $prefix }}EmployeeIdHint" class="mt-1 text-xs text-slate-400">
                {{ $empSuffix
                    ? 'Enter the 5-digit number only. OMC and '.$empSuffix.' are fixed.'
                    : 'Select type first, then enter the 5-digit number.' }}
            </p>
        </div>
    </div>
</section>

<section>
    <p class="{{ $sectionClass }}"><i data-lucide="id-card" class="h-3.5 w-3.5"></i> Name</p>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label for="{{ $prefix }}FirstName" class="{{ $labelClass }}">First name <span class="text-red-500">*</span></label>
            <input
                id="{{ $prefix }}FirstName"
                type="text"
                name="first_name"
                required
                maxlength="50"
                value="{{ old('first_name', $person->person_first_name ?? '') }}"
                placeholder="e.g. Juan"
                oninput="this.value = this.value.replace(/[^\p{L}\p{M}\s'-]/gu, '')"
                class="{{ $fieldClass }}"
            >
        </div>
        <div>
            <label for="{{ $prefix }}MiddleName" class="{{ $labelClass }}">Middle name</label>
            <input
                id="{{ $prefix }}MiddleName"
                type="text"
                name="middle_name"
                maxlength="50"
                value="{{ old('middle_name', $person->person_middle_name ?? '') }}"
                placeholder="Optional"
                oninput="this.value = this.value.replace(/[^\p{L}\p{M}\s'-]/gu, '')"
                class="{{ $fieldClass }}"
            >
        </div>
        <div>
            <label for="{{ $prefix }}LastName" class="{{ $labelClass }}">Last name <span class="text-red-500">*</span></label>
            <input
                id="{{ $prefix }}LastName"
                type="text"
                name="last_name"
                required
                maxlength="50"
                value="{{ old('last_name', $person->person_last_name ?? '') }}"
                placeholder="e.g. Dela Cruz"
                oninput="this.value = this.value.replace(/[^\p{L}\p{M}\s'-]/gu, '')"
                class="{{ $fieldClass }}"
            >
        </div>
    </div>
</section>

<section>
    <p class="{{ $sectionClass }}"><i data-lucide="building-2" class="h-3.5 w-3.5"></i> Office</p>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="{{ $prefix }}Basis" class="{{ $labelClass }}">Employment basis</label>
            <select id="{{ $prefix }}Basis" name="employment_basis" class="{{ $fieldClass }}">
                <option value="">Select basis</option>
                <option value="Full time" @selected(old('employment_basis', $person->person_employment_basis ?? '') === 'Full time')>Full time</option>
                <option value="Part time" @selected(old('employment_basis', $person->person_employment_basis ?? '') === 'Part time')>Part time</option>
            </select>
        </div>
        <div>
            <label for="{{ $prefix }}Department" class="{{ $labelClass }}">Department</label>
            <select id="{{ $prefix }}Department" name="department_id" class="{{ $fieldClass }}" data-searchable="1" data-search-placeholder="Search department">
                <option value="">No department</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->department_id }}" @selected((int) old('department_id', $person->person_department_id ?? 0) === (int) $dept->department_id)>
                        {{ $dept->department_name }}{{ ! empty($dept->department_is_archived) ? ' (archived)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</section>

<section>
    <p class="{{ $sectionClass }}"><i data-lucide="mail" class="h-3.5 w-3.5"></i> Contact</p>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="{{ $prefix }}Email" class="{{ $labelClass }}">Email</label>
            <input
                id="{{ $prefix }}Email"
                type="email"
                name="email"
                maxlength="{{ \App\Support\InputLimits::EMAIL }}"
                value="{{ old('email', $person->person_email_address ?? '') }}"
                placeholder="name@ormoc.sti.edu.ph"
                class="{{ $fieldClass }}"
            >
        </div>
        <div>
            <label for="{{ $prefix }}-contact" class="{{ $labelClass }}">Contact number</label>
            @include('partials.phone-input', [
                'name' => 'contact',
                'value' => old('contact', $person->person_contact_number ?? ''),
                'id' => $prefix.'-contact',
                'inputClass' => $fieldClass,
            ])
        </div>
    </div>
</section>

@if ($person)
    <section>
        <p class="{{ $sectionClass }}"><i data-lucide="toggle-left" class="h-3.5 w-3.5"></i> Status</p>
        <select name="status" class="{{ $fieldClass }}">
            <option value="Active" @selected(old('status', $person->person_status) === 'Active')>Active</option>
            <option value="Inactive" @selected(old('status', $person->person_status) === 'Inactive')>Inactive</option>
        </select>
    </section>
@endif
