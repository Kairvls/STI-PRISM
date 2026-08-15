{{-- ===================================================== --}}
{{-- RIS CONTENT PARTIAL --}}
{{-- RENDERED VIA AJAX OR FULL PAGE LOAD --}}
{{-- ===================================================== --}}

<div id="risContent">


    {{-- ===================================================== --}}
    {{-- CURRENT FILTER & SEARCH --}}
    {{-- ===================================================== --}}

    @php
        $filter = $filter ?? 'all';
        $search = $search ?? '';
    @endphp


    {{-- ===================================================== --}}
    {{-- RIS STATISTIC CARDS --}}
    {{-- ===================================================== --}}

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">


        {{-- ================================================= --}}
        {{-- PENDING --}}
        {{-- ================================================= --}}

        <div
            class="rounded-[18px] border border-gray-200 bg-white px-5 py-5 shadow-[0_1px_2px_rgba(15,23,42,0.03)]"
            title="RIS forms currently waiting for review"
        >

            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Pending
            </p>

            <div class="mt-3">

                <span class="font-['Outfit'] text-3xl font-bold text-amber-600">
                    {{ $pendingRis }}
                </span>

            </div>

        </div>


        {{-- ================================================= --}}
        {{-- AMEND --}}
        {{-- ================================================= --}}

        <div
            class="rounded-[18px] border border-gray-200 bg-white px-5 py-5 shadow-[0_1px_2px_rgba(15,23,42,0.03)]"
            title="RIS forms returned for amendment"
        >

            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Amend
            </p>

            <div class="mt-3">

                <span class="font-['Outfit'] text-3xl font-bold text-amber-500">
                    {{ $amendRis }}
                </span>

            </div>

        </div>


        {{-- ================================================= --}}
        {{-- FORWARDED TO PRESIDENT --}}
        {{-- ================================================= --}}

        <div
            class="rounded-[18px] border border-gray-200 bg-white px-5 py-5 shadow-[0_1px_2px_rgba(15,23,42,0.03)]"
            title="RIS forms approved by the President"
        >

            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Approved by the President
            </p>

            <div class="mt-3">

                <span class="font-['Outfit'] text-3xl font-bold text-emerald-600">
                    {{ $approvedRis }}
                </span>

            </div>

        </div>


        {{-- ================================================= --}}
        {{-- DIRECT APPROVED --}}
        {{-- ================================================= --}}

        <div
            class="rounded-[18px] border border-gray-200 bg-white px-5 py-5 shadow-[0_1px_2px_rgba(15,23,42,0.03)]"
            title="RIS forms that have been approved by Admin"
        >

            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Admin Approved
            </p>

            <div class="mt-3">

                <span class="font-['Outfit'] text-3xl font-bold text-sky-500">
                    {{ $directApprovedRis }}
                </span>

            </div>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- PROCUREMENT REQUEST TABLE CARD --}}
    {{-- ===================================================== --}}

    <div class="mt-6 overflow-hidden rounded-[18px] border border-gray-200 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.03)]">


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
                            RIS Records
                        </h2>

                        <p class="mt-1 text-xs text-gray-500">
                            Requisition Issue Slips forwarded for review
                        </p>

                    </div>


                    <div class="flex items-center gap-2">

                        <a
                            href="{{ route('admin.procurement-review.export-pdf', ['filter' => $filter, 'search' => $search]) }}"
                            title="Export the current RIS table to PDF"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Export Table to PDF
                        </a>

                        <div
                            id="risTotalCount"
                            class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700"
                            title="Number of RIS records matching the current filter"
                        >

                            {{ $risRecords->total() }} total

                        </div>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- FILTERS + LIVE SEARCH --}}
                {{-- ================================================= --}}

                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">


                    {{-- ================================================= --}}
                    {{-- STATUS FILTER SLIDER --}}
                    {{-- ================================================= --}}

                    <div
                        id="risFilterSlider"
                        class="relative inline-flex max-w-full items-center overflow-x-auto rounded-xl bg-slate-200/70 p-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                    >
                        <span
                            class="ris-filter-thumb pointer-events-none absolute top-1 left-0 z-0 h-9 rounded-lg bg-white shadow-sm will-change-transform"
                            style="transform: translate3d(0, 0, 0); transition: transform 220ms cubic-bezier(0.22, 1, 0.36, 1), width 220ms cubic-bezier(0.22, 1, 0.36, 1);"
                            aria-hidden="true"
                        ></span>

                        <button
                            type="button"
                            data-filter="all"
                            title="Show all RIS records"
                            class="ris-filter-btn relative z-10 flex h-9 shrink-0 items-center rounded-lg px-4 text-xs font-semibold transition-colors
                                {{ $filter === 'all' ? 'text-slate-950' : 'text-slate-500 hover:text-slate-900' }}
                            "
                        >
                            All
                        </button>

                        <button
                            type="button"
                            data-filter="pending"
                            title="Show only Pending RIS records"
                            class="ris-filter-btn relative z-10 flex h-9 shrink-0 items-center rounded-lg px-4 text-xs font-semibold transition-colors
                                {{ $filter === 'pending' ? 'text-slate-950' : 'text-slate-500 hover:text-slate-900' }}
                            "
                        >
                            Pending
                        </button>

                        <button
                            type="button"
                            data-filter="rejected"
                            title="Show RIS records returned for amendment"
                            class="ris-filter-btn relative z-10 flex h-9 shrink-0 items-center rounded-lg px-4 text-xs font-semibold transition-colors
                                {{ $filter === 'rejected' ? 'text-slate-950' : 'text-slate-500 hover:text-slate-900' }}
                            "
                        >
                            Amend
                        </button>

                        <button
                            type="button"
                            data-filter="approved"
                            title="Show RIS records approved by the President"
                            class="ris-filter-btn relative z-10 flex h-9 shrink-0 items-center rounded-lg px-4 text-xs font-semibold transition-colors
                                {{ in_array($filter, ['approved', 'president_approved'], true) ? 'text-slate-950' : 'text-slate-500 hover:text-slate-900' }}
                            "
                        >
                            Approved by the President
                        </button>

                        <button
                            type="button"
                            data-filter="president_rejected"
                            title="Show RIS records rejected by the President"
                            class="ris-filter-btn relative z-10 flex h-9 shrink-0 items-center rounded-lg px-4 text-xs font-semibold transition-colors
                                {{ $filter === 'president_rejected' ? 'text-slate-950' : 'text-slate-500 hover:text-slate-900' }}
                            "
                        >
                            Rejected by the President
                        </button>

                        <button
                            type="button"
                            data-filter="direct_approved"
                            title="Show only Admin Approved RIS records"
                            class="ris-filter-btn relative z-10 flex h-9 shrink-0 items-center rounded-lg px-4 text-xs font-semibold transition-colors
                                {{ $filter === 'direct_approved' ? 'text-slate-950' : 'text-slate-500 hover:text-slate-900' }}
                            "
                        >
                            Admin Approved
                        </button>

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
                            id="risLiveSearch"
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Search RIS, requester, equipment, status..."
                            autocomplete="off"
                            title="Search RIS records"
                            class="w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:border-gray-300 focus:ring-2 focus:ring-gray-100"
                        >

                    </div>

                </div>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- RIS TABLE --}}
        {{-- ===================================================== --}}

        <div class="overflow-x-auto" id="risTableContainer">

            @include('admin.procurement-review._table')

        </div>

    </div>


</div>

