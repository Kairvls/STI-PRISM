@extends('layouts.admin-layout')

@section('title', 'Faculty & Staff')

@section('content')
@php
    $fieldClass = 'h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10';
@endphp

<div class="space-y-6" x-data="personsPage()">
    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">{{ session('error') }}</div>
    @endif

    @if (! $tableReady)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
            Faculty & Staff table is missing. Run <code class="font-mono text-xs">php artisan migrate</code>.
        </div>
    @else
        <div class="flex flex-wrap items-center justify-end gap-2">
            <button
                type="button"
                @click="importOpen = true"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-[13px] font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                <i data-lucide="upload" class="h-4 w-4"></i>
                Add Bulk Faculty/Staff
            </button>
            <button
                type="button"
                @click="openAdd()"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-lg shadow-slate-900/10 transition hover:-translate-y-0.5 hover:bg-[rgba(0,44,155,0.85)]"
            >
                <i data-lucide="plus" class="h-4 w-4"></i>
                Add Faculty/Staff
            </button>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:gap-6 sm:p-5">
                <div class="min-w-0 shrink-0">
                    <h2 class="text-lg font-semibold text-slate-950">Faculty & Staff</h2>
                    <p class="mt-1 text-sm text-slate-500">Campus faculty and staff — identity source for other modules.</p>
                </div>

                <form data-auto-filter method="GET" action="{{ route('admin.persons.index') }}" class="flex w-full min-w-0 flex-col gap-3 sm:w-auto sm:flex-1 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
                    <div class="relative min-w-[220px] flex-1 sm:max-w-md">
                        <div class="flex gap-2">
                            <input type="search" name="search" value="{{ $search }}" placeholder="Name, employee ID, email…" aria-label="Search" class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100">
                            <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white">Search</button>
                        </div>
                    </div>
                    <div>
                        <select name="department" aria-label="Department" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100">
                            <option value="">All departments</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->department_id }}" @selected((int) $departmentId === (int) $dept->department_id)>{{ $dept->department_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <select name="filter" aria-label="Status" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100">
                            @foreach (\App\Support\Persons::FILTERS as $key => $label)
                                <option value="{{ $key }}" @selected($filter === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto rounded-b-2xl">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Name</th>
                            <th class="px-5 py-3">Employee ID</th>
                            <th class="px-5 py-3">Type</th>
                            <th class="px-5 py-3">Department</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($people as $person)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-5 py-3">
                                    <div class="font-medium text-slate-900">{{ $person->person_full_name }}</div>
                                    @if ($person->person_email_address)
                                        <div class="text-xs text-slate-500">{{ $person->person_email_address }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-3 font-mono text-xs text-slate-700">{{ $person->person_employee_id }}</td>
                                <td class="px-5 py-3 text-slate-700">
                                    {{ $person->person_employment_type ?: '—' }}
                                    @if ($person->person_employment_basis)
                                        <span class="text-xs text-slate-400">· {{ $person->person_employment_basis }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-slate-700">{{ $person->department_name ?: '—' }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $person->person_status === 'Active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $person->person_status }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('admin.persons.show', $person->person_id) }}" class="text-sm font-semibold text-[#0025cc] hover:underline">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">No faculty or staff match these filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-[1000] flex items-end justify-center bg-[#0b1220]/70 p-4 sm:items-center" @keydown.escape.window="if (open) open = false">
            <div class="absolute inset-0" @click="open = false"></div>
            <form
                method="POST"
                action="{{ route('admin.persons.store') }}"
                class="relative flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10"
                @click.stop
            >
                @csrf
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0025cc]/5 text-[#0025cc]">
                            <i data-lucide="user-plus" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold tracking-tight text-slate-900">Add faculty/staff</h3>
                            <p class="mt-0.5 text-sm text-slate-500">Fields marked <span class="text-red-500">*</span> are required. Choose Faculty or Staff — identity is shared with reporters, property, and users.</p>
                        </div>
                    </div>
                    <button type="button" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" @click="open = false" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
                <div class="min-h-0 flex-1 space-y-6 overflow-y-auto px-6 py-5">
                    @if ($errors->any())
                        <div class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-100">
                            @foreach ($errors->all() as $message)
                                <p>{{ $message }}</p>
                            @endforeach
                        </div>
                    @endif
                    @include('admin.persons._form-fields', ['person' => null, 'departments' => $departments, 'fieldClass' => $fieldClass, 'idPrefix' => 'create'])
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/70 px-6 py-4">
                    <button type="button" @click="open = false" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#0025cc] px-5 text-sm font-semibold text-white hover:bg-[#001fad]">
                        <i data-lucide="check" class="h-4 w-4"></i>
                        Save faculty/staff
                    </button>
                </div>
            </form>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="importOpen" x-cloak class="fixed inset-0 z-[1000] flex items-end justify-center bg-[#0b1220]/70 p-4 sm:items-center" @keydown.escape.window="if (importOpen) importOpen = false">
            <div class="absolute inset-0" @click="importOpen = false"></div>
            <div class="relative flex max-h-[88vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl" @click.stop>
                <div class="flex items-start justify-between border-b border-slate-100 px-6 py-5">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Add many faculty &amp; staff at once</h2>
                        <p class="mt-1 text-sm text-slate-500">Download the sample file, fill in faculty and staff only, then upload. Each row is checked the same way as adding one record.</p>
                    </div>
                    <button type="button" @click="importOpen = false" class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                <form id="personImportForm" action="{{ route('admin.persons.import') }}" method="POST" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col">
                    @csrf
                    <input type="hidden" name="mapping" id="personImportMapping" value="">

                    <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                        <label id="personImportDropzone" class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-8 text-center transition hover:border-slate-400 hover:bg-slate-100">
                            <i data-lucide="cloud-upload" class="h-8 w-8 text-slate-400"></i>
                            <p class="mt-3 text-sm font-medium text-slate-700">Drop a CSV or Excel file, or click to browse <span class="text-red-500">*</span></p>
                            <p class="mt-1 text-xs text-slate-400">Use the sample file so names and numbers import correctly</p>
                            <input id="personImportFile" name="file" type="file" accept=".csv,.txt,.xlsx" class="hidden" required>
                        </label>
                        <p id="personImportFileName" class="mt-3 hidden text-sm font-medium text-slate-600"></p>
                        <p id="personImportError" class="mt-3 hidden text-sm text-rose-600"></p>

                        <div id="personImportMappingPanel" class="mt-5 hidden">
                            <p id="personImportPreviewCount" class="text-sm font-medium text-slate-800"></p>
                            <p id="personImportUnknownDepartments" class="mt-2 hidden rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 ring-1 ring-amber-100"></p>
                            <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200">
                                <table class="min-w-full text-left text-sm">
                                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        <tr>
                                            <th class="px-3 py-2">Row</th>
                                            <th class="px-3 py-2">Status</th>
                                            <th class="px-3 py-2">Employee ID</th>
                                            <th class="px-3 py-2">Name</th>
                                            <th class="px-3 py-2">Type</th>
                                            <th class="px-3 py-2">Department</th>
                                            <th class="px-3 py-2">Email</th>
                                            <th class="px-3 py-2">Contact</th>
                                        </tr>
                                    </thead>
                                    <tbody id="personImportPreviewBody" class="divide-y divide-slate-100"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/70 px-6 py-4">
                        <a href="{{ route('admin.persons.import.template') }}" class="text-sm font-medium text-blue-600 hover:text-slate-900">Download sample CSV</a>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="importOpen = false" class="h-10 rounded-lg px-4 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                            <button id="personImportSubmitBtn" type="submit" disabled class="h-10 rounded-lg bg-[rgba(0,55,199,0.85)] px-5 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-40">
                                Import faculty &amp; staff
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </template>

    @if (session('import_result'))
        @php
            $importResult = session('import_result');
            $importCreated = (int) ($importResult['created'] ?? 0);
            $importSkipped = (int) ($importResult['skipped'] ?? 0);
            $importErrors = $importResult['errors'] ?? [];
            $importUnknownDepartments = $importResult['unknown_departments'] ?? [];
        @endphp
        <template x-teleport="body">
            <div x-data="{ show: true }" x-show="show" x-cloak class="fixed inset-0 z-[1100] flex items-center justify-center bg-[#0b1220]/70 p-4">
                <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <div class="flex items-start gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $importSkipped > 0 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                                <i data-lucide="{{ $importSkipped > 0 ? 'alert-triangle' : 'check' }}" class="h-5 w-5"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">{{ $importSkipped > 0 ? 'Import finished with issues' : 'Import complete' }}</h3>
                                <div class="mt-3 grid grid-cols-2 gap-3">
                                    <div class="rounded-xl bg-emerald-50 px-3 py-2">
                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Added</p>
                                        <p class="mt-1 text-2xl font-semibold text-emerald-900">{{ $importCreated }}</p>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Skipped</p>
                                        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $importSkipped }}</p>
                                    </div>
                                </div>
                                @if (count($importUnknownDepartments) > 0)
                                    <p class="mt-3 text-xs text-amber-800">Unknown departments left blank: <span class="font-medium">{{ implode(', ', $importUnknownDepartments) }}</span>.</p>
                                @endif
                                @if (count($importErrors) > 0)
                                    <ul class="mt-3 max-h-40 list-disc space-y-1 overflow-y-auto pl-5 text-xs text-rose-700">
                                        @foreach ($importErrors as $importError)
                                            <li>{{ $importError }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end px-6 py-4">
                        <button type="button" @click="show = false" class="h-10 rounded-xl bg-slate-900 px-5 text-sm font-semibold text-white">OK</button>
                    </div>
                </div>
            </div>
        </template>
    @endif
</div>

<script>
function personsPage() {
    return {
        open: @json($errors->any()),
        importOpen: false,
        openAdd() {
            this.open = true;
            this.$nextTick(() => {
                window.syncPersonEmployeeId?.('create');
                window.lucide?.createIcons();
            });
        },
    };
}

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
window.syncPersonEmployeeId = syncPersonEmployeeId;

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-person-emp-sync]').forEach((el) => {
        const prefix = el.getAttribute('data-person-emp-prefix') || 'create';
        const evt = el.getAttribute('data-person-emp-sync') === 'type' ? 'change' : 'input';
        el.addEventListener(evt, () => syncPersonEmployeeId(prefix));
    });
    syncPersonEmployeeId('create');
    syncPersonEmployeeId('edit');

    const fileInput = document.getElementById('personImportFile');
    const dropzone = document.getElementById('personImportDropzone');
    const fileNameEl = document.getElementById('personImportFileName');
    const errorEl = document.getElementById('personImportError');
    const panel = document.getElementById('personImportMappingPanel');
    const previewBody = document.getElementById('personImportPreviewBody');
    const mappingInput = document.getElementById('personImportMapping');
    const previewCount = document.getElementById('personImportPreviewCount');
    const unknownEl = document.getElementById('personImportUnknownDepartments');
    const submitBtn = document.getElementById('personImportSubmitBtn');

    function cellText(value) {
        const text = String(value ?? '').trim();
        if (text === '') return '—';
        return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
    }

    function renderPreview(data) {
        mappingInput.value = JSON.stringify(data.mapping || {});
        previewBody.innerHTML = '';
        (data.people || []).forEach((person) => {
            const row = document.createElement('tr');
            const name = [person.first_name, person.middle_name, person.last_name].filter(Boolean).join(' ');
            const status = person.error
                ? `<span class="inline-flex rounded-md bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700 ring-1 ring-inset ring-rose-200">Skip</span>
                   <p class="mt-1 max-w-[220px] text-xs text-rose-600">${cellText(person.error)}</p>`
                : '<span class="inline-flex rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">Ready</span>';
            const department = person.department && !person.department_id
                ? `${cellText(person.department)} <span class="text-xs text-amber-600">(not found)</span>`
                : cellText(person.department);
            row.className = person.error ? 'bg-rose-50/40 align-top' : 'align-top';
            row.innerHTML = `
                <td class="px-3 py-2 font-mono text-xs text-slate-400">${cellText(person.line)}</td>
                <td class="px-3 py-2">${status}</td>
                <td class="px-3 py-2 font-mono text-xs text-slate-700">${cellText(person.employee_id)}</td>
                <td class="px-3 py-2 text-slate-900">${cellText(name)}</td>
                <td class="px-3 py-2 text-slate-600">${cellText(person.type)}</td>
                <td class="px-3 py-2 text-slate-600">${department}</td>
                <td class="px-3 py-2 text-slate-600">${cellText(person.email_address)}</td>
                <td class="px-3 py-2 text-slate-600">${cellText(person.contact_number)}</td>
            `;
            previewBody.appendChild(row);
        });

        const total = data.total || 0;
        const ready = data.ready || 0;
        const skipped = data.skipped || 0;
        previewCount.textContent = total
            ? `${total} row${total === 1 ? '' : 's'} found: ${ready} ready to add` + (skipped ? `, ${skipped} will be skipped` : '') + (total > 50 ? ' (showing the first 50, problems first)' : '')
            : 'No employees found in the file';

        const unknown = data.unknown_departments || [];
        unknownEl.classList.toggle('hidden', unknown.length === 0);
        unknownEl.textContent = unknown.length
            ? `Departments not found (these faculty/staff will be saved without a department): ${unknown.join(', ')}.`
            : '';

        panel.classList.remove('hidden');
        submitBtn.disabled = ready === 0;
        submitBtn.textContent = ready ? `Add ${ready} faculty/staff` : 'Import faculty & staff';
        errorEl.classList.add('hidden');
    }

    async function previewFile(file) {
        if (!file) return;
        fileNameEl.textContent = file.name;
        fileNameEl.classList.remove('hidden');
        submitBtn.disabled = true;
        panel.classList.add('hidden');

        const formData = new FormData();
        formData.append('file', file);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}');

        try {
            const res = await fetch(@js(route('admin.persons.import.preview')), {
                method: 'POST',
                body: formData,
                headers: { Accept: 'application/json' },
            });
            const data = await res.json();
            if (!res.ok || !data.ok) {
                errorEl.textContent = data.message || 'Could not read this file.';
                errorEl.classList.remove('hidden');
                return;
            }
            renderPreview(data);
        } catch (e) {
            errorEl.textContent = 'Could not preview this file. Try CSV.';
            errorEl.classList.remove('hidden');
        }
    }

    fileInput?.addEventListener('change', () => previewFile(fileInput.files?.[0]));
    ['dragenter', 'dragover'].forEach((evt) => {
        dropzone?.addEventListener(evt, (e) => {
            e.preventDefault();
            dropzone.classList.add('border-slate-400', 'bg-slate-100');
        });
    });
    ['dragleave', 'drop'].forEach((evt) => {
        dropzone?.addEventListener(evt, (e) => {
            e.preventDefault();
            dropzone.classList.remove('border-slate-400', 'bg-slate-100');
        });
    });
    dropzone?.addEventListener('drop', (e) => {
        const file = e.dataTransfer?.files?.[0];
        if (!file || !fileInput) return;
        const transfer = new DataTransfer();
        transfer.items.add(file);
        fileInput.files = transfer.files;
        previewFile(file);
    });
});
</script>
@endsection
