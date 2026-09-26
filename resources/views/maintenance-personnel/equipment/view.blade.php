@extends("layouts.maintenance-layout")

@section("title", "Equipment Details")

@section("content")

@php
    $na = fn ($value) => filled($value) ? $value : "N/A";

    $formatDate = function ($value) {
        if (!filled($value)) {
            return "N/A";
        }

        try {
            return \Carbon\Carbon::parse($value)->format("M d, Y");
        } catch (\Throwable $e) {
            return $value;
        }
    };

    $condition = $equipment->equipment_condition_status ?? "";
    $inventory = $equipment->equipment_inventory_status ?? "";
    $hasQr = filled($equipment->equipment_qr_code ?? null);

    $conditionClass = match ($condition) {
        "Good" => "border-emerald-200 bg-emerald-50 text-emerald-700",
        "Fair" => "border-sky-200 bg-sky-50 text-sky-700",
        "Damaged" => "border-amber-200 bg-amber-50 text-amber-700",
        "Under Maintenance" => "border-amber-200 bg-amber-50 text-amber-700",
        "Critical" => "border-rose-200 bg-rose-50 text-rose-700",
        "Disposed" => "border-rose-200 bg-rose-50 text-rose-700",
        default => "border-slate-200 bg-slate-50 text-slate-600",
    };

    $conditionDot = match ($condition) {
        "Good" => "bg-emerald-500",
        "Fair" => "bg-sky-500",
        "Damaged", "Under Maintenance" => "bg-amber-500",
        "Critical", "Disposed" => "bg-rose-500",
        default => "bg-slate-400",
    };

    $inventoryClass = match ($inventory) {
        "Active" => "border-emerald-200 bg-emerald-50 text-emerald-700",
        "Borrowed" => "border-sky-200 bg-sky-50 text-sky-700",
        "Under Maintenance" => "border-amber-200 bg-amber-50 text-amber-700",
        "For Replacement" => "border-orange-200 bg-orange-50 text-orange-700",
        "Disposed" => "border-rose-200 bg-rose-50 text-rose-700",
        default => "border-slate-200 bg-slate-50 text-slate-600",
    };

    $inventoryDot = match ($inventory) {
        "Active" => "bg-emerald-500",
        "Borrowed" => "bg-sky-500",
        "Under Maintenance" => "bg-amber-500",
        "For Replacement" => "bg-orange-500",
        "Disposed" => "bg-rose-500",
        default => "bg-slate-400",
    };
