{{-- ===================================================== --}}
{{-- SIGNATURE HISTORY CONTENT PARTIAL --}}
{{-- RENDERED VIA AJAX OR FULL PAGE LOAD --}}
{{-- ===================================================== --}}

<div id="signatureHistoryContent">


    {{-- ===================================================== --}}
    {{-- CURRENT SEARCH --}}
    {{-- ===================================================== --}}

    @php
        $search = $search ?? '';
    @endphp


    {{-- ===================================================== --}}
    {{-- RIS STATISTIC CARDS --}}
    {{-- ===================================================== --}}

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">


        {{-- ================================================= --}}
        {{-- DIRECT APPROVED --}}
        {{-- ================================================= --}}

        <div
            class="rounded-xl border border-gray-200 bg-white px-5 py-5"
            title="RIS forms that have been directly approved by Admin (returned to Purchaser)"
        >

            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Direct Approved
            </p>

            <div class="mt-3">

                <span class="text-3xl font-bold text-slate-900">
                    {{ $directApprovedCount }}
                </span>

            </div>

        </div>


        {{-- ================================================= --}}
        {{-- SIGNED (FORWARDED TO PRESIDENT / PRESIDENT-APPROVED) --}}
        {{-- ================================================= --}}

        <div
            class="rounded-xl border border-gray-200 bg-white px-5 py-5"
            title="RIS forms signed/approved by the President"
        >

            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Signed
            </p>

            <div class="mt-3">

                <span class="text-3xl font-bold text-emerald-600">
                    {{ $signedCount }}
                </span>

            </div>

        </div>


        {{-- ================================================= --}}
        {{-- CO-SIGNED --}}
        {{-- ================================================= --}}

        <div
            class="rounded-xl border border-gray-200 bg-white px-5 py-5"
            title="RIS forms that have been co-signed"
        >

            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Co-signed
            </p>

            <div class="mt-3">

                <span class="text-3xl font-bold text-amber-600">
                    {{ $cosignedCount }}
                </span>

            </div>

        </div>


        {{-- ================================================= --}}
        {{-- AMENDED --}}
        {{-- ================================================= --}}

        <div
            class="rounded-xl border border-gray-200 bg-white px-5 py-5"
            title="RIS forms that were amended (rejected / returned for revision)"
        >

            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Amended
            </p>

            <div class="mt-3">

                <span class="text-3xl font-bold text-rose-600">
                    {{ $amendedCount }}
                </span>

            </div>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- SIGNATURE HISTORY TABLE CARD --}}
    {{-- ===================================================== --}}

    <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white">


        {{-- ================================================= --}}
        {{-- TABLE CARD HEADER --}}
        {{-- ================================================= --}}

        <div class="border-b border-gray-100 px-5 py-4">

            <div class="flex flex-col gap-4">


                {{-- ================================================= --}}
                {{-- TABLE TITLE --}}
                {{-- ================================================= --}}

                <div class="flex items-center justify-between gap-4">

                    <div>

                        <h2 class="text-sm font-semibold text-gray-900">
                            RIS History
                        </h2>

                        <p class="mt-1 text-xs text-gray-500">
                            View all finished / completed RIS forms, sorted by latest
                        </p>

                    </div>


                    {{-- ================================================= --}}
                    {{-- CURRENT RESULT TOTAL --}}
                    {{-- ================================================= --}}

                    <div
                        id="signatureHistoryTotalCount"
                        class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700"
                        title="Number of RIS records matching the current search"
                    >

                        {{ $signatureHistory->total() }} total

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- LIVE SEARCH (default "all" view, no toggle) --}}
                {{-- ================================================= --}}

                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">

                    <div class="flex items-center gap-2">

                        <span class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm">
                            All
                        </span>

                    </div>


                    {{-- ================================================= --}}
                    {{-- LIVE SEARCH --}}
                    {{-- ================================================= --}}

                    <div class="relative w-full lg:max-w-md">

                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">

                            <svg
                                class="h-4 w-4 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"
                                ></path>

                            </svg>

                        </div>


                        <input
                            id="signatureHistoryLiveSearch"
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Search RIS, requester, equipment..."
                            autocomplete="off"
                            title="Search signature history records"
                            class="w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:ring-2 focus:ring-gray-100"
                        >

                    </div>

                </div>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- SIGNATURE HISTORY TABLE --}}
        {{-- ===================================================== --}}

        <div class="overflow-x-auto" id="signatureHistoryTableContainer">

            @include('admin.digital-signatures._signature-history-table')

        </div>

    </div>


</div>

