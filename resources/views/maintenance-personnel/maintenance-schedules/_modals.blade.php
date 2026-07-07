<div
    id="scheduleModal"
    class="fixed inset-0 z-[1300] hidden items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]"
>
    <!-- ===================================== -->
    <!-- SCHEDULE MAINTENANCE MODAL -->
    <!-- ===================================== -->
    <div
        class="flex max-h-[90vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
    >
        <!-- ===================================== -->
        <!-- MODAL HEADER -->
        <!-- ===================================== -->
        <div
            class="flex shrink-0 items-start justify-between gap-6 px-6 pb-5 pt-6"
        >
            <div class="min-w-0">
                <p
                    class="text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400"
                >
                    Preventive Maintenance
                </p>

                <h2
                    class="mt-1.5 text-lg font-semibold tracking-tight text-slate-950"
                >
                    Schedule maintenance
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Create a maintenance schedule for equipment.
                </p>
            </div>

            <!-- CLOSE BUTTON -->
            <button
                type="button"
                onclick="closeScheduleModal()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close modal"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <!-- ===================================== -->
        <!-- SCHEDULE FORM -->
        <!-- ===================================== -->
        <form
            action="/maintenance/schedules/store"
            method="POST"
            class="flex min-h-0 flex-1 flex-col"
        >
            @csrf

            <!-- ===================================== -->
            <!-- SCROLLABLE FORM CONTENT -->
            <!-- ===================================== -->
            <div
                class="min-h-0 flex-1 overflow-y-auto border-y border-slate-100 px-6 py-5"
            >
                <div class="space-y-5">

                    <!-- ===================================== -->
                    <!-- EQUIPMENT -->
                    <!-- ===================================== -->
                    <div>
                        <label
                            for="scheduleEquipment"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Equipment
                        </label>

                        <select
                            id="scheduleEquipment"
                            name="equipment_id"
                            required
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        >
                            <option value="">
                                Select equipment
                            </option>

                            @foreach ($equipment as $item)
                                <option value="{{ $item->equipment_id }}">
                                    {{ $item->equipment_name }}{{ isset($item->room_name) && $item->room_name ? ' · '.$item->room_name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- ===================================== -->
                    <!-- MAINTENANCE TITLE -->
                    <!-- ===================================== -->
                    <div>
                        <label
                            for="scheduleTitle"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Maintenance title
                        </label>

                        <input
                            id="scheduleTitle"
                            type="text"
                            name="title"
                            placeholder="e.g. Quarterly air conditioner inspection"
                            required
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        />
                    </div>

                    <!-- ===================================== -->
                    <!-- DESCRIPTION -->
                    <!-- ===================================== -->
                    <div>
                        <div
                            class="mb-2 flex items-center justify-between gap-4"
                        >
                            <label
                                for="scheduleDescription"
                                class="text-sm font-medium text-slate-700"
                            >
                                Description
                            </label>

                            <span class="text-xs text-slate-400">
                                Optional
                            </span>
                        </div>

                        <textarea
                            id="scheduleDescription"
                            name="description"
                            rows="3"
                            placeholder="Add instructions or details about this maintenance schedule"
                            class="w-full resize-none rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm leading-6 text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        ></textarea>
                    </div>

                    <!-- ===================================== -->
                    <!-- SCHEDULE SETTINGS -->
                    <!-- ===================================== -->
                    <div class="grid gap-5 sm:grid-cols-2">

                        <!-- FREQUENCY -->
                        <div>
                            <label
                                for="scheduleFrequency"
                                class="mb-2 block text-sm font-medium text-slate-700"
                            >
                                Frequency
                            </label>

                            <select
                                id="scheduleFrequency"
                                name="frequency"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                            >
                                <option value="Monthly">
                                    Monthly
                                </option>

                                <option value="Quarterly">
                                    Quarterly
                                </option>

                                <option value="Semi Annual">
                                    Semi annual
                                </option>

                                <option value="Annual">
                                    Annual
                                </option>
                            </select>
                        </div>

                        <!-- NEXT MAINTENANCE DATE -->
                        <div>
                            <label
                                for="scheduleNextDate"
                                class="mb-2 block text-sm font-medium text-slate-700"
                            >
                                Next maintenance date
                            </label>

                            <input
                                id="scheduleNextDate"
                                type="date"
                                name="next_date"
                                required
                                class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===================================== -->
            <!-- MODAL FOOTER -->
            <!-- ===================================== -->
            <div
                class="flex shrink-0 items-center justify-end gap-2 px-6 py-4"
            >
                <button
                    type="button"
                    onclick="closeScheduleModal()"
                    class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-200 active:bg-black"
                >
                    Create schedule
                </button>
            </div>
        </form>
    </div>
</div>

<div
    id="viewModal"
    class="fixed inset-0 z-[1300] hidden items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]"
>
    <!-- ===================================== -->
    <!-- SCHEDULE DETAILS MODAL -->
    <!-- ===================================== -->
    <div
        class="flex max-h-[70vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
    >
        <!-- ===================================== -->
        <!-- MODAL HEADER -->
        <!-- ===================================== -->
        <div
            class="flex shrink-0 items-start justify-between gap-6 px-6 pb-5 pt-6"
        >
            <div>
                <p
                    class="text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400"
                >
                    Maintenance Schedule
                </p>

                <h2
                    class="mt-1.5 text-lg font-semibold tracking-tight text-slate-950"
                >
                    Schedule details
                </h2>
            </div>

            <!-- CLOSE BUTTON -->
            <button
                type="button"
                onclick="closeViewModal()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close modal"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <!-- ===================================== -->
        <!-- SCHEDULE DETAILS CONTENT -->
        <!-- ===================================== -->
        <div
            class="min-h-0 flex-1 overflow-y-auto border-y border-slate-100 px-6 py-2"
        >
            <div
                id="scheduleDetails"
                class="divide-y divide-slate-100"
            ></div>
        </div>

        <!-- ===================================== -->
        <!-- MODAL FOOTER -->
        <!-- ===================================== -->
        <div
            class="flex shrink-0 items-center justify-end px-6 py-4"
        >
            <button
                type="button"
                onclick="closeViewModal()"
                class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
            >
                Close
            </button>
        </div>
    </div>
</div>

<div
    id="completeModal"
    class="fixed inset-0 z-[1300] hidden items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]"
>
    <!-- ===================================== -->
    <!-- COMPLETE MAINTENANCE MODAL -->
    <!-- ===================================== -->
    <div
        class="flex max-h-[70vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
    >
        <!-- ===================================== -->
        <!-- MODAL HEADER -->
        <!-- ===================================== -->
        <div
            class="flex shrink-0 items-start justify-between gap-6 px-6 pb-5 pt-6"
        >
            <div class="min-w-0">
                <p
                    class="text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400"
                >
                    Maintenance Schedule
                </p>

                <h2
                    class="mt-1.5 text-lg font-semibold tracking-tight text-slate-950"
                >
                    Complete maintenance
                </h2>

                <p
                    id="completeEquipmentName"
                    class="mt-1 truncate text-sm text-slate-500"
                ></p>
            </div>

            <!-- CLOSE BUTTON -->
            <button
                type="button"
                onclick="closeCompleteModal()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close modal"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <!-- ===================================== -->
        <!-- COMPLETE MAINTENANCE FORM -->
        <!-- ===================================== -->
        <form
            action="/maintenance/schedules/complete"
            method="POST"
            enctype="multipart/form-data"
            class="flex min-h-0 flex-1 flex-col"
        >
            @csrf

            <input
                type="hidden"
                id="completeScheduleId"
                name="schedule_id"
            />

            <!-- ===================================== -->
            <!-- SCROLLABLE FORM CONTENT -->
            <!-- ===================================== -->
            <div
                class="min-h-0 flex-1 overflow-y-auto border-y border-slate-100 px-6 py-5"
            >
                <div class="space-y-5">

                    <!-- ===================================== -->
                    <!-- FINDINGS -->
                    <!-- ===================================== -->
                    <div>
                        <label
                            for="completeFindings"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Findings
                        </label>

                        <textarea
                            id="completeFindings"
                            name="findings"
                            rows="3"
                            placeholder="Describe the inspection findings or identified issues"
                            required
                            class="w-full resize-none rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm leading-6 text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        ></textarea>
                    </div>

                    <!-- ===================================== -->
                    <!-- REPAIR ACTION -->
                    <!-- ===================================== -->
                    <div>
                        <label
                            for="completeRepairAction"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Repair action
                        </label>

                        <textarea
                            id="completeRepairAction"
                            name="repair_action"
                            rows="3"
                            placeholder="Describe the maintenance or repair work performed"
                            required
                            class="w-full resize-none rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm leading-6 text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        ></textarea>
                    </div>

                    <!-- ===================================== -->
                    <!-- MAINTENANCE STATUS -->
                    <!-- ===================================== -->
                    <div>
                        <label
                            for="completeMaintenanceStatus"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Maintenance status
                        </label>

                        <select
                            id="completeMaintenanceStatus"
                            name="maintenance_status"
                            required
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        >
                            <option value="Resolved">
                                Resolved
                            </option>

                            <option value="Pending">
                                Pending
                            </option>

                            <option value="Escalated">
                                Escalated
                            </option>
                        </select>
                    </div>

                    <!-- ===================================== -->
                    <!-- PROOF IMAGE -->
                    <!-- ===================================== -->
                    <div>
                        <div
                            class="mb-2 flex items-center justify-between gap-4"
                        >
                            <label
                                for="completeProofImage"
                                class="text-sm font-medium text-slate-700"
                            >
                                Proof image
                            </label>

                            <span class="text-xs text-slate-400">
                                Optional
                            </span>
                        </div>

                        <label
                            for="completeProofImage"
                            class="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-slate-300 px-4 py-4 transition hover:border-slate-400 hover:bg-slate-50"
                        >
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500"
                            >
                                <i
                                    data-lucide="image-plus"
                                    class="h-4 w-4"
                                ></i>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-slate-700">
                                    Upload proof image
                                </p>

                                <p class="mt-0.5 text-xs text-slate-400">
                                    Select an image from your device
                                </p>
                            </div>

                            <input
                                id="completeProofImage"
                                type="file"
                                name="proof_image"
                                accept="image/*"
                                class="hidden"
                            />
                        </label>
                    </div>
                </div>
            </div>

            <!-- ===================================== -->
            <!-- MODAL FOOTER -->
            <!-- ===================================== -->
            <div
                class="flex shrink-0 items-center justify-end gap-2 px-6 py-4"
            >
                <button
                    type="button"
                    onclick="closeCompleteModal()"
                    class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-200 active:bg-black"
                >
                    Complete maintenance
                </button>
            </div>
        </form>
    </div>
</div>

<div
    id="rescheduleModal"
    class="fixed inset-0 z-[1300] hidden items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]"
>
    <!-- ===================================== -->
    <!-- RESCHEDULE MAINTENANCE MODAL -->
    <!-- ===================================== -->
    <div
        class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
    >
        <!-- ===================================== -->
        <!-- MODAL HEADER -->
        <!-- ===================================== -->
        <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6">
            <div class="min-w-0">
                <p
                    class="text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400"
                >
                    Maintenance Schedule
                </p>

                <h2
                    class="mt-1.5 text-lg font-semibold tracking-tight text-slate-950"
                >
                    Reschedule maintenance
                </h2>

                <p
                    id="rescheduleEquipmentName"
                    class="mt-1 truncate text-sm text-slate-500"
                ></p>
            </div>

            <!-- CLOSE BUTTON -->
            <button
                type="button"
                onclick="closeRescheduleModal()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close modal"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <!-- ===================================== -->
        <!-- RESCHEDULE FORM -->
        <!-- ===================================== -->
        <form
            action="/maintenance/schedules/reschedule"
            method="POST"
        >
            @csrf

            <input
                type="hidden"
                id="rescheduleScheduleId"
                name="schedule_id"
            />

            <!-- ===================================== -->
            <!-- FORM CONTENT -->
            <!-- ===================================== -->
            <div class="border-y border-slate-100 px-6 py-5">
                <div class="space-y-5">

                    <!-- ===================================== -->
                    <!-- NEW MAINTENANCE DATE -->
                    <!-- ===================================== -->
                    <div>
                        <label
                            for="rescheduleNewDate"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            New maintenance date
                        </label>

                        <input
                            id="rescheduleNewDate"
                            type="date"
                            name="new_date"
                            required
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        />
                    </div>

                    <!-- ===================================== -->
                    <!-- REASON -->
                    <!-- ===================================== -->
                    <div>
                        <label
                            for="rescheduleReason"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Reason
                        </label>

                        <textarea
                            id="rescheduleReason"
                            name="reason"
                            rows="4"
                            placeholder="Explain why this maintenance schedule is being moved"
                            required
                            class="w-full resize-none rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm leading-6 text-slate-900 outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- ===================================== -->
            <!-- MODAL FOOTER -->
            <!-- ===================================== -->
            <div class="flex items-center justify-end gap-2 px-6 py-4">
                <button
                    type="button"
                    onclick="closeRescheduleModal()"
                    class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-200 active:bg-black"
                >
                    Reschedule
                </button>
            </div>
        </form>
    </div>
</div>

<div
    id="deleteModal"
    class="fixed inset-0 z-[1300] hidden items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]"
>
    <!-- ===================================== -->
    <!-- DELETE SCHEDULE MODAL -->
    <!-- ===================================== -->
    <div
        class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
    >
        <!-- ===================================== -->
        <!-- MODAL HEADER -->
        <!-- ===================================== -->
        <div class="flex items-start justify-between gap-6 px-6 pb-5 pt-6">
            <div class="min-w-0">
                <!-- DELETE ICON -->
                <div
                    class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-rose-50 text-rose-600"
                >
                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                </div>

                <h2
                    class="text-lg font-semibold tracking-tight text-slate-950"
                >
                    Delete schedule?
                </h2>

                <p class="mt-2 text-sm leading-6 text-slate-500">
                    This maintenance schedule will be permanently deleted. This
                    action cannot be undone.
                </p>
            </div>

            <!-- CLOSE BUTTON -->
            <button
                type="button"
                onclick="closeDeleteModal()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close modal"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <!-- ===================================== -->
        <!-- SCHEDULE BEING DELETED -->
        <!-- ===================================== -->
        <div class="border-y border-slate-100 px-6 py-4">
            <div
                class="flex items-center justify-between gap-6 rounded-xl border border-slate-200 px-4 py-3.5"
            >
                <span class="shrink-0 text-sm text-slate-500">
                    Schedule
                </span>

                <span
                    id="deleteScheduleTitle"
                    class="min-w-0 truncate text-right text-sm font-medium text-slate-950"
                ></span>
            </div>
        </div>

        <!-- ===================================== -->
        <!-- DELETE FORM -->
        <!-- ===================================== -->
        <form
            action="/maintenance/schedules/delete"
            method="POST"
        >
            @csrf
            @method('DELETE')

            <input
                type="hidden"
                id="deleteScheduleId"
                name="schedule_id"
            />

            <!-- ===================================== -->
            <!-- MODAL FOOTER -->
            <!-- ===================================== -->
            <div class="flex items-center justify-end gap-2 px-6 py-4">
                <button
                    type="button"
                    onclick="closeDeleteModal()"
                    class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-rose-700 focus:outline-none focus:ring-4 focus:ring-rose-100 active:bg-rose-800"
                >
                    Delete schedule
                </button>
            </div>
        </form>
    </div>
</div>
