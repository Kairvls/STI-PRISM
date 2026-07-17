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

    {{-- Filter Buttons --}}
    @php
        $filter = $filter ?? 'all';
    @endphp
    <div class="flex gap-2">
        <a href="{{ route('admin.procurement-review') }}?filter=all" 
           class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $filter === 'all' ? 'bg-blue-600 text-white' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}">
            All
        </a>
        <a href="{{ route('admin.procurement-review') }}?filter=approved" 
           class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $filter === 'approved' ? 'bg-green-600 text-white' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}">
            Approved
        </a>
        <a href="{{ route('admin.procurement-review') }}?filter=rejected" 
           class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $filter === 'rejected' ? 'bg-red-600 text-white' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}">
            Rejected
        </a>
    </div>

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
                        <tr class="{{ $ris->ris_status !== 'Pending' ? 'bg-gray-50 opacity-60' : '' }}">
                            <td class="px-4 py-4 text-sm font-medium {{ $ris->ris_status !== 'Pending' ? 'text-gray-600' : 'text-gray-900' }}">
                                {{ $ris->ris_form_number ?? 'RIS-' . $ris->ris_id }}
                            </td>
                            <td class="px-4 py-4 text-sm {{ $ris->ris_status !== 'Pending' ? 'text-gray-500' : 'text-gray-600' }}">
                                Request #{{ $ris->procurement_request_id ?? 'N/A' }}<br>
                                <span class="text-xs text-gray-400">Report #{{ $ris->report_id ?? 'N/A' }}</span>
                            </td>
                            <td class="px-4 py-4 text-sm {{ $ris->ris_status !== 'Pending' ? 'text-gray-500' : 'text-gray-600' }}">
                                {{ $ris->equipment_name ?? $ris->report_unlisted_equipment_name ?? 'Unknown Equipment' }}
                            </td>
                            <td class="px-4 py-4 text-sm {{ $ris->ris_status !== 'Pending' ? 'text-gray-500' : 'text-gray-600' }}">
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
                                            <button type="submit" class="rounded-lg bg-green-600 px-3 py-2 text-xs font-medium text-white hover:bg-green-700">
                                                Approve
                                            </button>
                                        </form>
                                    @endif

                                    <button
                                        type="button"
                                        class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-medium text-white hover:bg-blue-700"
                                        onclick="openRisPreviewModal('{{ $ris->ris_id }}')"
                                    >
                                        Preview
                                    </button>

                                    <a
                                        href="{{ route('admin.procurement-review.ris.print', $ris->ris_id) }}"
                                        target="_blank"
                                        class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium {{ $ris->ris_status !== 'Pending' ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-50' }}"
                                        {{ $ris->ris_status !== 'Pending' ? 'disabled' : '' }}
                                    >
                                        View Form
                                    </a>

                                    @if($ris->ris_status === 'Pending')
                                        <form method="POST" action="{{ route('admin.procurement-review.ris.reject', $ris->ris_id) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-red-600 px-3 py-2 text-xs font-medium text-white hover:bg-red-700">
                                                Reject
                                            </button>
                                        </form>
                                    @endif
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

{{-- ===================================================== --}}
{{-- RIS PREVIEW MODAL --}}
{{-- ===================================================== --}}
<div id="risPreviewModal" class="fixed inset-0 z-50 hidden">
    <div class="flex h-screen items-center justify-center bg-black/30 p-2 backdrop-blur-[2px]" onclick="closeRisPreviewModal()">
        <div class="w-full max-w-6xl h-[calc(100vh-1rem)] overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]" onclick="event.stopPropagation()">
            <div class="border-b border-gray-100 px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-950">RIS Form Preview</h3>
                        <p id="risPreviewModalSubtitle" class="mt-1 text-sm text-slate-600">Requisition and Issue Slip</p>
                    </div>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" onclick="closeRisPreviewModal()" aria-label="Close">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="h-full overflow-auto bg-gray-50">
                <iframe id="risPreviewIframe" class="w-full h-full" style="min-height: calc(100vh - 140px);" src="about:blank"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
    function openRisPreviewModal(risId) {
        const modal = document.getElementById('risPreviewModal');
        const iframe = document.getElementById('risPreviewIframe');
        const subtitle = document.getElementById('risPreviewModalSubtitle');
        
        if (!modal || !iframe) return;

        subtitle.textContent = `RIS #${risId}`;
        
        // Load the printable RIS form with cache-buster
        iframe.src = `/admin/procurement-review/ris/${risId}/print?ts=${Date.now()}`;

        // Ensure the modal is visible after setting src
        modal.classList.remove('hidden');
    }

    function closeRisPreviewModal() {
        const modal = document.getElementById('risPreviewModal');
        const iframe = document.getElementById('risPreviewIframe');
        
        if (iframe) iframe.src = 'about:blank';
        if (modal) modal.classList.add('hidden');
    }
</script>

@endsection

