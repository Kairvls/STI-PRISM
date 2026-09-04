@extends('layouts.maintenance-layout')

@section('title', 'Log Walk-in Report')

@push('scripts')
<style>
    .swal2-container .swal2-popup.paayo-swal {
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        width: 400px !important;
        max-width: calc(100vw - 32px) !important;
        border-radius: 28px !important;
        border: 1px solid #e8ecf4 !important;
        background:
            radial-gradient(ellipse 80% 50% at 50% 0%, rgba(199, 216, 255, .45), transparent 62%),
            #ffffff !important;
        box-shadow: 0 32px 80px rgba(15, 23, 42, .18) !important;
        padding: 2.1rem 1.75rem 1.5rem !important;
    }
    .swal2-container .swal2-popup.paayo-swal .swal2-title,
    .paayo-swal-heading {
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        font-size: 1.4rem !important;
        font-weight: 800 !important;
        letter-spacing: -.035em !important;
        color: #1a1a2e !important;
        line-height: 1.2 !important;
        margin: 0 0 8px !important;
        padding: 0 !important;
    }
    .swal2-container .swal2-popup.paayo-swal .swal2-html-container,
    .paayo-swal-text {
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        color: #6b7280 !important;
        font-size: .95rem !important;
        line-height: 1.55 !important;
        margin: 0 auto !important;
        max-width: 300px;
    }
    .swal2-container .swal2-popup.paayo-swal .swal2-actions {
        margin: 22px 0 0 !important;
        width: 100% !important;
    }
    .swal2-container .swal2-popup.paayo-swal .swal2-confirm,
    .paayo-swal-btn {
        background: #0025cc !important;
        color: #fff !important;
        border: 0 !important;
        border-radius: 999px !important;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        font-weight: 600 !important;
        font-size: .95rem !important;
        padding: 12px 32px !important;
        min-width: 140px !important;
        box-shadow: 0 12px 28px rgba(0, 37, 204, .24) !important;
    }
    .swal2-container .swal2-popup.paayo-swal .swal2-icon {
        border: 0 !important;
        width: 58px !important;
        height: 58px !important;
        margin: 0 auto 1.05rem !important;
        border-radius: 18px !important;
        box-shadow: 0 12px 24px rgba(0, 37, 204, .18);
    }
    .swal2-container .swal2-popup.paayo-swal .swal2-icon.swal2-success,
    .swal2-container .swal2-popup.paayo-swal .paayo-icon-success {
        background: #0025cc !important;
        color: #fff !important;
    }
    .swal2-container .swal2-popup.paayo-swal .swal2-icon.swal2-error,
    .swal2-container .swal2-popup.paayo-swal .paayo-icon-error {
        background: #fef2f2 !important;
        color: #dc2626 !important;
    }
    .swal2-container .swal2-popup.paayo-swal .swal2-success-ring,
    .swal2-container .swal2-popup.paayo-swal .swal2-success-circular-line-left,
    .swal2-container .swal2-popup.paayo-swal .swal2-success-circular-line-right,
    .swal2-container .swal2-popup.paayo-swal .swal2-success-fix {
        display: none !important;
    }
    .swal2-container .swal2-popup.paayo-swal .swal2-icon svg {
        width: 26px !important;
        height: 26px !important;
    }
    .paayo-swal-in { animation: paayoSwalIn .28s ease; }
    @keyframes paayoSwalIn {
        from { opacity: 0; transform: translateY(18px) scale(.97); }
        to { opacity: 1; transform: none; }
    }

    .lr-equipment-picker {
        position: relative;
    }

    .lr-equipment-native {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
        pointer-events: none;
    }

    .lr-equipment-trigger {
        width: 100%;
        min-height: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        background: #fff;
        padding: 0 12px;
        text-align: left;
        font-size: 0.875rem;
        color: #1e293b;
        cursor: pointer;
    }

    .lr-equipment-trigger.is-placeholder {
        color: #64748b;
    }

    .lr-equipment-trigger.is-open,
    .lr-equipment-trigger:focus-visible {
        border-color: #94a3b8;
        outline: none;
        box-shadow: none;
    }

    .lr-equipment-trigger-label {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .lr-equipment-menu {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        z-index: 40;
        border-radius: 0.5rem;
        border: 1px solid #e2e8f0;
        background: #fff;
        box-shadow: none;
        padding: 4px;
    }

    .lr-equipment-menu.is-hidden {
        display: none;
    }

    .lr-equipment-list {
        max-height: 280px;
        overflow-y: auto;
        overflow-x: hidden;
        scrollbar-width: thin;
        scrollbar-color: #c7d2fe #f8fafc;
    }

    .lr-equipment-item {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        gap: 4px;
        border: 0;
        border-radius: 0.75rem;
        background: transparent;
        padding: 11px 14px;
        text-align: left;
        cursor: pointer;
        color: #1e293b;
    }

    .lr-equipment-item:hover,
    .lr-equipment-item.is-active {
        background: #f8fafc;
        color: #0f172a;
    }

    .lr-equipment-item-main {
        font-size: 0.875rem;
        font-weight: 500;
        line-height: 1.4;
    }

    .lr-equipment-item-sub {
        font-size: 0.75rem;
        font-weight: 400;
        line-height: 1.35;
        color: #94a3b8;
    }

    .lr-equipment-item.is-active .lr-equipment-item-sub {
        color: #64748b;
    }

    .lr-equipment-empty {
        padding: 16px 12px;
        text-align: center;
        font-size: 0.8125rem;
        color: #64748b;
    }

    .lr-issue-section {
        overflow: visible;
    }

    .lr-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .lr-field-label {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 12px;
        font-size: 0.8125rem;
        font-weight: 500;
        color: #475569;
    }

    .lr-field-hint {
        font-size: 0.75rem;
        font-weight: 400;
        color: #94a3b8;
    }

    .lr-issue-panel {
        min-height: 0;
        border-radius: 0.5rem;
        border: 1px solid #e2e8f0;
        background: #fff;
        padding: 12px;
    }

    .lr-issue-placeholder {
        font-size: 0.8125rem;
        line-height: 1.5;
        color: #94a3b8;
    }

    .lr-issue-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .lr-issue-chip {
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        background: #fff;
        padding: 6px 12px;
        font-size: 0.8125rem;
        font-weight: 500;
        line-height: 1.3;
        color: #475569;
        cursor: pointer;
        transition: border-color 0.15s ease, color 0.15s ease, background 0.15s ease;
    }

    .lr-issue-chip:hover {
        border-color: #cbd5e1;
        color: #0f172a;
    }

    .lr-issue-chip.is-active {
        border-color: #0025cc;
        background: #0025cc;
        color: #fff;
    }

    .lr-textarea {
        width: 100%;
        min-height: 96px;
        resize: vertical;
        border-radius: 0.5rem;
        border: 1px solid #e2e8f0;
        background: #fff;
        padding: 10px 12px;
        font-size: 0.875rem;
        line-height: 1.55;
        color: #0f172a;
        outline: none;
        transition: border-color 0.15s ease;
    }

    .lr-textarea:focus {
        border-color: #94a3b8;
    }

    .lr-priority-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .lr-priority-option {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        background: #fff;
        padding: 10px 14px;
        font-size: 0.875rem;
        font-weight: 500;
        color: #334155;
        cursor: pointer;
        transition: border-color 0.15s ease, background 0.15s ease;
    }

    .lr-priority-option:hover {
        border-color: #cbd5e1;
    }

    .lr-priority-option.is-active {
        border-color: #0f172a;
        background: #f8fafc;
    }

    .lr-priority-option input {
        margin: 0;
        accent-color: #0f172a;
    }

    .lr-date-input {
        width: 100%;
        max-width: 16rem;
        height: 40px;
        border-radius: 0.5rem;
        border: 1px solid #e2e8f0;
        background: #fff;
        padding: 0 12px;
        font-size: 0.875rem;
        color: #0f172a;
        outline: none;
        transition: border-color 0.15s ease;
        color-scheme: light;
    }

    .lr-date-input:focus {
        border-color: #94a3b8;
    }

    .lr-preferred-hint {
        font-size: 0.75rem;
        line-height: 1.45;
        color: #94a3b8;
    }

    .lr-upload-zone {
        position: relative;
        display: block;
        border-radius: 0.5rem;
        border: 1px dashed #d1d5db;
        background: #fff;
        padding: 14px;
        cursor: pointer;
        transition: border-color 0.15s ease;
    }

    .lr-upload-zone:hover {
        border-color: #94a3b8;
    }

    .lr-upload-zone.has-file {
        border-style: solid;
        border-color: #e2e8f0;
    }

    .lr-upload-zone input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }

    .lr-upload-copy {
        font-size: 0.8125rem;
        line-height: 1.5;
        color: #64748b;
    }

    .lr-upload-copy strong {
        font-weight: 500;
        color: #0f172a;
    }

    .lr-upload-preview {
        display: none;
        width: 100%;
        max-height: 160px;
        margin-top: 10px;
        object-fit: contain;
        border-radius: 0.375rem;
        border: 1px solid #e2e8f0;
    }

    .lr-upload-zone.has-file .lr-upload-preview {
        display: block;
    }

    .lr-upload-remove {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 2;
        display: none;
        border: 0;
        background: transparent;
        padding: 4px;
        font-size: 0.75rem;
        font-weight: 500;
        color: #64748b;
        cursor: pointer;
    }

    .lr-upload-zone.has-file .lr-upload-remove {
        display: inline-flex;
    }

    .lr-upload-remove:hover {
        color: #0f172a;
    }
