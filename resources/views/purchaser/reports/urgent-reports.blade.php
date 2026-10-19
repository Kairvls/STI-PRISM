@extends('layouts.purchaser-layout')

@section('page-title', 'Critical & High Reports')
@section('page-subtitle', 'Critical and High reports that need purchasing attention.')

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

    @include('partials.attention-focus-chip', ['focus' => $attentionFocus ?? null, 'total' => method_exists($reports, 'total') ? $reports->total() : null])

    @include('components.tables.reports-table', [
        'reports' => $reports,
        'context' => 'purchaser-urgent',
    ])
</div>
@endsection
