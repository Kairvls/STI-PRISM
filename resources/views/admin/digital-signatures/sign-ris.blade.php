@extends('layouts.admin-layout')

@section('title', 'Sign RIS')

@section('content')

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Sign RIS</h1>
        <p class="mt-1 text-sm text-gray-600">Review and digitally sign approved Requisition and Issue Slips from the President.</p>
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
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($signableRisRecords as $ris)
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
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-medium text-white hover:bg-blue-700"
                                        onclick="openRisPreviewModal('{{ $ris->ris_id }}')"
                                    >
                                        Preview
                                    </button>

                                    @if($ris->ris_status === 'Approved')
                                        <button
                                            type="button"
                                            class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-medium text-white hover:bg-emerald-700"
                                            onclick="openSignatureModal('{{ $ris->ris_id }}', 'approve')"
                                        >
                                            Approve
                                        </button>

                                        <button
                                            type="button"
                                            class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-medium text-white hover:bg-rose-700"
                                            onclick="openSignatureModal('{{ $ris->ris_id }}', 'reject')"
                                        >
                                            Reject
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-sm text-gray-500">
                                No RIS records available for signing.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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

{{-- ===================================================== --}}
{{-- SIGNATURE DECISION MODAL --}}
{{-- ===================================================== --}}
<div id="signatureModal" class="fixed inset-0 z-50 hidden">
    <div class="flex min-h-screen items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]" onclick="closeSignatureModal()">
        <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]" onclick="event.stopPropagation()">
            <div class="border-b border-gray-100 px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 id="signatureModalTitle" class="text-lg font-bold text-slate-950">Approve RIS</h3>
                        <p id="signatureModalSubtitle" class="mt-1 text-sm text-slate-600">Sign and submit your decision</p>
                    </div>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" onclick="closeSignatureModal()" aria-label="Close">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <form id="signatureForm" method="POST" action="">
                @csrf
                <div class="px-6 py-5">
                    <input type="hidden" name="target_id" id="targetId" value="" />
                    <input type="hidden" name="decision" id="targetDecision" value="" />
                    <input type="hidden" name="signature_data" id="signatureData" value="" />
                    <input type="hidden" name="signature_used" id="signatureUsed" value="0" />

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Remarks (optional)</label>
                            <textarea name="remarks" rows="3" placeholder="Add remarks for your decision..." class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">Sign here (mouse / touch)</label>
                            <div class="mt-2 rounded-lg border border-gray-200 bg-white p-2">
                                <canvas
                                    id="signatureCanvas"
                                    width="520"
                                    height="150"
                                    class="w-full rounded-md border border-gray-100 bg-white"
                                    style="touch-action: none;"
                                ></canvas>

                                <div class="mt-3 flex items-center justify-between gap-2">
                                    <button type="button" class="rounded-lg bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 border border-slate-200 hover:bg-slate-100" onclick="clearSignature()">
                                        Clear
                                    </button>
                                    <span id="signatureStatus" class="text-xs text-slate-500"></span>
                                </div>

                                <div class="mt-4 hidden" id="signatureHelpText">
                                    <p class="text-sm text-slate-600 font-medium">✓ Signature captured and ready</p>
                                </div>
                            </div>
                        </div>

                        <div class="text-xs text-slate-500">
                            By signing, you confirm your decision on this RIS request.
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-100 px-6 py-4">
                    <button type="button" class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950" onclick="closeSignatureModal()">Cancel</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700">Submit Decision</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // ===================================================== 
    // RIS PREVIEW MODAL FUNCTIONS
    // ===================================================== 
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

    // ===================================================== 
    // SIGNATURE MODAL FUNCTIONS
    // ===================================================== 
    function openSignatureModal(risId, action) {
        const modal = document.getElementById('signatureModal');
        const title = document.getElementById('signatureModalTitle');
        const subtitle = document.getElementById('signatureModalSubtitle');
        const targetId = document.getElementById('targetId');
        const targetDecision = document.getElementById('targetDecision');
        const form = document.getElementById('signatureForm');
        const signatureCanvas = document.getElementById('signatureCanvas');
        const signatureData = document.getElementById('signatureData');
        const signatureUsed = document.getElementById('signatureUsed');

        // Reset canvas
        if (signatureCanvas) {
            const ctx = signatureCanvas.getContext('2d');
            ctx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
        }

        // Reset form fields
        signatureData.value = '';
        signatureUsed.value = '0';
        document.querySelector('textarea[name="remarks"]').value = '';

        // Set modal values
        targetId.value = risId;
        targetDecision.value = action === 'approve' ? 'Approved' : 'Rejected';

        // Update title and subtitle
        if (action === 'approve') {
            title.textContent = 'Approve RIS';
            subtitle.textContent = `Sign and approve this RIS #${risId}`;
            form.action = `/admin/digital-signatures/ris/decide`;
        } else {
            title.textContent = 'Reject RIS';
            subtitle.textContent = `Sign and reject this RIS #${risId}`;
            form.action = `/admin/digital-signatures/ris/decide`;
        }

        // Show modal
        modal.classList.remove('hidden');
    }

    function closeSignatureModal() {
        const modal = document.getElementById('signatureModal');
        if (modal) modal.classList.add('hidden');
    }

    function clearSignature() {
        const canvas = document.getElementById('signatureCanvas');
        const signatureData = document.getElementById('signatureData');
        const signatureUsed = document.getElementById('signatureUsed');
        const signatureHelpText = document.getElementById('signatureHelpText');
        
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        signatureData.value = '';
        signatureUsed.value = '0';
        
        if (signatureHelpText) signatureHelpText.classList.add('hidden');
        updateSignatureStatus();
    }

    function updateSignatureStatus() {
        const status = document.getElementById('signatureStatus');
        const signatureUsed = document.getElementById('signatureUsed');
        
        if (signatureUsed.value === '1') {
            status.textContent = '✓ Signature ready';
            status.className = 'text-xs text-green-600 font-medium';
        } else {
            status.textContent = 'Draw your signature';
            status.className = 'text-xs text-slate-500';
        }
    }

    // ===================================================== 
    // SIGNATURE CANVAS - Transparent PNG
    // ===================================================== 
    (function initSignatureCanvas() {
        const canvas = document.getElementById('signatureCanvas');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        
        // Clear to transparent
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Styling
        ctx.lineWidth = 2.5;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#1f2937';

        let drawing = false;
        let lastX = 0;
        let lastY = 0;

        function getPos(evt) {
            const rect = canvas.getBoundingClientRect();
            const clientX = evt.touches ? evt.touches[0].clientX : evt.clientX;
            const clientY = evt.touches ? evt.touches[0].clientY : evt.clientY;
            const x = (clientX - rect.left) * (canvas.width / rect.width);
            const y = (clientY - rect.top) * (canvas.height / rect.height);
            return { x, y };
        }

        function start(evt) {
            drawing = true;
            const p = getPos(evt);
            lastX = p.x;
            lastY = p.y;
        }

        function move(evt) {
            if (!drawing) return;
            const p = getPos(evt);
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            lastX = p.x;
            lastY = p.y;
        }

        function end() {
            drawing = false;
            captureAndStoreSignature();
        }

        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        canvas.addEventListener('mouseup', end);
        canvas.addEventListener('mouseleave', end);

        canvas.addEventListener('touchstart', (e) => { e.preventDefault(); start(e); }, { passive: false });
        canvas.addEventListener('touchmove', (e) => { e.preventDefault(); move(e); }, { passive: false });
        canvas.addEventListener('touchend', end);
    })();

    // Form submission handler
    document.getElementById('signatureForm')?.addEventListener('submit', function (event) {
        const signatureUsed = document.getElementById('signatureUsed')?.value;

        if (signatureUsed !== '1') {
            event.preventDefault();
            alert('Please sign before submitting your decision.');
            return false;
        }
    });

    // Capture signature as transparent PNG
    function captureAndStoreSignature() {
        const canvas = document.getElementById('signatureCanvas');
        const signatureData = document.getElementById('signatureData');
        const signatureUsed = document.getElementById('signatureUsed');
        const signatureHelpText = document.getElementById('signatureHelpText');
        
        if (!canvas || !signatureData) return;

        // Check if signature is empty
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const data = imageData.data;
        let hasDrawn = false;
        
        for (let i = 3; i < data.length; i += 4) {
            if (data[i] > 128) {
                hasDrawn = true;
                break;
            }
        }

        if (!hasDrawn) return;

        // Export as transparent PNG
        const dataUrl = canvas.toDataURL('image/png');
        signatureData.value = dataUrl;
        signatureUsed.value = '1';
        if (signatureHelpText) signatureHelpText.classList.remove('hidden');
        updateSignatureStatus();
        
        return true;
    }
</script>

@endsection