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
        {{-- TOTAL RIS --}}
        {{-- ================================================= --}}

        <div
            class="rounded-xl border border-gray-200 bg-white px-5 py-5"
            title="Total number of submitted RIS forms"
        >

            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Total RIS
            </p>

            <div class="mt-3">

                <span class="text-3xl font-bold text-gray-900">
                    {{ $totalRis }}
                </span>

            </div>

        </div>


        {{-- ================================================= --}}
        {{-- PENDING --}}
        {{-- ================================================= --}}

        <div
            class="rounded-xl border border-gray-200 bg-white px-5 py-5"
            title="RIS forms currently waiting for review"
        >

            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Pending
            </p>

            <div class="mt-3">

                <span class="text-3xl font-bold text-amber-600">
                    {{ $pendingRis }}
                </span>

            </div>

        </div>


        {{-- ================================================= --}}
        {{-- AMEND --}}
        {{-- ================================================= --}}

        <div
            class="rounded-xl border border-gray-200 bg-white px-5 py-5"
            title="RIS forms returned for amendment"
        >

            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Amend
            </p>

            <div class="mt-3">

                <span class="text-3xl font-bold text-rose-600">
                    {{ $amendRis }}
                </span>

            </div>

        </div>


        {{-- ================================================= --}}
        {{-- APPROVED FOR PRESIDENT --}}
        {{-- ================================================= --}}

        <div
            class="rounded-xl border border-gray-200 bg-white px-5 py-5"
            title="RIS forms approved and forwarded to the President"
        >

            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Approved for President
            </p>

            <div class="mt-3">

                <span class="text-3xl font-bold text-emerald-600">
                    {{ $approvedRis }}
                </span>

            </div>

        </div>


        {{-- ================================================= --}}
        {{-- DIRECT APPROVAL --}}
        {{-- ================================================= --}}

        <div
            class="rounded-xl border border-gray-200 bg-white px-5 py-5"
            title="RIS forms that have been directly approved and returned to Purchaser"
        >

            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Direct Approval
            </p>

            <div class="mt-3">

                <span class="text-3xl font-bold text-slate-900">
                    {{ $directApprovedRis }}
                </span>

            </div>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- PROCUREMENT REQUEST TABLE CARD --}}
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
                            RIS Records
                        </h2>

                        <p class="mt-1 text-xs text-gray-500">
                            Requisition Issue Slips forwarded for review
                        </p>

                    </div>


                    {{-- ================================================= --}}
                    {{-- CURRENT RESULT TOTAL --}}
                    {{-- ================================================= --}}

                    <div
                        id="risTotalCount"
                        class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700"
                        title="Number of RIS records matching the current filter"
                    >

                        {{ $risRecords->total() }} total

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- FILTERS + LIVE SEARCH --}}
                {{-- ================================================= --}}

                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">


                    {{-- ================================================= --}}
                    {{-- STATUS FILTER TOGGLES --}}
                    {{-- ================================================= --}}

                    <div class="flex flex-wrap items-center gap-2">


                        {{-- ALL --}}

                        <button
                            type="button"
                            data-filter="all"
                            title="Show all RIS records"
                            class="ris-filter-btn rounded-lg px-4 py-2 text-sm font-semibold transition
                                {{ $filter === 'all'
                                    ? 'bg-slate-900 text-white shadow-sm'
                                    : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                }}
                            "
                        >
                            All
                        </button>


                        {{-- PENDING --}}

                        <button
                            type="button"
                            data-filter="pending"
                            title="Show only Pending RIS records"
                            class="ris-filter-btn rounded-lg px-4 py-2 text-sm font-semibold transition
                                {{ $filter === 'pending'
                                    ? 'border border-amber-300 bg-amber-50 text-amber-700'
                                    : 'border border-gray-200 bg-white text-gray-600 hover:border-amber-200 hover:bg-amber-50 hover:text-amber-700'
                                }}
                            "
                        >
                            Pending
                        </button>


                        {{-- AMEND --}}

                        <button
                            type="button"
                            data-filter="rejected"
                            title="Show RIS records returned for amendment"
                            class="ris-filter-btn rounded-lg px-4 py-2 text-sm font-semibold transition
                                {{ $filter === 'rejected'
                                    ? 'border border-rose-300 bg-rose-50 text-rose-700'
                                    : 'border border-gray-200 bg-white text-gray-600 hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700'
                                }}
                            "
                        >
                            Amend
                        </button>


                        {{-- APPROVED FOR PRESIDENT --}}

                        <button
                            type="button"
                            data-filter="approved"
                            title="Show only RIS records approved for the President"
                            class="ris-filter-btn rounded-lg px-4 py-2 text-sm font-semibold transition
                                {{ $filter === 'approved'
                                    ? 'border border-emerald-300 bg-emerald-50 text-emerald-700'
                                    : 'border border-gray-200 bg-white text-gray-600 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700'
                                }}
                            "
                        >
                            Approved for President
                        </button>


                        {{-- DIRECT APPROVAL --}}

                        <button
                            type="button"
                            data-filter="direct_approved"
                            title="Show only Directly Approved RIS records"
                            class="ris-filter-btn rounded-lg px-4 py-2 text-sm font-semibold transition
                                {{ $filter === 'direct_approved'
                                    ? 'border border-slate-400 bg-slate-100 text-slate-900'
                                    : 'border border-gray-200 bg-white text-gray-600 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900'
                                }}
                            "
                        >
                            Direct Approval
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

