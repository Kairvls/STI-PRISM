@extends('layouts.maintenance-layout')

@section('title', 'Rejected Reports')

@section('content')

<div class="mb-8">

    <h1 class="text-3xl font-extrabold text-red-500">

        Rejected Reports

    </h1>

    <p class="text-gray-400 mt-2">

        Reports rejected due to invalid findings or duplicate submissions.

    </p>

</div>

@include(
    'components.tables.reports-table',
    [
        'reports' => $reports
    ]
)

@endsection