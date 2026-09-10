@extends("layouts.app")

@section('body-class', 'pp-layout')

@section('main-bg', 'bg-white')
@section('main-pad', 'px-4 pb-6 pt-4 sm:px-6 sm:pb-8 sm:pt-5 lg:px-8')

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

@push('scripts')
    @include('layouts.partials.purchaser-daily-reminder')
    @include('partials.purchaser-print-sheet-helper')
    @include('partials.purchaser-confirm-dialog')
@endpush
