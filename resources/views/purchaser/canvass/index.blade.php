@extends($procurementLayout ?? 'layouts.purchaser-layout')

@section('page-title', 'Purchase Canvass')
@section('page-subtitle', 'Submit one request with multiple items. Each item has its own supplier and brand quotations, and School Admin approves each item independently.')

@section('content')
@php
    $routePrefix = $pp ?? 'purchaser';
    $statuses = \App\Support\CanvassWorkflow::statuses();
@endphp

<div class="space-y-5">
    {{-- Top bar: Active / Archive tabs + New Canvass CTA --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
            <a
                href="{{ route($routePrefix.'.canvass.index') }}"
                class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-semibold transition {{ ! $archiveView ? 'bg-[#0025cc] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
            >
                <i data-lucide="scale" class="h-4 w-4"></i>
                <span>Active</span>
                <span class="rounded-full px-1.5 py-0.5 text-[11px] font-bold {{ ! $archiveView ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $activeCount }}</span>
            </a>
            <a
                href="{{ route($routePrefix.'.canvass.index', ['view' => 'archive']) }}"
                class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-semibold transition {{ $archiveView ? 'bg-[#0025cc] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"
            >
                <i data-lucide="archive" class="h-4 w-4"></i>
                <span>Archive</span>
                <span class="rounded-full px-1.5 py-0.5 text-[11px] font-bold {{ $archiveView ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $archiveCount }}</span>
            </a>
        </div>

        <a
            href="{{ route($routePrefix.'.canvass.create') }}"
            class="inline-flex items-center gap-2 rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#001db3]"
        >
            <i data-lucide="plus" class="h-4 w-4"></i>
            <span>New Canvass</span>
        </a>
    </div>

    {{-- Filters + Table Card --}}
    <div class="pur-card overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form
            method="GET"
            action="{{ route($routePrefix.'.canvass.index') }}"
            class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4"
        >
            @if ($archiveView)
                <input type="hidden" name="view" value="archive">
            @endif
            <div class="flex items-center gap-2">
                <h2 class="text-base font-semibold text-slate-900">
                    {{ $archiveView ? 'Archived Canvasses' : 'Submitted & Draft Canvass Requests' }}
                </h2>
                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                    {{ $canvasses->total() }}
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search CAN no., item, PR..."
                        class="w-56 rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-900 outline-none focus:border-blue-500 focus:bg-white"
                    >
                </div>

                @unless ($archiveView)
                    <select
                        name="status"
                        class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 outline-none focus:border-blue-500 focus:bg-white"
                    >
                        <option value="">All statuses</option>
                        @foreach ($statuses as $st)
                            <option value="{{ $st }}" @selected($statusFilter === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                @endunless

                <button
                    type="submit"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#001db3]"
                >
                    <i data-lucide="filter" class="h-3.5 w-3.5"></i>
                    <span>Apply</span>
                </button>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/80 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Request No.</th>
                        <th class="px-4 py-3.5">Date Submitted</th>
                        <th class="px-4 py-3.5">Items</th>
                        <th class="px-4 py-3.5">Suppliers</th>
                        <th class="px-4 py-3.5 text-right">Lowest Est. Total</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5">Request Details</th>
                        <th class="px-5 py-3.5 text-right">Edit / Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($canvasses as $row)
                        @php
                            $badge = \App\Support\CanvassWorkflow::statusBadge((string) $row->canvass_status);
                            $editable = \App\Support\CanvassWorkflow::canEdit($row);
                            $canCreateRis = \App\Support\CanvassWorkflow::canConvertToRis($row);
                            $displayNo = \App\Support\CanvassWorkflow::displayNumber($row);
                        @endphp
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-5 py-3.5 align-middle">
                                <div class="font-semibold text-slate-900">{{ $displayNo }}</div>
                                @if (! empty($row->canvass_request_no))
                                    <div class="mt-0.5 text-xs font-medium text-blue-700">{{ $row->canvass_request_no }}</div>
                                @endif
                                @if (! empty($row->linked_ris_number))
                                    <div class="mt-0.5 text-[11px] text-teal-700">RIS: {{ $row->linked_ris_number }}</div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 align-middle text-xs text-slate-600">
                                @if ($row->canvass_submitted_at)
                                    {{ \Carbon\Carbon::parse($row->canvass_submitted_at)->format('Y-m-d H:i:s') }}
                                @else
                                    <span class="text-slate-400">Not submitted</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 align-middle">
                                <span class="font-semibold text-slate-900">{{ $row->item_count }}</span>
                                @if (! empty($row->first_item_name))
                                    <div class="max-w-[200px] truncate text-xs text-slate-500">
                                        {{ $row->first_item_name }}{{ $row->item_count > 1 ? ' + '.($row->item_count - 1).' more' : '' }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 align-middle text-slate-700">
                                {{ $row->supplier_count }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-right align-middle font-semibold text-slate-900">
                                ₱{{ number_format((float) $row->lowest_total_sum, 2) }}
                            </td>
                            <td class="px-4 py-3.5 align-middle">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $badge['class'] }}">
                                    {{ $badge['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 align-middle">
                                <a
                                    href="{{ route($routePrefix.'.canvass.show', $row->canvass_id) }}"
                                    class="text-xs font-semibold text-blue-600 underline-offset-2 hover:underline"
                                >
                                    View items
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right align-middle">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    @if (! empty($row->canvass_ris_id))
                                        <a
                                            href="{{ route($routePrefix.'.ris.index', ['view_ris' => $row->canvass_ris_id]) }}"
                                            class="inline-flex items-center gap-1 rounded-lg bg-teal-600 px-2.5 py-1.5 text-xs font-semibold text-white transition hover:bg-teal-700"
                                            title="Open generated RIS record"
                                        >
                                            <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                            <span>View RIS</span>
                                        </a>
                                    @elseif ($canCreateRis)
                                        <a
                                            href="{{ route($routePrefix.'.ris.index', ['from_canvass' => $row->canvass_id]) }}"
                                            class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700"
                                            title="Auto-fill a new RIS from approved canvass offers"
                                        >
                                            <i data-lucide="file-plus-2" class="h-3.5 w-3.5"></i>
                                            <span>Create RIS</span>
                                        </a>
                                    @endif

                                    @if (! $archiveView)
                                        @if ($editable)
                                            <a
                                                href="{{ route($routePrefix.'.canvass.edit', $row->canvass_id) }}"
                                                class="inline-flex items-center rounded-lg border border-blue-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-50"
                                            >
                                                Edit
                                            </a>
                                        @else
                                            <span
                                                class="inline-flex cursor-not-allowed items-center rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-medium text-slate-400"
                                                title="Locked once review starts or is approved"
                                            >
                                                Locked
                                            </span>
                                        @endif

                                        <form method="POST" action="{{ route($routePrefix.'.canvass.archive', $row->canvass_id) }}" class="inline">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                                                title="Archive canvass"
                                                onclick="return confirm('Archive this canvass?')"
                                            >
                                                <i data-lucide="archive" class="h-3.5 w-3.5"></i>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route($routePrefix.'.canvass.restore', $row->canvass_id) }}" class="inline">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                            >
                                                Restore
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                                    <i data-lucide="scale" class="h-6 w-6"></i>
                                </div>
                                <p class="mt-3 text-sm font-semibold text-slate-900">
                                    {{ $archiveView ? 'No archived canvasses' : 'No purchase canvasses yet' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Create a canvass to compare 2–3 suppliers and up to 10 brand offers per item before generating an RIS.
                                </p>
                                @unless ($archiveView)
                                    <a
                                        href="{{ route($routePrefix.'.canvass.create') }}"
                                        class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-[#0025cc] px-4 py-2 text-xs font-semibold text-white hover:bg-[#001db3]"
                                    >
                                        <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                                        <span>Create first canvass</span>
                                    </a>
                                @endunless
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($canvasses->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">
                {{ $canvasses->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