</style>
@endpush

@section('content')
    <div >
        <div class="mb-6">
            <a href="/maintenance/reports" class="inline-flex items-center gap-1 text-sm text-slate-500 transition hover:text-slate-800">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Back to Reports
            </a>
            <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900">Log Walk-in Report</h1>
            <p class="mt-1 max-w-2xl text-sm text-slate-500">
                Enter a concern on behalf of a faculty or staff reporter who walked in or cannot use the online form.
                The reporter must already be registered and active.
            </p>
        </div>

        <div class="mb-5 rounded-2xl border border-blue-100 bg-blue-50/70 px-4 py-3 text-sm text-blue-900">
            <strong>Audit trail:</strong> this form records you as the staff member who logged the report, while keeping the walk-in person as the reporter.
        </div>

        <form
            method="POST"
            action="{{ route('maintenance.reports.log.store') }}"
            enctype="multipart/form-data"
            id="walkInReportForm"
            class="space-y-5"
        >
            @csrf

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Reporter</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="employeeIdInput" class="mb-1.5 block text-sm font-medium text-slate-700">Employee ID</label>
                        <input
                            type="text"
                            name="report_reporter_employee_id"
                            id="employeeIdInput"
                            value="{{ old('report_reporter_employee_id') }}"
                            required
                            autocomplete="off"
                            placeholder="Enter reporter employee ID"
                            class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm text-slate-800 outline-none ring-blue-500/30 focus:border-blue-500 focus:ring-2"
                        >
                    </div>
                    <p id="employeeError" class="hidden sm:col-span-2 text-sm text-red-500"></p>
                    <div id="pendingReporterBox" class="hidden sm:col-span-2 rounded-xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        This reporter application is still waiting for maintenance approval. You can log reports after they are confirmed as faculty or staff.
                    </div>
                    <div id="reporterInfoBox" class="hidden sm:col-span-2 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                        <p class="font-semibold" id="reporterName"></p>
                        <p class="mt-0.5 text-emerald-800/80" id="reporterMeta"></p>
                    </div>
                    <div id="reporterErrorBox" class="hidden sm:col-span-2 rounded-xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-800"></div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Location & Equipment</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="roomSelect" class="mb-1.5 block text-sm font-medium text-slate-700">Location</label>
                        <select
                            name="report_room_id"
                            id="roomSelect"
                            required
                            class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm text-slate-800 outline-none ring-blue-500/30 focus:border-blue-500 focus:ring-2"
                        >
                            <option value="">Select location</option>
                            @foreach ($rooms as $room)
                                <option value="{{ $room->room_id }}" @selected((string) old('report_room_id') === (string) $room->room_id)>
                                    {{ $room->floor_level }} - {{ $room->room_name }} - {{ (int) ($room->equipment_count ?? 0) }}
                                </option>
                            @endforeach
                        </select>
                        <p id="locationError" class="mt-1 hidden text-sm text-red-500">Please select a location.</p>
                    </div>

                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label for="equipmentSelect" class="text-sm font-medium text-slate-700">Equipment</label>
                            <button type="button" id="toggleManualEquipment" class="text-xs font-semibold text-[#0025cc] hover:underline">
                                Not listed?
                            </button>
                        </div>
                        <div id="equipmentDropdownWrap" class="flex gap-2">
                            <div class="lr-equipment-picker min-w-0 flex-1">
                                <select
                                    id="equipmentSelect"
                                    class="lr-equipment-native"
                                    tabindex="-1"
                                    aria-hidden="true"
                                >
                                    <option value="">Select equipment</option>
                                </select>
                                <button
                                    type="button"
                                    id="equipmentPickerTrigger"
                                    class="lr-equipment-trigger is-placeholder"
                                    aria-haspopup="listbox"
                                    aria-expanded="false"
                                >
                                    <span id="equipmentPickerLabel" class="lr-equipment-trigger-label">Select equipment</span>
                                    <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-slate-500"></i>
                                </button>
                                <div id="equipmentPickerMenu" class="lr-equipment-menu is-hidden" role="listbox" aria-label="Equipment options">
                                    <div id="equipmentPickerList" class="lr-equipment-list"></div>
                                </div>
                            </div>
                            <button type="button" id="addEquipmentBtn" class="h-11 shrink-0 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001fa8]">
                                Add
                            </button>
                        </div>
                        <div id="equipmentManualWrap" class="hidden">
                            <div class="flex gap-2">
                                <input
                                    type="text"
                                    id="equipmentManualInput"
                                    maxlength="255"
                                    placeholder="Equipment name"
                                    class="h-11 min-w-0 flex-1 rounded-xl border border-slate-200 px-3 text-sm text-slate-800 outline-none ring-blue-500/30 focus:border-blue-500 focus:ring-2"
                                >
                                <button type="button" id="addManualEquipmentBtn" class="h-11 shrink-0 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001fa8]">
                                    Add
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="selectedEquipmentList" class="mt-4 space-y-2"></div>
                <p id="equipmentError" class="mt-1 hidden text-sm text-red-500"></p>
                <div id="selectedEquipmentInputs"></div>
            </section>

            <section class="lr-issue-section overflow-visible rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
                <h2 class="mb-5 text-sm font-medium text-slate-700">Issue & Priority</h2>

                <div class="space-y-5">
                    <div class="lr-field">
                        <div class="lr-field-label">
                            <span>Suggested issue</span>
                            <span class="lr-field-hint">Optional</span>
                        </div>
                        <div id="suggestedIssuesWrap" class="lr-issue-panel">
                            <p class="lr-issue-placeholder">Select equipment and click Add to load suggested issues.</p>
                        </div>
                        <input type="hidden" name="report_suggested_issue" id="suggestedIssueInput" value="{{ old('report_suggested_issue') }}">
                        <p id="issueError" class="hidden text-sm text-red-500">Please select a suggested issue or provide additional details.</p>
                    </div>

                    <div class="lr-field">
                        <div class="lr-field-label">
                            <label for="problemDescription">Additional details</label>
                            <span class="lr-field-hint">Optional</span>
                        </div>
                        <textarea
                            name="report_problem_description"
                            id="problemDescription"
                            rows="3"
                            placeholder="Add context if needed"
                            class="lr-textarea"
                        >{{ old('report_problem_description') }}</textarea>
                    </div>

                    <div class="lr-field">
                        <div class="lr-field-label">
                            <span>Type of report</span>
                        </div>
                        <div class="lr-priority-row">
                            <label class="lr-priority-option" id="priorityCardNonUrgent">
                                <input type="radio" name="report_urgency_level" value="Non-Urgent" @checked(old('report_urgency_level', 'Non-Urgent') === 'Non-Urgent')>
                                Non-Urgent
                            </label>
                            <label class="lr-priority-option" id="priorityCardUrgent">
                                <input type="radio" name="report_urgency_level" value="Urgent" @checked(old('report_urgency_level') === 'Urgent')>
                                Urgent
                            </label>
                        </div>
                    </div>

                    <div id="preferredDateWrap" class="lr-field">
                        <div class="lr-field-label">
                            <label for="preferredActionDate">Preferred action date</label>
                            <span class="lr-field-hint">Optional</span>
                        </div>
                        <input
                            type="date"
                            name="report_preferred_action_date"
                            id="preferredActionDate"
                            value="{{ old('report_preferred_action_date') }}"
                            min="{{ \App\Support\ReportGrouping::preferredActionDateMinimum() }}"
                            class="lr-date-input"
                        >
                        <p class="lr-preferred-hint">
                            Earliest date is 2 days from today.
                        </p>
                    </div>

                    <div class="lr-field">
                        <div class="lr-field-label">
                            <span>Photo</span>
                            <span class="lr-field-hint">Optional</span>
                        </div>
                        <label for="reportImage" class="lr-upload-zone" id="uploadZone">
                            <input
                                type="file"
                                name="report_uploaded_image"
                                id="reportImage"
                                accept="image/jpeg,image/png,image/webp"
                            >
                            <button type="button" class="lr-upload-remove" id="removeUploadBtn" aria-label="Remove photo">
                                Remove
                            </button>
                            <p class="lr-upload-copy"><strong>Upload photo</strong> · PNG, JPG, WEBP up to 10MB</p>
                            <img id="uploadPreview" class="lr-upload-preview" alt="Selected report photo preview">
                        </label>
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap items-center justify-end gap-3 pb-8">
                <a href="/maintenance/reports" class="inline-flex h-11 items-center rounded-xl border border-slate-200 px-5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancel
                </a>
                <button type="submit" class="inline-flex h-11 items-center rounded-xl bg-[#0025cc] px-6 text-sm font-semibold text-white shadow-sm hover:bg-[#001fa8]">
                    Log report
                </button>
            </div>
        </form>

        <section class="mt-10 pb-8">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold tracking-tight text-slate-900">Walk-in report log</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Reports entered through this page, with who logged them and who reported the concern.
                    </p>
                </div>
                @if ($walkInReports instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        {{ $walkInReports->total() }} record{{ $walkInReports->total() === 1 ? '' : 's' }}
                    </p>
                @endif
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                @if ($walkInReports instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $walkInReports->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Ticket</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Logged</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Reporter</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Location</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Equipment</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Logged by</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($walkInReports as $report)
                                    @php
                                        $statusClasses = match ($report->report_current_status) {
                                            'Pending' => 'bg-orange-50 text-orange-700',
                                            'Processing' => 'bg-sky-50 text-sky-700',
                                            'Resolved' => 'bg-emerald-50 text-emerald-700',
                                            'Rejected' => 'bg-rose-50 text-rose-700',
                                            'For Replacement' => 'bg-amber-50 text-amber-700',
                                            default => 'bg-slate-100 text-slate-600',
                                        };
                                        $urgencyClasses = ($report->report_urgency_level ?? '') === 'Urgent'
                                            ? 'bg-rose-50 text-rose-700'
                                            : 'bg-slate-100 text-slate-600';
                                        $equipmentLabel = $report->equipment_display
                                            ?? $report->equipment_name
                                            ?? $report->report_unlisted_equipment_name
                                            ?? 'Unlisted equipment';
                                        $locationLabel = trim(collect([
                                            $report->floor_level ?? null,
                                            $report->room_name ?? null,
                                        ])->filter()->implode(' · ')) ?: '—';
                                    @endphp
                                    <tr class="hover:bg-slate-50/80">
                                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">
                                            {{ \App\Support\ReportGrouping::ticketCode($report) }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                                            {{ $report->report_submitted_at ? \Carbon\Carbon::parse($report->report_submitted_at)->format('M d, Y g:i A') : '—' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="font-medium text-slate-800">{{ $report->reporter_full_name ?? 'Unknown' }}</p>
                                            <p class="text-xs text-slate-500">{{ $report->report_reporter_employee_id ?? '—' }}</p>
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">{{ $locationLabel }}</td>
                                        <td class="max-w-[220px] truncate px-4 py-3 text-slate-700" title="{{ $equipmentLabel }}">
                                            {{ $equipmentLabel }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $urgencyClasses }}">
                                                {{ $report->report_urgency_level ?? '—' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusClasses }}">
                                                {{ $report->report_current_status ?? '—' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">
                                            {{ $report->report_logged_by_name ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right">
                                            <a
                                                href="{{ url('/maintenance/reports/details/' . $report->report_id) }}"
                                                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-[#0025cc] transition hover:bg-blue-50"
                                            >
                                                View
                                                <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($walkInReports->hasPages())
                        <div class="border-t border-slate-200 px-4 py-3">
                            {{ $walkInReports->links() }}
                        </div>
                    @endif
                @else
                    <div class="px-6 py-12 text-center">
                        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <i data-lucide="clipboard-list" class="h-5 w-5"></i>
                        </div>
                        <p class="text-sm font-semibold text-slate-700">No walk-in reports logged yet</p>
                        <p class="mt-1 text-sm text-slate-500">Submitted walk-in reports will appear here with the audit trail.</p>
                    </div>
                @endif
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const employeeInput = document.getElementById('employeeIdInput');
            const reporterInfoBox = document.getElementById('reporterInfoBox');
            const reporterErrorBox = document.getElementById('reporterErrorBox');
            const reporterName = document.getElementById('reporterName');
            const reporterMeta = document.getElementById('reporterMeta');
            const roomSelect = document.getElementById('roomSelect');
            const equipmentSelect = document.getElementById('equipmentSelect');
            const equipmentPickerTrigger = document.getElementById('equipmentPickerTrigger');
            const equipmentPickerLabel = document.getElementById('equipmentPickerLabel');
            const equipmentPickerMenu = document.getElementById('equipmentPickerMenu');
            const equipmentPickerList = document.getElementById('equipmentPickerList');
            const equipmentManualInput = document.getElementById('equipmentManualInput');
            const equipmentDropdownWrap = document.getElementById('equipmentDropdownWrap');
            const equipmentManualWrap = document.getElementById('equipmentManualWrap');
            const toggleManualEquipment = document.getElementById('toggleManualEquipment');
            const addEquipmentBtn = document.getElementById('addEquipmentBtn');
            const addManualEquipmentBtn = document.getElementById('addManualEquipmentBtn');
            const selectedEquipmentList = document.getElementById('selectedEquipmentList');
            const selectedEquipmentInputs = document.getElementById('selectedEquipmentInputs');
            const suggestedIssuesWrap = document.getElementById('suggestedIssuesWrap');
            const suggestedIssueInput = document.getElementById('suggestedIssueInput');
            const problemDescription = document.getElementById('problemDescription');
            const preferredDateWrap = document.getElementById('preferredDateWrap');
            const preferredActionDate = document.getElementById('preferredActionDate');
            const priorityCardNonUrgent = document.getElementById('priorityCardNonUrgent');
            const priorityCardUrgent = document.getElementById('priorityCardUrgent');
            const uploadZone = document.getElementById('uploadZone');
            const reportImageInput = document.getElementById('reportImage');
            const uploadPreview = document.getElementById('uploadPreview');
            const removeUploadBtn = document.getElementById('removeUploadBtn');
            const employeeError = document.getElementById('employeeError');
            const locationError = document.getElementById('locationError');
            const equipmentError = document.getElementById('equipmentError');
            const issueError = document.getElementById('issueError');
            const pendingReporterBox = document.getElementById('pendingReporterBox');
            const walkInReportForm = document.getElementById('walkInReportForm');

            let manualMode = false;
            let roomEquipmentCache = [];
            let selectedItems = [];
            let reporterVerified = false;
            let reporterPending = false;

            function hideFormErrors() {
                employeeError.classList.add('hidden');
                employeeError.textContent = '';
                locationError.classList.add('hidden');
                equipmentError.classList.add('hidden');
                equipmentError.textContent = '';
                issueError.classList.add('hidden');
                reporterErrorBox.classList.add('hidden');
                pendingReporterBox.classList.add('hidden');
                employeeInput.style.borderColor = '';
                roomSelect.style.borderColor = '';
                equipmentSelect.style.borderColor = '';
                if (equipmentPickerTrigger) {
                    equipmentPickerTrigger.style.borderColor = '';
                }
                if (equipmentManualInput) {
                    equipmentManualInput.style.borderColor = '';
                }
            }

            function getIssueForAdd() {
                return (suggestedIssueInput.value || '').trim() || (problemDescription.value || '').trim();
            }

            function renderSelectedItems() {
                selectedEquipmentList.innerHTML = '';
                selectedEquipmentInputs.innerHTML = '';

                selectedItems.forEach(function (item, index) {
                    const row = document.createElement('div');
                    row.className = 'flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2';
                    row.innerHTML =
                        '<div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-800"></p>' +
                        '<p class="truncate text-xs text-slate-500"></p></div>' +
                        '<button type="button" class="shrink-0 rounded-lg bg-rose-50 px-2 py-1 text-[11px] font-bold text-rose-600 hover:bg-rose-100">Remove</button>';
                    row.querySelector('p:first-child').textContent = item.name;
                    row.querySelector('p:last-child').textContent = item.issue ? 'Issue: ' + item.issue : 'No issue set';
                    if (item.openReportTicket) {
                        const openNote = document.createElement('p');
                        openNote.className = 'mt-0.5 truncate text-xs text-slate-500';
                        openNote.textContent = 'Open report ' + item.openReportTicket + ' — submit will add your update there.';
                        row.querySelector('.min-w-0').appendChild(openNote);
                    }
                    row.querySelector('button').addEventListener('click', function () {
                        selectedItems.splice(index, 1);
                        renderSelectedItems();
                        rebuildEquipmentSelect();
                    });
                    selectedEquipmentList.appendChild(row);

                    if (item.type === 'id') {
                        const idInput = document.createElement('input');
                        idInput.type = 'hidden';
                        idInput.name = 'report_equipment_ids[]';
                        idInput.value = item.id;
                        selectedEquipmentInputs.appendChild(idInput);

                        const issueInput = document.createElement('input');
                        issueInput.type = 'hidden';
                        issueInput.name = 'report_equipment_issues[]';
                        issueInput.value = item.issue || '';
                        selectedEquipmentInputs.appendChild(issueInput);
                    } else {
                        const nameInput = document.createElement('input');
                        nameInput.type = 'hidden';
                        nameInput.name = 'report_equipment_manuals[]';
                        nameInput.value = item.name;
                        selectedEquipmentInputs.appendChild(nameInput);

                        const issueInput = document.createElement('input');
                        issueInput.type = 'hidden';
                        issueInput.name = 'report_equipment_manual_issues[]';
                        issueInput.value = item.issue || '';
                        selectedEquipmentInputs.appendChild(issueInput);
                    }
                });
            }

            function getAddedEquipmentIds() {
                return new Set(
                    selectedItems
                        .filter(function (item) { return item.type === 'id'; })
                        .map(function (item) { return String(item.id); })
                );
            }

            function formatEquipmentPrimaryLabel(equipment) {
                const name = String(equipment.equipment_name || 'Equipment').trim();
                const tag = String(equipment.equipment_asset_tag || '').trim();
                return tag ? name + ' · #' + tag : name + ' · #' + equipment.equipment_id;
            }

            function syncEquipmentPickerLabel() {
                if (!equipmentPickerLabel || !equipmentPickerTrigger) return;
                const option = equipmentSelect.options[equipmentSelect.selectedIndex];
                const label = option && option.value
                    ? option.textContent.trim()
                    : 'Select equipment';
                equipmentPickerLabel.textContent = label;
                equipmentPickerTrigger.classList.toggle('is-placeholder', !equipmentSelect.value);
            }

            function closeEquipmentPicker() {
                if (!equipmentPickerMenu || !equipmentPickerTrigger) return;
                equipmentPickerMenu.classList.add('is-hidden');
                equipmentPickerTrigger.classList.remove('is-open');
                equipmentPickerTrigger.setAttribute('aria-expanded', 'false');
            }

            function openEquipmentPicker() {
                if (!equipmentPickerMenu || !equipmentPickerTrigger) return;
                renderEquipmentPickerList();
                equipmentPickerMenu.classList.remove('is-hidden');
                equipmentPickerTrigger.classList.add('is-open');
                equipmentPickerTrigger.setAttribute('aria-expanded', 'true');
            }

            function renderEquipmentPickerList() {
                if (!equipmentPickerList) return;

                const added = getAddedEquipmentIds();
                equipmentPickerList.innerHTML = '';
                let visibleCount = 0;

                roomEquipmentCache.forEach(function (equipment) {
                    const id = String(equipment.equipment_id || '');
                    if (!id || added.has(id)) return;

                    visibleCount += 1;
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'lr-equipment-item' + (equipmentSelect.value === id ? ' is-active' : '');
                    item.innerHTML =
                        '<span class="lr-equipment-item-main"></span>' +
                        (equipment.open_report_ticket_code
                            ? '<span class="lr-equipment-item-sub">Open report: ' + equipment.open_report_ticket_code + '</span>'
                            : '');
                    item.querySelector('.lr-equipment-item-main').textContent = formatEquipmentPrimaryLabel(equipment);
                    item.addEventListener('click', function () {
                        equipmentSelect.value = id;
                        equipmentSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        syncEquipmentPickerLabel();
                        closeEquipmentPicker();
                    });
                    equipmentPickerList.appendChild(item);
                });

                if (visibleCount === 0) {
                    equipmentPickerList.innerHTML = '<div class="lr-equipment-empty">No equipment available for this room.</div>';
                }
            }

            function rebuildEquipmentSelect() {
                const added = getAddedEquipmentIds();
                equipmentSelect.innerHTML = '<option value="">Select equipment</option>';
                roomEquipmentCache.forEach(function (equipment) {
                    const id = String(equipment.equipment_id || '');
                    if (!id || added.has(id)) return;
                    const option = document.createElement('option');
                    option.value = id;
                    option.textContent = formatEquipmentPrimaryLabel(equipment);
                    equipmentSelect.appendChild(option);
                });
                syncEquipmentPickerLabel();
                renderEquipmentPickerList();
            }

            function clearSuggestedIssues(message) {
                suggestedIssuesWrap.innerHTML =
                    '<p class="lr-issue-placeholder">' +
                    (message || 'Select equipment and click Add to load suggested issues.') +
                    '</p>';
                suggestedIssueInput.value = '';
            }

            function renderSuggestedIssues(issues) {
                const selectedIssue = suggestedIssueInput.value;
                suggestedIssuesWrap.innerHTML = '';

                if (!issues.length) {
                    clearSuggestedIssues('No suggested issues for this equipment. Use additional details.');
                    return;
                }

                const chipsWrap = document.createElement('div');
                chipsWrap.className = 'lr-issue-chips';

                issues.forEach(function (issue) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'lr-issue-chip' + (selectedIssue === issue ? ' is-active' : '');
                    btn.textContent = issue;
                    btn.addEventListener('click', function () {
                        chipsWrap.querySelectorAll('.lr-issue-chip').forEach(function (el) {
                            el.classList.remove('is-active');
                        });
                        btn.classList.add('is-active');
                        suggestedIssueInput.value = issue;
                        issueError.classList.add('hidden');
                    });
                    chipsWrap.appendChild(btn);
                });

                suggestedIssuesWrap.appendChild(chipsWrap);
            }

            function updatePriorityCards() {
                document.querySelectorAll('.lr-priority-option').forEach(function (card) {
                    card.classList.remove('is-active');
                });

                const checked = document.querySelector('input[name="report_urgency_level"]:checked');
                if (checked) {
                    checked.closest('.lr-priority-option')?.classList.add('is-active');
                }
            }

            function clearUploadPreview() {
                if (!reportImageInput || !uploadZone || !uploadPreview) return;
                reportImageInput.value = '';
                uploadZone.classList.remove('has-file');
                uploadPreview.removeAttribute('src');
            }

            function loadSuggestions(equipmentId) {
                if (!equipmentId) {
                    clearSuggestedIssues();
                    return;
                }
                fetch('/get-suggestions/' + equipmentId)
                    .then(function (r) { return r.json(); })
                    .then(function (data) { renderSuggestedIssues(Array.isArray(data) ? data : []); })
                    .catch(function () { clearSuggestedIssues('Could not load suggested issues.'); });
            }

            const genericIssueSuggestions = [
                'Not Functioning',
                'Physical Damage',
                'Missing Parts',
                'Needs Inspection',
                'Needs Replacement',
                'Cannot Operate',
                'Electrical Issue',
                'Connectivity Issue',
                'Power Failure',
                'Malfunctioning Component',
            ];

            function loadGenericSuggestions() {
                renderSuggestedIssues(genericIssueSuggestions);
            }

            function toggleUrgencyFields() {
                const urgent = document.querySelector('input[name="report_urgency_level"]:checked')?.value === 'Urgent';
                preferredDateWrap.classList.toggle('hidden', urgent);
                if (preferredActionDate) {
                    preferredActionDate.disabled = urgent;
                    if (urgent) {
                        preferredActionDate.value = '';
                    }
                }
                updatePriorityCards();
            }

            employeeInput.addEventListener('input', function () {
                const id = this.value.trim();
                reporterVerified = false;
                reporterPending = false;
                reporterInfoBox.classList.add('hidden');
                reporterErrorBox.classList.add('hidden');
                pendingReporterBox.classList.add('hidden');
                employeeError.classList.add('hidden');
                employeeInput.style.borderColor = '';
                if (id.length < 8) return;

                fetch('/get-reporter/' + encodeURIComponent(id))
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data && data.reporter_status === 'Pending Approval') {
                            reporterPending = true;
                            reporterErrorBox.textContent = 'This reporter application is still waiting for maintenance approval.';
                            reporterErrorBox.classList.remove('hidden');
                            return;
                        }
                        if (data && data.reporter_full_name && data.reporter_status === 'Active') {
                            reporterVerified = true;
                            reporterName.textContent = data.reporter_full_name;
                            reporterMeta.textContent = 'Active reporter';
                            reporterInfoBox.classList.remove('hidden');
                            return;
                        }
                        if (data && data.reporter_full_name && data.reporter_status !== 'Active') {
                            reporterErrorBox.textContent = 'This reporter account is inactive and cannot submit maintenance reports.';
                            reporterErrorBox.classList.remove('hidden');
                            return;
                        }
                        reporterErrorBox.textContent = 'Employee ID not recognized.';
                        reporterErrorBox.classList.remove('hidden');
                    })
                    .catch(function () {
                        reporterErrorBox.textContent = 'Could not verify employee ID.';
                        reporterErrorBox.classList.remove('hidden');
                    });
            });

            roomSelect.addEventListener('change', function () {
                locationError.classList.add('hidden');
                roomSelect.style.borderColor = '';
                const roomId = this.value;
                selectedItems = [];
                renderSelectedItems();
                roomEquipmentCache = [];
                closeEquipmentPicker();
                equipmentSelect.innerHTML = '<option value="">Select equipment</option>';
                syncEquipmentPickerLabel();
                clearSuggestedIssues();
                if (!roomId) return;

                fetch('/get-equipment/' + roomId)
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        roomEquipmentCache = Array.isArray(data) ? data : [];
                        rebuildEquipmentSelect();
                    });
            });

            equipmentSelect.addEventListener('change', function () {
                loadSuggestions(this.value);
                syncEquipmentPickerLabel();
            });

            if (equipmentPickerTrigger) {
                equipmentPickerTrigger.addEventListener('click', function () {
                    if (equipmentPickerMenu.classList.contains('is-hidden')) {
                        openEquipmentPicker();
                    } else {
                        closeEquipmentPicker();
                    }
                });
            }

            document.addEventListener('click', function (event) {
                if (!equipmentPickerMenu || equipmentPickerMenu.classList.contains('is-hidden')) return;
                if (event.target.closest('.lr-equipment-picker')) return;
                closeEquipmentPicker();
            });

            toggleManualEquipment.addEventListener('click', function () {
                manualMode = !manualMode;
                equipmentDropdownWrap.classList.toggle('hidden', manualMode);
                equipmentManualWrap.classList.toggle('hidden', !manualMode);
                toggleManualEquipment.textContent = manualMode ? 'Use listed equipment' : 'Not listed?';
                equipmentError.classList.add('hidden');
                issueError.classList.add('hidden');
                suggestedIssueInput.value = '';

                if (manualMode) {
                    equipmentSelect.value = '';
                    closeEquipmentPicker();
                    syncEquipmentPickerLabel();
                    loadGenericSuggestions();
                } else {
                    equipmentManualInput.value = '';
                    if (equipmentSelect.value) {
                        loadSuggestions(equipmentSelect.value);
                    } else {
                        clearSuggestedIssues('Select equipment and click Add to load suggested issues.');
                    }
                }
            });

            addEquipmentBtn.addEventListener('click', function () {
                const equipmentId = equipmentSelect.value;
                const issue = getIssueForAdd();

                equipmentError.classList.add('hidden');
                equipmentSelect.style.borderColor = '';
                if (equipmentPickerTrigger) {
                    equipmentPickerTrigger.style.borderColor = '';
                }
                issueError.classList.add('hidden');

                if (!equipmentId) {
                    equipmentError.textContent = 'Please select equipment, then a suggested issue or additional details, then Add.';
                    equipmentError.classList.remove('hidden');
                    if (equipmentPickerTrigger) {
                        equipmentPickerTrigger.style.borderColor = '#dc2626';
                        equipmentPickerTrigger.focus();
                    }
                    return;
                }

                if (!issue) {
                    equipmentError.textContent = 'Please select a suggested issue or provide additional details before adding this equipment.';
                    equipmentError.classList.remove('hidden');
                    issueError.classList.remove('hidden');
                    return;
                }

                if (selectedItems.some(function (item) { return item.type === 'id' && String(item.id) === String(equipmentId); })) {
                    equipmentError.textContent = 'That equipment is already added.';
                    equipmentError.classList.remove('hidden');
                    return;
                }

                const equipmentFromCache = roomEquipmentCache.find(function (equipment) {
                    return String(equipment.equipment_id || '') === String(equipmentId);
                });
                const label = equipmentFromCache
                    ? formatEquipmentPrimaryLabel(equipmentFromCache)
                    : equipmentSelect.options[equipmentSelect.selectedIndex].textContent.trim();
                selectedItems.push({
                    type: 'id',
                    id: equipmentId,
                    name: label,
                    issue: issue,
                    openReportTicket: equipmentFromCache?.open_report_ticket_code || '',
                });
                renderSelectedItems();
                rebuildEquipmentSelect();
                equipmentSelect.value = '';
                clearSuggestedIssues();
            });

            addManualEquipmentBtn.addEventListener('click', function () {
                const name = equipmentManualInput.value.trim();
                const issue = getIssueForAdd();

                equipmentError.classList.add('hidden');
                equipmentManualInput.style.borderColor = '';
                issueError.classList.add('hidden');

                if (!name) {
                    equipmentError.textContent = 'Please enter an equipment name.';
                    equipmentError.classList.remove('hidden');
                    equipmentManualInput.style.borderColor = '#dc2626';
                    return;
                }

                if (!issue) {
                    equipmentError.textContent = 'Please select a suggested issue or provide additional details before adding this equipment.';
                    equipmentError.classList.remove('hidden');
                    issueError.classList.remove('hidden');
                    return;
                }

                if (selectedItems.some(function (item) {
                    return item.type === 'manual' && item.name.toLowerCase() === name.toLowerCase();
                })) {
                    equipmentError.textContent = 'That equipment name is already added.';
                    equipmentError.classList.remove('hidden');
                    return;
                }

                selectedItems.push({ type: 'manual', name: name, issue: issue });
                renderSelectedItems();
                equipmentManualInput.value = '';
                clearSuggestedIssues();
                loadGenericSuggestions();
            });

            document.querySelectorAll('input[name="report_urgency_level"]').forEach(function (radio) {
                radio.addEventListener('change', toggleUrgencyFields);
            });
            toggleUrgencyFields();

            if (removeUploadBtn) {
                removeUploadBtn.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    clearUploadPreview();
                });
            }

            if (reportImageInput) {
                reportImageInput.addEventListener('change', function () {
                    const file = this.files && this.files[0];
                    if (!file) {
                        clearUploadPreview();
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function (event) {
                        uploadPreview.src = event.target.result;
                        uploadZone.classList.add('has-file');
                    };
                    reader.readAsDataURL(file);
                });
            }

            walkInReportForm.addEventListener('submit', function (e) {
                hideFormErrors();

                const roomId = roomSelect.value;
                if (!roomId) {
                    e.preventDefault();
                    locationError.classList.remove('hidden');
                    roomSelect.style.borderColor = '#dc2626';
                    roomSelect.focus();
                    return;
                }

                if (!selectedItems.length) {
                    e.preventDefault();
                    equipmentError.textContent = 'Please add at least one equipment.';
                    equipmentError.classList.remove('hidden');
                    if (manualMode) {
                        equipmentManualInput.style.borderColor = '#dc2626';
                        equipmentManualInput.focus();
                    } else {
                        if (equipmentPickerTrigger) {
                            equipmentPickerTrigger.style.borderColor = '#dc2626';
                            equipmentPickerTrigger.focus();
                        }
                    }
                    return;
                }

                const itemsMissingIssue = selectedItems.filter(function (item) {
                    return !String(item.issue || '').trim();
                });

                if (itemsMissingIssue.length > 0) {
                    e.preventDefault();
                    equipmentError.textContent = 'Each equipment in the list needs a suggested issue. Remove incomplete items and add them again.';
                    equipmentError.classList.remove('hidden');
                    return;
                }

                if (suggestedIssueInput && selectedItems.length > 0) {
                    suggestedIssueInput.value = selectedItems[0].issue || '';
                }

                if (reporterPending) {
                    e.preventDefault();
                    pendingReporterBox.classList.remove('hidden');
                    pendingReporterBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }

                if (!reporterVerified) {
                    e.preventDefault();
                    employeeError.textContent = 'Employee ID not recognized.';
                    employeeError.classList.remove('hidden');
                    employeeInput.style.borderColor = '#dc2626';
                    employeeInput.focus();
                }
            });

            if (window.lucide) lucide.createIcons();

            if (employeeInput.value.trim().length >= 8) {
                employeeInput.dispatchEvent(new Event('input'));
            }

            if (roomSelect.value) {
                roomSelect.dispatchEvent(new Event('change'));
            }

            function paayoSwal(options) {
                const tone = options.tone || 'success';
                const swalIcon = tone === 'error' ? 'error' : 'success';
                const icons = {
                    success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
                    error: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/></svg>',
                };

                return Swal.fire({
                    icon: swalIcon,
                    iconHtml: icons[tone] || icons.success,
                    title: options.title || '',
                    text: options.html ? undefined : (options.text || ''),
                    html: options.html || undefined,
                    showConfirmButton: options.showConfirmButton !== false,
                    confirmButtonText: options.confirmText || 'OK',
                    buttonsStyling: false,
                    backdrop: 'rgba(11, 18, 32, 0.7)',
                    width: 400,
                    timer: options.timer,
                    timerProgressBar: !!options.timer,
                    showClass: { popup: 'swal2-show paayo-swal-in' },
                    customClass: {
                        popup: 'paayo-swal',
                        title: 'paayo-swal-heading',
                        htmlContainer: 'paayo-swal-text',
                        confirmButton: 'paayo-swal-btn',
                        icon: 'paayo-icon-' + tone,
                    },
                });
            }

        });
    </script>
@endsection
