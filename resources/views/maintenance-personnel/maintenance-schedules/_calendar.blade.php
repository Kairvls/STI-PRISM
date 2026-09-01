@php
    use Carbon\Carbon;

    $today = Carbon::today();

    $effectiveStatus = function ($schedule) use ($today) {
        if (($schedule->maintenance_schedule_status ?? "Active") === "Completed") {
            return "Completed";
        }

        if (
            !empty($schedule->maintenance_schedule_next_date) &&
            Carbon::parse($schedule->maintenance_schedule_next_date)->lt($today)
        ) {
            return "Overdue";
        }

        return $schedule->maintenance_schedule_status ?? "Active";
    };

    // =====================================================
    // BUILD CALENDAR DATA FROM ALL SCHEDULES
    // DO NOT USE PAGINATED TABLE DATA HERE
    // =====================================================

    $calendarSchedules = $calendarSchedulesData

        ->filter(
            fn($schedule) =>
                !empty(
                    $schedule->maintenance_schedule_next_date
                )
        )

        ->map(
            fn($schedule) => [

                "id" =>
                    (int) $schedule->maintenance_schedule_id,

                "equipment" =>
                    $schedule->equipment_name
                    ?? "Unassigned equipment",

                "asset_tag" =>
                    $schedule->equipment_asset_tag ?? "",

                "serial_number" =>
                    $schedule->equipment_serial_number ?? "",

                "equipment_identifier" =>
                    collect([
                        $schedule->equipment_asset_tag
                            ? 'Tag: '.$schedule->equipment_asset_tag
                            : null,
                        $schedule->equipment_serial_number
                            ? 'Serial: '.$schedule->equipment_serial_number
                            : null,
                    ])->filter()->implode(' · '),

                "category" =>
                    $schedule->equipment_category_name ?? "",

                "brand" =>
                    $schedule->equipment_brand_name ?? "",

                "model" =>
                    $schedule->equipment_model ?? "",

                "inventory_status" =>
                    $schedule->equipment_inventory_status ?? "",

                "condition_status" =>
                    $schedule->equipment_condition_status ?? "",

                "room" =>
                    $schedule->room_name
                    ?? "No room assigned",

                "title" =>
                    $schedule->maintenance_schedule_title
                    ?? "Maintenance Schedule",

                "frequency" =>
                    $schedule->maintenance_schedule_frequency
                    ?? "Not set",

                "date" =>
                    Carbon::parse(
                        $schedule->maintenance_schedule_next_date
                    )->format("Y-m-d"),

                "last_date" =>
                    !empty($schedule->maintenance_schedule_last_date)
                        ? Carbon::parse(
                            $schedule->maintenance_schedule_last_date
                        )->format("Y-m-d")
                        : "",

                "status" =>
                    $effectiveStatus($schedule),

                "description" =>
                    $schedule->maintenance_schedule_description
                    ?? "",

                "qr_code" =>
                    $schedule->equipment_qr_code
                    ?? "",

                "qr_image" =>
                    !empty($schedule->equipment_qr_code)
                        ? url("/maintenance/equipment/qr-image/".$schedule->equipment_qr_code)
                        : "",

            ]
        )

        ->values();

    $scheduleQrById = $calendarSchedulesData->mapWithKeys(function ($schedule) {
        $code = trim((string) ($schedule->equipment_qr_code ?? ""));

        return [
            (int) $schedule->maintenance_schedule_id => [
                "qr_code" => $code,
                "qr_image" => $code !== ""
                    ? url("/maintenance/equipment/qr-image/".$code)
                    : "",
            ],
        ];
    });

    $upcomingAll = $calendarSchedules
        ->filter(function ($item) use ($today) {
            if (($item["status"] ?? "") === "Completed") {
                return false;
            }

            if (empty($item["date"])) {
                return false;
            }

            return Carbon::parse($item["date"])->gte($today);
        })
        ->sortBy("date")
        ->values();

    $upcomingCount = $upcomingAll->count();

    $overdueAll = $calendarSchedules
        ->filter(function ($item) use ($today) {
            if (($item["status"] ?? "") === "Completed") {
                return false;
            }

            if (empty($item["date"])) {
                return false;
            }

            return Carbon::parse($item["date"])->lt($today);
        })
        ->sortByDesc("date")
        ->values();

    $overdueCount = $overdueAll->count();

    $completedPctBar = (int) min(100, max(0, round($completedMaintenancePercentage ?? 0)));
    $firstName = explode(" ", trim(Auth::user()->user_full_name ?? "there"))[0];
@endphp

<div
    class="space-y-6"
    x-data="{
        wide: localStorage.getItem('prism-schedule-wide') === '1',
        calendarFull: false,
        miniMonth: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
        selectedDay: new Date().toISOString().slice(0, 10),
        events: {{ Js::from($calendarSchedules) }},
        overdueAll: {{ Js::from($overdueAll) }},
        upcomingAll: {{ Js::from($upcomingAll) }},
        sidebarOverdueLimit: 2,
        sidebarUpcomingLimit: 2,
        sidebarLimitsObserver: null,
        toggleWide() {
            this.wide = !this.wide;
            localStorage.setItem('prism-schedule-wide', this.wide ? '1' : '0');
            this.$nextTick(() => this.updateSidebarLimits());
        },
        openBigCalendar() {
            this.calendarFull = true;
            this.$nextTick(() => {
                if (typeof renderCalendar === 'function') {
                    renderCalendar();
                }
                if (window.lucide) {
                    lucide.createIcons();
                }
            });
        },
        closeBigCalendar() {
            this.calendarFull = false;
            this.$nextTick(() => {
                this.updateSidebarLimits();
                if (window.lucide) {
                    lucide.createIcons();
                }
            });
        },
        monthLabel() {
            return this.miniMonth.toLocaleString('en-US', { month: 'long', year: 'numeric' });
        },
        dayCells() {
            const year = this.miniMonth.getFullYear();
            const month = this.miniMonth.getMonth();
            const firstDow = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const cells = [];
            for (let i = 0; i < firstDow; i++) {
                cells.push(null);
            }
            for (let day = 1; day <= daysInMonth; day++) {
                const value = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                cells.push({ day, value });
            }
            return cells;
        },
        hasEvent(value) {
            return this.events.some((item) => item.date === value);
        },
        selectedEvents() {
            return this.events.filter((item) => item.date === this.selectedDay);
        },
        formatSidebarDate(value) {
            if (!value) {
                return '';
            }
            const [year, month, day] = String(value).split('-').map(Number);
            return new Date(year, month - 1, day).toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
            });
        },
        visibleOverdue() {
            return this.overdueAll.slice(0, this.sidebarOverdueLimit);
        },
        visibleUpcoming() {
            return this.upcomingAll.slice(0, this.sidebarUpcomingLimit);
        },
        updateSidebarLimits() {
            const section = document.getElementById('scheduleListSection');
            if (!section || this.wide || this.calendarFull) {
                this.sidebarOverdueLimit = 2;
                this.sidebarUpcomingLimit = 2;
                return;
            }

            const dataRows = section.querySelectorAll(
                '.schedule-list-table tbody tr:not([data-empty-row])',
            ).length;
            const sectionHeight = section.offsetHeight;

            let overdueLimit = 2;
            if (sectionHeight >= 880 || dataRows >= 12) {
                overdueLimit = 4;
            } else if (sectionHeight >= 700 || dataRows >= 8) {
                overdueLimit = 3;
            }

            let upcomingLimit = 2;
            if (sectionHeight >= 820 || dataRows >= 11) {
                upcomingLimit = 3;
            } else if (sectionHeight >= 680 || dataRows >= 7) {
                upcomingLimit = 2;
            }

            this.sidebarOverdueLimit = overdueLimit;
            this.sidebarUpcomingLimit = upcomingLimit;
        },
        bindSidebarLimits() {
            const section = document.getElementById('scheduleListSection');
            if (!section) {
                return;
            }

            this.updateSidebarLimits();

            if (this.sidebarLimitsObserver) {
                this.sidebarLimitsObserver.disconnect();
            }

            this.sidebarLimitsObserver = new ResizeObserver(() => {
                this.updateSidebarLimits();
            });
            this.sidebarLimitsObserver.observe(section);
        },
    }"
    x-init="$nextTick(() => bindSidebarLimits())"
