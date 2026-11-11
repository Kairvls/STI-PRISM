@extends('layouts.maintenance-layout')

@section('title', 'Property Assignment')

@section('content')
@php
    $tabs = [
        'people' => ['label' => 'People directory', 'icon' => 'users'],
        'offices' => ['label' => 'By office / room', 'icon' => 'door-open'],
    ];
    $formatDate = fn ($value) => filled($value) ? \Carbon\Carbon::parse($value)->format('M d, Y') : '—';
    $statusTone = [
        'Active' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        'On Leave' => 'bg-sky-50 text-sky-700 ring-sky-100',
        'Inactive' => 'bg-slate-100 text-slate-600 ring-slate-200',
    ];
@endphp

<div class="space-y-6">
    @if (! $tableReady)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
            The property assignment tables are missing. Run <code class="font-mono text-xs">php artisan migrate</code> to enable this module.
        </div>
    @else

        @if ($view === 'people')
            <div class="flex flex-wrap items-center justify-end gap-2">
                <button
                    type="button"
                    onclick="openAddPersonModal()"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white shadow-lg shadow-slate-900/10 transition hover:-translate-y-0.5 hover:bg-[rgba(0,44,155,0.85)]"
                >
                    <i data-lucide="user-plus" class="h-4 w-4"></i>
                    Add person
                </button>
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between lg:gap-4">
                <div class="inline-flex shrink-0 rounded-xl bg-slate-100 p-1">
                    @foreach ($tabs as $key => $tab)
                        <a
                            href="{{ \App\Support\PropertyAssignments::url('?view='.$key) }}"
                            class="inline-flex h-9 items-center gap-2 rounded-lg px-3 text-sm font-semibold transition {{ $view === $key ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}"
                        >
                            <i data-lucide="{{ $tab['icon'] }}" class="h-4 w-4"></i>
                            {{ $tab['label'] }}
                        </a>
                    @endforeach
                </div>

                <form method="GET" action="{{ \App\Support\PropertyAssignments::url() }}" class="flex w-full gap-3 sm:w-auto sm:max-w-md lg:justify-end">
                    <input type="hidden" name="view" value="{{ $view }}">
                    @if ($view === 'people' && $filter !== 'all')
                        <input type="hidden" name="filter" value="{{ $filter }}">
                    @endif
                    @if ($view === 'people' && $departmentId)
                        <input type="hidden" name="department" value="{{ $departmentId }}">
                    @endif
                    <div class="relative min-w-0 flex-1 sm:w-64 sm:flex-none">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            placeholder="{{ $view === 'offices' ? 'Search room name' : 'Search name, ID, position, or office' }}"
                            class="h-10 w-full rounded-xl border-0 bg-slate-50 pl-10 pr-3 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                        >
                    </div>
                    <button type="submit" class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800">
                        <i data-lucide="search" class="h-4 w-4"></i>
                        Search
                    </button>
                </form>
            </div>

            @if ($view === 'people')
                <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach (\App\Support\PropertyAssignments::PEOPLE_FILTERS as $key => $label)
                            <a
                                href="{{ \App\Support\PropertyAssignments::url('?'.http_build_query(array_filter(['view' => 'people', 'filter' => $key === 'all' ? null : $key, 'department' => $departmentId, 'search' => $search ?: null]))) }}"
                                class="inline-flex h-8 items-center rounded-full px-3 text-xs font-semibold ring-1 transition {{ $filter === $key ? 'bg-slate-100 text-slate-900 ring-slate-300' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' }}"
                            >
                                {{ $label }}
                            </a>
                        @endforeach

                        @if ($departments->isNotEmpty())
                            <form method="GET" action="{{ \App\Support\PropertyAssignments::url() }}">
                                <input type="hidden" name="view" value="people">
                                @if ($filter !== 'all')
                                    <input type="hidden" name="filter" value="{{ $filter }}">
                                @endif
                                @if ($search !== '')
                                    <input type="hidden" name="search" value="{{ $search }}">
                                @endif
                                <select
                                    name="department"
                                    data-searchable="1"
                                    data-search-placeholder="Search department…"
                                    onchange="this.form.submit()"
                                    class="h-8 min-w-[11rem] rounded-full border-0 px-3 text-xs font-semibold ring-1 {{ $departmentId ? 'bg-slate-100 text-slate-900 ring-slate-300' : 'bg-white text-slate-600 ring-slate-200' }}"
                                >
                                    <option value="">All departments</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->department_id }}" @selected((int) $departmentId === (int) $department->department_id)>
                                            {{ $department->department_name }}{{ $department->department_is_archived ? ' (archived)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        @endif
                    </div>

                    @if ($unlinkedReporters > 0)
                        <form method="POST" action="{{ \App\Support\PropertyAssignments::route('people.import') }}" class="flex items-center gap-2 text-xs text-slate-500">
                            @csrf
                            <span>{{ $unlinkedReporters }} faculty/staff {{ \Illuminate\Support\Str::plural('account', $unlinkedReporters) }} not in the directory</span>
                            <button type="submit" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 font-semibold text-slate-700 hover:bg-slate-50">
                                <i data-lucide="download" class="h-3.5 w-3.5"></i>
                                Import
                            </button>
                        </form>
                    @endif
                </div>
            @endif

            @if ($view === 'people')
                @if ($rows->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-5 py-3">Person</th>
                                    <th class="px-5 py-3">Type - Department</th>
                                    <th class="px-5 py-3">Room</th>
                                    <th class="px-5 py-3">Equipment</th>
                                    <th class="px-5 py-3">Status</th>
                                    <th class="px-5 py-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($rows as $row)
                                    @php $personUrl = \App\Support\PropertyAssignments::route('person', $row->custodian_id); @endphp
                                    <tr class="align-top transition hover:bg-slate-50/80">
                                        <td class="px-5 py-3">
                                            <a href="{{ $personUrl }}" class="text-base font-semibold text-slate-950 hover:underline">{{ $row->custodian_full_name }}</a>
                                            <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                                <span class="font-mono text-sm font-medium text-slate-600">{{ $row->custodian_employee_id ?: 'No employee ID' }}</span>
                                                <span class="inline-flex items-center gap-1 text-xs text-slate-500" title="Employment basis">
                                                    <i data-lucide="users" class="h-3.5 w-3.5"></i>
                                                    {{ $row->custodian_employment_basis ?: '—' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-5 py-3 text-sm text-slate-700">
                                            {{ $row->custodian_position ?: '—' }}{{ $row->custodian_department ? ' - '.$row->custodian_department : '' }}
                                        </td>
                                        <td class="px-5 py-3 text-slate-700">{{ $row->home_room_name ?: '—' }}</td>
                                        <td class="px-5 py-3" onclick="event.stopPropagation()">
                                            @php
                                                $heldEquipment = collect(explode('||', (string) ($row->equipment_names ?? '')))
                                                    ->map(function ($chunk) {
                                                        $parts = explode('^^', (string) $chunk, 4);
                                                        // Prefer id^^name^^asset^^serial; fall back to older name^^asset^^serial.
                                                        $hasId = isset($parts[0]) && ctype_digit((string) $parts[0]) && count($parts) >= 2;
                                                        $equipmentId = $hasId ? (int) $parts[0] : 0;
                                                        $name = trim((string) ($hasId ? ($parts[1] ?? '') : ($parts[0] ?? '')));
                                                        if ($name === '') {
                                                            return null;
                                                        }
                                                        $asset = trim((string) ($hasId ? ($parts[2] ?? '') : ($parts[1] ?? '')));
                                                        $serial = trim((string) ($hasId ? ($parts[3] ?? '') : ($parts[2] ?? '')));
                                                        $unique = $asset !== ''
                                                            ? $asset
                                                            : ($serial !== '' ? 'SN: '.$serial : 'No asset tag');

                                                        return [
                                                            'id' => $equipmentId,
                                                            'name' => $name,
                                                            'unique' => $unique,
                                                        ];
                                                    })
                                                    ->filter()
                                                    ->values();
                                                $heldCount = $heldEquipment->count();
                                                $firstEquipment = $heldEquipment->first();
                                                $moreCount = max(0, $heldCount - 1);
                                            @endphp
                                            @if ($heldCount === 0)
                                                <span class="text-slate-400">—</span>
                                            @elseif ($heldCount === 1)
                                                <div>
                                                    @if (($firstEquipment['id'] ?? 0) > 0)
                                                        <a href="{{ $personUrl }}?equipment={{ $firstEquipment['id'] }}#equipment-{{ $firstEquipment['id'] }}" class="text-sm font-medium text-slate-800 hover:text-[#0025cc] hover:underline">
                                                            {{ $firstEquipment['name'] }}
                                                        </a>
                                                    @else
                                                        <p class="text-sm font-medium text-slate-800">{{ $firstEquipment['name'] }}</p>
                                                    @endif
                                                    @if ($row->last_issued_at)
                                                        <p class="mt-0.5 text-xs font-medium text-slate-500">Last issued {{ $formatDate($row->last_issued_at) }}</p>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="group relative inline-flex max-w-full flex-col items-start gap-0.5">
                                                    <div class="inline-flex max-w-full items-center gap-1.5">
                                                        <span class="max-w-[9rem] truncate text-sm font-medium text-slate-800">{{ $firstEquipment['name'] }}</span>
                                                        <span class="inline-flex shrink-0 items-center rounded-md border border-sky-100 bg-sky-50 px-1.5 py-0.5 text-[11px] font-semibold text-sky-800">
                                                            +{{ $moreCount }} more
                                                        </span>
                                                    </div>
                                                    @if ($row->last_issued_at)
                                                        <p class="text-xs font-medium text-slate-500">Last issued {{ $formatDate($row->last_issued_at) }}</p>
                                                    @endif
                                                    {{-- pt-1.5 is an invisible hover bridge so the cursor can reach the panel without dropping group-hover --}}
                                                    <div
                                                        class="invisible absolute left-0 top-full z-30 w-72 pt-1.5 opacity-0 transition group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100"
                                                        role="tooltip"
                                                    >
                                                        <div class="rounded-xl border border-slate-200 bg-white p-2 shadow-lg shadow-slate-900/10">
                                                            <p class="px-1.5 pb-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                                                Held equipment · {{ $heldCount }}
                                                            </p>
                                                            <ul class="max-h-[14rem] space-y-0.5 overflow-y-auto overscroll-contain pr-0.5">
                                                                @foreach ($heldEquipment as $equipment)
                                                                    <li>
                                                                        @if (($equipment['id'] ?? 0) > 0)
                                                                            <a
                                                                                href="{{ $personUrl }}?equipment={{ $equipment['id'] }}#equipment-{{ $equipment['id'] }}"
                                                                                class="block rounded-lg px-2.5 py-2 transition hover:bg-sky-50 focus:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-[#0025cc]/20"
                                                                            >
                                                                                <p class="text-sm font-semibold text-slate-900">{{ $equipment['name'] }}</p>
                                                                                <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $equipment['unique'] }}</p>
                                                                            </a>
                                                                        @else
                                                                            <div class="rounded-lg px-2.5 py-2">
                                                                                <p class="text-sm font-semibold text-slate-900">{{ $equipment['name'] }}</p>
                                                                                <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $equipment['unique'] }}</p>
                                                                            </div>
                                                                        @endif
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3">
                                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $statusTone[$row->custodian_status] ?? $statusTone['Inactive'] }}">{{ $row->custodian_status }}</span>
                                        </td>
                                        <td class="px-5 py-3 text-center">
                                            <a
                                                href="{{ $personUrl }}"
                                                class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                                                title="Open person record"
                                                aria-label="Open record for {{ $row->custodian_full_name }}"
                                            >
                                                <i data-lucide="file-text" class="h-4 w-4"></i>
                                                Record
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-5 py-16 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                            <i data-lucide="user-round-check" class="h-6 w-6"></i>
                        </div>
                        <p class="mt-4 text-sm font-semibold text-slate-900">
                            {{ $search !== '' || $filter !== 'all' || $departmentId ? 'No one matches this search or filter' : 'The people directory is empty' }}
                        </p>
                        <p class="mt-1 text-sm text-slate-500">
                            Add anyone who can be accountable for property, even people without a system account.
                        </p>
                        <button onclick="openAddPersonModal()" class="mt-4 inline-flex h-10 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001fad]">
                            <i data-lucide="user-plus" class="h-4 w-4"></i>
                            Add person
                        </button>
                    </div>
                @endif
            @else
                <div class="divide-y divide-slate-100">
                    @forelse ($rows as $row)
                        <a
                            href="{{ \App\Support\PropertyAssignments::url('/rooms/'.$row->room_id) }}"
                            class="flex flex-col gap-2 px-4 py-4 transition hover:bg-slate-50/80 sm:flex-row sm:items-center sm:justify-between sm:px-5"
                        >
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate text-sm font-semibold text-slate-950">{{ $row->room_name }}</p>
                                    @if ($row->room_type)
                                        <span class="rounded-full bg-slate-50 px-2 py-0.5 text-[11px] font-semibold text-slate-600 ring-1 ring-slate-200/80">{{ $row->room_type }}</span>
                                    @endif
                                </div>

                                <p class="mt-1 truncate text-sm text-slate-500">
                                    {{ implode(' · ', array_filter([$row->building_name, $row->floor_level ? 'Floor '.$row->floor_level : null])) ?: '—' }}
                                </p>
                            </div>
                            <div class="flex shrink-0 flex-wrap items-center gap-2 text-xs font-semibold">
                                <span class="rounded-full bg-teal-50 px-3 py-1 text-teal-700 ring-1 ring-teal-100">
                                    {{ $row->assigned_count }} assigned · {{ $row->custodian_count }} {{ \Illuminate\Support\Str::plural('person', $row->custodian_count) }}
                                </span>
                                @if ($row->unassigned_count > 0)
                                    <span class="rounded-full bg-amber-50 px-3 py-1 text-amber-700 ring-1 ring-amber-100">
                                        {{ $row->unassigned_count }} unassigned
                                    </span>
                                @endif
                            </div>
                        </a>
                    @empty
                        <div class="px-5 py-16 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                <i data-lucide="door-open" class="h-6 w-6"></i>
                            </div>
                            <p class="mt-4 text-sm font-semibold text-slate-900">No offices found</p>
                            <p class="mt-1 text-sm text-slate-500">
                                Rooms of type {{ implode(', ', \App\Support\PropertyAssignments::OFFICE_ROOM_TYPES) }}, or any room with assigned items, appear here.
                            </p>
                        </div>
                    @endforelse
                </div>
            @endif

            @if (method_exists($rows, 'links') && $rows->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $rows->links() }}
                </div>
            @endif
        </div>
    @endif
                                    <!-- Add Person Modal (mounted to body on open so backdrop covers topbar) -->
                                    <div id="addPersonModal" class="fixed inset-0 z-[12000] hidden items-center justify-center bg-[#0b1220]/70 p-4" onclick="if (event.target === this) closeAddPersonModal()">
                                        <div class="relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl shadow-slate-950/10" onclick="event.stopPropagation()">
                                            <button type="button" onclick="closeAddPersonModal()" class="absolute right-4 top-4 z-10 flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                                                <i data-lucide="x" class="h-4 w-4"></i>
                                            </button>
                                            @include('maintenance-personnel.property-assignments._person-form-fields', ['modalEmbed' => true])
                                        </div>
                                    </div>

                                    <script>
                                        function mountAddPersonModal(modal) {
                                            if (modal && modal.parentElement !== document.body) {
                                                document.body.appendChild(modal);
                                            }
                                            return modal;
                                        }

                                        function openAddPersonModal() {
                                            const modal = mountAddPersonModal(document.getElementById('addPersonModal'));
                                            if (!modal) return;
                                            modal.classList.remove('hidden');
                                            modal.classList.add('flex');
                                            document.body.classList.add('overflow-hidden');
                                            if (window.lucide) lucide.createIcons();
                                            try {
                                                const input = modal.querySelector('[data-phone-input]');
                                                if (typeof window.refreshPrismPhoneInput === 'function' && input) {
                                                    requestAnimationFrame(function () {
                                                        window.refreshPrismPhoneInput(input);
                                                    });
                                                }
                                            } catch (e) { /* ignore */ }
                                        }

                                        function closeAddPersonModal() {
                                            if (typeof window.closeAllPrismPhoneDropdowns === 'function') {
                                                window.closeAllPrismPhoneDropdowns();
                                            }
                                            const modal = document.getElementById('addPersonModal');
                                            if (!modal) return;
                                            modal.classList.add('hidden');
                                            modal.classList.remove('flex');
                                            document.body.classList.remove('overflow-hidden');
                                        }
                                    </script>

                                    <script>
                                        // Sync custodian employee id for modal form (same logic as standalone page)
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

                                        function syncCustodianEmployeeIdModal() {
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

                                        (function () {
                                            ['custodian-employment-type', 'custodianEmployeeDigits'].forEach((id) => {
                                                const el = document.getElementById(id);
                                                if (!el) return;
                                                el.addEventListener(id === 'custodian-employment-type' ? 'change' : 'input', () => syncCustodianEmployeeIdModal());
                                            });

                                            const field = document.getElementById('custodianEmployeeIdField');
                                            const digits = document.getElementById('custodianEmployeeDigits');
                                            if (field && digits) {
                                                field.addEventListener('click', function () { try { digits.focus(); } catch (e) { } });
                                            }

                                            // initialize when modal exists on page
                                            try { syncCustodianEmployeeIdModal(); } catch (e) { }
                                        })();
                                    </script>

                                    @php
                                        $__personFormFields = [
                                            'custodian_first_name', 'custodian_middle_name', 'custodian_last_name',
                                            'custodian_employee_id', 'custodian_position', 'custodian_employment_basis', 'custodian_department_id',
                                            'custodian_room_id', 'custodian_email_address', 'custodian_contact_number',
                                            'custodian_status', 'custodian_reporter_id', 'custodian_notes'
                                        ];
                                        $__hasPersonErrors = false;
                                        foreach ($__personFormFields as $__f) {
                                            if ($errors->has($__f)) { $__hasPersonErrors = true; break; }
                                        }
                                    @endphp

                                    @if ($__hasPersonErrors)
                                        <script>
                                            document.addEventListener('DOMContentLoaded', function () {
                                                try { openAddPersonModal(); } catch (e) { /* ignore */ }
                                                try { window.scrollTo(0, 0); } catch (e) { }
                                            });
                                        </script>
                                    @endif
    </div>
@endsection
