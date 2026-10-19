@extends("layouts.app")

@section('body-class', 'pp-layout')

@section('main-bg', 'bg-white')
@section('main-pad', 'px-4 pb-6 pt-4 sm:px-6 sm:pb-8 sm:pt-5 lg:px-8')

@section("sidebar")
    @include('layouts.partials.admin-design')
    @include('layouts.partials.admin-grayscale-theme', ['keepColors' => true])
    <link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">
    <style>
        main,
        main * {
            font-family: "Inter", sans-serif !important;
        }
        main h1,
        main .pur-page-title,
        main .dashboard-title {
            font-family: "Outfit", sans-serif !important;
        }
    </style>
    @include("layouts.supply-officer-sidebar")
@endsection

@section("topbar")
    @include("layouts.supply-officer-topbar")
@endsection

@push('scripts')
    @include('layouts.partials.prism-toast')
    @include('layouts.partials.module-importance-notice')
@endpush
