<div
    id="scheduleModal"
    x-data="scheduleEquipmentCart(@js($scheduleEquipmentJson ?? []))"
    x-cloak
    class="fixed inset-0 z-[1300] hidden items-start justify-center overflow-y-auto bg-[#0b1220]/70 p-4"
    @keydown.escape.window="if (!$el.classList.contains('hidden')) closeScheduleModal()"
>
    <div class="my-auto flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
        <div class="flex items-start justify-between gap-4 px-6 pt-6">
            <div class="min-w-0">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900">Schedule maintenance</h2>
                <p class="mt-1 text-sm text-slate-500">Search and add multiple QR-tagged equipment in one go.</p>
            </div>
            <button
                type="button"
                onclick="closeScheduleModal()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close modal"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <form
            action="/maintenance/schedules/store"
            method="POST"
            class="flex min-h-0 flex-1 flex-col"
            @submit="prepareSubmit($event)"
        >
            @csrf
            <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                <div class="space-y-4 rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Equipment</p>
                            <p class="mt-1 text-sm text-slate-500">Type to search, then add each asset to this schedule batch.</p>
                        </div>
                        <p class="rounded-lg bg-white px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200/80" x-text="cart.length ? (cart.length + ' selected') : 'None selected'"></p>
                    </div>

                    <div class="relative" @click.outside="open = false">
                        <label class="mb-1.5 block text-sm text-slate-600">Find equipment</label>
                        <div class="relative">
                            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                            <input
                                type="text"
                                x-model="query"
                                @focus="open = true"
                                @input="open = true"
                                @keydown.arrow-down.prevent="move(1)"
                                @keydown.arrow-up.prevent="move(-1)"
                                @keydown.enter.prevent="addHighlighted()"
                                placeholder="Search by name, room, QR, or asset tag"
                                class="h-11 w-full rounded-xl border-0 bg-white pl-10 pr-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:ring-2 focus:ring-slate-900/10"
                                autocomplete="off"
                            >
                        </div>

                        <div
                            x-show="open"
                            x-cloak
                            class="absolute left-0 right-0 top-[calc(100%+0.35rem)] z-40 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
                        >
                            <div class="max-h-64 overflow-y-auto py-1">
                                <template x-if="filtered.length === 0">
                                    <p class="px-3 py-4 text-sm text-slate-400">No matching schedulable equipment.</p>
                                </template>
                                <template x-for="(item, index) in filtered" :key="item.id">
                                    <button
                                        type="button"
                                        @click="addItem(item)"
                                        class="flex w-full items-start gap-3 px-3 py-2.5 text-left transition"
                                        :class="index === highlight ? 'bg-[#0025cc]/5' : 'hover:bg-slate-50'"
                                    >
                                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                            <i data-lucide="wrench" class="h-4 w-4"></i>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-medium text-slate-900" x-text="item.name"></span>
                                            <span class="mt-0.5 block truncate text-xs text-slate-500" x-text="meta(item)"></span>
                                        </span>
                                        <span class="shrink-0 text-[11px] font-semibold text-[#0025cc]">Add</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-400">Only equipment with a generated QR code can be scheduled.</p>
                        <p x-show="pickerError" x-cloak class="mt-2 text-xs font-medium text-rose-600" x-text="pickerError"></p>
                    </div>

                    <div class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/80">
                        <template x-if="cart.length === 0">
                            <div class="px-4 py-8 text-center">
                                <p class="text-sm font-medium text-slate-700">No equipment added</p>
                                <p class="mt-1 text-xs text-slate-400">Search above to add one or many assets to this maintenance batch.</p>
                            </div>
                        </template>
                        <template x-if="cart.length > 0">
                            <div class="divide-y divide-slate-100">
                                <template x-for="(line, index) in cart" :key="line.id">
                                    <div class="flex items-center gap-3 px-4 py-3">
                                        <input type="hidden" :name="'equipment_ids[' + index + ']'" :value="line.id">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium text-slate-900" x-text="line.name"></p>
                                            <p class="mt-0.5 truncate text-xs text-slate-500" x-text="meta(line)"></p>
                                        </div>
                                        <button
                                            type="button"
                                            @click="removeLine(index)"
                                            class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                                            aria-label="Remove equipment"
                                        >
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                    <p x-show="cartError" x-cloak class="text-xs font-medium text-rose-600" x-text="cartError"></p>
                </div>

                <div>
                    <label for="scheduleTitle" class="mb-1.5 block text-sm text-slate-600">Title</label>
                    <input
                        id="scheduleTitle"
                        type="text"
                        name="title"
                        placeholder="Quarterly inspection"
                        required
                        class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                    />
                </div>

                <div>
                    <label for="scheduleDescription" class="mb-1.5 block text-sm text-slate-600">
                        Description <span class="text-slate-400">(optional)</span>
                    </label>
                    <textarea
                        id="scheduleDescription"
                        name="description"
                        rows="3"
                        placeholder="Notes or instructions"
                        class="w-full resize-none rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm leading-6 text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                    ></textarea>
                </div>

                <div>
                    <label for="scheduleFrequency" class="mb-1.5 block text-sm text-slate-600">Frequency</label>
                    <select
                        id="scheduleFrequency"
                        name="frequency"
                        class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 transition focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                    >
                        <option value="Monthly">Monthly</option>
                        <option value="Quarterly">Quarterly</option>
                        <option value="Semi Annual">Semi annual</option>
                        <option value="Annual">Annual</option>
                    </select>
                </div>

                <div x-data="scheduleNextDatePicker()" class="space-y-2.5">
                    <label class="block text-sm text-slate-600">Next date</label>
                    <input id="scheduleNextDate" type="hidden" name="next_date" x-model="value" />

                    <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-3.5 py-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0025cc] text-white">
                            <i data-lucide="calendar" class="h-4 w-4"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Scheduled for</p>
                            <p class="truncate text-sm font-semibold text-slate-900" x-text="display"></p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm">
                        <div class="mb-3 flex items-center justify-between">
                            <button
                                type="button"
                                @click="prevMonth()"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                                aria-label="Previous month"
                            >
                                <i data-lucide="chevron-left" class="h-4 w-4"></i>
                            </button>
                            <p class="text-sm font-semibold text-slate-900" x-text="monthLabel"></p>
                            <button
                                type="button"
                                @click="nextMonth()"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                                aria-label="Next month"
                            >
                                <i data-lucide="chevron-right" class="h-4 w-4"></i>
                            </button>
                        </div>

                        <div class="mb-1.5 grid grid-cols-7">
                            <template x-for="day in weekdays" :key="day">
                                <div class="py-1 text-center text-[10px] font-semibold uppercase tracking-wide text-slate-400" x-text="day"></div>
                            </template>
                        </div>

                        <div class="grid grid-cols-7 gap-y-1">
                            <template x-for="day in days" :key="day.iso + (day.outside ? '-out' : '')">
                                <button
                                    type="button"
                                    @click="pick(day)"
                                    class="mx-auto flex h-9 w-9 items-center justify-center rounded-full text-sm transition"
                                    :class="day.selected
                                        ? 'bg-[#0025cc] font-semibold text-white shadow-sm shadow-[#0025cc]/25'
                                        : day.isToday
                                            ? 'font-semibold text-[#0025cc] ring-1 ring-[#0025cc]/30'
                                            : day.outside
                                                ? 'text-slate-300 hover:bg-slate-50 hover:text-slate-500'
                                                : 'text-slate-700 hover:bg-slate-100'"
                                    x-text="day.d"
                                ></button>
                            </template>
                        </div>

                        <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3">
                            <button
                                type="button"
                                @click="clearDate()"
                                class="rounded-lg px-2 py-1 text-xs font-medium text-slate-400 transition hover:bg-slate-50 hover:text-slate-700"
                            >
                                Clear
                            </button>
                            <button
                                type="button"
                                @click="goToday()"
                                class="rounded-lg px-2.5 py-1 text-xs font-semibold text-[#0025cc] transition hover:bg-[#0025cc]/5"
                            >
                                Today
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2 px-6 pb-6">
                <button
                    type="button"
                    onclick="closeScheduleModal()"
                    class="rounded-xl px-3.5 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#001fa8]"
                    x-text="cart.length ? ('Create ' + cart.length + ' schedule' + (cart.length === 1 ? '' : 's')) : 'Create schedule'"
                ></button>
            </div>
        </form>
    </div>
