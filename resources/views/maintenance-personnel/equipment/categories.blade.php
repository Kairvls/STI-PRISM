@extends('layouts.maintenance-layout')


@section('content')

<div
    class="min-h-full"
    x-data="equipmentCategoriesPage()"
>

    {{-- ===================================================== --}}
    {{-- PAGE HEADER --}}
    {{-- ===================================================== --}}

    

    <div class="flex justify-end">
        <button
            type="button"
            @click="openCreateModal()"
            class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 font-semibold font-sans-serif text-[13px] text-white transition hover:bg-blue-800"
        >
            <i data-lucide="plus" class="w-4 h-4"></i>

            Add Equipment
        </button>
    </div>



    {{-- ===================================================== --}}
    {{-- CATEGORY DASHBOARD --}}
    {{-- SAME DESIGN AS INVENTORY DASHBOARD --}}
    {{-- ===================================================== --}}

    <div
        class="overflow-hidden rounded-lg mt-6 mb-6 border-y border-slate-300 bg-gray-100 shadow-sm"
    >
        <div
            class="grid grid-cols-1 divide-y divide-slate-200
                md:grid-cols-2 md:divide-y-0
                xl:grid-cols-[380px_1fr_1fr_1fr]"
        >

            {{-- ===================================================== --}}
            {{-- TOTAL CATEGORIES --}}
            {{-- ===================================================== --}}

            <div class="flex items-center justify-between px-8 py-6">

                <div class="flex flex-col">

                    <p class="text-sm font-medium text-slate-500">
                        Total Categories
                    </p>

                    <h2 class="mt-2 text-5xl font-medium text-slate-900">
                        {{ number_format($totalCategories) }}
                    </h2>

                    <p class="mt-3 text-sm">

                        @if($categoryMonthlyPercentage === null)

                            <span class="font-semibold text-emerald-600">
                                New activity
                            </span>

                        @else

                            <span
                                class="font-semibold
                                {{
                                    $categoryMonthlyPercentage > 0
                                        ? 'text-emerald-600'
                                        : (
                                            $categoryMonthlyPercentage < 0
                                                ? 'text-red-600'
                                                : 'text-slate-500'
                                        )
                                }}"
                            >

                                {{
                                    $categoryMonthlyPercentage > 0
                                        ? '+'
                                        : ''
                                }}{{ number_format($categoryMonthlyPercentage,2) }}%

                            </span>

                        @endif

                        <span class="text-slate-500">
                            From last month
                        </span>

                    </p>

                </div>

                {{-- GRAPH --}}
                {{-- Reuse your Inventory graph here --}}
                {{-- Replace equipmentMonthlyTrend with categoryMonthlyTrend --}}
                {{-- Replace equipmentTrendPoints with categoryTrendPoints --}}
                {{-- Replace equipmentTrendAreaPoints with categoryTrendAreaPoints --}}

                {{-- ===================================================== --}}
                {{-- REAL 12 MONTH CATEGORY REGISTRATION TREND --}}
                {{-- ===================================================== --}}

                @php

                    // =====================================================
                    // GET REAL MONTHLY REGISTRATION COUNTS
                    // =====================================================

                    $categoryTrendCounts =
                        $categoryMonthlyTrend->pluck('count');


                    // =====================================================
                    // GRAPH DIMENSIONS
                    // =====================================================

                    $categoryGraphWidth = 300;

                    $categoryGraphHeight = 100;

                    $categoryGraphTopPadding = 10;

                    $categoryGraphBottomPadding = 10;


                    // =====================================================
                    // HIGHEST MONTHLY REGISTRATION COUNT
                    // =====================================================

                    $maxCategoryTrendCount =
                        max(
                            1,
                            $categoryTrendCounts->max()
                        );


                    // =====================================================
                    // NUMBER OF GRAPH POINTS
                    // =====================================================

                    $categoryTrendPointCount =
                        max(
                            1,
                            $categoryMonthlyTrend->count() - 1
                        );


                    // =====================================================
                    // BUILD LINE GRAPH POINTS
                    // =====================================================

                    $categoryTrendPoints =
                        $categoryMonthlyTrend

                            ->values()

                            ->map(function (
                                $item,
                                $index
                            ) use (
                                $categoryGraphWidth,
                                $categoryGraphHeight,
                                $categoryGraphTopPadding,
                                $categoryGraphBottomPadding,
                                $maxCategoryTrendCount,
                                $categoryTrendPointCount
                            ) {

                                // =========================================
                                // X POSITION
                                // =========================================

                                $x =
                                    (
                                        $index
                                        / $categoryTrendPointCount
                                    )
                                    * $categoryGraphWidth;


                                // =========================================
                                // AVAILABLE GRAPH HEIGHT
                                // =========================================

                                $usableHeight =
                                    $categoryGraphHeight
                                    - $categoryGraphTopPadding
                                    - $categoryGraphBottomPadding;


                                // =========================================
                                // Y POSITION
                                // =========================================

                                $y =
                                    $categoryGraphHeight
                                    - $categoryGraphBottomPadding
                                    - (
                                        (
                                            $item['count']
                                            / $maxCategoryTrendCount
                                        )
                                        * $usableHeight
                                    );


                                return
                                    round($x, 2)
                                    . ','
                                    . round($y, 2);

                            })

                            ->implode(' ');


                    // =====================================================
                    // BUILD AREA GRAPH POINTS
                    // =====================================================

                    $categoryTrendAreaPoints =
                        '0,100 '
                        . $categoryTrendPoints
                        . ' 300,100';

                @endphp

                <div class="ml-6 h-20 w-40 shrink-0">

                    <svg
                        viewBox="0 0 300 100"
                        class="h-full w-full"
                        fill="none"
                    >

                        <polygon
                            points="{{ $categoryTrendAreaPoints }}"
                            fill="currentColor"
                            fill-opacity=".08"
                            class="text-slate-900"
                        />

                        <polyline
                            points="{{ $categoryTrendPoints }}"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="text-slate-900"
                        />

                    </svg>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- CATEGORIES IN USE --}}
            {{-- ===================================================== --}}

            <div class="relative flex flex-col justify-between px-8 py-7">

                <span
                    class="absolute left-0 top-8 hidden h-[68%]
                    border-l border-slate-200 xl:block"
                ></span>

                <p class="text-md font-medium text-slate-600">
                    Categories In Use
                </p>

                <h2 class="text-5xl font-medium text-slate-900">
                    {{ number_format($categoriesInUse) }}
                </h2>

                <p class="text-base">

                    <span class="font-semibold text-emerald-600">

                        {{ number_format($categoriesInUsePercentage,2) }}%

                    </span>

                    <span class="text-slate-500">
                        of all categories
                    </span>

                </p>

            </div>


            {{-- ===================================================== --}}
            {{-- UNUSED CATEGORIES --}}
            {{-- ===================================================== --}}

            <div class="relative flex flex-col justify-between px-8 py-7">

                <span
                    class="absolute left-0 top-8 hidden h-[68%]
                    border-l border-slate-200 xl:block"
                ></span>

                <p class="text-md font-medium text-slate-600">
                    Unused
                </p>

                <h2 class="text-5xl font-medium text-slate-900">
                    {{ number_format($unusedCategories) }}
                </h2>

                <p class="text-base">

                    <span class="font-semibold text-amber-600">

                        {{ number_format($unusedCategoriesPercentage,2) }}%

                    </span>

                    <span class="text-slate-500">
                        of all categories
                    </span>

                </p>

            </div>


            {{-- ===================================================== --}}
            {{-- CATEGORIZED EQUIPMENT --}}
            {{-- ===================================================== --}}

            <div class="relative flex flex-col justify-between px-8 py-7">

                <span
                    class="absolute left-0 top-8 hidden h-[68%]
                    border-l border-slate-200 xl:block"
                ></span>

                <p class="text-md font-medium text-slate-600">
                    Categorized Equipment
                </p>

                <h2 class="text-5xl font-medium text-slate-900">
                    {{ number_format($categorizedEquipment) }}
                </h2>

                <p class="text-base">

                    <span class="font-semibold text-blue-600">

                        {{ number_format($categorizedEquipmentPercentage,2) }}%

                    </span>

                    <span class="text-slate-500">
                        of all equipment
                    </span>

                </p>

            </div>

        </div>
    </div>



    {{-- ===================================================== --}}
    {{-- CATEGORY TABLE CONTAINER --}}
    {{-- ===================================================== --}}

    <div
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
    >


        {{-- ===================================================== --}}
        {{-- TABLE TOOLBAR --}}
        {{-- ===================================================== --}}

        <div
            class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between"
        >

            <div>

                <h2
                    class="text-lg font-semibold text-slate-950"
                >
                    Categories
                </h2>

                <p
                    class="mt-1 text-sm text-slate-500"
                >
                    Manage equipment classification records.
                </p>

            </div>



            {{-- ===================================================== --}}
            {{-- SEARCH FORM --}}
            {{-- ===================================================== --}}

            <form
                method="GET"
                action="/maintenance/equipment/categories"
                class="flex items-center gap-2"
            >

                <div class="relative">

                    <i
                        data-lucide="search"
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    ></i>

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search categories..."
                        class="h-9 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-4 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100 sm:w-72"
                    >

                </div>


                <button
                    type="submit"
                    class="inline-flex h-9 shrink-0
                            items-center justify-center gap-2
                            rounded-lg bg-[#0025cc] px-4
                            text-sm font-semibold text-white
                            transition hover:bg-blue-800"
                >
                        <i
                            data-lucide="search"
                            class="h-4 w-4"
                        ></i>
                    Search
                </button>


                @if(request()->filled('search'))

                    <a
                        href="/maintenance/equipment/categories"
                        class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-950"
                    >
                        Clear
                    </a>

                @endif

            </form>

        </div>



        {{-- ===================================================== --}}
        {{-- TABLE --}}
        {{-- ===================================================== --}}

        <div class="overflow-x-auto">

            <table class="w-full">

                <thead
                    class="border-b border-slate-200 bg-slate-50"
                >

                    <tr>

                        <th
                            class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500"
                        >
                            Category
                        </th>


                        <th
                            class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500"
                        >
                            Equipment Assigned
                        </th>


                        <th
                            class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500"
                        >
                            Usage
                        </th>


                        <th
                            class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider text-slate-500"
                        >
                            Actions
                        </th>

                    </tr>

                </thead>



                <tbody
                    class="divide-y divide-slate-100"
                >

                    @forelse($categories as $category)

                        <tr
                            class="transition hover:bg-slate-50/80"
                        >


                            {{-- ===================================================== --}}
                            {{-- CATEGORY NAME --}}
                            {{-- ===================================================== --}}

                            <td class="px-6 py-4">

                                <div
                                    class="flex items-center gap-3"
                                >

                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-500"
                                    >

                                        <i
                                            data-lucide="tag"
                                            class="h-4 w-4"
                                        ></i>

                                    </div>


                                    <div>

                                        <p
                                            class="font-semibold text-slate-950"
                                        >
                                            {{ $category->equipment_category_name }}
                                        </p>


                                        <p
                                            class="mt-0.5 text-xs text-slate-400"
                                        >
                                            Category ID:
                                            {{ $category->equipment_category_id }}
                                        </p>

                                    </div>

                                </div>

                            </td>



                            {{-- ===================================================== --}}
                            {{-- EQUIPMENT COUNT --}}
                            {{-- ===================================================== --}}

                            <td class="px-6 py-4">

                                <div
                                    class="flex items-center gap-2"
                                >

                                    <span
                                        class="inline-flex min-w-9 items-center justify-center rounded-lg bg-slate-100 px-2.5 py-1.5 text-sm font-semibold text-slate-700"
                                    >
                                        {{ number_format($category->equipment_count) }}
                                    </span>


                                    <span
                                        class="text-sm text-slate-500"
                                    >
                                        {{ $category->equipment_count == 1 ? 'equipment' : 'equipment records' }}
                                    </span>

                                </div>

                            </td>



                            {{-- ===================================================== --}}
                            {{-- USAGE STATUS --}}
                            {{-- ===================================================== --}}

                            <td class="px-6 py-4">

                                @if($category->equipment_count > 0)

                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700"
                                    >

                                        <span
                                            class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                                        ></span>

                                        In Use

                                    </span>

                                @else

                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-semibold text-slate-500"
                                    >

                                        <span
                                            class="h-1.5 w-1.5 rounded-full bg-slate-400"
                                        ></span>

                                        Unused

                                    </span>

                                @endif

                            </td>



                            {{-- ===================================================== --}}
                            {{-- ACTIONS --}}
                            {{-- ===================================================== --}}

                            <td class="px-6 py-4">

                                <div
                                    class="flex items-center justify-end gap-2"
                                >


                                    {{-- ===================================================== --}}
                                    {{-- EDIT BUTTON --}}
                                    {{-- ===================================================== --}}

                                    <button
                                        type="button"

                                        @click='openEditModal(
                                            @json($category->equipment_category_id),
                                            @json($category->equipment_category_name)
                                        )'

                                        data-tooltip="Edit category"

                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-950"
                                    >

                                        <i
                                            data-lucide="pencil"
                                            class="h-4 w-4"
                                        ></i>

                                    </button>



                                    {{-- ===================================================== --}}
                                    {{-- DELETE BUTTON --}}
                                    {{-- ===================================================== --}}

                                    <button
                                        type="button"

                                        @click='confirmDelete(
                                            @json($category->equipment_category_id),
                                            @json($category->equipment_category_name),
                                            @json((int) $category->equipment_count)
                                        )'

                                        data-tooltip="Delete category"

                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                                    >

                                        <i
                                            data-lucide="trash-2"
                                            class="h-4 w-4"
                                        ></i>

                                    </button>


                                    {{-- ===================================================== --}}
                                    {{-- HIDDEN DELETE FORM --}}
                                    {{-- ===================================================== --}}

                                    <form
                                        id="delete-category-form-{{ $category->equipment_category_id }}"
                                        method="POST"
                                        action="/maintenance/equipment/categories/{{ $category->equipment_category_id }}"
                                        class="hidden"
                                    >

                                        @csrf

                                        @method('DELETE')

                                    </form>

                                </div>

                            </td>

                        </tr>


                    @empty


                        {{-- ===================================================== --}}
                        {{-- EMPTY STATE --}}
                        {{-- ===================================================== --}}

                        <tr>

                            <td
                                colspan="4"
                                class="px-6 py-16 text-center"
                            >

                                <div
                                    class="mx-auto flex max-w-sm flex-col items-center"
                                >

                                    <div
                                        class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400"
                                    >

                                        <i
                                            data-lucide="tags"
                                            class="h-6 w-6"
                                        ></i>

                                    </div>


                                    <h3
                                        class="mt-4 text-base font-semibold text-slate-950"
                                    >
                                        No categories found
                                    </h3>


                                    <p
                                        class="mt-1 text-sm leading-6 text-slate-500"
                                    >

                                        @if(request()->filled('search'))

                                            No equipment categories matched your search.

                                        @else

                                            Add your first category to organize equipment records.

                                        @endif

                                    </p>


                                    @if(!request()->filled('search'))

                                        <button
                                            type="button"
                                            @click="openCreateModal()"
                                            class="mt-5 inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-slate-950 px-4 text-sm font-semibold text-white transition hover:bg-slate-800"
                                        >

                                            <i
                                                data-lucide="plus"
                                                class="h-4 w-4"
                                            ></i>

                                            Add Category

                                        </button>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>



        {{-- ===================================================== --}}
        {{-- PAGINATION --}}
        {{-- ===================================================== --}}

        @if($categories->hasPages())

            <div
                class="border-t border-slate-200 px-6 py-4"
            >

                {{ $categories->links() }}

            </div>

        @endif

    </div>



    {{-- ===================================================== --}}
    {{-- CREATE CATEGORY MODAL --}}
    {{-- ===================================================== --}}

    <div
        x-show="createModalOpen"
        x-cloak
        @keydown.escape.window="closeCreateModal()"

        class="fixed inset-0 z-50 flex items-center justify-center bg-[#0b1220]/70 p-4"
    >

        <div
            @click.self="closeCreateModal()"
            class="absolute inset-0"
        ></div>


        <div
            x-show="createModalOpen"

            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="translate-y-3 scale-[0.98] opacity-0"
            x-transition:enter-end="translate-y-0 scale-100 opacity-100"

            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="translate-y-0 scale-100 opacity-100"
            x-transition:leave-end="translate-y-3 scale-[0.98] opacity-0"

            class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
        >


            {{-- ===================================================== --}}
            {{-- CREATE MODAL HEADER --}}
            {{-- ===================================================== --}}

            <div
                class="flex items-start justify-between border-b border-slate-200 px-6 py-5"
            >

                <div>

                    <h2
                        class="text-lg font-semibold text-slate-950"
                    >
                        Add Category
                    </h2>

                    <p
                        class="mt-1 text-sm text-slate-500"
                    >
                        Create a new category for equipment records.
                    </p>

                </div>


                <button
                    type="button"
                    @click="closeCreateModal()"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                >

                    <i
                        data-lucide="x"
                        class="h-5 w-5"
                    ></i>

                </button>

            </div>



            {{-- ===================================================== --}}
            {{-- CREATE FORM --}}
            {{-- ===================================================== --}}

            <form
                method="POST"
                action="/maintenance/equipment/categories"
                @submit="createSubmitting = true"
            >

                @csrf


                <div class="px-6 py-6">

                    <label
                        for="equipment_category_name"
                        class="mb-2 block text-sm font-semibold text-slate-700"
                    >
                        Category Name
                    </label>


                    <input
                        id="equipment_category_name"
                        name="equipment_category_name"
                        type="text"

                        x-ref="createCategoryInput"

                        value="{{ old('equipment_category_name') }}"

                        placeholder="Example: Computer Equipment"

                        maxlength="255"

                        required

                        class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                    >


                    @error('equipment_category_name')

                        <p
                            class="mt-2 text-sm font-medium text-red-600"
                        >
                            {{ $message }}
                        </p>

                    @enderror

                </div>



                {{-- ===================================================== --}}
                {{-- CREATE MODAL FOOTER --}}
                {{-- ===================================================== --}}

                <div
                    class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4"
                >

                    <button
                        type="button"
                        @click="closeCreateModal()"
                        class="inline-flex h-10 items-center justify-center px-2 text-sm font-medium text-slate-600 transition hover:text-slate-950"
                    >
                        Cancel
                    </button>


                    <button
                        type="submit"
                        :disabled="createSubmitting"
                        class="inline-flex h-10 min-w-32 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60"
                    >

                        <svg
                            x-show="createSubmitting"
                            class="h-4 w-4 animate-spin"
                            viewBox="0 0 24 24"
                            fill="none"
                        >

                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>

                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                            ></path>

                        </svg>


                        <span
                            x-text="createSubmitting ? 'Adding...' : 'Add Category'"
                        ></span>

                    </button>

                </div>

            </form>

        </div>

    </div>



    {{-- ===================================================== --}}
    {{-- EDIT CATEGORY MODAL --}}
    {{-- ===================================================== --}}

    <div
        x-show="editModalOpen"
        x-cloak
        @keydown.escape.window="closeEditModal()"

        class="fixed inset-0 z-50 flex items-center justify-center bg-[#0b1220]/70 p-4"
    >

        <div
            @click.self="closeEditModal()"
            class="absolute inset-0"
        ></div>


        <div
            x-show="editModalOpen"

            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="translate-y-3 scale-[0.98] opacity-0"
            x-transition:enter-end="translate-y-0 scale-100 opacity-100"

            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="translate-y-0 scale-100 opacity-100"
            x-transition:leave-end="translate-y-3 scale-[0.98] opacity-0"

            class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
        >


            {{-- ===================================================== --}}
            {{-- EDIT MODAL HEADER --}}
            {{-- ===================================================== --}}

            <div
                class="flex items-start justify-between border-b border-slate-200 px-6 py-5"
            >

                <div>

                    <h2
                        class="text-lg font-semibold text-slate-950"
                    >
                        Edit Category
                    </h2>

                    <p
                        class="mt-1 text-sm text-slate-500"
                    >
                        Update the selected equipment category.
                    </p>

                </div>


                <button
                    type="button"
                    @click="closeEditModal()"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                >

                    <i
                        data-lucide="x"
                        class="h-5 w-5"
                    ></i>

                </button>

            </div>



            {{-- ===================================================== --}}
            {{-- EDIT FORM --}}
            {{-- ===================================================== --}}

            <form
                method="POST"
                :action="editFormAction"
                @submit="editSubmitting = true"
            >

                @csrf

                @method('PUT')


                <div class="px-6 py-6">

                    <label
                        for="edit_equipment_category_name"
                        class="mb-2 block text-sm font-semibold text-slate-700"
                    >
                        Category Name
                    </label>


                    <input
                        id="edit_equipment_category_name"
                        name="equipment_category_name"
                        type="text"

                        x-model="editCategoryName"

                        x-ref="editCategoryInput"

                        placeholder="Category name"

                        maxlength="255"

                        required

                        class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                    >

                </div>



                {{-- ===================================================== --}}
                {{-- EDIT MODAL FOOTER --}}
                {{-- ===================================================== --}}

                <div
                    class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4"
                >

                    <button
                        type="button"
                        @click="closeEditModal()"
                        class="inline-flex h-10 items-center justify-center px-2 text-sm font-medium text-slate-600 transition hover:text-slate-950"
                    >
                        Cancel
                    </button>


                    <button
                        type="submit"
                        :disabled="editSubmitting"
                        class="inline-flex h-10 min-w-32 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60"
                    >

                        <svg
                            x-show="editSubmitting"
                            class="h-4 w-4 animate-spin"
                            viewBox="0 0 24 24"
                            fill="none"
                        >

                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>

                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                            ></path>

                        </svg>


                        <span
                            x-text="editSubmitting ? 'Saving...' : 'Save Changes'"
                        ></span>

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


