@extends('layouts.maintenance-layout')

@section('title', 'Processing Reports')

@section('content')

<div class="mb-8">

    <h1 class="text-3xl font-extrabold text-blue-400">

        Processing Reports

    </h1>

    <p class="text-gray-400 mt-2">

        Reports currently being processed by maintenance personnel.

    </p>

</div>

@include(
    'components.tables.reports-table',
    [
        'reports' => $reports
    ]
)

@endsection