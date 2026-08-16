@extends('layouts.app')

@section('sidebar')

    @include('layouts.accounting-sidebar')

@endsection

@section('topbar')

    @include('layouts.accounting-topbar')
    @include('accounting.partials.ui')

@endsection

@push('scripts')
<style>
    /* Accounting sidebar density — loads after shared app sidebar CSS */
    #sidebar {
        width: 260px !important;
    }
    #sidebar .sidebar-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px 6px !important;
    }
    #sidebar .logo-icon {
        width: 40px !important;
        height: 40px !important;
        border-radius: 11px !important;
    }
    #sidebar .sidebar-header h2 { font-size: 16px !important; }
    #sidebar .sidebar-header span { font-size: 11px !important; }
    #sidebar .sidebar-content {
        padding: 12px 14px 14px !important;
    }
    #sidebar .sidebar-search {
        height: 34px !important;
        margin-bottom: 10px !important;
        font-size: 13px !important;
    }
    #sidebar .quick-actions {
        gap: 6px !important;
        margin-bottom: 10px !important;
    }
    #sidebar .quick-card {
        height: 54px !important;
        border-radius: 10px !important;
        gap: 4px !important;
    }
    #sidebar .quick-card span { font-size: 10px !important; }
    #sidebar .menu-title {
        margin-top: 14px !important;
        margin-bottom: 4px !important;
        font-size: 10px !important;
    }
    #sidebar .menu-item {
        height: 38px !important;
        font-size: 13px !important;
        gap: 10px !important;
    }
    #sidebar .menu-item.active::before {
        left: -14px !important;
        width: 4px !important;
        height: 24px !important;
    }
</style>
@endpush
