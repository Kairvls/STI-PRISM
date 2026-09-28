@extends('layouts.admin-layout')

@section('title', '3D Building Layout')

@section('content')
<span class="admin-keep-colors hidden" aria-hidden="true"></span>

@include('partials.building-3d.styles')

<style>
    .admin-building-layout .dashboard-building-view:not(.is-building-fullscreen),
    .admin-building-layout .dashboard-building-view:not(.is-building-fullscreen) #building3DViewport {
        height: max(480px, calc(100vh - 250px));
        height: max(480px, calc(100dvh - 250px));
    }
</style>

<div class="admin-page admin-building-layout">
    @include('partials.building-3d.section')
</div>

@include('partials.building-3d.scripts', [
    'building3DRoomViewUrl' => url('/maintenance/property-assignments/rooms/__ROOM__'),
])
@endsection
