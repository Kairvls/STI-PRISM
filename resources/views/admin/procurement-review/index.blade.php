@extends('layouts.admin-layout')

{{-- ===================================================== --}}
{{-- ADDED RIS ADMIN APPROVAL: PAGE CONTENT --}}
{{-- ===================================================== --}}

@section('content')

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">RIS Approval</h1>
        <p class="mt-1 text-sm text-gray-600">Submitted RIS records from Purchaser appear here for Admin approval.</p>
    </div>

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

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px]">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">RIS No.</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Request</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Equipment</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Requested By</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($risRecords as $ris)
                        <tr>
                            <td class="px-4 py-4 text-sm font-medium text-gray-900">
                                {{ $ris->ris_form_number ?? 'RIS-' . $ris->ris_id }}
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600">
                                Request #{{ $ris->procurement_request_id ?? 'N/A' }}<br>
                                <span class="text-xs text-gray-400">Report #{{ $ris->report_id ?? 'N/A' }}</span>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600">
                                {{ $ris->equipment_name ?? $ris->report_unlisted_equipment_name ?? 'Unknown Equipment' }}
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600">
                                {{ $ris->ris_requested_by_signature ?? 'Purchaser' }}<br>
                                <span class="text-xs text-gray-400">{{ $ris->ris_requested_by_date }}</span>
                            </td>
                            <td class="px-4 py-4 text-sm">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold
                                    {{ $ris->ris_status === 'Approved' ? 'bg-green-100 text-green-700' : '' }}
                                    {{ $ris->ris_status === 'Rejected' ? 'bg-red-100 text-red-700' : '' }}
                                    {{ $ris->ris_status === 'Pending' ? 'bg-blue-100 text-blue-700' : '' }}
                                ">
                                    {{ $ris->ris_status === 'Pending' ? 'For Approval' : $ris->ris_status }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm">
                                <div class="flex flex-wrap gap-2">
                                    @if($ris->ris_status === 'Pending')
                                        <form method="POST" action="{{ route('admin.procurement-review.ris.approve', $ris->ris_id) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-green-600 px-3 py-2 text-xs font-medium text-white">
                                                Approve
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.procurement-review.ris.reject', $ris->ris_id) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-red-600 px-3 py-2 text-xs font-medium text-white">
                                                Reject
                                            </button>
                                        </form>
                                    @endif

                                    <a
                                        href="{{ route('admin.procurement-review.ris.print', $ris->ris_id) }}"
                                        target="_blank"
                                        class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700"
                                    >
                                        View Form
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-500">
                                No submitted RIS records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        {{ $risRecords->links() }}
    </div>
</div>

@endsection