</div>

<div
    id="viewModal"
    class="fixed inset-0 z-[1300] hidden items-start justify-center bg-[#0b1220]/70 p-4"
>
    <div class="flex max-h-[86vh] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
        <div class="flex items-start justify-between gap-4 px-6 pt-6">
            <div class="min-w-0">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900">Schedule details</h2>
                <p class="mt-1 text-sm text-slate-500">View this maintenance schedule.</p>
            </div>
            <button
                type="button"
                onclick="closeViewModal()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close modal"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
            <div id="scheduleDetails" class="space-y-3"></div>
        </div>

        <div class="flex shrink-0 items-center justify-end px-6 pb-6">
            <button
                type="button"
                onclick="closeViewModal()"
                class="rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#001fa8]"
            >
                Close
            </button>
        </div>
    </div>
</div>

<div
    id="completeModal"
    class="fixed inset-0 z-[1300] hidden items-start justify-center bg-[#0b1220]/70 p-4"
>
    <div class="flex max-h-[86vh] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
        <div class="flex items-start justify-between gap-4 px-6 pt-6">
            <div class="min-w-0 flex-1">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900">Complete maintenance</h2>
                <div class="mt-1 flex items-center justify-between gap-3">
                    <p id="completeEquipmentName" class="min-w-0 truncate text-sm text-slate-500"></p>
                    <p id="completeQrCode" class="shrink-0 font-mono text-sm text-slate-500"></p>
                </div>
            </div>
            <button
                type="button"
                onclick="closeCompleteModal()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close modal"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <form
            action="/maintenance/schedules/complete"
            method="POST"
            enctype="multipart/form-data"
            class="flex min-h-0 flex-1 flex-col"
        >
            @csrf
            <input type="hidden" id="completeScheduleId" name="schedule_id" />

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-6 py-6">
                <div>
                    <label for="completeFindings" class="mb-1.5 block text-sm text-slate-600">Findings</label>
                    <textarea
                        id="completeFindings"
                        name="findings"
                        rows="3"
                        placeholder="Inspection findings"
                        required
                        class="w-full resize-none rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm leading-6 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-400"
                    ></textarea>
                </div>

                <div>
                    <label for="completeRepairAction" class="mb-1.5 block text-sm text-slate-600">Repair action</label>
                    <textarea
                        id="completeRepairAction"
                        name="repair_action"
                        rows="3"
                        placeholder="Work performed"
                        required
                        class="w-full resize-none rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm leading-6 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-400"
                    ></textarea>
                </div>

                <div>
                    <label for="completeMaintenanceStatus" class="mb-1.5 block text-sm text-slate-600">Status</label>
                    <select
                        id="completeMaintenanceStatus"
                        name="maintenance_status"
                        required
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-400"
                    >
                        <option value="Resolved">Resolved</option>
                        <option value="Pending">Pending</option>
                        <option value="Escalated">Escalated</option>
                    </select>
                </div>

                <div x-data="completeProofUploader()" class="space-y-1.5">
                    <label class="block text-sm text-slate-600">
                        Proof image <span class="text-slate-400">(optional)</span>
                    </label>

                    <div
                        class="relative overflow-hidden rounded-2xl border border-dashed transition"
                        :class="dragging
                            ? 'border-[#0025cc] bg-[#0025cc]/5'
                            : preview
                                ? 'border-slate-200 bg-white'
                                : 'border-slate-300 bg-slate-50 hover:border-[#0025cc]/40 hover:bg-[#0025cc]/[0.03]'"
                        @dragover.prevent="dragging = true"
                        @dragleave.prevent="dragging = false"
                        @drop.prevent="onDrop($event)"
                    >
                        <input
                            id="completeProofImage"
                            type="file"
                            name="proof_image"
                            accept="image/*"
                            class="sr-only"
                            @change="onFile($event.target.files[0] || null)"
                        />

                        <template x-if="!preview">
                            <label for="completeProofImage" class="flex cursor-pointer flex-col items-center px-4 py-6 text-center">
                                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#0025cc] text-white">
                                    <i data-lucide="image-plus" class="h-5 w-5"></i>
                                </span>
                                <p class="mt-3 text-sm font-medium text-slate-800">
                                    Drop an image here, or <span class="text-[#0025cc]">browse</span>
                                </p>
                                <p class="mt-1 text-xs text-slate-400">PNG, JPG, or WEBP</p>
                            </label>
                        </template>

                        <template x-if="preview">
                            <div class="flex items-center gap-3 p-3">
                                <img :src="preview" alt="Proof preview" class="h-14 w-14 shrink-0 rounded-xl object-cover ring-1 ring-slate-200" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-slate-900" x-text="fileName"></p>
                                    <p class="mt-0.5 text-xs text-slate-400" x-text="fileMeta"></p>
                                    <label for="completeProofImage" class="mt-1 inline-block cursor-pointer text-xs font-semibold text-[#0025cc] hover:underline">
                                        Replace
                                    </label>
                                </div>
                                <button
                                    type="button"
                                    @click="clearFile()"
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                                    aria-label="Remove image"
                                >
                                    <i data-lucide="x" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2 px-6 pb-6">
                <button
                    type="button"
                    onclick="closeCompleteModal()"
                    class="rounded-xl px-3.5 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#001fa8]"
                >
                    Complete
                </button>
            </div>
        </form>
    </div>
