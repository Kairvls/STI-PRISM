@extends('layouts.maintenance-layout')

@section('title', 'Suggested Issues')

@section('content')
@php
    $issueTrendCounts = collect($issuesMonthlyTrend)->pluck('count');
    $issueTrendMax = max(1, $issueTrendCounts->max() ?? 0);
    $issueTrendTotalPoints = max(1, $issueTrendCounts->count() - 1);

    $issueTrendPoints = collect($issuesMonthlyTrend)
        ->values()
        ->map(function ($item, $index) use ($issueTrendMax, $issueTrendTotalPoints) {
            $x = ($index / $issueTrendTotalPoints) * 300;
            $y = 90 - (($item['count'] / $issueTrendMax) * 75);

            return round($x, 2).','.round($y, 2);
        })
        ->implode(' ');

    $issueTrendAreaPoints = $issueTrendPoints.' 300,100 0,100';
@endphp

<style>[x-cloak]{display:none!important}</style>

<div
    class="min-h-full"
    x-data="suggestedIssuesPage()"
>
    <div class="flex justify-end">
        <button
            type="button"
            @click="openCreateModal()"
            class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white transition hover:bg-blue-800"
        >
            <i data-lucide="plus" class="h-4 w-4"></i>
            Add Issue
        </button>
    </div>

    {{-- Dashboard --}}
    <div class="mb-6 mt-6 overflow-hidden rounded-lg border-y border-slate-300 bg-gray-100 shadow-sm">
        <div class="grid grid-cols-1 divide-y divide-slate-200 md:grid-cols-2 md:divide-y-0 xl:grid-cols-[380px_1fr_1fr_1fr]">
            <div class="flex items-center justify-between px-8 py-6">
                <div class="flex flex-col">
                    <p class="text-sm font-medium text-slate-500">Total Issues</p>
                    <h2 class="mt-2 text-5xl font-medium text-slate-900">
                        {{ number_format($totalIssues) }}
                    </h2>
                    <p class="mt-3 text-sm">
                        @if ($issuesMonthlyPercentage === null)
                            <span class="font-semibold text-emerald-500">New activity</span>
                        @else
                            <span
                                class="font-semibold {{ $issuesMonthlyPercentage > 0 ? 'text-emerald-500' : ($issuesMonthlyPercentage < 0 ? 'text-red-500' : 'text-slate-500') }}"
                            >
                                {{ $issuesMonthlyPercentage > 0 ? '+' : '' }}{{ number_format($issuesMonthlyPercentage, 2) }}%
                            </span>
                        @endif
                        <span class="text-slate-500">From last month</span>
                    </p>
                </div>

                <div class="ml-6 h-20 w-40 shrink-0">
                    <svg viewBox="0 0 300 100" class="h-full w-full" fill="none">
                        <polygon
                            points="{{ $issueTrendAreaPoints }}"
                            fill="currentColor"
                            fill-opacity=".08"
                            class="text-slate-900"
                        />
                        <polyline
                            points="{{ $issueTrendPoints }}"
                            fill="none"
                            stroke="#3b82f6"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </div>
            </div>

            <div class="relative flex flex-col justify-between px-8 py-7">
                <span class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"></span>
                <p class="text-md font-medium text-slate-600">Categories Covered</p>
                <h2 class="text-5xl font-medium text-slate-900">
                    {{ number_format($categoriesCovered) }}
                </h2>
                <p class="text-base">
                    <span class="font-semibold text-slate-900">
                        {{ number_format($categoriesCoveredPercentage, 2) }}%
                    </span>
                    <span class="text-slate-500">of all categories</span>
                </p>
            </div>

            <div class="relative flex flex-col justify-between px-8 py-7">
                <span class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"></span>
                <p class="text-md font-medium text-slate-600">Category-wide</p>
                <h2 class="text-5xl font-medium text-slate-900">
                    {{ number_format($categoryWideIssues) }}
                </h2>
                <p class="text-base">
                    <span class="font-semibold text-emerald-600">
                        {{ number_format($categoryWidePercentage, 2) }}%
                    </span>
                    <span class="text-slate-500">of all issues</span>
                </p>
            </div>

            <div class="relative flex flex-col justify-between px-8 py-7">
                <span class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"></span>
                <p class="text-md font-medium text-slate-600">Component-specific</p>
                <h2 class="text-5xl font-medium text-slate-900">
                    {{ number_format($componentSpecificIssues) }}
                </h2>
                <p class="text-base">
                    <span class="font-semibold text-blue-600">
                        {{ number_format($componentSpecificPercentage, 2) }}%
                    </span>
                    <span class="text-slate-500">of all issues</span>
                </p>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-950">Suggested Issues</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Templates shown when reporting equipment problems.
                </p>
            </div>

            <form
                method="GET"
                action="{{ url('/maintenance/equipment/suggested-issues') }}"
                class="flex flex-wrap items-center gap-2"
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
                        placeholder="Search issues..."
                        class="h-9 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-4 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100 sm:w-56"
                    >
                </div>

                <select
                    name="category"
                    class="h-9 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                >
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option
                            value="{{ $category->equipment_category_id }}"
                            @selected(request('category') == $category->equipment_category_id)
                        >
                            {{ $category->equipment_category_name }}
                        </option>
                    @endforeach
                </select>

                <button
                    type="submit"
                    class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800"
                >
                    <i data-lucide="search" class="h-4 w-4"></i>
                    Search
                </button>

                @if (request()->filled('search') || request()->filled('category'))
                    <a
                        href="{{ url('/maintenance/equipment/suggested-issues') }}"
                        class="inline-flex h-9 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-950"
                    >
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Issue
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Category
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                            Shows for
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold uppercase tracking-wider text-slate-500">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($issues as $issue)
                        <tr class="transition hover:bg-slate-50/80">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-500">
                                        <i data-lucide="alert-circle" class="h-4 w-4"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-950">
                                            {{ $issue->issue_template_name }}
                                        </p>
                                        <p class="mt-0.5 text-xs text-slate-400">
                                            Issue ID: {{ $issue->issue_template_id }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-semibold text-slate-700">
                                    {{ $issue->equipment_category_name ?? '—' }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                @if (!empty($issue->issue_template_component))
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-2.5 py-1.5 text-xs font-semibold text-blue-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                        {{ $issue->issue_template_component }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        All in category
                                    </span>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button
                                        type="button"
                                        data-tooltip="Edit Issue"
                                        aria-label="Edit issue"
                                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#FFF200] text-black transition hover:bg-[#E6E600] active:scale-95"
                                        @click="openEditModal({
                                            id: '{{ $issue->issue_template_id }}',
                                            name: @js($issue->issue_template_name),
                                            category: '{{ $issue->issue_template_category_id }}',
                                            component: @js($issue->issue_template_component ?? '')
                                        })"
                                    >
                                        <i data-lucide="edit-3" class="h-3.5 w-3.5"></i>
                                    </button>

                                    <button
                                        type="button"
                                        data-tooltip="Delete Issue"
                                        aria-label="Delete issue"
                                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-50 text-rose-600 ring-1 ring-inset ring-rose-200 transition hover:bg-rose-100 active:scale-95"
                                        @click="openDeleteModal({
                                            id: '{{ $issue->issue_template_id }}',
                                            name: @js($issue->issue_template_name),
                                            category: @js($issue->equipment_category_name ?? '—')
                                        })"
                                    >
                                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400">
                                        <i data-lucide="inbox" class="h-5 w-5"></i>
                                    </div>
                                    <p class="mt-4 text-sm font-semibold text-slate-900">No suggested issues yet</p>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Add templates so reporters can pick common problems faster.
                                    </p>
                                    <button
                                        type="button"
                                        @click="openCreateModal()"
                                        class="mt-4 inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white transition hover:bg-blue-800"
                                    >
                                        <i data-lucide="plus" class="h-4 w-4"></i>
                                        Add Issue
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($issues->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $issues->links() }}
            </div>
        @endif
    </div>

    {{-- Create modal --}}
    <div
        x-show="createOpen"
        x-cloak
        @keydown.escape.window="closeCreateModal()"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <div @click.self="closeCreateModal()" class="absolute inset-0"></div>

        <div
            x-show="createOpen"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="translate-y-3 scale-[0.98] opacity-0"
            x-transition:enter-end="translate-y-0 scale-100 opacity-100"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="translate-y-0 scale-100 opacity-100"
            x-transition:leave-end="translate-y-3 scale-[0.98] opacity-0"
            class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
        >
            <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Add suggested issue</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Create a reusable issue template for reporters.
                    </p>
                </div>
                <button
                    type="button"
                    @click="closeCreateModal()"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                >
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <form
                method="POST"
                action="/maintenance/equipment/suggested-issues"
                @submit="createSubmitting = true"
            >
                @csrf

                <div class="space-y-4 px-6 py-6">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Category</label>
                        <select
                            name="issue_template_category_id"
                            required
                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        >
                            <option value="">Select category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->equipment_category_id }}">
                                    {{ $category->equipment_category_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Shows for</label>
                        <select
                            name="issue_template_component"
                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        >
                            <option value="">All in category</option>
                            @foreach ($components as $component)
                                <option value="{{ $component }}">{{ $component }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Issue name</label>
                        <input
                            name="issue_template_name"
                            required
                            placeholder="Mouse Defective"
                            maxlength="255"
                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        >
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
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
                        <span x-text="createSubmitting ? 'Saving...' : 'Save'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit modal --}}
    <div
        x-show="editOpen"
        x-cloak
        @keydown.escape.window="closeEditModal()"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <div @click.self="closeEditModal()" class="absolute inset-0"></div>

        <div
            x-show="editOpen"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="translate-y-3 scale-[0.98] opacity-0"
            x-transition:enter-end="translate-y-0 scale-100 opacity-100"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="translate-y-0 scale-100 opacity-100"
            x-transition:leave-end="translate-y-3 scale-[0.98] opacity-0"
            class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
        >
            <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">Edit suggested issue</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Update this issue template.
                    </p>
                </div>
                <button
                    type="button"
                    @click="closeEditModal()"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                >
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <form
                method="POST"
                :action="'/maintenance/equipment/suggested-issues/' + editId"
                @submit="editSubmitting = true"
            >
                @csrf
                @method('PUT')

                <div class="space-y-4 px-6 py-6">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Category</label>
                        <select
                            name="issue_template_category_id"
                            x-model="editCategory"
                            required
                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        >
                            @foreach ($categories as $category)
                                <option value="{{ $category->equipment_category_id }}">
                                    {{ $category->equipment_category_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Shows for</label>
                        <select
                            name="issue_template_component"
                            x-model="editComponent"
                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        >
                            <option value="">All in category</option>
                            @foreach ($components as $component)
                                <option value="{{ $component }}">{{ $component }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Issue name</label>
                        <input
                            name="issue_template_name"
                            x-model="editName"
                            required
                            maxlength="255"
                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        >
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
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
                        class="inline-flex h-10 min-w-36 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <span x-text="editSubmitting ? 'Saving...' : 'Save changes'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete modal --}}
    <div
        x-show="deleteOpen"
        x-cloak
        @keydown.escape.window="closeDeleteModal()"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[#0b1220]/70 p-4"
    >
        <div @click.self="closeDeleteModal()" class="absolute inset-0"></div>

        <div
            x-show="deleteOpen"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="translate-y-3 scale-[0.98] opacity-0"
            x-transition:enter-end="translate-y-0 scale-100 opacity-100"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="translate-y-0 scale-100 opacity-100"
            x-transition:leave-end="translate-y-3 scale-[0.98] opacity-0"
            class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
        >
            <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6">
                <div class="min-w-0">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-rose-50 text-rose-600">
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                    </div>

                    <h2 class="text-lg font-semibold tracking-tight text-slate-950">
                        Delete suggested issue?
                    </h2>

                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        You are about to delete
                        <strong class="font-semibold text-slate-800" x-text="deleteName"></strong>
                        <span x-show="deleteCategory">
                            from
                            <strong class="font-semibold text-slate-800" x-text="deleteCategory"></strong>
                        </span>.
                        This action cannot be undone.
                    </p>
                </div>

                <button
                    type="button"
                    @click="closeDeleteModal()"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                    aria-label="Close modal"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <form
                method="POST"
                :action="'/maintenance/equipment/suggested-issues/' + deleteId"
                @submit="deleteSubmitting = true"
            >
                @csrf
                @method('DELETE')

                <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-6 py-4">
                    <button
                        type="button"
                        @click="closeDeleteModal()"
                        class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        :disabled="deleteSubmitting"
                        class="rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-rose-700 focus:outline-none focus:ring-4 focus:ring-rose-100 active:bg-rose-800 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <span x-text="deleteSubmitting ? 'Deleting...' : 'Delete Issue'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function suggestedIssuesPage() {
        return {
            createOpen: false,
            editOpen: false,
            deleteOpen: false,
            createSubmitting: false,
            editSubmitting: false,
            deleteSubmitting: false,
            editId: '',
            editName: '',
            editCategory: '',
            editComponent: '',
            deleteId: '',
            deleteName: '',
            deleteCategory: '',

            openCreateModal() {
                this.createOpen = true;
                this.createSubmitting = false;
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            },

            closeCreateModal() {
                this.createOpen = false;
                this.createSubmitting = false;
            },

            openEditModal(issue) {
                this.editId = issue.id;
                this.editName = issue.name;
                this.editCategory = String(issue.category ?? '');
                this.editComponent = issue.component ?? '';
                this.editOpen = true;
                this.editSubmitting = false;
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            },

            closeEditModal() {
                this.editOpen = false;
                this.editSubmitting = false;
            },

            openDeleteModal(issue) {
                this.deleteId = issue.id;
                this.deleteName = issue.name;
                this.deleteCategory = issue.category ?? '';
                this.deleteOpen = true;
                this.deleteSubmitting = false;
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            },

            closeDeleteModal() {
                this.deleteOpen = false;
                this.deleteSubmitting = false;
            },
        };
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) window.lucide.createIcons();
    });
</script>
@endsection
