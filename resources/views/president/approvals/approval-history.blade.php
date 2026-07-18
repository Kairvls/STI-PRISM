@extends('layouts.president-layout')

@section('title', 'Approval History')

@section('content')

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between fade-in">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Approval History</h1>
        <p class="mt-1 text-sm leading-6 text-gray-500">
            View past RIS/procurement decisions made by the President.
        </p>
    </div>

    <div class="flex items-center gap-2">
        <a href="/president/approvals" class="action-btn inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-900 transition-all duration-200 hover:bg-gray-50 active:scale-95">
            <i data-lucide="clipboard-check" class="h-4 w-4"></i>
            Back to Approvals
        </a>
    </div>
</div>

{{-- History table --}}
<div class="mt-6 grid grid-cols-1 gap-4">
    <section class="rounded-xl border border-gray-200 bg-white p-5 slide-up" style="animation-delay: 0.1s">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Decision Records</h2>
                <p class="mt-1 text-xs text-gray-500">A timeline of approval decisions (RIS / Procurement).</p>
            </div>

            <div class="text-right">
                <p class="text-xs font-semibold text-gray-600">Showing</p>
                <p id="historyCount" class="text-sm font-bold text-gray-900">0</p>
            </div>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Type</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Reference</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Decision</th>
                        <th class="px-2 py-3 text-left text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Date</th>
                        <th class="px-2 py-3 text-center text-[12px] font-bold uppercase tracking-wider text-black bg-gray-50">Actions</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody">

                    {{--
                      Expected future variables (optional):
                        - $approvalHistoryRecords = collection/array of records
                      Each record (suggested shape):
                        - type: 'RIS'|'Procurement'
                        - reference: e.g. 'RIS#123' / 'PR#456'
                        - supplier_name: string
                        - decision: 'Approved'|'Rejected'
                        - remarks: string|null
                        - decided_at: datetime|string
                    --}}

                    @isset($approvalHistoryRecords)
                        @forelse($approvalHistoryRecords as $row)
                            @php
                                $type = $row->type ?? $row->reference_type ?? 'RIS';
                                $reference = $row->ris_form_number ?? $row->reference ?? ($row->procurement_request_id ? 'PR#'.$row->procurement_request_id : '—');
                                $decision = $row->decision ?? $row->ris_status ?? 'Approved';
                                $decidedAt = $row->decided_at ?? $row->ris_approved_by_date ?? null;
                                $decisionLower = is_string($decision) ? strtolower($decision) : '';
                                $risId = $row->ris_id ?? null;
                            @endphp

                            <tr class="border-b border-gray-100 history-row transition-all duration-200">
                                <td class="px-2 py-4 text-sm font-semibold text-gray-600">{{ $type }}</td>
                                <td class="px-2 py-4 text-sm text-gray-700">{{ $reference }}</td>
                                <td class="px-2 py-4 text-sm">
                                    @if ($decision === 'Approved' || $decisionLower === 'approved')
                                        <span class="inline-flex items-center rounded-lg bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800 border border-emerald-200">Approved</span>
                                    @elseif ($decision === 'Rejected' || $decisionLower === 'rejected')
                                        <span class="inline-flex items-center rounded-lg bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-800 border border-rose-200">Rejected</span>
                                    @else
                                        <span class="inline-flex items-center rounded-lg bg-gray-50 px-3 py-1 text-xs font-semibold text-gray-600 border border-gray-200">{{ $decision }}</span>
                                    @endif
                                </td>
                                <td class="px-2 py-4 text-sm text-gray-700">
                                    {{ $decidedAt ? 
                                        (is_object($decidedAt)
                                            ? $decidedAt->format('Y-m-d')
                                            : date('Y-m-d', strtotime((string)$decidedAt))
                                        ) : '—'
                                    }}
                                </td>
                                <td class="px-2 py-4 text-center">
                                    @if ($type === 'RIS' && $risId)
                                        <button type="button" class="action-btn inline-flex h-8 items-center justify-center rounded-lg bg-white px-2.5 text-xs font-semibold text-slate-700 border border-gray-200 transition-all duration-200 hover:bg-gray-50 active:scale-95" title="View approved RIS form" onclick="openRisViewModal({{ $risId }})">
                                            <i data-lucide="eye" class="h-4 w-4"></i>
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-2 py-12 text-center fade-in">
                                    <p class="text-sm font-semibold text-gray-800">No approval records found.</p>
                                    <p class="mt-1 text-xs text-gray-500">When backend data is wired, the table will automatically populate here.</p>
                                </td>
                            </tr>
                        @endforelse
                    @else
                        <tr>
                            <td colspan="5" class="px-2 py-12 text-center fade-in">
                                <p class="text-sm font-semibold text-gray-800">Approval history UI is ready.</p>
                                <p class="mt-1 text-xs text-gray-500">Backend data will populate this table once the controller is updated.</p>
                            </td>
                        </tr>
                    @endisset

                </tbody>
            </table>
        </div>
    </section>
