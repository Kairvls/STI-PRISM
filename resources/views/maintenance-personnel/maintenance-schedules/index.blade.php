@extends('layouts.maintenance-layout')

@section('title', 'Maintenance Schedules')

@section('content')
    {{-- CALENDAR PAGE CONTENT --}}
    @include('maintenance-personnel.maintenance-schedules._calendar')
@endsection