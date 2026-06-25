{{-- ══════════════════════════════════════════════════════════════
     MAINTENANCE REPORT FORM  ·  PRISM Dark Theme
     reporter/partials/report-form.blade.php
══════════════════════════════════════════════════════════════ --}}

<style>
    /* ── TOKENS ─────────────────────────────────────────────── */
    .rf-input {
        width: 100%;
        background: #ffffff;
        border: 1px solid rgba(41, 71, 240, 0.15);
        border-radius: 14px;
        padding: 11px 14px;
        font-size: 14px;
        color: #0f172a;
        font-family: "Inter", sans-serif;
        outline: none;
        transition:
            border-color 0.2s,
            background 0.2s;
        appearance: none;
        -webkit-appearance: none;
    }
    .rf-input::placeholder {
        color: #4a5568;
    }
    .rf-input:focus {
        border-color: #2947f0;
        background: rgba(255, 255, 255, 0.08);
        box-shadow: 0 0 0 3px rgba(41, 71, 240, 0.1);
    }
    .rf-input option {
        background: #f7f7f8;
        color: #0f172a;
    }

    .details-textarea {
        background: #ffffff;
        border: 1px solid rgba(41, 71, 240, 0.15);
    }

    .details-textarea:focus {
        border-color: #2947f0;
        box-shadow: 0 0 0 3px rgba(41, 71, 240, 0.1);
    }

    .rf-label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        color: #000000;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    /* ── SELECT ARROW ── */
    .rf-select-wrap {
        position: relative;
    }
    .rf-select-wrap::after {
        content: "";
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        width: 0;
        height: 0;
        border-left: 5px solid transparent;
        border-right: 5px solid transparent;
        border-top: 5px solid #8892a4;
        pointer-events: none;
    }

    /* ── REPORTER INFO BOX ── */
    .reporter-box {
        background: rgba(41, 71, 240, 0.05);
        border: 1px solid rgba(41, 71, 240, 0.15);
        border-radius: 16px;
        padding: 16px 18px;
    }

    /* ── PRIORITY RADIO CARD ── */
    .priority-card {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px 16px;
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.07);
        background: rgba(255, 255, 255, 0.03);
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .priority-card:hover {
        border-color: rgba(255, 255, 255, 0.15);
        background: rgba(255, 255, 255, 0.06);
    }
    .priority-card.p-non-urgent {
        border-color: rgba(52, 211, 153, 0.35);
        background: rgba(52, 211, 153, 0.07);
    }
    .priority-card.p-urgent {
        border-color: rgba(239, 68, 68, 0.35);
        background: rgba(239, 68, 68, 0.07);
    }

    /* ── ISSUE TAG ── */
    .issue-btn {
        background: #fef3c7;
        border: 1.5px solid rgba(240, 180, 41, 0.45);
        color: #b45309;
        padding: 8px 16px;
        border-radius: 999px;
        font-size: 12.5px;
        font-weight: 600;
        white-space: nowrap;
        flex-shrink: 0;
        transition: all 0.2s ease;
        font-family: "Inter", sans-serif;
        cursor: pointer;
    }

    .issue-btn:hover {
        background: #fde68a;
        border: 1.5px solid #f0b429;
        color: #92400e;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(240, 180, 41, 0.2);
    }

    .issue-btn.active {
        background: #f0b429;
        border: 1.5px solid #e8920a;
        color: #080c18;
        box-shadow: 0 4px 12px rgba(240, 180, 41, 0.3);
    }

    .issue-action-btn {
        width: 36px;
        height: 36px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    /* LEFT & RIGHT BUTTONS */
    .issue-action-btn.yellow {
        background: #fef3c7;
        border: 1.5px solid rgba(240, 180, 41, 0.45);
        color: #b45309;
    }

    .issue-action-btn.yellow:hover {
        background: #f0b429;
        border-color: #f0b429;
        color: #080c18;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(240, 180, 41, 0.25);
    }

    /* CLEAR BUTTON */
    .issue-action-btn.red {
        background: #fef2f2;
        border: 1.5px solid rgba(239, 68, 68, 0.18);
        color: #ef4444;
    }

    .issue-action-btn.red:hover {
        background: #ef4444;
        border-color: #ef4444;
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
    }

    /* SELECTED ISSUE CONTAINER */
    .selected-issue-container {
        background: #fff8e6;
        border: 1px solid #f0b429;
        border-radius: 14px;
        padding: 12px;
    }

    /* SELECTED ISSUE BADGE */
    .selected-issue-pill {
        display: inline-flex;
        align-items: center;

        background: #f0b429;
        color: #080c18;

        padding: 8px 14px;

        border-radius: 999px;

        font-size: 13px;
        font-weight: 600;
    }

    /* ── UPLOAD ZONE ── */
    .upload-zone {
        border: 1.5px dashed rgba(255, 255, 255, 0.12);
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        background: rgba(255, 255, 255, 0.02);
        cursor: pointer;
        transition: all 0.2s ease;
        display: block;
    }
    .upload-zone:hover {
        border-color: rgba(240, 180, 41, 0.4);
        background: rgba(240, 180, 41, 0.04);
    }
    .upload-zone.uploaded {
        border-color: rgba(52, 211, 153, 0.4);
        background: rgba(52, 211, 153, 0.05);
    }

    #issueCarousel {
        margin-bottom: 16px;
    }

    /* ── SCROLL HIDE ── */
    #issueCarousel::-webkit-scrollbar {
        display: none;
    }
    #issueCarousel {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    /* ── MOBILE MODAL ALIGN ── */
    @media (max-width: 640px) {
        #reportModal {
            align-items: flex-start;
        }
    }
</style>

<style>
    #issueCarousel {
        cursor: grab;

        user-select: none;

        -webkit-user-select: none;

        overflow-x: auto;

        scroll-behavior: auto;
    }

    #issueCarousel:active {
        cursor: grabbing;
    }

    .issue-btn {
        pointer-events: auto;
    }

    .issue-btn {
        transition:
            background 0.25s ease,
            border-color 0.25s ease,
            transform 0.15s ease;
    }

    .issue-btn:hover {
        transform: translateY(-2px);
    }

    .issue-btn:active {
        transform: scale(0.96);
    }
