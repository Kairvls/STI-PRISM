{{-- ===================================================== --}}
{{-- SIGN RIS CONTENT PARTIAL --}}
{{-- RENDERED VIA AJAX OR FULL PAGE LOAD --}}
{{-- ===================================================== --}}

<div id="signRisContent">


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

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">


        {{-- ================================================= --}}
        {{-- FOR CO-SIGN --}}
        {{-- ================================================= --}}

        <div
            class="rounded-xl border border-gray-200 bg-white px-5 py-5"
            title="RIS forms awaiting your co-sign"
        >

            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                For Co-sign
            </p>

            <div class="mt-3">

                <span class="text-3xl font-bold text-amber-600">
                    {{ $forCosignCount }}
                </span>

            </div>

            <div class="mt-1 text-xs text-gray-400">
                ₱{{ number_format((float) ($forCosignAmount ?? 0), 2) }}
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

                <span class="text-3xl font-bold text-emerald-600">
                    {{ $cosignedCount }}
                </span>

            </div>

            <div class="mt-1 text-xs text-gray-400">
                ₱{{ number_format((float) ($cosignedAmount ?? 0), 2) }}
            </div>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- SIGN RIS TABLE CARD --}}
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
                            President-Approved RIS
                        </h2>

                        <p class="mt-1 text-xs text-gray-500">
                            President-approved and legacy Approved RIS records for co-sign / logging
                        </p>

                    </div>


                    <div class="flex items-center gap-2">

                        <a
                            href="{{ route('admin.digital-signatures.sign-ris.export-pdf', ['filter' => $filter, 'search' => $search]) }}"
                            title="Export the current Sign RIS table to PDF"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Export PDF
                        </a>

                        <div
                            id="signRisTotalCount"
                            class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700"
                            title="Number of RIS records matching the current filter"
                        >

                            {{ $signableRisRecords->total() }} total

                        </div>

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
                            title="Show all President-approved RIS records"
                            class="sign-ris-filter-btn rounded-lg px-4 py-2 text-sm font-semibold transition
                                {{ $filter === 'all'
                                    ? 'bg-slate-900 text-white shadow-sm'
                                    : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                }}
                            "
                        >
                            All
                        </button>


                        {{-- FOR CO-SIGN --}}

                        <button
                            type="button"
                            data-filter="for_cosign"
                            title="Show only RIS records awaiting your co-sign"
                            class="sign-ris-filter-btn rounded-lg px-4 py-2 text-sm font-semibold transition
                                {{ $filter === 'for_cosign'
                                    ? 'border border-amber-300 bg-amber-50 text-amber-700'
                                    : 'border border-gray-200 bg-white text-gray-600 hover:border-amber-200 hover:bg-amber-50 hover:text-amber-700'
                                }}
                            "
                        >
                            For Co-sign
                        </button>


                        {{-- CO-SIGNED --}}

                        <button
                            type="button"
                            data-filter="cosigned"
                            title="Show only co-signed RIS records"
                            class="sign-ris-filter-btn rounded-lg px-4 py-2 text-sm font-semibold transition
                                {{ $filter === 'cosigned'
                                    ? 'border border-emerald-300 bg-emerald-50 text-emerald-700'
                                    : 'border border-gray-200 bg-white text-gray-600 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700'
                                }}
                            "
                        >
                            Co-signed
                        </button>


                        {{-- LEGACY / INVALID --}}

                        <button
                            type="button"
                            data-filter="legacy"
                            title="Show old or incomplete Approved RIS records that are not eligible for co-sign"
                            class="sign-ris-filter-btn rounded-lg px-4 py-2 text-sm font-semibold transition
                                {{ $filter === 'legacy'
                                    ? 'border border-slate-400 bg-slate-100 text-slate-900'
                                    : 'border border-gray-200 bg-white text-gray-600 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900'
                                }}
                            "
                        >
                            Legacy / Invalid
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
                            id="signRisLiveSearch"
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Search RIS, requester, equipment..."
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

        <div class="overflow-x-auto" id="signRisTableContainer">

            @include('admin.digital-signatures._sign-ris-table')

        </div>

    </div>


</div>

