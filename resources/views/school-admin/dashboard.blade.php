@extends('layouts.admin-layout')

@section('title', 'School Administrator Dashboard')

@section('content')
<link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">

@php
    $actionMetrics = [
        [
            'label' => 'RIS to accept',
            'value' => $stats['pending_ris'],
            'meta' => '₱'.number_format((float) $stats['pending_ris_amount'], 2).' awaiting review',
            'url' => route('school-admin.procurement-review', ['focus' => \App\Support\AdminAttentionSummary::FOCUS_PENDING_REVIEW]),
            'alert' => $stats['pending_ris'] > 0,
        ],
        [
            'label' => 'RIS to sign',
            'value' => $stats['awaiting_cosign'],
            'meta' => 'Decision or Issued by signature',
            'url' => route('school-admin.digital-signatures.sign-ris', ['focus' => \App\Support\AdminAttentionSummary::FOCUS_AWAITING_COSIGN]),
            'alert' => $stats['awaiting_cosign'] > 0,
        ],
        [
            'label' => 'Returned / amendments',
            'value' => $stats['amend_ris'],
            'meta' => 'Minor revision or rejected',
            'url' => route('school-admin.procurement-review', ['focus' => \App\Support\AdminAttentionSummary::FOCUS_AMENDMENTS]),
        ],
        [
            'label' => 'Signatures · 30d',
            'value' => $stats['signed_30d'],
            'meta' => 'Your RIS actions',
            'url' => route('school-admin.digital-signatures.history'),
        ],
    ];

    $monitorMetrics = [
        [
            'label' => 'Equipment',
            'value' => $stats['equipment_total'],
            'meta' => $stats['needs_maintenance'].' maintenance · '.$stats['for_replacement'].' replacement',
            'url' => route('school-admin.operations.equipment'),
        ],
        [
            'label' => 'Open RIS',
            'value' => $stats['open_ris'],
            'meta' => 'In the procurement pipeline',
            'url' => route('school-admin.operations.procurement', ['filter' => 'open']),
        ],
        [
            'label' => 'Overdue schedules',
            'value' => $stats['overdue_schedules'],
            'meta' => 'Needs attention',
            'url' => route('school-admin.operations.schedules', ['filter' => 'overdue']),
            'alert' => $stats['overdue_schedules'] > 0,
        ],
        [
            'label' => 'Active inspections',
            'value' => $stats['active_inspections'],
            'meta' => $stats['overdue_inspections'].' past due',
            'url' => route('school-admin.semester-inspections.index'),
            'alert' => $stats['overdue_inspections'] > 0,
        ],
    ];

    $risPanels = [
        [
            'title' => 'Waiting for your acceptance',
            'url' => route('school-admin.procurement-review', ['focus' => \App\Support\AdminAttentionSummary::FOCUS_PENDING_REVIEW]),
            'total' => $stats['pending_ris'],
            'empty' => 'No RIS waiting for acceptance.',
            'rows' => $pendingRisList,
        ],
        [
            'title' => 'Waiting for your signature',
            'url' => route('school-admin.digital-signatures.sign-ris', ['focus' => \App\Support\AdminAttentionSummary::FOCUS_AWAITING_COSIGN]),
            'total' => $stats['awaiting_cosign'],
            'empty' => 'No RIS waiting for your signature.',
            'rows' => $awaitingSignList,
        ],
    ];
@endphp