</style>

<form
    method="POST"
    action="/store-report"
    enctype="multipart/form-data"
    id="reportForm"
>
    @csrf

    <div class="mx-auto w-full max-w-6xl px-2">
        <div
            class="overflow-hidden rounded-3xl"
            style="
                background: #0d1120;
                border: 1px solid rgba(255, 255, 255, 0.09);
                box-shadow: 0 40px 100px rgba(0, 0, 0, 0.7);
                font-family: &quot;Inter&quot;, sans-serif;
            "
        >
            <div class="grid grid-cols-1 lg:min-h-[580px] lg:grid-cols-12">
                {{-- ══════════════════ LEFT PANEL ══════════════════ --}}
                <div
                    class="flex flex-col justify-center bg-white p-6 sm:p-7 lg:col-span-8 lg:p-9"
                    style="border-right: 1px solid rgba(255, 255, 255, 0.07)"
                >
                    {{-- HEADER --}}
                    <div class="mb-7 flex items-center gap-4">
                        <div
                            class="w-13 h-13 flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl"
                            style="
                                background: rgba(54, 41, 240, 0.12);
                                border: 1px solid rgba(41, 61, 240, 0.2);
                            "
                        >
                            <i
                                data-lucide="clipboard-pen"
                                class="h-6 w-6"
                                style="color: #2947f0"
                            ></i>
                        </div>

                        <div class="flex-1">
                            <h2
                                style="
                                    font-family: &quot;Outfit&quot;, sans-serif;
                                    font-weight: 800;
                                    font-size: 1.5rem;
                                    color: #0f172a;
                                    line-height: 1.1;
                                "
                            >
                                Maintenance Report
                            </h2>
                            <p
                                style="
                                    color: #656568;
                                    font-size: 0.8rem;
                                    margin-top: 3px;
                                "
                            >Report room, facility, or equipment concerns.</p>
                        </div>

                        {{-- CLOSE (mobile) --}}
                        <button
                            type="button"
                            onclick="closeReportModal()"
                            class="flex h-8 w-8 items-center justify-center rounded-lg lg:hidden"
                            style="
                                background: rgba(255, 255, 255, 0.06);
                                border: 1px solid rgba(255, 255, 255, 0.1);
                                color: #2947f0;
                            "
                        >
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>

                    {{-- EMPLOYEE ID --}}
                    <div class="mb-5">
                        <label class="rf-label">Employee ID</label>

                        <div style="position: relative">
                            <span
                                style="
                                    position: absolute;
                                    left: 13px;
                                    top: 50%;
                                    transform: translateY(-50%);
                                    color: #2947f0;
                                    pointer-events: none;
                                "
                            >
                                <i data-lucide="scan-face" class="h-5 w-5"></i>
                            </span>
                            <input
                                type="text"
                                id="employeeIdInput"
                                name="report_reporter_employee_id"
                                value="{{ old('report_reporter_employee_id') }}"
                                placeholder="EMPLOYEE ID"
                                class="rf-input details-textarea"
                                style="padding-left: 42px; height: 48px"
                                required
                            />
                        </div>

                        {{-- EMPLOYEE ERROR --}}
                        <p
                            id="employeeError"
                            style="
                                color: #ef4444;
                                font-size: 0.82rem;
                                font-weight: 600;
                                margin-top: 8px;
                                display: none;
                            "
                        >Employee ID not recognized.</p>
                    </div>

                    {{-- REPORTER INFO BOX --}}
                    <div id="reporterInfoBox" class="reporter-box mb-5 hidden">
                        <p
                            style="
                                font-size: 11px;
                                color: #2947f0;
                                text-transform: uppercase;
                                letter-spacing: 0.08em;
                                margin-bottom: 6px;
                            "
                        >Reporter Verified</p>

                        <p
                            id="reporterName"
                            style="
                                display: flex;
                                align-items: center;
                                gap: 8px;
                                font-family: &quot;Outfit&quot;, sans-serif;
                                color: #3c3c3f;
                                font-size: 1rem;
                            "
                        ></p>
                    </div>

                    {{-- LOCATION + EQUIPMENT --}}
                    <div class="mb-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                        {{-- LOCATION --}}
                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <label class="rf-label" style="margin-bottom: 0"
                                    >Location</label
                                >
                            </div>

                            <div
                                id="roomDropdownContainer"
                                class="rf-select-wrap"
                            >
                                <select
                                    name="report_room_id"
                                    id="roomSelect"
                                    class="rf-input details-textarea"
                                    style="
                                        height: 48px;
                                        padding-right: 36px;
                                        color: #0f172a;
                                        cursor: pointer;
                                    "
                                >
                                    <option value="">Select Location</option>
                                    @foreach ($rooms as $room)
                                        <option value="{{ $room->room_id }}">
                                            {{ $room->floor_level }} - {{ $room->room_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <p id="locationError" class="mt-1 hidden text-[14px] text-red-500">Please select a location.</p>
                        </div>

                        {{-- EQUIPMENT --}}
                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <label class="rf-label" style="margin-bottom: 0"
                                    >Equipment</label
                                >
                                <button
                                    type="button"
                                    id="toggleEquipmentInput"
                                    style="
                                        font-size: 11px;
                                        font-weight: 700;
                                        color: #0037c7;
                                        background: none;
                                        border: none;
                                        cursor: pointer;
                                        transition: color 0.2s;
                                    "
                                    onmouseover="this.style.color = '#002ea8'"
                                    onmouseout="this.style.color = '#0037C7'"
                                >
                                    Equipment not listed?
                                </button>
                            </div>

                            <div
                                id="equipmentDropdownContainer"
                                class="rf-select-wrap"
                            >
                                <select
                                    name="report_equipment_id"
                                    id="equipmentSelect"
                                    class="rf-input details-textarea"
                                    style="
                                        height: 48px;
                                        padding-right: 36px;
                                        cursor: pointer;
                                    "
                                >
                                    <option value="">Select Equipment</option>
                                </select>
                            </div>

                            <div id="equipmentManualContainer" class="hidden">
                                <input
                                    type="text"
                                    name="report_equipment_manual"
                                    id="equipmentManualInput"
                                    placeholder="Enter equipment name manually..."
                                    class="rf-input details-textarea"
                                    style="height: 48px"
                                />
                            </div>

                            <p id="equipmentError" class="mt-1 hidden text-[14px] text-red-500">Please enter or select an equipment.</p>
                        </div>
                    </div>

                    {{-- SUGGESTED ISSUES --}}
                    <div>
                        <div
                            class="mb-3 mt-3 flex items-center justify-between"
                        >
                            <label class="rf-label" style="margin-bottom: 0">
                                Suggested Issues
                                <span id="issueCount">(0)</span>
                            </label>

                            <div
                                id="issueControls"
                                class="flex hidden items-center gap-2"
                            >
                                <button
                                    type="button"
                                    id="scrollLeftBtn"
                                    class="issue-action-btn yellow"
                                >
                                    <i
                                        data-lucide="chevron-left"
                                        class="h-4 w-4"
                                    >
                                    </i>
                                </button>

                                <button
                                    type="button"
                                    id="scrollRightBtn"
                                    class="issue-action-btn yellow"
                                >
                                    <i
                                        data-lucide="chevron-right"
                                        class="h-4 w-4"
                                    >
                                    </i>
                                </button>

                                <button
                                    type="button"
                                    onclick="clearSuggestedIssue()"
                                    class="issue-action-btn red"
                                >
                                    <i data-lucide="x" class="h-4 w-4"> </i>
                                </button>
                            </div>
                        </div>

                        <div
                            id="issueCarousel"
                            class="flex gap-2 overflow-x-auto scroll-smooth"
                        >
                            <div
                                id="issuePlaceholder"
                                style="
                                    width: 100%;
                                    height: 48px;
                                    border: 1.5px dashed rgba(98, 98, 100, 0.61);
                                    border-radius: 999px;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    color: #6b6c6e;
                                    font-size: 12px;
                                    font-weight: 600;
                                "
                            >
                                Select a location and choose or type equipment
                                to see suggestions
                            </div>
                        </div>

                        <p
                            id="issueError"
                            class="hidden"
                            style="
                                color: red;
                                font-size: 14px;
                                margin-top: -12px;
                                margin-bottom: 22px;
                            "
                        >Please select a suggested issue.</p>

                        <input
                            type="hidden"
                            id="suggestedIssueInput"
                            name="report_suggested_issue"
                        />
                    </div>

                    <!-- PROBLEM DESCRIPTION WRAPPER -->
                    <div style="margin-top: 4px">
                        <label
                            class="rf-label"
                            style="margin-top: 12px; margin-bottom: 8px"
                        >
                            Additional Details
                        </label>

                        <div style="position: relative">
                            <textarea
                                id="problemDescription"
                                name="report_problem_description"
                                rows="4"
                                placeholder="(Optional) - Provide specific details or context about the issue here..."
                                class="rf-input details-textarea"
                                style="
                                    resize: vertical;
                                    min-height: 140px;
                                    padding: 16px 40px 16px 16px;
                                    line-height: 1.6;
                                "
                                >{{
                                    old(
                                        "report_problem_description",
                                    )
                                }}</textarea
                            >

                            <div
                                id="clearDescriptionWrapper"
                                class="hidden"
                                style="
                                    position: absolute;
                                    top: 10px;
                                    right: 8px;
                                    z-index: 20;
                                "
                            >
                                <button
                                    type="button"
                                    id="clearDescriptionBtn"
                                    onclick="clearProblemDescription()"
                                    style="
                                        width: 24px;
                                        height: 24px;
                                        border-radius: 999px;
                                        border: none;
                                        background: rgba(239, 68, 68, 0.12);
                                        color: #ef4444;
                                        cursor: pointer;
                                        font-size: 12px;
                                        font-weight: 700;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                    "
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- This is the single, correct closing tag for the LEFT PANEL grid column --}}

                {{-- ══════════════════ RIGHT PANEL ══════════════════ --}}
                <div
                    class="flex flex-col gap-6 p-6 sm:p-7 lg:col-span-4"
                    style="
                        background: rgba(255, 255, 255, 0.015);
                        border-top: 1px solid rgba(255, 255, 255, 0.07);
                    "
                    class="lg:border-top-0"
                >
                    {{-- CLOSE (desktop) --}}
                    <div class="hidden justify-end lg:flex">
                        <button
                            type="button"
                            onclick="closeReportModal()"
                            class="flex h-8 w-8 items-center justify-center rounded-lg transition"
                            style="
                                background: rgba(255, 255, 255, 0.06);
                                border: 1px solid rgba(255, 255, 255, 0.1);
                                color: #a7aab9;
                            "
                            onmouseover="
                                this.style.color = '#f0f2f8';
                                this.style.background = 'rgba(255,255,255,0.1)';
                            "
                            onmouseout="
                                this.style.color = '#8892a4';
                                this.style.background =
                                    'rgba(255,255,255,0.06)';
                            "
                        >
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>

                    {{-- PRIORITY LEVEL --}}
                    <div>
                        <label
                            class="rf-label"
                            style="margin-bottom: 14px; color: white"
                            >Priority Level</label
                        >

                        {{-- NON-URGENT --}}
                        <label
                            class="priority-card p-non-urgent mb-3"
                            id="card-non-urgent"
                        >
                            <input
                                type="radio"
                                name="report_urgency_level"
                                value="Non-Urgent"
                                checked
                                class="mt-1 flex-shrink-0"
                                style="accent-color: #34d399"
                                onchange="updatePriorityCards()"
                            />
                            <div>
                                <div
                                    style="
                                        font-family:
                                            &quot;Outfit&quot;, sans-serif;
                                        font-weight: 700;
                                        font-size: 0.9rem;
                                        color: #34d399;
                                    "
                                >
                                    Non-Urgent
                                </div>
                                <p
                                    style="
                                        font-size: 0.73rem;
                                        color: #b6b6b6;
                                        margin-top: 3px;
                                        line-height: 1.4;
                                    "
                                >Minor issue or repair concern</p>
                            </div>
                        </label>

                        {{-- URGENT --}}
                        <label class="priority-card" id="card-urgent">
                            <input
                                type="radio"
                                name="report_urgency_level"
                                value="Urgent"
                                class="mt-1 flex-shrink-0"
                                style="accent-color: #ef4444"
                                onchange="updatePriorityCards()"
                            />
                            <div>
                                <div
                                    style="
                                        font-family:
                                            &quot;Outfit&quot;, sans-serif;
                                        font-weight: 700;
                                        font-size: 0.9rem;
                                        color: #f0f2f8;
                                    "
                                >
                                    Urgent
                                </div>
                                <p
                                    style="
                                        font-size: 0.73rem;
                                        color: #b6b6b6;
                                        margin-top: 3px;
                                        line-height: 1.4;
                                    "
                                >Immediate maintenance required</p>
                            </div>
                        </label>
                    </div>

                    {{-- UPLOAD PROOF --}}
                    <div>
                        <label
                            class="rf-label"
                            style="margin-bottom: 10px; color: white"
                            >Upload Proof Image</label
                        >

                        <label
                            for="proofImageInput"
                            class="upload-zone"
                            id="uploadZone"
                            style="position: relative"
                        >
                            <i
                                data-lucide="image-plus"
                                class="mx-auto mb-2 h-7 w-7"
                                style="color: #2947f0"
                            ></i>
                            <div
                                id="uploadLabel"
                                style="
                                    color: #a7aab9;
                                    font-size: 0.8rem;
                                    font-weight: 600;
                                "
                            >
                                Click to upload photo <br />
                                (Optional)
                            </div>
                            <div
                                id="removeFileBtn"
                                class="hidden"
                                style="
                                    position: absolute;
                                    top: 10px;
                                    right: 10px;
                                    z-index: 20;
                                "
                            >
                                <button
                                    type="button"
                                    onclick="
                                        event.preventDefault();
                                        removeSelectedFile();
                                    "
                                    class="flex items-center justify-center transition"
                                    style="
                                        width: 30px;
                                        height: 30px;
                                        border-radius: 999px;
                                        background: rgba(239, 68, 68, 0.15);
                                        border: 1px solid rgba(239, 68, 68, 0.3);
                                        color: #ef4444;
                                        font-size: 14px;
                                        font-weight: 700;
                                    "
                                    title="Remove file"
                                >
                                    <i data-lucide="x" class="h-4 w-4"></i>
                                </button>
                            </div>
                            <div
                                style="
                                    color: #777777;
                                    font-size: 0.7rem;
                                    margin-top: 3px;
                                "
                            >
                                PNG, JPG, JPEG, WEBP up to 10MB
                            </div>
                            <input
                                type="file"
                                id="proofImageInput"
                                name="report_uploaded_image"
                                accept=".jpg,.jpeg,.png,.webp"
                                class="hidden"
                                onchange="handleFileSelect(this)"
                            />
                        </label>
                    </div>

                    {{-- ACTIONS --}}
                    <div class="mt-auto flex flex-col gap-2 pt-2">
                        <button
                            type="submit"
                            class="flex w-full items-center justify-center gap-2 rounded-2xl py-4 font-bold transition"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    #f0b429,
                                    #e8920a
                                );
                                color: #080c18;
                                font-family: &quot;Outfit&quot;, sans-serif;
                                font-size: 0.95rem;
                            "
                            onmouseover="
                                this.style.background =
                                    'linear-gradient(135deg, #e8920a, #c67a05)'
                            "
                            onmouseout="
                                this.style.background =
                                    'linear-gradient(135deg, #f0b429, #e8920a)'
                            "
                        >
                            <i data-lucide="send" class="h-4 w-4"></i>
                            Submit Report
                        </button>

                        <button
                            type="button"
                            onclick="closeReportModal()"
                            class="w-full rounded-2xl py-4 font-semibold transition"
                            style="
                                background: rgba(255, 255, 255, 0.05);
                                border: 1px solid rgba(255, 255, 255, 0.09);
                                color: #a7aab9;
                                font-size: 0.9rem;
                            "
                            onmouseover="
                                this.style.background =
                                    'rgba(255,255,255,0.09)';
                                this.style.color = '#f0f2f8';
                            "
                            onmouseout="
                                this.style.background =
                                    'rgba(255,255,255,0.05)';
                                this.style.color = '#8892a4';
                            "
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    /* ── RE-RENDER ICONS ── */
    let reporterVerified = false;

    let selectedSuggestedIssue = "";

    if (typeof lucide !== "undefined") lucide.createIcons();

    /* ─────────────────────────────────────────────────────────
   PRIORITY CARD VISUAL STATE
───────────────────────────────────────────────────────── */
    function updatePriorityCards() {
        const radios = document.querySelectorAll(
            'input[name="report_urgency_level"]',
        );
        const cardNon = document.getElementById("card-non-urgent");
        const cardUrg = document.getElementById("card-urgent");

        radios.forEach((r) => {
            if (r.value === "Non-Urgent") {
                if (r.checked) {
                    cardNon.classList.add("p-non-urgent");
                    cardNon.querySelector("div div:first-child").style.color =
                        "#34d399";
                } else {
                    cardNon.classList.remove("p-non-urgent");
                    cardNon.querySelector("div div:first-child").style.color =
                        "#f0f2f8";
                }
            }
            if (r.value === "Urgent") {
                if (r.checked) {
                    cardUrg.classList.add("p-urgent");
                    cardUrg.querySelector("div div:first-child").style.color =
                        "#ef4444"; // Red text when checked
                } else {
                    cardUrg.classList.remove("p-urgent");
                    cardUrg.querySelector("div div:first-child").style.color =
                        "#f0f2f8"; // Reverts when unchecked
                }
            }
        });
    }

    updatePriorityCards();

    /* ─────────────────────────────────────────────────────────
   SUGGESTED ISSUE AUTO-FILL
───────────────────────────────────────────────────────── */
    const descriptionTextarea = document.querySelector(
        'textarea[name="report_problem_description"]',
    );
    const clearDescriptionBtn = document.getElementById("clearDescriptionBtn");
    const clearDescriptionWrapper = document.getElementById(
        "clearDescriptionWrapper",
    );

    descriptionTextarea.addEventListener("input", updateClearButtonVisibility);

    /*
|--------------------------------------------------------------------------
| REMOVE ACTIVE IF DESCRIPTION CLEARED
|--------------------------------------------------------------------------


descriptionTextarea.addEventListener('input', function () {

    if(this.value.trim() === ''){

        document.querySelectorAll('.issue-btn')
            .forEach(btn =>
                btn.classList.remove('active')
            );

    }

});*/

    /*descriptionTextarea.addEventListener(
    'input',
    function(){

        const text =
            this.value.trim();

        let matched = false;

        document
            .querySelectorAll('.issue-btn')
            .forEach(btn => {

                if(
                    btn.innerText.trim() === text
                ){

                    btn.classList.add(
                        'active'
                    );

                    matched = true;

                }
                else{

                    btn.classList.remove(
                        'active'
                    );

                }

            });

        if(!matched){

            document
                .querySelectorAll('.issue-btn')
                .forEach(btn =>
                    btn.classList.remove(
                        'active'
                    )
                );

        }

    }
);*/

    /*
descriptionTextarea.addEventListener(
    'input',
    function(){

        if(
            this.value.trim() !== ''
        ){

            document
                .querySelectorAll('.issue-btn')
                .forEach(btn =>
                    btn.classList.remove('active')
                );

        }

    }
);*/

    /* ─────────────────────────────────────────────────────────
   ISSUE CAROUSEL SCROLL
───────────────────────────────────────────────────────── */
    const issueCarousel = document.getElementById("issueCarousel");

    document.getElementById("scrollLeftBtn").addEventListener("click", () => {
        issueCarousel.scrollBy({ left: -280, behavior: "smooth" });
    });
    document.getElementById("scrollRightBtn").addEventListener("click", () => {
        issueCarousel.scrollBy({ left: 280, behavior: "smooth" });
    });

    /*
|--------------------------------------------------------------------------
| PREMIUM MOMENTUM DRAG CAROUSEL
|--------------------------------------------------------------------------
*/

    let isDragging = false;
    let startX = 0;
    let startScrollLeft = 0;
    let velocity = 0;
    let lastX = 0;
    let animationFrame;

    issueCarousel.style.cursor = "grab";

    issueCarousel.addEventListener("pointerdown", (e) => {
        isDragging = true;

        issueCarousel.style.cursor = "grabbing";

        cancelAnimationFrame(animationFrame);

        startX = e.clientX;

        lastX = e.clientX;

        startScrollLeft = issueCarousel.scrollLeft;

        velocity = 0;
    });

    window.addEventListener("pointermove", (e) => {
        if (!isDragging) return;

        e.preventDefault();

        const dx = e.clientX - startX;

        issueCarousel.scrollLeft = startScrollLeft - dx;

        velocity = e.clientX - lastX;

        lastX = e.clientX;
    });

    window.addEventListener("pointerup", () => {
        if (!isDragging) return;

        isDragging = false;

        issueCarousel.style.cursor = "grab";

        momentumScroll();
    });

    function momentumScroll() {
        issueCarousel.scrollLeft -= velocity * 4;

        velocity *= 0.95;

        if (Math.abs(velocity) > 0.5) {
            animationFrame = requestAnimationFrame(momentumScroll);
        }
    }

    /*
|--------------------------------------------------------------------------
| TOUCH SWIPE SUPPORT
|--------------------------------------------------------------------------
*/

    let touchStartX = 0;
    let touchScrollLeft = 0;

    issueCarousel.addEventListener("touchstart", (e) => {
        touchStartX = e.touches[0].pageX;

        touchScrollLeft = issueCarousel.scrollLeft;
    });

    issueCarousel.addEventListener("touchmove", (e) => {
        const touchX = e.touches[0].pageX;

        const walk = touchX - touchStartX;

        issueCarousel.scrollLeft = touchScrollLeft - walk;
    });

    /* ─────────────────────────────────────────────────────────
   FILE UPLOAD PREVIEW
───────────────────────────────────────────────────────── */
    function handleFileSelect(input) {
        const file = input.files[0];

        const label = document.getElementById("uploadLabel");

        const zone = document.getElementById("uploadZone");

        if (!file) return;

        const allowedTypes = ["image/jpeg", "image/png", "image/webp"];

        const maxSize = 10 * 1024 * 1024;

        /*
    |--------------------------------------------------------------------------
    | INVALID FILE TYPE
    |--------------------------------------------------------------------------
    */

        if (!allowedTypes.includes(file.type)) {
            alert(
                "Unsupported file format. Please upload a PNG, JPG, JPEG, or WEBP image.",
            );

            input.value = "";

            label.innerText = "Click to upload photo";

            label.style.color = "#8892a4";

            zone.classList.remove("uploaded");

            return;
        }

        /*
    |--------------------------------------------------------------------------
    | FILE TOO LARGE
    |--------------------------------------------------------------------------
    */

        if (file.size > maxSize) {
            alert(
                "File size exceeds the 10MB limit. Please upload a smaller image.",
            );

            input.value = "";

            label.innerText = "Click to upload photo";

            label.style.color = "#8892a4";

            zone.classList.remove("uploaded");

            return;
        }

        /*
    |--------------------------------------------------------------------------
    | VALID FILE
    |--------------------------------------------------------------------------
    */

        label.textContent = "🔵✓ " + file.name;

        label.style.color = "#34d399";

        zone.classList.add("uploaded");

        document.getElementById("removeFileBtn").classList.remove("hidden");

        const icon = zone.querySelector("i");

        if (icon) {
            icon.style.color = "#34d399";
        }
    }

    function removeSelectedFile() {
        const input = document.getElementById("proofImageInput");

        const label = document.getElementById("uploadLabel");

        const zone = document.getElementById("uploadZone");

        const removeBtn = document.getElementById("removeFileBtn");

        input.value = "";

        label.textContent = "Click to upload photo";

        label.style.color = "#8892a4";

        zone.classList.remove("uploaded");

        removeBtn.classList.add("hidden");

        const icon = zone.querySelector("i");

        if (icon) {
            icon.style.color = "#8892a4";
        }
    }

    /* ─────────────────────────────────────────────────────────
   ROOM INPUT TOGGLE
───────────────────────────────────────────────────────── 
const toggleRoomBtn         = document.getElementById('toggleRoomInput');
const roomDropdown          = document.getElementById('roomDropdownContainer');
const roomManual            = document.getElementById('roomManualContainer');
let   roomManualMode        = false;

toggleRoomBtn.addEventListener('click', function () {
    roomManualMode = !roomManualMode;
    if (roomManualMode) {
        roomDropdown.classList.add('hidden');
        roomManual.classList.remove('hidden');
        this.innerText = 'Use location list instead';
    } else {
        roomDropdown.classList.remove('hidden');
        roomManual.classList.add('hidden');
        this.innerText = 'Other Location?';
    }
}); */

    /* ─────────────────────────────────────────────────────────
   EQUIPMENT INPUT TOGGLE
───────────────────────────────────────────────────────── */

    const toggleEquipmentBtn = document.getElementById("toggleEquipmentInput");

    const equipmentDropdown = document.getElementById(
        "equipmentDropdownContainer",
    );

    const equipmentManual = document.getElementById("equipmentManualContainer");

    const equipmentSelect = document.getElementById("equipmentSelect");

    const equipmentManualInput = document.getElementById(
        "equipmentManualInput",
    );

    let equipmentManualMode = false;

    let lastSelectedEquipment = "";

    toggleEquipmentBtn.addEventListener("click", function () {
        document.getElementById("equipmentError").classList.add("hidden");

        equipmentSelect.style.borderColor = "";

        equipmentManualInput.style.borderColor = "";

        equipmentManualMode = !equipmentManualMode;

        /*
        |--------------------------------------------------------------------------
        | SWITCH TO MANUAL EQUIPMENT
        |--------------------------------------------------------------------------
        */

        if (equipmentManualMode) {
            if (equipmentSelect.value) {
                lastSelectedEquipment = equipmentSelect.value;
            }

            equipmentSelect.value = "";

            equipmentDropdown.classList.add("hidden");

            equipmentManual.classList.remove("hidden");

            this.innerText = "Back to equipment list";

            document
                .querySelectorAll(".issue-btn")
                .forEach((btn) => btn.classList.remove("active"));

            loadGenericSuggestions();
        } else {
            /*
        |--------------------------------------------------------------------------
        | SWITCH BACK TO EQUIPMENT LIST
        |--------------------------------------------------------------------------
        */
            equipmentDropdown.classList.remove("hidden");

            equipmentManual.classList.add("hidden");

            equipmentManualInput.value = "";

            this.innerText = "Equipment not listed?";

            document
                .querySelectorAll(".issue-btn")
                .forEach((btn) => btn.classList.remove("active"));

            if (lastSelectedEquipment) {
                equipmentSelect.value = lastSelectedEquipment;

                fetch(`/get-suggestions/${lastSelectedEquipment}`)
                    .then((response) => response.json())
                    .then((data) => {
                        issueCarousel.innerHTML = "";

                        data.forEach((issue) => {
                            issueCarousel.innerHTML += `
                            <button
                                type="button"
                                class="issue-btn">
                                ${issue}
                            </button>
                        `;
                        });

                        bindIssueButtons();

                        updateIssueCount();
                    });
            } else {
                issueCarousel.innerHTML = `
                    <div id="issuePlaceholder"
                        style="
                            width:100%;
                            height:48px;
                            border:1.5px dashed rgba(98, 98, 100, 0.61);
                            border-radius:999px;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            color:#6b6c6e;
                            font-size:12px;
                            font-weight:600;
                        ">
                        Select a location and choose or type equipment to see suggestions
                    </div>
                `;

                updateIssueCount();

                toggleIssueControls();
            }
        }
    });

    function showIssuePlaceholder() {
        issueCarousel.innerHTML = `
        <div id="issuePlaceholder"
            style="
                width:100%;
                height:48px;
                border:1.5px dashed rgba(98, 98, 100, 0.61);
                border-radius:999px;
                display:flex;
                align-items:center;
                justify-content:center;
                color:#6b6c6e;
                font-size:12px;
                font-weight:600;
            ">
            Select a location and choose or type equipment to see suggestions
        </div>
    `;

        updateIssueCount();

        toggleIssueControls();
    }

    function toggleIssueControls() {
        const controls = document.getElementById("issueControls");

        const issueCount = document.querySelectorAll(".issue-btn").length;

        if (issueCount > 0) {
            controls.classList.remove("hidden");
        } else {
            controls.classList.add("hidden");
        }
    }

    /*
|--------------------------------------------------------------------------
| GENERIC SUGGESTIONS
|--------------------------------------------------------------------------
*/

    function loadGenericSuggestions() {
        issueCarousel.innerHTML = `

        <button type="button" class="issue-btn">
            Not Functioning
        </button>

        <button type="button" class="issue-btn">
            Physical Damage
        </button>

        <button type="button" class="issue-btn">
            Missing Parts
        </button>

        <button type="button" class="issue-btn">
            Needs Inspection
        </button>

        <button type="button" class="issue-btn">
            Needs Replacement
        </button>

        <button type="button" class="issue-btn">
            Cannot Operate
        </button>

        <button type="button" class="issue-btn">
            Electrical Issue
        </button>

        <!-- Changed from "Issues" to "Issue" -->
        <button type="button" class="issue-btn">
            Connectivity Issue 
        </button>

        <button type="button" class="issue-btn">
            Power Failure
        </button>

        <button type="button" class="issue-btn">
            Malfunctioning Component
        </button>

    `;

        updateIssueCount();

        toggleIssueControls();

        bindIssueButtons();
    }

    // AUTO SUGGESTIOn

    function bindIssueButtons() {
        document.querySelectorAll(".issue-btn").forEach((btn) => {
            btn.addEventListener("click", function () {
                const newIssue = this.innerText.trim();

                document
                    .querySelectorAll(".issue-btn")
                    .forEach((b) => b.classList.remove("active"));

                this.classList.add("active");

                document.getElementById("issueError").classList.add("hidden");

                selectedSuggestedIssue = newIssue;

                document.getElementById("suggestedIssueInput").value = newIssue;
            });
        });
    }

    function updateIssuePlaceholder() {
        const placeholder = document.getElementById("issuePlaceholder");

        const issueButtons = document.querySelectorAll(".issue-btn");

        if (issueButtons.length > 0) {
            placeholder.classList.add("hidden");
        } else {
            placeholder.classList.remove("hidden");
        }
    }

    function updateClearButtonVisibility() {
        if (descriptionTextarea.value.trim() !== "") {
            clearDescriptionWrapper.classList.remove("hidden");
        } else {
            clearDescriptionWrapper.classList.add("hidden");
        }
    }

    /*
|--------------------------------------------------------------------------
| CLEAR PROBLEM DESCRIPTION
|--------------------------------------------------------------------------
*/

    function clearProblemDescription() {
        descriptionTextarea.value = "";

        clearDescriptionWrapper.classList.add("hidden");
    }

    //SUGGESTED ISSUE
    function clearSuggestedIssue() {
        selectedSuggestedIssue = "";

        document.getElementById("suggestedIssueInput").value = "";

        document
            .querySelectorAll(".issue-btn")
            .forEach((btn) => btn.classList.remove("active"));
    }

    /* ─────────────────────────────────────────────────────────
   REPORT SUBMISSION INTELLIGENCE LAYER
───────────────────────────────────────────────────────── */
    document.addEventListener("DOMContentLoaded", function () {
        const employeeInput = document.getElementById("employeeIdInput");

        const reporterBox = document.getElementById("reporterInfoBox");

        const employeeError = document.getElementById("employeeError");

        const roomSelect = document.getElementById("roomSelect");

        const equipSelect = document.getElementById("equipmentSelect");

        const equipmentManualInput = document.getElementById(
            "equipmentManualInput",
        );

        /*
    |--------------------------------------------------------------------------
    | CLEAR EMPLOYEE ERROR WHILE TYPING
    |--------------------------------------------------------------------------
    */
        employeeInput.addEventListener("input", function () {
            employeeError.style.display = "none";

            this.style.borderColor = "";
        });

        /*
    |--------------------------------------------------------------------------
    | CLEAR LOCATION ERROR
    |--------------------------------------------------------------------------
    */
        roomSelect.addEventListener("change", function () {
            this.style.borderColor = "";

            document.getElementById("locationError").classList.add("hidden");
        });

        /*
    |--------------------------------------------------------------------------
    | CLEAR EQUIPMENT ERROR
    |--------------------------------------------------------------------------
    */
        equipSelect.addEventListener("change", function () {
            this.style.borderColor = "";

            document.getElementById("equipmentError").classList.add("hidden");
        });

        /*
    |--------------------------------------------------------------------------
    | CLEAR MANUAL EQUIPMENT ERROR
    |--------------------------------------------------------------------------
    */
        if (equipmentManualInput) {
            equipmentManualInput.addEventListener("input", function () {
                this.style.borderColor = "";

                document
                    .getElementById("equipmentError")
                    .classList.add("hidden");
            });
        }

        /*
    |--------------------------------------------------------------------------
    | CLEAR SUGGESTED ISSUE ERROR
    |--------------------------------------------------------------------------
    */
        document.addEventListener("click", function (e) {
            if (e.target.classList.contains("issue-btn")) {
                document.getElementById("issueError").classList.add("hidden");
            }
        });

        /* EMPLOYEE LIVE LOOKUP */
        /* EMPLOYEE LIVE LOOKUP */
        employeeInput.addEventListener("input", function () {
            employeeError.style.display = "none";

            const id = this.value.trim();

            /*
        |--------------------------------------------------------------------------
        | RESET IF TOO SHORT
        |--------------------------------------------------------------------------
        */
            if (id.length < 8) {
                reporterVerified = false;

                reporterBox.classList.add("hidden");

                employeeError.style.display = "none";

                return;
            }

            /*
        |--------------------------------------------------------------------------
        | LOOKUP REPORTER
        |--------------------------------------------------------------------------
        */
            fetch(`/get-reporter/${id}`)
                .then((response) => response.json())

                .then((data) => {
                    //console.log(data);

                    /*
                |--------------------------------------------------------------------------
                | REPORTER FOUND
                |--------------------------------------------------------------------------
                */
                    if (data && data.reporter_full_name) {
                        reporterVerified = true;

                        reporterBox.classList.remove("hidden");

                        document.getElementById("reporterName").innerHTML = `
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="#1877F2"
                            flex-shrink="0">

                            <circle
                                cx="12"
                                cy="12"
                                r="12">
                            </circle>

                            <path
                                d="M10.2 15.3L6.9 12l1.4-1.4 1.9 1.9 5.5-5.5 1.4 1.4z"
                                fill="white">
                            </path>

                        </svg>

                        <span>
                            ${data.reporter_full_name}
                        </span>
                    `;

                        employeeError.style.display = "none";
                    } else {
                        /*
                |--------------------------------------------------------------------------
                | REPORTER NOT FOUND
                |--------------------------------------------------------------------------
                */
                        reporterVerified = false;

                        reporterBox.classList.add("hidden");

                        employeeError.style.display = "none";
                    }
                })

                .catch((error) => {
                    reporterVerified = false;

                    reporterBox.classList.add("hidden");

                    employeeError.style.display = "none";
                });
        });

        /* ROOM → EQUIPMENT FILTER */
        roomSelect.addEventListener("change", function () {
            document.getElementById("locationError").classList.add("hidden");

            clearSuggestedIssue();

            document.getElementById("issueError").classList.add("hidden");

            lastSelectedEquipment = "";

            const roomId = this.value;

            if (equipmentManualMode) {
                document.getElementById("equipmentManualInput").value = "";

                document
                    .querySelectorAll(".issue-btn")
                    .forEach((btn) => btn.classList.remove("active"));

                loadGenericSuggestions();
            }

            equipSelect.innerHTML =
                '<option value="">Select Equipment</option>';
            if (!roomId) {
                lastSelectedEquipment = "";

                equipSelect.value = "";

                clearSuggestedIssue();

                showIssuePlaceholder();

                return;
            }
            fetch(`/get-equipment/${roomId}`)
                .then((r) => r.json())
                .then((data) => {
                    data.forEach((e) => {
                        equipSelect.innerHTML += `<option value="${e.equipment_id}">${e.equipment_name}</option>`;
                    });
                });
        });

        /* AUTO SUGGESTION */
        equipSelect.addEventListener("change", function () {
            lastSelectedEquipment = this.value;

            const equipmentId = this.value;

            if (!equipmentId) {
                showIssuePlaceholder();

                clearSuggestedIssue();

                return;
            }

            fetch(`/get-suggestions/${equipmentId}`)
                .then((response) => response.json())
                .then((data) => {
                    if (data.length === 0) {
                        showIssuePlaceholder();
                    } else {
                        issueCarousel.innerHTML = "";

                        data.forEach((issue) => {
                            issueCarousel.innerHTML += `
                            <button
                                type="button"
                                class="issue-btn">
                                ${issue}
                            </button>
                        `;
                        });
                    }

                    updateIssueCount();

                    toggleIssueControls();

                    bindIssueButtons();
                });
        });
    });
