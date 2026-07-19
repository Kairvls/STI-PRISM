@extends('layouts.admin-layout')

@section('title', 'Signature History')

@section('content')

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Signature History</h1>
        <p class="mt-1 text-sm text-gray-600">View all RIS records that have been digitally signed.</p>
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
            <table class="w-full min-w-[1000px]">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">RIS No.</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Request</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Equipment</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Requested By</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Signed Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($signatureHistory as $history)
                        <tr>
                            <td class="px-4 py-4 text-sm font-medium text-gray-900">
                                {{ $history->ris_form_number ?? 'RIS-' . $history->ris_id }}
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600">
                                Request #{{ $history->procurement_request_id ?? 'N/A' }}<br>
                                <span class="text-xs text-gray-400">Report #{{ $history->report_id ?? 'N/A' }}</span>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600">
                                {{ $history->equipment_name ?? $history->report_unlisted_equipment_name ?? 'Unknown Equipment' }}
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600">
                                {{ $history->ris_requested_by_signature ?? 'Purchaser' }}<br>
                                <span class="text-xs text-gray-400">{{ $history->ris_requested_by_date }}</span>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600">
                                <span class="text-green-700 font-medium">{{ $history->ris_issued_by_date }}</span>
                            </td>
                            <td class="px-4 py-4 text-sm">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-medium text-white hover:bg-blue-700"
                                        onclick="openRisPreviewModal('{{ $history->ris_id }}')"
                                    >
                                        Preview
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-500">
                                No signed RIS records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        {{ $signatureHistory->links() }}
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
        iframe.src = `/admin/procurement-review/ris/${risId}/print?ts=${Date.now()}`;
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