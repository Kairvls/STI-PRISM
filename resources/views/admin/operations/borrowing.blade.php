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
        'Overdue' => 'border-rose-200 bg-rose-50 text-rose-700',
        'Borrowed' => 'border-amber-200 bg-amber-50 text-amber-800',
        'Returned' => 'border-green-200 bg-green-50 text-green-700',
    ];

    $formatDate = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('M j, Y') : null;
@endphp

<div class="admin-page space-y-6">
    @include('layouts.partials.maintenance-stat-cards', [
        'cards' => [
            [
                'label' => 'Currently borrowed',
                'hint' => 'Not yet returned',
                'value' => number_format($counts['active']),
                'href' => \App\Support\AdminPortal::route('operations.borrowing', ['filter' => 'active']),
                'active' => $filter === 'active',
            ],
            [
                'label' => 'Overdue',
                'hint' => 'Past the return date',
                'value' => number_format($counts['overdue']),
                'href' => \App\Support\AdminPortal::route('operations.borrowing', ['filter' => 'Overdue']),
                'active' => $filter === 'Overdue',
            ],
            [
                'label' => 'Due this week',
                'hint' => 'Return date within 7 days',
                'value' => number_format($counts['due_week']),
                'href' => \App\Support\AdminPortal::route('operations.borrowing', ['filter' => 'due_week']),
                'active' => $filter === 'due_week',
            ],
            [
                'label' => 'Returned',
                'hint' => 'Last 30 days',
                'value' => number_format($counts['returned_30d']),
                'href' => \App\Support\AdminPortal::route('operations.borrowing', ['filter' => 'Returned']),
                'active' => $filter === 'Returned',
            ],
        ],
    ])

    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">Borrowing records</h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">{{ $rowCount }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">View only. Maintenance records borrows and returns. Click a row to see the full details.</p>
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
                        class="pur-filter-chip {{ $filter === $key ? 'is-active' : '' }}"
                    >{{ $label }}</a>
                @endforeach
            </div>
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
                            $badgeClass = $statusClasses[$status] ?? 'border-gray-200 bg-gray-50 text-gray-600';
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
                                'equipment' => $row->equipment_name ?: 'Equipment',
                                'assetTag' => $row->equipment_asset_tag,
                                'status' => $status,
                                'statusClass' => $badgeClass,
                                'borrower' => $row->borrowing_borrower_name,
                                'department' => $row->borrowing_borrower_department,
                                'quantity' => $row->borrowing_quantity,
                                'condition' => $row->borrowing_equipment_condition,
                                'borrowedOn' => $formatDate($row->borrowing_date),
                                'dueBack' => $formatDate($row->borrowing_expected_return_date),
                                'returnedOn' => $formatDate($row->borrowing_actual_return_date),
                                'destination' => $row->borrowing_destination_location,
                                'authorizedBy' => $row->borrowing_authorized_by,
                                'purpose' => $row->borrowing_purpose,
                                'remarks' => $row->borrowing_remarks,
                            ];
                        @endphp
                        <tr
                            class="cursor-pointer transition hover:bg-gray-50/70"
                            tabindex="0"
                            data-borrow-detail='@json($detail)'
                            onclick="window.openBorrowDetail(this)"
                            onkeydown="if (event.key === 'Enter') window.openBorrowDetail(this)"
                        >
                            <td>
                                <p class="font-semibold text-gray-900">{{ $row->equipment_name ?: '—' }}</p>
                                <p class="mt-0.5 text-xs text-gray-400">{{ $row->equipment_asset_tag ?: 'No asset tag' }}</p>
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
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium {{ $badgeClass }}">
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

<div id="borrowDetailModal" class="fixed inset-0 z-[11000] hidden items-center justify-center bg-gray-950/40 p-4" role="dialog" aria-modal="true" aria-labelledby="borrowDetailTitle">
    <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-xl">
        <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4">
            <div class="min-w-0">
                <h3 id="borrowDetailTitle" class="truncate text-base font-semibold text-gray-950" data-field="equipment"></h3>
                <p class="mt-0.5 text-xs text-gray-400" data-field="assetTag"></p>
            </div>
            <span class="inline-flex shrink-0 rounded-full border px-2.5 py-1 text-xs font-medium" data-field="status"></span>
        </div>

        <dl class="grid grid-cols-2 gap-x-6 gap-y-4 px-5 py-5 text-sm">
            <div>
                <dt class="text-xs text-gray-400">Borrower</dt>
                <dd class="mt-0.5 font-medium text-gray-900" data-field="borrower"></dd>
                <dd class="text-xs text-gray-500" data-field="department"></dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400">Authorized by</dt>
                <dd class="mt-0.5 text-gray-700" data-field="authorizedBy"></dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400">Borrowed on</dt>
                <dd class="mt-0.5 text-gray-700" data-field="borrowedOn"></dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400">Due back</dt>
                <dd class="mt-0.5 text-gray-700" data-field="dueBack"></dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400">Returned on</dt>
                <dd class="mt-0.5 text-gray-700" data-field="returnedOn"></dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400">Taken to</dt>
                <dd class="mt-0.5 text-gray-700" data-field="destination"></dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400">Quantity</dt>
                <dd class="mt-0.5 text-gray-700" data-field="quantity"></dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400">Condition when borrowed</dt>
                <dd class="mt-0.5 text-gray-700" data-field="condition"></dd>
            </div>
            <div class="col-span-2">
                <dt class="text-xs text-gray-400">Purpose</dt>
                <dd class="mt-0.5 whitespace-pre-line text-gray-700" data-field="purpose"></dd>
            </div>
            <div class="col-span-2">
                <dt class="text-xs text-gray-400">Remarks</dt>
                <dd class="mt-0.5 whitespace-pre-line text-gray-700" data-field="remarks"></dd>
            </div>
        </dl>

        <div class="flex justify-end border-t border-gray-100 px-5 py-3">
            <button type="button" class="px-3 py-2 text-sm font-medium text-gray-500 transition hover:text-gray-950" onclick="window.closeBorrowDetail()">
                Close
            </button>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('borrowDetailModal');
        if (!modal) return;

        const badgeBase = 'inline-flex shrink-0 rounded-full border px-2.5 py-1 text-xs font-medium';
        let lastTrigger = null;

        window.openBorrowDetail = function (row) {
            let data = {};
            try {
                data = JSON.parse(row.getAttribute('data-borrow-detail') || '{}');
            } catch (e) {
                return;
            }

            modal.querySelectorAll('[data-field]').forEach(function (el) {
                const key = el.getAttribute('data-field');
                const value = data[key];
                const emptyText = { assetTag: 'No asset tag', department: '' };
                el.textContent = (value === null || value === undefined || value === '')
                    ? (key in emptyText ? emptyText[key] : '—')
                    : (key === 'assetTag' ? 'Asset tag: ' + value : String(value));
            });

            const badge = modal.querySelector('[data-field="status"]');
            badge.className = badgeBase + ' ' + (data.statusClass || 'border-gray-200 bg-gray-50 text-gray-600');

            lastTrigger = row;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.querySelector('button').focus();
        };

        window.closeBorrowDetail = function () {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            if (lastTrigger) lastTrigger.focus();
        };

        modal.addEventListener('click', function (event) {
            if (event.target === modal) window.closeBorrowDetail();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) window.closeBorrowDetail();
        });
    })();
</script>
@endsection
