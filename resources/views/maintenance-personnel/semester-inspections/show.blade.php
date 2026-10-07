@extends('layouts.maintenance-layout')

@section('title', $campaign->campaign_title)

@section('content')
@php
    $due = \Carbon\Carbon::parse($campaign->campaign_due_date)->startOfDay();
    $today = now()->startOfDay();
    $days = (int) $today->diffInDays($due, false);
    $isOpen = ! in_array($campaign->campaign_status, ['Completed', 'Cancelled'], true);
    $isSchoolAdmin = \App\Support\AdminPortal::isSchoolAdmin();
    $canManage = ! $isSchoolAdmin && \App\Support\RoleAccess::hasRole(\App\Support\RoleAccess::MAINTENANCE);
    $conditionTone = [
        'OK' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        'Malfunctioning' => 'bg-amber-50 text-amber-700 ring-amber-100',
        'Defective' => 'bg-orange-50 text-orange-700 ring-orange-100',
        'Destroyed' => 'bg-rose-50 text-rose-700 ring-rose-100',
    ];
    $semester = trim((string) ($campaign->campaign_semester ?? ''));
    $titleLower = mb_strtolower((string) ($campaign->campaign_title ?? ''));
    $showSemester = $semester !== '' && ! str_contains($titleLower, mb_strtolower($semester));
    $metaParts = array_values(array_filter([
        $showSemester ? $semester : null,
        $campaign->campaign_academic_year ?: null,
        $scopeLabel ?: null,
        'Due '.$due->format('M j, Y'),
    ]));
@endphp

<div
    class="space-y-6"
    x-data="semesterInspectPage()"