</div>

<div id="risViewModal" class="fixed inset-0 z-50 hidden">
    <div class="flex h-screen items-center justify-center bg-black/30 p-2 backdrop-blur-[2px] modal-overlay" onclick="closeRisViewModal()">
        <div class="w-full max-w-6xl h-[calc(100vh-1rem)] overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)] modal-content" onclick="event.stopPropagation()">
            <div class="border-b border-gray-100 px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-950">RIS Form</h3>
                        <p id="risViewTitle" class="mt-1 text-sm text-slate-600">Approved RIS</p>
                    </div>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition-all duration-200 hover:bg-slate-100 hover:text-slate-900 active:scale-90" onclick="closeRisViewModal()" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
            </div>

            <div class="h-full">
                <div class="h-full w-full overflow-hidden rounded-b-2xl border border-gray-200 bg-gray-50">
                    <iframe id="risViewIframe" class="w-full h-full" style="min-height: calc(100vh - 140px);" src="about:blank"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* ======================================
       ANIMATIONS
    ====================================== */

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes modalIn {
        from { opacity: 0; transform: scale(0.95) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }

    @keyframes overlayIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .fade-in {
        animation: fadeIn 0.4s ease-out forwards;
    }

    .slide-up {
        opacity: 0;
        animation: slideUp 0.5s ease-out forwards;
    }

    /* Modal opening animation */
    .modal-overlay {
        animation: overlayIn 0.2s ease-out forwards;
    }

    .modal-content {
        animation: modalIn 0.25s ease-out forwards;
    }

    /* Table row hover */
    .history-row {
        transition: background-color 0.2s ease;
    }

    .history-row:hover {
        background-color: rgba(254, 252, 232, 0.4);
    }

    /* Button click */
    .action-btn {
        transition: all 0.2s ease;
    }

    .action-btn:active {
        transform: scale(0.95);
    }
</style>

<script>
    function openRisViewModal(risId) {
        const modal = document.getElementById('risViewModal');
        const iframe = document.getElementById('risViewIframe');
        const title = document.getElementById('risViewTitle');
        if (!modal || !iframe) return;

        iframe.src = `/president/ris/${risId}/print?ts=${Date.now()}`;
        if (title) title.textContent = `RIS #${risId}`;
        modal.classList.remove('hidden');
    }

    function closeRisViewModal() {
        const modal = document.getElementById('risViewModal');
        const iframe = document.getElementById('risViewIframe');
        if (iframe) iframe.src = 'about:blank';
        if (modal) modal.classList.add('hidden');
    }

    function setHistoryCount() {
        const body = document.getElementById('historyTableBody');
        const rows = body ? body.querySelectorAll('.history-row') : [];
        document.getElementById('historyCount').textContent = rows.length;
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) {
            lucide.createIcons();
        }
        setHistoryCount();
    });
</script>

@endsection