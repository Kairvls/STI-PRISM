@extends('layouts.admin-layout')

@section('title', 'Procurement Monitor')

@section('content')
<div class="admin-page space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <!--<div>
            <h1 class="admin-page-title">Procurement Monitor</h1>
            <p class="admin-page-subtitle">Track every RIS and stage counts. Open a form to review or act from Procurement Requests.</p>
        </div>-->
        <div class="flex items-center gap-3">
            <a href="{{ url('/admin/procurement-review') }}" class="admin-btn-primary h-10">Open Procurement Requests</a>
            <!--<a href="{{ route('admin.operations.overview') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">← Operations</a>-->
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
        @foreach([
            'ris' => 'RIS forms',
            'atp' => 'ATP',
            'rfc' => 'Request for Check',
            'receiving' => 'Receiving',
            'liquidation' => 'Liquidation',
        ] as $key => $label)
            <div class="rounded-[18px] border border-gray-200 bg-white px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ $stageCounts[$key] ?? 0 }}</p>
            </div>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-[18px] border border-gray-200 bg-white">
        <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 px-5 py-4">
            <form method="GET" class="relative min-w-[220px] flex-1">
                <input type="hidden" name="filter" value="{{ $filter }}">
                <input type="text" name="q" value="{{ $q }}" placeholder="Search RIS number, purpose, submitter..." class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm outline-none">
            </form>
            <div class="flex flex-wrap gap-1">
                @foreach(['all' => 'All', 'Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected'] as $key => $label)
                    <a href="{{ route('admin.operations.procurement', ['filter' => $key, 'q' => $q]) }}"
                       class="rounded-lg px-3 py-2 text-xs font-semibold {{ $filter === $key ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">RIS</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Purpose</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Submitted by</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Amount</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                        <tr>
                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $row->ris_form_number ?: ('#'.$row->ris_id) }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($row->ris_purpose_description, 80) ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $row->created_by_name ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-slate-700">₱{{ number_format((float) ($row->ris_calculated_total ?? 0), 2) }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">{{ $row->ris_status ?: '—' }}</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ url('/admin/procurement-review') }}" class="text-xs font-semibold text-slate-700 hover:text-slate-950">Open review →</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-16 text-center text-sm text-gray-400">No procurement forms found.</td></tr>
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
