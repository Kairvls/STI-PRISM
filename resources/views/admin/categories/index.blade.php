@extends('layouts.maintenance-layout')


@section('content')
@php
    $catalogTab = $catalogTab ?? 'classifications';
    $tabLabels = [
        'classifications' => 'Classifications',
        'brands' => 'Brands',
        'models' => 'Models',
    ];
@endphp

<div
    class="min-h-full"
    x-data="equipmentCategoriesPage()"
>

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
        <div class="inline-flex rounded-xl bg-slate-100 p-1 ring-1 ring-slate-200/80">
            @foreach ($tabLabels as $tabKey => $tabLabel)
                <a
                    href="{{ route('admin.equipment-categories.index', ['tab' => $tabKey]) }}"
                    class="inline-flex h-9 items-center rounded-lg px-3.5 text-xs font-semibold transition {{ $catalogTab === $tabKey ? 'bg-[#0025cc] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900' }}"
                >{{ $tabLabel }}</a>
            @endforeach
        </div>

        <div class="flex justify-end">
            @if ($catalogTab === 'classifications')
                <button
                    type="button"
                    @click="openCreateModal()"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white transition hover:bg-blue-800"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Add Classification
                </button>
            @elseif ($catalogTab === 'brands')
                <button
                    type="button"
                    @click="openBrandCreate()"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white transition hover:bg-blue-800"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Add Brand
                </button>
            @else
                <button
                    type="button"
                    @click="openModelCreate()"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white transition hover:bg-blue-800"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Add Model
                </button>
            @endif
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- TABLE CONTAINER --}}
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
                <h2 class="text-lg font-semibold text-slate-950">
                    {{ $tabLabels[$catalogTab] ?? 'Classifications' }}
                </h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    @if ($catalogTab === 'brands')
                        Brand names used when adding equipment to inventory.
                    @elseif ($catalogTab === 'models')
                        Model names used when adding equipment to inventory.
                    @else
                        Classifications and the equipment names inside them (auto-predict).
                    @endif
                </p>
            </div>

            <form
                method="GET"
                action="{{ route('admin.equipment-categories.index') }}"
                class="flex items-center gap-2"
            >
                <input type="hidden" name="tab" value="{{ $catalogTab }}">

                <div class="relative">
                    <i
                        data-lucide="search"
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    ></i>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="{{ $catalogTab === 'brands' ? 'Search brands…' : ($catalogTab === 'models' ? 'Search models…' : 'Search categories…') }}"
                        class="h-9 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-4 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100 sm:w-72"
                    >
                </div>

                <button
                    type="submit"
                    class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800"
                >
                    <i data-lucide="search" class="h-4 w-4"></i>
                    Search
                </button>

                @if(request()->filled('search'))
                    <a
                        href="{{ route('admin.equipment-categories.index', ['tab' => $catalogTab]) }}"
                        class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-950"
                    >
                        Clear
                    </a>
                @endif
            </form>

        </div>

@if ($catalogTab === 'classifications')



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
                            Classification
                        </th>


                        <th
                            class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500"
                        >
                            Equipment in classification
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
                                            Classification ID:
                                            {{ $category->equipment_category_id }}
                                        </p>

                                    </div>

                                </div>

                            </td>



                            {{-- Equipment names maintained under this classification (auto-predict catalog) --}}
                            <td class="px-6 py-4">
                                @php
                                    $typeCount = (int) (($categoryTypeCounts ?? [])[$category->equipment_category_id] ?? 0);
                                @endphp
                                <button
                                    type="button"
                                    @click='openTypesModal(
                                        @json($category->equipment_category_id),
                                        @json($category->equipment_category_name)
                                    )'
                                    class="group inline-flex max-w-full items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-left transition hover:border-slate-300 hover:bg-slate-50"
                                >
                                    <span class="inline-flex min-w-9 items-center justify-center rounded-lg bg-slate-100 px-2.5 py-1 text-sm font-semibold text-slate-700 group-hover:bg-slate-200/80">
                                        {{ number_format($typeCount) }}
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-medium text-slate-800">
                                            {{ $typeCount === 1 ? 'equipment name' : 'equipment names' }}
                                        </span>
                                        <span class="block text-[11px] text-slate-400">Manage list · helps auto-predict</span>
                                    </span>
                                </button>
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

                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001db3]"
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

                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-red-700 transition hover:bg-slate-50"
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
                                        action="{{ route('admin.equipment-categories.destroy', $category->equipment_category_id) }}"
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
                                colspan="5"
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
                                            class="mt-5 inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-[#001fad]"
                                        >

                                            <i
                                                data-lucide="plus"
                                                class="h-4 w-4"
                                            ></i>

                                            Add Classification

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

