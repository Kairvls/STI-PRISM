@extends('layouts.maintenance-layout')

@section('title', 'Equipment History')

@section('content')
<style>[x-cloak]{display:none!important}</style>
<div class="space-y-6">
    <form method="GET" class="flex flex-wrap items-center gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search equipment, room, or category" class="h-10 w-full max-w-md rounded-xl border-0 bg-slate-50 px-3.5 text-sm ring-1 ring-slate-200/80 outline-none focus:bg-white focus:ring-2 focus:ring-slate-900/10" />
        <button class="h-10 rounded-xl bg-neutral-100 px-4 text-sm font-medium text-slate-700 ring-1 ring-slate-200/80">Search</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-[12px] font-bold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-5 py-3">Equipment</th>
                    <th class="px-5 py-3">Room</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Reports</th>
                    <th class="px-5 py-3">Last reported</th>
                    <th class="px-5 py-3 text-right">History</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($equipment as $item)
                    <tr class="border-t border-slate-100" x-data="{ open: false }">
                        <td class="px-5 py-4">
                            <p class="font-semibold text-slate-900">{{ $item->equipment_name }}</p>
                            <p class="text-xs text-slate-400">{{ $item->equipment_category_name ?? 'Uncategorized' }}</p>
                        </td>
                        <td class="px-5 py-4 text-slate-500">{{ $item->room_name ?? '—' }}</td>
                        <td class="px-5 py-4">
                            <span class="rounded-md bg-slate-50 px-2 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200/80">
                                {{ $item->equipment_inventory_status }}
                            </span>
                        </td>
                        <td class="px-5 py-4 font-medium text-slate-800">{{ $item->report_count }}</td>
                        <td class="px-5 py-4 text-slate-500">
                            {{ $item->last_reported_at ? \Carbon\Carbon::parse($item->last_reported_at)->format('M d, Y') : '—' }}
                        </td>
                        <td class="px-5 py-4 text-right">
                            <button type="button" @click="open = true" class="rounded-lg px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">View</button>
                            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-[#0b1220]/70 p-4">
                                <div class="max-h-[88vh] w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                                    <div class="flex items-start justify-between px-6 pt-6">
                                        <div>
                                            <p class="text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400">Equipment</p>
                                            <h2 class="mt-1 text-lg font-semibold text-slate-900">{{ $item->equipment_name }}</h2>
                                            <p class="mt-1 text-sm text-slate-500">{{ $item->room_name ?? 'No room' }}</p>
                                        </div>
                                        <button type="button" @click="open = false" class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100">
                                            <i data-lucide="x" class="h-4 w-4"></i>
                                        </button>
                                    </div>
                                    <div class="mt-4 max-h-[55vh] space-y-2 overflow-y-auto px-6 pb-6">
                                        @forelse ($item->history as $row)
                                            <div class="rounded-xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200/70">
                                                <div class="flex items-center justify-between gap-3">
                                                    <p class="text-sm font-semibold text-slate-900">#{{ $row->report_id }} · {{ $row->report_suggested_issue ?: 'No issue named' }}</p>
                                                    <span class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($row->report_submitted_at)->format('M d, Y') }}</span>
                                                </div>
                                                <p class="mt-1 text-xs text-slate-500">{{ $row->reporter_full_name ?? 'Unknown reporter' }} · {{ $row->report_current_status }} · {{ $row->report_urgency_level }}</p>
                                            </div>
                                        @empty
                                            <p class="py-8 text-center text-sm text-slate-500">No reports yet for this equipment.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-16 text-center text-sm text-slate-500">No equipment history found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $equipment->links() }}</div>
</div>
@endsection