{{-- ===================================================== --}}
{{-- CATEGORY PAGE SCRIPT --}}
{{-- ===================================================== --}}

<script>

    document.addEventListener('alpine:init', () => {

        Alpine.data('equipmentCategoriesPage', () => ({

            // =====================================================
            // CREATE MODAL STATE
            // =====================================================

            createModalOpen: false,

            createSubmitting: false,


            // =====================================================
            // EDIT MODAL STATE
            // =====================================================

            editModalOpen: false,

            editSubmitting: false,

            editCategoryId: null,

            editCategoryName: '',

            editFormAction: '',


            // =====================================================
            // OPEN CREATE MODAL
            // =====================================================

            openCreateModal() {

                this.createSubmitting = false;

                this.createModalOpen = true;


                this.$nextTick(() => {

                    this.$refs
                        .createCategoryInput
                        ?.focus();

                });

            },


            // =====================================================
            // CLOSE CREATE MODAL
            // =====================================================

            closeCreateModal() {

                if (this.createSubmitting) {
                    return;
                }


                this.createModalOpen = false;

            },


            // =====================================================
            // OPEN EDIT MODAL
            // =====================================================

            openEditModal(
                categoryId,
                categoryName
            ) {

                this.editSubmitting = false;

                this.editCategoryId =
                    categoryId;

                this.editCategoryName =
                    categoryName;

                this.editFormAction =
                    `/maintenance/equipment/categories/${categoryId}`;

                this.editModalOpen = true;


                this.$nextTick(() => {

                    this.$refs
                        .editCategoryInput
                        ?.focus();

                });

            },


            // =====================================================
            // CLOSE EDIT MODAL
            // =====================================================

            closeEditModal() {

                if (this.editSubmitting) {
                    return;
                }


                this.editModalOpen = false;

                this.editCategoryId = null;

                this.editCategoryName = '';

                this.editFormAction = '';

            },


            // =====================================================
            // CONFIRM DELETE CATEGORY
            // =====================================================

            confirmDelete(
                categoryId,
                categoryName,
                equipmentCount
            ) {

                // =====================================================
                // CATEGORY IS STILL IN USE
                // =====================================================

                if (equipmentCount > 0) {

                    Swal.fire({

                        icon: 'warning',

                        title: 'Category In Use',

                        text:
                            `"${categoryName}" cannot be deleted because ` +
                            `${equipmentCount} equipment record` +
                            `${equipmentCount === 1 ? ' is' : 's are'} still assigned to it.`,

                        confirmButtonText:
                            'Understood',

                        background:
                            '#ffffff',

                        color:
                            '#0f172a',

                        confirmButtonColor:
                            '#0f172a',

                    });


                    return;

                }


                // =====================================================
                // CATEGORY CAN BE DELETED
                // =====================================================

                Swal.fire({

                    icon:
                        'warning',

                    title:
                        'Delete Category?',

                    html:
                        `You are about to delete <strong>${this.escapeHtml(categoryName)}</strong>. ` +
                        `This action cannot be undone.`,

                    showCancelButton:
                        true,

                    confirmButtonText:
                        'Delete Category',

                    cancelButtonText:
                        'Cancel',

                    reverseButtons:
                        true,

                    focusCancel:
                        true,

                    background:
                        '#ffffff',

                    color:
                        '#0f172a',

                    confirmButtonColor:
                        '#dc2626',

                    cancelButtonColor:
                        '#64748b',

                }).then((result) => {

                    if (!result.isConfirmed) {
                        return;
                    }


                    const form =
                        document.getElementById(
                            `delete-category-form-${categoryId}`
                        );


                    if (form) {

                        form.submit();

                    }

                });

            },


            // =====================================================
            // ESCAPE HTML FOR SWEETALERT CONTENT
            // =====================================================

            escapeHtml(value) {

                const element =
                    document.createElement('div');


                element.textContent =
                    value ?? '';


                return element.innerHTML;

            },

        }));

    });


    // =====================================================
    // REFRESH LUCIDE ICONS
    // =====================================================

    document.addEventListener(
        'DOMContentLoaded',
        () => {

            if (window.lucide) {

                lucide.createIcons();

            }

        }
    );

