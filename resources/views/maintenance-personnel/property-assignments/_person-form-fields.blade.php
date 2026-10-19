@php
    $editing = $person ?? false;
    $value = fn (string $field, $default = null) => old($field, $editing ? ($person->{$field} ?? $default) : $default);
    $fieldClass = 'h-10 w-full rounded-xl border-0 bg-slate-50 px-3 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10';
    $errorClass = 'ring-rose-300 bg-rose-50/40';
    $selectedRoom = (string) $value('custodian_room_id', $defaultRoomId ?? null);
    $selectedReporter = (string) $value('custodian_reporter_id');
    $selectedBasis = $value('custodian_employment_basis', '');
    $selectedEmploymentType = old('custodian_employment_type', $editing ? ($person->custodian_employment_type ?? $person->custodian_position ?? '') : '');
    $selectedStatus = $value('custodian_status', \App\Support\Custodians::STATUS_ACTIVE);
    $selectedDepartment = (string) $value('custodian_department_id');
    $nameParts = \App\Support\Custodians::nameParts($person ?? null);
    // Provide safe defaults so this partial can be included from views that
    // don't preload the full set of auxiliary variables (rooms, departments, reporters).
    $rooms = $rooms ?? collect();
    $departments = $departments ?? collect();
    $linkableReporters = $linkableReporters ?? collect();
    $defaultRoomId = $defaultRoomId ?? null;
    $modalEmbed = ! empty($modalEmbed);
    $formClass = $modalEmbed
        ? 'bg-white'
        : 'overflow-hidden rounded-2xl border border-slate-200 bg-white';
@endphp

