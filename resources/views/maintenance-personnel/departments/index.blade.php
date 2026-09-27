@extends('layouts.maintenance-layout')

@section('title', 'Departments')

@section('content')
@php
    $fieldClass = 'h-10 w-full rounded-xl border-0 bg-slate-50 px-3 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10';
    $formErrors = $errors->getBag('department');
    $reopen = $formErrors->any()
        ? [
            'id' => old('department_id') ? (int) old('department_id') : null,
            'name' => old('department_name', ''),
            'description' => old('department_description', ''),
        ]
        : null;
@endphp

<div
    class="space-y-6"
    x-data="{
        open: @js($reopen !== null),
        form: @js($reopen ?? ['id' => null, 'name' => '', 'description' => '']),
        storeUrl: @js(route('maintenance.departments.store')),
        updateBase: @js(url('/maintenance/departments')),
        start(dept = null) {
            this.form = dept ? { ...dept } : { id: null, name: '', description: '' };
            this.open = true;
            this.$nextTick(() => {
                window.lucide?.createIcons();
                document.getElementById('department-name-input')?.focus();
            });
        },
    }"
    @keydown.escape.window="open = false"
>
    @if (! $tableReady)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
            The departments table is missing. Run <code class="font-mono text-xs">php artisan migrate</code> to enable this page.
        </div>
    @else
        @include('layouts.partials.maintenance-stat-cards', [
            'cards' => [
                ['label' => 'Active departments', 'hint' => $stats['archived'] > 0 ? number_format($stats['archived']).' archived' : 'Shown when adding people', 'value' => number_format($stats['active'])],
                ['label' => 'People with a department', 'hint' => 'In the people directory', 'value' => number_format($stats['assigned_people'])],
                ['label' => 'People without a department', 'hint' => 'Edit them to set one', 'value' => number_format($stats['unassigned_people'])],
            ],
        ])

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Departments / offices</h2>
                    <p class="mt-0.5 text-sm text-slate-500">The units people belong to. Used in the people accountability forms.</p>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <form method="GET" action="{{ route('maintenance.departments.index') }}" class="flex gap-2">
                        @if ($filter !== 'active')
                            <input type="hidden" name="filter" value="{{ $filter }}">
                        @endif
                        <div class="relative">
                            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                            <input type="search" name="search" value="{{ $search }}" placeholder="Search department" class="h-10 w-full rounded-xl border-0 bg-slate-50 pl-10 pr-3 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10 sm:w-64">
                        </div>
                        <button type="submit" class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-blue-800">
                            <i data-lucide="search" class="h-4 w-4"></i>
                            Search
                        </button>
                    </form>
                    <button type="button" @click="start()" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-[#001fad]">
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        Add department
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 border-b border-slate-100 px-4 py-3 sm:px-5">
                @foreach (\App\Support\Departments::FILTERS as $key => $label)
                    <a
                        href="{{ route('maintenance.departments.index', array_filter(['filter' => $key === 'active' ? null : $key, 'search' => $search ?: null])) }}"
                        class="inline-flex h-8 items-center rounded-full px-3 text-xs font-semibold ring-1 transition {{ $filter === $key ? 'bg-slate-100 text-slate-900 ring-slate-300' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            @if ($departments->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Department</th>
                                <th class="px-5 py-3">People</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-center justify-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($departments as $dept)
                                @php
                                    $payload = [
                                        'id' => (int) $dept->department_id,
                                        'name' => $dept->department_name,
                                        'description' => (string) $dept->department_description,
                                    ];
                                @endphp
                                <tr class="align-middle {{ $dept->department_is_archived ? 'bg-slate-50/60' : '' }}">
                                    <td class="px-5 py-3">
                                        <p class="font-semibold {{ $dept->department_is_archived ? 'text-slate-500' : 'text-slate-900' }}">{{ $dept->department_name }}</p>
                                        @if ($dept->department_description)
                                            <p class="mt-0.5 text-xs text-slate-500">{{ $dept->department_description }}</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        @if ($dept->people_count > 0)
                                            <a href="{{ route('maintenance.property-assignments.index', ['department' => $dept->department_id]) }}" class="inline-flex items-center gap-1.5 font-semibold text-[#0025cc] hover:underline">
                                                <i data-lucide="users" class="h-3.5 w-3.5"></i>
                                                {{ $dept->people_count }} {{ \Illuminate\Support\Str::plural('person', $dept->people_count) }}
                                            </a>
                                            @if ($dept->active_people_count < $dept->people_count)
                                                <p class="text-[11px] text-slate-400">{{ $dept->active_people_count }} not inactive</p>
                                            @endif
                                        @else
                                            <span class="text-slate-400">No one yet</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        @if ($dept->department_is_archived)
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 ring-1 ring-slate-200">Archived</span>
                                        @else
                                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-100">Active</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button
                                                type="button"
                                                @click="start(@js($payload))"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001db3]"
                                                data-tooltip="Edit department"
                                                aria-label="Edit department"
                                            >
                                                <i data-lucide="pencil" class="h-4 w-4"></i>
                                            </button>
                                            @if ($dept->department_is_archived)
                                                <form method="POST" action="{{ route('maintenance.departments.restore', $dept->department_id) }}">
                                                    @csrf
                                                    <button
                                                        type="submit"
                                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#0025cc] transition hover:bg-slate-50 active:scale-95"
                                                        data-tooltip="Restore department"
                                                        aria-label="Restore department"
                                                    >
                                                        <i data-lucide="archive-restore" class="h-4 w-4"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form
                                                    method="POST"
                                                    action="{{ route('maintenance.departments.archive', $dept->department_id) }}"
                                                    data-pur-confirm="{{ $dept->department_name }} will be hidden when adding new people. Anyone already in it keeps it, and you can restore it anytime."
                                                    data-pur-confirm-title="Archive department?"
                                                    data-pur-confirm-ok="Archive"
                                                    data-pur-confirm-kind="archive"
                                                >
                                                    @csrf
                                                    <button
                                                        type="submit"
                                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#007a3f] transition hover:bg-slate-50 active:scale-95"
                                                        data-tooltip="Archive department"
                                                        aria-label="Archive department"
                                                    >
                                                        <i data-lucide="archive" class="h-4 w-4"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            @if ((int) $dept->people_count === 0)
                                                <form
                                                    method="POST"
                                                    action="{{ route('maintenance.departments.destroy', $dept->department_id) }}"
                                                    data-pur-confirm="{{ $dept->department_name }} will be permanently removed. This cannot be undone."
                                                    data-pur-confirm-title="Delete department?"
                                                    data-pur-confirm-ok="Delete"
                                                    data-pur-confirm-danger="1"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="submit"
                                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-red-700 transition hover:bg-slate-50 active:scale-95"
                                                        data-tooltip="Delete department"
                                                        aria-label="Delete department"
                                                    >
                                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <span
                                                    class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-300"
                                                    data-tooltip="Has people. Archive it instead."
                                                    aria-label="Cannot delete: department has people"
                                                >
                                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-5 py-16 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <i data-lucide="building-2" class="h-6 w-6"></i>
                    </div>
                    <p class="mt-4 text-sm font-semibold text-slate-900">
                        {{ $search !== '' ? 'No department matches your search' : ($filter === 'archived' ? 'No archived departments' : 'No departments yet') }}
                    </p>
                    <button type="button" @click="start()" class="mt-4 inline-flex h-10 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001fad]">
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        Add department
                    </button>
                </div>
            @endif
        </div>
    @endif

    <template x-teleport="body">
    <div x-show="open" x-cloak class="fixed inset-0 z-[1300] flex items-start justify-center overflow-y-auto bg-[#0b1220]/70 p-4" @click.self="open = false">
        <form
            method="POST"
            :action="form.id ? updateBase + '/' + form.id : storeUrl"
            class="my-auto w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl"
        >
            @csrf
            <template x-if="form.id">
                <input type="hidden" name="_method" value="PUT">
            </template>
            <input type="hidden" name="department_id" :value="form.id ?? ''">

            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h3 class="text-base font-semibold text-slate-900" x-text="form.id ? 'Edit department' : 'Add department'"></h3>
                <button type="button" @click="open = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <div class="space-y-4 px-5 py-5">
                @if ($formErrors->any())
                    <div class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-100">
                        @foreach ($formErrors->all() as $message)
                            <p>{{ $message }}</p>
                        @endforeach
                    </div>
                @endif

                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Name <span class="text-rose-500">*</span></span>
                    <input type="text" name="department_name" id="department-name-input" x-model="form.name" required maxlength="120" placeholder="e.g. Registrar" class="{{ $fieldClass }}">
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Description</span>
                    <input type="text" name="department_description" x-model="form.description" maxlength="255" placeholder="Optional, e.g. Enrollment records and transcripts" class="{{ $fieldClass }}">
                </label>

                <p x-show="form.id" class="text-xs text-slate-400">Renaming updates everyone in this department automatically.</p>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-4">
                <button type="button" @click="open = false" class="inline-flex h-10 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001fad]">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    <span x-text="form.id ? 'Save changes' : 'Add department'"></span>
                </button>
            </div>
        </form>
    </div>
    </template>
</div>
@endsection