@endphp

    <div>
        <header class="mb-6">
            <div class="mb-4 flex items-center gap-2 text-sm text-slate-400">
                <a
                    href="{{ $equipmentBack['url'] ?? url('/maintenance/equipment/all') }}"
                    class="transition hover:text-slate-700"
                >
                    {{ $equipmentBack['label'] ?? 'All Equipment' }}
                </a>
                <i data-lucide="chevron-right" class="h-4 w-4"></i>
                <span class="font-medium text-slate-600">Equipment details</span>
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <h1 class="truncate text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                        {{ $equipment->equipment_name }}
                    </h1>

                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-500">
                        <span class="inline-flex items-center gap-2">
                            <i data-lucide="tag" class="h-4 w-4"></i>
                            {{ $na($equipment->equipment_category_name) }}
                        </span>
                        <span class="inline-flex items-center gap-2">
                            <i data-lucide="map-pin" class="h-4 w-4"></i>
                            {{ $na($equipment->room_name) }}
                        </span>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        @if ($condition)
                            <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold {{ $conditionClass }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $conditionDot }}"></span>
                                {{ $condition }}
                            </span>
                        @endif

                        @if ($inventory)
                            <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold {{ $inventoryClass }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $inventoryDot }}"></span>
                                {{ $inventory }}
                            </span>
                        @endif

                        <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold {{ $equipment->equipment_is_borrowable ? 'border-sky-200 bg-sky-50 text-sky-700' : 'border-slate-200 bg-slate-50 text-slate-600' }}">
                            <i data-lucide="{{ $equipment->equipment_is_borrowable ? 'package-check' : 'package-x' }}" class="h-3.5 w-3.5"></i>
                            {{ $equipment->equipment_is_borrowable ? "Borrowable" : "Not borrowable" }}
                        </span>
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <a
                        href="{{ url('/maintenance/equipment/audit-pack/'.$equipment->equipment_id) }}"
                        class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                    >
                        <i data-lucide="file-down" class="h-4 w-4"></i>
                        Audit pack
                    </a>
                    <a
                        href="{{ $equipmentBack['url'] ?? url('/maintenance/equipment/all') }}"
                        class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                    >
                        <i data-lucide="arrow-left" class="h-4 w-4"></i>
                        Back to {{ $equipmentBack['label'] ?? 'All Equipment' }}
                    </a>
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <main class="min-w-0 space-y-6">
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Equipment identity</h2>
                            <p class="mt-1 text-sm text-slate-500">Asset tag, brand, and serial details.</p>
                        </div>
                        <i data-lucide="monitor" class="h-5 w-5 text-slate-400"></i>
                    </div>

                    <div class="px-6 py-6">
                        <div class="rounded-2xl bg-slate-50 px-4 py-4 ring-1 ring-slate-200/80">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Asset tag</p>
                            <p class="mt-1 break-all font-mono text-sm font-semibold tracking-wide text-slate-900">
                                {{ $na($equipment->equipment_asset_tag) }}
                            </p>
                        </div>

                        <dl class="mt-6 grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm text-slate-500">Equipment name</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $na($equipment->equipment_name) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-slate-500">Category</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $na($equipment->equipment_category_name) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-slate-500">Brand</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $na($equipment->equipment_brand_name) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-slate-500">Model</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $na($equipment->equipment_model) }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-sm text-slate-500">Serial number</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $na($equipment->equipment_serial_number) }}</dd>
                            </div>
                        </dl>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Location & acquisition</h2>
                            <p class="mt-1 text-sm text-slate-500">Where it lives and when it was purchased.</p>
                        </div>
                        <i data-lucide="map-pin" class="h-5 w-5 text-slate-400"></i>
                    </div>

                    <div class="grid grid-cols-1 gap-6 px-6 py-6 md:grid-cols-2">
                        <div>
                            <div class="mb-3 flex items-center gap-2">
                                <i data-lucide="door-open" class="h-4 w-4 text-slate-400"></i>
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Room</p>
                            </div>
                            <p class="font-semibold text-slate-900">{{ $na($equipment->room_name) }}</p>
                        </div>
                        <div>
                            <div class="mb-3 flex items-center gap-2">
                                <i data-lucide="hash" class="h-4 w-4 text-slate-400"></i>
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Quantity</p>
                            </div>
                            <p class="font-semibold text-slate-900">{{ $na($equipment->equipment_quantity) }}</p>
                        </div>
                        <div>
                            <div class="mb-3 flex items-center gap-2">
                                <i data-lucide="calendar" class="h-4 w-4 text-slate-400"></i>
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Purchase date</p>
                            </div>
                            <p class="font-semibold text-slate-900">{{ $formatDate($equipment->equipment_purchase_date) }}</p>
                        </div>
                        <div>
                            <div class="mb-3 flex items-center gap-2">
                                <i data-lucide="calendar-check" class="h-4 w-4 text-slate-400"></i>
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Acquired date</p>
                            </div>
                            <p class="font-semibold text-slate-900">{{ $formatDate($equipment->equipment_acquired_date) }}</p>
                        </div>
                        <div>
                            <div class="mb-3 flex items-center gap-2">
                                <i data-lucide="banknote" class="h-4 w-4 text-slate-400"></i>
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Purchase cost</p>
                            </div>
                            <p class="font-semibold text-slate-900">
                                @if (isset($equipment->equipment_purchase_cost) && $equipment->equipment_purchase_cost !== null && $equipment->equipment_purchase_cost !== '')
                                    ₱{{ number_format((float) $equipment->equipment_purchase_cost, 2) }}
                                @else
                                    {{ $na(null) }}
                                @endif
                            </p>
                        </div>
                        <div>
                            <div class="mb-3 flex items-center gap-2">
                                <i data-lucide="shield-check" class="h-4 w-4 text-slate-400"></i>
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Warranty expiration</p>
                            </div>
                            <p class="font-semibold text-slate-900">{{ $formatDate($equipment->equipment_warranty_expiration) }}</p>
                        </div>
                        <div>
                            <div class="mb-3 flex items-center gap-2">
                                <i data-lucide="hourglass" class="h-4 w-4 text-slate-400"></i>
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Useful lifespan</p>
                            </div>
                            <p class="font-semibold text-slate-900">
                                @if(!empty($equipment->equipment_useful_life_years))
                                    {{ (int) $equipment->equipment_useful_life_years }} years
                                @else
                                    {{ $na(null) }} <span class="text-xs font-normal text-slate-400">(default 5 years)</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Procurement & lifecycle</h2>
                            <p class="mt-1 text-sm text-slate-500">Bought, delivered, stocked, deployed, and disposed — with dates and actors.</p>
                        </div>
                        <i data-lucide="git-branch" class="h-5 w-5 text-slate-400"></i>
                    </div>
                    @php $lp = $lifecycleProfile ?? null; @endphp
                    <div class="grid grid-cols-1 gap-6 px-6 py-6 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Supplier</p>
                            <p class="mt-1 font-semibold text-slate-900">{{ $na($lp['supplier_name'] ?? null) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Purchase order</p>
                            <p class="mt-1 font-semibold text-slate-900">
                                {{ $na($lp['purchase_order_number'] ?? null) }}
                                @if (!empty($lp['purchase_order_date']))
                                    <span class="text-sm font-normal text-slate-500">· {{ $formatDate($lp['purchase_order_date']) }}</span>
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">ATP / RIS</p>
                            <p class="mt-1 font-semibold text-slate-900">
                                {{ $lp['atp_number'] ?? '—' }}
                                <span class="text-slate-300">/</span>
                                {{ $lp['ris_number'] ?? '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Received (RR)</p>
                            <p class="mt-1 font-semibold text-slate-900">
                                {{ $na($lp['receiving_report_number'] ?? null) }}
                                @if (!empty($lp['receiving_report_date']))
                                    <span class="text-sm font-normal text-slate-500">· {{ $formatDate($lp['receiving_report_date']) }}</span>
                                @endif
                            </p>
                            @if (!empty($lp['received_by']))
                                <p class="mt-0.5 text-xs text-slate-500">By {{ $lp['received_by'] }}</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Stocked</p>
                            <p class="mt-1 font-semibold text-slate-900">{{ $formatDate($lp['acquired_date'] ?? $equipment->equipment_acquired_date ?? null) }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ $lp['stocked_by_name'] ?? '—' }}
                                @if (!empty($lp['tracking_mode'])) · {{ $lp['tracking_mode'] }} @endif
                                @if (!empty($lp['stock_lot_code'])) · Lot {{ $lp['stock_lot_code'] }} @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">QR issued</p>
                            <p class="mt-1 font-semibold text-slate-900">{{ $formatDate($lp['qr_issued_at'] ?? null) }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $na($lp['qr_code'] ?? $equipment->equipment_qr_code ?? null) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Replacement link</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">
                                @if (!empty($lp['replaces_id']))
                                    Replaces <a class="text-[#0025cc] hover:underline" href="{{ url('/maintenance/equipment/view/'.$lp['replaces_id']) }}">#{{ $lp['replaces_id'] }}</a>
                                @elseif (!empty($lp['replaced_by_id']))
                                    Replaced by <a class="text-[#0025cc] hover:underline" href="{{ url('/maintenance/equipment/view/'.$lp['replaced_by_id']) }}">#{{ $lp['replaced_by_id'] }}</a>
                                @else
                                    —
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Repair cost (total)</p>
                            <p class="mt-1 font-semibold text-slate-900">₱{{ number_format((float) ($repairCostTotal ?? 0), 2) }}</p>
                        </div>
                    </div>
                    @if (($conditionHistory ?? collect())->isNotEmpty())
                        <div class="border-t border-slate-100 px-6 py-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Condition history</p>
                            <ul class="mt-2 space-y-1.5 text-sm text-slate-700">
                                @foreach ($conditionHistory as $ch)
                                    <li>
                                        <span class="font-medium">{{ $ch->condition_from ?: '—' }} → {{ $ch->condition_to }}</span>
                                        <span class="text-slate-400">· {{ \Illuminate\Support\Carbon::parse($ch->created_at)->format('Y-m-d H:i') }}</span>
                                        @if (!empty($ch->changed_by_name))
                                            <span class="text-slate-400">· {{ $ch->changed_by_name }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Lifecycle timeline</h2>
                            <p class="mt-1 text-sm text-slate-500">Order → receive → stock → deploy → borrow → repair → dispose.</p>
                        </div>
                        <i data-lucide="history" class="h-5 w-5 text-slate-400"></i>
                    </div>
                    @php
                        $events = $lifecycleEvents ?? collect();
                        $counts = $lifecycleCounts ?? [];
                        $typeColors = [
                            'acquisition' => 'bg-indigo-500',
                            'created' => 'bg-slate-400',
                            'qr' => 'bg-sky-500',
                            'transfer' => 'bg-violet-500',
                            'borrow' => 'bg-cyan-500',
                            'condition' => 'bg-amber-500',
                            'maintenance' => 'bg-orange-500',
                            'report' => 'bg-rose-500',
                            'disposal' => 'bg-rose-700',
                        ];
                    @endphp
                    @if (!empty($counts))
                        <div class="flex flex-wrap gap-2 border-b border-slate-100 px-6 py-3">
                            @foreach ($counts as $type => $count)
                                <span class="rounded-full bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-600 ring-1 ring-slate-200/80">
                                    {{ \App\Support\EquipmentTimeline::eventTypes()[$type] ?? ucfirst($type) }}
                                    · {{ $count }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                    <div class="px-6 py-5">
                        @forelse ($events as $event)
                            <div class="relative flex gap-4 {{ ! $loop->last ? 'pb-5' : '' }}">
                                @if (! $loop->last)
                                    <span class="absolute left-[7px] top-4 bottom-0 w-px bg-slate-200"></span>
                                @endif
                                <span class="relative z-10 mt-1.5 h-3.5 w-3.5 shrink-0 rounded-full ring-4 ring-white {{ $typeColors[$event['type'] ?? ''] ?? 'bg-slate-400' }}"></span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-semibold text-slate-900">{{ $event['title'] ?? 'Event' }}</p>
                                        <span class="rounded-full bg-slate-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500 ring-1 ring-slate-200/80">
                                            {{ $event['type_label'] ?? ($event['type'] ?? '') }}
                                        </span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-slate-400">
                                        {{ !empty($event['occurred_at']) ? $formatDate($event['occurred_at']) : '—' }}
                                        @if (!empty($event['meta']['actor']))
                                            · {{ $event['meta']['actor'] }}
                                        @elseif (!empty($event['meta']['transferred_by']))
                                            · {{ $event['meta']['transferred_by'] }}
                                        @elseif (!empty($event['meta']['borrower']))
                                            · {{ $event['meta']['borrower'] }}
                                        @endif
                                    </p>
                                    @if (!empty($event['description']))
                                        <p class="mt-1 text-sm text-slate-600">{{ $event['description'] }}</p>
                                    @endif
                                    @if (!empty($event['meta']['parts_used']) || isset($event['meta']['repair_cost']) || isset($event['meta']['downtime_hours']))
                                        <p class="mt-1 text-xs text-slate-500">
                                            @if (!empty($event['meta']['parts_used'])) Parts: {{ $event['meta']['parts_used'] }} @endif
                                            @if (isset($event['meta']['repair_cost']) && $event['meta']['repair_cost'] !== null)
                                                · Cost: ₱{{ number_format((float) $event['meta']['repair_cost'], 2) }}
                                            @endif
                                            @if (isset($event['meta']['downtime_hours']) && $event['meta']['downtime_hours'] !== null)
                                                · Downtime: {{ $event['meta']['downtime_hours'] }}h
                                            @endif
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="py-6 text-center text-sm text-slate-400">No lifecycle events recorded yet.</p>
                        @endforelse
                    </div>
                </section>
            </main>

            <aside class="space-y-6">
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <div class="border-b border-slate-100 px-5 py-4">
                        <h2 class="text-sm font-semibold text-slate-900">Status snapshot</h2>
                    </div>
                    <div class="grid grid-cols-2 gap-3 p-5">
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Condition</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $na($condition) }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Status</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $na($inventory) }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Quantity</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $na($equipment->equipment_quantity) }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Borrowable</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">
                                {{ $equipment->equipment_is_borrowable ? "Yes" : "No" }}
                            </p>
                        </div>
                    </div>
                </section>

                @if (filled($equipment->equipment_image))
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                        <div class="border-b border-slate-100 px-5 py-4">
                            <h2 class="text-sm font-semibold text-slate-900">Photo</h2>
                        </div>
                        <div class="px-5 py-5">
                            <button
                                type="button"
                                onclick="openEquipmentPhotoViewer({{ json_encode(asset('storage/'.$equipment->equipment_image)) }}, {{ json_encode($equipment->equipment_name) }})"
                                class="group relative block w-full overflow-hidden rounded-xl ring-1 ring-slate-200/80"
                                aria-label="View {{ $equipment->equipment_name }} photo fullscreen"
                            >
                                <img
                                    src="{{ asset('storage/'.$equipment->equipment_image) }}"
                                    alt="{{ $equipment->equipment_name }}"
                                    class="w-full object-cover"
                                >
                                <span class="absolute inset-0 flex items-center justify-center bg-slate-950/0 transition group-hover:bg-slate-950/35">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/95 px-3 py-1.5 text-xs font-semibold text-slate-800 opacity-0 shadow-sm transition group-hover:opacity-100">
                                        <i data-lucide="expand" class="h-3.5 w-3.5"></i>
                                        View full size
                                    </span>
                                </span>
                            </button>
                        </div>
                    </section>
                @else
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                        <div class="border-b border-slate-100 px-5 py-4">
                            <h2 class="text-sm font-semibold text-slate-900">Icon</h2>
                        </div>
                        <div class="flex items-center justify-center px-5 py-5">
                            <div class="flex h-24 w-24 items-center justify-center rounded-2xl bg-white ring-1 ring-slate-200/80">
                                <span
                                    class="inline-flex h-12 w-12 items-center justify-center [&_svg]:h-full [&_svg]:w-full"
                                    data-equipment-layout-icon="{{ $equipment->equipment_name }}"
                                ></span>
                            </div>
                        </div>
                    </section>
                @endif

                @if ($hasQr)
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                        <div class="border-b border-slate-100 px-5 py-4">
                            <h2 class="text-sm font-semibold text-slate-900">QR label</h2>
                        </div>
                        <div class="flex flex-col items-center px-5 py-5">
                            <div class="rounded-xl border border-slate-200 bg-white p-3">
                                <img
                                    src="{{ url('/maintenance/equipment/qr-image/' . $equipment->equipment_qr_code) }}"
                                    alt="Equipment QR code"
                                    class="h-40 w-40 object-contain"
                                >
                            </div>
                            <p class="mt-3 max-w-full break-all text-center font-mono text-xs font-semibold tracking-wide text-slate-700">
                                {{ $equipment->equipment_qr_code }}
                            </p>
                        </div>
                    </section>
                @endif
            </aside>
        </div>
    </div>

    @include('layouts.partials.equipment-layout-icons')
    @include('layouts.partials.equipment-photo-viewer')

@endsection
