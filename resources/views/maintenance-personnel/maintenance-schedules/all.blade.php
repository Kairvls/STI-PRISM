@extends('layouts.maintenance-layout')

@section('title', 'Maintenance Schedule')

@section('content')
    <div id="scheduleTypeTabs" class="mb-6">
        @include('maintenance-personnel.maintenance-schedules.partials.type-tabs')
    </div>

    @include('maintenance-personnel.maintenance-schedules._calendar', ['calendarOnly' => true])
@endsection
