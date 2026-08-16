@extends('layouts.app')

@section('sidebar')

    @include('layouts.receiving-sidebar')

@endsection

@section('topbar')

    @include('layouts.receiving-topbar')

@endsection

@push('scripts')
    @include('layouts.partials.receiving-table-filters-script')
@endpush