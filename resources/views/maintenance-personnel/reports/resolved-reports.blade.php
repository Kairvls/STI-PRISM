@extends('layouts.maintenance-layout')

@section('title', 'Resolved Reports')

@section('content')

<div class="mb-8">

    <h1 class="text-3xl font-extrabold text-green-400">

        Resolved Reports

    </h1>

    <p class="text-gray-400 mt-2">

        Successfully resolved and completed maintenance reports.

    </p>

</div>

@include(
    'components.tables.reports-table',
    [
        'reports' => $reports
    ]
)

@endsection