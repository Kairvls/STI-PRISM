@extends('layouts.maintenance-layout')

@section('title', 'Maintenance Dashboard')

@section('content')

<!-- HEADER -->
<div class="mb-8">

    <h1 class="text-3xl font-extrabold">
        Welcome back, Kenn Mehares
    </h1>

    <p class="text-gray-400 mt-2">
        Maintenance Personnel Dashboard
    </p>

    <p class="text-sm text-gray-500 mt-1">

        {{ now()->format('F d, Y h:i A') }}

    </p>

</div>

<!-- QUICK ACTIONS -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">

    <button class="bg-blue-600 hover:bg-blue-700 p-5 rounded-2xl font-semibold">

        + Scan QR

    </button>

    <button class="bg-yellow-500 hover:bg-yellow-600 p-5 rounded-2xl font-semibold text-black">

        + Add Maintenance Findings

    </button>

    <button class="bg-red-600 hover:bg-red-700 p-5 rounded-2xl font-semibold">

        + View Urgent Reports

    </button>

    <button class="bg-green-600 hover:bg-green-700 p-5 rounded-2xl font-semibold">

        + Create Borrowing Record

    </button>

</div>

<!-- STAT CARDS -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-5">

    <!-- CARD -->
    <div class="bg-[#1F2937] p-5 rounded-2xl">

        <p class="text-gray-400 text-sm">
            Pending Reports
        </p>

        <h2 class="text-4xl font-extrabold mt-3">
            14
        </h2>

    </div>

    <!-- CARD -->
    <div class="bg-[#1F2937] p-5 rounded-2xl">

        <p class="text-gray-400 text-sm">
            Urgent Reports
        </p>

        <h2 class="text-4xl font-extrabold mt-3 text-red-400">
            5
        </h2>

    </div>

    <!-- CARD -->
    <div class="bg-[#1F2937] p-5 rounded-2xl">

        <p class="text-gray-400 text-sm">
            Equipment Under Maintenance
        </p>

        <h2 class="text-4xl font-extrabold mt-3 text-yellow-400">
            11
        </h2>

    </div>

    <!-- CARD -->
    <div class="bg-[#1F2937] p-5 rounded-2xl">

        <p class="text-gray-400 text-sm">
            Borrowed Equipment
        </p>

        <h2 class="text-4xl font-extrabold mt-3 text-green-400">
            9
        </h2>

    </div>

    <!-- CARD -->
    <div class="bg-[#1F2937] p-5 rounded-2xl">

        <p class="text-gray-400 text-sm">
            Overdue Maintenance
        </p>

        <h2 class="text-4xl font-extrabold mt-3 text-orange-400">
            3
        </h2>

    </div>

</div>

@endsection