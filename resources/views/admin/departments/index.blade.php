@extends('layouts.maintenance-layout')

@section('title', 'Departments')

@section('content')
@php
    $fieldClass = 'h-10 w-full rounded-xl border-0 bg-slate-50 px-3 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10';
    $formErrors = $errors->getBag('department');
    $reopen = $formErrors->any()
        ? [
            'id' => old('department_id') ? (int) old('department_id') : null,
            'name' => old('department_name', ''),
            'description' => old('department_description', ''),
        ]
        : null;
@endphp

<div
    class="space-y-6"
    x-data="{
        open: @js($reopen !== null),
        form: @js($reopen ?? ['id' => null, 'name' => '', 'description' => '']),
        storeUrl: @js(route('admin.departments.store')),
        updateBase: @js(url('/admin/departments')),
        start(dept = null) {
            this.form = dept ? { ...dept } : { id: null, name: '', description: '' };
            this.open = true;
            this.$nextTick(() => {
                window.lucide?.createIcons();
                document.getElementById('department-name-input')?.focus();
            });
        },
        init() {
            this.$watch('viewer.search', () => this.$nextTick(() => window.lucide?.createIcons()));
        },
        viewer: { open: false, loading: false, error: '', tab: 'equipment', search: '', data: null },
        async viewDept(id, tab = 'equipment') {
            this.viewer = { open: true, loading: true, error: '', tab, search: '', data: null };
            try {
                const response = await fetch(this.updateBase + '/' + id, { headers: { Accept: 'application/json' } });
                if (! response.ok) throw new Error();
                this.viewer.data = await response.json();
            } catch (e) {
                this.viewer.error = 'Could not load this department. Please try again.';
            } finally {
                this.viewer.loading = false;
                this.$nextTick(() => window.lucide?.createIcons());
            }
        },
        setViewTab(tab, search = '') {
            this.viewer.tab = tab;
            this.viewer.search = search;
            this.$nextTick(() => window.lucide?.createIcons());
        },
        matches(values) {
            const term = this.viewer.search.trim().toLowerCase();
            return term === '' || values.some((v) => String(v ?? '').toLowerCase().includes(term));
        },
        get viewEquipment() {
            return (this.viewer.data?.equipment ?? []).filter((item) => this.matches([
                item.name, item.asset_tag, item.serial, item.brand_model, item.category, item.room, item.holder, item.condition, item.status,
            ]));
        },
        get viewPeople() {
            return (this.viewer.data?.people ?? []).filter((person) => this.matches([
                person.name, person.employee_id, person.position, person.email, person.contact, person.room, person.status,
            ]));
        },
        profileHref(item) {
            return item.profile_url + '?return=' + encodeURIComponent(window.location.href);
        },
        initials(name) {
            return String(name || '?').split(/\s+/).filter(Boolean).slice(0, 2).map((p) => p[0].toUpperCase()).join('');
        },
        toneFor(value) {
            const v = String(value || '').toLowerCase();
            if (/(good|working|active|operational|new|available)/.test(v)) return 'bg-emerald-50 text-emerald-700 ring-emerald-100';
            if (/(repair|maint|fair|borrow|pending|replacement)/.test(v)) return 'bg-amber-50 text-amber-700 ring-amber-100';
            if (/(damage|broken|defect|lost|poor|inactive|condemn|dispos)/.test(v)) return 'bg-rose-50 text-rose-700 ring-rose-100';
            return 'bg-slate-100 text-slate-600 ring-slate-200';
        },
        formatDate(value) {
            if (! value) return '';
            const date = new Date(String(value).replace(' ', 'T'));
            return isNaN(date) ? '' : date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
        },
    }"
    @keydown.escape.window="open = false; viewer.open = false"
