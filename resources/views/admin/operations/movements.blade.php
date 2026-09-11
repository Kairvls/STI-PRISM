@extends('layouts.admin-layout')

@section('title', 'Equipment Movements')

@section('content')
<div class="admin-page space-y-6">
    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <a href="{{ route('admin.operations.movements', ['tab' => 'transfers']) }}" class="rounded-[18px] border {{ $tab === 'transfers' ? 'border-slate-900' : 'border-gray-200' }} bg-white px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Transfers</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $counts['transfers'] }}</p>
        </a>
        <a href="{{ route('admin.operations.movements', ['tab' => 'borrowing', 'filter' => 'active']) }}" class="rounded-[18px] border {{ $tab === 'borrowing' ? 'border-slate-900' : 'border-gray-200' }} bg-white px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Active borrows</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $counts['borrowing_active'] }}</p>
            <p class="mt-1 text-xs text-rose-600">{{ $counts['borrowing_overdue'] }} overdue</p>
        </a>
        <a href="{{ route('admin.operations.movements', ['tab' => 'disposal']) }}" class="rounded-[18px] border {{ $tab === 'disposal' ? 'border-slate-900' : 'border-gray-200' }} bg-white px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Disposals</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $counts['disposal'] }}</p>
        </a>
        <a href="{{ route('admin.operations.equipment') }}" class="rounded-[18px] border border-gray-200 bg-white px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Inventory</p>
            <p class="mt-1 text-sm font-semibold text-slate-700">Open equipment monitor →</p>
        </a>
    </div>

    <div class="overflow-hidden rounded-[18px] border border-gray-200 bg-white">
        <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 px-5 py-4">
            <div class="flex flex-wrap gap-1">
                @foreach(['transfers' => 'Transfers', 'borrowing' => 'Borrowing', 'disposal' => 'Disposal'] as $key => $label)
                    <a href="{{ route('admin.operations.movements', ['tab' => $key, 'q' => $q]) }}"
                       class="rounded-lg px-3 py-2 text-xs font-semibold {{ $tab === $key ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $label }}</a>
                @endforeach
            </div>
            <form method="GET" class="relative min-w-[220px] flex-1">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <input type="hidden" name="filter" value="{{ $filter }}">
                <input type="text" name="q" value="{{ $q }}" placeholder="Search..." class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm outline-none">
            </form>
            @if($tab === 'transfers')
                <div class="flex flex-wrap gap-1">
                    @foreach(['all' => 'All', 'recent' => 'Last 30 days'] as $key => $label)
                        <a href="{{ route('admin.operations.movements', ['tab' => 'transfers', 'filter' => $key, 'q' => $q]) }}"
                           class="rounded-lg px-3 py-2 text-xs font-semibold {{ $filter === $key ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $label }}</a>
                    @endforeach
                </div>
            @elseif($tab === 'borrowing')
                <div class="flex flex-wrap gap-1">
                    @foreach(['all' => 'All', 'active' => 'Active', 'Overdue' => 'Overdue', 'Borrowed' => 'Borrowed', 'Returned' => 'Returned'] as $key => $label)
                        <a href="{{ route('admin.operations.movements', ['tab' => 'borrowing', 'filter' => $key, 'q' => $q]) }}"
                           class="rounded-lg px-3 py-2 text-xs font-semibold {{ $filter === $key ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $label }}</a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="overflow-x-auto">
            @if($tab === 'transfers')
                <table class="w-full min-w-[900px] text-left">
                    <thead class="border-b border-gray-200 bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Equipment</th>
                            <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">From</th>
                            <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">To</th>
                            <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Remarks</th>
                            <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">When</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($rows as $row)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-semibold text-slate-900">{{ $row->equipment_name ?: ('Equipment #'.$row->equipment_id) }}</p>
                                    <p class="text-xs text-slate-500">{{ $row->equipment_asset_tag ?: '' }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ $row->from_room_name ?: '—' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ $row->to_room_name ?: '—' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($row->remarks, 60) ?: '—' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-500">{{ $row->created_at ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-16 text-center text-sm text-gray-400">No transfers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @elseif($tab === 'borrowing')
                <table class="w-full min-w-[900px] text-left">
                    <thead class="border-b border-gray-200 bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Equipment</th>
                            <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Borrower</th>
                            <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Expected return</th>
                            <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                            <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Authorized by</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($rows as $row)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-semibold text-slate-900">{{ $row->equipment_name ?: '—' }}</p>
                                    <p class="text-xs text-slate-500">{{ $row->equipment_asset_tag ?: '' }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    {{ $row->borrowing_borrower_name ?: '—' }}
                                    <p class="text-xs text-slate-400">{{ $row->borrowing_borrower_department ?: '' }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ $row->borrowing_expected_return_date ?: '—' }}</td>
                                <td class="px-5 py-4">
                                    @php $status = $row->borrowing_status ?: '—'; @endphp
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset
                                        {{ $status === 'Overdue' ? 'bg-rose-50 text-rose-800 ring-rose-200' : ($status === 'Borrowed' ? 'bg-amber-50 text-amber-800 ring-amber-200' : 'bg-slate-100 text-slate-700 ring-slate-200') }}">
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ $row->borrowing_authorized_by ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-16 text-center text-sm text-gray-400">No borrowing records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <table class="w-full min-w-[900px] text-left">
                    <thead class="border-b border-gray-200 bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Equipment</th>
                            <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Category</th>
                            <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Reason</th>
                            <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Location</th>
                            <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Inventory</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($rows as $row)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-semibold text-slate-900">{{ $row->equipment_name ?: '—' }}</p>
                                    <p class="text-xs text-slate-500">{{ $row->equipment_asset_tag ?: '' }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ $row->equipment_category_name ?: '—' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ $row->disposal_reason ?: '—' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ $row->disposal_area_location ?: '—' }}</td>
                                <td class="px-5 py-4 text-sm text-slate-600">{{ $row->equipment_inventory_status ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-16 text-center text-sm text-gray-400">No disposal records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>

        @if(method_exists($rows, 'links'))
            <div class="border-t border-gray-100 px-5 py-4">{{ $rows->links() }}</div>
        @endif
    </div>
</div>
@endsection