<style>
    .reporter-employee-id-field {
        display: flex;
        align-items: stretch;
        height: 2.75rem;
        overflow: hidden;
        border-radius: 0.75rem;
        background: #f8fafc;
        box-shadow: inset 0 0 0 1px rgba(226, 232, 240, 0.9);
        transition: background 0.15s ease, box-shadow 0.15s ease;
    }

    .reporter-employee-id-field:focus-within {
        background: #fff;
        box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.1);
    }

    .reporter-employee-id-field.is-disabled {
        opacity: 0.85;
    }

    .reporter-employee-id-prefix,
    .reporter-employee-id-suffix {
        display: inline-flex;
        align-items: center;
        font-size: 0.875rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        color: #64748b;
        user-select: none;
    }

    .reporter-employee-id-prefix { padding-left: 0.875rem; }
    .reporter-employee-id-suffix {
        min-width: 2rem;
        justify-content: center;
        padding-right: 0.875rem;
    }
    .reporter-employee-id-suffix.is-empty { color: #94a3b8; }

    .reporter-employee-id-field input {
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

    .reporter-employee-id-field.is-disabled input {
        pointer-events: none;
        color: #94a3b8;
    }
</style>

<form
    method="POST"
    action="{{ $editing ? \App\Support\PropertyAssignments::route('people.update', $person->custodian_id) : \App\Support\PropertyAssignments::route('people.store') }}"
    class="{{ $formClass }}"
    id="personCreateForm"
    @if (! $editing && \App\Support\Persons::tableReady())
        x-data="propertyPersonPicker()"
    @endif
>
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="border-b border-slate-100 px-5 py-4">
        <h2 class="text-base font-semibold text-slate-900">{{ $editing ? 'Edit person' : 'Add a person to the directory' }}</h2>
        <p class="mt-0.5 text-sm text-slate-500">They don't need a system account.</p>
    </div>

    @if ($errors->any())
        <div class="mx-5 mt-5 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-100">
            <p class="font-semibold">Please fix the following:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('partials.duplicate-name-warning', ['matches' => session('duplicate_people', []), 'noun' => 'person'])

    <div class="grid grid-cols-1 gap-4 px-5 py-5 sm:grid-cols-2">
        @if (! $editing && \App\Support\Persons::tableReady())
            <div class="sm:col-span-2">
                @include('admin.partials.person-picker', [
                    'label' => 'From Faculty & Staff',
                    'required' => false,
                    'hint' => 'Optional — pick to auto-fill the form and lock identity.',
                    'searchUrl' => request()->is('admin/*')
                        ? route('admin.persons.search')
                        : route('maintenance.persons.search'),
                ])
            </div>
        @endif
        <div class="grid grid-cols-1 gap-4 sm:col-span-2 sm:grid-cols-2">
            <div>
                <label for="custodian-employment-type" class="mb-1 block text-xs font-medium text-slate-500">Type <span class="text-red-500">*</span></label>
                <select id="custodian-employment-type" name="custodian_employment_type" required class="{{ $fieldClass }}">
                    <option value="">Select type</option>
                    <option value="Faculty" @selected($selectedEmploymentType === 'Faculty')>Faculty</option>
                    <option value="Staff" @selected($selectedEmploymentType === 'Staff')>Staff</option>
                </select>
            </div>
            <div>
                @php
                    $fullEmp = $value('custodian_employee_id');
                    $empDigits = '';
                    $empSuffix = '?';
                    if (preg_match('/^OMC(\d{5})([FS])$/', (string) $fullEmp, $m)) {
                        $empDigits = $m[1];
                        $empSuffix = $m[2];
                    }
                @endphp
                <label for="custodianEmployeeDigits" class="mb-1 block text-xs font-medium text-slate-500">Employee ID <span class="text-red-500">*</span></label>
                <div id="custodianEmployeeIdField" class="reporter-employee-id-field {{ $selectedEmploymentType ? '' : 'is-disabled' }}">
                    <span class="reporter-employee-id-prefix">OMC</span>
                    <input id="custodianEmployeeDigits" type="text" inputmode="numeric" maxlength="5" placeholder="00127" autocomplete="off" value="{{ $empDigits }}" {{ $selectedEmploymentType ? '' : 'readonly' }}>
                    <span id="custodianEmployeeIdSuffix" class="reporter-employee-id-suffix {{ $empSuffix === '?' ? 'is-empty' : '' }}">{{ $empSuffix }}</span>
                </div>
                <input type="hidden" id="custodian_employee_id" name="custodian_employee_id" value="{{ $fullEmp }}" required>
                <p id="custodianEmployeeHint" class="mt-1 text-xs text-slate-400">Select type first, then enter the 5-digit number.</p>
                @if (! $editing)
                    <p id="custodianEmployeeExistsHint" class="mt-1 hidden text-xs text-amber-700">This employee ID is already in the people directory.</p>
                @endif
                @error('custodian_employee_id')
                    <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror

                @if(session('mismatch_force_data') && session('mismatch_force_data.type') === 'reporter')
                    @php $r = session('mismatch_force_data.reporter'); @endphp
                    <div class="mt-3 rounded-lg border border-amber-100 bg-amber-50 p-3 text-xs text-amber-800">
                        <div class="flex items-center justify-between">
                            <div class="font-semibold">Reporter directory (authoritative)</div>
                            <div class="text-[11px] text-amber-700">These values will be used if you choose <strong>Force sync</strong></div>
                        </div>
                        <div class="mt-2 grid grid-cols-1 gap-1 sm:grid-cols-2">
                            <div><span class="font-medium">Name:</span> {{ $r['reporter_full_name'] ?? '—' }}</div>
                            <div><span class="font-medium">Email:</span> {{ $r['reporter_email_address'] ?? $r['reporter_email'] ?? '—' }}</div>
                            <div><span class="font-medium">Contact:</span> {{ \App\Support\PhoneNumber::formatForDisplay($r['reporter_contact_number'] ?? $r['reporter_contact'] ?? null) ?? '—' }}</div>
                            <div><span class="font-medium">Department ID:</span> {{ $r['reporter_department_id'] ?? '—' }}</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:col-span-2 sm:grid-cols-3">
            <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-500">First name <span class="text-rose-500">*</span></span>
                <input type="text" name="custodian_first_name" required maxlength="50" value="{{ old('custodian_first_name', $nameParts['first']) }}" placeholder="e.g. Ana" oninput="this.value = this.value.replace(/[^\p{L}\p{M}\s'-]/gu, '')" class="{{ $fieldClass }} @error('custodian_first_name') {{ $errorClass }} @enderror">
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-500">Middle name</span>
                <input type="text" name="custodian_middle_name" maxlength="50" value="{{ old('custodian_middle_name', $nameParts['middle']) }}" placeholder="Optional" oninput="this.value = this.value.replace(/[^\p{L}\p{M}\s'-]/gu, '')" class="{{ $fieldClass }} @error('custodian_middle_name') {{ $errorClass }} @enderror">
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-500">Last name <span class="text-rose-500">*</span></span>
                <input type="text" name="custodian_last_name" required maxlength="50" value="{{ old('custodian_last_name', $nameParts['last']) }}" placeholder="e.g. Reyes" oninput="this.value = this.value.replace(/[^\p{L}\p{M}\s'-]/gu, '')" class="{{ $fieldClass }} @error('custodian_last_name') {{ $errorClass }} @enderror">
            </label>
        </div>

        <div class="block">
            <label for="custodian-department" class="mb-1 flex items-center justify-between text-xs font-medium text-slate-500">
                Department / office
                @if (\App\Support\RoleAccess::isAdmin())
                    <a href="{{ route('admin.departments.index') }}" target="_blank" rel="noopener" class="font-semibold text-[#0025cc] hover:underline">Manage</a>
                @endif
            </label>
            <select name="custodian_department_id" id="custodian-department" data-searchable="1" data-search-placeholder="Search department…" class="{{ $fieldClass }} @error('custodian_department_id') {{ $errorClass }} @enderror">
                <option value="">Not set</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->department_id }}" @selected($selectedDepartment === (string) $department->department_id)>
                        {{ $department->department_name }}{{ $department->department_is_archived ? ' (archived)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Room</span>
            <select name="custodian_room_id" id="custodian-room" data-searchable="1" data-search-placeholder="Search room…" class="{{ $fieldClass }}">
                <option value="">Not set</option>
                @foreach ($rooms as $room)
                    <option value="{{ $room->room_id }}" @selected($selectedRoom === (string) $room->room_id)>
                        {{ $room->room_name }}{{ $room->room_type ? ' · '.$room->room_type : '' }}
                    </option>
                @endforeach
            </select>
        </label>

        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Employment basis</span>
            <select name="custodian_employment_basis" class="{{ $fieldClass }} @error('custodian_employment_basis') {{ $errorClass }} @enderror">
                <option value="">Select basis</option>
                <option value="Full time" @selected($selectedBasis === 'Full time')>Full time</option>
                <option value="Part time" @selected($selectedBasis === 'Part time')>Part time</option>
            </select>
        </label>

        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Status <span class="text-red-500">*</span></span>
            <select name="custodian_status" class="{{ $fieldClass }} @error('custodian_status') {{ $errorClass }} @enderror">
                @foreach (\App\Support\Custodians::STATUSES as $status)
                    <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <span class="mt-1 block text-[11px] text-slate-400">Only Active people can receive new items. Inactive requires all items returned first.</span>
        </label>

        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Email</span>
            <input type="email" name="custodian_email_address" maxlength="150" value="{{ $value('custodian_email_address') }}" placeholder="Optional" class="{{ $fieldClass }} @error('custodian_email_address') {{ $errorClass }} @enderror">
        </label>

        <div class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Contact no.</span>
            @include('partials.phone-input', [
                'name' => 'custodian_contact_number',
                'value' => $value('custodian_contact_number'),
                'id' => 'custodian-contact-number',
                'inputClass' => 'h-10 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-100',
            ])
        </div>

        <label class="block sm:col-span-2">
            <span class="mb-1 block text-xs font-medium text-slate-500">Notes</span>
            <textarea name="custodian_notes" rows="3" maxlength="1000" placeholder="Optional" class="w-full rounded-xl border-0 bg-slate-50 px-3 py-2 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10">{{ $value('custodian_notes') }}</textarea>
        </label>
    </div>

    <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-4">
        <a href="{{ $editing ? \App\Support\PropertyAssignments::route('person', $person->custodian_id) : \App\Support\PropertyAssignments::url() }}" class="inline-flex h-10 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
        @if(session('mismatch_force_data'))
            <button type="submit" name="force_sync" value="1" class="inline-flex h-10 items-center gap-2 rounded-xl bg-amber-600 px-4 text-sm font-semibold text-white transition hover:bg-amber-700">
                <i data-lucide="zap" class="h-4 w-4"></i>
                Force sync and add
            </button>
            <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-[#001fad]">
                <i data-lucide="{{ $editing ? 'save' : 'user-plus' }}" class="h-4 w-4"></i>
                {{ $editing ? 'Save changes' : 'Add person' }}
            </button>
        @else
            <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-[#001fad]">
                <i data-lucide="{{ $editing ? 'save' : 'user-plus' }}" class="h-4 w-4"></i>
                {{ $editing ? 'Save changes' : 'Add person' }}
            </button>
        @endif
    </div>
</form>

@if (! $editing && \App\Support\Persons::tableReady())
<script>
function getPropertyPersonPhoneInput() {
    return document.querySelector('#personCreateForm [data-phone-input]');
}

function setPropertyPersonPhone(value) {
    const input = getPropertyPersonPhoneInput();
    if (!input) return;

    const raw = value == null ? '' : String(value).trim();
    if (typeof window.writePrismPhoneStorage === 'function') {
        window.writePrismPhoneStorage(input, raw);
    }

    const iti = window.PRISM_PHONE_INPUTS && window.PRISM_PHONE_INPUTS.get(input);
    if (iti && typeof iti.setNumber === 'function') {
        try {
            if (raw) {
                iti.setNumber(raw);
            } else {
                iti.setNumber('');
                input.value = '';
            }
        } catch (e) {
            input.value = raw ? raw.replace(/^\+?63/, '').replace(/^0/, '').trim() : '';
        }
        return;
    }

    input.value = raw;
    if (typeof window.initPrismPhoneInput === 'function') {
        window.initPrismPhoneInput(input);
        const created = window.PRISM_PHONE_INPUTS && window.PRISM_PHONE_INPUTS.get(input);
        if (created && raw && typeof created.setNumber === 'function') {
            try { created.setNumber(raw); } catch (e) { /* keep raw */ }
        }
    }
}

function setPropertyPersonSelectLocked(select, locked, hiddenId) {
    if (!select) return;
    const form = document.getElementById('personCreateForm');
    let hidden = document.getElementById(hiddenId);
    select.disabled = !!locked;
    if (locked && form) {
        if (!hidden) {
            hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = select.name;
            hidden.id = hiddenId;
            form.appendChild(hidden);
        }
        hidden.value = select.value || '';
        hidden.disabled = false;
    } else if (hidden) {
        hidden.disabled = true;
        hidden.value = '';
    }
}

function setPropertyPersonIdentityLocked(locked) {
    const form = document.getElementById('personCreateForm');
    if (!form) return;

    setPropertyPersonSelectLocked(form.querySelector('#custodian-employment-type'), locked, 'propertyPersonTypeLocked');
    setPropertyPersonSelectLocked(form.querySelector('#custodian-department'), locked, 'propertyPersonDepartmentLocked');
    setPropertyPersonSelectLocked(form.querySelector('[name="custodian_employment_basis"]'), locked, 'propertyPersonBasisLocked');

    const digitsEl = form.querySelector('#custodianEmployeeDigits');
    if (digitsEl) {
        if (locked) {
            digitsEl.setAttribute('readonly', 'readonly');
        } else {
            const typeEl = form.querySelector('#custodian-employment-type');
            if (typeEl && typeEl.value) {
                digitsEl.removeAttribute('readonly');
                digitsEl.removeAttribute('disabled');
            } else {
                digitsEl.setAttribute('readonly', 'readonly');
            }
        }
    }

    ['custodian_first_name', 'custodian_middle_name', 'custodian_last_name', 'custodian_email_address'].forEach(function (name) {
        const input = form.querySelector('[name="' + name + '"]');
        if (input) input.readOnly = !!locked;
    });

    const phone = getPropertyPersonPhoneInput();
    if (phone) {
        phone.readOnly = !!locked;
        const wrap = phone.closest('.iti');
        if (wrap) {
            wrap.classList.toggle('pointer-events-none', !!locked);
            wrap.style.opacity = locked ? '0.85' : '';
        }
    }
}

function clearPropertyPersonAutofill() {
    const form = document.getElementById('personCreateForm');
    if (!form) return;

    setPropertyPersonIdentityLocked(false);

    const typeEl = form.querySelector('#custodian-employment-type');
    if (typeEl) {
        typeEl.disabled = false;
        typeEl.value = '';
        typeEl.dispatchEvent(new Event('change', { bubbles: true }));
    }

    const digitsEl = form.querySelector('#custodianEmployeeDigits');
    if (digitsEl) {
        digitsEl.value = '';
        digitsEl.setAttribute('readonly', 'readonly');
    }

    const fullId = form.querySelector('#custodian_employee_id');
    if (fullId) fullId.value = '';

    ['custodian_first_name', 'custodian_middle_name', 'custodian_last_name', 'custodian_email_address'].forEach(function (name) {
        const input = form.querySelector('[name="' + name + '"]');
        if (input) {
            input.readOnly = false;
            input.value = '';
        }
    });

    const dept = form.querySelector('#custodian-department');
    if (dept) {
        dept.disabled = false;
        dept.value = '';
    }
    const basis = form.querySelector('[name="custodian_employment_basis"]');
    if (basis) {
        basis.disabled = false;
        basis.value = '';
    }

    setPropertyPersonPhone('');

    if (typeof syncCustodianEmployeeIdModal === 'function') {
        syncCustodianEmployeeIdModal();
    }
}

function propertyPersonPicker() {
    return {
        personId: '',
        personQuery: '',
        personOpen: false,
        personResults: [],
        personLabel: '',
        async searchPersons(url) {
            try {
                const endpoint = url || @js(request()->is('admin/*') ? route('admin.persons.search') : route('maintenance.persons.search'));
                const res = await fetch(`${endpoint}?q=${encodeURIComponent(this.personQuery || '')}`);
                this.personResults = await res.json();
            } catch (e) {
                this.personResults = [];
            }
        },
        pickPerson(row) {
            this.personId = String(row.person_id);
            const name = String(row.person_full_name || '').trim();
            const meta = String(row.label || '').trim();
            this.personLabel = name && meta ? (name + ' · ' + meta) : (name || meta || 'Selected person');
            this.personOpen = false;
            const form = document.getElementById('personCreateForm');
            if (!form) return;

            setPropertyPersonIdentityLocked(false);

            const typeEl = form.querySelector('#custodian-employment-type');
            if (typeEl && row.person_employment_type) {
                typeEl.value = row.person_employment_type;
                typeEl.dispatchEvent(new Event('change', { bubbles: true }));
            }
            const digitsEl = form.querySelector('#custodianEmployeeDigits');
            const empMatch = String(row.person_employee_id || '').replace(/\s+/g, '').match(/^OMC(\d{5})([FS])$/i);
            if (digitsEl && empMatch) {
                digitsEl.readOnly = false;
                digitsEl.removeAttribute('disabled');
                digitsEl.value = empMatch[1];
                digitsEl.dispatchEvent(new Event('input', { bubbles: true }));
            }
            if (typeof syncCustodianEmployeeIdModal === 'function') {
                syncCustodianEmployeeIdModal();
            }

            const map = {
                custodian_first_name: row.person_first_name,
                custodian_middle_name: row.person_middle_name,
                custodian_last_name: row.person_last_name,
                custodian_email_address: row.person_email_address,
                custodian_employment_basis: row.person_employment_basis,
                custodian_department_id: row.person_department_id,
            };
            Object.entries(map).forEach(([name, value]) => {
                const input = form.querySelector(`[name="${name}"]`);
                if (input && value != null && value !== '') input.value = value;
            });

            setPropertyPersonPhone(row.person_contact_number || '');
            setPropertyPersonIdentityLocked(true);

            if (window.lucide) lucide.createIcons();
        },
        clearPerson() {
            this.personId = '';
            this.personLabel = '';
            this.personOpen = false;
            clearPropertyPersonAutofill();
        },
    };
}
</script>
@endif
