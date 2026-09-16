@extends("layouts.app")

@section('body-class', 'pp-layout')

@section('main-bg', 'bg-white')
@section('main-pad', 'px-4 pb-6 pt-4 sm:px-6 sm:pb-8 sm:pt-5 lg:px-8')

@section("sidebar")

    {{-- Shared design tokens (Inter/Outfit) + Receiving-matched minimalist theme --}}
    @include('layouts.partials.admin-design')
    @include('layouts.partials.admin-grayscale-theme')
    <link rel="stylesheet" href="{{ asset('css/purchaser-modern.css') }}">

    <style>
        main,
        main * {
            font-family: "Inter", sans-serif !important;
        }

        main h1,
        main .admin-page-title,
        main .dashboard-title,
        main .stat-value,
        main .admin-stat-card-value,
        main .pur-page-title,
        main [class*="Outfit"] {
            font-family: "Outfit", sans-serif !important;
        }
    </style>

    @include("layouts.purchaser-sidebar")

@endsection


@section("topbar")

    {{-- ===================================================== --}}
    {{-- PURCHASER TOPBAR HERE --}}
    {{-- ===================================================== --}}

    @include("layouts.purchaser-topbar")

@endsection

@push('scripts')
    @include('layouts.partials.page-carousel-script')
    @include('layouts.partials.prism-toast')
    @include('layouts.partials.purchaser-daily-reminder')
    @include('partials.purchaser-print-sheet-helper')
    @include('partials.purchaser-confirm-dialog')
@endpush
