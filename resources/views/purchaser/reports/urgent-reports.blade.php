@extends('layouts.purchaser-layout')

@section('page-title', 'Urgent Reports')
@section('page-subtitle', 'Reports that need immediate purchasing attention.')

@section('content')
@php
    $isArchiveView = request('archive') == 1 || request('view') === 'archive';
    $urgentBaseQuery = $isArchiveView ? ['archive' => 1] : [];
    $activeStatus = request('status');
    $scopeHint = $isArchiveView
        ? 'All archived urgent reports (any date)'
        : 'All active urgent reports (any date)';
@endphp
<div>
    <div class="mb-6">
        @include('layouts.partials.maintenance-stat-cards', [
            'cards' => [
                [
                    'label' => 'Pending',
                    'hint' => 'Any date · awaiting action',
                    'value' => number_format($urgentSummary['pending'] ?? 0),
                    'href' => route('purchaser.reports.urgent', array_merge($urgentBaseQuery, ['status' => 'Pending'])),
                    'active' => $activeStatus === 'Pending',
                ],
                [
                    'label' => 'Processing',
                    'hint' => 'Any date · currently handled',
                    'value' => number_format($urgentSummary['processing'] ?? 0),
                    'href' => route('purchaser.reports.urgent', array_merge($urgentBaseQuery, ['status' => 'Processing'])),
                    'active' => $activeStatus === 'Processing',
                ],
                [
                    'label' => 'For Replacement',
                    'hint' => 'Any date · ready for procurement',
                    'value' => number_format($urgentSummary['for_replacement'] ?? 0),
                    'href' => route('purchaser.reports.urgent', array_merge($urgentBaseQuery, ['status' => 'For Replacement'])),
                    'active' => $activeStatus === 'For Replacement',
                ],
            ],
        ])
    </div>

    @include('components.tables.reports-table', [
        'reports' => $reports,
        'context' => 'purchaser-urgent',
    ])
</div>
@endsection
