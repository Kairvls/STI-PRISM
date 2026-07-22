@extends('layouts.purchaser-layout')

@section("page-title", "Request Check")
@section("page-subtitle", "Review and verify procurement requests")

@section("content")

<div>

    {{-- =========================
         PAGE HEADER
    ========================== --}}
    <div class="mb-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-gray-900"></span>
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">
                        Procurement
                    </span>
                </div>

                <h1 class="text-3xl font-semibold tracking-tight text-gray-950">
                    Request Check
                </h1>

                <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-500">
                    Review procurement requests and verify their details before processing.
                </p>
            </div>
        </div>
    </div>


    {{-- =========================
         SUMMARY STRIP
    ========================== --}}
    <div class="mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="grid grid-cols-2 divide-gray-100 sm:grid-cols-3 lg:grid-cols-5 lg:divide-x">

            <div class="px-5 py-5">
                <p class="text-2xl font-semibold tracking-tight text-gray-950">0</p>
                <p class="mt-1 text-xs font-medium text-gray-500">Total Requests</p>
            </div>

            <div class="px-5 py-5">
                <div class="flex items-center gap-2">
                    <p class="text-2xl font-semibold tracking-tight text-gray-950">0</p>
                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                </div>
                <p class="mt-1 text-xs font-medium text-gray-500">Pending Check</p>
            </div>

            <div class="px-5 py-5">
                <p class="text-2xl font-semibold tracking-tight text-gray-950">0</p>
                <p class="mt-1 text-xs font-medium text-gray-500">Under Review</p>
            </div>

            <div class="px-5 py-5">
                <p class="text-2xl font-semibold tracking-tight text-gray-950">0</p>
                <p class="mt-1 text-xs font-medium text-gray-500">Verified</p>
            </div>

            <div class="px-5 py-5">
                <p class="text-2xl font-semibold tracking-tight text-gray-950">0</p>
                <p class="mt-1 text-xs font-medium text-gray-500">Returned</p>
            </div>

        </div>
    </div>


    {{-- =========================
         RECORDS CONTAINER
    ========================== --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">

        {{-- TOOLBAR --}}
        <div class="border-b border-gray-100 px-5 py-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">

                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-base font-semibold text-gray-950">
                            Request Records
                        </h2>

                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">
                            0
                        </span>
                    </div>

                    <p class="mt-1 text-xs text-gray-400">
                        Review requests requiring purchaser verification.
                    </p>
                </div>

                {{-- FILTERS --}}
                <div class="flex flex-col gap-2 sm:flex-row">

                    {{-- SEARCH --}}
                    <div class="relative">
                        <svg
                            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"
                            />
                        </svg>

                        <input
                            type="text"
                            placeholder="Search requests..."
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-4 text-sm text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-64"
                        >
                    </div>

                    {{-- STATUS --}}
                    <select
                        class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white"
                    >
                        <option>All statuses</option>
                        <option>Pending Check</option>
                        <option>Under Review</option>
                        <option>Verified</option>
                        <option>Returned</option>
                    </select>

                    {{-- DATE --}}
                    <input
                        type="date"
                        class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-600 outline-none transition focus:border-gray-300 focus:bg-white"
                    >

                    <button
                        type="button"
                        class="rounded-xl bg-gray-950 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800"
                    >
                        Apply
                    </button>

                </div>
            </div>
        </div>


        {{-- TABLE --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">

                <thead class="bg-gray-50/70">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            Request Number
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            Requested By
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            Request Type
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            Status
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            Date
                        </th>

                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">
                            Action
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 bg-white">

                    {{-- EMPTY STATE --}}
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">

                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100">
                                <svg
                                    class="h-5 w-5 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M9 12h6m-6 4h6M9 8h2m-4 13h10a2 2 0 0 0 2-2V5l-4-4H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2Z"
                                    />
                                </svg>
                            </div>

                            <p class="mt-4 font-medium text-gray-700">
                                No requests found
                            </p>

                            <p class="mt-1 text-sm text-gray-400">
                                Requests requiring verification will appear here.
                            </p>

                        </td>
                    </tr>

                </tbody>
            </table>
        </div>


        {{-- PAGINATION PLACEHOLDER --}}
        <div class="border-t border-gray-100 px-6 py-4">
            <p class="text-xs text-gray-400">
                Pagination will appear here.
            </p>
        </div>

    </div>

</div>

@endsection