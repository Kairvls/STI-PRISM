@extends('layouts.app')

@section('body-class', 'pp-layout')

@section('sidebar')
    @include('layouts.admin-sidebar')
@endsection

@section('topbar')
    @include('layouts.admin-topbar')
@endsection

@push('scripts')
    @include('layouts.partials.prism-toast')
@endpush
