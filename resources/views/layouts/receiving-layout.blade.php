@extends('layouts.app')

@section('body-class', 'pp-layout')

@section('sidebar')

    @include('layouts.receiving-sidebar')

@endsection



@section('topbar')

    @include('layouts.receiving-topbar')

@endsection

@push('scripts')
    @include('layouts.partials.prism-toast')
    @include('layouts.partials.receiving-table-filters-script')
@endpush