>
    @if (session("success"))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-700">
            {{ session("success") }}
        </div>
    @endif

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_280px]" x-show="!calendarFull">
        <div class="relative overflow-hidden rounded-2xl border border-stone-200/80 bg-[#f5f6f8] px-7 py-8 text-slate-900">
            <img
                src="{{ asset('image/maintenance_home_card_image.png') }}"
                alt=""
                class="pointer-events-none absolute inset-y-0 right-0 h-full w-[58%] object-cover object-right"
            >
            <div
                class="pointer-events-none absolute inset-0 bg-gradient-to-r from-[#f5f6f8] from-[0%] via-[#f5f6f8] via-[38%] to-transparent to-[82%]"
            ></div>
            <div class="relative z-10 pr-28 sm:pr-40">
            <p class="text-2xl font-semibold tracking-tight">
                {{ number_format($overdueMaintenance ?? 0) }} Overdue {{ \Illuminate\Support\Str::plural('Schedule', (int) ($overdueMaintenance ?? 0)) }}
            </p>
            <p class="mt-1 max-w-md text-sm text-slate-500">
                Review upcoming work and keep preventive maintenance on schedule.
            </p>
            <div class="mt-6 flex flex-wrap gap-2.5">
                <button
                    type="button"
                    onclick="openScheduleModal()"
                    class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800"
                >
                    Schedule maintenance
                </button>
                <button
                    type="button"
                    @click="openBigCalendar()"
                    class="rounded-xl bg-white/70 px-4 py-2.5 text-sm font-medium text-slate-800 ring-1 ring-stone-300/80 transition hover:bg-white"
                >
                    Open calendar
                </button>
            </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <p class="text-[32px] font-semibold leading-none tracking-tight text-slate-900">
                {{ number_format($completedMaintenance) }}
            </p>
            <p class="mt-2 text-sm font-medium text-slate-500">Schedules completed</p>
            <div class="mt-5 flex items-center justify-between text-xs text-slate-400">
                <span>{{ number_format($upcomingMaintenance) }} pending</span>
                <span>{{ $completedPctBar }}%</span>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-slate-900" style="width: {{ $completedPctBar }}%"></div>
            </div>
        </div>
    </div>

    <div
        class="grid items-stretch gap-6"
        :class="wide ? 'grid-cols-1' : 'xl:grid-cols-[minmax(0,1fr)_300px]'"
        x-show="!calendarFull"
    >
    <section id="scheduleListSection" class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="flex flex-col gap-4 px-6 pb-2 pt-6 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold tracking-tight text-slate-900">All Schedules</h2>
                <p class="mt-0.5 text-sm text-slate-400">
                    {{ $schedules->total() }}
                    {{ $schedules->total() === 1 ? "schedule" : "schedules" }}
                    available
                </p>
            </div>
            <div class="flex items-center gap-2">
            <button
                type="button"
                @click="toggleWide()"
                class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-700 transition hover:bg-slate-200"
                :data-tooltip="wide ? 'Exit full width' : 'Full width'"
                :aria-label="wide ? 'Exit full width' : 'Full width'"
            >
                <i data-lucide="maximize-2" class="h-4 w-4" x-show="!wide"></i>
                <i data-lucide="minimize-2" class="h-4 w-4" x-show="wide" x-cloak></i>
            </button>
            <button
                type="button"
                onclick="openScheduleModal()"
                class="inline-flex h-9 items-center justify-center rounded-xl bg-slate-100 px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-200"
            >
                Add schedule
            </button>
            </div>
        </div>


        {{-- ===================================================== --}}
        {{-- SEARCH AND FILTER BAR --}}
        {{-- ADD BETWEEN SCHEDULE LIST HEADER AND TABLE --}}
        {{-- ===================================================== --}}

        <form
            method="GET"
            action="{{ url()->current() }}"
            class="border-b border-slate-200 px-5 py-4"
        >

            <div
                class="flex flex-col gap-3
                    lg:flex-row lg:items-center"
            >

                {{-- ================================================= --}}
                {{-- SEARCH --}}
                {{-- ================================================= --}}

                <div class="relative min-w-0 flex-1">

                    <i
                        data-lucide="search"
                        class="pointer-events-none absolute
                            left-3 top-1/2 h-4 w-4
                            -translate-y-1/2 text-slate-400"
                    ></i>

                    <input
                        type="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search equipment, asset tag, serial, room..."

                        class="h-10 w-full rounded-lg
                            border border-slate-200
                            bg-white pl-10 pr-3
                            text-sm text-slate-700
                            outline-none transition
                            placeholder:text-slate-400
                            focus:border-slate-400
                            focus:ring-2 focus:ring-slate-100"
                    >

                </div>


                {{-- ===================================================== --}}
                {{-- FREQUENCY FILTER --}}
                {{-- VALUES MATCH CREATE SCHEDULE MODAL --}}
                {{-- ===================================================== --}}

                <div class="relative">

                    <select
                        name="frequency"

                        class="h-10 min-w-[170px]
                            appearance-none rounded-lg
                            border border-slate-200
                            bg-white pl-3 pr-9
                            text-sm text-slate-600
                            outline-none transition
                            focus:border-slate-400
                            focus:ring-2 focus:ring-slate-100"
                    >

                        <option value="">
                            All Frequencies
                        </option>


                        {{-- ================================================= --}}
                        {{-- MONTHLY --}}
                        {{-- ================================================= --}}

                        <option
                            value="Monthly"
                            @selected(request('frequency') === 'Monthly')
                        >
                            Monthly
                        </option>


                        {{-- ================================================= --}}
                        {{-- QUARTERLY --}}
                        {{-- ================================================= --}}

                        <option
                            value="Quarterly"
                            @selected(request('frequency') === 'Quarterly')
                        >
                            Quarterly
                        </option>


                        {{-- ================================================= --}}
                        {{-- SEMI ANNUAL --}}
                        {{-- MUST MATCH VALUE SAVED BY CREATE MODAL --}}
                        {{-- ================================================= --}}

                        <option
                            value="Semi annual"
                            @selected(request('frequency') === 'Semi annual')
                        >
                            Semi annual
                        </option>


                        {{-- ================================================= --}}
                        {{-- ANNUAL --}}
                        {{-- ================================================= --}}

                        <option
                            value="Annual"
                            @selected(request('frequency') === 'Annual')
                        >
                            Annual
                        </option>

                    </select>


                    {{-- ===================================================== --}}
                    {{-- DROPDOWN ICON --}}
                    {{-- ===================================================== --}}

                    <i
                        data-lucide="chevron-down"
                        class="pointer-events-none absolute
                            right-3 top-1/2 h-4 w-4
                            -translate-y-1/2 text-slate-400"
                    ></i>

                </div>


                {{-- ================================================= --}}
                {{-- STATUS FILTER --}}
                {{-- ================================================= --}}

                <div class="relative">

                    <select
                        name="status"

                        class="h-10 min-w-[160px]
                            appearance-none rounded-lg
                            border border-slate-200
                            bg-white pl-3 pr-9
                            text-sm text-slate-600
                            outline-none transition
                            focus:border-slate-400
                            focus:ring-2 focus:ring-slate-100"
                    >

                        <option value="">
                            All Status
                        </option>

                        <option
                            value="Active"
                            @selected(request('status') === 'Active')
                        >
                            Active
                        </option>

                        <option
                            value="Completed"
                            @selected(request('status') === 'Completed')
                        >
                            Completed
                        </option>

                        <option
                            value="Overdue"
                            @selected(request('status') === 'Overdue')
                        >
                            Overdue
                        </option>

                    </select>

                    <i
                        data-lucide="chevron-down"
                        class="pointer-events-none absolute
                            right-3 top-1/2 h-4 w-4
                            -translate-y-1/2 text-slate-400"
                    ></i>

                </div>


                {{-- ================================================= --}}
                {{-- ROOM FILTER --}}
                {{-- ================================================= --}}

                <div class="relative">

                    <select
                        name="room"

                        class="h-10 min-w-[180px]
                            appearance-none rounded-lg
                            border border-slate-200
                            bg-white pl-3 pr-9
                            text-sm text-slate-600
                            outline-none transition
                            focus:border-slate-400
                            focus:ring-2 focus:ring-slate-100"
                    >

                        <option value="">
                            All Rooms
                        </option>

                        @foreach ($rooms ?? [] as $room)
                            <option
                                value="{{ $room->room_id }}"
                                @selected((string) request('room') === (string) $room->room_id)
                            >
                                {{ $room->room_name }}
                            </option>
                        @endforeach

                    </select>

                    <i
                        data-lucide="chevron-down"
                        class="pointer-events-none absolute
                            right-3 top-1/2 h-4 w-4
                            -translate-y-1/2 text-slate-400"
                    ></i>

                </div>


                {{-- ================================================= --}}
                {{-- APPLY --}}
                {{-- ================================================= --}}

                <button
                    type="submit"
                    data-tooltip="Apply"
                    class="inline-flex h-10 items-center
                        justify-center gap-2 rounded-lg
                        bg-[#0025cc] px-4
                        text-sm font-semibold text-white
                        transition hover:bg-[#001fa8]"
                >

                    <i
                        data-lucide="sliders-horizontal"
                        class="h-4 w-4"
                    ></i>

                    

                </button>


                {{-- ================================================= --}}
                {{-- CLEAR --}}
                {{-- ================================================= --}}

                @if (
                    request()->filled('search')
                    || request()->filled('frequency')
                    || request()->filled('status')
                    || request()->filled('room')
                )

                    <a
                        href="{{ url()->current() }}"

                        class="inline-flex h-10 items-center
                            justify-center gap-2 rounded-lg
                            border border-slate-200
                            bg-white px-4
                            text-sm font-medium text-slate-600
                            transition
                            hover:border-slate-300
                            hover:bg-slate-50
                            hover:text-slate-900"
                    >

                        <i
                            data-lucide="x"
                            class="h-4 w-4"
                        ></i>

                        Clear

                    </a>

                @endif

            </div>

        </form>


        {{-- ===================================================== --}}
        {{-- TABLE --}}
        {{-- ===================================================== --}}

        <div class="overflow-x-auto">

            <table
                class="schedule-list-table w-full text-left"
                :class="wide ? 'is-wide' : 'min-w-0'"
            >

                {{-- ================================================= --}}
                {{-- TABLE HEADER --}}
                {{-- ================================================= --}}

                <thead class="border-b border-slate-100">
                    <tr class="text-[11px] font-medium uppercase tracking-[0.12em] text-slate-400">
                        <th class="px-5 py-3">
                            Equipment
                        </th>

                        <th class="schedule-col-location px-5 py-3">
                            Location
                        </th>

                        <th class="px-5 py-3">
                            QR Code
                        </th>

                        <th class="schedule-col-extra px-5 py-3">
                            Frequency
                        </th>

                        <th class="px-5 py-3">
                            Next Date
                        </th>

                        <th class="px-5 py-3">
                            Status
                        </th>

                        <th class="w-12 px-5 py-3 text-center">
                            Actions
                        </th>
                    </tr>
                </thead>


                {{-- ================================================= --}}
                {{-- TABLE BODY --}}
                {{-- ================================================= --}}

                <tbody class="divide-y divide-dashed divide-slate-200">

                    @forelse ($schedules as $schedule)

                        @php
                            $rowStatus = $effectiveStatus($schedule);

                            $statusClass = match ($rowStatus) {
                                "Completed" => "bg-emerald-50 text-emerald-700",
                                "Overdue" => "bg-rose-50 text-rose-700",
                                default => "bg-sky-50 text-sky-700",
                            };

                            $statusDotClass = match ($rowStatus) {
                                "Completed" => "bg-emerald-500",

                                "Overdue" => "bg-red-500",

                                default => "bg-blue-500",
                            };

                            $equipmentIdentifier = collect([
                                $schedule->equipment_asset_tag
                                    ? 'Tag: '.$schedule->equipment_asset_tag
                                    : null,
                                $schedule->equipment_serial_number
                                    ? 'Serial: '.$schedule->equipment_serial_number
                                    : null,
                            ])->filter()->implode(' · ');

                            $rowQrCode = trim((string) ($schedule->equipment_qr_code ?? ""));
                            $rowQrImage = $rowQrCode !== ""
                                ? url('/maintenance/equipment/qr-image/'.$rowQrCode)
                                : "";
                        @endphp


                        <tr
                            class="group transition-colors
                                hover:bg-slate-50/70"
                        >

                            {{-- ===================================== --}}
                            {{-- EQUIPMENT --}}
                            {{-- ===================================== --}}

                            <td class="px-5 py-4">

                                <div class="flex items-center gap-3">

                                    {{-- EQUIPMENT ICON --}}
                                    <div
                                        class="schedule-equipment-icon flex h-9 w-9 shrink-0
                                            items-center justify-center
                                            rounded-lg border border-slate-200
                                            bg-white text-slate-400"
                                    >
                                        <i
                                            data-lucide="wrench"
                                            class="h-4 w-4"
                                        ></i>
                                    </div>


                                    {{-- EQUIPMENT INFORMATION --}}
                                    <div class="min-w-0">

                                        <p
                                            class="schedule-equipment-name max-w-[220px] truncate
                                                text-sm font-semibold
                                                text-slate-800"
                                        >
                                            {{
                                                $schedule->equipment_name
                                                    ?? "Unassigned equipment"
                                            }}
                                        </p>

                                        <p class="schedule-equipment-meta mt-0.5 max-w-[220px] truncate text-[11px] text-slate-400">
                                            {{ $equipmentIdentifier ?: 'No asset tag or serial' }}
                                        </p>

                                    </div>

                                </div>

                            </td>


                            {{-- ===================================== --}}
                            {{-- LOCATION --}}
                            {{-- ===================================== --}}

                            <td class="schedule-col-location px-5 py-4">
                                <div class="flex items-center gap-1.5 text-sm text-slate-600">
                                    <i data-lucide="map-pin" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                                    <span class="schedule-room-text max-w-[180px] truncate">
                                        {{ $schedule->room_name ?? "No room assigned" }}
                                    </span>
                                </div>
                            </td>


                            {{-- ===================================== --}}
                            {{-- QR CODE --}}
                            {{-- ===================================== --}}

                            <td class="px-5 py-4">
                                @if ($rowQrCode !== "")
                                    <button
                                        type="button"
                                        onclick="openScheduleQrModal(@js($rowQrImage), @js($rowQrCode), @js($schedule->equipment_name ?? 'Equipment'))"
                                        class="schedule-qr-btn flex items-center gap-2.5 rounded-lg text-left transition hover:bg-slate-50"
                                        data-tooltip="{{ $rowQrCode }}"
                                    >
                                        <img
                                            src="{{ $rowQrImage }}"
                                            alt="QR code for {{ $schedule->equipment_name ?? 'equipment' }}"
                                            class="schedule-qr-img h-12 w-12 rounded-lg border border-slate-200 bg-white object-contain p-1"
                                        />
                                        <span class="schedule-qr-text min-w-0 truncate font-mono text-[11px] text-slate-500">
                                            {{ $rowQrCode }}
                                        </span>
                                    </button>
                                @else
                                    <div class="schedule-qr-btn flex items-center gap-2 text-slate-400">
                                        <span class="schedule-qr-empty flex h-12 w-12 items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50">
                                            <i data-lucide="qr-code" class="h-4 w-4"></i>
                                        </span>
                                        <span class="schedule-qr-text text-[11px]">No QR</span>
                                    </div>
                                @endif
                            </td>


                            <td class="schedule-col-extra px-5 py-4">

                                <span
                                    class="schedule-frequency-badge inline-flex rounded-md
                                        bg-slate-100 px-2 py-1
                                        text-[11px] font-medium
                                        text-slate-600"
                                >
                                    {{
                                        $schedule->maintenance_schedule_frequency
                                    }}
                                </span>

                            </td>


                            {{-- ===================================== --}}
                            {{-- NEXT DATE --}}
                            {{-- ===================================== --}}

                            <td class="px-5 py-4">

                                <div class="flex items-center gap-2">

                                    <i
                                        data-lucide="calendar-clock"
                                        class="h-3.5 w-3.5 text-slate-400"
                                    ></i>

                                    <span
                                        class="whitespace-nowrap
                                            text-xs font-medium text-slate-700"
                                    >
                                        {{
                                            $schedule->maintenance_schedule_next_date
                                                ? Carbon::parse(
                                                    $schedule->maintenance_schedule_next_date
                                                )->format("M d, Y")
                                                : "-"
                                        }}
                                    </span>

                                </div>

                            </td>


                            {{-- ===================================== --}}
                            {{-- STATUS --}}
                            {{-- ===================================== --}}

                            <td class="px-5 py-4">
                                <span class="inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-medium {{ $statusClass }}">
                                    {{ $rowStatus }}
                                </span>
                            </td>


                            {{-- ===================================== --}}
                            {{-- ACTION MENU --}}
                            {{-- ===================================== --}}

                            <td class="px-5 py-4 text-center">

                                <div
                                    class="relative inline-block"
                                    x-data="{
                                        open: false,
                                        dropUp: false,
                                        menuStyle: {},
                                        toggle() {
                                            this.open = !this.open;
                                            if (this.open) {
                                                this.$nextTick(() => this.place());
                                            }
                                        },
                                        place() {
                                            const button = this.$refs.button;
                                            const menu = this.$refs.menu;
                                            if (!button || !menu) {
                                                return;
                                            }

                                            const rect = button.getBoundingClientRect();
                                            const height = menu.offsetHeight;
                                            const width = menu.offsetWidth;
                                            const gap = 8;
                                            const spaceBelow = window.innerHeight - rect.bottom - gap;
                                            const spaceAbove = rect.top - gap;

                                            this.dropUp = spaceBelow < height && spaceAbove > spaceBelow;

                                            let top = this.dropUp
                                                ? rect.top - height - gap
                                                : rect.bottom + gap;
                                            let left = rect.right - width;

                                            top = Math.min(
                                                Math.max(8, top),
                                                window.innerHeight - height - 8,
                                            );
                                            left = Math.min(
                                                Math.max(8, left),
                                                window.innerWidth - width - 8,
                                            );

                                            this.menuStyle = {
                                                top: `${top}px`,
                                                left: `${left}px`,
                                            };
                                        },
                                    }"
                                    @keydown.escape.window="open = false"
                                    @scroll.window="open && place()"
                                    @resize.window="open && place()"
                                    @click.outside="open = false"
                                >

                                    {{-- MENU BUTTON --}}
                                    <button
                                        type="button"
                                        x-ref="button"
                                        @click="toggle()"
                                        data-tooltip="Actions"
                                        class="flex h-8 w-8 items-center
                                            justify-center rounded-lg
                                            text-slate-400 transition
                                            hover:bg-slate-200/70
                                            hover:text-slate-700"
                                    >
                                        <i
                                            data-lucide="ellipsis"
                                            class="h-4 w-4"
                                        ></i>
                                    </button>


                                    {{-- DROPDOWN --}}
                                    <div
                                        x-cloak
                                        x-ref="menu"
                                        x-show="open"
                                        x-transition
                                        :style="menuStyle"
                                        class="fixed z-[80]
                                            w-44 overflow-hidden rounded-xl
                                            border border-slate-200 bg-white
                                            p-1.5 text-left
                                            shadow-lg shadow-slate-900/10"
                                    >

                                        {{-- VIEW --}}
                                        <button
                                            type="button"
                                            @click="
                                                open = false;
                                                viewScheduleById(
                                                    {{ (int) $schedule->maintenance_schedule_id }}
                                                );
                                            "
                                            class="flex w-full items-center gap-2.5
                                                rounded-lg px-3 py-2
                                                text-xs font-medium text-slate-600
                                                transition
                                                hover:bg-slate-50
                                                hover:text-slate-900"
                                        >
                                            <i
                                                data-lucide="eye"
                                                class="h-3.5 w-3.5"
                                            ></i>

                                            View details
                                        </button>


                                        {{-- COMPLETE --}}
                                        <button
                                            type="button"
                                            @click="
                                                open = false;
                                                openCompleteModal(
                                                    {{ (int) $schedule->maintenance_schedule_id }},
                                                    @js(
                                                        $schedule->equipment_name
                                                            ?? "Unassigned equipment"
                                                    ),
                                                    @js($equipmentIdentifier ?: 'No asset tag or serial'),
                                                    @js($schedule->room_name ?? 'No room assigned')
                                                );
                                            "
                                            class="flex w-full items-center gap-2.5
                                                rounded-lg px-3 py-2
                                                text-xs font-medium text-slate-600
                                                transition
                                                hover:bg-slate-50
                                                hover:text-slate-900"
                                        >
                                            <i
                                                data-lucide="circle-check"
                                                class="h-3.5 w-3.5"
                                            ></i>

                                            Mark complete
                                        </button>


                                        {{-- RESCHEDULE --}}
                                        <button
                                            type="button"
                                            @click="
                                                open = false;
                                                openRescheduleModal(
                                                    {{ (int) $schedule->maintenance_schedule_id }},
                                                    @js(
                                                        $schedule->equipment_name
                                                            ?? "Unassigned equipment"
                                                    )
                                                );
                                            "
                                            class="flex w-full items-center gap-2.5
                                                rounded-lg px-3 py-2
                                                text-xs font-medium text-slate-600
                                                transition
                                                hover:bg-slate-50
                                                hover:text-slate-900"
                                        >
                                            <i
                                                data-lucide="calendar-sync"
                                                class="h-3.5 w-3.5"
                                            ></i>

                                            Reschedule
                                        </button>


                                        <div
                                            class="my-1 border-t border-slate-100"
                                        ></div>


                                        {{-- DELETE --}}
                                        <button
                                            type="button"
                                            @click="
                                                open = false;
                                                openDeleteModal(
                                                    {{ (int) $schedule->maintenance_schedule_id }},
                                                    @js(
                                                        $schedule->maintenance_schedule_title
                                                            ?? "this schedule"
                                                    ),
                                                    @js(
                                                        $schedule->equipment_name
                                                            ?? "Unassigned equipment"
                                                    ),
                                                    @js($equipmentIdentifier ?: 'No asset tag or serial')
                                                );
                                            "
                                            class="flex w-full items-center gap-2.5
                                                rounded-lg px-3 py-2
                                                text-xs font-medium text-red-600
                                                transition
                                                hover:bg-red-50"
                                        >
                                            <i
                                                data-lucide="trash-2"
                                                class="h-3.5 w-3.5"
                                            ></i>

                                            Delete schedule
                                        </button>

                                    </div>

                                </div>

                            </td>

                        </tr>


                    @empty

                        <tr data-empty-row>
                            <td
                                :colspan="7"
                                class="px-6 py-16 text-center"
                            >
                                <div class="mx-auto flex max-w-sm flex-col items-center">

                                    {{-- ================================================= --}}
                                    {{-- ICON --}}
                                    {{-- ================================================= --}}

                                    <div
                                        class="flex h-12 w-12 items-center justify-center
                                            rounded-2xl border border-slate-200
                                            bg-slate-50 text-slate-400"
                                    >
                                        <i
                                            data-lucide="{{
                                                request()->filled('search')
                                                || request()->filled('frequency')
                                                || request()->filled('status')
                                                || request()->filled('room')
                                                    ? 'search-x'
                                                    : 'calendar-plus'
                                            }}"
                                            class="h-5 w-5"
                                        ></i>
                                    </div>


                                    {{-- ================================================= --}}
                                    {{-- TITLE --}}
                                    {{-- ================================================= --}}

                                    <h3 class="mt-4 text-sm font-semibold text-slate-800">

                                        {{
                                            request()->filled('search')
                                            || request()->filled('frequency')
                                            || request()->filled('status')
                                            || request()->filled('room')

                                                ? 'No matching schedules'

                                                : 'No maintenance schedules yet'
                                        }}

                                    </h3>


                                    {{-- ================================================= --}}
                                    {{-- DESCRIPTION --}}
                                    {{-- ================================================= --}}

                                    <p class="mt-1.5 max-w-xs text-xs leading-5 text-slate-400">

                                        {{
                                            request()->filled('search')
                                            || request()->filled('frequency')
                                            || request()->filled('status')

                                                ? 'No maintenance schedules match your current search or filters.'

                                                : 'Create a maintenance schedule to track upcoming preventive maintenance for your equipment.'
                                        }}

                                    </p>


                                    {{-- ================================================= --}}
                                    {{-- EMPTY STATE ACTION --}}
                                    {{-- ================================================= --}}

                                    @if (
                                        request()->filled('search')
                                        || request()->filled('frequency')
                                        || request()->filled('status')
                                    )

                                        <a
                                            href="{{ url()->current() }}"

                                            class="mt-5 inline-flex h-9 items-center gap-2
                                                rounded-lg border border-slate-200
                                                bg-white px-3.5
                                                text-xs font-semibold text-slate-600
                                                shadow-sm transition
                                                hover:border-slate-300
                                                hover:bg-slate-50
                                                hover:text-slate-900"
                                        >
                                            <i
                                                data-lucide="rotate-ccw"
                                                class="h-3.5 w-3.5"
                                            ></i>

                                            Clear filters
                                        </a>

                                    @else

                                        <button
                                            type="button"
                                            onclick="openScheduleModal()"

                                            class="mt-5 inline-flex h-9 items-center gap-2
                                                rounded-lg border border-slate-200
                                                bg-white px-3.5
                                                text-xs font-semibold text-slate-600
                                                shadow-sm transition
                                                hover:border-slate-300
                                                hover:bg-slate-50
                                                hover:text-slate-900"
                                        >
                                            <i
                                                data-lucide="plus"
                                                class="h-3.5 w-3.5"
                                            ></i>

                                            Create schedule
                                        </button>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        {{-- ===================================================== --}}
        {{-- PAGINATION --}}
        {{-- ADD BELOW THE TABLE CONTAINER --}}
        {{-- ===================================================== --}}

        @if ($schedules->hasPages())

            <div
                class="flex flex-col gap-3
                    border-t border-slate-200
                    px-5 py-4
                    sm:flex-row
                    sm:items-center
                    sm:justify-between"
            >

                {{-- ================================================= --}}
                {{-- PAGINATION INFORMATION --}}
                {{-- ================================================= --}}

                <p class="text-xs text-slate-500">

                    Showing

                    <span class="font-semibold text-slate-700">
                        {{ $schedules->firstItem() }}
                    </span>

                    to

                    <span class="font-semibold text-slate-700">
                        {{ $schedules->lastItem() }}
                    </span>

                    of

                    <span class="font-semibold text-slate-700">
                        {{ $schedules->total() }}
                    </span>

                    maintenance schedules

                </p>


                {{-- ================================================= --}}
                {{-- PAGINATION LINKS --}}
                {{-- ================================================= --}}

                <div>
                    {{ $schedules->links() }}
                </div>

            </div>

        @endif

    </section>

    <aside class="flex h-full flex-col gap-5" x-show="!wide && !calendarFull" x-cloak>
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Maintenance Calendar</h3>
                    <p class="mt-0.5 text-xs text-slate-400">Reports and scheduled maintenance</p>
                </div>
                <button
                    type="button"
                    @click="openBigCalendar()"
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600 transition hover:bg-slate-200"
                    data-tooltip="Full screen"
                >
                    <i data-lucide="maximize-2" class="h-4 w-4"></i>
                </button>
            </div>

            <p class="mt-5 text-sm font-semibold text-slate-800" x-text="monthLabel()"></p>
            <div class="mt-3 grid grid-cols-7 text-center">
                <template x-for="label in ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']">
                    <span class="py-1 text-[10px] font-medium uppercase text-slate-400" x-text="label"></span>
                </template>
                <template x-for="(cell, index) in dayCells()" :key="index">
                    <div class="py-0.5">
                        <button
                            type="button"
                            x-show="cell"
                            class="relative mx-auto flex h-7 min-w-7 items-center justify-center rounded-[10px] px-2 text-xs"
                            :class="cell && selectedDay === cell.value ? 'bg-[#e8ecff] font-semibold text-[#0025cc]' : 'text-slate-600 hover:bg-slate-100'"
                            @click="if (cell) selectedDay = cell.value"
                        >
                            <span x-text="cell ? cell.day : ''"></span>
                            <span
                                class="absolute bottom-0.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-rose-500"
                                x-show="cell && hasEvent(cell.value) && selectedDay !== cell.value"
                            ></span>
                        </button>
                    </div>
                </template>
            </div>

            <div class="mt-4 border-t border-slate-100 pt-3">
                <template x-if="selectedEvents().length === 0">
                    <p class="text-xs leading-5 text-slate-400">No reports or maintenance schedules for this date.</p>
                </template>
                <div class="space-y-1" x-show="selectedEvents().length > 0">
                    <template x-for="item in selectedEvents().slice(0, 2)" :key="item.id">
                        <div>
                            <p class="truncate text-xs font-medium text-slate-800" x-text="item.title"></p>
                            <p class="truncate text-[11px] text-slate-400" x-text="item.equipment"></p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div class="space-y-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex items-baseline justify-between gap-2">
                <h3 class="text-sm font-semibold text-slate-900">Overdue</h3>
                <p
                    class="text-[11px] text-slate-400"
                    x-show="overdueAll.length > 0"
                    x-text="`${Math.min(sidebarOverdueLimit, overdueAll.length)} of ${overdueAll.length}`"
                ></p>
            </div>
            <div class="mt-4 space-y-0">
                <template x-if="visibleOverdue().length === 0">
                    <p class="text-sm text-slate-400">No overdue schedules.</p>
                </template>
                <template x-for="(item, index) in visibleOverdue()" :key="`overdue-${item.id}`">
                    <div class="relative flex gap-3 pb-4 last:pb-0">
                        <span
                            class="absolute left-[5px] top-3 h-full w-px bg-slate-200"
                            x-show="index < visibleOverdue().length - 1"
                        ></span>
                        <span class="relative z-10 mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-slate-300 ring-1 ring-slate-200"></span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-medium text-slate-400" x-text="formatSidebarDate(item.date)"></p>
                            <p class="mt-0.5 truncate text-sm font-medium text-slate-800" x-text="item.title"></p>
                            <p class="truncate text-xs text-slate-400">
                                <span x-text="item.equipment"></span>
                                <template x-if="item.equipment_identifier">
                                    <span x-text="` · ${item.equipment_identifier}`"></span>
                                </template>
                            </p>
                        </div>
                    </div>
                </template>
            </div>
            <p
                class="mt-3 text-[11px] font-medium text-rose-500"
                x-show="overdueAll.length > sidebarOverdueLimit"
                x-text="`+${overdueAll.length - sidebarOverdueLimit} more overdue`"
            ></p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex items-baseline justify-between gap-2">
                <h3 class="text-sm font-semibold text-slate-900">Upcoming</h3>
                <p
                    class="text-[11px] text-slate-400"
                    x-show="upcomingAll.length > 0"
                    x-text="`${Math.min(sidebarUpcomingLimit, upcomingAll.length)} of ${upcomingAll.length}`"
                ></p>
            </div>
            <div class="mt-4 space-y-0">
                <template x-if="visibleUpcoming().length === 0">
                    <p class="text-sm text-slate-400">No upcoming schedules.</p>
                </template>
                <template x-for="(item, index) in visibleUpcoming()" :key="`upcoming-${item.id}`">
                    <div class="relative flex gap-3 pb-4 last:pb-0">
                        <span
                            class="absolute left-[5px] top-3 h-full w-px bg-slate-200"
                            x-show="index < visibleUpcoming().length - 1"
                        ></span>
                        <span class="relative z-10 mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-slate-300 ring-1 ring-slate-200"></span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-medium text-slate-400" x-text="formatSidebarDate(item.date)"></p>
                            <p class="mt-0.5 truncate text-sm font-medium text-slate-800" x-text="item.title"></p>
                            <p class="truncate text-xs text-slate-400">
                                <span x-text="item.equipment"></span>
                                <template x-if="item.equipment_identifier">
                                    <span x-text="` · ${item.equipment_identifier}`"></span>
                                </template>
                            </p>
                        </div>
                    </div>
                </template>
            </div>
            <p
                class="mt-3 text-[11px] font-medium text-sky-600"
                x-show="upcomingAll.length > sidebarUpcomingLimit"
                x-text="`+${upcomingAll.length - sidebarUpcomingLimit} more upcoming`"
            ></p>
        </div>
        </div>
    </aside>
    </div>

    {{-- ========================================================= --}}
    {{-- MINIMALIST MAINTENANCE CALENDAR --}}
    {{-- ========================================================= --}}

    <section
        id="maintenance-calendar"
        x-show="calendarFull"
        x-cloak
        class="flex min-h-[calc(100vh-7rem)] overflow-hidden rounded-[32px] border border-slate-200/70 bg-slate-100 shadow-[0_30px_80px_rgba(15,23,42,0.08)]"
    >
        <aside class="calendar-sidebar relative z-0 flex w-[220px] shrink-0 flex-col sm:w-[250px]">
            <div class="calendar-sidebar-wave pointer-events-none absolute inset-x-0 top-0 z-0" aria-hidden="true">
                <svg viewBox="0 0 250 250" preserveAspectRatio="none" class="h-full w-full">
                    <path
                        fill="#0025cc"
                        d="M0 0 H250 C250 55 210 110 155 160 C110 200 55 210 0 185 Z"
                    ></path>
                </svg>
            </div>

            <img
                src="{{ asset('image/calendar_schedule.png') }}"
                alt=""
                class="calendar-sidebar-art"
                aria-hidden="true"
            >

            <div class="relative z-10 flex min-h-0 flex-1 flex-col px-5 pb-7 pt-10">
                <div class="calendar-sidebar-nav flex min-h-0 flex-1 flex-col items-center overflow-hidden">
                    <h2 class="calendar-sidebar-title">
                        Calendar
                    </h2>

                    <div class="calendar-sidebar-carousel flex w-full flex-1 flex-col items-center">
                        <div class="flex items-center justify-center gap-3">
                            <button
                                type="button"
                                onclick="calendarSidebarYear(-1)"
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-slate-900 shadow-sm transition hover:bg-slate-50"
                                aria-label="Previous year"
                            >
                                <i data-lucide="chevron-left" class="h-4 w-4"></i>
                            </button>
                            <p id="calendarSidebarYear" class="calendar-sidebar-year"></p>
                            <button
                                type="button"
                                onclick="calendarSidebarYear(1)"
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-slate-900 shadow-sm transition hover:bg-slate-50"
                                aria-label="Next year"
                            >
                                <i data-lucide="chevron-right" class="h-4 w-4"></i>
                            </button>
                        </div>

                        <div id="calendarMonthList" class="mt-7 flex w-full flex-1 flex-col items-center gap-2.5 overflow-y-auto px-1 pb-2"></div>
                    </div>
                </div>

                <div class="mt-6 space-y-2 border-t border-slate-200/80 pt-5 text-center">
                    <p class="calendar-sidebar-meta text-[12px] leading-5 text-slate-500">
                        This month you have
                        <span class="font-semibold text-slate-700">{{ number_format($upcomingCount) }}</span>
                        upcoming
                    </p>
                    <p class="calendar-sidebar-meta text-[12px] leading-5 text-slate-500">
                        Completed
                        <span class="font-semibold text-slate-700">{{ number_format($completedMaintenance) }}</span>
                        schedules
                    </p>
                </div>
            </div>
        </aside>

        <div class="calendar-main relative z-10 flex min-w-0 flex-1 flex-col rounded-l-[40px] bg-white pl-6 pr-5 pb-6 pt-6 shadow-[-18px_0_40px_rgba(15,23,42,0.04)] sm:pl-8 sm:pr-7 sm:pb-7">
            <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h3 id="calendarFocusDate" class="calendar-focus-date"></h3>
                    <p id="calendarCurrentTitle" class="calendar-focus-sub"></p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
                        <input
                            id="calendarSearchInput"
                            type="search"
                            placeholder="Search..."
                            oninput="renderCalendar()"
                            class="h-9 w-40 rounded-full border border-slate-200 bg-slate-50 pl-8 pr-3 text-xs text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-[#6b7cff]/50 focus:bg-white sm:w-48"
                        >
                    </div>

                    <select
                        id="calendarStatusFilter"
                        onchange="renderCalendar()"
                        class="h-9 rounded-full border border-slate-200 bg-slate-50 px-3 text-xs font-medium text-slate-600 outline-none transition focus:border-[#6b7cff]/50 focus:bg-white"
                    >
                        <option value="all">All statuses</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="overdue">Overdue</option>
                    </select>

                    <div class="flex rounded-full bg-slate-100 p-1">
                        <button type="button" data-calendar-view="week" class="calendar-view-button rounded-full px-3 py-1.5 text-[11px] font-semibold text-slate-500 transition">Week</button>
                        <button type="button" data-calendar-view="month" class="calendar-view-button rounded-full bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-900 shadow-sm transition">Month</button>
                        <button type="button" data-calendar-view="year" class="calendar-view-button rounded-full px-3 py-1.5 text-[11px] font-semibold text-slate-500 transition">Year</button>
                    </div>

                    <button
                        type="button"
                        onclick="calendarGoToday()"
                        class="h-9 rounded-full bg-[#0025cc] px-3.5 text-[11px] font-semibold text-white transition hover:bg-[#5a6af0]"
                    >
                        Today
                    </button>

                    <button
                        type="button"
                        @click="closeBigCalendar()"
                        class="inline-flex h-9 items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 text-[11px] font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800"
                    >
                        <i data-lucide="minimize-2" class="h-3.5 w-3.5"></i>
                        Exit
                    </button>
                </div>
            </div>

            <div class="mb-3 flex flex-wrap items-center gap-4">
                <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-slate-400">
                    <span class="h-2.5 w-2.5 rounded-full bg-[#0025cc]"></span>
                    Active
                </span>
                <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-slate-400">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    Completed
                </span>
                <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-slate-400">
                    <span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                    Overdue
                </span>
            </div>

            <div
                id="calendarWeekdayHeader"
                class="grid shrink-0 grid-cols-7 overflow-hidden border border-b-0 border-slate-200 bg-[#f5f6f8]"
            >
                <div class="calendar-weekday">Sun</div>
                <div class="calendar-weekday">Mon</div>
                <div class="calendar-weekday">Tue</div>
                <div class="calendar-weekday">Wed</div>
                <div class="calendar-weekday">Thu</div>
                <div class="calendar-weekday">Fri</div>
                <div class="calendar-weekday">Sat</div>
            </div>

            <div
                id="calendarGrid"
                class="grid min-h-0 flex-1 grid-cols-7 overflow-hidden border border-slate-200 bg-white"
            ></div>
        </div>
    </section>

    
