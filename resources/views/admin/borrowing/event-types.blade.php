@extends('layouts.maintenance-layout')

@section('content')
    @if (session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <ul class="list-disc space-y-1 pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div
        class="space-y-6"
        x-data="{ tab: @js($tab ?? 'names') }"
    >
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
            <div class="inline-flex rounded-xl bg-slate-100 p-1 ring-1 ring-slate-200/80">
                <button
                    type="button"
                    @click="tab = 'names'"
                    :class="tab === 'names' ? 'bg-[#0025cc] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                    class="inline-flex h-9 items-center rounded-lg px-3.5 text-xs font-semibold transition"
                >Event names</button>
                <button
                    type="button"
                    @click="tab = 'types'"
                    :class="tab === 'types' ? 'bg-[#0025cc] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                    class="inline-flex h-9 items-center rounded-lg px-3.5 text-xs font-semibold transition"
                >Event types</button>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a
                    href="{{ route('admin.borrowing.index') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-[13px] font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    <i data-lucide="clipboard-list" class="h-4 w-4"></i>
                    Borrowing records
                </a>
                <button
                    type="button"
                    x-show="tab === 'names'"
                    x-cloak
                    onclick="document.getElementById('addEventNameModal')?.classList.replace('hidden','flex')"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white transition hover:bg-[#001fa8]"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Add event name
                </button>
                <button
                    type="button"
                    x-show="tab === 'types'"
                    x-cloak
                    onclick="document.getElementById('addEventTypeModal')?.classList.replace('hidden','flex')"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-[13px] font-semibold text-white transition hover:bg-[#001fa8]"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Add event type
                </button>
            </div>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-950" x-text="tab === 'names' ? 'Event names' : 'Event types'"></h2>
                    <p class="mt-0.5 text-sm text-slate-500" x-text="tab === 'names'
                        ? 'Saved event titles used on Borrow Equipment.'
                        : 'Classifications like Campus activity or City / LGU event.'"></p>
                </div>
                <form method="GET" class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                    <input type="hidden" name="tab" :value="tab">
                    <input
                        type="text"
                        name="q"
                        value="{{ $q }}"
                        :placeholder="tab === 'names' ? 'Search event name…' : 'Search event type…'"
                        maxlength="50"
                        class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 outline-none focus:border-slate-300 focus:bg-white sm:w-64"
                    >
                    <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-[#001fa8]">
                        <i data-lucide="filter" class="h-4 w-4"></i>
                        Apply
                    </button>
                </form>
            </div>

            {{-- Event names list --}}
            <div x-show="tab === 'names'" x-cloak class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-100 text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">
                            <th class="px-5 py-3">Event name</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="w-[100px] px-5 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse (($names ?? collect()) as $name)
                            @php $isActive = (bool) ($name->event_name_is_active ?? false); @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-slate-800">{{ $name->event_name_label }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    @if ($isActive)
                                        <span class="inline-flex rounded-md bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700">Active</span>
                                    @else
                                        <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600">Archived</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-1.5">
                                        @if ($isActive)
                                            <form
                                                method="POST"
                                                action="{{ route('admin.borrowing.event-names.archive', $name->event_name_id) }}"
                                                data-pur-confirm="This event name will be hidden from the Borrow Equipment picker."
                                                data-pur-confirm-title="Archive event name?"
                                                data-pur-confirm-ok="Archive"
                                                data-pur-confirm-kind="archive"
                                                class="inline"
                                            >
                                                @csrf
                                                <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#007a3f] transition hover:bg-slate-50" title="Archive" aria-label="Archive">
                                                    <i data-lucide="archive" class="h-4 w-4"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form
                                                method="POST"
                                                action="{{ route('admin.borrowing.event-names.restore', $name->event_name_id) }}"
                                                data-pur-confirm="This event name will appear again in the Borrow Equipment picker."
                                                data-pur-confirm-title="Restore event name?"
                                                data-pur-confirm-ok="Restore"
                                                data-pur-confirm-kind="archive"
                                                class="inline"
                                            >
                                                @csrf
                                                <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#0025cc] transition hover:bg-slate-50" title="Restore" aria-label="Restore">
                                                    <i data-lucide="archive-restore" class="h-4 w-4"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-16 text-center">
                                    <p class="text-sm font-medium text-slate-700">No event names yet</p>
                                    <p class="mt-1 text-xs text-slate-400">Add titles used when borrowing for school or city events.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Event types list --}}
            <div x-show="tab === 'types'" x-cloak class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-100 text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">
                            <th class="px-5 py-3">Event type</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="w-[100px] px-5 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse (($types ?? collect()) as $type)
                            @php $isActive = (bool) ($type->event_type_is_active ?? false); @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-slate-800">{{ $type->event_type_name }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    @if ($isActive)
                                        <span class="inline-flex rounded-md bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700">Active</span>
                                    @else
                                        <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600">Archived</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-1.5">
                                        @if ($isActive)
                                            <form
                                                method="POST"
                                                action="{{ route('admin.borrowing.event-types.archive', $type->event_type_id) }}"
                                                data-pur-confirm="This event type will be hidden from the Borrow Equipment picker."
                                                data-pur-confirm-title="Archive event type?"
                                                data-pur-confirm-ok="Archive"
                                                data-pur-confirm-kind="archive"
                                                class="inline"
                                            >
                                                @csrf
                                                <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#007a3f] transition hover:bg-slate-50" title="Archive" aria-label="Archive">
                                                    <i data-lucide="archive" class="h-4 w-4"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form
                                                method="POST"
                                                action="{{ route('admin.borrowing.event-types.restore', $type->event_type_id) }}"
                                                data-pur-confirm="This event type will appear again in the Borrow Equipment picker."
                                                data-pur-confirm-title="Restore event type?"
                                                data-pur-confirm-ok="Restore"
                                                data-pur-confirm-kind="archive"
                                                class="inline"
                                            >
                                                @csrf
                                                <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#0025cc] transition hover:bg-slate-50" title="Restore" aria-label="Restore">
                                                    <i data-lucide="archive-restore" class="h-4 w-4"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-16 text-center">
                                    <p class="text-sm font-medium text-slate-700">No event types yet</p>
                                    <p class="mt-1 text-xs text-slate-400">Add types used when borrowing for school or city events.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div
        id="addEventNameModal"
        class="fixed inset-0 z-[11000] hidden items-center justify-center bg-slate-950/40 p-4"
        onclick="if (event.target === this) this.classList.replace('flex','hidden')"
    >
        <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-950">Add event name</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Max 50 characters. Appears in the Borrow Equipment picker.</p>
                </div>
                <button type="button" onclick="document.getElementById('addEventNameModal')?.classList.replace('flex','hidden')" class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.borrowing.event-names.store') }}" class="space-y-4 px-5 py-5">
                @csrf
                <div>
                    <label for="newEventNameLabel" class="mb-1.5 block text-sm text-slate-600">Event name <span class="text-red-500">*</span></label>
                    <input
                        id="newEventNameLabel"
                        type="text"
                        name="name"
                        required
                        maxlength="50"
                        placeholder="e.g. City Founding Anniversary"
                        class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm outline-none ring-1 ring-slate-200/80 focus:ring-2 focus:ring-slate-900/10"
                    >
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                    <button type="button" onclick="document.getElementById('addEventNameModal')?.classList.replace('flex','hidden')" class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#001fa8]">
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div
        id="addEventTypeModal"
        class="fixed inset-0 z-[11000] hidden items-center justify-center bg-slate-950/40 p-4"
        onclick="if (event.target === this) this.classList.replace('flex','hidden')"
    >
        <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-950">Add event type</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Max 50 characters. Appears in the Borrow Equipment picker.</p>
                </div>
                <button type="button" onclick="document.getElementById('addEventTypeModal')?.classList.replace('flex','hidden')" class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.borrowing.event-types.store') }}" class="space-y-4 px-5 py-5">
                @csrf
                <div>
                    <label for="newEventTypeName" class="mb-1.5 block text-sm text-slate-600">Event type name <span class="text-red-500">*</span></label>
                    <input
                        id="newEventTypeName"
                        type="text"
                        name="name"
                        required
                        maxlength="50"
                        placeholder="e.g. Inter-school competition"
                        class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm outline-none ring-1 ring-slate-200/80 focus:ring-2 focus:ring-slate-900/10"
                    >
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                    <button type="button" onclick="document.getElementById('addEventTypeModal')?.classList.replace('flex','hidden')" class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-[#0025cc] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#001fa8]">
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
