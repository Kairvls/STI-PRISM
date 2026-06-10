@extends('layouts.maintenance-layout')

@section('title', 'Assign Report')

@section('content')

<div class="max-w-5xl mx-auto">

    <!-- HEADER -->
    <div class="mb-8">

        <h1 class="text-3xl font-extrabold">
            Assign Report
        </h1>

        <p class="text-gray-400 mt-2">
            Assign maintenance personnel to this report.
        </p>

    </div>

    <!-- REPORT CARD -->
    <div class="bg-[#1E293B] rounded-3xl p-8 mb-8">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div>

                <p class="text-gray-400 text-sm">
                    Report ID
                </p>

                <h1 class="text-xl font-bold mt-2">
                    RP-2026-001
                </h1>

            </div>

            <div>

                <p class="text-gray-400 text-sm">
                    Current Status
                </p>

                <span class="bg-yellow-500/20 text-yellow-400 px-4 py-2 rounded-xl inline-block mt-2">

                    Pending

                </span>

            </div>

            <div>

                <p class="text-gray-400 text-sm">
                    Problem Description
                </p>

                <h1 class="font-semibold mt-2">
                    Air conditioning unit not cooling properly.
                </h1>

            </div>

            <div>

                <p class="text-gray-400 text-sm">
                    Room
                </p>

                <h1 class="font-semibold mt-2">
                    Computer Laboratory 1
                </h1>

            </div>

        </div>

    </div>

    <!-- ASSIGN FORM -->
    <div class="bg-[#1E293B] rounded-3xl p-8">

        <form action="#"
              method="POST">

            @csrf

            <div class="mb-6">

                <label class="block mb-3 font-semibold">

                    Assign Maintenance Personnel

                </label>

                <select
                    class="w-full bg-[#0F172A] border border-white/10 rounded-2xl px-5 py-4 text-white">

                    <option>
                        Select Personnel
                    </option>

                    <option>
                        Kenn Mehares
                    </option>

                    <option>
                        John Dela Cruz
                    </option>

                </select>

            </div>

            <div class="mb-6">

                <label class="block mb-3 font-semibold">

                    Assignment Remarks

                </label>

                <textarea
                    rows="5"
                    class="w-full bg-[#0F172A] border border-white/10 rounded-2xl px-5 py-4 text-white resize-none"
                    placeholder="Enter assignment remarks..."></textarea>

            </div>

            <button
                class="bg-blue-600 hover:bg-blue-700 px-8 py-4 rounded-2xl font-bold transition">

                Assign Report

            </button>

        </form>

    </div>

</div>

@endsection