</script>



{{-- ===================================================== --}}
{{-- VALIDATION ERROR --}}
{{-- REOPEN CREATE MODAL --}}
{{-- ===================================================== --}}

@if($errors->has('equipment_category_name'))

<script>

    document.addEventListener(
        'alpine:initialized',
        () => {

            const categoryPage =
                Alpine.$data(
                    document.querySelector(
                        '[x-data="equipmentCategoriesPage()"]'
                    )
                );


            if (categoryPage) {

                categoryPage.openCreateModal();

            }

        }
    );

</script>

@endif



{{-- Session flash toasts are handled globally by maintenance-layout (showMpToast) --}}

@if(session('error'))

<script>

    document.addEventListener(
        'DOMContentLoaded',
        () => {

            // Keep confirm dialog only for hard errors that need acknowledgment
            if (typeof window.showMpToast === 'function') {
                return;
            }

            Swal.fire({

                icon:
                    'error',

                title:
                    'Unable to Complete Action',

                text:
                    @json(session('error')),

                confirmButtonText:
                    'Close',

                background:
                    '#ffffff',

                color:
                    '#0f172a',

                confirmButtonColor:
                    '#0f172a',

            });

        }
    );

</script>

@endif


<style>

    /* ===================================================== */
    /* PREVENT ALPINE CONTENT FLASH */
    /* ===================================================== */

    [x-cloak] {
        display: none !important;
    }

</style>

@endsection