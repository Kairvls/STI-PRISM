@extends('layouts.maintenance-layout')

@section('title', 'Suggested Issues')

@section('content')
<style>[x-cloak]{display:none!important}</style>
<div class="space-y-6" x-data="{ createOpen: false, editOpen: false, editId: '', editName: '', editCategory: '', editComponent: '' }">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-4xl font-black text-slate-900">Suggested Issues</h1>
            <p class="mt-1 text-slate-500">Issues follow the survey categories. For Computer Equipment, tag Mouse, Keyboard, Monitor, System Unit, or UPS / AVR so the report form only shows matching chips.</p>
        </div>
        <button type="button" @click="createOpen = true" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
            <i data-lucide="plus" class="h-4 w-4"></i>
            Add issue
        </button>
    </div>

    <form method="GET" class="flex flex-wrap items-center gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search issues" class="h-10 w-full max-w-xs rounded-xl border-0 bg-slate-50 px-3.5 text-sm ring-1 ring-slate-200/80 outline-none focus:bg-white focus:ring-2 focus:ring-slate-900/10" />
        <select name="category" class="h-10 rounded-xl border-0 bg-slate-50 px-3 text-sm ring-1 ring-slate-200/80 outline-none">
            <option value="">All categories</option>
            @foreach ($categories as $category)
                <option value="{{ $category->equipment_category_id }}" @selected(request('category') == $category->equipment_category_id)>
                    {{ $category->equipment_category_name }}
                </option>
            @endforeach
        </select>
        <button class="h-10 rounded-xl bg-neutral-100 px-4 text-sm font-medium text-slate-700 ring-1 ring-slate-200/80">Search</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-[12px] font-bold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-5 py-3">Issue</th>
                    <th class="px-5 py-3">Category</th>
                    <th class="px-5 py-3">Shows for</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($issues as $issue)
                    <tr class="border-t border-slate-100">
                        <td class="px-5 py-4 font-medium text-slate-900">{{ $issue->issue_template_name }}</td>
                        <td class="px-5 py-4 text-slate-500">{{ $issue->equipment_category_name ?? '—' }}</td>
                        <td class="px-5 py-4 text-slate-500">{{ $issue->issue_template_component ?: 'All in category' }}</td>
                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-1">
                                <button
                                    type="button"
                                    class="rounded-lg px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50"
                                    @click="editOpen = true; editId = '{{ $issue->issue_template_id }}'; editName = @js($issue->issue_template_name); editCategory = '{{ $issue->issue_template_category_id }}'; editComponent = @js($issue->issue_template_component ?? '')"
                                >Edit</button>
                                <form method="POST" action="/maintenance/equipment/suggested-issues/{{ $issue->issue_template_id }}" onsubmit="return confirm('Delete this suggested issue?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-lg px-3 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-5 py-16 text-center text-sm text-slate-500">No suggested issues yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $issues->links() }}</div>

    <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/30 p-4 backdrop-blur-[2px]">
        <form method="POST" action="/maintenance/equipment/suggested-issues" class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
            @csrf
            <h2 class="text-lg font-semibold text-slate-900">Add suggested issue</h2>
            <div class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Category</label>
                    <select name="issue_template_category_id" required class="h-11 w-full rounded-xl bg-slate-50 px-3.5 text-sm ring-1 ring-slate-200/80">
                        <option value="">Select category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->equipment_category_id }}">{{ $category->equipment_category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Shows for</label>
                    <select name="issue_template_component" class="h-11 w-full rounded-xl bg-slate-50 px-3.5 text-sm ring-1 ring-slate-200/80">
                        <option value="">All in category</option>
                        @foreach ($components as $component)
                            <option value="{{ $component }}">{{ $component }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Issue name</label>
                    <input name="issue_template_name" required placeholder="Mouse Defective" class="h-11 w-full rounded-xl bg-slate-50 px-3.5 text-sm ring-1 ring-slate-200/80" />
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="createOpen = false" class="h-10 rounded-xl px-4 text-sm text-slate-600 hover:bg-slate-100">Cancel</button>
                <button class="h-10 rounded-xl bg-slate-900 px-5 text-sm font-medium text-white">Save</button>
            </div>
        </form>
    </div>

    <div x-show="editOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/30 p-4 backdrop-blur-[2px]">
        <form method="POST" :action="'/maintenance/equipment/suggested-issues/' + editId" class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
            @csrf
            @method('PUT')
            <h2 class="text-lg font-semibold text-slate-900">Edit suggested issue</h2>
            <div class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Category</label>
                    <select name="issue_template_category_id" x-model="editCategory" required class="h-11 w-full rounded-xl bg-slate-50 px-3.5 text-sm ring-1 ring-slate-200/80">
                        @foreach ($categories as $category)
                            <option value="{{ $category->equipment_category_id }}">{{ $category->equipment_category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Shows for</label>
                    <select name="issue_template_component" x-model="editComponent" class="h-11 w-full rounded-xl bg-slate-50 px-3.5 text-sm ring-1 ring-slate-200/80">
                        <option value="">All in category</option>
                        @foreach ($components as $component)
                            <option value="{{ $component }}">{{ $component }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Issue name</label>
                    <input name="issue_template_name" x-model="editName" required class="h-11 w-full rounded-xl bg-slate-50 px-3.5 text-sm ring-1 ring-slate-200/80" />
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="editOpen = false" class="h-10 rounded-xl px-4 text-sm text-slate-600 hover:bg-slate-100">Cancel</button>
                <button class="h-10 rounded-xl bg-slate-900 px-5 text-sm font-medium text-white">Save changes</button>
            </div>
        </form>
    </div>
</div>
@endsection
