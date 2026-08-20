@extends('layouts.app')

@section('body-class', 'pp-layout')

@section('main-bg', 'bg-white')
@section('main-pad', 'px-4 pb-6 pt-4 sm:px-6 sm:pb-8 sm:pt-5 lg:px-8')

@section('sidebar')
    @include('layouts.president-sidebar')
@endsection

@section('topbar')
    @include('layouts.president-topbar')
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/president-modern.css') }}">
@endpush

@push('scripts')
    @include('layouts.partials.prism-toast')
@endpush
