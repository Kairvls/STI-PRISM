@foreach ($reports as $report)
    @if (
        $report->report_current_status === 'Processing'
        && (int) $report->report_assigned_purchaser_id === (int) auth()->id()
    )
        <div id="purchaser-resolve-modal-{{ $report->report_id }}" class="fixed inset-0 z-50 hidden overflow-hidden">
            <div class="flex min-h-screen items-center justify-center bg-[#0b1220]/70 p-4" onclick="closeReportModal('purchaser-resolve-modal-{{ $report->report_id }}')">
                <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl" onclick="event.stopPropagation()">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Resolve Urgent Report</h2>
                            <p class="mt-1 text-sm text-slate-500">Add resolution notes and optional proof image.</p>
                        </div>
                        <button type="button" onclick="closeReportModal('purchaser-resolve-modal-{{ $report->report_id }}')" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                    <form method="POST" enctype="multipart/form-data" action="{{ route('purchaser.reports.urgent.resolve', $report->report_id) }}" class="mt-5 space-y-4">
                        @csrf
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Resolution Notes</label>
                            <textarea name="resolution_notes" rows="4" placeholder="Describe how the urgent issue was resolved." class="w-full resize-none rounded-lg border border-slate-200 px-3 py-2.5 text-sm outline-none transition focus:border-slate-400"></textarea>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Proof Image</label>
                            <input type="file" name="resolution_image" accept="image/*" class="block w-full text-sm text-slate-500">
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" onclick="closeReportModal('purchaser-resolve-modal-{{ $report->report_id }}')" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Cancel</button>
                            <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">Resolve Report</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div id="purchaser-replacement-modal-{{ $report->report_id }}" class="fixed inset-0 z-50 hidden overflow-hidden">
            <div class="flex min-h-screen items-center justify-center bg-[#0b1220]/70 p-4" onclick="closeReportModal('purchaser-replacement-modal-{{ $report->report_id }}')">
                <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl" onclick="event.stopPropagation()">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Send for Replacement</h2>
                            <p class="mt-1 text-sm text-slate-500">Explain why this equipment requires replacement.</p>
                        </div>
                        <button type="button" onclick="closeReportModal('purchaser-replacement-modal-{{ $report->report_id }}')" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                    <form method="POST" enctype="multipart/form-data" action="{{ route('purchaser.reports.urgent.replacement', $report->report_id) }}" class="mt-5 space-y-4">
                        @csrf
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Replacement Reason</label>
                            <textarea name="replacement_notes" rows="4" required placeholder="Describe why replacement is needed." class="w-full resize-none rounded-lg border border-slate-200 px-3 py-2.5 text-sm outline-none transition focus:border-slate-400"></textarea>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Supporting Image</label>
                            <input type="file" name="replacement_image" accept="image/*" class="block w-full text-sm text-slate-500">
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" onclick="closeReportModal('purchaser-replacement-modal-{{ $report->report_id }}')" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Cancel</button>
                            <button type="submit" class="rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-800">Send for Replacement</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach
