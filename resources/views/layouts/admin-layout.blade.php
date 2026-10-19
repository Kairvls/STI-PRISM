@extends('layouts.app')

@section('body-class', 'pp-layout')

@section('main-bg', 'bg-white')
@section('main-pad', 'px-4 pb-6 pt-4 sm:px-6 sm:pb-8 sm:pt-5 lg:px-8')

@section('sidebar')
    @if (\App\Support\AdminPortal::isSchoolAdmin())
        @include('layouts.school-admin-sidebar')
    @else
        @include('layouts.admin-sidebar')
    @endif
@endsection

@section('topbar')
    @include('layouts.admin-topbar')
@endsection

@push('styles')
    @if (request()->is(
        'school-admin/authority-to-purchase*',
        'school-admin/purchase-orders*',
        'school-admin/request-check*',
        'school-admin/ris/*'
    ))
        <link rel="stylesheet" href="{{ asset('css/president-modern.css') }}">
        <link rel="stylesheet" href="{{ asset('css/accounting-modern.css') }}">
        @include('accounting.partials.ui')
    @endif
@endpush

@push('scripts')
    @include('layouts.partials.prism-toast')
    @include('layouts.partials.ris-form-number-js')
    @include('layouts.partials.admin-daily-reminder')
    @include('layouts.partials.module-importance-notice')
    @if (request()->is(
        'school-admin/authority-to-purchase*',
        'school-admin/purchase-orders*',
        'school-admin/request-check*',
        'school-admin/ris/*'
    ))
        @include('layouts.partials.page-carousel-script')
        @include('accounting.partials.ui-scripts')
        @include('accounting.partials.print-form')
    @endif
@endpush
