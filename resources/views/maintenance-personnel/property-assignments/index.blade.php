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
    $readOnly = \App\Support\PropertyAssignments::isReadOnly();
@endphp

<div class="space-y-6">
    @if (! $tableReady)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
            The property assignment tables are missing. Run <code class="font-mono text-xs">php artisan migrate</code> to enable this module.
        </div>
    @else
        @include('layouts.partials.maintenance-stat-cards', [
            'cards' => [
                ['label' => 'Items assigned', 'hint' => 'Across '.number_format($stats['offices']).' '.\Illuminate\Support\Str::plural('room', $stats['offices']), 'value' => number_format($stats['active'])],
                ['label' => 'People holding property', 'hint' => number_format($stats['people']).' in the directory', 'value' => number_format($stats['custodians'])],
                ['label' => 'Unassigned office items', 'hint' => 'Individual items in offices with no custodian', 'value' => number_format($stats['unassigned'])],
            ],
        ])

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="inline-flex rounded-xl bg-slate-100 p-1">
                    @foreach ($tabs as $key => $tab)
                        <a
                            href="{{ url('/maintenance/property-assignments?view='.$key) }}"
                            class="inline-flex h-9 items-center gap-2 rounded-lg px-3 text-sm font-semibold transition {{ $view === $key ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}"
                        >
                            <i data-lucide="{{ $tab['icon'] }}" class="h-4 w-4"></i>
                            {{ $tab['label'] }}
                        </a>
                    @endforeach
                </div>

                <div class="flex flex-1 flex-col gap-3 sm:flex-row lg:max-w-2xl lg:justify-end">
                    <form method="GET" action="{{ url('/maintenance/property-assignments') }}" class="flex flex-1 gap-3 lg:max-w-md">
                        <input type="hidden" name="view" value="{{ $view }}">
                        @if ($view === 'people' && $filter !== 'all')
                            <input type="hidden" name="filter" value="{{ $filter }}">
                        @endif
                        @if ($view === 'people' && $departmentId)
                            <input type="hidden" name="department" value="{{ $departmentId }}">
                        @endif
                        <div class="relative flex-1">
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

                    @if ($view === 'people' && ! $readOnly)
                        <a
                            href="{{ route('maintenance.property-assignments.people.create') }}"
                            class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-[#001fad]"
                        >
                            <i data-lucide="user-plus" class="h-4 w-4"></i>
                            Add person
                        </a>
                    @endif
                </div>
            </div>

            @if ($view === 'people')
                <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach (\App\Support\PropertyAssignments::PEOPLE_FILTERS as $key => $label)
                            <a
                                href="{{ url('/maintenance/property-assignments?'.http_build_query(array_filter(['view' => 'people', 'filter' => $key === 'all' ? null : $key, 'department' => $departmentId, 'search' => $search ?: null]))) }}"
                                class="inline-flex h-8 items-center rounded-full px-3 text-xs font-semibold ring-1 transition {{ $filter === $key ? 'bg-slate-100 text-slate-900 ring-slate-300' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' }}"
                            >
                                {{ $label }}
                            </a>
                        @endforeach

                        @if ($departments->isNotEmpty())
                            <form method="GET" action="{{ url('/maintenance/property-assignments') }}">
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

                    @if ($unlinkedReporters > 0 && ! $readOnly)
                        <form method="POST" action="{{ route('maintenance.property-assignments.people.import') }}" class="flex items-center gap-2 text-xs text-slate-500">
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
                                    <th class="px-5 py-3">Position / office</th>
                                    <th class="px-5 py-3">Home room</th>
                                    <th class="px-5 py-3">Contact</th>
                                    <th class="px-5 py-3">Property held</th>
                                    <th class="px-5 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($rows as $row)
                                    @php $personUrl = route('maintenance.property-assignments.person', $row->custodian_id); @endphp
                                    <tr class="cursor-pointer align-top transition hover:bg-slate-50/80" onclick="window.location='{{ $personUrl }}'">
                                        <td class="px-5 py-3">
                                            <a href="{{ $personUrl }}" class="font-semibold text-slate-950 hover:underline">{{ $row->custodian_full_name }}</a>
                                            <p class="font-mono text-xs text-slate-400">{{ $row->custodian_employee_id ?: 'No employee ID' }}</p>
                                            @if ($row->custodian_reporter_id)
                                                <p class="mt-0.5 inline-flex items-center gap-1 text-[11px] text-slate-400" title="Linked to a faculty/staff account">
                                                    <i data-lucide="link" class="h-3 w-3"></i> System account
                                                </p>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-slate-700">
                                            {{ $row->custodian_position ?: '—' }}
                                            @if ($row->custodian_department)
                                                <p class="text-xs text-slate-500">{{ $row->custodian_department }}</p>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-slate-700">{{ $row->home_room_name ?: '—' }}</td>
                                        <td class="px-5 py-3 text-xs text-slate-600">
                                            {{ $row->custodian_email_address ?: '' }}
                                            @if ($row->custodian_contact_number)
                                                <p>{{ $row->custodian_contact_number }}</p>
                                            @endif
                                            @if (! $row->custodian_email_address && ! $row->custodian_contact_number)
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3">
                                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $row->active_count > 0 ? 'bg-teal-50 text-teal-700 ring-teal-100' : 'bg-slate-50 text-slate-500 ring-slate-200' }}">
                                                <i data-lucide="package" class="h-3.5 w-3.5"></i>
                                                {{ $row->active_count }} {{ \Illuminate\Support\Str::plural('item', $row->active_count) }}
                                            </span>
                                            @if ($row->room_names)
                                                <p class="mt-1 max-w-[16rem] truncate text-xs text-slate-500" title="{{ $row->room_names }}">{{ $row->room_names }}</p>
                                            @endif
                                            @if ($row->last_issued_at)
                                                <p class="text-[11px] text-slate-400">Last issued {{ $formatDate($row->last_issued_at) }}</p>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3">
                                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $statusTone[$row->custodian_status] ?? $statusTone['Inactive'] }}">{{ $row->custodian_status }}</span>
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
                        @if ($readOnly)
                            <p class="mt-1 text-sm text-slate-500">
                                Maintenance adds people and assigns property. This view is read only.
                            </p>
                        @else
                            <p class="mt-1 text-sm text-slate-500">
                                Add anyone who can be accountable for property, even people without a system account.
                            </p>
                            <a href="{{ route('maintenance.property-assignments.people.create') }}" class="mt-4 inline-flex h-10 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001fad]">
                                <i data-lucide="user-plus" class="h-4 w-4"></i>
                                Add person
                            </a>
                        @endif
                    </div>
                @endif
            @else
                <div class="divide-y divide-slate-100">
                    @forelse ($rows as $row)
                        <a
                            href="{{ url('/maintenance/property-assignments/rooms/'.$row->room_id) }}"
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
</div>
@endsection
