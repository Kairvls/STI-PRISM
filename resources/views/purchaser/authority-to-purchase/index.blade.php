@extends('layouts.purchaser-layout')

@section('page-title', 'Authority to Purchase')
@section('page-subtitle', 'Manage ATP drafts, submissions, approvals, and archives')

@section('content')

<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Authority to Purchase</h2>
            <p class="text-sm text-slate-600">Create ATP only from approved RIS and track its approval lifecycle.</p>
        </div>

        <a href="{{ route('purchaser.atp.create') }}" class="h-10 rounded-lg bg-blue-600 px-5 text-sm font-medium text-white">New ATP</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm font-medium text-gray-500">Draft</p>
            <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $atpSummary['draft'] }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm font-medium text-gray-500">Submitted</p>
            <p class="mt-3 text-3xl font-semibold text-blue-600">{{ $atpSummary['submitted'] }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm font-medium text-gray-500">Approved</p>
            <p class="mt-3 text-3xl font-semibold text-green-600">{{ $atpSummary['approved'] }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <p class="text-sm font-medium text-gray-500">Rejected</p>
            <p class="mt-3 text-3xl font-semibold text-red-600">{{ $atpSummary['rejected'] }}</p>
        </div>
    </div>

    <form method="GET" class="grid gap-3 rounded-lg border border-gray-200 bg-white p-4 lg:grid-cols-5">
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search ATP, RIS, supplier, or equipment"
            class="h-10 rounded-lg border border-gray-300 px-3 text-sm lg:col-span-2"
        >

        <select name="status" class="h-10 rounded-lg border border-gray-300 px-3 text-sm">
            <option value="">All statuses</option>
            <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
            <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Approved</option>
            <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Rejected</option>
        </select>

        <select name="request_type" class="h-10 rounded-lg border border-gray-300 px-3 text-sm">
            <option value="">All RIS types</option>
            <option value="Replacement Procurement" {{ request('request_type') === 'Replacement Procurement' ? 'selected' : '' }}>Replacement Procurement</option>
            <option value="New Procurement" {{ request('request_type') === 'New Procurement' ? 'selected' : '' }}>New Procurement</option>
        </select>

        <div class="flex gap-2">
            <button type="submit" class="h-10 rounded-lg bg-gray-900 px-5 text-sm font-medium text-white">Search</button>
            <a href="{{ route('purchaser.atp.index') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700">Reset</a>
        </div>
    </form>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1120px] text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">ATP No.</th>
                        <th class="px-4 py-3">RIS</th>
                        <th class="px-4 py-3">Supplier</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($atps as $atp)
                        <tr>
                            <td class="px-4 py-4 font-medium text-slate-900">{{ $atp->authority_purchase_form_number ?? 'ATP-' . $atp->authority_purchase_id }}</td>
                            <td class="px-4 py-4 text-gray-600">
                                {{ $atp->ris_form_number ?? 'RIS-' . $atp->authority_purchase_ris_id }}<br>
                                <span class="text-xs text-gray-400">
                                    {{ $atp->equipment_name ?? $atp->report_unlisted_equipment_name ?? 'No equipment' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-gray-600">
                                @if($atp->supplier_store_type === 'Physical Store')
                                    {{ $atp->company_name ?? 'Physical supplier' }}
                                @else
                                    {{ $atp->shop_name ?? 'Online supplier' }}
                                @endif
                            </td>
                            <td class="px-4 py-4 text-gray-600">{{ optional(\Carbon\Carbon::parse($atp->authority_purchase_date))->format('M d, Y') ?? '—' }}</td>
                            <td class="px-4 py-4 text-sm">
                                @if($atp->authority_purchase_status === 'Approved')
                                    <span class="rounded-full bg-emerald-100 px-3 py-1 font-semibold text-emerald-700">Approved</span>
                                @elseif($atp->authority_purchase_status === 'Rejected')
                                    <span class="rounded-full bg-red-100 px-3 py-1 font-semibold text-red-700">Rejected</span>
                                @elseif($atp->authority_purchase_submitted_at)
                                    <span class="rounded-full bg-blue-100 px-3 py-1 font-semibold text-blue-700">Submitted</span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-3 py-1 font-semibold text-gray-700">Draft</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-sm">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('purchaser.atp.show', $atp->authority_purchase_id) }}" class="rounded-lg border border-blue-600 bg-blue-50 px-3 py-2 text-xs font-medium text-blue-600">View</a>

                                    @if(!$atp->authority_purchase_submitted_at && !$archiveView)
                                        <a href="{{ route('purchaser.atp.edit', $atp->authority_purchase_id) }}" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700">Edit</a>
                                        <form method="POST" action="{{ route('purchaser.atp.submit', $atp->authority_purchase_id) }}" class="inline-block">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-gray-900 px-3 py-2 text-xs font-medium text-white">Submit</button>
                                        </form>
                                    @endif

                                    @if($atp->authority_purchase_status === 'Pending' && $atp->authority_purchase_submitted_at && !$archiveView)
                                        <form method="POST" action="{{ route('purchaser.atp.approve', $atp->authority_purchase_id) }}" class="inline-block">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-emerald-100 px-3 py-2 text-xs font-medium text-emerald-700">Approve</button>
                                        </form>
                                        <a href="{{ route('purchaser.atp.show', $atp->authority_purchase_id) }}#reject" class="rounded-lg border border-red-300 px-3 py-2 text-xs font-medium text-red-700">Reject</a>
                                    @endif

                                    @if($archiveView)
                                        <form method="POST" action="{{ route('purchaser.atp.restore', $atp->authority_purchase_id) }}" class="inline-block">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-blue-50 px-3 py-2 text-xs font-medium text-blue-600">Restore</button>
                                        </form>
                                    @elseif(!$atp->authority_purchase_is_archived)
                                        <form method="POST" action="{{ route('purchaser.atp.archive', $atp->authority_purchase_id) }}" class="inline-block">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-gray-100 px-3 py-2 text-xs font-medium text-gray-700">Archive</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-500">No ATP records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        {{ $atps->links() }}
    </div>
</div>

@endsection
