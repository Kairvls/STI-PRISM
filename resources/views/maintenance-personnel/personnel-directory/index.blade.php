@extends('layouts.maintenance-layout')

@section('title', 'Personnel Directory')

@section('content')
@php
    $filterUrl = fn (array $overrides) => route('maintenance.personnel-directory.index', array_filter(array_merge([
        'status' => $status ?: null,
        'type' => $type ?: null,
        'department' => $departmentId ?: null,
        'search' => $search ?: null,
    ], $overrides)));
    $chipClass = fn (bool $active) => 'inline-flex h-8 items-center rounded-full px-3 text-xs font-semibold ring-1 transition '
        .($active ? 'bg-slate-100 text-slate-900 ring-slate-300' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50');
    $filtered = $search !== '' || $status !== '' || $type !== '' || $departmentId > 0;
    $canManageReporters = \App\Support\RoleAccess::isAdmin();
@endphp

<div class="space-y-6">
    @include('layouts.partials.maintenance-stat-cards', [
        'cards' => [
            ['label' => 'Active faculty', 'hint' => 'Can file reports', 'value' => number_format($stats['faculty'])],
            ['label' => 'Active staff', 'hint' => 'Can file reports', 'value' => number_format($stats['staff'])],
            ['label' => 'Inactive', 'hint' => number_format($stats['total']).' reporters in total', 'value' => number_format($stats['inactive'])],
            ['label' => 'Reports filed', 'hint' => 'By everyone in this list', 'value' => number_format($stats['reports'])],
        ],
    ])

    <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 text-sm text-slate-600">
        <div class="flex items-start gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#0025cc]/5 text-[#0025cc]">
                <i data-lucide="book-user" class="h-4 w-4"></i>
            </div>
            <div class="min-w-0">
                <p class="font-semibold text-slate-900">View only</p>
                <p class="mt-0.5 text-slate-500">
                    This is the list of faculty and staff who can file reports, with the reports each one has filed.
                    @if ($canManageReporters)
                        To add, upload, edit or deactivate reporters, use
                        <a href="{{ route('admin.reporters.index') }}" class="font-semibold text-[#0025cc] hover:underline">Equipment Reporters</a>.
                    @else
                        Reporters are added, edited and deactivated by the Administrator.
                    @endif
                </p>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between">
            <h2 class="text-base font-semibold text-slate-900">Faculty and staff</h2>
            <form method="GET" action="{{ route('maintenance.personnel-directory.index') }}" class="flex flex-col gap-2 sm:flex-row">
                @foreach (['status' => $status, 'type' => $type] as $key => $value)
                    @if ($value !== '')
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                @if ($hasDepartment)
                    <select name="department" onchange="this.form.submit()" class="h-10 rounded-xl border-0 bg-slate-50 px-3 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10 sm:w-56" data-searchable="1" data-search-placeholder="Search department">
                        <option value="">All departments</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->department_id }}" @selected($departmentId === (int) $department->department_id)>{{ $department->department_name }}</option>
                        @endforeach
                    </select>
                @endif
                <div class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input type="search" name="search" value="{{ $search }}" placeholder="Search ID, name, email or contact" class="h-10 w-full rounded-xl border-0 bg-slate-50 pl-10 pr-3 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10 sm:w-72">
                </div>
                <button type="submit" class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800">
                    <i data-lucide="search" class="h-4 w-4"></i>
                    Search
                </button>
            </form>
        </div>

        <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 px-4 py-3 sm:px-5">
            @foreach (['' => 'All', 'Active' => 'Active', 'Inactive' => 'Inactive'] as $key => $label)
                <a href="{{ $filterUrl(['status' => $key ?: null]) }}" class="{{ $chipClass($status === $key) }}">{{ $label }}</a>
            @endforeach
            @if ($hasType)
                <span class="mx-1 h-5 w-px bg-slate-200"></span>
                @foreach (['' => 'Faculty & staff', 'Faculty' => 'Faculty', 'Staff' => 'Staff'] as $key => $label)
                    <a href="{{ $filterUrl(['type' => $key ?: null]) }}" class="{{ $chipClass($type === $key) }}">{{ $label }}</a>
                @endforeach
            @endif
            @if ($filtered)
                <a href="{{ route('maintenance.personnel-directory.index') }}" class="ml-auto text-xs font-semibold text-slate-500 hover:text-slate-900">Clear filters</a>
            @endif
        </div>

        @if ($people->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Employee ID</th>
                            <th class="px-5 py-3">Name</th>
                            <th class="px-5 py-3">Type</th>
                            @if ($hasDepartment)
                                <th class="px-5 py-3">Department</th>
                            @endif
                            <th class="px-5 py-3">Contact</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Reports</th>
                            <th class="px-5 py-3 text-center">Records</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($people as $person)
                            @php
                                $inactiveRow = $person->reporter_status !== 'Active';
                            @endphp
                            <tr class="align-middle {{ $inactiveRow ? 'bg-slate-50/60' : '' }}">
                                <td class="px-5 py-3 font-mono text-[13px] font-medium text-slate-900">{{ $person->reporter_employee_id }}</td>
                                <td class="px-5 py-3">
                                    <p class="font-semibold {{ $inactiveRow ? 'text-slate-500' : 'text-slate-900' }}">{{ $person->reporter_full_name }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $person->reporter_email_address ?: 'No email on file' }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    @if (!empty($person->reporter_employment_type))
                                        <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">{{ $person->reporter_employment_type }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                @if ($hasDepartment)
                                    <td class="px-5 py-3 text-slate-600">{{ $person->department_name ?: '—' }}</td>
                                @endif
                                <td class="px-5 py-3 text-slate-600">{{ $person->reporter_contact_number ?: '—' }}</td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $inactiveRow ? 'bg-slate-100 text-slate-600 ring-slate-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-100' }}">{{ $person->reporter_status }}</span>
                                </td>
                                <td class="px-5 py-3">
                                    <p class="font-semibold text-slate-900">{{ number_format($person->reports_total) }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        @if ($person->reports_total > 0)
                                            {{ number_format($person->reports_open) }} open · {{ number_format($person->reports_resolved) }} resolved
                                        @else
                                            No reports yet
                                        @endif
                                    </p>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex justify-center">
                                        <a
                                            href="{{ route('maintenance.personnel-directory.index', ['reporter' => $person->reporter_id]) }}"
                                            class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-slate-100 px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-200 hover:text-slate-900"
                                        >
                                            <i data-lucide="history" class="h-3.5 w-3.5"></i>
                                            
                                        </a>
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
                    {{ $filtered ? 'No one matches these filters' : 'No reporters yet' }}
                </p>
                <p class="mx-auto mt-1 max-w-sm text-xs leading-5 text-slate-500">
                    @if ($canManageReporters)
                        Faculty and staff appear here once they are added in
                        <a href="{{ route('admin.reporters.index') }}" class="font-semibold text-[#0025cc] hover:underline">Equipment Reporters</a>.
                    @else
                        Faculty and staff appear here once the Administrator adds them as reporters.
                    @endif
                </p>
            </div>
        @endif
    </div>
</div>
@endsection