</div>

<div
    id="calendarEventPopover"
    class="fixed z-[1200] hidden w-[320px] overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_20px_60px_rgba(0,0,0,0.16)]"
>
    <!-- ===================================== -->
    <!-- POPOVER HEADER -->
    <!-- ===================================== -->
    <div class="px-5 pb-4 pt-5">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <!-- STATUS -->
                <p
                    id="popoverStatus"
                    class="mb-2 inline-flex rounded-full px-2.5 py-1 text-[10px] font-medium uppercase tracking-[0.08em]"
                ></p>

                <!-- SCHEDULE TITLE -->
                <h3
                    id="popoverTitle"
                    class="truncate text-base font-semibold tracking-tight text-slate-950"
                ></h3>

                <!-- EQUIPMENT -->
                <p
                    id="popoverEquipment"
                    class="mt-1 truncate text-sm text-slate-500"
                ></p>
            </div>

            <!-- CLOSE BUTTON -->
            <button
                type="button"
                onclick="closeEventPopover()"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close popover"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>
    </div>

    <!-- ===================================== -->
    <!-- SCHEDULE DETAILS -->
    <!-- ===================================== -->
    <div class="border-y border-slate-100 px-5 py-1">
        <!-- LOCATION -->
        <div class="flex items-center justify-between gap-6 py-3">
            <span class="text-sm text-slate-500">
                Location
            </span>

            <span
                id="popoverRoom"
                class="max-w-[60%] truncate text-right text-sm font-medium text-slate-900"
            ></span>
        </div>

        <!-- FREQUENCY -->
        <div
            class="flex items-center justify-between gap-6 border-t border-slate-100 py-3"
        >
            <span class="text-sm text-slate-500">
                Frequency
            </span>

            <span
                id="popoverFrequency"
                class="max-w-[60%] truncate text-right text-sm font-medium text-slate-900"
            ></span>
        </div>

        <!-- SCHEDULED DATE -->
        <div
            class="flex items-center justify-between gap-6 border-t border-slate-100 py-3"
        >
            <span class="text-sm text-slate-500">
                Scheduled date
            </span>

            <span
                id="popoverDate"
                class="max-w-[60%] truncate text-right text-sm font-medium text-slate-900"
            ></span>
        </div>
    </div>

    <!-- ===================================== -->
    <!-- POPOVER ACTIONS -->
    <!-- ===================================== -->
    <div class="flex items-center justify-end gap-2 px-5 py-4">
        <button
            type="button"
            onclick="viewSelectedSchedule()"
            class="rounded-lg bg-[#f5f6f8] px-3.5 py-2 text-sm font-medium text-slate-800 transition hover:bg-slate-100"
        >
            View details
        </button>

        <button
            type="button"
            onclick="completeSelectedSchedule()"
            class="rounded-lg bg-[#0025cc] px-3.5 py-2 text-sm font-medium text-white transition hover:bg-[#001fa8] focus:outline-none focus:ring-4 focus:ring-[#0025cc]/20"
        >
            Complete
        </button>
    </div>