>
    <header class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-slate-200">
                    {{ $campaign->campaign_status }}
                </span>
            </div>
            <p class="mt-2 text-sm text-slate-500">
                {{ implode(' · ', $metaParts) }}
                @if ($isOpen)
                    @if ($days < 0)
                        · <span class="font-medium text-rose-600">{{ abs($days) }}d overdue</span>
                    @elseif ($days === 0)
                        · <span class="font-medium text-amber-600">Due today</span>
                    @elseif ($days <= 7)
                        · <span class="font-medium text-amber-600">In {{ $days }}d</span>
                    @endif
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a
                href="{{ url($isSchoolAdmin ? '/school-admin/semester-inspections' : '/maintenance/semester-inspections') }}"
                class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-950"
            >
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Back
            </a>
            @if ($canManage && $campaign->campaign_status === 'Draft')
                <form action="{{ url('/maintenance/semester-inspections/'.$campaign->campaign_id.'/activate') }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white">Activate</button>
                </form>
            @endif
            @if ($canManage && $isOpen)
                <form action="{{ url('/maintenance/semester-inspections/'.$campaign->campaign_id.'/complete') }}" method="POST"
                    onsubmit="return confirm(@json($progress['pending'] > 0 ? 'Force-complete with '.$progress['pending'].' pending item(s)?' : 'Mark this campaign complete?'))">
                    @csrf
                    @if ($progress['pending'] > 0)
                        <input type="hidden" name="force" value="1">
                    @endif
                    <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-medium text-white transition hover:bg-[#001fad]">
                        Complete
                    </button>
                </form>
                <form action="{{ url('/maintenance/semester-inspections/'.$campaign->campaign_id.'/cancel') }}" method="POST"
                    onsubmit="return confirm('Cancel this campaign?')">
                    @csrf
                    <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg px-4 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-800">
                        Cancel
                    </button>
                </form>
            @endif
        </div>
    </header>

    @include('layouts.partials.maintenance-stat-cards', [
        'cards' => [
            ['label' => 'Inspected', 'hint' => '', 'value' => number_format($progress['inspected'])],
            ['label' => 'Pending', 'hint' => '', 'value' => number_format($progress['pending'])],
            ['label' => 'Defects found', 'hint' => '', 'value' => number_format($progress['defects'])],
        ],
    ])


    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 p-4 sm:p-5">
            <div class="mb-3 h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-[#0025cc] transition-all" style="width: {{ $progress['percent'] }}%"></div>
            </div>
            @php
                $activeFilterClass = 'si-filter-active';
            @endphp
            {{-- Plain CSS so the admin grayscale theme doesn't mute the active-filter highlight. --}}
            <style>
                .si-filter-active {
                    background: #eef2ff !important;
                    color: #0025cc !important;
                    font-weight: 600;
                    box-shadow: inset 0 0 0 1.5px rgba(0, 37, 204, 0.55) !important;
                }
            </style>
            <form method="GET" class="flex flex-col gap-2 lg:flex-row lg:items-center">
                <div class="relative min-w-0 lg:flex-[2]">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input type="search" name="search" value="{{ $search }}" placeholder="Search name, brand, serial, QR"
                        class="h-10 w-full rounded-xl border-0 bg-slate-50 pl-10 pr-3 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10 {{ filled($search) ? $activeFilterClass : '' }}">
                </div>
                <select name="room_id" data-searchable="1" data-search-placeholder="Search rooms…" class="h-10 min-w-0 rounded-xl border-0 bg-slate-50 px-3 text-sm ring-1 ring-slate-200/80 lg:flex-1 {{ filled($roomId) ? $activeFilterClass : '' }}" onchange="this.form.submit()">
                    <option value="">All rooms</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->room_id }}" @selected((string) $roomId === (string) $room->room_id)>
                            {{ $room->room_name }} ({{ $room->item_count }})
                        </option>
                    @endforeach
                </select>
                <select name="asset_tag" data-searchable="1" data-search-placeholder="Search asset tags…" class="h-10 min-w-0 rounded-xl border-0 bg-slate-50 px-3 text-sm ring-1 ring-slate-200/80 lg:flex-1 {{ filled($assetTag ?? null) ? $activeFilterClass : '' }}" onchange="this.form.submit()">
                    <option value="">All asset tags</option>
                    <option value="__none" @selected(($assetTag ?? '') === '__none')>No asset tag</option>
                    @foreach (($assetTags ?? collect()) as $tag)
                        <option value="{{ $tag }}" @selected(($assetTag ?? '') === $tag)>{{ $tag }}</option>
                    @endforeach
                </select>
                <select name="filter" data-searchable="1" data-search-placeholder="Search status or condition…" class="h-10 min-w-0 rounded-xl border-0 bg-slate-50 px-3 text-sm ring-1 ring-slate-200/80 lg:flex-1 {{ filled($filter) && $filter !== 'all' ? $activeFilterClass : '' }}" onchange="this.form.submit()">
                    <option value="all" @selected($filter === 'all')>All items</option>
                    <option value="pending" @selected($filter === 'pending')>Pending</option>
                    <option value="inspected" @selected($filter === 'inspected')>Inspected</option>
                    <option value="defects" @selected($filter === 'defects')>All defects</option>
                    @foreach ($conditions as $c)
                        <option value="{{ $c }}" @selected($filter === $c)>{{ $c }}</option>
                    @endforeach
                </select>
                @if ($custodianTracking)
                    <select name="custodian" data-searchable="1" data-search-placeholder="Search custodians…" class="h-10 min-w-0 rounded-xl border-0 bg-slate-50 px-3 text-sm ring-1 ring-slate-200/80 lg:flex-1 {{ filled($custodian) ? $activeFilterClass : '' }}" onchange="this.form.submit()">
                        <option value="">All custodians</option>
                        <option value="assigned" @selected($custodian === 'assigned')>Assigned to a person</option>
                        <option value="unassigned" @selected($custodian === 'unassigned')>No accountable person</option>
                        <option value="unconfirmed" @selected($custodian === 'unconfirmed')>Custodian not confirmed</option>
                        @foreach ($custodianOptions as $person)
                            <option value="{{ $person->custodian_id }}" @selected($custodian === (string) $person->custodian_id)>
                                {{ $person->custodian_full_name }} ({{ $person->item_count }})
                            </option>
                        @endforeach
                    </select>
                @endif
                <button type="submit" class="inline-flex h-10 shrink-0 items-center justify-center rounded-xl bg-[#0025cc] px-4 text-[13px] font-medium text-white transition hover:bg-[#001fa8]">Apply</button>
            </form>
        </div>

        @php
            $showInspectColumn = $canManage && $isOpen;
            $itemGridCols = $showInspectColumn
                ? 'lg:grid-cols-[minmax(0,2fr)_minmax(0,1.3fr)_minmax(0,1.3fr)_minmax(0,1.7fr)_6.5rem]'
                : 'lg:grid-cols-[minmax(0,2fr)_minmax(0,1.3fr)_minmax(0,1.3fr)_minmax(0,1.7fr)]';
        @endphp

        <div class="hidden border-b border-slate-100 bg-slate-50/70 px-5 py-2.5 lg:grid lg:gap-4 {{ $itemGridCols }}">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Equipment</p>
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">QR code</p>
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Type & tracking</p>
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Location & status</p>
            @if ($showInspectColumn)
                <p class="text-right text-[11px] font-semibold uppercase tracking-wide text-slate-400">Action</p>
            @endif
        </div>

        <div class="divide-y divide-slate-100">
            @forelse ($items as $item)
                @php
                    $location = collect([
                        $item->building_name ?? null,
                        isset($item->floor_level) ? 'F'.$item->floor_level : null,
                        $item->room_name ?? null,
                    ])->filter()->implode(' · ');

                    $brandModel = collect([
                        $item->equipment_brand_name ?? null,
                        $item->equipment_model ?? null,
                    ])->filter()->implode(' ');

                    $assetTagValue = trim((string) ($item->equipment_asset_tag ?? ''));
                    $qrValue = trim((string) ($item->equipment_qr_code ?? ''));
                    $category = trim((string) ($item->equipment_category_name ?? ''));
                    $tracking = trim((string) ($item->equipment_tracking_mode ?? ''));
                    $serial = trim((string) ($item->equipment_serial_number ?? ''));

                    $typeParts = array_values(array_filter([
                        $category ?: null,
                        $brandModel !== '' ? $brandModel : null,
                        $tracking !== '' ? $tracking : null,
                        $serial !== '' ? 'SN '.$serial : null,
                    ]));

                    $statusParts = array_values(array_filter([
                        $location ?: null,
                        $item->equipment_inventory_status ?: null,
                        $item->equipment_condition_status ? 'Cond. '.$item->equipment_condition_status : null,
                    ]));

                    $modalMeta = collect([
                        $assetTagValue !== '' ? 'Tag '.$assetTagValue : null,
                        $qrValue !== '' ? 'QR '.$qrValue : null,
                        $location ?: 'No room',
                        $brandModel !== '' ? $brandModel : null,
                    ])->filter()->implode(' · ');
                @endphp
                <div class="px-4 py-4 sm:px-5">
                    <div class="flex flex-col gap-3 lg:grid lg:items-center lg:gap-4 {{ $itemGridCols }}">
                        <div class="min-w-0">
                        <div class="flex min-w-0 flex-wrap items-center gap-2">
                            <p class="truncate text-[15px] font-semibold text-slate-950">{{ $item->equipment_name }}</p>
                            @if ($item->item_status === 'Inspected' && $item->item_condition)
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 {{ $conditionTone[$item->item_condition] ?? 'bg-slate-50 text-slate-600 ring-slate-200' }}">
                                    {{ $item->item_condition }}
                                </span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-50 px-2.5 py-0.5 text-[11px] font-semibold text-slate-500 ring-1 ring-slate-200">Pending</span>
                            @endif
                            @if ($item->custodian_id)
                                @if ($isSchoolAdmin)
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full bg-teal-50 px-2.5 py-0.5 text-[11px] font-semibold text-teal-700 ring-1 ring-teal-100"
                                        title="Accountable person"
                                    >
                                        <i data-lucide="user-round-check" class="h-3 w-3"></i>
                                        {{ $item->custodian_name }}
                                    </span>
                                @else
                                <a
                                    href="{{ url('/maintenance/property-assignments/people/'.$item->custodian_id) }}"
                                    class="inline-flex items-center gap-1 rounded-full bg-teal-50 px-2.5 py-0.5 text-[11px] font-semibold text-teal-700 ring-1 ring-teal-100 hover:bg-teal-100"
                                    title="Accountable person"
                                >
                                    <i data-lucide="user-round-check" class="h-3 w-3"></i>
                                    {{ $item->custodian_name }}
                                </a>
                                @endif
                                @if ($item->item_status === 'Inspected' && $item->item_custodian_verified !== null)
                                    @if ($item->item_custodian_verified)
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-100">Custodian confirmed</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-semibold text-amber-700 ring-1 ring-amber-100">Custodian not confirmed</span>
                                    @endif
                                @endif
                            @endif
                        </div>
                        <p class="mt-0.5 truncate text-xs text-slate-400" title="{{ $assetTagValue !== '' ? 'Asset tag '.$assetTagValue : 'No asset tag' }}">
                            {{ $assetTagValue !== '' ? $assetTagValue : 'No asset tag' }}
                        </p>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-2 lg:contents">
                            <div class="min-w-0">
                                <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400 lg:hidden">QR code</p>
                                @if ($qrValue !== '')
                                    <button
                                        type="button"
                                        class="mt-0.5 inline-flex max-w-full items-center gap-1.5 font-mono text-[13px] text-[#0025cc] underline-offset-2 hover:underline lg:mt-0"
                                        title="Show QR code"
                                        @click="openQr(@js([
                                            'name' => $item->equipment_name,
                                            'code' => $qrValue,
                                            'tag' => $assetTagValue,
                                            'src' => url('/maintenance/equipment/qr-image/'.rawurlencode($qrValue)),
                                        ]))"
                                    >
                                        <i data-lucide="qr-code" class="h-3.5 w-3.5 shrink-0"></i>
                                        <span class="truncate">{{ $qrValue }}</span>
                                    </button>
                                @else
                                    <p class="mt-0.5 font-mono text-[13px] text-slate-400 lg:mt-0">—</p>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400 lg:hidden">Type & tracking</p>
                                <p class="mt-0.5 truncate text-sm text-slate-600 lg:mt-0" title="{{ implode(' · ', $typeParts) }}">
                                    {{ $typeParts ? implode(' · ', $typeParts) : '—' }}
                                </p>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400 lg:hidden">Location & status</p>
                                <p class="mt-0.5 truncate text-sm text-slate-600 lg:mt-0" title="{{ implode(' · ', $statusParts) }}">
                                    {{ $statusParts ? implode(' · ', $statusParts) : 'No room assigned' }}
                                </p>
                            </div>
                        </div>
                        @if ($showInspectColumn)
                            <div class="lg:justify-self-end">
                                <button
                                    type="button"
                                    class="inline-flex h-9 shrink-0 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                    @click="openInspect(@js([
                                        'itemId' => $item->item_id,
                                        'name' => $item->equipment_name,
                                        'meta' => $modalMeta,
                                        'condition' => $item->item_condition,
                                        'findings' => $item->item_findings,
                                        'action' => $item->item_action_taken,
                                        'status' => $item->item_status,
                                        'custodian' => $item->custodian_name,
                                        'custodianVerified' => isset($item->item_custodian_verified) ? (string) (int) $item->item_custodian_verified : '',
                                    ]))"
                                >
                                    <i data-lucide="{{ $item->item_status === 'Inspected' ? 'pencil' : 'scan-eye' }}" class="h-4 w-4"></i>
                                    {{ $item->item_status === 'Inspected' ? 'Update' : 'Inspect' }}
                                </button>
                            </div>
                        @endif
                    </div>
                    @if ($item->item_findings)
                        <div class="mt-2 min-w-0">
                            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Last findings</p>
                            <p class="mt-0.5 truncate text-sm text-slate-600" title="{{ $item->item_findings }}">
                                {{ \Illuminate\Support\Str::limit($item->item_findings, 120) }}
                            </p>
                        </div>
                    @endif
                </div>
            @empty
                <div class="px-5 py-14 text-center text-sm text-slate-500">No equipment matches this filter.</div>
            @endforelse
        </div>

        @if ($items->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $items->links() }}</div>
        @endif
    </div>

    {{-- Inspect modal (teleport so backdrop isn't clipped by main overflow) --}}
    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            class="fixed inset-0 z-[1300] flex items-start justify-center overflow-y-auto bg-[#0b1220]/70 p-4"
            @keydown.escape.window="open = false"
        >
            <div class="my-auto w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-xl" @click.outside="open = false">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 pt-6 pb-4">
                    <div class="min-w-0">
                        <h2 class="text-xl font-semibold text-slate-900">Record inspection</h2>
                        <p class="mt-1 truncate text-sm text-slate-500" x-text="current.name"></p>
                        <p class="mt-0.5 truncate text-xs text-slate-400" x-text="current.meta"></p>
                    </div>
                    <button type="button" @click="open = false" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                <form :action="'/maintenance/semester-inspections/{{ $campaign->campaign_id }}/inspect/' + current.itemId" method="POST" enctype="multipart/form-data" class="space-y-4 px-6 py-5">
                    @csrf
                    <div>
                        <label class="mb-1.5 block text-sm text-slate-600">Condition <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($conditions as $c)
                                <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm has-[:checked]:border-[#0025cc]/40 has-[:checked]:bg-[#0025cc]/5">
                                    <input type="radio" name="item_condition" value="{{ $c }}" x-model="current.condition" required class="text-[#0025cc]">
                                    <span>{{ $c }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm text-slate-600">Findings <span class="text-red-500">*</span></label>
                        <textarea name="item_findings" rows="3" required x-model="current.findings"
                            placeholder="What did you observe?"
                            class="w-full resize-none rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10"></textarea>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm text-slate-600">Action taken <span class="text-slate-400">(optional)</span></label>
                        <textarea name="item_action_taken" rows="2" x-model="current.action"
                            placeholder="Temporary fix, tagged for disposal, etc."
                            class="w-full resize-none rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10"></textarea>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm text-slate-600">Proof image <span class="text-slate-400">(optional)</span></label>
                        <input type="file" name="proof_image" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700">
                    </div>
                    <template x-if="current.custodian">
                        <div class="rounded-xl bg-teal-50/60 px-3 py-3 ring-1 ring-teal-100">
                            <p class="flex items-center gap-2 text-sm text-slate-700">
                                <i data-lucide="user-round-check" class="h-4 w-4 text-teal-600"></i>
                                <span>Accountable person: <span class="font-semibold" x-text="current.custodian"></span></span>
                            </p>
                            <div class="mt-2 grid gap-2 sm:grid-cols-3">
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs has-[:checked]:border-emerald-300 has-[:checked]:bg-emerald-50">
                                    <input type="radio" name="custodian_verified" value="1" x-model="current.custodianVerified" class="text-emerald-600">
                                    <span>Confirmed with them</span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs has-[:checked]:border-amber-300 has-[:checked]:bg-amber-50">
                                    <input type="radio" name="custodian_verified" value="0" x-model="current.custodianVerified" class="text-amber-600">
                                    <span>Could not confirm</span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs has-[:checked]:border-slate-300 has-[:checked]:bg-slate-50">
                                    <input type="radio" name="custodian_verified" value="" x-model="current.custodianVerified" class="text-slate-500">
                                    <span>Not checked</span>
                                </label>
                            </div>
                        </div>
                    </template>
                    <label class="flex items-start gap-3 rounded-xl bg-slate-50 px-3 py-3 ring-1 ring-slate-200/80">
                        <input type="hidden" name="apply_status" value="0">
                        <input type="checkbox" name="apply_status" value="1" checked class="mt-0.5 rounded border-slate-300 text-[#0025cc]">
                        <span class="text-sm text-slate-600">
                            Update equipment status from condition
                            <span class="mt-0.5 block text-xs text-slate-400">OK→Good · Malfunctioning→Under Maintenance · Defective/Destroyed→For Replacement</span>
                        </span>
                    </label>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <button type="button" @click="open = false" class="inline-flex h-10 items-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-600">Close</button>
                        <button type="submit" class="inline-flex h-10 items-center rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white">Save inspection</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div
            x-show="qrOpen"
            x-cloak
            class="fixed inset-0 z-[1300] flex items-center justify-center bg-[#0b1220]/70 p-4"
            role="dialog"
            aria-modal="true"
            @keydown.escape.window="qrOpen = false"
        >
            <div class="w-full max-w-xs overflow-hidden rounded-2xl bg-white shadow-xl" @click.outside="qrOpen = false">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="min-w-0">
                        <h2 class="truncate text-sm font-semibold text-slate-900" x-text="qr.name"></h2>
                        <p class="mt-0.5 truncate text-xs text-slate-400" x-text="qr.tag ? 'Asset tag ' + qr.tag : 'No asset tag'"></p>
                    </div>
                    <button type="button" @click="qrOpen = false" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
                <div class="flex flex-col items-center px-5 py-5">
                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                        <img :src="qr.src" :alt="'QR code for ' + qr.name" class="h-48 w-48 object-contain">
                    </div>
                    <p class="mt-3 max-w-full break-all text-center font-mono text-xs font-semibold tracking-wide text-slate-700" x-text="qr.code"></p>
                </div>
                <div class="flex justify-end border-t border-slate-100 px-5 py-3">
                    <button type="button" @click="qrOpen = false" class="px-3 py-2 text-sm font-medium text-slate-500 transition hover:text-slate-950">Close</button>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function semesterInspectPage() {
    return {
        open: false,
        current: { itemId: null, name: '', meta: '', condition: 'OK', findings: '', action: '', status: 'Pending', custodian: '', custodianVerified: '' },
        openInspect(payload) {
            this.current = {
                itemId: payload.itemId,
                name: payload.name || '',
                meta: payload.meta || '',
                condition: payload.condition || 'OK',
                findings: payload.findings || '',
                action: payload.action || '',
                status: payload.status || 'Pending',
                custodian: payload.custodian || '',
                custodianVerified: payload.custodianVerified ?? '',
            };
            this.open = true;
            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
        },
        qrOpen: false,
        qr: { name: '', code: '', tag: '', src: '' },
        openQr(payload) {
            this.qr = {
                name: payload.name || 'Equipment',
                code: payload.code || '',
                tag: payload.tag || '',
                src: payload.src || '',
            };
            this.qrOpen = true;
            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
        },
    };
}
</script>
@endsection
