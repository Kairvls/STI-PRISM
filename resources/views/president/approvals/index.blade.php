@extends('layouts.president-layout')

@section('title', 'Approvals')

@section('content')

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Approvals</h1>
    <p class="mt-1 text-sm leading-6 text-gray-500">Review pending RIS requests, then approve or reject.</p>
    </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-4">

    {{-- ============================== --}}
    {{-- RIS APPROVALS --}}
    {{-- ============================== --}}
    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">RIS Approvals</h2>
                <p class="mt-1 text-xs text-gray-500">Pending Request Information Sheets</p>
            </div>
            <span class="inline-flex items-center rounded-lg bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 border border-amber-200">
                {{ $pendingRisCount ?? ($pendingRis->count() ?? 0) }} pending
            </span>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">RIS ID</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Form #</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Supplier</th>
                        <th class="px-2 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pendingRis as $ris)
                        @php
                            // Supplier name may not exist; keep blank-friendly.
                            $supplierName = $ris->supplier_name ?? null;
                        @endphp
                        <tr class="border-b border-gray-100 hover:bg-yellow-50/30 transition">
                            <td class="px-2 py-4 text-sm font-semibold text-gray-600">RIS#{{ $ris->ris_id }}</td>
                            <td class="px-2 py-4 text-sm text-gray-700">{{ $ris->ris_form_number ?? '—' }}</td>
                            <td class="px-2 py-4 text-sm text-gray-700">{{ $supplierName ?? '—' }}</td>
                            <td class="px-2 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex h-9 items-center justify-center rounded-lg bg-emerald-50 px-3 text-xs font-semibold text-emerald-700 border border-emerald-200 transition hover:bg-emerald-100"
                                        onclick="openDecisionModal('ris', '{{ $ris->ris_id }}', 'Approved')"
                                    >
                                        Approve
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex h-9 items-center justify-center rounded-lg bg-rose-50 px-3 text-xs font-semibold text-rose-700 border border-rose-200 transition hover:bg-rose-100"
                                        onclick="openDecisionModal('ris', '{{ $ris->ris_id }}', 'Rejected')"
                                    >
                                        Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-2 py-10 text-center">
                                <p class="text-sm font-semibold text-gray-800">No pending RIS approvals</p>
                                <p class="mt-1 text-xs text-gray-500">You're all caught up.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

</div>


{{-- ============================== --}}
{{-- DECISION MODAL (single, dynamic) --}}
{{-- ============================== --}}
<div id="decisionModal" class="fixed inset-0 z-50 hidden">
    <div class="flex min-h-screen items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]" onclick="closeDecisionModal()">
        <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]" onclick="event.stopPropagation()">
            <div class="border-b border-gray-100 px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-950">Decision</h3>
                        <p id="decisionModalSubtitle" class="mt-1 text-sm text-slate-600">Approve or reject the selected RIS</p>
                    </div>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" onclick="closeDecisionModal()" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>

            <form id="decisionForm" method="POST" action="">
                @csrf
                <div class="px-6 py-5">
                    <input type="hidden" name="target_type" id="targetType" value="" />
                    <input type="hidden" name="target_id" id="targetId" value="" />
                    <input type="hidden" name="decision" id="targetDecision" value="" />
                    {{-- In-memory signature capture (no persistence) --}}
                    <input type="hidden" name="signature_data" id="signatureData" value="" />

                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Remarks (optional)</label>
                            <textarea name="remarks" rows="4" placeholder="Add remarks for your RIS decision..." class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:ring-4 focus:ring-amber-100"></textarea>
                        </div>

                        <div id="signatureBlock" class="hidden">
                            <div class="mt-2">
                                <label class="block text-sm font-medium text-slate-700">Sign here (mouse / touch)</label>
                                <div class="mt-2 rounded-lg border border-gray-200 bg-white p-2">
                                    <canvas
                                        id="signatureCanvas"
                                        width="520"
                                        height="180"
                                        class="w-full rounded-md border border-gray-100"
                                        style="touch-action: none;"
                                    ></canvas>

                                    <div class="mt-3 flex items-center justify-between gap-2">
                                        <button type="button" class="rounded-lg bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700 border border-slate-200 hover:bg-slate-100" onclick="clearSignature()">
                                            Clear
                                        </button>
                                        <button type="button" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-medium text-white hover:bg-slate-800" onclick="captureSignature()">
                                            Use signature
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-xs text-slate-500">
                            This will update RIS status.
                        </div>
                    </div>
                </div>


                <div class="flex items-center justify-end gap-2 border-t border-gray-100 px-6 py-4">
                    <button type="button" class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950" onclick="closeDecisionModal()">Cancel</button>

                    <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700" onclick="document.getElementById('targetDecision').value='Approved'; document.getElementById('signatureBlock').classList.remove('hidden'); captureSignature();">Approve</button>

                    <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-rose-700" onclick="document.getElementById('targetDecision').value='Rejected';">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openDecisionModal(type, id, presetDecision) {
        const modal = document.getElementById('decisionModal');
        const targetType = document.getElementById('targetType');
        const targetId = document.getElementById('targetId');
        const subtitle = document.getElementById('decisionModalSubtitle');
        const decisionForm = document.getElementById('decisionForm');
        const targetDecision = document.getElementById('targetDecision');

        // UI reset
        const signatureBlock = document.getElementById('signatureBlock');
        const signatureCanvas = document.getElementById('signatureCanvas');
        const signatureDataInput = document.getElementById('signatureData');

        if (signatureDataInput) signatureDataInput.value = '';
        if (signatureCanvas) {
            const ctx = signatureCanvas.getContext('2d');
            ctx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
        }

        targetType.value = type;
        targetId.value = id;
        targetDecision.value = presetDecision || '';

        subtitle.textContent = `RIS #${id}`;

        decisionForm.action = `/president/approvals/ris/decide`;

        // Show signature UI only when approving
        if (signatureBlock) {
            const isApproved = (presetDecision || '').toLowerCase() === 'approved';
            signatureBlock.classList.toggle('hidden', !isApproved);
        }

        modal.classList.remove('hidden');

        if (window.lucide) {
            lucide.createIcons();
        }
    }

    function closeDecisionModal() {
        document.getElementById('decisionModal').classList.add('hidden');
    }

    // =====================================================
    // SIGNATURE CANVAS (in-memory capture only)
    // =====================================================

    function clearSignature() {
        const canvas = document.getElementById('signatureCanvas');
        const input = document.getElementById('signatureData');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        if (input) input.value = '';
    }

    function captureSignature() {
        const canvas = document.getElementById('signatureCanvas');
        const input = document.getElementById('signatureData');
        if (!canvas || !input) return;

        const isEmpty = canvas.toDataURL('image/png');
        // store data URL (not persisted by backend in this version)
        input.value = canvas.toDataURL('image/png');
        return isEmpty;
    }

    (function initSignatureCanvas() {
        const canvas = document.getElementById('signatureCanvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');

        // styling
        ctx.lineWidth = 2;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#111827';

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
        }

        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        canvas.addEventListener('mouseup', end);
        canvas.addEventListener('mouseleave', end);

        canvas.addEventListener('touchstart', (e) => { e.preventDefault(); start(e); }, { passive: false });
        canvas.addEventListener('touchmove', (e) => { e.preventDefault(); move(e); }, { passive: false });
        canvas.addEventListener('touchend', end);
    })();



</script>

@endsection