>
    @if (! $tableReady)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
            The departments table is missing. Run <code class="font-mono text-xs">php artisan migrate</code> to enable this page.
        </div>
    @else
        @include('layouts.partials.maintenance-stat-cards', [
            'cards' => [
                ['label' => 'Active departments', 'hint' => $stats['archived'] > 0 ? number_format($stats['archived']).' archived' : 'Shown when adding people', 'value' => number_format($stats['active'])],
                ['label' => 'People with a department', 'hint' => 'In the people directory', 'value' => number_format($stats['assigned_people'])],
                ['label' => 'People without a department', 'hint' => 'Edit them to set one', 'value' => number_format($stats['unassigned_people'])],
                ['label' => 'Equipment charged to an office', 'hint' => 'Tagged during a transfer', 'value' => number_format($stats['charged_equipment'] ?? 0)],
            ],
        ])

        <div class="flex items-start gap-3 rounded-2xl border border-sky-100 bg-sky-50/70 px-4 py-3 text-sm text-sky-900">
            <i data-lucide="info" class="mt-0.5 h-4 w-4 shrink-0"></i>
            <p>
                A department is an <span class="font-semibold">office</span>, not a place.
                A <span class="font-semibold">room</span> says where equipment physically is; a <span class="font-semibold">department</span> says which office owns it and is accountable for it.
                Equipment always has a room, and can also be charged to a department or issued to a person.
            </p>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Departments / offices</h2>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <form method="GET" action="{{ route('admin.departments.index') }}" class="flex gap-2">
                        @if ($filter !== 'active')
                            <input type="hidden" name="filter" value="{{ $filter }}">
                        @endif
                        <div class="relative">
                            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                            <input type="search" name="search" value="{{ $search }}" placeholder="Search department" class="h-10 w-full rounded-xl border-0 bg-slate-50 pl-10 pr-3 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10 sm:w-64">
                        </div>
                        <button type="submit" class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800">
                            <i data-lucide="search" class="h-4 w-4"></i>
                            Search
                        </button>
                    </form>
                    <button type="button" @click="start()" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-[#001fad]">
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        Add department
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 border-b border-slate-100 px-4 py-3 sm:px-5">
                @foreach (\App\Support\Departments::FILTERS as $key => $label)
                    <a
                        href="{{ route('admin.departments.index', array_filter(['filter' => $key === 'active' ? null : $key, 'search' => $search ?: null])) }}"
                        class="inline-flex h-8 items-center rounded-full px-3 text-xs font-semibold ring-1 transition {{ $filter === $key ? 'bg-slate-100 text-slate-900 ring-slate-300' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            @if ($departments->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Department</th>
                                <th class="px-5 py-3">People</th>
                                <th class="px-5 py-3">Equipment charged</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-center justify-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($departments as $dept)
                                @php
                                    $payload = [
                                        'id' => (int) $dept->department_id,
                                        'name' => $dept->department_name,
                                        'description' => (string) $dept->department_description,
                                    ];
                                @endphp
                                <tr class="align-middle {{ $dept->department_is_archived ? 'bg-slate-50/60' : '' }}">
                                    <td class="px-5 py-3">
                                        <button type="button" @click="viewDept({{ (int) $dept->department_id }})" class="text-left font-semibold hover:text-[#0025cc] hover:underline {{ $dept->department_is_archived ? 'text-slate-500' : 'text-slate-900' }}">{{ $dept->department_name }}</button>
                                        @if ($dept->department_description)
                                            <p class="mt-0.5 text-xs text-slate-500">{{ $dept->department_description }}</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        @if ($dept->people_count > 0)
                                            <a href="{{ route('maintenance.property-assignments.index', ['department' => $dept->department_id]) }}" class="inline-flex items-center gap-1.5 font-semibold text-[#0025cc] hover:underline">
                                                <i data-lucide="users" class="h-3.5 w-3.5"></i>
                                                {{ $dept->people_count }} {{ \Illuminate\Support\Str::plural('person', $dept->people_count) }}
                                            </a>
                                            @if ($dept->active_people_count < $dept->people_count)
                                                <p class="text-[11px] text-slate-400">{{ $dept->active_people_count }} not inactive</p>
                                            @endif
                                        @else
                                            <span class="text-slate-400">No one yet</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        @if ($dept->equipment_count > 0)
                                            <a href="{{ route('admin.inventory.deployed', ['department' => $dept->department_id]) }}" class="inline-flex items-center gap-1.5 font-semibold text-[#0025cc] hover:underline">
                                                <i data-lucide="package" class="h-3.5 w-3.5"></i>
                                                {{ $dept->equipment_count }} {{ \Illuminate\Support\Str::plural('item', $dept->equipment_count) }}
                                            </a>
                                            @if ($dept->equipment_units > $dept->equipment_count)
                                                <p class="text-[11px] text-slate-400">{{ number_format($dept->equipment_units) }} units in total</p>
                                            @endif
                                        @else
                                            <span class="text-slate-400">None yet</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        @if ($dept->department_is_archived)
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 ring-1 ring-slate-200">Archived</span>
                                        @else
                                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-100">Active</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button
                                                type="button"
                                                @click="viewDept({{ (int) $dept->department_id }})"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#0025cc] transition hover:bg-slate-50 active:scale-95"
                                                data-tooltip="View equipment and people"
                                                aria-label="View equipment and people"
                                            >
                                                <i data-lucide="eye" class="h-4 w-4"></i>
                                            </button>
                                            <button
                                                type="button"
                                                @click="start(@js($payload))"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001db3]"
                                                data-tooltip="Edit department"
                                                aria-label="Edit department"
                                            >
                                                <i data-lucide="pencil" class="h-4 w-4"></i>
                                            </button>
                                            @if ($dept->department_is_archived)
                                                <form method="POST" action="{{ route('admin.departments.restore', $dept->department_id) }}">
                                                    @csrf
                                                    <button
                                                        type="submit"
                                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#0025cc] transition hover:bg-slate-50 active:scale-95"
                                                        data-tooltip="Restore department"
                                                        aria-label="Restore department"
                                                    >
                                                        <i data-lucide="archive-restore" class="h-4 w-4"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.departments.archive', $dept->department_id) }}"
                                                    data-pur-confirm="{{ $dept->department_name }} will be hidden when adding new people. Anyone already in it keeps it, and you can restore it anytime."
                                                    data-pur-confirm-title="Archive department?"
                                                    data-pur-confirm-ok="Archive"
                                                    data-pur-confirm-kind="archive"
                                                >
                                                    @csrf
                                                    <button
                                                        type="submit"
                                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#007a3f] transition hover:bg-slate-50 active:scale-95"
                                                        data-tooltip="Archive department"
                                                        aria-label="Archive department"
                                                    >
                                                        <i data-lucide="archive" class="h-4 w-4"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            @if ((int) $dept->people_count === 0)
                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.departments.destroy', $dept->department_id) }}"
                                                    data-pur-confirm="{{ $dept->department_name }} will be permanently removed. This cannot be undone."
                                                    data-pur-confirm-title="Delete department?"
                                                    data-pur-confirm-ok="Delete"
                                                    data-pur-confirm-danger="1"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="submit"
                                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-red-700 transition hover:bg-slate-50 active:scale-95"
                                                        data-tooltip="Delete department"
                                                        aria-label="Delete department"
                                                    >
                                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <span
                                                    class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-300"
                                                    data-tooltip="Has people. Archive it instead."
                                                    aria-label="Cannot delete: department has people"
                                                >
                                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-5 py-16 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <i data-lucide="building-2" class="h-6 w-6"></i>
                    </div>
                    <p class="mt-4 text-sm font-semibold text-slate-900">
                        {{ $search !== '' ? 'No department matches your search' : ($filter === 'archived' ? 'No archived departments' : 'No departments yet') }}
                    </p>
                    <button type="button" @click="start()" class="mt-4 inline-flex h-10 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001fad]">
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        Add department
                    </button>
                </div>
            @endif
        </div>
    @endif

    <template x-teleport="body">
    <div x-show="viewer.open" x-cloak class="fixed inset-0 z-[1300] flex items-start justify-center overflow-y-auto bg-[#0b1220]/70 p-4" @click.self="viewer.open = false">
        <div class="my-auto flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
                <div class="flex min-w-0 items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0025cc]/10 text-[#0025cc]">
                        <i data-lucide="building-2" class="h-5 w-5"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="truncate text-base font-semibold text-slate-900" x-text="viewer.data?.department.name ?? 'Department'"></h3>
                            <span x-show="viewer.data?.department.archived" class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 ring-1 ring-slate-200">Archived</span>
                        </div>
                        <p class="mt-0.5 text-xs text-slate-500" x-text="viewer.data?.department.description || 'Equipment this office answers for and the people who belong to it.'"></p>
                    </div>
                </div>
                <button type="button" @click="viewer.open = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <div x-show="viewer.loading" class="flex flex-1 items-center justify-center gap-2 px-5 py-16 text-sm text-slate-500">
                <i data-lucide="loader-2" class="h-4 w-4 animate-spin"></i>
                Loading department...
            </div>

            <div x-show="! viewer.loading && viewer.error" class="px-5 py-12 text-center text-sm text-rose-600" x-text="viewer.error"></div>

            <template x-if="! viewer.loading && viewer.data">
                <div class="flex min-h-0 flex-1 flex-col">
                    <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="inline-flex rounded-xl bg-slate-100 p-1 text-sm font-semibold">
                            <button type="button" @click="setViewTab('equipment')" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 transition" :class="viewer.tab === 'equipment' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                                <i data-lucide="package" class="h-4 w-4"></i>
                                Equipment
                                <span class="rounded-full bg-slate-200/80 px-1.5 text-[11px] text-slate-700" x-text="viewer.data.equipment.length"></span>
                            </button>
                            <button type="button" @click="setViewTab('people')" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 transition" :class="viewer.tab === 'people' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                                <i data-lucide="users" class="h-4 w-4"></i>
                                People
                                <span class="rounded-full bg-slate-200/80 px-1.5 text-[11px] text-slate-700" x-text="viewer.data.people.length"></span>
                            </button>
                        </div>
                        <div class="relative">
                            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                            <input type="search" x-model="viewer.search" :placeholder="viewer.tab === 'equipment' ? 'Search equipment, tag, room, holder' : 'Search name, position, email'" class="h-9 w-full rounded-xl border-0 bg-slate-50 pl-10 pr-3 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10 sm:w-72">
                        </div>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                        <div x-show="viewer.tab === 'equipment'" class="space-y-2.5">
                            <template x-for="item in viewEquipment" :key="'eq-' + item.id">
                                <div class="flex gap-3 rounded-xl border border-slate-200 p-3 transition hover:border-slate-300">
                                    <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                                        <template x-if="item.image_url">
                                            <img :src="item.image_url" :alt="item.name" class="h-full w-full object-cover" loading="lazy">
                                        </template>
                                        <template x-if="! item.image_url">
                                            <div class="flex h-full w-full items-center justify-center text-slate-400">
                                                <i data-lucide="package" class="h-6 w-6"></i>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-start justify-between gap-2">
                                            <div class="min-w-0">
                                                <p class="font-semibold text-slate-900">
                                                    <span x-text="item.name"></span>
                                                    <span x-show="item.quantity > 1" class="ml-1 text-xs font-medium text-slate-500" x-text="'× ' + item.quantity"></span>
                                                </p>
                                                <p class="text-xs text-slate-500" x-text="[item.asset_tag, item.category, item.brand_model].filter(Boolean).join(' · ') || 'No tag or category'"></p>
                                            </div>
                                            <a :href="profileHref(item)" class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-semibold text-[#0025cc] hover:bg-slate-50">
                                                <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                                                Profile
                                            </a>
                                        </div>
                                        <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[11px] font-semibold">
                                            <span x-show="item.condition" class="rounded-full px-2 py-0.5 ring-1" :class="toneFor(item.condition)" x-text="item.condition"></span>
                                            <span x-show="item.status" class="rounded-full px-2 py-0.5 ring-1" :class="toneFor(item.status)" x-text="item.status"></span>
                                            <span x-show="item.charged" class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-0.5 text-indigo-700 ring-1 ring-indigo-100">
                                                <i data-lucide="building-2" class="h-3 w-3"></i>
                                                Charged to office
                                            </span>
                                            <span x-show="item.holder" class="inline-flex items-center gap-1 rounded-full bg-violet-50 px-2 py-0.5 text-violet-700 ring-1 ring-violet-100">
                                                <i data-lucide="user-round" class="h-3 w-3"></i>
                                                <span x-text="'Issued to ' + item.holder + (item.holder_in_department ? '' : ' (other dept)')"></span>
                                            </span>
                                        </div>
                                        <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                                            <span class="inline-flex items-center gap-1">
                                                <i data-lucide="map-pin" class="h-3.5 w-3.5"></i>
                                                <span x-text="item.room || 'No room'"></span>
                                            </span>
                                            <span x-show="item.serial" class="inline-flex items-center gap-1">
                                                <i data-lucide="hash" class="h-3.5 w-3.5"></i>
                                                <span x-text="'S/N ' + item.serial"></span>
                                            </span>
                                            <span x-show="item.issued_at" class="inline-flex items-center gap-1">
                                                <i data-lucide="calendar" class="h-3.5 w-3.5"></i>
                                                <span x-text="'Issued ' + formatDate(item.issued_at)"></span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <div x-show="viewEquipment.length === 0" class="rounded-xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                <p class="font-semibold text-slate-700" x-text="viewer.search ? 'No equipment matches your search' : 'No equipment yet'"></p>
                                <p x-show="! viewer.search" class="mt-1 text-xs">Equipment shows here once it is charged to this office in a transfer, or issued to one of its people.</p>
                            </div>
                        </div>

                        <div x-show="viewer.tab === 'people'" class="grid gap-2.5 sm:grid-cols-2">
                            <template x-for="person in viewPeople" :key="'p-' + person.id">
                                <div class="flex flex-col rounded-xl border border-slate-200 p-3 transition hover:border-slate-300">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#0025cc]/10 text-sm font-bold text-[#0025cc]" x-text="initials(person.name)"></div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <p class="truncate font-semibold text-slate-900" x-text="person.name"></p>
                                                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1" :class="toneFor(person.status)" x-text="person.status"></span>
                                            </div>
                                            <p class="text-xs text-slate-500" x-text="[person.position, person.employee_id ? 'ID ' + person.employee_id : null].filter(Boolean).join(' · ') || 'No position set'"></p>
                                        </div>
                                    </div>
                                    <div class="mt-2.5 space-y-1 text-xs text-slate-600">
                                        <p x-show="person.email" class="flex items-center gap-1.5 truncate">
                                            <i data-lucide="mail" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                            <span class="truncate" x-text="person.email"></span>
                                        </p>
                                        <p x-show="person.contact" class="flex items-center gap-1.5">
                                            <i data-lucide="phone" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                            <span x-text="person.contact"></span>
                                        </p>
                                        <p class="flex items-center gap-1.5">
                                            <i data-lucide="map-pin" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                            <span x-text="person.room || 'No room set'"></span>
                                        </p>
                                    </div>
                                    <div class="mt-3 flex items-center justify-between gap-2 border-t border-slate-100 pt-2.5">
                                        <button
                                            type="button"
                                            @click="person.held_count > 0 && setViewTab('equipment', person.name)"
                                            class="inline-flex items-center gap-1 text-xs font-semibold"
                                            :class="person.held_count > 0 ? 'text-[#0025cc] hover:underline' : 'cursor-default text-slate-400'"
                                        >
                                            <i data-lucide="package" class="h-3.5 w-3.5"></i>
                                            <span x-text="person.held_count > 0 ? person.held_count + (person.held_count === 1 ? ' item held' : ' items held') : 'No items held'"></span>
                                        </button>
                                        <a :href="person.profile_url" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-semibold text-[#0025cc] hover:bg-slate-50">
                                            <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                                            Open
                                        </a>
                                    </div>
                                </div>
                            </template>
                            <div x-show="viewPeople.length === 0" class="rounded-xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500 sm:col-span-2">
                                <p class="font-semibold text-slate-700" x-text="viewer.search ? 'No one matches your search' : 'No one in this department yet'"></p>
                                <p x-show="! viewer.search" class="mt-1 text-xs">Set a person's department when adding or editing them in Property Assignment.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
    </template>

    <template x-teleport="body">
    <div x-show="open" x-cloak class="fixed inset-0 z-[1300] flex items-start justify-center overflow-y-auto bg-[#0b1220]/70 p-4" @click.self="open = false">
        <form
            method="POST"
            :action="form.id ? updateBase + '/' + form.id : storeUrl"
            class="my-auto w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl"
        >
            @csrf
            <template x-if="form.id">
                <input type="hidden" name="_method" value="PUT">
            </template>
            <input type="hidden" name="department_id" :value="form.id ?? ''">

            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h3 class="text-base font-semibold text-slate-900" x-text="form.id ? 'Edit department' : 'Add department'"></h3>
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

                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Name <span class="text-red-500">*</span></span>
                    <input type="text" name="department_name" id="department-name-input" x-model="form.name" required maxlength="120" placeholder="e.g. Registrar" class="{{ $fieldClass }}">
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Description</span>
                    <input type="text" name="department_description" x-model="form.description" maxlength="255" placeholder="Optional, e.g. Enrollment records and transcripts" class="{{ $fieldClass }}">
                </label>

                <p x-show="form.id" class="text-xs text-slate-400">Renaming updates everyone in this department automatically.</p>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-4">
                <button type="button" @click="open = false" class="inline-flex h-10 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001fad]">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    <span x-text="form.id ? 'Save changes' : 'Add department'"></span>
                </button>
            </div>
        </form>
    </div>
    </template>
</div>
@endsection