</div>

@include ("maintenance-personnel.maintenance-schedules._modals")

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    #maintenance-calendar,
    #maintenance-calendar * {
        font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
    }

    .calendar-sidebar-title {
        margin: 0;
        width: 100%;
        text-align: center;
        font-size: 28px;
        font-weight: 700;
        letter-spacing: -0.04em;
        line-height: 1.1;
        color: #ffffff;
    }

    .calendar-sidebar {
        --calendar-wave-height: 62%;
    }

    .calendar-sidebar-art {
        position: absolute;
        right: -22px;
        bottom: -14px;
        z-index: 1;
        width: 176px;
        max-width: 78%;
        height: auto;
        pointer-events: none;
        user-select: none;
        opacity: 0.92;
    }

    @media (min-width: 640px) {
        .calendar-sidebar-art {
            width: 198px;
            right: -26px;
            bottom: -16px;
        }
    }

    .calendar-sidebar-wave {
        height: var(--calendar-wave-height);
        /* Bleed under the main panel’s left curve so no gap shows */
        width: calc(100% + 48px);
        right: auto;
    }

    .calendar-sidebar-nav {
        width: 100%;
    }

    .calendar-sidebar-carousel {
        margin-top: 2.5rem;
    }

    /* Keep gray under the curve below the wave */
    .calendar-sidebar::after {
        content: "";
        position: absolute;
        top: var(--calendar-wave-height);
        right: -48px;
        bottom: 0;
        width: 48px;
        background: #f1f5f9;
        z-index: 0;
        pointer-events: none;
    }

    .calendar-sidebar-year {
        min-width: 3.5rem;
        text-align: center;
        font-size: 18px;
        font-weight: 700;
        letter-spacing: -0.03em;
        color: #0f172a;
        transition: color 0.15s ease;
    }
    .calendar-sidebar-year.is-on-wave {
        color: #ffffff;
    }
    .calendar-sidebar-year:not(.is-on-wave) {
        color: #334155;
    }

    .calendar-sidebar-meta {
        font-size: 12px;
        font-weight: 500;
        letter-spacing: -0.01em;
        line-height: 1.45;
        color: #64748b;
    }

    .calendar-focus-date {
        margin: 0;
        font-size: 26px;
        font-weight: 700;
        letter-spacing: -0.045em;
        line-height: 1.1;
        color: #0f172a;
    }

    .calendar-focus-sub {
        margin: 4px 0 0;
        font-size: 13px;
        font-weight: 500;
        letter-spacing: -0.01em;
        color: #94a3b8;
    }

    .schedule-list-table:not(.is-wide) .schedule-col-extra {
        display: none;
    }
    .schedule-list-table:not(.is-wide) {
        table-layout: fixed;
        width: 100%;
        min-width: 0;
    }
    .schedule-list-table:not(.is-wide) th,
    .schedule-list-table:not(.is-wide) td {
        padding: 0.75rem;
        overflow: hidden;
        vertical-align: middle;
    }
    .schedule-list-table:not(.is-wide) th:nth-child(1),
    .schedule-list-table:not(.is-wide) td:nth-child(1) {
        width: 24%;
    }
    .schedule-list-table:not(.is-wide) th:nth-child(2),
    .schedule-list-table:not(.is-wide) td:nth-child(2) {
        width: 14%;
    }
    .schedule-list-table:not(.is-wide) th:nth-child(3),
    .schedule-list-table:not(.is-wide) td:nth-child(3) {
        width: 22%;
    }
    .schedule-list-table:not(.is-wide) th:nth-child(5),
    .schedule-list-table:not(.is-wide) td:nth-child(5) {
        width: 14%;
    }
    .schedule-list-table:not(.is-wide) th:nth-child(6),
    .schedule-list-table:not(.is-wide) td:nth-child(6) {
        width: 11%;
    }
    .schedule-list-table:not(.is-wide) th:nth-child(7),
    .schedule-list-table:not(.is-wide) td:nth-child(7) {
        width: 3rem;
        padding-left: 0.375rem;
        padding-right: 0.375rem;
    }
    .schedule-list-table:not(.is-wide) .schedule-equipment-name,
    .schedule-list-table:not(.is-wide) .schedule-equipment-meta,
    .schedule-list-table:not(.is-wide) .schedule-room-text {
        max-width: 100%;
    }
    .schedule-list-table:not(.is-wide) .schedule-qr-text {
        min-width: 0;
        max-width: 100%;
        flex: 1 1 0%;
    }
    .schedule-list-table:not(.is-wide) .schedule-qr-btn {
        min-width: 0;
        max-width: 100%;
    }
    .schedule-list-table.is-wide {
        table-layout: fixed;
        width: 100%;
        min-width: 0;
    }
    .schedule-list-table.is-wide th,
    .schedule-list-table.is-wide td {
        padding: 0.625rem 0.75rem;
        overflow: hidden;
        vertical-align: middle;
    }
    .schedule-list-table.is-wide th:nth-child(1),
    .schedule-list-table.is-wide td:nth-child(1) {
        width: 22%;
    }
    .schedule-list-table.is-wide th:nth-child(2),
    .schedule-list-table.is-wide td:nth-child(2) {
        width: 11%;
    }
    .schedule-list-table.is-wide th:nth-child(3),
    .schedule-list-table.is-wide td:nth-child(3) {
        width: 22%;
    }
    .schedule-list-table.is-wide th:nth-child(4),
    .schedule-list-table.is-wide td:nth-child(4) {
        width: 10%;
    }
    .schedule-list-table.is-wide th:nth-child(5),
    .schedule-list-table.is-wide td:nth-child(5) {
        width: 13%;
    }
    .schedule-list-table.is-wide th:nth-child(6),
    .schedule-list-table.is-wide td:nth-child(6) {
        width: 10%;
    }
    .schedule-list-table.is-wide th:nth-child(7),
    .schedule-list-table.is-wide td:nth-child(7) {
        width: 3rem;
        padding-left: 0.375rem;
        padding-right: 0.375rem;
    }
    .schedule-list-table.is-wide .schedule-equipment-icon {
        height: 2rem;
        width: 2rem;
    }
    .schedule-list-table.is-wide .schedule-equipment-name,
    .schedule-list-table.is-wide .schedule-equipment-meta,
    .schedule-list-table.is-wide .schedule-room-text {
        max-width: 100%;
    }
    .schedule-list-table.is-wide .schedule-qr-text {
        display: block;
        min-width: 0;
        max-width: 100%;
        flex: 1 1 0%;
    }
    .schedule-list-table.is-wide .schedule-qr-img,
    .schedule-list-table.is-wide .schedule-qr-empty {
        height: 2.25rem;
        width: 2.25rem;
        flex-shrink: 0;
    }
    .schedule-list-table.is-wide .schedule-qr-btn {
        gap: 0.5rem;
        min-width: 0;
        max-width: 100%;
    }
    .schedule-list-table.is-wide .schedule-frequency-badge {
        white-space: nowrap;
        padding-left: 0.375rem;
        padding-right: 0.375rem;
    }
    .schedule-list-table.is-wide td > .flex.items-center.gap-3 {
        gap: 0.5rem;
    }
    .schedule-drawer-scroll {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
    }
    .schedule-drawer-scroll::-webkit-scrollbar {
        width: 6px;
    }
    .schedule-drawer-scroll::-webkit-scrollbar-thumb {
        border-radius: 999px;
        background: #cbd5e1;
    }
    .calendar-month-item {
        border: 0;
        background: transparent;
        padding: 0.2rem 0.75rem;
        min-width: 9rem;
        text-align: center;
        font-size: 15px;
        font-weight: 500;
        letter-spacing: -0.025em;
        color: #64748b;
        transition: color 0.2s ease, transform 0.15s ease, font-size 0.15s ease, text-shadow 0.2s ease;
        cursor: pointer;
        border-radius: 9999px;
    }
    .calendar-month-item:hover {
        color: #334155;
    }
    .calendar-month-item.is-active {
        font-size: 24px;
        font-weight: 700;
        letter-spacing: -0.045em;
        color: #0025cc;
    }
    .calendar-month-item.is-on-wave {
        color: rgba(255, 255, 255, 0.82);
    }
    .calendar-month-item.is-on-wave:hover {
        color: #ffffff;
    }
    .calendar-month-item.is-on-wave.is-active {
        color: #ffffff;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.18);
    }
    .calendar-weekday {
        display: flex;
        height: 40px;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #94a3b8;
        border-right: 1px solid #e2e8f0;
        background: #f5f6f8;
    }
    .calendar-weekday:last-child {
        border-right: 0;
    }
    .calendar-day {
        position: relative;
        min-width: 0;
        min-height: 0;
        overflow: hidden;
        border: 0;
        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        border-radius: 0;
        background: #fff;
        padding: 8px;
        text-align: left;
        box-shadow: none;
        transition: background-color 0.15s ease;
    }
    .calendar-day:nth-child(7n) {
        border-right: 0;
    }
    .calendar-day:hover {
        background: #fafafa;
        box-shadow: none;
    }
    .calendar-day-outside {
        background: #fff;
    }
    .calendar-day-outside:hover {
        background: #fafafa;
    }
    .calendar-day-number {
        position: relative;
        z-index: 1;
        display: inline-flex;
        height: 28px;
        width: 28px;
        align-items: center;
        justify-content: center;
        margin: 0;
        border-radius: 999px;
        padding: 0;
        font-size: 12px;
        font-weight: 600;
        color: #334155;
    }
    .calendar-day-outside .calendar-day-number {
        color: #cbd5e1;
    }
    .calendar-today {
        background: #fff;
    }
    .calendar-today .calendar-day-number {
        background: #e8ecff;
        color: #0025cc;
        box-shadow: none;
    }
    .calendar-day.has-events .calendar-day-number::after {
        display: none;
    }
    .calendar-today.has-events .calendar-day-number::after,
    .calendar-day.has-overdue .calendar-day-number::after,
    .calendar-day.has-completed .calendar-day-number::after {
        display: none;
    }
    .calendar-day.has-overdue:not(.calendar-today) .calendar-day-number {
        background: transparent;
        color: #334155;
    }
    .calendar-day.has-completed:not(.has-overdue):not(.calendar-today) .calendar-day-number {
        background: transparent;
        color: #334155;
    }
    .calendar-events {
        display: flex;
        flex-direction: column;
        gap: 4px;
        margin-top: 8px;
        text-align: left;
    }
    .calendar-event {
        position: relative;
        width: 100%;
        overflow: hidden;
        border-radius: 8px;
        padding: 5px 8px 5px 10px;
        text-align: left;
        transition:
            transform 0.15s ease,
            filter 0.15s ease,
            box-shadow 0.15s ease;
    }
    .calendar-event:hover {
        transform: translateY(-1px);
        filter: brightness(0.98);
        box-shadow: 0 6px 14px rgb(15 23 42 / 0.08);
    }
    .calendar-event::before {
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        width: 3px;
        content: "";
    }
    .calendar-event-active {
        background: rgb(0 37 204 / 0.08);
        color: #0025cc;
    }
    .calendar-event-active::before {
        background: #0025cc;
    }
    .calendar-event-completed {
        background: rgb(236 253 245);
        color: #047857;
    }
    .calendar-event-completed::before {
        background: #10b981;
    }
    .calendar-event-overdue {
        background: rgb(255 241 242);
        color: #e11d48;
    }
    .calendar-event-overdue::before {
        background: #f43f5e;
    }
    .calendar-event-title,
    .calendar-event-equipment {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .calendar-event-title {
        font-size: 10px;
        font-weight: 600;
        letter-spacing: -0.01em;
    }
    .calendar-event-equipment {
        display: none;
    }
    .calendar-year-card {
        min-height: 220px;
        border: 1px solid rgb(226 232 240 / 0.9);
        border-radius: 24px;
        background: white;
        padding: 16px;
        box-shadow: 0 1px 0 rgb(15 23 42 / 0.02);
    }
    .calendar-year-day {
        display: flex;
        min-height: 28px;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 600;
        color: rgb(100 116 139);
        transition: background-color 0.15s ease, color 0.15s ease;
    }
    .calendar-year-day.has-events {
        background: rgb(107 124 255 / 0.14);
        color: #4f63f0;
    }
    @media (max-width: 768px) {
        .calendar-day {
            min-height: 84px;
            padding: 6px 4px;
        }
        .calendar-events {
            margin-top: 6px;
        }
        #maintenance-calendar {
            flex-direction: column;
        }
        #maintenance-calendar > aside {
            width: 100%;
        }
        #maintenance-calendar > .calendar-main {
            border-radius: 28px 28px 0 0;
            margin-left: 0;
        }
    }
