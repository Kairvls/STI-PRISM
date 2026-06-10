@extends('layouts.maintenance-layout')

@section('title', 'Urgent Reports')

@section('content')

<!-- PAGE HEADER -->
<div class="mb-8">

    <h1 class="text-3xl font-extrabold text-red-400">

        Urgent Reports

    </h1>

    <p class="text-gray-400 mt-2">

        View all urgent and high-priority maintenance reports.

    </p>

</div>

<!-- REUSABLE REPORT TABLE -->
@include(
    'components.tables.reports-table',
    [
        'reports' => $reports
    ]
)

@endsection

