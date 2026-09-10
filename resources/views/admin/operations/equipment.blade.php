@extends('layouts.admin-layout')

@section('title', 'Equipment Monitor')

@section('content')
<div class="admin-page space-y-6">
    <!--<div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="admin-page-title">Equipment Monitor</h1>
            <p class="admin-page-subtitle">See inventory status, maintenance needs, replacements, and lifecycle alerts ({{ $usefulLifeYears }}-year useful life).</p>
        </div>
        <a href="{{ route('admin.operations.overview') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">← Operations</a>
    </div>-->

    @if($lifecycleAlerts->isNotEmpty())
    <div class="rounded-[18px] border border-amber-200 bg-amber-50 px-5 py-4">
        <h2 class="text-sm font-bold text-amber-950">Lifecycle broadcasts</h2>
        <p class="mt-1 text-xs text-amber-800">Equipment within 1 year of the {{ $usefulLifeYears }}-year replacement horizon (or already due).</p>
        <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($lifecycleAlerts as $alert)
                <div class="rounded-xl border border-amber-200 bg-white px-3 py-2 text-sm">
                    <p class="font-semibold text-slate-900">{{ $alert->equipment_name }}</p>
                    <p class="text-xs text-slate-500">{{ $alert->room_name ?: 'No room' }} · Age {{ (int) $alert->age_years }}y ·
                        @if((int) $alert->years_remaining < 0)
                            overdue for replacement
                        @elseif((int) $alert->years_remaining === 0)
                            replace this year
                        @else
                            ~{{ (int) $alert->years_remaining }} year{{ (int) $alert->years_remaining === 1 ? '' : 's' }} left
                        @endif
                    </p>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="overflow-hidden rounded-[18px] border border-gray-200 bg-white">
        <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 px-5 py-4">
            <form method="GET" class="relative min-w-[220px] flex-1">
                <input type="hidden" name="filter" value="{{ $filter }}">
                <input type="text" name="q" value="{{ $q }}" placeholder="Search equipment..." class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm outline-none">
            </form>
            <div class="flex flex-wrap gap-1">
                @foreach(['all' => 'All', 'maintenance' => 'Needs maintenance', 'replacement' => 'For replacement', 'damaged' => 'Damaged', 'lifecycle' => 'Lifecycle'] as $key => $label)
                    <a href="{{ route('admin.operations.equipment', ['filter' => $key, 'q' => $q]) }}"
                       class="rounded-lg px-3 py-2 text-xs font-semibold {{ $filter === $key ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Equipment</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Room</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Condition</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Inventory</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Warranty</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-semibold text-slate-900">{{ $row->equipment_name }}</p>
                                <p class="text-xs text-slate-500">{{ $row->equipment_category_name ?: 'Uncategorized' }} · Qty {{ $row->equipment_quantity }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $row->room_name ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-slate-700">{{ $row->equipment_condition_status ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-slate-700">{{ $row->equipment_inventory_status ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-slate-500">{{ $row->equipment_warranty_expiration ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-16 text-center text-sm text-gray-400">No equipment found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($rows, 'links'))
            <div class="border-t border-gray-100 px-5 py-4">{{ $rows->links() }}</div>
        @endif
    </div>
</div>
@endsection