</script>

<script>
    function updateIssueCount() {
        const count = document.querySelectorAll(".issue-btn").length;

        document.getElementById("issueCount").textContent = `(${count})`;

        toggleIssueControls();
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document
        .getElementById("reportForm")
        .addEventListener("submit", function (e) {
            const employeeInput = document.getElementById("employeeIdInput");

            const employeeError = document.getElementById("employeeError");

            const roomSelect = document.getElementById("roomSelect");

            const equipmentSelect = document.getElementById("equipmentSelect");

            const equipmentManualInput = document.getElementById(
                "equipmentManualInput",
            );

            const roomId = roomSelect.value;

            const equipmentId = equipmentSelect.value;

            const equipmentManual = equipmentManualInput.value.trim();

            const selectedIssue = document
                .getElementById("suggestedIssueInput")
                .value.trim();

            /*
        |--------------------------------------------------------------------------
        | RESET ALL ERRORS
        |--------------------------------------------------------------------------
        */
            document.getElementById("locationError").classList.add("hidden");

            document.getElementById("equipmentError").classList.add("hidden");

            document.getElementById("issueError").classList.add("hidden");

            employeeError.style.display = "none";

            /*
        |--------------------------------------------------------------------------
        | RESET BORDERS
        |--------------------------------------------------------------------------
        */
            roomSelect.style.borderColor = "";

            equipmentSelect.style.borderColor = "";

            if (equipmentManualInput) {
                equipmentManualInput.style.borderColor = "";
            }

            employeeInput.style.borderColor = "";

            /*
        |--------------------------------------------------------------------------
        | STEP 1 : LOCATION REQUIRED
        |--------------------------------------------------------------------------
        */
            if (!roomId) {
                document
                    .getElementById("locationError")
                    .classList.remove("hidden");

                roomSelect.style.borderColor = "#dc2626";

                roomSelect.focus();

                e.preventDefault();

                return;
            }

            /*
        |--------------------------------------------------------------------------
        | STEP 2 : EQUIPMENT REQUIRED
        |--------------------------------------------------------------------------
        */
            if (!equipmentManualMode && !equipmentId) {
                document
                    .getElementById("equipmentError")
                    .classList.remove("hidden");

                equipmentSelect.style.borderColor = "#dc2626";

                equipmentSelect.focus();

                e.preventDefault();

                return;
            }

            /*
        |--------------------------------------------------------------------------
        | STEP 3 : MANUAL EQUIPMENT REQUIRED
        |--------------------------------------------------------------------------
        */
            if (equipmentManualMode && equipmentManual === "") {
                document
                    .getElementById("equipmentError")
                    .classList.remove("hidden");

                equipmentManualInput.style.borderColor = "#dc2626";

                equipmentManualInput.focus();

                e.preventDefault();

                return;
            }

            /*
        |--------------------------------------------------------------------------
        | STEP 4 : SUGGESTED ISSUE REQUIRED
        |--------------------------------------------------------------------------
        */
            if (selectedIssue === "") {
                document
                    .getElementById("issueError")
                    .classList.remove("hidden");

                e.preventDefault();

                return;
            }

            /*
        |--------------------------------------------------------------------------
        | STEP 5 : EMPLOYEE ID VALIDATION
        |--------------------------------------------------------------------------
        */
            if (!reporterVerified) {
                employeeError.innerText = "Employee ID not recognized.";

                employeeError.style.display = "block";

                employeeInput.style.borderColor = "#dc2626";

                employeeInput.focus();

                e.preventDefault();

                return;
            }

            /*
        |--------------------------------------------------------------------------
        | LOADING MODAL
        |--------------------------------------------------------------------------
        */
            Swal.fire({
                title: "Submitting Report",

                html: `
                <div class="mt-2 text-sm">
                    Processing maintenance report...
                </div>
            `,

                allowOutsideClick: false,

                allowEscapeKey: false,

                showConfirmButton: false,

                background: "#0f1628",

                color: "#f0f2f8",

                backdrop: `
                rgba(0,0,0,0.65)
                blur(6px)
            `,

                didOpen: () => {
                    const popup = Swal.getPopup();

                    popup.style.border = "1px solid rgba(255,255,255,0.08)";

                    popup.style.borderRadius = "24px";

                    popup.style.boxShadow = "0 32px 80px rgba(0,0,0,.55)";

                    Swal.showLoading();
                },
            });
        });
</script>