</style>

<script>
    const calendarSchedules = @js ($calendarSchedules);
    const scheduleQrById = @js ($scheduleQrById);
    let calendarCurrentDate = new Date();
    let calendarSelectedSchedule = null;
    let calendarCurrentView = "month";
    document.addEventListener("DOMContentLoaded", () => {
        bindCalendarViewButtons();
        renderCalendar();
        if (window.lucide) lucide.createIcons();

        const monthList = document.getElementById("calendarMonthList");
        if (monthList) {
            monthList.addEventListener(
                "scroll",
                () => {
                    syncCalendarSidebarWave();
                    syncCalendarSidebarContrast();
                },
                { passive: true },
            );
        }
        window.addEventListener("resize", () => {
            syncCalendarSidebarWave();
            syncCalendarSidebarContrast();
        });
    });
    function bindCalendarViewButtons() {
        document.querySelectorAll(".calendar-view-button").forEach((button) => {
            button.addEventListener("click", () => {
                calendarCurrentView = button.dataset.calendarView;
                document
                    .querySelectorAll(".calendar-view-button")
                    .forEach((item) => {
                        item.classList.remove(
                            "bg-white",
                            "text-slate-900",
                            "text-slate-950",
                            "font-semibold",
                            "font-black",
                            "shadow-sm",
                        );
                        item.classList.add("text-slate-500");
                    });
                button.classList.add(
                    "bg-white",
                    "text-slate-900",
                    "font-semibold",
                    "shadow-sm",
                );
                button.classList.remove("text-slate-500");
                closeEventPopover();
                renderCalendar();
            });
        });
    }
    function renderCalendar() {
        if (calendarCurrentView === "week") renderWeekCalendar();
        else if (calendarCurrentView === "year") renderYearCalendar();
        else renderMonthCalendar();
        syncCalendarSidebar();
    }
    function syncCalendarSidebar() {
        const yearEl = document.getElementById("calendarSidebarYear");
        const listEl = document.getElementById("calendarMonthList");
        const focusEl = document.getElementById("calendarFocusDate");
        if (yearEl) {
            yearEl.textContent = String(calendarCurrentDate.getFullYear());
        }
        if (focusEl) {
            focusEl.textContent = calendarCurrentDate.toLocaleDateString("en-US", {
                month: "short",
                day: "numeric",
                weekday: "short",
            });
        }
        if (!listEl) {
            return;
        }
        const months = [
            "January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December",
        ];
        const active = calendarCurrentDate.getMonth();
        listEl.innerHTML = months
            .map((name, index) => {
                const activeClass = index === active ? " is-active" : "";
                return `<button type="button" class="calendar-month-item${activeClass}" onclick="calendarJumpToMonth(${index})">${name}</button>`;
            })
            .join("");

        requestAnimationFrame(() => {
            syncCalendarSidebarWave();
            syncCalendarSidebarContrast();
            requestAnimationFrame(() => {
                syncCalendarSidebarWave();
                syncCalendarSidebarContrast();
            });
        });
    }

    function syncCalendarSidebarWave() {
        const sidebar = document.querySelector("#maintenance-calendar .calendar-sidebar");
        if (!sidebar) return;
        // Match the reference corner-wave proportions (~60% down the left edge)
        sidebar.style.setProperty("--calendar-wave-height", "62%");
    }

    function isElementOnWave(el) {
        if (!el) return false;

        const path = document.querySelector(
            "#maintenance-calendar .calendar-sidebar-wave path",
        );
        const svg = path?.ownerSVGElement;
        if (!path || !svg || typeof path.isPointInFill !== "function") {
            return false;
        }

        const ctm = path.getScreenCTM();
        if (!ctm) return false;
        const inverse = ctm.inverse();
        const rect = el.getBoundingClientRect();

        // Sample across the label; majority on blue wave → light text
        const samples = [
            [0.5, 0.5],
            [0.5, 0.2],
            [0.5, 0.8],
            [0.25, 0.5],
            [0.75, 0.5],
        ];

        let hits = 0;
        samples.forEach(([rx, ry]) => {
            const pt = svg.createSVGPoint();
            pt.x = rect.left + rect.width * rx;
            pt.y = rect.top + rect.height * ry;
            const local = pt.matrixTransform(inverse);
            if (path.isPointInFill(local)) hits += 1;
        });

        return hits >= 3;
    }

    function syncCalendarSidebarContrast() {
        const yearEl = document.getElementById("calendarSidebarYear");
        const listEl = document.getElementById("calendarMonthList");

        if (yearEl) {
            yearEl.classList.toggle("is-on-wave", isElementOnWave(yearEl));
        }

        if (!listEl) return;
        listEl.querySelectorAll(".calendar-month-item").forEach((item) => {
            item.classList.toggle("is-on-wave", isElementOnWave(item));
        });
    }

    function calendarSidebarYear(delta) {
        calendarCurrentDate = new Date(
            calendarCurrentDate.getFullYear() + delta,
            calendarCurrentDate.getMonth(),
            1,
        );
        closeEventPopover();
        renderCalendar();
    }
    function calendarJumpToMonth(monthIndex) {
        calendarCurrentView = "month";
        calendarCurrentDate = new Date(
            calendarCurrentDate.getFullYear(),
            monthIndex,
            1,
        );
        document.querySelectorAll(".calendar-view-button").forEach((item) => {
            item.classList.remove(
                "bg-white",
                "text-slate-900",
                "text-slate-950",
                "font-semibold",
                "font-black",
                "shadow-sm",
            );
            item.classList.add("text-slate-500");
            if (item.dataset.calendarView === "month") {
                item.classList.add(
                    "bg-white",
                    "text-slate-900",
                    "font-semibold",
                    "shadow-sm",
                );
                item.classList.remove("text-slate-500");
            }
        });
        closeEventPopover();
        renderCalendar();
    }
    function renderMonthCalendar() {

        const grid =
            document.getElementById(
                "calendarGrid",
            );

        const title =
            document.getElementById(
                "calendarCurrentTitle",
            );

        const header =
            document.getElementById(
                "calendarWeekdayHeader",
            );


        if (!grid || !title) {
            return;
        }


        // SHOW WEEKDAY HEADER
        header.classList.remove("hidden");


        // MONTH GRID
        grid.className =
            "grid min-h-0 flex-1 grid-cols-7 overflow-hidden rounded-b-2xl border border-slate-200 bg-white";


        // ALWAYS USE SIX CALENDAR ROWS
        grid.style.gridTemplateRows =
            "repeat(6, minmax(0, 1fr))";


        // CLEAR OLD CALENDAR
        grid.innerHTML = "";


        const year =
            calendarCurrentDate.getFullYear();


        const month =
            calendarCurrentDate.getMonth();


        // UPDATE CALENDAR TITLE
        title.textContent =
            calendarCurrentDate.toLocaleDateString(
                "en-US",
                {
                    month: "long",

                    year: "numeric",
                },
            );


        const firstDay =
            new Date(
                year,
                month,
                1,
            );


        // =========================================================
        // SUNDAY FIRST CALENDAR
        // =========================================================
        //
        // JavaScript already uses:
        //
        // Sunday    = 0
        // Monday    = 1
        // Tuesday   = 2
        // Wednesday = 3
        // Thursday  = 4
        // Friday    = 5
        // Saturday  = 6
        //
        // So no conversion is needed.
        //

        const startIndex =
            firstDay.getDay();


        const startDate =
            new Date(
                year,
                month,
                1 - startIndex,
            );


        // CREATE 42 CALENDAR CELLS
        for (
            let index = 0;
            index < 42;
            index++
        ) {

            const date =
                new Date(startDate);


            date.setDate(
                startDate.getDate() +
                index,
            );


            grid.appendChild(
                createCalendarDay(
                    date,

                    month,
                ),
            );

        }


        if (window.lucide) {
            lucide.createIcons();
        }

    }
    function renderWeekCalendar() {
        const grid = document.getElementById("calendarGrid"),
            title = document.getElementById("calendarCurrentTitle"),
            header = document.getElementById("calendarWeekdayHeader");
        if (!grid || !title) return;
        header.classList.remove("hidden");
        grid.className = "grid min-h-[720px] grid-cols-7 overflow-hidden rounded-b-2xl border border-slate-200 bg-white";
        grid.style.gridTemplateRows = "1fr";
        grid.innerHTML = "";
        const start = startOfWeek(calendarCurrentDate),
            end = new Date(start);
        end.setDate(start.getDate() + 6);
        title.textContent = `${formatShortDate(start)} - ${formatShortDate(end)}`;
        for (let i = 0; i < 7; i++) {
            const d = new Date(start);
            d.setDate(start.getDate() + i);
            grid.appendChild(createCalendarDay(d, d.getMonth(), 8));
        }
        if (window.lucide) lucide.createIcons();
    }
    function renderYearCalendar() {
        const grid = document.getElementById("calendarGrid"),
            title = document.getElementById("calendarCurrentTitle"),
            header = document.getElementById("calendarWeekdayHeader");
        if (!grid || !title) return;
        header.classList.add("hidden");
        grid.className =
            "grid min-h-[720px] grid-cols-1 gap-4 p-1 md:grid-cols-2 xl:grid-cols-3";
        grid.style.gridTemplateRows = "";
        grid.innerHTML = "";
        const year = calendarCurrentDate.getFullYear();
        title.textContent = String(year);
        for (let month = 0; month < 12; month++)
            grid.appendChild(createYearMonthCard(year, month));
        if (window.lucide) lucide.createIcons();
    }
    function createCalendarDay(
        date,
        currentMonth,
        max = 3,
    ) {

        const day =
            document.createElement(
                "div",
            );


        day.className =
            "calendar-day";


        // =====================================================
        // OUTSIDE CURRENT MONTH
        // =====================================================

        if (
            date.getMonth() !==
            currentMonth
        ) {

            day.classList.add(
                "calendar-day-outside",
            );

        }


        // =====================================================
        // SATURDAY OR SUNDAY
        // =====================================================

        if (
            date.getDay() === 0 ||
            date.getDay() === 6
        ) {

            day.classList.add(
                "calendar-day-weekend",
            );

        }


        // =====================================================
        // TODAY
        // =====================================================

        if (
            isSameCalendarDate(
                date,

                new Date(),
            )
        ) {

            day.classList.add(
                "calendar-today",
            );

        }


        // =====================================================
        // DATE NUMBER
        // =====================================================

        const number =
            document.createElement(
                "div",
            );


        number.className =
            "calendar-day-number";


        number.textContent =
            date.getDate();


        day.appendChild(
            number,
        );


        // =====================================================
        // EVENTS
        // =====================================================

        const events =
            document.createElement(
                "div",
            );


        events.className =
            "calendar-events";


        const schedules =
            getSchedulesForDate(
                date,
            );

        if (schedules.length > 0) {
            day.classList.add("has-events");
        }

        if (date.getMonth() === currentMonth) {
            if (schedules.some((item) => String(item.status).toLowerCase() === "overdue")) {
                day.classList.add("has-overdue");
            } else if (schedules.some((item) => String(item.status).toLowerCase() === "completed")) {
                day.classList.add("has-completed");
            }
        }


        schedules
            .slice(
                0,

                max,
            )
            .forEach(
                (schedule) => {

                    events.appendChild(
                        createCalendarEvent(
                            schedule,
                        ),
                    );

                },
            );


        // =====================================================
        // MORE EVENTS
        // =====================================================

        if (
            schedules.length >
            max
        ) {

            const more =
                document.createElement(
                    "button",
                );


            more.type =
                "button";


            more.className =
                "mt-1 text-left text-[10px] font-semibold text-slate-400";


            more.textContent =
                `+${schedules.length - max} more`;


            events.appendChild(
                more,
            );

        }


        day.appendChild(
            events,
        );


        return day;

    }
    function createCalendarEvent(schedule) {
        const button = document.createElement("button");
        button.type = "button";
        button.className = `calendar-event ${getCalendarEventClass(schedule.status)}`;
        button.innerHTML = `<div class="calendar-event-title">${escapeCalendarHTML(schedule.title)}</div><div class="calendar-event-equipment">${escapeCalendarHTML(schedule.equipment)}</div>`;
        button.addEventListener("click", (event) => {
            event.stopPropagation();
            openEventPopover(schedule, button);
        });
        return button;
    }
    function createYearMonthCard(year, month) {
        const card = document.createElement("div");
        card.className = "calendar-year-card rounded-3xl";
        const title = document.createElement("div");
        title.className = "mb-3 flex items-center justify-between";
        title.innerHTML = `<h4 class="font-black text-slate-900">${new Date(year, month, 1).toLocaleDateString("en-US", { month: "long" })}</h4><span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-black text-slate-500">${getSchedulesForMonth(year, month).length} events</span>`;
        card.appendChild(title);
        const mini = document.createElement("div");
        mini.className = "grid grid-cols-7 gap-1";
        const first = new Date(year, month, 1),
            days = new Date(year, month + 1, 0).getDate();
        for (let blank = 0; blank < first.getDay(); blank++)
            mini.appendChild(document.createElement("div"));
        for (let n = 1; n <= days; n++) {
            const date = new Date(year, month, n),
                schedules = getSchedulesForDate(date),
                cell = document.createElement("button");
            cell.type = "button";
            cell.className = `calendar-year-day ${schedules.length ? "has-events" : ""}`;
            cell.textContent = n;
            cell.addEventListener("click", () => {
                calendarCurrentDate = date;
                document.querySelector('[data-calendar-view="month"]')?.click();
            });
            mini.appendChild(cell);
        }
        card.appendChild(mini);
        return card;
    }
    function getSchedulesForDate(date) {
        const search =
                document
                    .getElementById("calendarSearchInput")
                    ?.value.trim()
                    .toLowerCase() ?? "",
            status =
                document.getElementById("calendarStatusFilter")?.value ?? "all";
        return calendarSchedules.filter((schedule) => {
            const scheduleDate = parseCalendarDate(schedule.date),
                text =
                    `${schedule.title} ${schedule.equipment} ${schedule.room}`.toLowerCase();
            return (
                isSameCalendarDate(date, scheduleDate) &&
                (!search || text.includes(search)) &&
                (status === "all" || schedule.status.toLowerCase() === status)
            );
        });
    }
    function getSchedulesForMonth(year, month) {
        return calendarSchedules.filter((schedule) => {
            const date = parseCalendarDate(schedule.date);
            return date.getFullYear() === year && date.getMonth() === month;
        });
    }
    function calendarPrevious() {
        if (calendarCurrentView === "week")
            calendarCurrentDate.setDate(calendarCurrentDate.getDate() - 7);
        else if (calendarCurrentView === "year")
            calendarCurrentDate = new Date(
                calendarCurrentDate.getFullYear() - 1,
                0,
                1,
            );
        else
            calendarCurrentDate = new Date(
                calendarCurrentDate.getFullYear(),
                calendarCurrentDate.getMonth() - 1,
                1,
            );
        closeEventPopover();
        renderCalendar();
    }
    function calendarNext() {
        if (calendarCurrentView === "week")
            calendarCurrentDate.setDate(calendarCurrentDate.getDate() + 7);
        else if (calendarCurrentView === "year")
            calendarCurrentDate = new Date(
                calendarCurrentDate.getFullYear() + 1,
                0,
                1,
            );
        else
            calendarCurrentDate = new Date(
                calendarCurrentDate.getFullYear(),
                calendarCurrentDate.getMonth() + 1,
                1,
            );
        closeEventPopover();
        renderCalendar();
    }
    function calendarGoToday() {
        calendarCurrentDate = new Date();
        closeEventPopover();
        renderCalendar();
    }
    function openEventPopover(schedule, eventElement) {
        calendarSelectedSchedule = schedule;
        const popover = document.getElementById("calendarEventPopover");
        document.getElementById("popoverTitle").textContent = schedule.title;
        document.getElementById("popoverEquipment").textContent =
            schedule.equipment;
        document.getElementById("popoverRoom").textContent = schedule.room;
        document.getElementById("popoverFrequency").textContent =
            schedule.frequency;
        document.getElementById("popoverDate").textContent = parseCalendarDate(
            schedule.date,
        ).toLocaleDateString("en-US", {
            month: "long",
            day: "numeric",
            year: "numeric",
        });
        const status = document.getElementById("popoverStatus");
        status.textContent = schedule.status;
        status.className = `mb-2 inline-flex rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide ${getCalendarPopoverStatusClass(schedule.status)}`;
        popover.classList.remove("hidden");
        const rect = eventElement.getBoundingClientRect(),
            pop = popover.getBoundingClientRect();
        let left = rect.right + 10,
            top = rect.top;
        if (left + pop.width > window.innerWidth - 12)
            left = rect.left - pop.width - 10;
        if (top + pop.height > window.innerHeight - 12)
            top = window.innerHeight - pop.height - 12;
        popover.style.left = `${Math.max(12, left)}px`;
        popover.style.top = `${Math.max(12, top)}px`;
        if (window.lucide) lucide.createIcons();
    }
    function closeEventPopover() {
        document.getElementById("calendarEventPopover")?.classList.add("hidden");
    }
    function openScheduleModal() {
        const modal = document.getElementById("scheduleModal");
        if (modal?._x_dataStack?.[0]?.reset) {
            modal._x_dataStack[0].reset();
        }
        showModal("scheduleModal");
        if (window.lucide) lucide.createIcons();
    }
    function scheduleEquipmentCart(catalog) {
        return {
            catalog: Array.isArray(catalog) ? catalog : [],
            query: '',
            open: false,
            highlight: 0,
            cart: [],
            pickerError: '',
            cartError: '',
            get filtered() {
                const q = String(this.query || '').trim().toLowerCase();
                const selectedIds = new Set(this.cart.map((line) => line.id));
                return this.catalog
                    .filter((item) => !selectedIds.has(item.id))
                    .filter((item) => {
                        if (!q) return true;
                        return [item.name, item.room, item.qr, item.assetTag, item.serialNumber]
                            .join(' ')
                            .toLowerCase()
                            .includes(q);
                    })
                    .slice(0, 40);
            },
            meta(item) {
                const bits = [];
                if (item.room) bits.push(item.room);
                if (item.assetTag) bits.push('Tag: ' + item.assetTag);
                if (item.serialNumber) bits.push('Serial: ' + item.serialNumber);
                if (item.qr) bits.push(item.qr);
                return bits.join(' · ') || 'QR-ready equipment';
            },
            reset() {
                this.query = '';
                this.open = false;
                this.highlight = 0;
                this.cart = [];
                this.pickerError = '';
                this.cartError = '';
            },
            move(delta) {
                if (!this.filtered.length) return;
                this.highlight = (this.highlight + delta + this.filtered.length) % this.filtered.length;
            },
            addHighlighted() {
                if (!this.filtered.length) return;
                this.addItem(this.filtered[this.highlight] || this.filtered[0]);
            },
            addItem(item) {
                this.pickerError = '';
                if (!item) return;
                if (this.cart.some((line) => line.id === item.id)) {
                    this.pickerError = 'That equipment is already in the list.';
                    return;
                }
                this.cart.push({ ...item });
                this.query = '';
                this.open = false;
                this.highlight = 0;
                this.cartError = '';
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            },
            removeLine(index) {
                this.cart.splice(index, 1);
                this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
            },
            prepareSubmit(event) {
                this.cartError = '';
                if (!this.cart.length) {
                    event.preventDefault();
                    this.cartError = 'Add at least one equipment item.';
                    return;
                }
                const nextDate = document.getElementById('scheduleNextDate')?.value;
                if (!nextDate) {
                    event.preventDefault();
                    this.cartError = 'Please choose a next maintenance date.';
                }
            },
        };
    }
    window.scheduleEquipmentCart = scheduleEquipmentCart;
    document.addEventListener("alpine:init", () => {
        if (window.Alpine?.data) {
            window.Alpine.data("scheduleEquipmentCart", scheduleEquipmentCart);
        }
    });
    function scheduleNextDatePicker() {
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const months = [
            "January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December",
        ];
        const toIso = (date) => {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, "0");
            const d = String(date.getDate()).padStart(2, "0");
            return `${y}-${m}-${d}`;
        };
        const fromIso = (iso) => {
            const [y, m, d] = String(iso).split("-").map(Number);
            return new Date(y, m - 1, d);
        };

        return {
            value: toIso(today),
            viewYear: today.getFullYear(),
            viewMonth: today.getMonth(),
            todayIso: toIso(today),
            weekdays: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"],
            get display() {
                if (!this.value) {
                    return "Select a date";
                }
                return fromIso(this.value).toLocaleDateString("en-US", {
                    weekday: "short",
                    month: "short",
                    day: "numeric",
                    year: "numeric",
                });
            },
            get monthLabel() {
                return `${months[this.viewMonth]} ${this.viewYear}`;
            },
            get days() {
                const first = new Date(this.viewYear, this.viewMonth, 1);
                const start = first.getDay();
                const daysInMonth = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
                const prevDays = new Date(this.viewYear, this.viewMonth, 0).getDate();
                const cells = [];

                for (let i = 0; i < 42; i += 1) {
                    let year = this.viewYear;
                    let month = this.viewMonth;
                    let date;
                    let outside = false;

                    if (i < start) {
                        date = prevDays - start + i + 1;
                        month -= 1;
                        if (month < 0) {
                            month = 11;
                            year -= 1;
                        }
                        outside = true;
                    } else if (i >= start + daysInMonth) {
                        date = i - start - daysInMonth + 1;
                        month += 1;
                        if (month > 11) {
                            month = 0;
                            year += 1;
                        }
                        outside = true;
                    } else {
                        date = i - start + 1;
                    }

                    const iso = toIso(new Date(year, month, date));
                    cells.push({
                        d: date,
                        iso,
                        outside,
                        isToday: iso === this.todayIso,
                        selected: iso === this.value,
                    });
                }

                return cells;
            },
            prevMonth() {
                if (this.viewMonth === 0) {
                    this.viewMonth = 11;
                    this.viewYear -= 1;
                    return;
                }
                this.viewMonth -= 1;
            },
            nextMonth() {
                if (this.viewMonth === 11) {
                    this.viewMonth = 0;
                    this.viewYear += 1;
                    return;
                }
                this.viewMonth += 1;
            },
            pick(day) {
                this.value = day.iso;
                if (!day.outside) {
                    return;
                }
                const selected = fromIso(day.iso);
                this.viewYear = selected.getFullYear();
                this.viewMonth = selected.getMonth();
            },
            goToday() {
                this.value = this.todayIso;
                this.viewYear = today.getFullYear();
                this.viewMonth = today.getMonth();
            },
            clearDate() {
                this.value = "";
            },
        };
    }
    window.scheduleNextDatePicker = scheduleNextDatePicker;
    document.addEventListener("alpine:init", () => {
        if (window.Alpine?.data) {
            window.Alpine.data("scheduleNextDatePicker", scheduleNextDatePicker);
        }
    });
    function closeScheduleModal() {
        const modal = document.getElementById("scheduleModal");
        hideModal("scheduleModal");
        if (modal?._x_dataStack?.[0]?.reset) {
            modal._x_dataStack[0].reset();
        }
    }
    function closeViewModal() {
        const modal = document.getElementById("viewModal");
        const panel = document.getElementById("viewModalPanel");

        panel?.classList.add("translate-x-full");
        modal?.setAttribute("aria-hidden", "true");
        document.body.style.overflow = "";

        setTimeout(() => {
            if (panel?.classList.contains("translate-x-full")) {
                modal?.classList.add("hidden");
            }
        }, 300);
    }
    let scheduleDrawerCurrentAssetTag = "";
    let scheduleDrawerCurrentSchedule = null;
    function switchScheduleDrawerTab(tab) {
        const equipmentTab = document.getElementById("scheduleDrawer_tab_equipment");
        const scheduleTab = document.getElementById("scheduleDrawer_tab_schedule");
        const equipmentPanel = document.getElementById("scheduleDrawerPanelEquipment");
        const schedulePanel = document.getElementById("scheduleDrawerPanelSchedule");
        const isEquipment = tab === "equipment";

        equipmentTab?.classList.toggle("border-[#0025cc]", isEquipment);
        equipmentTab?.classList.toggle("text-[#0025cc]", isEquipment);
        equipmentTab?.classList.toggle("font-semibold", isEquipment);
        equipmentTab?.classList.toggle("border-transparent", !isEquipment);
        equipmentTab?.classList.toggle("text-slate-500", !isEquipment);
        equipmentTab?.classList.toggle("font-medium", !isEquipment);
        equipmentTab?.setAttribute("aria-selected", isEquipment ? "true" : "false");

        scheduleTab?.classList.toggle("border-[#0025cc]", !isEquipment);
        scheduleTab?.classList.toggle("text-[#0025cc]", !isEquipment);
        scheduleTab?.classList.toggle("font-semibold", !isEquipment);
        scheduleTab?.classList.toggle("border-transparent", isEquipment);
        scheduleTab?.classList.toggle("text-slate-500", isEquipment);
        scheduleTab?.classList.toggle("font-medium", isEquipment);
        scheduleTab?.setAttribute("aria-selected", !isEquipment ? "true" : "false");

        equipmentPanel?.classList.toggle("hidden", !isEquipment);
        schedulePanel?.classList.toggle("hidden", isEquipment);
    }
    function scheduleDrawerDisplayValue(value) {
        const text = String(value ?? "").trim();
        return text !== "" ? text : "—";
    }
    function scheduleEquipmentInitials(name) {
        const parts = String(name || "").trim().split(/\s+/).filter(Boolean);
        if (!parts.length) {
            return "?";
        }
        if (parts.length === 1) {
            return parts[0].slice(0, 2).toUpperCase();
        }
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    function scheduleDrawerStatusBadgeClass(status) {
        switch (String(status || "").toLowerCase()) {
            case "completed":
                return "bg-emerald-50 text-emerald-700";
            case "overdue":
                return "bg-rose-50 text-rose-700";
            default:
                return "bg-sky-50 text-sky-700";
        }
    }
    function scheduleDrawerKvCard(title, rows) {
        const rowHtml = rows
            .map(
                (row) => `
                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3.5 last:border-b-0">
                        <span class="text-sm text-slate-500">${row.label}</span>
                        <span class="text-right text-sm font-medium text-slate-800">${row.value}</span>
                    </div>
                `,
            )
            .join("");

        return `
            <div>
                <h4 class="text-sm font-semibold text-slate-900">${title}</h4>
                <div class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white">
                    ${rowHtml}
                </div>
            </div>
        `;
    }
    function copyScheduleAssetTag() {
        const tag = scheduleDrawerCurrentAssetTag;
        if (!tag) {
            return;
        }
        navigator.clipboard?.writeText(tag).catch(() => {});
    }
    function openViewDrawer() {
        const modal = document.getElementById("viewModal");
        const panel = document.getElementById("viewModalPanel");

        modal?.classList.remove("hidden");
        modal?.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden";

        requestAnimationFrame(() => {
            panel?.classList.remove("translate-x-full");
        });

        if (window.lucide) {
            lucide.createIcons();
        }
    }
    function closeCompleteModal() {
        hideModal("completeModal");
    }
    function closeRescheduleModal() {
        hideModal("rescheduleModal");
    }
    function closeDeleteModal() {
        hideModal("deleteModal");
    }
    // =========================================================
    // FIND SCHEDULE THEN OPEN VIEW MODAL
    // =========================================================

    function getScheduleQr(id) {
        return (
            scheduleQrById[id] ||
            scheduleQrById[String(id)] || { qr_code: "", qr_image: "" }
        );
    }
    function fillScheduleQr(prefix, id) {
        const code = document.getElementById(`${prefix}QrCode`);
        if (code) {
            code.textContent = getScheduleQr(id).qr_code || "";
        }
    }
    function viewScheduleById(scheduleId) {
        // FIND THE SCHEDULE FROM THE CALENDAR DATA
        const schedule = calendarSchedules.find(
            (item) => Number(item.id) === Number(scheduleId),
        );

        // STOP IF SCHEDULE DOES NOT EXIST
        if (!schedule) {
            console.error("Schedule not found:", scheduleId);
            return;
        }

        // OPEN THE VIEW MODAL
        viewSchedule(schedule);
}
    function viewSchedule(schedule) {
        scheduleDrawerCurrentSchedule = schedule;
        scheduleDrawerCurrentAssetTag = String(schedule.asset_tag || "").trim();

        const equipment = schedule.equipment || "Unassigned equipment";
        const room = schedule.room || "No room assigned";
        const title = schedule.title || "—";
        const frequency = schedule.frequency || "—";
        const nextDate = schedule.date || "—";
        const lastDate = schedule.last_date || "Never";
        const status = schedule.status || "—";
        const description = schedule.description || "No description provided";
        const assetTag = scheduleDrawerDisplayValue(schedule.asset_tag);
        const serialNumber = scheduleDrawerDisplayValue(schedule.serial_number);
        const category = scheduleDrawerDisplayValue(schedule.category);
        const brand = scheduleDrawerDisplayValue(schedule.brand);
        const model = scheduleDrawerDisplayValue(schedule.model);
        const inventoryStatus = scheduleDrawerDisplayValue(schedule.inventory_status);
        const conditionStatus = scheduleDrawerDisplayValue(schedule.condition_status);
        const qr = getScheduleQr(schedule.id);
        const qrCode = schedule.qr_code || qr.qr_code || "";
        const qrImage = schedule.qr_image || qr.qr_image || "";

        document.getElementById("scheduleDrawer_subtitle").textContent =
            `Review ${equipment} maintenance schedule.`;
        document.getElementById("scheduleDrawer_profile_name").textContent = equipment;
        document.getElementById("scheduleDrawerAvatar").textContent =
            scheduleEquipmentInitials(equipment);

        const statusBadge = document.getElementById("scheduleDrawer_status_badge");
        if (statusBadge) {
            statusBadge.textContent = status;
            statusBadge.className =
                `inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ${scheduleDrawerStatusBadgeClass(status)}`;
        }

        const categoryBadge = document.getElementById("scheduleDrawer_category_badge");
        if (categoryBadge) {
            categoryBadge.textContent = category;
            categoryBadge.classList.toggle("hidden", category === "—");
        }

        const inventoryBadge = document.getElementById("scheduleDrawer_inventory_badge");
        if (inventoryBadge) {
            inventoryBadge.textContent = inventoryStatus;
            inventoryBadge.classList.toggle("hidden", inventoryStatus === "—");
        }

        const frequencyBadge = document.getElementById("scheduleDrawer_frequency_badge");
        if (frequencyBadge) {
            frequencyBadge.textContent = frequency;
            frequencyBadge.classList.toggle("hidden", frequency === "—");
        }

        document.getElementById("scheduleDrawer_meta_tag").textContent = assetTag;
        document.getElementById("scheduleDrawer_meta_serial").textContent = serialNumber;
        document.getElementById("scheduleDrawer_meta_room").textContent = room;
        document.getElementById("scheduleDrawer_meta_qr").textContent =
            qrCode || "No QR code";

        const copyTagButton = document.getElementById("scheduleDrawer_copy_tag");
        if (copyTagButton) {
            copyTagButton.classList.toggle("hidden", !scheduleDrawerCurrentAssetTag);
        }

        const viewQrButton = document.getElementById("scheduleDrawer_view_qr");
        if (viewQrButton) {
            if (qrImage) {
                viewQrButton.classList.remove("hidden");
                viewQrButton.onclick = () =>
                    openScheduleQrModal(qrImage, qrCode, equipment);
            } else {
                viewQrButton.classList.add("hidden");
                viewQrButton.onclick = null;
            }
        }

        document.getElementById("scheduleDrawerPanelEquipment").innerHTML =
            scheduleDrawerKvCard("Equipment information", [
                { label: "Equipment", value: escapeCalendarHTML(equipment) },
                { label: "Asset tag", value: escapeCalendarHTML(assetTag) },
                { label: "Serial number", value: escapeCalendarHTML(serialNumber) },
                { label: "Category", value: escapeCalendarHTML(category) },
                { label: "Brand", value: escapeCalendarHTML(brand) },
                { label: "Model", value: escapeCalendarHTML(model) },
                { label: "Room", value: escapeCalendarHTML(room) },
                { label: "Inventory status", value: escapeCalendarHTML(inventoryStatus) },
                { label: "Condition", value: escapeCalendarHTML(conditionStatus) },
            ]);

        document.getElementById("scheduleDrawerPanelSchedule").innerHTML = `
            ${scheduleDrawerKvCard("Schedule information", [
                { label: "Title", value: escapeCalendarHTML(title) },
                { label: "Frequency", value: escapeCalendarHTML(frequency) },
                {
                    label: "Next date",
                    value: `<span class="inline-flex items-center gap-1.5"><i data-lucide="calendar" class="h-3.5 w-3.5 text-slate-400"></i>${escapeCalendarHTML(nextDate)}</span>`,
                },
                {
                    label: "Last date",
                    value: `<span class="inline-flex items-center gap-1.5"><i data-lucide="calendar-clock" class="h-3.5 w-3.5 text-slate-400"></i>${escapeCalendarHTML(lastDate)}</span>`,
                },
                {
                    label: "Status",
                    value: `<span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ${scheduleDrawerStatusBadgeClass(status)}">${escapeCalendarHTML(status)}</span>`,
                },
            ])}
            <div>
                <h4 class="text-sm font-semibold text-slate-900">Description</h4>
                <div class="mt-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5 text-sm leading-6 text-slate-700">
                    ${escapeCalendarHTML(description)}
                </div>
            </div>
            ${
                qrImage
                    ? `
                        <div>
                            <h4 class="text-sm font-semibold text-slate-900">Equipment QR code</h4>
                            <button
                                type="button"
                                onclick="openScheduleQrModal(${JSON.stringify(qrImage)}, ${JSON.stringify(qrCode)}, ${JSON.stringify(equipment)})"
                                class="mt-3 flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 text-left transition hover:border-[#0025cc]/30 hover:bg-[#0025cc]/[0.03]"
                            >
                                <img src="${escapeCalendarHTML(qrImage)}" alt="Equipment QR code" class="h-16 w-16 rounded-lg border border-slate-200 bg-white object-contain p-1">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">QR code</p>
                                    <p class="truncate font-mono text-sm font-semibold text-slate-900">${escapeCalendarHTML(qrCode)}</p>
                                </div>
                            </button>
                        </div>
                    `
                    : ""
            }
        `;

        const primaryAction = document.getElementById("scheduleDrawer_primary_action");
        if (primaryAction) {
            if (String(status).toLowerCase() === "completed") {
                primaryAction.innerHTML =
                    '<i data-lucide="x" class="h-4 w-4"></i> Close';
                primaryAction.onclick = closeViewModal;
            } else {
                primaryAction.innerHTML =
                    '<i data-lucide="circle-check" class="h-4 w-4"></i> Mark complete';
                primaryAction.onclick = () => {
                    closeViewModal();
                    openCompleteModal(
                        schedule.id,
                        equipment,
                        schedule.equipment_identifier || "No asset tag or serial",
                        schedule.room || "No room assigned",
                    );
                };
            }
        }

        switchScheduleDrawerTab("equipment");
        openViewDrawer();
    }
    function openCompleteModal(id, name, identifier = "", room = "") {
        const equipmentName = name || "Unassigned equipment";
        const equipmentIdentifier = identifier || "No asset tag or serial";
        const equipmentRoom = room || "No room assigned";
        const qr = getScheduleQr(id);
        const qrCode = qr.qr_code || "";

        document.getElementById("completeScheduleId").value = id;
        document.getElementById("completeEquipmentName").textContent = equipmentName;
        document.getElementById("completeEquipmentAvatar").textContent =
            scheduleEquipmentInitials(equipmentName);
        document.getElementById("completeEquipmentIdentifier").textContent =
            equipmentIdentifier;

        const roomEl = document.getElementById("completeEquipmentRoom");
        if (roomEl) {
            roomEl.innerHTML = `<i data-lucide="map-pin" class="h-3 w-3 shrink-0"></i><span class="truncate">${escapeCalendarHTML(equipmentRoom)}</span>`;
        }

        const qrEl = document.getElementById("completeEquipmentQr");
        if (qrEl) {
            if (qrCode) {
                qrEl.textContent = qrCode;
                qrEl.classList.remove("hidden");
            } else {
                qrEl.textContent = "";
                qrEl.classList.add("hidden");
            }
        }

        const proofInput = document.getElementById("completeProofImage");
        if (proofInput) {
            proofInput.value = "";
            proofInput.dispatchEvent(new Event("change"));
        }
        showModal("completeModal");
        if (window.lucide) {
            lucide.createIcons();
        }
    }
    function completeProofUploader() {
        const formatSize = (bytes) => {
            if (!bytes) {
                return "";
            }
            if (bytes < 1024) {
                return `${bytes} B`;
            }
            if (bytes < 1024 * 1024) {
                return `${Math.round(bytes / 1024)} KB`;
            }
            return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
        };

        return {
            preview: "",
            fileName: "",
            fileMeta: "",
            dragging: false,
            init() {
                this.$nextTick(() => {
                    if (window.lucide) {
                        lucide.createIcons();
                    }
                });
            },
            onFile(file) {
                this.dragging = false;
                if (this.preview) {
                    URL.revokeObjectURL(this.preview);
                }
                if (!file || !file.type.startsWith("image/")) {
                    this.preview = "";
                    this.fileName = "";
                    this.fileMeta = "";
                    this.$nextTick(() => {
                        if (window.lucide) {
                            lucide.createIcons();
                        }
                    });
                    return;
                }
                this.fileName = file.name;
                this.fileMeta = formatSize(file.size);
                this.preview = URL.createObjectURL(file);
                this.$nextTick(() => {
                    if (window.lucide) {
                        lucide.createIcons();
                    }
                });
            },
            onDrop(event) {
                this.dragging = false;
                const file = event.dataTransfer?.files?.[0];
                if (!file) {
                    return;
                }
                const input = document.getElementById("completeProofImage");
                if (input) {
                    const transfer = new DataTransfer();
                    transfer.items.add(file);
                    input.files = transfer.files;
                }
                this.onFile(file);
            },
            clearFile() {
                const input = document.getElementById("completeProofImage");
                if (input) {
                    input.value = "";
                }
                this.onFile(null);
                this.$nextTick(() => {
                    if (window.lucide) {
                        lucide.createIcons();
                    }
                });
            },
        };
    }
    window.completeProofUploader = completeProofUploader;
    function openRescheduleModal(id, name) {
        document.getElementById("rescheduleScheduleId").value = id;
        document.getElementById("rescheduleEquipmentName").innerText = name;
        fillScheduleQr("reschedule", id);
        showModal("rescheduleModal");
    }
    function openDeleteModal(id, title, equipmentName, identifier) {
        document.getElementById("deleteScheduleId").value = id;
        document.getElementById("deleteScheduleEquipmentName").textContent =
            equipmentName || "Unassigned equipment";
        document.getElementById("deleteScheduleIdentifier").textContent =
            identifier || "No asset tag or serial";
        document.getElementById("deleteScheduleTitle").textContent =
            `Are you sure you want to delete "${title}"?`;
        showModal("deleteModal");
    }
    function viewSelectedSchedule() {
        if (!calendarSelectedSchedule) return;
        viewSchedule(calendarSelectedSchedule);
        closeEventPopover();
    }
    function completeSelectedSchedule() {
        if (!calendarSelectedSchedule) return;
        openCompleteModal(
            calendarSelectedSchedule.id,
            calendarSelectedSchedule.equipment,
            calendarSelectedSchedule.equipment_identifier || "No asset tag or serial",
            calendarSelectedSchedule.room || "No room assigned",
        );
        closeEventPopover();
    }
    function showModal(id) {
        const modal = document.getElementById(id);
        modal?.classList.remove("hidden");
        modal?.classList.add("flex");
        if (window.lucide) lucide.createIcons();
    }
    function hideModal(id) {
        const modal = document.getElementById(id);
        modal?.classList.add("hidden");
        modal?.classList.remove("flex");
    }
    function openScheduleQrModal(image, code, name) {
        document.getElementById("scheduleQrPreviewImage").src = image || "";
        document.getElementById("scheduleQrPreviewCode").textContent = code || "";
        document.getElementById("scheduleQrEquipmentName").textContent = name || "";
        showModal("scheduleQrModal");
    }
    function closeScheduleQrModal() {
        hideModal("scheduleQrModal");
    }
    function getCalendarEventClass(status) {
        switch (status.toLowerCase()) {
            case "completed":
                return "calendar-event-completed";
            case "overdue":
                return "calendar-event-overdue";
            default:
                return "calendar-event-active";
        }
    }
    function getCalendarPopoverStatusClass(status) {
        switch (status.toLowerCase()) {
            case "completed":
                return "bg-emerald-100 text-emerald-700";
            case "overdue":
                return "bg-red-100 text-red-700";
            default:
                return "bg-[#6b7cff]/15 text-[#4f63f0]";
        }
    }
    function parseCalendarDate(dateString) {
        const [y, m, d] = String(dateString).split("-").map(Number);
        return new Date(y, m - 1, d);
    }
    function startOfWeek(date) {
        const start = new Date(date);
        start.setDate(date.getDate() - date.getDay());
        return start;
    }
    function formatShortDate(date) {
        return date.toLocaleDateString("en-US", {
            month: "short",
            day: "numeric",
            year: "numeric",
        });
    }
    function isSameCalendarDate(a, b) {
        return (
            a.getFullYear() === b.getFullYear() &&
            a.getMonth() === b.getMonth() &&
            a.getDate() === b.getDate()
        );
    }
    function escapeCalendarHTML(value) {
        return String(value ?? "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }
    document.addEventListener("click", (event) => {
        const popover = document.getElementById("calendarEventPopover");
        if (!popover) return;
        if (
            !popover.contains(event.target) &&
            !event.target.closest(".calendar-event")
        )
            closeEventPopover();
    });
    document.addEventListener("keydown", (event) => {
        if (event.key !== "Escape") {
            return;
        }
        const modal = document.getElementById("viewModal");
        if (modal && !modal.classList.contains("hidden")) {
            closeViewModal();
        }
    });
    window.addEventListener("resize", closeEventPopover);
</script>