</div>

<div
    id="rescheduleModal"
    class="fixed inset-0 z-[1300] hidden items-start justify-center bg-[#0b1220]/70 p-4"
>
    <div class="flex w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
        <div class="flex items-start justify-between gap-4 px-6 pt-6">
            <div class="min-w-0 flex-1">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900">Reschedule</h2>
                <div class="mt-1 flex items-center justify-between gap-3">
                    <p id="rescheduleEquipmentName" class="min-w-0 truncate text-sm text-slate-500"></p>
                    <p id="rescheduleQrCode" class="shrink-0 font-mono text-sm text-slate-500"></p>
                </div>
            </div>
            <button
                type="button"
                onclick="closeRescheduleModal()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close modal"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <form action="/maintenance/schedules/reschedule" method="POST">
            @csrf
            <input type="hidden" id="rescheduleScheduleId" name="schedule_id" />

            <div class="space-y-4 px-6 py-6">
                <div>
                    <label for="rescheduleNewDate" class="mb-1.5 block text-sm text-slate-600">New date</label>
                    <input
                        id="rescheduleNewDate"
                        type="date"
                        name="new_date"
                        required
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-400"
                    />
                </div>
                <div>
                    <label for="rescheduleReason" class="mb-1.5 block text-sm text-slate-600">Reason</label>
                    <textarea
                        id="rescheduleReason"
                        name="reason"
                        rows="3"
                        placeholder="Why this date is changing"
                        required
                        class="w-full resize-none rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm leading-6 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-400"
                    ></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 pb-6">
                <button
                    type="button"
                    onclick="closeRescheduleModal()"
                    class="rounded-xl px-3.5 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#001fa8]"
                >
                    Save date
                </button>
            </div>
        </form>
    </div>
