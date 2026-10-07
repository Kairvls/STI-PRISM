{{--
  Right-side procurement pipeline drawer + document preview.
  Open from anywhere with:
    window.dispatchEvent(new CustomEvent('open-procurement-pipeline', { detail: { id: risId, label: 'RIS-…' } }))
--}}
<style>
    .ppd-backdrop { background: rgba(15, 23, 42, 0.45); }
    .ppd-panel { background: #fff; box-shadow: 0 24px 64px rgba(15, 23, 42, 0.16); }
    .ppd-drawer { border-left: 1px solid #e2e8f0; }
    .ppd-modal { border-radius: 18px; border: 1px solid #e2e8f0; overflow: hidden; }
    .ppd-modal.is-fullscreen { border-radius: 0; border: 0; }
</style>

<div
    x-data="procurementPipelineDrawer"
    @open-procurement-pipeline.window="openPipeline($event.detail.id, $event.detail.label)"
>
    <template x-teleport="body">
        <div
            x-show="docOpen"
            x-cloak
            class="ppd-backdrop fixed inset-0 z-[12000] flex items-center justify-center"
            :class="docFullscreen ? 'p-0' : 'p-4'"
            @keydown.escape.window="if (docOpen) { $event.preventDefault(); docFullscreen ? toggleDocFullscreen() : closeDoc() }"
            @click.self="closeDoc()"
        >
            <div
                class="ppd-panel ppd-modal flex w-full flex-col"
                :class="docFullscreen ? 'is-fullscreen h-full max-w-none' : 'max-h-[90vh] max-w-5xl'"
                @click.stop
            >
                <div x-ref="docHeader" class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-950" x-text="docTitle">Document</p>
                        <p class="mt-0.5 text-xs text-gray-400">View only</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="pur-btn-secondary inline-flex h-9 items-center gap-1.5 px-3 text-xs" @click="printDoc()">
                            <i data-lucide="printer" class="h-4 w-4"></i>
                            Print
                        </button>
                        <button
                            type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50 hover:text-gray-800"
                            @click="toggleDocFullscreen()"
                            :title="docFullscreen ? 'Exit full screen' : 'Full screen'"
                            :aria-label="docFullscreen ? 'Exit full screen' : 'Full screen'"
                        >
                            <svg x-show="!docFullscreen" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4h4M20 8V4h-4M4 16v4h4M20 16v4h-4"></path>
                            </svg>
                            <svg x-show="docFullscreen" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 4H5v4M15 4h4v4M9 20H5v-4M15 20h4v-4"></path>
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50 hover:text-gray-800"
                            @click="closeDoc()"
                            aria-label="Close"
                        >
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>
                <div class="relative min-h-0 bg-gray-50">
                    <div x-show="docLoading" class="absolute inset-0 z-10 overflow-hidden bg-gray-50 py-6">
                        @include('partials.skeleton', ['skeletonType' => 'document', 'skeletonLabel' => 'Loading form'])
                    </div>
                    <iframe
                        x-ref="docFrame"
                        class="block w-full border-0 bg-white"
                        :style="'height: ' + (docFrameHeight ? docFrameHeight + 'px' : '70vh')"
                        title="Document preview"
                    ></iframe>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div
            x-show="pipelineOpen"
            x-cloak
            class="ppd-backdrop fixed inset-0 z-[11900] flex items-stretch justify-end"
            @keydown.escape.window="if (pipelineOpen && !docOpen && !$event.defaultPrevented) closePipeline()"
            @click.self="closePipeline()"
        >
            <div class="ppd-panel ppd-drawer flex h-full w-full max-w-md flex-col overflow-y-auto" @click.stop>
                <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-100 bg-white px-5 py-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-950">Procurement pipeline</p>
                        <p class="mt-0.5 text-xs text-gray-400" x-text="pipelineSubtitle"></p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50"
                        @click="closePipeline()"
                        aria-label="Close"
                    >
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                <div class="flex-1 space-y-5 px-5 py-5" x-show="!pipelineLoading && pipelineData">
                    <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Current stage</p>
                        <p class="mt-1 text-base font-semibold uppercase tracking-tight text-gray-950" x-text="(pipelineData?.current_stage || '—')"></p>
                        <p class="mt-1 text-sm text-gray-500" x-text="pipelineData?.current_hint || ''"></p>
                        <p class="mt-1 text-xs text-gray-400" x-show="pipelineData?.payment_path_label" x-text="'Payment: ' + (pipelineData?.payment_path_label || '')"></p>
                    </div>

                    <div class="divide-y divide-gray-100 rounded-xl border border-gray-100">
                        <template x-for="key in ['ris','atp','rfc','rr','liq']" :key="key">
                            <div class="flex items-start justify-between gap-3 px-4 py-3">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400" x-text="key"></p>
                                    <p class="mt-1 text-sm font-semibold text-gray-900" x-text="pipelineData?.stages?.[key]?.exists ? (pipelineData.stages[key].label) : 'Not started'"></p>
                                    <p class="mt-0.5 text-xs text-gray-400" x-text="pipelineData?.stages?.[key]?.hint || ''"></p>
                                </div>
                                <button
                                    type="button"
                                    class="shrink-0 rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 disabled:opacity-30"
                                    :disabled="!pipelineData?.stages?.[key]?.exists"
                                    @click="openDoc(key, pipelineData.stages[key].id, pipelineData.stages[key].label)"
                                >View</button>
                            </div>
                        </template>
                    </div>

                    <div class="rounded-xl border border-gray-100 px-4 py-3" x-show="pipelineData?.funds">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Funds</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900" x-text="pipelineData?.funds?.label || '—'"></p>
                        <p class="mt-0.5 text-xs text-gray-400" x-text="pipelineData?.funds?.released_at || ''"></p>
                    </div>

                    <div>
                        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-gray-400">Approval activity</p>
                        <template x-if="!(pipelineData?.logs || []).length">
                            <p class="text-sm text-gray-400">No approval logs yet.</p>
                        </template>
                        <div class="divide-y divide-gray-100 rounded-xl border border-gray-100">
                            <template x-for="(log, idx) in (pipelineData?.logs || [])" :key="idx">
                                <div class="px-4 py-3 text-xs">
                                    <p class="font-semibold text-gray-800">
                                        <span x-text="log.type"></span>
                                        · <span x-text="log.status"></span>
                                    </p>
                                    <p class="mt-0.5 text-gray-400" x-text="(log.actor || 'System') + (log.at ? (' · ' + log.at) : '')"></p>
                                    <p class="mt-1 text-gray-600" x-show="log.remarks" x-text="log.remarks"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div x-show="pipelineLoading">
                    @include('partials.skeleton', ['skeletonType' => 'detail', 'skeletonRows' => 5, 'skeletonLabel' => 'Loading pipeline'])
                </div>

                <div class="sticky bottom-0 border-t border-gray-100 bg-white px-5 py-3 text-right">
                    <a
                        :href="moduleUrl"
                        class="text-xs font-semibold text-gray-500 transition hover:text-[#0025cc]"
                    >Open in Procurement Monitoring</a>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('procurementPipelineDrawer', () => ({
        docOpen: false,
        docLoading: false,
        docTitle: '',
        docFrameHeight: null,
        docResizeObserver: null,
        docFullscreen: false,

        toggleDocFullscreen() {
            this.docFullscreen = !this.docFullscreen;
            this.$nextTick(() => {
                this.applyDocZoom();
                this.fitDocFrame();
            });
        },

        // Grows the paper to the fullscreen width (max 1.5x); print always uses the original size.
        applyDocZoom() {
            let doc = null;
            try {
                doc = this.$refs.docFrame?.contentDocument;
            } catch (e) {}
            const shell = doc?.querySelector('.document-viewer-shell');
            if (!shell) return;

            let style = doc.getElementById('ppd-fullscreen-zoom');
            if (!style) {
                style = doc.createElement('style');
                style.id = 'ppd-fullscreen-zoom';
                doc.head.appendChild(style);
            }

            style.textContent = '';
            let zoom = 1;
            if (this.docFullscreen) {
                const bodyStyle = doc.defaultView.getComputedStyle(doc.body);
                const available = this.$refs.docFrame.clientWidth
                    - parseFloat(bodyStyle.paddingLeft || 0)
                    - parseFloat(bodyStyle.paddingRight || 0);
                const natural = shell.getBoundingClientRect().width;
                if (natural > 0) zoom = Math.min(1.5, Math.max(1, available / natural));
            }

            if (zoom !== 1) {
                style.textContent = '.document-viewer-shell { zoom: ' + zoom.toFixed(3) + '; }'
                    + ' @media print { .document-viewer-shell { zoom: 1 !important; } }';
            }
        },

        init() {
            window.addEventListener('resize', () => {
                if (!this.docOpen || this.docLoading) return;
                this.fitDocFrame();
                this.$nextTick(() => this.applyDocZoom());
            });
        },

        // The viewer body has min-height: 100vh, so measure the paper shell rather than the document.
        fitDocFrame() {
            const headerHeight = this.$refs.docHeader?.offsetHeight || 64;
            if (this.docFullscreen) {
                this.docFrameHeight = window.innerHeight - headerHeight;
                return;
            }

            const frame = this.$refs.docFrame;
            let doc = null;
            try {
                doc = frame?.contentDocument;
            } catch (e) {}
            const shell = doc?.querySelector('.document-viewer-shell');
            if (!shell) {
                this.docFrameHeight = null;
                return;
            }

            const bodyStyle = doc.defaultView.getComputedStyle(doc.body);
            const contentHeight = Math.ceil(
                shell.getBoundingClientRect().height
                + parseFloat(bodyStyle.paddingTop || 0)
                + parseFloat(bodyStyle.paddingBottom || 0)
            );
            const maxHeight = Math.floor(window.innerHeight * 0.9) - headerHeight;

            this.docFrameHeight = Math.max(240, Math.min(contentHeight, maxHeight));
        },

        watchDocFrame() {
            this.docResizeObserver?.disconnect();
            this.docResizeObserver = null;

            const shell = this.$refs.docFrame?.contentDocument?.querySelector('.document-viewer-shell');
            if (!shell || typeof ResizeObserver !== 'function') return;

            this.docResizeObserver = new ResizeObserver(() => this.fitDocFrame());
            this.docResizeObserver.observe(shell);
        },
        pipelineOpen: false,
        pipelineLoading: false,
        pipelineData: null,
        pipelineSubtitle: '',
        moduleUrl: @json(\App\Support\AdminPortal::route('operations.procurement')),

        refreshIcons() {
            this.$nextTick(() => {
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            });
        },

        openDoc(type, id, title) {
            if (!type || !id) return;
            this.docTitle = title || (String(type).toUpperCase() + ' #' + id);
            this.docOpen = true;
            this.docLoading = true;
            this.docFrameHeight = null;
            const url = @json(\App\Support\AdminPortal::url('operations/documents')) + '/' + encodeURIComponent(type) + '/' + encodeURIComponent(id) + '?ts=' + Date.now();
            this.refreshIcons();
            this.$nextTick(() => {
                const frame = this.$refs.docFrame;
                if (!frame) return;
                frame.onload = () => {
                    if (!this.docOpen) return;
                    this.applyDocZoom();
                    this.fitDocFrame();
                    this.watchDocFrame();
                    this.docLoading = false;
                };
                frame.src = url;
            });
        },

        closeDoc() {
            this.docOpen = false;
            this.docLoading = false;
            this.docResizeObserver?.disconnect();
            this.docResizeObserver = null;
            this.docFrameHeight = null;
            this.docFullscreen = false;
            if (this.$refs.docFrame) this.$refs.docFrame.src = 'about:blank';
        },

        printDoc() {
            try {
                this.$refs.docFrame?.contentWindow?.print();
            } catch (e) {}
        },

        async openPipeline(risId, label) {
            if (!risId) return;
            this.pipelineOpen = true;
            this.pipelineLoading = true;
            this.pipelineData = null;
            this.pipelineSubtitle = label
                || ((typeof window.risFormNumberLabel === 'function') ? window.risFormNumberLabel(risId) : ('RIS #' + risId));
            this.moduleUrl = @json(\App\Support\AdminPortal::route('operations.procurement'))
                + (label ? ('?q=' + encodeURIComponent(label)) : '');
            this.refreshIcons();

            try {
                const res = await fetch(@json(\App\Support\AdminPortal::url('operations/procurement')) + '/' + encodeURIComponent(risId) + '/pipeline', {
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
            this.pipelineLoading = false;
        },
    }));
});
</script>
