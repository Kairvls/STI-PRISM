@extends('layouts.maintenance-layout')

@section('title', "Today's Maintenance")

@section('content')

    <div class="space-y-6">

        {{-- ===================================== --}}
        {{-- SCHEDULE LIST --}}
        {{-- ===================================== --}}

        <div
            class="overflow-hidden rounded-2xl
                border border-slate-200 bg-white"
        >

            @forelse ($schedules as $schedule)

                <div
                    class="flex items-center justify-between
                        border-b border-slate-100
                        px-5 py-4 last:border-b-0"
                >

                    {{-- ===================================== --}}
                    {{-- SCHEDULE INFORMATION --}}
                    {{-- ===================================== --}}

                    <div>

                        <p class="text-sm font-semibold text-slate-900">

                            {{
                                $schedule->maintenance_schedule_title
                                ?? 'Maintenance Task'
                            }}

                        </p>


                        <div
                            class="mt-1 flex items-center gap-3
                                text-xs text-slate-500"
                        >

                            <span>

                                {{
                                    $schedule->equipment_name
                                    ?? 'Unknown equipment'
                                }}

                            </span>


                            <span>
                                •
                            </span>


                            <span>

                                {{
                                    $schedule->room_name
                                    ?? 'No room assigned'
                                }}

                            </span>

                        </div>

                    </div>


                    {{-- ===================================== --}}
                    {{-- STATUS --}}
                    {{-- ===================================== --}}

                    <span
                        class="rounded-full bg-slate-100
                            px-2.5 py-1 text-xs
                            font-medium text-slate-600"
                    >

                        {{
                            $schedule->maintenance_schedule_status
                        }}

                    </span>

                </div>


            @empty

                {{-- ===================================== --}}
                {{-- EMPTY STATE --}}
                {{-- ===================================== --}}

                <div
                    class="flex min-h-[280px]
                        flex-col items-center
                        justify-center text-center"
                >

                    <div
                        class="flex h-11 w-11
                            items-center justify-center
                            rounded-xl bg-slate-100
                            text-slate-400"
                    >

                        <i
                            data-lucide="calendar-check"
                            class="h-5 w-5"
                        ></i>

                    </div>


                    <h3
                        class="mt-3 text-sm font-semibold
                            text-slate-700"
                    >
                        No maintenance scheduled today
                    </h3>


                    <p
                        class="mt-1 text-xs
                            text-slate-400"
                    >
                        Today's maintenance tasks will appear here.
                    </p>

                </div>

            @endforelse

        </div>

    </div>

@endsection