@elseif ($catalogTab === 'brands')
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Brand name</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse(($catalogBrands ?? collect()) as $brand)
                        <tr class="transition hover:bg-slate-50/80">
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-950">{{ $brand->brand_name }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">Brand ID: {{ $brand->brand_id }}</p>
                            </td>
                            <td class="px-6 py-4">
                                @if (strcasecmp((string) $brand->brand_status, 'Active') === 0)
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-semibold text-slate-500">
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        @click='openBrandEdit(@json($brand->brand_id), @json($brand->brand_name), @json($brand->brand_status))'
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001db3]"
                                        title="Edit brand"
                                    >
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                    </button>
                                    <form method="POST" action="{{ route('admin.equipment-categories.brands.destroy', $brand->brand_id) }}" onsubmit="return confirm('Delete brand {{ addslashes($brand->brand_name) }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-rose-600 text-white transition hover:bg-rose-700" title="Delete brand">
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-16 text-center text-sm text-slate-400">
                                No brands yet. Add one for inventory Brand name pickers.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($catalogBrands && $catalogBrands->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">{{ $catalogBrands->links() }}</div>
        @endif

@else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Model</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse(($catalogModels ?? collect()) as $modelRow)
                        <tr class="transition hover:bg-slate-50/80">
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-950">{{ $modelRow->model_name }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">Model ID: {{ $modelRow->model_id }}</p>
                            </td>
                            <td class="px-6 py-4">
                                @if ($modelRow->model_is_active)
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-semibold text-slate-500">
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        @click='openModelEdit(@json($modelRow->model_id), @json($modelRow->model_name), @json((bool) $modelRow->model_is_active))'
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001db3]"
                                        title="Edit model"
                                    >
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                    </button>
                                    <form method="POST" action="{{ route('admin.equipment-categories.models.destroy', $modelRow->model_id) }}" onsubmit="return confirm('Delete model {{ addslashes($modelRow->model_name) }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-rose-600 text-white transition hover:bg-rose-700" title="Delete model">
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-16 text-center text-sm text-slate-400">
                                No models yet. Add one for inventory Model pickers.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($catalogModels && $catalogModels->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">{{ $catalogModels->links() }}</div>
        @endif
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
                        Add Classification
                    </h2>

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
                action="{{ route('admin.equipment-categories.store') }}"
                @submit="createSubmitting = true"
            >

                @csrf


                <div class="px-6 py-6">

                    <label
                        for="equipment_category_name"
                        class="mb-2 block text-sm font-semibold text-slate-700"
                    >
                        Classification Name <span class="text-red-500">*</span>
                    </label>


                    <input
                        id="equipment_category_name"
                        name="equipment_category_name"
                        type="text"

                        x-ref="createCategoryInput"

                        value="{{ old('equipment_category_name') }}"

                        placeholder="Example: Computer Equipment"

                        maxlength="{{ \App\Support\InputLimits::CATEGORY_NAME }}"

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
                            x-text="createSubmitting ? 'Adding...' : 'Add Classification'"
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
                        Edit Classification
                    </h2>

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
                        Classification Name <span class="text-red-500">*</span>
                    </label>


                    <input
                        id="edit_equipment_category_name"
                        name="equipment_category_name"
                        type="text"

                        x-model="editCategoryName"

                        x-ref="editCategoryInput"

                        placeholder="Classification name"

                        maxlength="{{ \App\Support\InputLimits::CATEGORY_NAME }}"

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

    {{-- Brand create/edit --}}
    <div x-show="brandModalOpen" x-cloak @keydown.escape.window="closeBrandModal()" class="fixed inset-0 z-50 flex items-center justify-center bg-[#0b1220]/70 p-4">
        <div @click.self="closeBrandModal()" class="absolute inset-0"></div>
        <div class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl" @click.stop>
            <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950" x-text="brandEditId ? 'Edit brand' : 'Add brand'"></h2>
                    <p class="mt-1 text-sm text-slate-500">Used in Add to inventory Brand name.</p>
                </div>
                <button type="button" @click="closeBrandModal()" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
            <form method="POST" :action="brandEditId ? (brandUpdateBase + '/' + brandEditId) : brandStoreUrl" class="space-y-4 px-6 py-5">
                @csrf
                <input type="hidden" name="_method" value="PUT" x-bind:disabled="!brandEditId">
                <div>
                    <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Brand name</label>
                    <input type="text" name="name" x-model="brandName" maxlength="{{ \App\Support\EquipmentBrands::maxLength() }}" required placeholder="e.g. Logitech" class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Status</label>
                    <select name="status" x-model="brandStatus" class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="closeBrandModal()" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="rounded-lg bg-[#0025cc] px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800" x-text="brandEditId ? 'Save changes' : 'Add brand'"></button>
                </div>
            </form>
        </div>
    </div>

    {{-- Model create/edit --}}
    <div x-show="modelModalOpen" x-cloak @keydown.escape.window="closeModelModal()" class="fixed inset-0 z-50 flex items-center justify-center bg-[#0b1220]/70 p-4">
        <div @click.self="closeModelModal()" class="absolute inset-0"></div>
        <div class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl" @click.stop>
            <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950" x-text="modelEditId ? 'Edit model' : 'Add model'"></h2>
                    <p class="mt-1 text-sm text-slate-500">Used in Add to inventory Model.</p>
                </div>
                <button type="button" @click="closeModelModal()" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
            <form method="POST" :action="modelEditId ? (modelUpdateBase + '/' + modelEditId) : modelStoreUrl" class="space-y-4 px-6 py-5">
                @csrf
                <input type="hidden" name="_method" value="PUT" x-bind:disabled="!modelEditId">
                <div>
                    <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Model</label>
                    <input type="text" name="name" x-model="modelName" maxlength="{{ \App\Support\EquipmentModels::maxLength() }}" required placeholder="e.g. B100" class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Status</label>
                    <select name="active" x-model="modelActive" class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="closeModelModal()" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="rounded-lg bg-[#0025cc] px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800" x-text="modelEditId ? 'Save changes' : 'Add model'"></button>
                </div>
            </form>
        </div>
    </div>

    {{-- Manage equipment names inside a classification --}}
    <div
        x-show="typesModalOpen"
        x-cloak
        @keydown.escape.window="closeTypesModal()"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <div @click.self="closeTypesModal()" class="absolute inset-0"></div>
        <div
            x-show="typesModalOpen"
            x-transition
            class="relative z-10 flex max-h-[min(40rem,90vh)] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
            @click.stop
        >
            <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                <div class="min-w-0 pr-4">
                    <h2 class="text-lg font-semibold text-slate-950">Equipment in classification</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Names listed under
                        <span class="font-medium text-slate-700" x-text="typesCategoryName"></span>
                        drive inventory auto-predict. Vague words (e.g. Wire) only map here if you add them.
                    </p>
                </div>
                <button type="button" @click="closeTypesModal()" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <div class="border-b border-slate-100 bg-slate-50 px-6 py-4">
                <p class="mb-1.5 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Add equipment name</p>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
                    <div class="min-w-0 flex-1 space-y-2">
                        <input
                            type="text"
                            x-model="typesNewName"
                            maxlength="{{ \App\Support\EquipmentCategoryTypes::maxLength() }}"
                            @keydown.enter.prevent="addType()"
                            placeholder="e.g. Mouse, Ethernet Cable"
                            class="h-10 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 focus:ring-2 focus:ring-slate-900/10"
                        >
                        <input
                            type="text"
                            x-model="typesNewAliases"
                            maxlength="255"
                            @keydown.enter.prevent="addType()"
                            placeholder="Also match (optional): lan cable, network cable"
                            class="h-9 w-full rounded-xl border-0 bg-white px-3.5 text-sm text-slate-700 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 focus:ring-2 focus:ring-slate-900/10"
                        >
                    </div>
                    <button
                        type="button"
                        @click="addType()"
                        :disabled="typesSaving"
                        class="inline-flex h-10 shrink-0 items-center justify-center rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:opacity-60"
                        x-text="typesSaving ? 'Adding…' : 'Add'"
                    ></button>
                </div>
                <p class="mt-2 text-xs text-rose-600" x-show="typesError" x-text="typesError"></p>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-2 py-2">
                <template x-if="typesLoading">
                    <p class="px-4 py-8 text-center text-sm text-slate-400">Loading…</p>
                </template>
                <template x-if="!typesLoading && typesList.length === 0">
                    <p class="px-4 py-8 text-center text-sm text-slate-400">No equipment names yet. Add the first one above.</p>
                </template>
                <ul class="divide-y divide-slate-100">
                    <template x-for="row in typesList" :key="row.id">
                        <li class="flex items-start gap-3 px-4 py-3">
                            <div class="min-w-0 flex-1">
                                <template x-if="typesEditId !== row.id">
                                    <div>
                                        <p class="font-medium text-slate-900" x-text="row.name"></p>
                                        <p class="mt-0.5 text-xs text-slate-400" x-show="row.aliases" x-text="'Also match: ' + row.aliases"></p>
                                    </div>
                                </template>
                                <template x-if="typesEditId === row.id">
                                    <div class="space-y-2">
                                        <input type="text" x-model="typesEditName" maxlength="{{ \App\Support\EquipmentCategoryTypes::maxLength() }}" class="h-9 w-full rounded-lg border-0 bg-slate-50 px-3 text-sm ring-1 ring-slate-200 outline-none focus:bg-white focus:ring-slate-900/10">
                                        <input type="text" x-model="typesEditAliases" maxlength="255" placeholder="Also match…" class="h-9 w-full rounded-lg border-0 bg-slate-50 px-3 text-sm ring-1 ring-slate-200 outline-none focus:bg-white focus:ring-slate-900/10">
                                        <div class="flex gap-2">
                                            <button type="button" @click="saveType(row)" :disabled="typesSaving" class="rounded-lg bg-[#0025cc] px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-800 disabled:opacity-60">Save</button>
                                            <button type="button" @click="cancelEditType()" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div class="flex shrink-0 gap-1" x-show="typesEditId !== row.id">
                                <button type="button" @click="startEditType(row)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#0025cc] hover:bg-blue-50" title="Edit">
                                    <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                </button>
                                <button type="button" @click="removeType(row)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-rose-600 hover:bg-rose-50" title="Remove">
                                    <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                </button>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>
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

            brandModalOpen: false,
            brandEditId: null,
            brandName: '',
            brandStatus: 'Active',
            brandStoreUrl: @js(route('admin.equipment-categories.brands.store')),
            brandUpdateBase: @js(url('/admin/equipment/categories/brands')),

            modelModalOpen: false,
            modelEditId: null,
            modelName: '',
            modelActive: '1',
            modelStoreUrl: @js(route('admin.equipment-categories.models.store')),
            modelUpdateBase: @js(url('/admin/equipment/categories/models')),

            openBrandCreate() {
                this.brandEditId = null;
                this.brandName = '';
                this.brandStatus = 'Active';
                this.brandModalOpen = true;
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            },
            openBrandEdit(id, name, status) {
                this.brandEditId = id;
                this.brandName = name;
                this.brandStatus = status || 'Active';
                this.brandModalOpen = true;
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            },
            closeBrandModal() {
                this.brandModalOpen = false;
                this.brandEditId = null;
                this.brandName = '';
                this.brandStatus = 'Active';
            },
            openModelCreate() {
                this.modelEditId = null;
                this.modelName = '';
                this.modelActive = '1';
                this.modelModalOpen = true;
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            },
            openModelEdit(id, name, active) {
                this.modelEditId = id;
                this.modelName = name;
                this.modelActive = active ? '1' : '0';
                this.modelModalOpen = true;
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            },
            closeModelModal() {
                this.modelModalOpen = false;
                this.modelEditId = null;
                this.modelName = '';
                this.modelActive = '1';
            },

            typesModalOpen: false,
            typesLoading: false,
            typesSaving: false,
            typesCategoryId: null,
            typesCategoryName: '',
            typesList: [],
            typesNewName: '',
            typesNewAliases: '',
            typesError: '',
            typesEditId: null,
            typesEditName: '',
            typesEditAliases: '',
            typesBaseUrl: @js(url('/admin/equipment/categories')),
            csrfToken() {
                return document.querySelector('meta[name="csrf-token"]')?.content || '';
            },
            async openTypesModal(categoryId, categoryName) {
                this.typesModalOpen = true;
                this.typesCategoryId = categoryId;
                this.typesCategoryName = categoryName;
                this.typesNewName = '';
                this.typesNewAliases = '';
                this.typesError = '';
                this.typesEditId = null;
                this.typesList = [];
                this.typesLoading = true;
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                try {
                    const res = await fetch(this.typesBaseUrl + '/' + categoryId + '/types', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) throw new Error(data.message || 'Could not load equipment names.');
                    this.typesList = Array.isArray(data.types) ? data.types : [];
                } catch (e) {
                    this.typesError = e.message || 'Could not load equipment names.';
                } finally {
                    this.typesLoading = false;
                    this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
                }
            },
            closeTypesModal() {
                if (this.typesSaving) return;
                this.typesModalOpen = false;
                this.typesCategoryId = null;
                this.typesCategoryName = '';
                this.typesList = [];
                this.typesEditId = null;
                this.typesError = '';
            },
            async addType() {
                if (this.typesSaving || !this.typesCategoryId) return;
                const name = String(this.typesNewName || '').trim();
                if (!name) {
                    this.typesError = 'Type the equipment name first.';
                    return;
                }
                this.typesSaving = true;
                this.typesError = '';
                try {
                    const res = await fetch(this.typesBaseUrl + '/' + this.typesCategoryId + '/types', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.csrfToken(),
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            name,
                            aliases: String(this.typesNewAliases || '').trim() || null,
                        }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        const first = data.errors ? Object.values(data.errors).flat()[0] : null;
                        throw new Error(first || data.message || 'Could not add.');
                    }
                    this.typesNewName = '';
                    this.typesNewAliases = '';
                    await this.openTypesModal(this.typesCategoryId, this.typesCategoryName);
                } catch (e) {
                    this.typesError = e.message || 'Could not add.';
                } finally {
                    this.typesSaving = false;
                }
            },
            startEditType(row) {
                this.typesEditId = row.id;
                this.typesEditName = row.name;
                this.typesEditAliases = row.aliases || '';
                this.typesError = '';
            },
            cancelEditType() {
                this.typesEditId = null;
                this.typesEditName = '';
                this.typesEditAliases = '';
            },
            async saveType(row) {
                if (this.typesSaving) return;
                const name = String(this.typesEditName || '').trim();
                if (!name) {
                    this.typesError = 'Enter the equipment name.';
                    return;
                }
                this.typesSaving = true;
                this.typesError = '';
                try {
                    const res = await fetch(this.typesBaseUrl + '/' + this.typesCategoryId + '/types/' + row.id, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.csrfToken(),
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            name,
                            aliases: String(this.typesEditAliases || '').trim() || null,
                            active: true,
                        }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        const first = data.errors ? Object.values(data.errors).flat()[0] : null;
                        throw new Error(first || data.message || 'Could not save.');
                    }
                    this.cancelEditType();
                    await this.openTypesModal(this.typesCategoryId, this.typesCategoryName);
                } catch (e) {
                    this.typesError = e.message || 'Could not save.';
                } finally {
                    this.typesSaving = false;
                }
            },
            async removeType(row) {
                if (this.typesSaving) return;
                const ok = window.confirm('Remove "' + row.name + '" from this classification?');
                if (!ok) return;
                this.typesSaving = true;
                this.typesError = '';
                try {
                    const res = await fetch(this.typesBaseUrl + '/' + this.typesCategoryId + '/types/' + row.id, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.csrfToken(),
                        },
                        credentials: 'same-origin',
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) throw new Error(data.message || 'Could not remove.');
                    await this.openTypesModal(this.typesCategoryId, this.typesCategoryName);
                } catch (e) {
                    this.typesError = e.message || 'Could not remove.';
                } finally {
                    this.typesSaving = false;
                }
            },


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
                    @js(url('/admin/equipment/categories')) + '/' + categoryId;

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
                        title: 'Classification in use',
                        text:
                            `"${categoryName}" cannot be deleted because ` +
                            `${equipmentCount} equipment record` +
                            `${equipmentCount === 1 ? ' is' : 's are'} still assigned to it.`,
                        confirmButtonText: 'Understood',
                        buttonsStyling: false,
                        background: '#ffffff',
                        color: '#0f172a',
                        customClass: {
                            popup: 'prism-swal-dialog',
                            icon: 'prism-swal-dialog__icon',
                            title: 'prism-swal-dialog__title',
                            htmlContainer: 'prism-swal-dialog__body',
                            actions: 'prism-swal-dialog__actions',
                            confirmButton: 'prism-swal-dialog__confirm',
                        },
                    });


                    return;

                }


                // =====================================================
                // CATEGORY CAN BE DELETED
                // =====================================================

                Swal.fire({
                    icon: 'warning',
                    title: 'Delete classification?',
                    html:
                        `You are about to delete <strong>${this.escapeHtml(categoryName)}</strong>. ` +
                        `This action cannot be undone.`,
                    showCancelButton: true,
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    focusCancel: true,
                    buttonsStyling: false,
                    background: '#ffffff',
                    color: '#0f172a',
                    customClass: {
                        popup: 'prism-swal-dialog',
                        icon: 'prism-swal-dialog__icon',
                        title: 'prism-swal-dialog__title',
                        htmlContainer: 'prism-swal-dialog__body',
                        actions: 'prism-swal-dialog__actions',
                        confirmButton: 'prism-swal-dialog__confirm prism-swal-dialog__confirm--danger',
                        cancelButton: 'prism-swal-dialog__cancel',
                    },
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





<style>
    [x-cloak] {
        display: none !important;
    }

    .prism-swal-dialog.swal2-popup {
        width: min(22.5rem, calc(100vw - 2rem)) !important;
        padding: 1.75rem 1.5rem 1.35rem !important;
        border-radius: 1.25rem !important;
        border: 1px solid #e2e8f0 !important;
        background: #ffffff !important;
        box-shadow: 0 18px 48px rgba(15, 23, 42, 0.12) !important;
        color: #0f172a !important;
    }

    .prism-swal-dialog .swal2-icon.prism-swal-dialog__icon {
        width: 2.75rem !important;
        height: 2.75rem !important;
        margin: 0 auto 1rem !important;
        border-width: 1.5px !important;
    }

    .prism-swal-dialog .swal2-icon.swal2-warning {
        border-color: #fdba74 !important;
        color: #ea580c !important;
    }

    .prism-swal-dialog .swal2-icon .swal2-icon-content {
        font-size: 1.35rem !important;
        font-weight: 600 !important;
    }

    .prism-swal-dialog .prism-swal-dialog__title {
        margin: 0 !important;
        padding: 0 !important;
        font-size: 1.05rem !important;
        font-weight: 600 !important;
        letter-spacing: -0.02em !important;
        line-height: 1.35 !important;
        color: #0f172a !important;
    }

    .prism-swal-dialog .prism-swal-dialog__body,
    .prism-swal-dialog .swal2-html-container,
    .prism-swal-dialog .swal2-html-container.prism-swal-dialog__body {
        margin: 0.55rem 0 0 !important;
        padding: 0 !important;
        font-size: 0.875rem !important;
        font-weight: 400 !important;
        line-height: 1.55 !important;
        color: #64748b !important;
    }

    .prism-swal-dialog .prism-swal-dialog__actions {
        width: 100% !important;
        margin: 1.35rem 0 0 !important;
        gap: 0.5rem !important;
    }

    .prism-swal-dialog .prism-swal-dialog__confirm,
    .prism-swal-dialog .prism-swal-dialog__cancel {
        margin: 0 !important;
        flex: 1 1 0% !important;
        border-radius: 0.75rem !important;
        padding: 0.7rem 1rem !important;
        font-size: 0.8125rem !important;
        font-weight: 600 !important;
        box-shadow: none !important;
        transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }

    .prism-swal-dialog .prism-swal-dialog__confirm {
        background: #0025cc !important;
        color: #ffffff !important;
        border: 1px solid #0025cc !important;
    }

    .prism-swal-dialog .prism-swal-dialog__confirm:hover {
        background: #001fad !important;
        border-color: #001fad !important;
    }

    .prism-swal-dialog .prism-swal-dialog__confirm--danger {
        background: #ffffff !important;
        color: #b91c1c !important;
        border: 1px solid #fecaca !important;
    }

    .prism-swal-dialog .prism-swal-dialog__confirm--danger:hover {
        background: #fff1f2 !important;
        border-color: #fda4af !important;
    }

    .prism-swal-dialog .prism-swal-dialog__cancel {
        background: #f8fafc !important;
        color: #475569 !important;
        border: 1px solid #e2e8f0 !important;
    }

    .prism-swal-dialog .prism-swal-dialog__cancel:hover {
        background: #f1f5f9 !important;
        color: #0f172a !important;
    }
</style>

@endsection