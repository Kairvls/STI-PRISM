@extends("layouts.app")


@section("sidebar")

    <link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">

    {{-- ===================================================== --}}
    {{-- PURCHASER SIDEBAR HERE --}}
    {{-- FIXED WIDTH: w-64 --}}
    {{-- ===================================================== --}}

    @include("layouts.purchaser-sidebar")

@endsection


@section("topbar")

    {{-- ===================================================== --}}
    {{-- PURCHASER TOPBAR HERE --}}
    {{-- ===================================================== --}}

    @include("layouts.purchaser-topbar")

@endsection