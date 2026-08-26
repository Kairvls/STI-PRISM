@extends("layouts.purchaser-layout")


{{-- ===================================================== --}}
{{-- PAGE TITLE --}}
{{-- ===================================================== --}}

@section("page-title", "Dashboard")


{{-- ===================================================== --}}
{{-- PAGE SUBTITLE --}}
{{-- ===================================================== --}}

@section(
    "page-subtitle",
    "Overview of procurement requests and purchasing activity"
)


{{-- ===================================================== --}}
{{-- PAGE CONTENT --}}
{{-- ===================================================== --}}

@section("content")

<div>


    {{-- ===================================================== --}}
    {{-- PAGE HEADING --}}
    {{-- ===================================================== --}}

    <div class="flex justify-end">
        <a
            href="{{ route('purchaser.procurement.replacement-requests') }}"
            class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 text-sm font-semibold text-white transition hover:bg-gray-800"
        >

            <i
                data-lucide="inbox"
                class="h-4 w-4"
            ></i>

            View Requests

        </a>
    </div>



    {{-- ===================================================== --}}
    {{-- DASHBOARD STAT CARDS --}}
    {{-- ===================================================== --}}

    <div class="mt-7 grid grid-cols-1 gap-4 md:grid-cols-4">


        {{-- ================================================= --}}
        {{-- PENDING REQUESTS --}}
        {{-- ================================================= --}}

        <a
            href="{{
                route(
                    'purchaser.procurement.replacement-requests',
                    ['status' => 'Pending']
                )
            }}"
            class="group rounded-xl border border-gray-200 bg-white p-5 transition hover:border-gray-300 hover:shadow-sm"
        >

            <div class="flex items-start justify-between gap-4">

                <div>

                    <p class="text-sm font-medium text-gray-500">
                        Pending Requests
                    </p>

                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">

                        {{
                            number_format(
                                $pendingReplacementRequests
                            )
                        }}

                    </p>

                </div>


                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-700">

                    <i
                        data-lucide="clock-3"
                        class="h-5 w-5"
                    ></i>

                </div>

            </div>


            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">

                <span>
                    Waiting for purchaser review
                </span>

                <i
                    data-lucide="arrow-right"
                    class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"
                ></i>

            </div>

        </a>



        {{-- ================================================= --}}
        {{-- APPROVED REQUESTS --}}
        {{-- ================================================= --}}

        <a
            href="{{
                route(
                    'purchaser.procurement.replacement-requests',
                    ['status' => 'Approved']
                )
            }}"
            class="group rounded-xl border border-gray-200 bg-white p-5 transition hover:border-gray-300 hover:shadow-sm"
        >

            <div class="flex items-start justify-between gap-4">

                <div>

                    <p class="text-sm font-medium text-gray-500">
                        Approved Requests
                    </p>

                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">

                        {{
                            number_format(
                                $approvedReplacementRequests
                            )
                        }}

                    </p>

                </div>


                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">

                    <i
                        data-lucide="circle-check-big"
                        class="h-5 w-5"
                    ></i>

                </div>

            </div>


            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">

                <span>
                    Approved for purchasing workflow
                </span>

                <i
                    data-lucide="arrow-right"
                    class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"
                ></i>

            </div>

        </a>



        {{-- ================================================= --}}
        {{-- COMPLETED REQUESTS --}}
        {{-- ================================================= --}}

        <a
            href="{{
                route(
                    'purchaser.procurement.replacement-requests',
                    ['status' => 'Completed']
                )
            }}"
            class="group rounded-xl border border-gray-200 bg-white p-5 transition hover:border-gray-300 hover:shadow-sm"
        >

            <div class="flex items-start justify-between gap-4">

                <div>

                    <p class="text-sm font-medium text-gray-500">
                        Completed Requests
                    </p>

                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">

                        {{
                            number_format(
                                $completedReplacementRequests
                            )
                        }}

                    </p>

                </div>


                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-700">

                    <i
                        data-lucide="package-check"
                        class="h-5 w-5"
                    ></i>

                </div>

            </div>


            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">

                <span>
                    Finished procurement requests
                </span>

                <i
                    data-lucide="arrow-right"
                    class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"
                ></i>

            </div>

        </a>


        {{-- ================================================= --}}
        {{-- ATP READY RIS --}}
        {{-- ================================================= --}}

        <a
            href="{{ route('purchaser.ris.index', ['status' => 'Approved']) }}"
            class="group rounded-xl border border-gray-200 bg-white p-5 transition hover:border-gray-300 hover:shadow-sm"
        >

            <div class="flex items-start justify-between gap-4">

                <div>

                    <p class="text-sm font-medium text-gray-500">
                        Approved RIS ready for ATP
                    </p>

                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">

                        {{ number_format($risReadyForAtp) }}

                    </p>

                </div>


                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-700">

                    <i
                        data-lucide="file-check-2"
                        class="h-5 w-5"
                    ></i>

                </div>

            </div>


            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">

                <span>
                    Create ATP from approved RIS
                </span>

                <i
                    data-lucide="arrow-right"
                    class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"
                ></i>

            </div>

        </a>

    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">

        <a
            href="{{ route('purchaser.rfc.index', ['create' => 1]) }}"
            class="group rounded-xl border border-gray-200 bg-white p-5 transition hover:border-gray-300 hover:shadow-sm"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">ATP ready for RFC</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($atpReadyForRfc) }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-700">
                    <i data-lucide="file-plus-2" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Create Request for Check from approved ATP</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>

        <a
            href="{{ route('purchaser.rfc.index', ['status' => 'Approved']) }}"
            class="group rounded-xl border border-gray-200 bg-white p-5 transition hover:border-gray-300 hover:shadow-sm"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Funds ready for RR</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($rfcReadyForRr) }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-50 text-sky-700">
                    <i data-lucide="banknote" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Collect funds, then create Receiving Report</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>

        <a
            href="{{ route('purchaser.rr.index', ['status' => 'Completed']) }}"
            class="group rounded-xl border border-gray-200 bg-white p-5 transition hover:border-gray-300 hover:shadow-sm"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">RR ready for liquidation</p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">
                        {{ number_format($rrReadyForLiq) }}
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-50 text-violet-700">
                    <i data-lucide="receipt" class="h-5 w-5"></i>
                </div>
            </div>
            <div class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">
                <span>Create Liquidation from completed Receiving Report</span>
                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>
            </div>
        </a>

    </div>



    {{-- ===================================================== --}}
    {{-- PROCUREMENT WORKFLOW --}}
    {{-- ===================================================== --}}

    <div class="mt-6 rounded-xl border border-gray-200 bg-white">

        <div class="border-b border-gray-100 px-5 py-4">

            <h2 class="text-sm font-semibold text-gray-900">
                Procurement Workflow
            </h2>

            <p class="mt-1 text-xs text-gray-500">
                Current purchasing process for equipment replacement requests.
            </p>

        </div>


        <div class="grid grid-cols-1 divide-y divide-gray-100 md:grid-cols-4 md:divide-x md:divide-y-0">


            {{-- ================================================= --}}
            {{-- STEP 1 --}}
            {{-- ================================================= --}}

            <div class="p-5">

                <div class="flex items-center gap-3">

                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-900 text-xs font-semibold text-white">
                        1
                    </div>

                    <div>

                        <p class="text-sm font-semibold text-gray-900">
                            Request Received
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            Forwarded by maintenance
                        </p>

                    </div>

                </div>

            </div>



            {{-- ================================================= --}}
            {{-- STEP 2 --}}
            {{-- ================================================= --}}

            <div class="p-5">

                <div class="flex items-center gap-3">

                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-600">
                        2
                    </div>

                    <div>

                        <p class="text-sm font-semibold text-gray-900">
                            Review Request
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            Validate replacement details
                        </p>

                    </div>

                </div>

            </div>



            {{-- ================================================= --}}
            {{-- STEP 3 --}}
            {{-- ================================================= --}}

            <div class="p-5">

                <div class="flex items-center gap-3">

                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-600">
                        3
                    </div>

                    <div>

                        <p class="text-sm font-semibold text-gray-900">
                            Purchasing Process
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            Continue procurement documents
                        </p>

                    </div>

                </div>

            </div>



            {{-- ================================================= --}}
            {{-- STEP 4 --}}
            {{-- ================================================= --}}

            <div class="p-5">

                <div class="flex items-center gap-3">

                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-600">
                        4
                    </div>

                    <div>

                        <p class="text-sm font-semibold text-gray-900">
                            Completed
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            Equipment procurement finished
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>



    {{-- ===================================================== --}}
    {{-- CURRENT SYSTEM STATUS --}}
    {{-- ===================================================== --}}

    <div class="mt-6 rounded-xl border border-gray-200 bg-white p-5">

        <div class="flex items-start gap-4">

            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-700">

                <i
                    data-lucide="info"
                    class="h-5 w-5"
                ></i>

            </div>


            <div>

                <h2 class="text-sm font-semibold text-gray-900">
                    Current Implementation
                </h2>

                <p class="mt-1 max-w-3xl text-sm leading-6 text-gray-500">
                    Maintenance personnel can forward equipment replacement requests to the purchaser module. The purchaser can currently view and filter those requests. Approval, rejection, supplier selection, and the remaining purchasing workflow will be connected next.
                </p>

            </div>

        </div>

    </div>

</div>

@endsection