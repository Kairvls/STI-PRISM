@extends('layouts.admin-layout')

@section('title', 'Borrowed Equipment')

@section('content')
<link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">

@php
    $rowCount = method_exists($rows, 'total') ? $rows->total() : $rows->count();
    $today = now()->startOfDay();

    $filterLabels = [
        'active' => 'Currently borrowed',
        'Overdue' => 'Overdue',
        'due_week' => 'Due this week',
        'Returned' => 'Returned',
        'all' => 'All',
    ];

    $statusClasses = [
        'Overdue' => 'borrow-status-overdue',
        'Borrowed' => 'borrow-status-borrowed',
        'Returned' => 'borrow-status-returned',
    ];

    $formatDate = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('M j, Y') : null;
@endphp

{{-- Plain CSS colors so the admin grayscale theme (which targets Tailwind color utilities) leaves status badges colored. --}}
<style>
    .borrow-status { background: #f8fafc; color: #475569; }
    .borrow-status-overdue { background: #fff1f2; color: #be123c; }
    .borrow-status-borrowed { background: #fffbeb; color: #b45309; }
    .borrow-status-returned { background: #ecfdf5; color: #047857; }
</style>

<div class="admin-page space-y-6">

    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">Borrowing records</h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">{{ $rowCount }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">View only. The Administrator records borrows and returns. Newest on top.</p>
                </div>

                <form method="GET" class="flex w-full flex-col gap-2 sm:flex-row sm:items-center xl:w-auto">
                    <input type="hidden" name="filter" value="{{ $filter }}">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                        </svg>
                        <input
                            type="text"
                            name="q"
                            value="{{ $q }}"
                            placeholder="Search equipment or borrower…"
                            class="h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-64"
                        >
                    </div>
                    <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-[#0025cc] px-4 text-[13px] font-medium text-white transition hover:bg-[#001fa8]">
                        Search
                    </button>
                    @if($q !== '')
                        <a
                            href="{{ \App\Support\AdminPortal::route('operations.borrowing', ['filter' => $filter]) }}"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-100 px-3.5 text-[13px] font-medium text-gray-600 transition hover:bg-gray-50"
                        >Clear</a>
                    @endif
                </form>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                @foreach($filterLabels as $key => $label)
                    <a
                        href="{{ \App\Support\AdminPortal::route('operations.borrowing', ['filter' => $key, 'q' => $q !== '' ? $q : null]) }}"
                        class="pur-filter-chip {{ $filter === $key && ! $recordId ? 'is-active' : '' }}"
                    >{{ $label }}</a>
                @endforeach
            </div>

            @if($recordId)
                <div class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-blue-100 bg-blue-50/60 px-3.5 py-2.5 text-xs text-gray-600">
                    <span>Showing only the borrow you selected.</span>
                    <a
                        href="{{ \App\Support\AdminPortal::route('operations.borrowing', ['filter' => $filter]) }}"
                        class="font-semibold text-[#0025cc] hover:underline"
                    >Show all {{ strtolower($filterLabels[$filter] ?? 'borrows') }}</a>
                </div>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="pur-table min-w-[1080px]">
                <thead>
                    <tr>
                        <th>Equipment</th>
                        <th>Borrower</th>
                        <th>Borrowed on</th>
                        <th>Due back</th>
                        <th>Authorized by</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $status = $row->borrowing_status ?: 'Borrowed';
                            $badgeClass = $statusClasses[$status] ?? '';
                            $due = $row->borrowing_expected_return_date
                                ? \Carbon\Carbon::parse($row->borrowing_expected_return_date)->startOfDay()
                                : null;

                            $dueNote = null;
                            $dueNoteClass = 'text-gray-400';
                            if ($status === 'Returned') {
                                $returnedOn = $formatDate($row->borrowing_actual_return_date);
                                $dueNote = $returnedOn ? 'Returned '.$returnedOn : 'Returned';
                            } elseif ($due && $due->lt($today)) {
                                $late = (int) $due->diffInDays($today);
                                $dueNote = $late.' '.\Illuminate\Support\Str::plural('day', $late).' late';
                                $dueNoteClass = 'font-semibold text-rose-700';
                            } elseif ($due) {
                                $left = (int) $today->diffInDays($due);
                                $dueNote = $left === 0 ? 'Due today' : 'Due in '.$left.' '.\Illuminate\Support\Str::plural('day', $left);
                                $dueNoteClass = $left <= 2 ? 'font-semibold text-amber-700' : 'text-gray-400';
                            }

                            $detail = [
                                'id' => $row->borrowing_record_id,
                                'equipmentId' => $row->borrowing_equipment_id,
                                'equipment' => $row->equipment_name,
                                'assetTag' => $row->equipment_asset_tag,
                                'borrower' => $row->borrowing_borrower_name,
                                'department' => $row->borrowing_borrower_department,
                                'quantity' => $row->borrowing_quantity,
                                'borrowDate' => $row->borrowing_date,
                                'expectedReturn' => $row->borrowing_expected_return_date,
                                'actualReturn' => $row->borrowing_actual_return_date,
                                'purpose' => $row->borrowing_purpose,
                                'destination' => $row->borrowing_destination_location,
                                'authorized' => $row->borrowing_authorized_by,
                                'remarks' => $row->borrowing_remarks,
                                'status' => $status,
                                'conditionOut' => $row->borrowing_equipment_condition,
                                'fromRoom' => $row->borrowed_from_room_name ?? null,
                                'returnedTo' => $row->returned_to_room_name ?? null,
                                'returnCondition' => $row->borrowing_return_condition ?? null,
                                'returnRemarks' => $row->borrowing_return_remarks ?? null,
                                'returnedBy' => $row->returned_by_name ?? null,
                            ];
                        @endphp
                        <tr
                            class="cursor-pointer transition hover:bg-gray-50/70"
                            tabindex="0"
                            data-borrow-detail='@json($detail)'
                            onclick="viewBorrowing(JSON.parse(this.dataset.borrowDetail))"
                            onkeydown="if (event.key === 'Enter') viewBorrowing(JSON.parse(this.dataset.borrowDetail))"
                        >
                            <td>
                                <p class="font-semibold text-gray-900">{{ $row->equipment_name ?: '—' }}</p>
                                <p class="mt-0.5 text-xs text-gray-400">
                                    {{ $row->equipment_asset_tag ?: 'No asset tag' }}
                                    @if(!empty($row->borrowed_from_room_name))
                                        · from {{ $row->borrowed_from_room_name }}
                                    @endif
                                </p>
                            </td>
                            <td>
                                <p class="text-sm text-gray-700">{{ $row->borrowing_borrower_name ?: '—' }}</p>
                                @if(!empty($row->borrowing_borrower_department))
                                    <p class="mt-0.5 text-xs text-gray-400">{{ $row->borrowing_borrower_department }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-sm text-gray-600">{{ $formatDate($row->borrowing_date) ?? '—' }}</td>
                            <td class="whitespace-nowrap">
                                <p class="text-sm text-gray-700">{{ $due ? $due->format('M j, Y') : '—' }}</p>
                                @if($dueNote)
                                    <p class="mt-0.5 text-xs {{ $dueNoteClass }}">{{ $dueNote }}</p>
                                @endif
                            </td>
                            <td class="text-sm text-gray-600">{{ $row->borrowing_authorized_by ?: '—' }}</td>
                            <td>
                                <span class="borrow-status {{ $badgeClass }} inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-medium">
                                    {{ $status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="pur-empty">
                                {{ $q !== '' ? 'No borrowing records match your search.' : 'No borrowing records in this list.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($rows, 'links'))
            <div class="border-t border-gray-100 px-5 py-4">{{ $rows->links() }}</div>
        @endif
    </div>
</div>

@include('admin.borrowing.partials.details-drawer', [
    'borrowReadOnly' => true,
    'borrowProfileUrl' => \App\Support\AdminPortal::route('operations.equipment.show', '__ID__'),
])
@endsection
