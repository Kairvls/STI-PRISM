@extends('layouts.maintenance-layout')

@section('title', 'Maintenance Dashboard')

@section('content')

<!-- TOP HEADER -->
<div class="flex flex-col xl:flex-row xl:items-center xl:justify-between mb-8 gap-4">

    <div>

        <h1 class="text-4xl font-extrabold">

            Welcome back, Kenn Mehares

        </h1>

        <p class="text-gray-400 mt-2">

            Maintenance Personnel Dashboard

        </p>

    </div>

    <div class="bg-[#1E293B] px-5 py-4 rounded-2xl">

        <p class="text-sm text-gray-400">

            Current Date & Time

        </p>

        <h1 class="text-lg font-bold mt-1">

            {{ now()->format('F d, Y h:i A') }}

        </h1>

    </div>

</div>

<!-- QUICK ACTIONS -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">

    <button class="dashboard-action bg-blue-600">

        <i data-lucide="scan-line"></i>

        Scan QR

    </button>

    <button class="dashboard-action bg-yellow-500 text-black">

        <i data-lucide="clipboard-pen-line"></i>

        Add Findings

    </button>

    <button class="dashboard-action bg-red-500">

        <i data-lucide="triangle-alert"></i>

        Urgent Reports

    </button>

    <button class="dashboard-action bg-green-600">

        <i data-lucide="package-plus"></i>

        Borrowing Record

    </button>

</div>

<!-- STATISTICS -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-5 mb-8">

    <div class="dashboard-card">

        <p class="dashboard-card-title">
            Pending Reports
        </p>

        <h1 class="dashboard-card-number text-yellow-400">

            {{ $pendingReports }}

        </h1>

    </div>

    <div class="dashboard-card">

        <p class="dashboard-card-title">
            Urgent Reports
        </p>

        <h1 class="dashboard-card-number text-red-400">

            {{ $urgentReports }}

        </h1>

    </div>

    <div class="dashboard-card">

        <p class="dashboard-card-title">
            Under Maintenance
        </p>

        <h1 class="dashboard-card-number text-blue-400">

            {{ $underMaintenance }}

        </h1>

    </div>

    <div class="dashboard-card">

        <p class="dashboard-card-title">
            Borrowed Equipment
        </p>

        <h1 class="dashboard-card-number text-green-400">

            {{ $borrowedEquipment }}

        </h1>

    </div>

    <div class="dashboard-card">

        <p class="dashboard-card-title">
            Overdue Maintenance
        </p>

        <h1 class="dashboard-card-number text-orange-400">

            {{ $overdueMaintenance }}

        </h1>

    </div>

</div>

<!-- BUILDINGS & ROOMS -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">

    <!-- BUILDINGS -->
    <div class="xl:col-span-2 bg-[#1E293B] rounded-3xl p-6">

        <div class="flex items-center justify-between mb-6">

            <div>

                <h1 class="text-2xl font-bold">

                    Buildings & Rooms Monitoring

                </h1>

                <p class="text-gray-400 mt-1">

                    Real-time room equipment tracking

                </p>

            </div>

            <button class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-xl text-sm font-semibold">

                View All

            </button>

        </div>

        <!-- BUILDING GRID -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            <!-- BUILDING CARD -->
            <div class="bg-[#0F172A] rounded-2xl p-5 border border-white/5">

                <div class="flex items-center justify-between mb-4">

                    <div>

                        <h1 class="text-xl font-bold">
                            Building A
                        </h1>

                        <p class="text-gray-400 text-sm">
                            2 Floors • 18 Rooms
                        </p>

                    </div>

                    <div class="w-12 h-12 rounded-2xl bg-blue-500/20 flex items-center justify-center">

                        <i data-lucide="building-2"
                           class="text-blue-400"></i>

                    </div>

                </div>

                <div class="space-y-3">

                    <div class="flex items-center justify-between">

                        <span class="text-gray-400">
                            Active Equipment
                        </span>

                        <span class="font-bold text-green-400">
                            128
                        </span>

                    </div>

                    <div class="flex items-center justify-between">

                        <span class="text-gray-400">
                            Damaged
                        </span>

                        <span class="font-bold text-red-400">
                            4
                        </span>

                    </div>

                </div>

            </div>

            <!-- ROOM CARD -->
            <div class="bg-[#0F172A] rounded-2xl p-5 border border-white/5">

                <div class="flex items-center justify-between mb-4">

                    <div>

                        <h1 class="text-xl font-bold">
                            Computer Lab 1
                        </h1>

                        <p class="text-gray-400 text-sm">
                            32 Equipment
                        </p>

                    </div>

                    <div class="w-12 h-12 rounded-2xl bg-yellow-500/20 flex items-center justify-center">

                        <i data-lucide="monitor-smartphone"
                           class="text-yellow-400"></i>

                    </div>

                </div>

                <div class="space-y-3">

                    <div class="flex items-center justify-between">

                        <span class="text-gray-400">
                            Functional
                        </span>

                        <span class="font-bold text-green-400">
                            29
                        </span>

                    </div>

                    <div class="flex items-center justify-between">

                        <span class="text-gray-400">
                            Need Repair
                        </span>

                        <span class="font-bold text-red-400">
                            3
                        </span>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- ACTIVITY -->
    <div class="bg-[#1E293B] rounded-3xl p-6">

        <h1 class="text-2xl font-bold mb-6">

            Recent Activities

        </h1>

        <div class="space-y-5">

            <div class="flex gap-4">

                <div class="w-11 h-11 rounded-2xl bg-red-500/20 flex items-center justify-center">

                    <i data-lucide="triangle-alert"
                       class="text-red-400"></i>

                </div>

                <div>

                    <h1 class="font-semibold">

                        Urgent Report Submitted

                    </h1>

                    <p class="text-sm text-gray-400 mt-1">

                        Aircon malfunction at Room 204

                    </p>

                </div>

            </div>

            <div class="flex gap-4">

                <div class="w-11 h-11 rounded-2xl bg-green-500/20 flex items-center justify-center">

                    <i data-lucide="badge-check"
                       class="text-green-400"></i>

                </div>

                <div>

                    <h1 class="font-semibold">

                        Equipment Repaired

                    </h1>

                    <p class="text-sm text-gray-400 mt-1">

                        Projector repaired at AVR Room

                    </p>

                </div>

            </div>

        </div>

    </div>

</div>

<style>

.dashboard-action{

    padding:22px;
    border-radius:24px;
    display:flex;
    align-items:center;
    gap:15px;
    font-weight:700;
    transition:.2s;

}

.dashboard-action:hover{

    transform:translateY(-3px);

}

.dashboard-card{

    background:#1E293B;
    padding:24px;
    border-radius:24px;

}

.dashboard-card-title{

    color:#94A3B8;
    font-size:14px;

}

.dashboard-card-number{

    font-size:42px;
    font-weight:800;
    margin-top:12px;

}

</style>

@endsection

