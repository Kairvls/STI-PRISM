@extends('layouts.maintenance-layout')

@section('title', 'Personnel Directory')

@section('content')
@use('App\Support\PersonnelDirectory')
@php
    $fieldClass = 'h-10 w-full rounded-xl border-0 bg-slate-50 px-3 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10';
    $labelClass = 'mb-1 block text-xs font-medium text-slate-500';
    $formErrors = $errors->getBag('personnel');
    $importErrors = $errors->getBag('personnelImport');
    $blankPerson = [
        'id' => null, 'employee_id' => '', 'first_name' => '', 'middle_name' => '', 'last_name' => '',
        'type' => 'Faculty', 'department_id' => '', 'email' => '', 'contact' => '', 'status' => PersonnelDirectory::STATUS_ACTIVE,
    ];
    $duplicates = session('personnel_duplicates', []);
    $reopen = $formErrors->any() || $duplicates !== []
        ? [
            'id' => old('personnel_id') ? (int) old('personnel_id') : null,
            'employee_id' => old('employee_id', ''),
            'first_name' => old('first_name', ''),
            'middle_name' => old('middle_name', ''),
            'last_name' => old('last_name', ''),
            'type' => old('type', 'Faculty'),
            'department_id' => (string) old('department_id', ''),
            'email' => old('email', ''),
            'contact' => old('contact', ''),
            'status' => old('status', PersonnelDirectory::STATUS_ACTIVE),
        ]
        : null;
    $importResult = session('personnel_import_result');
    $statusChip = [
        PersonnelDirectory::STATUS_ACTIVE => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        PersonnelDirectory::STATUS_ON_LEAVE => 'bg-amber-50 text-amber-700 ring-amber-100',
        PersonnelDirectory::STATUS_SEPARATED => 'bg-slate-100 text-slate-600 ring-slate-200',
    ];
    $filterUrl = fn (array $overrides) => route('maintenance.personnel-directory.index', array_filter(array_merge([
        'status' => $status !== 'all' ? $status : null,
        'type' => $type ?: null,
        'search' => $search ?: null,
    ], $overrides)));
@endphp

<div
    class="space-y-6"
    x-data="{
        open: @js($reopen !== null),
        importOpen: @js($importErrors->any()),
        form: @js($reopen ?? $blankPerson),
        blank: @js($blankPerson),
        showDuplicates: @js($duplicates !== []),
        storeUrl: @js(route('maintenance.personnel-directory.store')),
        updateBase: @js(url('/maintenance/personnel-directory')),
        start(person = null) {
            this.showDuplicates = false;
            this.form = person ? { ...person } : { ...this.blank };
            this.open = true;
            this.$nextTick(() => {
                window.lucide?.createIcons();
                document.getElementById('personnel-employee-id')?.focus();
            });
        },
        syncTypeFromId() {
            const last = String(this.form.employee_id || '').trim().toUpperCase().slice(-1);
            if (last === 'F') this.form.type = 'Faculty';
            if (last === 'S') this.form.type = 'Staff';
        },
    }"
    @keydown.escape.window="open = false; importOpen = false"
