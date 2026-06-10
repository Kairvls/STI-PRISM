@extends('layouts.maintenance-layout')

@section('title', 'Pending Reports')

@section('content')

<!-- PAGE HEADER -->
<div class="mb-8">

    <h1 class="text-3xl font-extrabold text-yellow-400">

        Pending Reports

    </h1>

    <p class="text-gray-400 mt-2">

        Reports waiting for maintenance action and assignment.

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

