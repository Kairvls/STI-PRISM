@extends('layouts.admin-layout')

@section('title', 'Maintenance History')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">
@endpush

@section('content')

@php
    $urgencyClasses = [
        'Urgent' => 'border-rose-200 bg-rose-50 text-rose-700',
        'Critical' => 'border-rose-200 bg-rose-50 text-rose-700',
        'High' => 'border-amber-200 bg-amber-50 text-amber-700',
        'Normal' => 'border-slate-200 bg-slate-50 text-slate-600',
        'Low' => 'border-slate-200 bg-slate-50 text-slate-600',
    ];

    $statusClasses = [
        'Pending' => 'border-amber-200 bg-amber-50 text-amber-700',
        'Processing' => 'border-blue-200 bg-blue-50 text-blue-700',
        'Resolved' => 'border-green-200 bg-green-50 text-green-700',
        'Rejected' => 'border-red-200 bg-red-50 text-red-700',
        'For Replacement' => 'border-amber-200 bg-amber-50 text-amber-800',
    ];

    $rowCount = method_exists($rows, 'total') ? $rows->total() : $rows->count();
@endphp

<div class="admin-page space-y-6">
    <h1 class="admin-page-title print-only" hidden>Maintenance report — {{ now()->format('M d, Y') }}</h1>

    @include('layouts.partials.admin-system-reports-nav', ['current' => 'maintenance'])

    {{-- KPI strip --}}
    @include('layouts.partials.maintenance-stat-cards', [
        'cards' => [
            [
                'label' => 'Filed',
                'value' => number_format($filed),
            ],
            [
                'label' => 'Resolved',
                'value' => number_format($resolved),
            ],
            [
                'label' => 'Rejected',
                'value' => number_format($rejected),
            ],
            [
                'label' => 'For replacement',
                'value' => number_format($replacement),
            ],
        ],
    ])

    {{-- Insights --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="pur-card px-5 py-5">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Average time to close</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-gray-950">
                @if($avgCloseHours === null)
                    —
                @else
                    {{ number_format(((float) $avgCloseHours) / 24, 1) }} days
                @endif
            </p>
            <p class="mt-1 text-xs text-gray-400">
                Resolved, rejected, and replacement tickets. Pending {{ $pending }} · Processing {{ $processing }}
            </p>
        </div>

        <div class="pur-card px-5 py-5">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Repeat equipment</p>
            <ul class="mt-3 divide-y divide-gray-100">
                @forelse($repeatEquipment as $item)
                    <li class="flex items-center justify-between gap-3 py-2 text-sm">
                        <span class="truncate text-gray-700">{{ $item->equipment_label }}</span>
                        <span class="shrink-0 font-semibold tabular-nums text-gray-950">{{ $item->report_count }}</span>
                    </li>
                @empty
                    <li class="py-2 text-sm text-gray-400">No repeat equipment in this period.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Records --}}
    <div class="pur-card">
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="mb-4 flex items-center gap-3">
                <h2 class="text-base font-semibold text-gray-950">Maintenance records</h2>
                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">{{ $rowCount }}</span>
            </div>
            @include('layouts.partials.admin-system-reports-filters', ['placeholder' => 'Search equipment, room, building, technician…'])
        </div>

        <div class="overflow-x-auto">
            <table class="pur-table w-full min-w-[1000px]">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Equipment</th>
                        <th>Location</th>
                        <th>Urgency</th>
                        <th>Status</th>
                        <th>Technician</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $urgency = (string) ($row->report_urgency_level ?: '');
                            $status = (string) ($row->report_current_status ?: '');
                            $urgencyClass = $urgencyClasses[$urgency] ?? 'border-gray-200 bg-gray-50 text-gray-600';
                            $statusClass = $statusClasses[$status] ?? 'border-gray-200 bg-gray-50 text-gray-600';
                        @endphp
                        <tr class="transition hover:bg-gray-50/70">
                            <td class="text-sm text-gray-500">{{ $row->report_id }}</td>
                            <td class="text-sm font-semibold text-gray-900">
                                {{ $row->equipment_name ?: ($row->report_unlisted_equipment_name ?: ($row->report_suggested_issue ?: '—')) }}
                            </td>
                            <td class="text-sm text-gray-600">
                                {{ trim(($row->building_name ? $row->building_name.' · ' : '').($row->room_name ?: '')) ?: '—' }}
                            </td>
                            <td>
                                @if($urgency !== '')
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium {{ $urgencyClass }}">{{ $urgency }}</span>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </td>
                            <td>
                                @if($status !== '')
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium {{ $statusClass }}">{{ $status }}</span>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="text-sm text-gray-600">{{ $row->technician_name ?: 'Unassigned' }}</td>
                            <td class="whitespace-nowrap text-sm text-gray-500">
                                {{ $row->report_submitted_at ? \Carbon\Carbon::parse($row->report_submitted_at)->format('M j, Y') : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="pur-empty">No records in this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('layouts.partials.table-showing-pager', ['pager' => $rows, 'noun' => 'tickets'])
    </div>
</div>

@endsection