<style>
    .sa-alert { color: #b45309; }
</style>

<div class="admin-page space-y-6">
    <div>
        <h2 class="text-base font-semibold text-gray-950">Your approvals</h2>
        <p class="mt-1 text-xs text-gray-400">RIS submitted by the Purchaser that need the School Administrator.</p>
    </div>

    <div class="pur-card">
        <div class="grid grid-cols-2 divide-gray-100 lg:grid-cols-4 lg:divide-x">
            @foreach($actionMetrics as $metric)
                <a href="{{ $metric['url'] }}" class="block px-5 py-5 transition hover:bg-gray-50/70">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ $metric['label'] }}</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight {{ !empty($metric['alert']) ? 'sa-alert' : 'text-gray-950' }}">
                        {{ $metric['value'] }}
                    </p>
                    <p class="mt-1.5 text-xs text-gray-500">{{ $metric['meta'] }}</p>
                </a>
            @endforeach
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @foreach($risPanels as $panel)
            <div class="pur-card">
                <div class="flex items-baseline justify-between gap-3 border-b border-gray-100 px-5 py-4">
                    <h3 class="text-sm font-semibold text-gray-950">{{ $panel['title'] }}</h3>
                    <a href="{{ $panel['url'] }}" class="text-xs font-semibold text-gray-400 transition hover:text-[#0025cc]">All {{ $panel['total'] }}</a>
                </div>
                <ul class="divide-y divide-gray-100">
                    @forelse($panel['rows'] as $row)
                        @php
                            $submitted = ! empty($row->ris_requested_by_date)
                                ? \Carbon\Carbon::parse($row->ris_requested_by_date)->startOfDay()
                                : null;
                            $age = $submitted ? (int) $submitted->diffInDays(now()->startOfDay()) : null;
                        @endphp
                        <li>
                            <a href="{{ $panel['url'] }}" class="flex items-start justify-between gap-3 px-5 py-3.5 transition hover:bg-gray-50/70">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900">{{ \App\Support\RisWorkflow::formNumber($row) }}</p>
                                    <p class="mt-0.5 truncate text-xs text-gray-400">
                                        {{ \Illuminate\Support\Str::limit($row->ris_purpose_description ?: 'No purpose noted', 60) }}
                                        · ₱{{ number_format((float) ($row->ris_calculated_total ?? 0), 2) }}
                                    </p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-[11px] font-semibold text-gray-700">{{ \App\Support\RisWorkflow::statusLabel($row) }}</p>
                                    @if($age !== null)
                                        <p class="mt-0.5 text-[11px] {{ $age >= 3 ? 'sa-alert font-semibold' : 'text-gray-400' }}">
                                            {{ $age === 0 ? 'Today' : $age.'d waiting' }}
                                        </p>
                                    @endif
                                </div>
                            </a>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-sm text-gray-400">{{ $panel['empty'] }}</li>
                    @endforelse
                </ul>
            </div>
        @endforeach
    </div>

    <div>
        <h2 class="text-base font-semibold text-gray-950">Campus monitoring</h2>
        <p class="mt-1 text-xs text-gray-400">Equipment, procurement, schedules, and inspections across the campus.</p>
    </div>

    <div class="pur-card">
        <div class="grid grid-cols-2 divide-gray-100 lg:grid-cols-4 lg:divide-x">
            @foreach($monitorMetrics as $metric)
                <a href="{{ $metric['url'] }}" class="block px-5 py-5 transition hover:bg-gray-50/70">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ $metric['label'] }}</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight {{ !empty($metric['alert']) ? 'sa-alert' : 'text-gray-950' }}">
                        {{ $metric['value'] }}
                    </p>
                    <p class="mt-1.5 text-xs text-gray-500">{{ $metric['meta'] }}</p>
                </a>
            @endforeach
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @php
            $overdueBorrowsUrl = route('school-admin.operations.movements', ['tab' => 'borrowing', 'filter' => 'Overdue']);
        @endphp
        <div class="pur-card">
            <div class="flex items-baseline justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <h3 class="text-sm font-semibold text-gray-950">Overdue equipment borrows</h3>
                <a href="{{ $overdueBorrowsUrl }}" class="text-xs font-semibold text-gray-400 transition hover:text-[#0025cc]">All {{ $stats['overdue_borrows'] }}</a>
            </div>
            <ul class="divide-y divide-gray-100">
                @forelse($overdueBorrows as $row)
                    @php
                        $expected = \Carbon\Carbon::parse($row->borrowing_expected_return_date)->startOfDay();
                        $days = (int) $expected->diffInDays(now()->startOfDay());
                    @endphp
                    <li>
                        <a href="{{ $overdueBorrowsUrl }}" class="flex items-start justify-between gap-3 px-5 py-3.5 transition hover:bg-gray-50/70">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900">{{ $row->equipment_name ?: 'Equipment' }}</p>
                                <p class="mt-0.5 text-xs text-gray-400">{{ ($row->borrowing_borrower_name ?: 'Unknown borrower').' · Due '.$expected->format('M j') }}</p>
                            </div>
                            <span class="shrink-0 text-[11px] font-semibold sa-alert">{{ $days }}d overdue</span>
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-8 text-sm text-gray-400">No overdue borrows.</li>
                @endforelse
            </ul>
        </div>

        <div class="pur-card">
            <div class="flex items-baseline justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <h3 class="text-sm font-semibold text-gray-950">Overdue maintenance schedules</h3>
                <a href="{{ route('school-admin.operations.schedules', ['filter' => 'overdue']) }}" class="text-xs font-semibold text-gray-400 transition hover:text-[#0025cc]">All {{ $stats['overdue_schedules'] }}</a>
            </div>
            <ul class="divide-y divide-gray-100">
                @forelse($overdueSchedules as $row)
                    @php
                        $due = \Carbon\Carbon::parse($row->maintenance_schedule_next_date)->startOfDay();
                        $days = (int) $due->diffInDays(now()->startOfDay());
                    @endphp
                    <li>
                        <a href="{{ route('school-admin.operations.schedules', ['filter' => 'overdue']) }}" class="flex items-start justify-between gap-3 px-5 py-3.5 transition hover:bg-gray-50/70">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900">{{ $row->equipment_name ?: ($row->maintenance_schedule_title ?: 'Schedule') }}</p>
                                <p class="mt-0.5 text-xs text-gray-400">{{ ($row->room_name ?: 'No room').' · Due '.$due->format('M j') }}</p>
                            </div>
                            <span class="shrink-0 text-[11px] font-semibold sa-alert">{{ $days }}d overdue</span>
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-8 text-sm text-gray-400">No overdue schedules.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
