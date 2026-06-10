@extends('layouts.maintenance-layout')

@section('title', 'Incoming Reports')

@section('content')

<!-- PAGE HEADER -->
<div class="mb-8">

    <h1 class="text-3xl font-extrabold">

        Incoming Reports

    </h1>

    <p class="text-gray-400 mt-2">

        Monitor all submitted maintenance reports.

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