>
    @if (! $tableReady)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
            The personnel directory table is missing. Run <code class="font-mono text-xs">php artisan migrate</code> to enable this page.
        </div>
    @else
        @include('layouts.partials.maintenance-stat-cards', [
            'cards' => [
                ['label' => 'Active faculty', 'hint' => 'Can be verified as Faculty', 'value' => number_format($stats['faculty'])],
                ['label' => 'Active staff', 'hint' => 'Can be verified as Staff', 'value' => number_format($stats['staff'])],
                ['label' => 'Not active', 'hint' => number_format($stats['on_leave']).' on leave · '.number_format($stats['separated']).' separated', 'value' => number_format($stats['on_leave'] + $stats['separated'])],
                ['label' => 'Last updated', 'hint' => $stats['total'] > 0 ? number_format($stats['total']).' people in the list' : 'Upload the HR list to start', 'value' => $stats['updated_at'] ? \Illuminate\Support\Carbon::parse($stats['updated_at'])->format('M j, Y') : '—'],
            ],
        ])

        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 text-sm text-slate-600">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#0025cc]/5 text-[#0025cc]">
                    <i data-lucide="shield-check" class="h-4 w-4"></i>
                </div>
                <div class="min-w-0">
                    <p class="font-semibold text-slate-900">How this list is used</p>
                    <p class="mt-0.5 text-slate-500">
                        Every reporter application is compared against this list by employee ID, name, type and email.
                        Only active, matching records count as verified. Anything else needs a written reason to approve, which is kept on record.
                        Marking someone Separated also sets their reporter account to Inactive.
                    </p>
                </div>
            </div>
        </div>

        @if (is_array($importResult) && (!empty($importResult['errors']) || !empty($importResult['unknown_departments'])))
            <div class="rounded-2xl border border-amber-200 bg-amber-50/70 px-5 py-4 text-sm text-amber-900">
                <p class="font-semibold">Upload notes</p>
                @if (!empty($importResult['errors']))
                    <p class="mt-1">{{ $importResult['skipped'] }} {{ \Illuminate\Support\Str::plural('row', $importResult['skipped']) }} skipped:</p>
                    <ul class="mt-1 list-disc space-y-0.5 pl-5 text-amber-800">
                        @foreach ($importResult['errors'] as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                        @if ($importResult['skipped'] > count($importResult['errors']))
                            <li>…and {{ $importResult['skipped'] - count($importResult['errors']) }} more.</li>
                        @endif
                    </ul>
                @endif
                @if (!empty($importResult['unknown_departments']))
                    <p class="mt-2">
                        Departments not found (saved without a department):
                        <span class="font-medium">{{ implode(', ', $importResult['unknown_departments']) }}</span>.
                        <a href="{{ route('maintenance.departments.index') }}" class="font-semibold text-[#0025cc] hover:underline">Add them in Departments</a> and upload again.
                    </p>
                @endif
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Faculty and staff</h2>
                    <p class="mt-0.5 text-sm text-slate-500">The official campus list. Keep it in sync with HR.</p>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <form method="GET" action="{{ route('maintenance.personnel-directory.index') }}" class="flex gap-2">
                        @if ($status !== 'all')
                            <input type="hidden" name="status" value="{{ $status }}">
                        @endif
                        @if ($type)
                            <input type="hidden" name="type" value="{{ $type }}">
                        @endif
                        <div class="relative">
                            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                            <input type="search" name="search" value="{{ $search }}" placeholder="Search ID, name or email" class="h-10 w-full rounded-xl border-0 bg-slate-50 pl-10 pr-3 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10 sm:w-64">
                        </div>
                        <button type="submit" class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800">
                            <i data-lucide="search" class="h-4 w-4"></i>
                            Search
                        </button>
                    </form>
                    <button type="button" @click="importOpen = true; $nextTick(() => window.lucide?.createIcons())" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        <i data-lucide="upload" class="h-4 w-4"></i>
                        Upload list
                    </button>
                    <button type="button" @click="start()" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-[#001fad]">
                        <i data-lucide="user-plus" class="h-4 w-4"></i>
                        Add person
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 px-4 py-3 sm:px-5">
                @foreach (PersonnelDirectory::FILTERS as $key => $label)
                    <a
                        href="{{ $filterUrl(['status' => $key === 'all' ? null : $key]) }}"
                        class="inline-flex h-8 items-center rounded-full px-3 text-xs font-semibold ring-1 transition {{ $status === $key ? 'bg-slate-100 text-slate-900 ring-slate-300' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' }}"
                    >{{ $label }}</a>
                @endforeach
                <span class="mx-1 h-5 w-px bg-slate-200"></span>
                @foreach (['' => 'Faculty & staff', 'Faculty' => 'Faculty', 'Staff' => 'Staff'] as $key => $label)
                    <a
                        href="{{ $filterUrl(['type' => $key ?: null]) }}"
                        class="inline-flex h-8 items-center rounded-full px-3 text-xs font-semibold ring-1 transition {{ $type === $key ? 'bg-slate-100 text-slate-900 ring-slate-300' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' }}"
                    >{{ $label }}</a>
                @endforeach
            </div>

            @if ($people->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Employee ID</th>
                                <th class="px-5 py-3">Name</th>
                                <th class="px-5 py-3">Type</th>
                                <th class="px-5 py-3">Department</th>
                                <th class="px-5 py-3">Contact</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Reporter account</th>
                                <th class="px-5 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($people as $person)
                                @php
                                    $name = PersonnelDirectory::fullName($person);
                                    $payload = [
                                        'id' => (int) $person->personnel_id,
                                        'employee_id' => $person->personnel_employee_id,
                                        'first_name' => $person->personnel_first_name,
                                        'middle_name' => (string) $person->personnel_middle_name,
                                        'last_name' => $person->personnel_last_name,
                                        'type' => $person->personnel_type,
                                        'department_id' => $person->personnel_department_id ? (string) $person->personnel_department_id : '',
                                        'email' => (string) $person->personnel_email,
                                        'contact' => (string) $person->personnel_contact,
                                        'status' => $person->personnel_status,
                                    ];
                                    $reporterStatus = $reporterStatuses[$person->personnel_employee_number] ?? null;
                                    $inactiveRow = $person->personnel_status !== PersonnelDirectory::STATUS_ACTIVE;
                                @endphp
                                <tr class="align-middle {{ $inactiveRow ? 'bg-slate-50/60' : '' }}">
                                    <td class="px-5 py-3 font-mono text-[13px] font-medium text-slate-900">{{ $person->personnel_employee_id }}</td>
                                    <td class="px-5 py-3">
                                        <p class="font-semibold {{ $inactiveRow ? 'text-slate-500' : 'text-slate-900' }}">{{ $name }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $person->personnel_email ?: 'No email on file' }}</p>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">{{ $person->personnel_type }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-slate-600">{{ $person->department_name ?: '—' }}</td>
                                    <td class="px-5 py-3 text-slate-600">{{ $person->personnel_contact ?: '—' }}</td>
                                    <td class="px-5 py-3">
                                        <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $statusChip[$person->personnel_status] ?? $statusChip[PersonnelDirectory::STATUS_SEPARATED] }}">{{ $person->personnel_status }}</span>
                                    </td>
                                    <td class="px-5 py-3">
                                        @if ($reporterStatus === 'Active')
                                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-[#0025cc]"><i data-lucide="badge-check" class="h-3.5 w-3.5"></i> Registered</span>
                                        @elseif ($reporterStatus)
                                            <span class="text-xs font-medium text-slate-500">{{ $reporterStatus }}</span>
                                        @else
                                            <span class="text-xs text-slate-400">Not registered</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button
                                                type="button"
                                                @click="start(@js($payload))"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001db3]"
                                                data-tooltip="Edit person"
                                                aria-label="Edit person"
                                            >
                                                <i data-lucide="pencil" class="h-4 w-4"></i>
                                            </button>
                                            <form
                                                method="POST"
                                                action="{{ route('maintenance.personnel-directory.destroy', $person->personnel_id) }}"
                                                data-pur-confirm="{{ $name }} will be removed from the directory. Use Separated instead if they left the campus, so the record stays."
                                                data-pur-confirm-title="Remove from directory?"
                                                data-pur-confirm-ok="Remove"
                                                data-pur-confirm-danger="1"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-red-700 transition hover:bg-slate-50 active:scale-95"
                                                    data-tooltip="Remove from directory"
                                                    aria-label="Remove from directory"
                                                >
                                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($people->hasPages())
                    <div class="border-t border-slate-100 px-5 py-4">
                        {{ $people->links() }}
                    </div>
                @endif
            @else
                <div class="px-5 py-16 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <i data-lucide="book-user" class="h-6 w-6"></i>
                    </div>
                    <p class="mt-4 text-sm font-semibold text-slate-900">
                        {{ $search !== '' || $status !== 'all' || $type ? 'No one matches these filters' : 'The directory is empty' }}
                    </p>
                    <p class="mx-auto mt-1 max-w-sm text-xs leading-5 text-slate-500">
                        Upload the faculty and staff list from HR (CSV or Excel), or add people one by one. Reporter applications are checked against it.
                    </p>
                    <div class="mt-4 flex justify-center gap-2">
                        <button type="button" @click="importOpen = true" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <i data-lucide="upload" class="h-4 w-4"></i>
                            Upload list
                        </button>
                        <button type="button" @click="start()" class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001fad]">
                            <i data-lucide="user-plus" class="h-4 w-4"></i>
                            Add person
                        </button>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <template x-teleport="body">
    <div x-show="open" x-cloak class="fixed inset-0 z-[1300] flex items-start justify-center overflow-y-auto bg-[#0b1220]/70 p-4" @click.self="open = false">
        <form
            method="POST"
            :action="form.id ? updateBase + '/' + form.id : storeUrl"
            class="my-auto w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl"
        >
            @csrf
            <template x-if="form.id">
                <input type="hidden" name="_method" value="PUT">
            </template>
            <input type="hidden" name="personnel_id" :value="form.id ?? ''">

            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h3 class="text-base font-semibold text-slate-900" x-text="form.id ? 'Edit person' : 'Add person'"></h3>
                <button type="button" @click="open = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <div class="space-y-4 px-5 py-5">
                @if ($formErrors->any())
                    <div class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-100">
                        @foreach ($formErrors->all() as $message)
                            <p>{{ $message }}</p>
                        @endforeach
                    </div>
                @endif

                @if ($duplicates !== [])
                    <template x-if="showDuplicates">
                        <div x-init="$nextTick(() => window.lucide?.createIcons())">
                            @include('partials.duplicate-name-warning', ['matches' => $duplicates, 'noun' => 'person', 'wrapperClass' => ''])
                        </div>
                    </template>
                @endif

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="{{ $labelClass }}">Employee ID <span class="text-rose-500">*</span></span>
                        <input type="text" name="employee_id" id="personnel-employee-id" x-model="form.employee_id" @input="syncTypeFromId()" required maxlength="50" placeholder="OMC00127F" class="{{ $fieldClass }} font-mono uppercase">
                    </label>
                    <label class="block">
                        <span class="{{ $labelClass }}">Type <span class="text-rose-500">*</span></span>
                        <select name="type" x-model="form.type" class="{{ $fieldClass }}">
                            @foreach (PersonnelDirectory::TYPES as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <label class="block">
                        <span class="{{ $labelClass }}">First name <span class="text-rose-500">*</span></span>
                        <input type="text" name="first_name" x-model="form.first_name" required maxlength="100" class="{{ $fieldClass }}">
                    </label>
                    <label class="block">
                        <span class="{{ $labelClass }}">Middle name</span>
                        <input type="text" name="middle_name" x-model="form.middle_name" maxlength="100" class="{{ $fieldClass }}">
                    </label>
                    <label class="block">
                        <span class="{{ $labelClass }}">Last name <span class="text-rose-500">*</span></span>
                        <input type="text" name="last_name" x-model="form.last_name" required maxlength="100" class="{{ $fieldClass }}">
                    </label>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="{{ $labelClass }}">Email</span>
                        <input type="email" name="email" x-model="form.email" maxlength="255" placeholder="name@ormoc.sti.edu.ph" class="{{ $fieldClass }}">
                    </label>
                    <label class="block">
                        <span class="{{ $labelClass }}">Contact number</span>
                        <input type="text" name="contact" x-model="form.contact" maxlength="50" placeholder="09171234567" class="{{ $fieldClass }}">
                    </label>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="{{ $labelClass }}">Department</span>
                        <select name="department_id" x-model="form.department_id" class="{{ $fieldClass }}" data-searchable="1" data-search-placeholder="Search department">
                            <option value="">No department</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->department_id }}">{{ $department->department_name }}{{ $department->department_is_archived ? ' (archived)' : '' }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="{{ $labelClass }}">Status <span class="text-rose-500">*</span></span>
                        <select name="status" x-model="form.status" class="{{ $fieldClass }}">
                            @foreach (PersonnelDirectory::STATUSES as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <p x-show="form.status === @js(PersonnelDirectory::STATUS_SEPARATED)" x-cloak class="rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-800 ring-1 ring-amber-100">
                    Saving as Separated also sets their reporter account to Inactive, so they can no longer file reports.
                </p>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-4">
                <button type="button" @click="open = false" class="inline-flex h-10 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001fad]">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    <span x-text="form.id ? 'Save changes' : 'Add person'"></span>
                </button>
            </div>
        </form>
    </div>
    </template>

    <template x-teleport="body">
    <div x-show="importOpen" x-cloak class="fixed inset-0 z-[1300] flex items-start justify-center overflow-y-auto bg-[#0b1220]/70 p-4" @click.self="importOpen = false">
        <form
            method="POST"
            action="{{ route('maintenance.personnel-directory.import') }}"
            enctype="multipart/form-data"
            class="my-auto w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl"
        >
            @csrf
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h3 class="text-base font-semibold text-slate-900">Upload faculty and staff list</h3>
                <button type="button" @click="importOpen = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <div class="space-y-4 px-5 py-5">
                @if ($importErrors->any())
                    <div class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-100">
                        @foreach ($importErrors->all() as $message)
                            <p>{{ $message }}</p>
                        @endforeach
                    </div>
                @endif

                <div class="rounded-xl bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-600 ring-1 ring-slate-200/80">
                    <p class="font-semibold text-slate-800">Columns</p>
                    <p class="mt-0.5">
                        Required: <span class="font-medium text-slate-800">Employee ID, First name, Last name</span>.
                        Optional: Middle name, Type, Department, Email address, Contact number, Status.
                    </p>
                    <p class="mt-1">Type is read from the ID letter when blank (F = Faculty, S = Staff). Status defaults to Active. People already in the list are updated by employee ID.</p>
                    <a href="{{ route('maintenance.personnel-directory.template') }}" class="mt-2 inline-flex items-center gap-1.5 font-semibold text-[#0025cc] hover:underline">
                        <i data-lucide="download" class="h-3.5 w-3.5"></i>
                        Download CSV template
                    </a>
                </div>

                <label class="block">
                    <span class="{{ $labelClass }}">File (CSV or Excel .xlsx) <span class="text-rose-500">*</span></span>
                    <input type="file" name="file" accept=".csv,.txt,.xlsx" required class="block w-full rounded-xl bg-slate-50 text-sm text-slate-600 ring-1 ring-slate-200/80 file:mr-3 file:h-10 file:rounded-l-xl file:border-0 file:bg-[#0025cc] file:px-4 file:text-sm file:font-semibold file:text-white hover:file:bg-[#001fad]">
                </label>

                <label class="flex items-start gap-3 rounded-xl bg-white px-3 py-3 ring-1 ring-slate-200/80">
                    <input type="checkbox" name="mark_missing_separated" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#0025cc] focus:ring-[#0025cc]/30">
                    <span class="text-sm">
                        <span class="font-medium text-slate-900">This file is the complete current list</span>
                        <span class="mt-0.5 block text-xs text-slate-500">People in the directory but missing from this file are marked Separated, and their reporter accounts are set to Inactive.</span>
                    </span>
                </label>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-4">
                <button type="button" @click="importOpen = false" class="inline-flex h-10 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001fad]">
                    <i data-lucide="upload" class="h-4 w-4"></i>
                    Upload
                </button>
            </div>
        </form>
    </div>
    </template>
</div>
@endsection
