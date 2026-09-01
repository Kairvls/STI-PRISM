@foreach ($reports as $report)
    @php
        $modalItems = collect($report->report_items ?? []);
        $openModalItems = $modalItems->filter(
            fn ($item) => in_array($item->report_item_status, ['Pending', 'Processing'], true)
        );
        $isMultiItem = $modalItems->count() > 1;
        $canClaimUrgent = $report->report_current_status === 'Pending'
            && empty($report->report_assigned_personnel_id)
            && empty($report->report_assigned_purchaser_id);
    @endphp

    @if ($canClaimUrgent)
        <div id="purchaser-reject-modal-{{ $report->report_id }}" class="fixed inset-0 z-50 hidden overflow-hidden">
            <div class="flex min-h-screen items-center justify-center bg-[#0b1220]/70 p-4" onclick="closeReportModal('purchaser-reject-modal-{{ $report->report_id }}')">
                <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl" onclick="event.stopPropagation()">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Reject Urgent Report</h2>
                            <p class="mt-1 text-sm text-slate-500">Explain why this urgent report will not be handled.</p>
                        </div>
                        <button type="button" onclick="closeReportModal('purchaser-reject-modal-{{ $report->report_id }}')" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('purchaser.reports.urgent.reject', $report->report_id) }}" class="mt-5 space-y-4">
                        @csrf
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Rejection Notes</label>
                            <textarea name="rejection_notes" rows="4" required placeholder="Describe why this report is being rejected." class="w-full resize-none rounded-lg border border-slate-200 px-3 py-2.5 text-sm outline-none transition focus:border-slate-400"></textarea>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" onclick="closeReportModal('purchaser-reject-modal-{{ $report->report_id }}')" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Cancel</button>
                            <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700">Reject Report</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if (
        $report->report_current_status === 'Processing'
        && (int) $report->report_assigned_purchaser_id === (int) auth()->id()
    )
        <div id="purchaser-resolve-modal-{{ $report->report_id }}" class="fixed inset-0 z-50 hidden overflow-hidden">
            <div class="flex min-h-screen items-center justify-center bg-[#0b1220]/70 p-4" onclick="closeReportModal('purchaser-resolve-modal-{{ $report->report_id }}')">
                <div class="flex max-h-[86vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" onclick="event.stopPropagation()">
                    <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ \App\Support\ReportGrouping::ticketCode($report) }}</p>
                            <h2 class="mt-1 text-lg font-semibold text-slate-900">Resolve Urgent Report</h2>
                            <p class="mt-1 text-sm text-slate-500">Add resolution notes and optional proof image.</p>
                        </div>
                        <button type="button" onclick="closeReportModal('purchaser-resolve-modal-{{ $report->report_id }}')" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                    <form method="POST" enctype="multipart/form-data" action="{{ route('purchaser.reports.urgent.resolve', $report->report_id) }}" class="flex min-h-0 flex-1 flex-col">
                        @csrf
                        <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-6 py-5">
                            @if ($isMultiItem)
                                <div>
                                    <p class="mb-2 text-sm font-semibold text-slate-700">Equipment items</p>
                                    <p class="mb-3 text-xs leading-5 text-slate-500">
                                        Select which equipment this resolution applies to. Leave unchecked to resolve every open item.
                                    </p>
                                    <div class="space-y-2">
                                        @foreach ($modalItems as $item)
                                            @php
                                                $itemIsOpen = in_array($item->report_item_status, ['Pending', 'Processing'], true);
                                            @endphp
                                            <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 {{ $itemIsOpen ? 'cursor-pointer' : 'opacity-60' }}">
                                                @if ($itemIsOpen)
                                                    <input type="checkbox" name="report_item_ids[]" value="{{ $item->report_item_id }}" class="mt-1 rounded border-slate-300 text-slate-900 focus:ring-slate-400" />
                                                @else
                                                    <input type="checkbox" disabled class="mt-1 rounded border-slate-300" />
                                                @endif
                                                <span class="min-w-0">
                                                    <span class="block text-sm font-semibold text-slate-900">{{ \App\Support\ReportItems::displayName($item) }}</span>
                                                    <span class="mt-0.5 block text-xs text-slate-500">Current: {{ $item->report_item_status }}</span>
                                                    @include('components.tables.partials.report-item-equipment-details', ['item' => $item, 'compact' => true])
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @if ($openModalItems->isEmpty())
                                        <p class="mt-2 text-xs text-amber-700">All equipment items on this report already have a final status.</p>
                                    @endif
                                </div>
                            @endif
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Resolution Notes</label>
                                <textarea name="resolution_notes" rows="4" placeholder="Describe how the urgent issue was resolved." class="w-full resize-none rounded-lg border border-slate-200 px-3 py-2.5 text-sm outline-none transition focus:border-slate-400"></textarea>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Proof Image</label>
                                <input type="file" name="resolution_image" accept="image/*" class="block w-full text-sm text-slate-500">
                            </div>
                        </div>
                        <div class="flex shrink-0 justify-end gap-2 border-t border-slate-100 px-6 py-4">
                            <button type="button" onclick="closeReportModal('purchaser-resolve-modal-{{ $report->report_id }}')" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Cancel</button>
                            <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">Resolve Report</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div id="purchaser-replacement-modal-{{ $report->report_id }}" class="fixed inset-0 z-50 hidden overflow-hidden">
            <div class="flex min-h-screen items-center justify-center bg-[#0b1220]/70 p-4" onclick="closeReportModal('purchaser-replacement-modal-{{ $report->report_id }}')">
                <div class="flex max-h-[86vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" onclick="event.stopPropagation()">
                    <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ \App\Support\ReportGrouping::ticketCode($report) }}</p>
                            <h2 class="text-lg font-semibold text-slate-900">Send for Replacement</h2>
                            <p class="mt-1 text-sm text-slate-500">Explain why this equipment requires replacement.</p>
                        </div>
                        <button type="button" onclick="closeReportModal('purchaser-replacement-modal-{{ $report->report_id }}')" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                    <form method="POST" enctype="multipart/form-data" action="{{ route('purchaser.reports.urgent.replacement', $report->report_id) }}" class="flex min-h-0 flex-1 flex-col">
                        @csrf
                        <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-6 py-5">
                            @if ($isMultiItem)
                                <div>
                                    <p class="mb-2 text-sm font-semibold text-slate-700">Equipment items</p>
                                    <p class="mb-3 text-xs leading-5 text-slate-500">
                                        Select which equipment this replacement applies to. Leave unchecked to replace every open item.
                                    </p>
                                    <div class="space-y-2">
                                        @foreach ($modalItems as $item)
                                            @php
                                                $itemIsOpen = in_array($item->report_item_status, ['Pending', 'Processing'], true);
                                            @endphp
                                            <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 {{ $itemIsOpen ? 'cursor-pointer' : 'opacity-60' }}">
                                                @if ($itemIsOpen)
                                                    <input type="checkbox" name="report_item_ids[]" value="{{ $item->report_item_id }}" class="mt-1 rounded border-slate-300 text-slate-900 focus:ring-slate-400" />
                                                @else
                                                    <input type="checkbox" disabled class="mt-1 rounded border-slate-300" />
                                                @endif
                                                <span class="min-w-0">
                                                    <span class="block text-sm font-semibold text-slate-900">{{ \App\Support\ReportItems::displayName($item) }}</span>
                                                    <span class="mt-0.5 block text-xs text-slate-500">Current: {{ $item->report_item_status }}</span>
                                                    @include('components.tables.partials.report-item-equipment-details', ['item' => $item, 'compact' => true])
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @if ($openModalItems->isEmpty())
                                        <p class="mt-2 text-xs text-amber-700">All equipment items on this report already have a final status.</p>
                                    @endif
                                </div>
                            @endif
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Replacement Reason</label>
                                <textarea name="replacement_notes" rows="4" required placeholder="Describe why replacement is needed." class="w-full resize-none rounded-lg border border-slate-200 px-3 py-2.5 text-sm outline-none transition focus:border-slate-400"></textarea>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Supporting Image</label>
                                <input type="file" name="replacement_image" accept="image/*" class="block w-full text-sm text-slate-500">
                            </div>
                        </div>
                        <div class="flex shrink-0 justify-end gap-2 border-t border-slate-100 px-6 py-4">
                            <button type="button" onclick="closeReportModal('purchaser-replacement-modal-{{ $report->report_id }}')" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Cancel</button>
                            <button type="submit" class="rounded-lg bg-[#0025cc] px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-800">Send for Replacement</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach
