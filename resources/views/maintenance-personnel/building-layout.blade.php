@extends('layouts.maintenance-layout')

@section('title', 'Campus Monitoring')

@section('content')
@include('partials.building-3d.styles')

<style>
    .maintenance-building-layout .dashboard-building-view:not(.is-building-fullscreen),
    .maintenance-building-layout .dashboard-building-view:not(.is-building-fullscreen) #building3DViewport {
        height: max(480px, calc(100vh - 250px));
        height: max(480px, calc(100dvh - 250px));
    }
</style>

<div class="maintenance-building-layout">
    @include('partials.building-3d.section')
</div>

@include('partials.building-3d.scripts', [
    'building3DRoomViewUrl' => url('/maintenance/property-assignments/rooms/__ROOM__'),
])
@endsection