</div>

<div
    id="deleteModal"
    class="fixed inset-0 z-[1300] hidden items-center justify-center bg-[#0b1220]/70 p-4"
>
    <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-xl">
        <div class="flex items-start justify-between gap-4 px-6 pt-6">
            <div class="min-w-0">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900">Delete schedule</h2>
                <p id="deleteScheduleTitle" class="mt-1 text-sm leading-6 text-slate-500"></p>
            </div>
            <button
                type="button"
                onclick="closeDeleteModal()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close modal"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <form action="/maintenance/schedules/delete" method="POST">
            @csrf
            @method('DELETE')
            <input type="hidden" id="deleteScheduleId" name="schedule_id" />

            <div class="flex items-center justify-end gap-2 px-6 py-6">
                <button
                    type="button"
                    onclick="closeDeleteModal()"
                    class="rounded-xl px-3.5 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-rose-700"
                >
                    Delete
                </button>
            </div>
        </form>
    </div>
</div>

<div
    id="scheduleQrModal"
    class="fixed inset-0 z-[1400] hidden items-center justify-center bg-[#0b1220]/70 p-4"
    onclick="if (event.target === this) closeScheduleQrModal()"
>
    <div class="w-full max-w-sm overflow-hidden rounded-2xl bg-white shadow-xl">
        <div class="flex items-start justify-between gap-4 px-6 pt-6">
            <div class="min-w-0">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900">QR code</h2>
                <p id="scheduleQrEquipmentName" class="mt-1 truncate text-sm text-slate-500"></p>
            </div>
            <button
                type="button"
                onclick="closeScheduleQrModal()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close modal"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>
        <div class="flex flex-col items-center px-6 py-6">
            <img
                id="scheduleQrPreviewImage"
                alt="Equipment QR code"
                class="h-56 w-56 rounded-2xl border border-slate-200 bg-white object-contain p-3"
            />
            <p class="mt-4 text-[11px] font-medium uppercase tracking-wide text-slate-400">QR code</p>
            <p id="scheduleQrPreviewCode" class="mt-1 break-all text-center font-mono text-sm font-semibold text-slate-900"></p>
        </div>
    </div>
</div>
