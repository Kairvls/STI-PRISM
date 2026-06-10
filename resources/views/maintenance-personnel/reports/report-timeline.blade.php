@extends('layouts.maintenance-layout')

@section('title', 'Report Timeline')

@section('content')

<div class="max-w-6xl mx-auto">

    <div class="mb-8">

        <h1 class="text-3xl font-extrabold">
            Report Timeline
        </h1>

        <p class="text-gray-400 mt-2">
            Full audit logs and activity history.
        </p>

    </div>

    <div class="bg-[#1E293B] rounded-3xl p-8">

        <div class="space-y-8">

            <!-- TIMELINE ITEM -->
            <div class="flex gap-5">

                <div
                    class="w-14 h-14 rounded-2xl bg-blue-500/20 flex items-center justify-center flex-shrink-0">

                    <i data-lucide="file-text"
                       class="text-blue-400"></i>

                </div>

                <div>

                    <h1 class="font-bold text-lg">

                        Report Submitted

                    </h1>

                    <p class="text-gray-400 mt-1">

                        Submitted by Juan Dela Cruz

                    </p>

                    <p class="text-sm text-gray-500 mt-2">

                        June 10, 2026 08:15 AM

                    </p>

                </div>

            </div>

            <!-- TIMELINE ITEM -->
            <div class="flex gap-5">

                <div
                    class="w-14 h-14 rounded-2xl bg-yellow-500/20 flex items-center justify-center flex-shrink-0">

                    <i data-lucide="user-check"
                       class="text-yellow-400"></i>

                </div>

                <div>

                    <h1 class="font-bold text-lg">

                        Assigned Personnel

                    </h1>

                    <p class="text-gray-400 mt-1">

                        Assigned to Kenn Mehares

                    </p>

                    <p class="text-sm text-gray-500 mt-2">

                        June 10, 2026 09:00 AM

                    </p>

                </div>

            </div>

            <!-- TIMELINE ITEM -->
            <div class="flex gap-5">

                <div
                    class="w-14 h-14 rounded-2xl bg-green-500/20 flex items-center justify-center flex-shrink-0">

                    <i data-lucide="badge-check"
                       class="text-green-400"></i>

                </div>

                <div>

                    <h1 class="font-bold text-lg">

                        Report Resolved

                    </h1>

                    <p class="text-gray-400 mt-1">

                        Air conditioning unit repaired successfully.

                    </p>

                    <p class="text-sm text-gray-500 mt-2">

                        June 11, 2026 02:45 PM

                    </p>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection