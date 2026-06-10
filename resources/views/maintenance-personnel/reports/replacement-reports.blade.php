@extends('layouts.maintenance-layout')

@section('title', 'Replacement Reports')

@section('content')

<div class="mb-8">

    <h1 class="text-3xl font-extrabold text-orange-400">

        Replacement Reports

    </h1>

    <p class="text-gray-400 mt-2">

        Equipment reports requiring replacement and procurement action.

    </p>

</div>

@include(
    'components.tables.reports-table',
    [
        'reports' => $reports
    ]
)

@endsection