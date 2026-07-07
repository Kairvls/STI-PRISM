@extends ("layouts.maintenance-layout")

@section ("content")

    <div
        class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
    >
        <div>
            <h1 class="text-4xl font-black tracking-tight text-slate-950">
                Reporter
            </h1>
            <p class="mt-1 text-slate-500">Manage directory records and system contact profiles</p>
        </div>

        <button
            type="button"
            onclick="openCreateModal()"
            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-950 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-slate-900/10 transition hover:-translate-y-0.5 hover:bg-slate-800"
        >
            <i data-lucide="plus" class="h-4 w-4"></i>
            Add Reporter
        </button>
    </div>

    <div
        class="overflow-hidden rounded-lg mt-6 mb-6 border-t border-b border-slate-300 bg-gray-100 shadow-sm"
    >
        <div
            class="grid grid-cols-1 divide-y divide-slate-200 md:grid-cols-2 md:divide-y-0 xl:grid-cols-[380px_1fr_1fr_1fr] "
        >
            <!-- Total Equipment -->
            <div class="flex items-center justify-between px-8 py-6">

                <!-- Left Content -->
                <div class="flex flex-col">
                    <p class="text-sm font-medium text-slate-500">
                        Total Reporters
                    </p>

                    <h2 class="mt-2 text-5xl font-medium text-slate-900">
                        {{ $reporters->count() }}
                    </h2>

                    <p class="mt-3 text-sm">
                        <span class="font-semibold text-emerald-500">
                            +12.45%
                        </span>

                        <span class="text-slate-500">
                            From last month
                        </span>
                    </p>
                </div>

                <!-- Right Graph -->
                <div class="ml-6 h-20 w-40 shrink-0">
                    <svg
                        viewBox="0 0 300 100"
                        class="h-full w-full"
                        fill="none"
                    >
                        <path
                            d="M0 62
                            L35 28
                            L62 58
                            L82 52
                            L112 82
                            L162 82
                            L200 42
                            L232 64
                            L270 64
                            L300 18"
                            stroke="#3b82f6"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />

                        <path
                            d="M0 62
                            L35 28
                            L62 58
                            L82 52
                            L112 82
                            L162 82
                            L200 42
                            L232 64
                            L270 64
                            L300 18
                            L300 100
                            L0 100 Z"
                            fill="#3b82f6"
                            fill-opacity=".08"
                        />
                    </svg>
                </div>

            </div>

            <!-- Active -->
            <div class="relative flex flex-col justify-between px-8 py-7">

                <span
                    class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"
                ></span>

                <p class="text-md font-medium text-slate-600">
                    With Email
                </p>

                <h2 class="text-5xl font-medium text-slate-900">
                    {{
                        $reporters
                            ->whereNotNull("reporter_email_address")
                            ->count()
                    }}
                </h2>

                <p class="text-base">
                    <span class="font-semibold text-emerald-500">
                        +8.32%
                    </span>

                    <span class="text-slate-500">
                        From last month
                    </span>
                </p>
            </div>

            <!-- Under Maintenance -->
            <div class="relative flex flex-col justify-between px-8 py-7">

                <span
                    class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"
                ></span>

                <p class="text-md font-medium text-slate-600">
                    With Contact
                </p>

                <h2 class="text-5xl font-medium text-slate-900">
                    {{
                        $reporters
                            ->whereNotNull("reporter_contact_number")
                            ->count()
                    }}
                </h2>

                <p class="text-base">
                    <span class="font-semibold text-red-500">
                        -4.67%
                    </span>

                    <span class="text-slate-500">
                        From last month
                    </span>
                </p>
            </div>

            <!-- Disposed -->
            <div class="relative flex flex-col justify-between px-8 py-7">

                <span
                    class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"
                ></span>

                <p class="text-md font-medium text-slate-600">
                    Registered
                </p>

                <h2 class="text-5xl font-medium text-slate-900">
                    {{ $reporters->count() }}
                </h2>

                <p class="text-base">
                    <span class="font-semibold text-emerald-500">
                        +2.15%
                    </span>

                    <span class="text-slate-500">
                        From last month
                    </span>
                </p>
            </div>
        </div>
    </div>

        <!--<div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-4">
            <div
                class="rounded-2xl border border-blue-100 bg-blue-50/60 p-5 transition hover:shadow-md hover:shadow-blue-50"
            >
                <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-blue-600">Total Reporters</p>
                <h2 class="text-3xl font-black text-blue-900">
                    {{ $reporters->count() }}
                </h2>
            </div>

            <div
                class="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-5 transition hover:shadow-md hover:shadow-emerald-50"
            >
                <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-emerald-600">With Email</p>
                <h2 class="text-3xl font-black text-emerald-900">
                    {{
                        $reporters
                            ->whereNotNull("reporter_email_address")
                            ->count()
                    }}
                </h2>
            </div>

            <div
                class="rounded-2xl border border-purple-100 bg-purple-50/60 p-5 transition hover:shadow-md hover:shadow-purple-50"
            >
                <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-purple-600">With Contact</p>
                <h2 class="text-3xl font-black text-purple-900">
                    {{
                        $reporters
                            ->whereNotNull("reporter_contact_number")
                            ->count()
                    }}
                </h2>
            </div>

            <div
                class="rounded-2xl border border-amber-100 bg-amber-50/60 p-5 transition hover:shadow-md hover:shadow-amber-50"
            >
                <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-amber-600">Registered</p>
                <h2 class="text-3xl font-black text-amber-900">
                    {{ $reporters->count() }}
                </h2>
            </div>
        </div>-->

    <div class="rounded-3xl border border-slate-100 bg-white shadow-sm">
        @if (session("success"))
            <div
                class="mb-6 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
            >
                <svg class="h-5 w-5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{
                    session(
                        "success",
                    )
                }}
            </div>
        @endif

        

        <!--<div
            class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    Reporters
                </h1>
                <p class="text-sm text-slate-500">Manage directory records and system contact profiles</p>
            </div>
            <button
                onclick="openCreateModal()"
                class="flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-medium text-white shadow-sm shadow-blue-200 transition hover:bg-blue-700 active:bg-blue-800"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add Reporter
            </button>
        </div>-->

        {{-- ========================================================= --}}
        {{-- REPORTER DIRECTORY --}}
        {{-- ========================================================= --}}

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">

            {{-- ===================================================== --}}
            {{-- HEADER --}}
            {{-- ===================================================== --}}

            <div
                class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4
                    lg:flex-row lg:items-center lg:justify-between"
            >

                {{-- TITLE --}}
                <div class="flex items-center gap-3">

                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center
                            rounded-lg bg-slate-100 text-slate-600"
                    >
                        <i data-lucide="users" class="h-4 w-4"></i>
                    </div>

                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">
                            Reporter Directory
                        </h2>

                        <p class="mt-0.5 text-xs text-slate-400">
                            {{ $reporters->count() }}
                            {{
                                $reporters->count() === 1
                                    ? "registered reporter"
                                    : "registered reporters"
                            }}
                        </p>
                    </div>

                </div>


                {{-- SEARCH + STATUS FILTER --}}
                <div
                    class="flex w-full flex-col gap-2
                        sm:flex-row lg:w-auto lg:items-center"
                >

                    {{-- SEARCH --}}
                    <div class="relative w-full sm:w-[280px]">

                        <i
                            data-lucide="search"
                            class="pointer-events-none absolute left-3
                                top-1/2 h-4 w-4 -translate-y-1/2
                                text-slate-400"
                        ></i>

                        <input
                            type="search"
                            id="searchInput"
                            placeholder="Search reporters..."
                            class="h-9 w-full rounded-lg border border-slate-200
                                bg-white pl-9 pr-3 text-xs font-medium
                                text-slate-700 outline-none transition
                                placeholder:text-slate-400
                                focus:border-slate-400"
                        >

                    </div>


                    {{-- STATUS FILTER --}}
                    <div class="relative">

                        <i
                            data-lucide="list-filter"
                            class="pointer-events-none absolute left-3
                                top-1/2 h-3.5 w-3.5 -translate-y-1/2
                                text-slate-400"
                        ></i>

                        <select
                            id="statusFilter"
                            class="h-9 w-full appearance-none rounded-lg
                                border border-slate-200 bg-white
                                pl-9 pr-9 text-xs font-medium
                                text-slate-600 outline-none transition
                                focus:border-slate-400 sm:w-[150px]"
                        >
                            <option value="all">
                                All statuses
                            </option>

                            <option value="active">
                                Active
                            </option>

                            <option value="inactive">
                                Inactive
                            </option>

                        </select>


                        <i
                            data-lucide="chevron-down"
                            class="pointer-events-none absolute right-3
                                top-1/2 h-3.5 w-3.5 -translate-y-1/2
                                text-slate-400"
                        ></i>

                    </div>

                </div>

            </div>



            {{-- ===================================================== --}}
            {{-- TABLE --}}
            {{-- ===================================================== --}}

            <div class="overflow-x-auto">

                <table class="w-full min-w-[950px] text-left">

                    {{-- ================================================= --}}
                    {{-- TABLE HEADER --}}
                    {{-- ================================================= --}}

                    <thead class="border-b border-slate-200 bg-slate-50/70">

                        <tr
                            class="text-[10px] font-semibold uppercase
                                tracking-[0.08em] text-slate-400"
                        >
                            <th class="px-5 py-3">
                                Employee
                            </th>

                            <th class="px-5 py-3">
                                Reporter
                            </th>

                            <th class="px-5 py-3">
                                Email Address
                            </th>

                            <th class="px-5 py-3">
                                Contact
                            </th>

                            <th class="px-5 py-3">
                                Status
                            </th>

                            <th class="w-16 px-5 py-3 text-right">
                                Actions
                            </th>
                        </tr>

                    </thead>



                    {{-- ================================================= --}}
                    {{-- TABLE BODY --}}
                    {{-- ================================================= --}}

                    <tbody
                        id="reporterTable"
                        class="divide-y divide-slate-100"
                    >

                        @forelse ($reporters as $reporter)

                            @php
                                // CHANGE THIS IF YOUR DATABASE COLUMN
                                // USES A DIFFERENT STATUS NAME

                                $reporterStatus =
                                    $reporter->reporter_status ?? "Active";


                                $reporterStatusClass =
                                    strtolower($reporterStatus) === "active"
                                        ? "bg-emerald-50 text-emerald-700 ring-emerald-200"
                                        : "bg-slate-100 text-slate-600 ring-slate-200";


                                $reporterStatusDotClass =
                                    strtolower($reporterStatus) === "active"
                                        ? "bg-emerald-500"
                                        : "bg-slate-400";
                            @endphp


                            <tr
                                class="reporter-row group transition-colors
                                    hover:bg-slate-50/70"

                                data-status="{{ strtolower($reporterStatus) }}"
                            >

                                {{-- ===================================== --}}
                                {{-- EMPLOYEE ID --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <span
                                        class="font-mono text-xs font-medium
                                            tracking-wide text-slate-500"
                                    >
                                        {{ $reporter->reporter_employee_id }}
                                    </span>

                                </td>



                                {{-- ===================================== --}}
                                {{-- REPORTER --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <div class="flex items-center gap-3">

                                        {{-- AVATAR --}}
                                        <div
                                            class="flex h-9 w-9 shrink-0
                                                items-center justify-center
                                                rounded-full bg-slate-100
                                                text-xs font-semibold
                                                text-slate-600"
                                        >
                                            {{
                                                strtoupper(
                                                    substr(
                                                        $reporter->reporter_full_name,
                                                        0,
                                                        1
                                                    )
                                                )
                                            }}
                                        </div>


                                        <div class="min-w-0">

                                            <p
                                                class="max-w-[220px] truncate
                                                    text-sm font-semibold
                                                    text-slate-800"
                                            >
                                                {{ $reporter->reporter_full_name }}
                                            </p>


                                            <p
                                                class="mt-0.5 text-[11px]
                                                    text-slate-400"
                                            >
                                                Reporter account
                                            </p>

                                        </div>

                                    </div>

                                </td>



                                {{-- ===================================== --}}
                                {{-- EMAIL --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <div class="flex items-center gap-2">

                                        <i
                                            data-lucide="mail"
                                            class="h-3.5 w-3.5 shrink-0
                                                text-slate-400"
                                        ></i>

                                        <span
                                            class="max-w-[240px] truncate
                                                text-xs text-slate-600"
                                        >
                                            {{
                                                $reporter->reporter_email_address
                                                    ?? "No email provided"
                                            }}
                                        </span>

                                    </div>

                                </td>



                                {{-- ===================================== --}}
                                {{-- CONTACT --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <div class="flex items-center gap-2">

                                        <i
                                            data-lucide="phone"
                                            class="h-3.5 w-3.5 shrink-0
                                                text-slate-400"
                                        ></i>

                                        <span
                                            class="whitespace-nowrap
                                                text-xs text-slate-600"
                                        >
                                            {{
                                                $reporter->reporter_contact_number
                                                    ?? "No contact provided"
                                            }}
                                        </span>

                                    </div>

                                </td>



                                {{-- ===================================== --}}
                                {{-- STATUS --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <span
                                        class="inline-flex items-center gap-1.5
                                            rounded-full px-2.5 py-1
                                            text-[11px] font-medium
                                            ring-1 ring-inset
                                            {{ $reporterStatusClass }}"
                                    >

                                        <span
                                            class="h-1.5 w-1.5 rounded-full
                                                {{ $reporterStatusDotClass }}"
                                        ></span>

                                        {{ $reporterStatus }}

                                    </span>

                                </td>



                                {{-- ===================================== --}}
                                {{-- ACTIONS --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <div class="flex items-center justify-center gap-2">

                                        {{-- ================================= --}}
                                        {{-- VIEW BUTTON --}}
                                        {{-- ================================= --}}

                                        <button
                                            type="button"

                                            onclick="viewReporter(
                                                '{{ $reporter->reporter_employee_id }}',
                                                '{{ $reporter->reporter_full_name }}',
                                                '{{ $reporter->reporter_email_address }}',
                                                '{{ $reporter->reporter_contact_number }}'
                                            )"

                                            class="flex h-9 w-9 shrink-0 items-center
                                                justify-center rounded-xl
                                                bg-slate-100 text-slate-600
                                                transition
                                                hover:bg-slate-200
                                                hover:text-slate-900
                                                active:scale-95"

                                            title="View reporter details"

                                            aria-label="View reporter details"
                                        >
                                            <i
                                                data-lucide="eye"
                                                class="h-3.5 w-3.5"
                                            ></i>
                                        </button>


                                        {{-- ================================= --}}
                                        {{-- EDIT BUTTON --}}
                                        {{-- ================================= --}}

                                        <button
                                            type="button"

                                            onclick="editReporter(
                                                '{{ $reporter->reporter_id }}',
                                                '{{ $reporter->reporter_employee_id }}',
                                                '{{ $reporter->reporter_full_name }}',
                                                '{{ $reporter->reporter_email_address }}',
                                                '{{ $reporter->reporter_contact_number }}'
                                            )"

                                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-[rgba(0,55,199,0.85)] text-white transition hover:bg-[rgba(0,44,155,0.85)]"

                                            title="Edit reporter"

                                            aria-label="Edit reporter"
                                        >
                                            <i
                                                data-lucide="edit-3"
                                                class="h-3.5 w-3.5"
                                            ></i>
                                        </button>


                                        {{-- ================================= --}}
                                        {{-- DELETE BUTTON --}}
                                        {{-- ================================= --}}

                                        <button
                                            type="button"

                                            onclick='openDeleteModal(
                                                @js($reporter->reporter_id)
                                            )'

                                            class="flex h-9 w-9 items-center
                                                justify-center rounded-xl
                                                bg-red-600 text-white
                                                shadow-sm transition
                                                hover:bg-red-700
                                                active:scale-95"

                                            title="Delete reporter"

                                            aria-label="Delete reporter"
                                        >
                                            <i
                                                data-lucide="trash-2"
                                                class="h-3.5 w-3.5"
                                            ></i>
                                        </button>

                                    </div>

                                </td>

                            </tr>


                        @empty

                            {{-- ========================================= --}}
                            {{-- DATABASE EMPTY STATE --}}
                            {{-- ========================================= --}}

                            <tr>

                                <td
                                    colspan="6"
                                    class="px-5 py-16 text-center"
                                >

                                    <div class="mx-auto max-w-xs">

                                        <div
                                            class="mx-auto flex h-11 w-11
                                                items-center justify-center
                                                rounded-xl bg-slate-100
                                                text-slate-400"
                                        >
                                            <i
                                                data-lucide="users"
                                                class="h-5 w-5"
                                            ></i>
                                        </div>


                                        <h3
                                            class="mt-3 text-sm font-semibold
                                                text-slate-700"
                                        >
                                            No reporters found
                                        </h3>


                                        <p
                                            class="mt-1 text-xs leading-5
                                                text-slate-400"
                                        >
                                            Reporter accounts will appear here
                                            after they are added.
                                        </p>

                                    </div>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- ===================================================== --}}
            {{-- FILTER EMPTY STATE --}}
            {{-- ===================================================== --}}

            <div
                id="reporterFilterEmptyState"
                class="hidden px-5 py-16 text-center"
            >

                <div class="mx-auto max-w-xs">

                    <div
                        class="mx-auto flex h-11 w-11 items-center
                            justify-center rounded-xl bg-slate-100
                            text-slate-400"
                    >
                        <i
                            data-lucide="search-x"
                            class="h-5 w-5"
                        ></i>
                    </div>


                    <h3
                        class="mt-3 text-sm font-semibold text-slate-700"
                    >
                        No matching reporters
                    </h3>


                    <p class="mt-1 text-xs text-slate-400">
                        Try changing your search or status filter.
                    </p>

                </div>

            </div>

        </section>
    </div>

    <div
        id="createModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/30 p-4 backdrop-blur-[2px]"
    >
        <form
            action="/maintenance/reporters/store"
            method="POST"
            class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-950/10"
        >
            @csrf

            {{-- ===================================== --}}
            {{-- MODAL HEADER --}}
            {{-- ===================================== --}}

            <div class="flex items-start justify-between border-b border-dashed border-slate-500 px-6 py-5">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">
                        Add New Reporter
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Enter the reporter's information below.
                    </p>
                </div>

                <button
                    type="button"
                    onclick="closeCreateModal()"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close modal"
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18 18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>


            {{-- ===================================== --}}
            {{-- FORM CONTENT --}}
            {{-- ===================================== --}}

            <div class="space-y-5 px-6 py-6">

                {{-- EMPLOYEE ID --}}

                <div>
                    <label
                        for="employee_id"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Employee ID
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="employee_id"
                        name="employee_id"
                        type="text"
                        placeholder="OMC****F"
                        required
                        class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                    />
                </div>


                {{-- FULL NAME --}}

                <div>
                    <label
                        for="full_name"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Full Name
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="full_name"
                        name="full_name"
                        type="text"
                        placeholder="Joseph Diaz"
                        required
                        class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                    />
                </div>


                {{-- EMAIL ADDRESS --}}

                <div>
                    <label
                        for="email"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Email Address
                        <span class="ml-1 text-xs font-normal text-slate-400">
                            Optional
                        </span>
                    </label>

                    <input
                        id="email"
                        name="email"
                        type="email"
                        placeholder="joseph.diaz@sti.edu.ph"
                        class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                    />
                </div>


                {{-- CONTACT NUMBER --}}

                <div>
                    <label
                        for="contact"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Contact Number
                        <span class="ml-1 text-xs font-normal text-slate-400">
                            Optional
                        </span>
                    </label>

                    <input
                        id="contact"
                        name="contact"
                        type="text"
                        placeholder="09103102012"
                        maxlength="11"
                        pattern="[0-9]*"
                        inputmode="numeric"
                        class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                    />
                </div>

            </div>


            {{-- ===================================== --}}
            {{-- MODAL FOOTER --}}
            {{-- ===================================== --}}

            <div class="flex items-center justify-end gap-2 border-t border-dashed border-slate-500 bg-slate-50/60 px-6 py-4">

                <button
                    type="button"
                    onclick="closeCreateModal()"
                    class="h-10 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50 active:bg-slate-100"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="h-10 rounded-lg bg-slate-800 px-5 text-sm font-medium text-white shadow-sm transition hover:bg-slate-700 active:bg-slate-800"
                >
                    Add Reporter
                </button>

            </div>

        </form>
    </div>

    <div
        id="viewModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]"
    >
        <!-- ===================================== -->
        <!-- VIEW REPORTER MODAL -->
        <!-- ===================================== -->
        <div
            class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
        >
            <!-- ===================================== -->
            <!-- MODAL HEADER -->
            <!-- ===================================== -->
            <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400">
                        Reporter Profile
                    </p>

                    <h2 class="mt-1.5 text-lg font-semibold tracking-tight text-slate-950">
                        Profile details
                    </h2>
                </div>

                <!-- CLOSE BUTTON -->
                <button
                    type="button"
                    onclick="closeViewModal()"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                    aria-label="Close modal"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <!-- ===================================== -->
            <!-- REPORTER DETAILS -->
            <!-- ===================================== -->
            <div class="border-y border-slate-100 px-6 py-2">
                <div
                    id="reporterDetails"
                    class="divide-y divide-slate-100 text-sm"
                ></div>
            </div>

            <!-- ===================================== -->
            <!-- MODAL FOOTER -->
            <!-- ===================================== -->
            <div class="flex justify-end px-6 py-4">
                <button
                    type="button"
                    onclick="closeViewModal()"
                    class="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                >
                    Close
                </button>
            </div>
        </div>
    </div>

    <div
        id="editModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]"
    >
        <!-- ===================================== -->
        <!-- EDIT REPORTER MODAL -->
        <!-- ===================================== -->
        <form
            action="/maintenance/reporters/update"
            method="POST"
            class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
        >
            @csrf

            <input
                type="hidden"
                name="reporter_id"
                id="editReporterId"
            />

            <!-- ===================================== -->
            <!-- MODAL HEADER -->
            <!-- ===================================== -->
            <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400">
                        Reporter Profile
                    </p>

                    <h2 class="mt-1.5 text-lg font-semibold tracking-tight text-slate-950">
                        Edit profile
                    </h2>
                </div>

                <!-- CLOSE BUTTON -->
                <button
                    type="button"
                    onclick="closeEditModal()"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                    aria-label="Close modal"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <!-- ===================================== -->
            <!-- FORM CONTENT -->
            <!-- ===================================== -->
            <div class="border-y border-slate-100 px-6 py-5">
                <div class="space-y-5">

                    <!-- ===================================== -->
                    <!-- EMPLOYEE ID -->
                    <!-- ===================================== -->
                    <div>
                        <label
                            for="editEmployeeId"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Employee ID
                        </label>

                        <input
                            type="text"
                            name="employee_id"
                            id="editEmployeeId"
                            placeholder="Enter employee ID"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                            required
                        />
                    </div>

                    <!-- ===================================== -->
                    <!-- FULL NAME -->
                    <!-- ===================================== -->
                    <div>
                        <label
                            for="editFullName"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Full name
                        </label>

                        <input
                            type="text"
                            name="full_name"
                            id="editFullName"
                            placeholder="Enter full name"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                            required
                        />
                    </div>

                    <!-- ===================================== -->
                    <!-- EMAIL ADDRESS -->
                    <!-- ===================================== -->
                    <div>
                        <label
                            for="editEmail"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Email address
                        </label>

                        <input
                            type="email"
                            name="email"
                            id="editEmail"
                            placeholder="Enter email address"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        />
                    </div>

                    <!-- ===================================== -->
                    <!-- CONTACT NUMBER -->
                    <!-- ===================================== -->
                    <div>
                        <label
                            for="editContact"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Contact number
                        </label>

                        <input
                            type="text"
                            name="contact"
                            id="editContact"
                            placeholder="Enter contact number"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        />
                    </div>
                </div>
            </div>

            <!-- ===================================== -->
            <!-- MODAL FOOTER -->
            <!-- ===================================== -->
            <div class="flex items-center justify-end gap-2 px-6 py-4">
                <button
                    type="button"
                    onclick="closeEditModal()"
                    class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-200 active:bg-black"
                >
                    Save changes
                </button>
            </div>
        </form>
    </div>

    <div
        id="deleteModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]"
    >
        <!-- ===================================== -->
        <!-- DELETE REPORTER MODAL -->
        <!-- ===================================== -->
        <div
            class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
        >
            <!-- ===================================== -->
            <!-- MODAL HEADER -->
            <!-- ===================================== -->
            <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6">
                <div>
                    <!-- DELETE ICON -->
                    <div
                        class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-rose-50 text-rose-600"
                    >
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                    </div>

                    <h2 class="text-lg font-semibold tracking-tight text-slate-950">
                        Delete reporter?
                    </h2>

                    <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">
                        This reporter profile will be permanently deleted. This
                        action cannot be undone.
                    </p>
                </div>

                <!-- CLOSE BUTTON -->
                <button
                    type="button"
                    onclick="closeDeleteModal()"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                    aria-label="Close modal"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <!-- ===================================== -->
            <!-- DELETE FORM -->
            <!-- ===================================== -->
            <form
                action="/maintenance/reporters/delete"
                method="POST"
            >
                @csrf

                <input
                    type="hidden"
                    name="reporter_id"
                    id="deleteReporterId"
                />

                <!-- ===================================== -->
                <!-- MODAL FOOTER -->
                <!-- ===================================== -->
                <div
                    class="flex items-center justify-end gap-2 border-t border-slate-100 px-6 py-4"
                >
                    <button
                        type="button"
                        onclick="closeDeleteModal()"
                        class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-rose-700 focus:outline-none focus:ring-4 focus:ring-rose-100 active:bg-rose-800"
                    >
                        Delete reporter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const createModal = document.getElementById("createModal");
        const viewModal = document.getElementById("viewModal");
        const editModal = document.getElementById("editModal");
        const deleteModal = document.getElementById("deleteModal");

        function openCreateModal() {
            createModal.classList.remove("hidden");
            createModal.classList.add("flex");
        }

        function closeCreateModal() {
            createModal.classList.add("hidden");
            createModal.classList.remove("flex");
        }

        function closeViewModal() {
            viewModal.classList.add("hidden");
            viewModal.classList.remove("flex");
        }

        function closeEditModal() {
            editModal.classList.add("hidden");
            editModal.classList.remove("flex");
        }

        function closeDeleteModal() {
            deleteModal.classList.add("hidden");
            deleteModal.classList.remove("flex");
        }

        function viewReporter(employee, name, email, contact) {
            // =====================================
            // REPORTER DETAILS CONTAINER
            // =====================================
            document.getElementById("reporterDetails").innerHTML = `

                <!-- ===================================== -->
                <!-- EMPLOYEE ID -->
                <!-- ===================================== -->
                <div class="flex items-center justify-between gap-8 py-3.5">
                    <span class="text-sm text-slate-500">
                        Employee ID
                    </span>

                    <span class="rounded-md bg-slate-100 px-2 py-1 font-mono text-xs font-medium text-slate-700">
                        ${employee || "—"}
                    </span>
                </div>

                <!-- ===================================== -->
                <!-- FULL NAME -->
                <!-- ===================================== -->
                <div class="flex items-center justify-between gap-8 py-3.5">
                    <span class="text-sm text-slate-500">
                        Full name
                    </span>

                    <span class="max-w-[65%] text-right text-sm font-medium text-slate-950">
                        ${name || "—"}
                    </span>
                </div>

                <!-- ===================================== -->
                <!-- EMAIL ADDRESS -->
                <!-- ===================================== -->
                <div class="flex items-center justify-between gap-8 py-3.5">
                    <span class="shrink-0 text-sm text-slate-500">
                        Email address
                    </span>

                    <span class="min-w-0 break-all text-right text-sm font-medium text-slate-800">
                        ${email || "—"}
                    </span>
                </div>

                <!-- ===================================== -->
                <!-- CONTACT NUMBER -->
                <!-- ===================================== -->
                <div class="flex items-center justify-between gap-8 py-3.5">
                    <span class="text-sm text-slate-500">
                        Contact number
                    </span>

                    <span class="text-right text-sm font-medium text-slate-800">
                        ${contact || "—"}
                    </span>
                </div>
            `;

            // =====================================
            // OPEN VIEW MODAL
            // =====================================
            viewModal.classList.remove("hidden");
            viewModal.classList.add("flex");
        }

        function editReporter(id, employee, name, email, contact) {
            document.getElementById("editReporterId").value = id;
            document.getElementById("editEmployeeId").value = employee;
            document.getElementById("editFullName").value = name;
            document.getElementById("editEmail").value = email;
            document.getElementById("editContact").value = contact;

            editModal.classList.remove("hidden");
            editModal.classList.add("flex");
        }

        function openDeleteModal(id) {
            document.getElementById("deleteReporterId").value = id;
            deleteModal.classList.remove("hidden");
            deleteModal.classList.add("flex");
        }

        document
            .getElementById("searchInput")
            .addEventListener("keyup", function () {
                let value = this.value.toLowerCase();
                document
                    .querySelectorAll(".reporter-row")
                    .forEach(function (row) {
                        row.style.display = row.innerText
                            .toLowerCase()
                            .includes(value)
                            ? ""
                            : "none";
                    });
            });
    </script>

@endsection
