@extends('layouts.admin-layout')

@section('title', 'Procurement Monitor')

@section('content')
<div class="admin-page space-y-6" x-data="adminProcurementMonitor()">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div class="max-w-2xl">
            <p class="text-sm text-slate-600">
                Monitor RIS → ATP → RFC/CA → RR → Liquidation here (view-only).
                To <span class="font-semibold text-slate-900">create and drive</span> documents, enable procurement on your Admin account (Users) and open the <span class="font-semibold text-slate-900">Purchaser</span> portal via the portal switcher.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @if(\App\Support\RoleAccess::hasRole(\App\Support\RoleAccess::PURCHASER))
                <a href="{{ url('/purchaser/dashboard') }}" class="admin-btn-primary h-10">Open Purchaser portal</a>
            @endif
            <a href="{{ url('/admin/procurement-review') }}" class="inline-flex h-10 items-center rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Procurement Requests</a>
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
                @foreach([
                    'all' => 'All',
                    'open' => 'Open',
                    'awaiting_admin' => 'Awaiting Admin',
                    'atp' => 'At ATP',
                    'rfc' => 'At RFC/CA',
                    'rr' => 'At RR',
                    'liq' => 'At LIQ',
                ] as $key => $label)
                    <a href="{{ route('admin.operations.procurement', ['filter' => $key, 'q' => $q]) }}"
                       class="rounded-lg px-3 py-2 text-xs font-semibold {{ $filter === $key ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">RIS</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Purpose</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Pipeline</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Submitted by</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Amount</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                        @php
                            $pipeline = $row->pipeline ?? null;
                            $stages = $pipeline['stages'] ?? [];
                            $stageKeys = ['ris', 'atp', 'rfc', 'rr', 'liq'];
                            $current = $pipeline['current_stage'] ?? 'ris';
                        @endphp
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-semibold text-slate-900">{{ $row->ris_form_number ?: ('#'.$row->ris_id) }}</p>
                                <p class="text-xs text-slate-500">{{ $row->ris_status ?: '—' }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-600">
                                {{ \Illuminate\Support\Str::limit($row->ris_purpose_description, 70) ?: '—' }}
                                @if(!empty($row->report_id))
                                    <p class="mt-1 text-xs text-slate-400">From report #{{ $row->report_id }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap items-center gap-1">
                                    @foreach($stageKeys as $key)
                                        @php $stage = $stages[$key] ?? null; $exists = !empty($stage['exists']); @endphp
                                        <span
                                            class="inline-flex rounded-md px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide
                                                {{ $exists && $key === $current ? 'bg-slate-900 text-white' : ($exists ? 'bg-emerald-50 text-emerald-800 ring-1 ring-inset ring-emerald-200' : 'bg-slate-50 text-slate-400 ring-1 ring-inset ring-slate-200') }}"
                                            title="{{ $exists ? ($stage['hint'] ?? $stage['label']) : (strtoupper($key).' not started') }}"
                                        >{{ strtoupper($key) }}</span>
                                        @if(!$loop->last)
                                            <span class="text-slate-300">›</span>
                                        @endif
                                    @endforeach
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $pipeline['current_hint'] ?? '—' }}
                                    @if(!empty($pipeline['payment_path_label']) && $pipeline['payment_path_label'] !== 'Not chosen')
                                        · {{ $pipeline['payment_path_label'] }}
                                    @endif
                                </p>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $row->created_by_name ?: '—' }}</td>
                            <td class="px-5 py-4 text-sm text-slate-700">₱{{ number_format((float) ($row->ris_calculated_total ?? 0), 2) }}</td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        class="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                        @click="openDoc('ris', {{ (int) $row->ris_id }}, '{{ e($row->ris_form_number ?: ('RIS #'.$row->ris_id)) }}')"
                                    >View RIS</button>
                                    <button
                                        type="button"
                                        class="rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-slate-800"
                                        @click="openPipeline({{ (int) $row->ris_id }})"
                                    >Pipeline</button>
                                </div>
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

    {{-- Document preview modal --}}
    <div
        x-show="docOpen"
        x-cloak
        class="fixed inset-0 z-[12000] flex items-center justify-center bg-slate-900/50 p-4"
        @keydown.escape.window="closeDoc()"
    >
        <div class="flex h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl" @click.outside="closeDoc()">
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                <div>
                    <p class="text-sm font-bold text-slate-900" x-text="docTitle">Document</p>
                    <p class="text-xs text-slate-500">View only</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="printDoc()">Print</button>
                    <button type="button" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200" @click="closeDoc()">Close</button>
                </div>
            </div>
            <div class="relative min-h-0 flex-1 bg-slate-100">
                <div x-show="docLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 text-sm font-semibold text-slate-600">Loading form…</div>
                <iframe x-ref="docFrame" class="h-full w-full border-0 bg-white" title="Document preview"></iframe>
            </div>
        </div>
    </div>

    {{-- Pipeline drawer --}}
    <div
        x-show="pipelineOpen"
        x-cloak
        class="fixed inset-0 z-[11900] flex justify-end bg-slate-900/40"
        @keydown.escape.window="closePipeline()"
    >
        <div class="h-full w-full max-w-md overflow-y-auto bg-white shadow-2xl" @click.outside="closePipeline()">
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white px-5 py-4">
                <div>
                    <p class="text-sm font-bold text-slate-900">Procurement pipeline</p>
                    <p class="text-xs text-slate-500" x-text="pipelineSubtitle"></p>
                </div>
                <button type="button" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700" @click="closePipeline()">Close</button>
            </div>

            <div class="space-y-4 px-5 py-5" x-show="!pipelineLoading && pipelineData">
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Current stage</p>
                    <p class="mt-1 font-bold uppercase text-slate-900" x-text="(pipelineData?.current_stage || '—')"></p>
                    <p class="mt-1 text-xs text-slate-600" x-text="pipelineData?.current_hint || ''"></p>
                    <p class="mt-1 text-xs text-slate-500" x-show="pipelineData?.payment_path_label" x-text="'Payment: ' + (pipelineData?.payment_path_label || '')"></p>
                </div>

                <template x-for="key in ['ris','atp','rfc','rr','liq']" :key="key">
                    <div class="rounded-xl border border-gray-200 px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500" x-text="key"></p>
                                <p class="mt-1 text-sm font-semibold text-slate-900" x-text="pipelineData?.stages?.[key]?.exists ? (pipelineData.stages[key].label) : 'Not started'"></p>
                                <p class="mt-0.5 text-xs text-slate-500" x-text="pipelineData?.stages?.[key]?.hint || ''"></p>
                            </div>
                            <button
                                type="button"
                                class="shrink-0 rounded-lg border border-gray-200 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-40"
                                :disabled="!pipelineData?.stages?.[key]?.exists"
                                @click="openDoc(key, pipelineData.stages[key].id, pipelineData.stages[key].label)"
                            >View</button>
                        </div>
                    </div>
                </template>

                <div class="rounded-xl border border-gray-200 px-4 py-3" x-show="pipelineData?.funds">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Funds</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900" x-text="pipelineData?.funds?.label || '—'"></p>
                    <p class="mt-0.5 text-xs text-slate-500" x-text="pipelineData?.funds?.released_at || ''"></p>
                </div>

                <div>
                    <p class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Approval activity</p>
                    <template x-if="!(pipelineData?.logs || []).length">
                        <p class="text-sm text-slate-400">No approval logs yet.</p>
                    </template>
                    <div class="space-y-2">
                        <template x-for="(log, idx) in (pipelineData?.logs || [])" :key="idx">
                            <div class="rounded-lg border border-gray-100 bg-white px-3 py-2 text-xs">
                                <p class="font-semibold text-slate-800">
                                    <span x-text="log.type"></span>
                                    · <span x-text="log.status"></span>
                                </p>
                                <p class="text-slate-500" x-text="(log.actor || 'System') + (log.at ? (' · ' + log.at) : '')"></p>
                                <p class="mt-0.5 text-slate-600" x-show="log.remarks" x-text="log.remarks"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="px-5 py-10 text-center text-sm text-slate-500" x-show="pipelineLoading">Loading pipeline…</div>
        </div>
    </div>
</div>

<script>
function adminProcurementMonitor() {
    return {
        docOpen: false,
        docLoading: false,
        docTitle: '',
        pipelineOpen: false,
        pipelineLoading: false,
        pipelineData: null,
        pipelineSubtitle: '',

        openDoc(type, id, title) {
            if (!type || !id) return;
            this.docTitle = title || (String(type).toUpperCase() + ' #' + id);
            this.docOpen = true;
            this.docLoading = true;
            const url = @json(url('/admin/operations/documents')) + '/' + encodeURIComponent(type) + '/' + encodeURIComponent(id) + '?ts=' + Date.now();
            const frame = this.$refs.docFrame;
            if (frame) {
                frame.onload = () => { this.docLoading = false; };
                frame.src = url;
            }
        },

        closeDoc() {
            this.docOpen = false;
            this.docLoading = false;
            if (this.$refs.docFrame) this.$refs.docFrame.src = 'about:blank';
        },

        printDoc() {
            const frame = this.$refs.docFrame;
            if (!frame || !frame.contentWindow) return;
            frame.contentWindow.focus();
            frame.contentWindow.print();
        },

        async openPipeline(risId) {
            this.pipelineOpen = true;
            this.pipelineLoading = true;
            this.pipelineData = null;
            this.pipelineSubtitle = 'RIS #' + risId;
            try {
                const res = await fetch(@json(url('/admin/operations/procurement')) + '/' + risId + '/pipeline', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('Failed');
                this.pipelineData = await res.json();
                this.pipelineSubtitle = (this.pipelineData?.stages?.ris?.label) || this.pipelineSubtitle;
            } catch (e) {
                this.pipelineData = { stages: {}, logs: [], current_stage: '—', current_hint: 'Unable to load pipeline.' };
            } finally {
                this.pipelineLoading = false;
            }
        },

        closePipeline() {
            this.pipelineOpen = false;
            this.pipelineData = null;
        }
    }
}
</script>
<style>
[x-cloak] { display: none !important; }
</style>
@endsection
