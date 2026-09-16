@extends('layouts.admin-layout')

@section('title', 'Equipment Movements')

@section('content')
<link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">

@php
    $tabLabels = [
        'transfers' => 'Transfer records',
        'borrowing' => 'Borrowing records',
        'disposal' => 'Disposal records',
    ];

    $tabHints = [
        'transfers' => 'Track equipment room-to-room transfers.',
        'borrowing' => 'Monitor active and historical equipment borrows.',
        'disposal' => 'Review disposed equipment and inventory status.',
    ];

    $rowCount = method_exists($rows, 'total') ? $rows->total() : $rows->count();

    $borrowStatusClasses = [
        'Overdue' => 'border-rose-200 bg-rose-50 text-rose-700',
        'Borrowed' => 'border-amber-200 bg-amber-50 text-amber-800',
        'Returned' => 'border-green-200 bg-green-50 text-green-700',
        'Active' => 'border-blue-200 bg-blue-50 text-blue-700',
    ];
@endphp

<div class="admin-page space-y-6">
    {{-- Summary metrics --}}
    <div class="pur-card">
        <div class="grid grid-cols-2 divide-gray-100 lg:grid-cols-4 lg:divide-x">
            <a href="{{ route('admin.operations.movements', ['tab' => 'transfers']) }}" class="block px-5 py-5 transition hover:bg-gray-50/70">
                <div class="flex items-center gap-2">
                    <p class="text-2xl font-semibold tracking-tight text-gray-950">{{ $counts['transfers'] }}</p>
                    @if($tab === 'transfers')
                        <span class="h-1.5 w-1.5 rounded-full bg-[#0025cc]"></span>
                    @endif
                </div>
                <p class="mt-1 text-xs font-medium text-gray-500">Transfers</p>
            </a>

            <a href="{{ route('admin.operations.movements', ['tab' => 'borrowing', 'filter' => 'active']) }}" class="block px-5 py-5 transition hover:bg-gray-50/70">
                <div class="flex items-center gap-2">
                    <p class="text-2xl font-semibold tracking-tight text-gray-950">{{ $counts['borrowing_active'] }}</p>
                    @if((int) $counts['borrowing_overdue'] > 0)
                        <span class="h-1.5 w-1.5 rounded-full bg-rose-400"></span>
                    @elseif($tab === 'borrowing')
                        <span class="h-1.5 w-1.5 rounded-full bg-[#0025cc]"></span>
                    @endif
                </div>
                <p class="mt-1 text-xs font-medium text-gray-500">Active borrows</p>
                @if((int) $counts['borrowing_overdue'] > 0)
                    <p class="mt-1 text-xs font-medium text-rose-600">{{ $counts['borrowing_overdue'] }} overdue</p>
                @endif
            </a>

            <a href="{{ route('admin.operations.movements', ['tab' => 'disposal']) }}" class="block px-5 py-5 transition hover:bg-gray-50/70">
                <div class="flex items-center gap-2">
                    <p class="text-2xl font-semibold tracking-tight text-gray-950">{{ $counts['disposal'] }}</p>
                    @if($tab === 'disposal')
                        <span class="h-1.5 w-1.5 rounded-full bg-[#0025cc]"></span>
                    @endif
                </div>
                <p class="mt-1 text-xs font-medium text-gray-500">Disposals</p>
            </a>

            <a href="{{ route('admin.operations.equipment') }}" class="block px-5 py-5 transition hover:bg-gray-50/70">
                <p class="text-sm font-semibold text-gray-900">Inventory</p>
                <p class="mt-1 text-xs font-medium text-[#0025cc]">Open equipment monitor →</p>
            </a>
        </div>
    </div>

    {{-- Records --}}
    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">{{ $tabLabels[$tab] ?? 'Movement records' }}</h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">{{ $rowCount }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">{{ $tabHints[$tab] ?? 'Track equipment movement activity.' }}</p>
                </div>

                <form method="GET" class="flex w-full flex-col gap-2 sm:flex-row sm:items-center xl:w-auto">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <input type="hidden" name="filter" value="{{ $filter }}">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                        </svg>
                        <input
                            type="text"
                            name="q"
                            value="{{ $q }}"
                            placeholder="Search…"
                            class="h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-64"
                        >
                    </div>
                    <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-[#0025cc] px-4 text-[13px] font-medium text-white transition hover:bg-[#001fa8]">
                        Search
                    </button>
                    @if($q !== '' && $q !== null)
                        <a
                            href="{{ route('admin.operations.movements', ['tab' => $tab, 'filter' => $filter]) }}"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-100 px-3.5 text-[13px] font-medium text-gray-600 transition hover:bg-gray-50"
                        >Clear</a>
                    @endif
                </form>
            </div>

            @if(in_array($tab, ['transfers', 'borrowing'], true))
            <div class="mt-4 flex flex-wrap items-center gap-2">
                @if($tab === 'transfers')
                    @foreach(['all' => 'All', 'recent' => 'Last 30 days'] as $key => $label)
                        <a
                            href="{{ route('admin.operations.movements', ['tab' => 'transfers', 'filter' => $key, 'q' => $q]) }}"
                            class="pur-filter-chip {{ $filter === $key ? 'is-active' : '' }}"
                        >{{ $label }}</a>
                    @endforeach
                @elseif($tab === 'borrowing')
                    @foreach(['all' => 'All', 'active' => 'Active', 'Overdue' => 'Overdue', 'Borrowed' => 'Borrowed', 'Returned' => 'Returned'] as $key => $label)
                        <a
                            href="{{ route('admin.operations.movements', ['tab' => 'borrowing', 'filter' => $key, 'q' => $q]) }}"
                            class="pur-filter-chip {{ $filter === $key ? 'is-active' : '' }}"
                        >{{ $label }}</a>
                    @endforeach
                @endif
            </div>
            @endif
        </div>

        <div class="overflow-x-auto">
            @if($tab === 'transfers')
                <table class="pur-table min-w-[900px]">
                    <thead>
                        <tr>
                            <th>Equipment</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Remarks</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $when = $row->created_at
                                    ? \Carbon\Carbon::parse($row->created_at)->format('M j, Y g:i A')
                                    : '—';
                            @endphp
                            <tr class="transition hover:bg-gray-50/70">
                                <td>
                                    <p class="font-semibold text-gray-900">{{ $row->equipment_name ?: ('Equipment #'.$row->equipment_id) }}</p>
                                    @if(!empty($row->equipment_asset_tag))
                                        <p class="mt-0.5 text-xs text-gray-400">{{ $row->equipment_asset_tag }}</p>
                                    @endif
                                </td>
                                <td class="text-sm text-gray-600">{{ $row->from_room_name ?: '—' }}</td>
                                <td class="text-sm text-gray-600">{{ $row->to_room_name ?: '—' }}</td>
                                <td class="text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($row->remarks, 60) ?: '—' }}</td>
                                <td class="whitespace-nowrap text-sm text-gray-500">{{ $when }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="pur-empty">No transfers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @elseif($tab === 'borrowing')
                <table class="pur-table min-w-[900px]">
                    <thead>
                        <tr>
                            <th>Equipment</th>
                            <th>Borrower</th>
                            <th>Expected return</th>
                            <th>Status</th>
                            <th>Authorized by</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $status = $row->borrowing_status ?: '—';
                                $badgeClass = $borrowStatusClasses[$status] ?? 'border-gray-200 bg-gray-50 text-gray-600';
                                $expected = $row->borrowing_expected_return_date
                                    ? \Carbon\Carbon::parse($row->borrowing_expected_return_date)->format('M j, Y')
                                    : '—';
                            @endphp
                            <tr class="transition hover:bg-gray-50/70">
                                <td>
                                    <p class="font-semibold text-gray-900">{{ $row->equipment_name ?: '—' }}</p>
                                    @if(!empty($row->equipment_asset_tag))
                                        <p class="mt-0.5 text-xs text-gray-400">{{ $row->equipment_asset_tag }}</p>
                                    @endif
                                </td>
                                <td>
                                    <p class="text-sm text-gray-700">{{ $row->borrowing_borrower_name ?: '—' }}</p>
                                    @if(!empty($row->borrowing_borrower_department))
                                        <p class="mt-0.5 text-xs text-gray-400">{{ $row->borrowing_borrower_department }}</p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap text-sm {{ $status === 'Overdue' ? 'font-semibold text-rose-700' : 'text-gray-600' }}">
                                    {{ $expected }}
                                </td>
                                <td>
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium {{ $badgeClass }}">
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="text-sm text-gray-600">{{ $row->borrowing_authorized_by ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="pur-empty">No borrowing records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <table class="pur-table min-w-[900px]">
                    <thead>
                        <tr>
                            <th>Equipment</th>
                            <th>Category</th>
                            <th>Reason</th>
                            <th>Location</th>
                            <th>Inventory</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr class="transition hover:bg-gray-50/70">
                                <td>
                                    <p class="font-semibold text-gray-900">{{ $row->equipment_name ?: '—' }}</p>
                                    @if(!empty($row->equipment_asset_tag))
                                        <p class="mt-0.5 text-xs text-gray-400">{{ $row->equipment_asset_tag }}</p>
                                    @endif
                                </td>
                                <td class="text-sm text-gray-600">{{ $row->equipment_category_name ?: '—' }}</td>
                                <td class="text-sm text-gray-600">{{ $row->disposal_reason ?: '—' }}</td>
                                <td class="text-sm text-gray-600">{{ $row->disposal_area_location ?: '—' }}</td>
                                <td>
                                    @if(!empty($row->equipment_inventory_status))
                                        <span class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-600">
                                            {{ $row->equipment_inventory_status }}
                                        </span>
                                    @else
                                        <span class="text-sm text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="pur-empty">No disposal records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>

        @if(method_exists($rows, 'links'))
            <div class="border-t border-gray-100 px-5 py-4">{{ $rows->links() }}</div>
        @endif
    </div>
</div>
@endsection
