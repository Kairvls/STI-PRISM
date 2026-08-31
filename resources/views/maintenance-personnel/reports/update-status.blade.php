@extends ("layouts.maintenance-layout")

@section ("title", "Update Status")

@section ("content")
    <div>
        <!-- REPORT CARD -->
        <div class="mb-8 rounded-3xl bg-[#1E293B] p-8">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <p class="text-sm text-gray-400">Ticket</p>

                    <h1 class="mt-2 text-xl font-bold text-white">
                        {{ \App\Support\ReportGrouping::ticketCode($report) }}
                    </h1>
                </div>

                <div>
                    <p class="text-sm text-gray-400">Current Status</p>

                    <span
                        class="px-4 py-2 rounded-xl inline-block mt-2
                    @if($report->report_current_status == 'Pending')
                        bg-yellow-500/20 text-yellow-400
                    @elseif($report->report_current_status == 'Processing')
                        bg-blue-500/20 text-blue-400
                    @elseif($report->report_current_status == 'Resolved')
                        bg-green-500/20 text-green-400
                    @elseif($report->report_current_status == 'Rejected')
                        bg-red-500/20 text-red-400
                    @else
                        bg-orange-500/20 text-orange-400
                    @endif
                "
                    >
                        {{ $report->report_current_status }}
                    </span>
                </div>

                <div>
                    <p class="text-sm text-gray-400">Problem Description</p>

                    <h1 class="mt-2 font-semibold text-white">
                        {{ $report->report_problem_description }}
                    </h1>
                </div>

                <div>
                    <p class="text-sm text-gray-400">Room</p>

                    <h1 class="mt-2 font-semibold text-white">
                        {{
                            $report->room_name ??
                                "No Assigned Room"
                        }}
                    </h1>
                </div>

                <div class="md:col-span-2">
                    <p class="text-sm text-gray-400">Equipment</p>
                    <h1 class="mt-2 font-semibold text-white">
                        {{ $report->equipment_display ?? $report->equipment_name ?? $report->report_unlisted_equipment_name ?? 'Not specified' }}
                    </h1>
                </div>
            </div>
        </div>

        <!-- UPDATE FORM -->
        <div class="rounded-3xl bg-[#1E293B] p-8">
            <form
                action="/maintenance/reports/update-status/{{ $report->report_id }}"
                method="POST"
                enctype="multipart/form-data"
            >
                @csrf

                @php
                    $items = $reportItems ?? ($report->report_items ?? collect());
                    $openItems = $items->filter(fn ($item) => in_array($item->report_item_status, ['Pending', 'Processing'], true));
                @endphp

                @if ($items->count() > 1)
                    <div class="mb-6">
                        <label class="mb-3 block font-semibold text-white">
                            Equipment items
                        </label>
                        <p class="mb-3 text-sm text-gray-400">
                            For Resolved / For Replacement, select which equipment this decision applies to.
                            Leave all unchecked to apply the status to every item.
                        </p>
                        <div class="space-y-2">
                            @foreach ($items as $item)
                                <label class="flex items-start gap-3 rounded-2xl border border-white/10 bg-[#0F172A] px-4 py-3 {{ in_array($item->report_item_status, ['Pending', 'Processing'], true) ? 'cursor-pointer' : 'opacity-60' }}">
                                    @if (in_array($item->report_item_status, ['Pending', 'Processing'], true))
                                        <input
                                            type="checkbox"
                                            name="report_item_ids[]"
                                            value="{{ $item->report_item_id }}"
                                            class="mt-1"
                                        />
                                    @else
                                        <input type="checkbox" disabled class="mt-1" />
                                    @endif
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-semibold text-white">
                                            {{ \App\Support\ReportItems::displayName($item) }}
                                        </span>
                                        <span class="block text-xs text-gray-400">
                                            Current: {{ $item->report_item_status }}
                                        </span>
                                        <div class="mt-1 text-gray-300 [&_dt]:text-gray-500 [&_dd]:text-gray-200">
                                            @include('components.tables.partials.report-item-equipment-details', [
                                                'item' => $item,
                                                'compact' => true,
                                            ])
                                        </div>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @if ($openItems->isEmpty())
                            <p class="mt-2 text-sm text-amber-300">
                                All equipment items on this report already have a final status.
                            </p>
                        @endif
                    </div>
                @endif

                <div class="mb-6">
                    <label class="mb-3 block font-semibold text-white">
                        Change Status
                    </label>

                    <select
                        name="status"
                        required
                        class="w-full rounded-2xl border border-white/10 bg-[#0F172A] px-5 py-4 text-white focus:border-green-500 focus:outline-none"
                    >
                        @if ($report->report_current_status == "Pending")
                            <option value="Processing">Processing</option>
                            <option value="Rejected">Rejected</option>
                        @elseif ($report->report_current_status == "Processing" || $openItems->isNotEmpty())
                            <option value="Resolved">Resolved</option>
                            <option value="Rejected">Rejected</option>
                            <option value="For Replacement">
                                For Replacement
                            </option>
                        @else
                            <option
                                value="{{ $report->report_current_status }}"
                                selected
                            >
                                {{ $report->report_current_status }}
                            </option>
                        @endif
                    </select>
                </div>

                <div class="mb-6">
                    <label class="mb-3 block font-semibold text-white">
                        Remarks (optional)
                    </label>
                    <textarea
                        name="remarks"
                        rows="3"
                        class="w-full rounded-2xl border border-white/10 bg-[#0F172A] px-5 py-4 text-white focus:border-green-500 focus:outline-none"
                        placeholder="Repair notes or replacement reason..."
                    ></textarea>
                </div>

                <div class="mb-6">
                    <label class="mb-3 block font-semibold text-white">
                        Proof image (optional)
                    </label>
                    <input
                        type="file"
                        name="proof_image"
                        accept="image/*"
                        class="w-full rounded-2xl border border-white/10 bg-[#0F172A] px-5 py-4 text-white"
                    />
                </div>

                <div class="flex flex-col gap-4 sm:flex-row">
                    <button
                        type="submit"
                        class="rounded-2xl bg-green-600 px-8 py-4 font-bold transition hover:bg-green-700"
                    >
                        Update Status
                    </button>

                    <a
                        href="/maintenance/reports/details/{{ $report->report_id }}"
                        class="rounded-2xl bg-gray-700 px-8 py-4 text-center font-bold transition hover:bg-gray-600"
                    >
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

@endsection
