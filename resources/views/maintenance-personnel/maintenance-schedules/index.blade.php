@extends('layouts.maintenance-layout')

@section('title', 'Maintenance Schedule')

@section('content')
    <div class="mb-6">
        @include('maintenance-personnel.maintenance-schedules.partials.type-tabs')
    </div>

    {{-- CALENDAR PAGE CONTENT --}}
    @include('maintenance-personnel.maintenance-schedules._calendar')
@endsection
