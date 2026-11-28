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
    $unitTags = \App\Support\EquipmentIdentity::unitTags($equipment);
    $requestedUnit = trim((string) request('unit', ''));
    $selectedUnit = in_array($requestedUnit, $unitTags, true) ? $requestedUnit : null;
    $displayAssetTag = $selectedUnit ?: ($equipment->equipment_asset_tag ?? null);
    $displayQuantity = $selectedUnit ? 1 : ($equipment->equipment_quantity ?? null);

    $conditionClass = match ($condition) {
        "Good" => "ring-emerald-200 bg-emerald-50 text-emerald-700",
        "Under Maintenance" => "ring-amber-200 bg-amber-50 text-amber-700",
        "Defective" => "ring-orange-200 bg-orange-50 text-orange-700",
        "Damaged" => "ring-rose-200 bg-rose-50 text-rose-700",
        "Disposed" => "ring-slate-200 bg-slate-100 text-slate-700",
        default => "ring-slate-200 bg-slate-50 text-slate-600",
    };

    $conditionDot = match ($condition) {
        "Good" => "bg-emerald-500",
        "Under Maintenance" => "bg-amber-500",
        "Defective" => "bg-orange-500",
        "Damaged" => "bg-rose-500",
        "Disposed" => "bg-slate-500",
        default => "bg-slate-400",
    };

    $inventoryClass = match ($inventory) {
        "Active" => "ring-emerald-200 bg-emerald-50 text-emerald-700",
        "Borrowed" => "ring-sky-200 bg-sky-50 text-sky-700",
        "Under Maintenance" => "ring-amber-200 bg-amber-50 text-amber-700",
        "For Replacement" => "ring-orange-200 bg-orange-50 text-orange-700",
        "Replaced" => "ring-blue-200 bg-blue-50 text-blue-700",
        "Disposed" => "ring-rose-200 bg-rose-50 text-rose-700",
        default => "ring-slate-200 bg-slate-50 text-slate-600",
    };

    $inventoryDot = match ($inventory) {
        "Active" => "bg-emerald-500",
        "Borrowed" => "bg-sky-500",
        "Under Maintenance" => "bg-amber-500",
        "For Replacement" => "bg-orange-500",
        "Replaced" => "bg-blue-500",
        "Disposed" => "bg-rose-500",
        default => "bg-slate-400",
    };
@endphp

    <div>
        {{-- Compact top header & return bar --}}
        <header class="mb-4">
            <nav aria-label="Breadcrumb" class="mb-2 flex items-center gap-1.5 text-xs text-slate-400">
                <a
                    href="{{ $equipmentBack['url'] ?? url('/maintenance/equipment/all') }}"
                    class="transition hover:text-slate-700"
                >
                    {{ $equipmentBack['label'] ?? 'All Equipment' }}
                </a>
                <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                <span class="font-medium text-slate-600">Equipment details</span>
            </nav>

            <div class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="truncate text-lg font-bold tracking-tight text-slate-950 sm:text-xl">
                            {{ $equipment->equipment_name }}
                        </h1>

                        @if ($condition)
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $conditionClass }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $conditionDot }}"></span>
                                {{ $condition }}
                            </span>
                        @endif

                        @if ($inventory)
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $inventoryClass }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $inventoryDot }}"></span>
                                {{ $inventory }}
                            </span>
                        @endif
                    </div>

                    <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                        <span class="inline-flex items-center gap-1.5">
                            <i data-lucide="tag" class="h-3.5 w-3.5 text-slate-400"></i>
                            {{ $na($equipment->equipment_category_name) }}
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <i data-lucide="map-pin" class="h-3.5 w-3.5 text-slate-400"></i>
                            {{ $na($equipment->room_name) }}
                        </span>
                        @if (! empty($equipment->equipment_department_name))
                            <span class="inline-flex items-center gap-1.5">
                                <i data-lucide="network" class="h-3.5 w-3.5 text-slate-400"></i>
                                {{ $equipment->equipment_department_name }}
                            </span>
                        @endif
                        @if ($propertyAssignment)
                            <a href="#property-assignment" class="inline-flex items-center gap-1.5 font-medium text-teal-700 hover:underline">
                                <i data-lucide="user-round-check" class="h-3.5 w-3.5"></i>
                                {{ $propertyAssignment->custodian_full_name ?? 'Assigned' }}
                            </a>
                        @endif
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <a
                        href="{{ $equipmentBack['url'] ?? url('/maintenance/equipment/all') }}"
                        class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                    >
                        <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                        Back to {{ $equipmentBack['label'] ?? 'All Equipment' }}
                    </a>

                    @if ($hasQr)
                        <a
                            href="{{ url('/maintenance/equipment/qr-image/' . $equipment->equipment_qr_code) }}"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                        >
                            <i data-lucide="qr-code" class="h-3.5 w-3.5"></i>
                            QR Label
                        </a>
                    @endif

                    @if ($hasPendingTransfer ?? false)
                        <a
                            href="{{ $transferRequestsUrl ?? '#' }}"
                            class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 text-xs font-semibold text-amber-800 transition hover:bg-amber-100"
                        >
                            <i data-lucide="clock" class="h-3.5 w-3.5"></i>
                            Pending transfer
                        </a>
                    @elseif ($canTransferItem ?? false)
                        <button
                            type="button"
                            onclick="openEquipmentViewTransferModal()"
                            class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white transition hover:bg-blue-800"
                        >
                            <i data-lucide="{{ ($isStorageItem ?? false) ? 'send' : 'arrow-right-left' }}" class="h-3.5 w-3.5"></i>
                            {{ ($transferNeedsApproval ?? false) ? 'Request transfer' : (($isStorageItem ?? false) ? 'Deploy' : 'Transfer') }}
                        </button>
                    @endif

                    <a
                        href="{{ url('/maintenance/equipment/audit-pack/'.$equipment->equipment_id) }}"
                        class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                    >
                        <i data-lucide="file-down" class="h-3.5 w-3.5"></i>
                        Audit pack
                    </a>

                    <div class="relative">
                        <button
                            type="button"
                            id="equipmentProfileMoreBtn"
                            aria-haspopup="menu"
                            aria-expanded="false"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                            title="More actions"
                        >
                            <i data-lucide="more-horizontal" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        {{-- Floating More menu (fixed positioning prevents clipping) --}}
        <div
            id="equipmentProfileMoreMenu"
            role="menu"
            class="fixed z-50 hidden w-48 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg ring-1 ring-slate-900/5"
        >
            @if (filled($displayAssetTag))
                <button
                    type="button"
                    data-copy-text="{{ $displayAssetTag }}"
                    class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                >
                    <i data-lucide="copy" class="h-3.5 w-3.5 text-slate-400"></i>
                    <span data-copy-label>Copy asset tag</span>
                </button>
            @endif
            @if ($hasQr)
                <a
                    href="{{ url('/maintenance/equipment/qr-image/' . $equipment->equipment_qr_code) }}"
                    download="{{ $equipment->equipment_qr_code }}.png"
                    class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                >
                    <i data-lucide="download" class="h-3.5 w-3.5 text-slate-400"></i>
                    Download QR PNG
                </a>
                <a
                    href="#qr-label"
                    class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                >
                    <i data-lucide="scan-line" class="h-3.5 w-3.5 text-slate-400"></i>
                    Jump to QR section
                </a>
            @endif
            <a
                href="#property-assignment"
                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
            >
                <i data-lucide="user-round-check" class="h-3.5 w-3.5 text-slate-400"></i>
                Accountable person
            </a>
            <button
                type="button"
                onclick="window.print()"
                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-xs font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
            >
                <i data-lucide="printer" class="h-3.5 w-3.5 text-slate-400"></i>
                Print profile
            </button>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_312px]">
            <main class="min-w-0 space-y-4">
                {{-- Equipment identity --}}
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-700">Equipment identity</h2>
                        <i data-lucide="monitor" class="h-4 w-4 text-slate-400"></i>
                    </div>

                    <div class="p-4">
                        <div class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2 ring-1 ring-slate-200/80">
                            <div class="min-w-0">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Asset tag</p>
                                <p class="mt-0.5 break-all font-mono text-xs font-semibold tracking-wide text-slate-900">
                                    {{ $na($displayAssetTag) }}
                                </p>
                            </div>
                            @if (filled($displayAssetTag))
                                <button
                                    type="button"
                                    data-copy-text="{{ $displayAssetTag }}"
                                    class="inline-flex h-7 shrink-0 items-center gap-1 rounded-md border border-slate-200 bg-white px-2 text-[11px] font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                                    title="Copy asset tag"
                                >
                                    <i data-lucide="copy" class="h-3 w-3"></i>
                                    <span data-copy-label>Copy</span>
                                </button>
                            @endif
                        </div>

                        <dl class="mt-3 grid grid-cols-1 gap-x-6 sm:grid-cols-2">
                            <div class="flex items-baseline justify-between gap-3 border-b border-slate-100 py-2 text-xs">
                                <dt class="shrink-0 text-slate-500">Equipment name</dt>
                                <dd class="truncate text-right font-semibold text-slate-900">{{ $na($equipment->equipment_name) }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-3 border-b border-slate-100 py-2 text-xs">
                                <dt class="shrink-0 text-slate-500">Classification</dt>
                                <dd class="truncate text-right font-semibold text-slate-900">{{ $na($equipment->equipment_category_name) }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-3 border-b border-slate-100 py-2 text-xs">
                                <dt class="shrink-0 text-slate-500">Brand</dt>
                                <dd class="truncate text-right font-semibold text-slate-900">{{ $na($equipment->equipment_brand_name) }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-3 border-b border-slate-100 py-2 text-xs">
                                <dt class="shrink-0 text-slate-500">Model</dt>
                                <dd class="truncate text-right font-semibold text-slate-900">{{ $na($equipment->equipment_model) }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-3 py-2 text-xs sm:col-span-2">
                                <dt class="shrink-0 text-slate-500">Serial number</dt>
                                <dd class="break-all text-right font-mono font-semibold text-slate-900">{{ $na($equipment->equipment_serial_number) }}</dd>
                            </div>
                        </dl>
                    </div>
                </section>

                {{-- Location & acquisition --}}
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-700">Location & acquisition</h2>
                        <i data-lucide="map-pin" class="h-4 w-4 text-slate-400"></i>
                    </div>

                    <dl class="grid grid-cols-1 gap-x-6 p-4 md:grid-cols-2">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 py-2 text-xs">
                            <dt class="inline-flex shrink-0 items-center gap-1.5 text-slate-500">
                                <i data-lucide="door-open" class="h-3.5 w-3.5 text-slate-400"></i>
                                Room
                            </dt>
                            <dd class="text-right font-semibold text-slate-900">{{ $na($equipment->room_name) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 py-2 text-xs">
                            <dt class="inline-flex shrink-0 items-center gap-1.5 text-slate-500">
                                <i data-lucide="network" class="h-3.5 w-3.5 text-slate-400"></i>
                                Department
                            </dt>
                            <dd class="text-right font-semibold text-slate-900">{{ $na($equipment->equipment_department_name ?? null) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 py-2 text-xs">
                            <dt class="inline-flex shrink-0 items-center gap-1.5 text-slate-500">
                                <i data-lucide="hash" class="h-3.5 w-3.5 text-slate-400"></i>
                                Quantity
                            </dt>
                            <dd class="text-right font-semibold text-slate-900">{{ $na($displayQuantity) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 py-2 text-xs">
                            <dt class="inline-flex shrink-0 items-center gap-1.5 text-slate-500">
                                <i data-lucide="calendar" class="h-3.5 w-3.5 text-slate-400"></i>
                                Purchase date
                            </dt>
                            <dd class="text-right font-semibold text-slate-900">{{ $formatDate($equipment->equipment_purchase_date) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 py-2 text-xs">
                            <dt class="inline-flex shrink-0 items-center gap-1.5 text-slate-500">
                                <i data-lucide="calendar-check" class="h-3.5 w-3.5 text-slate-400"></i>
                                Acquired date
                            </dt>
                            <dd class="text-right font-semibold text-slate-900">{{ $formatDate($equipment->equipment_acquired_date) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 py-2 text-xs">
                            <dt class="inline-flex shrink-0 items-center gap-1.5 text-slate-500">
                                <i data-lucide="banknote" class="h-3.5 w-3.5 text-slate-400"></i>
                                Purchase cost
                            </dt>
                            <dd class="text-right font-semibold text-slate-900">
                                @if (isset($equipment->equipment_purchase_cost) && $equipment->equipment_purchase_cost !== null && $equipment->equipment_purchase_cost !== '')
                                    ₱{{ number_format((float) $equipment->equipment_purchase_cost, 2) }}
                                @else
                                    {{ $na(null) }}
                                @endif
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 py-2 text-xs md:border-b-0">
                            <dt class="inline-flex shrink-0 items-center gap-1.5 text-slate-500">
                                <i data-lucide="shield-check" class="h-3.5 w-3.5 text-slate-400"></i>
                                Warranty expiration
                            </dt>
                            <dd class="text-right font-semibold text-slate-900">{{ $formatDate($equipment->equipment_warranty_expiration) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 py-2 text-xs">
                            <dt class="inline-flex shrink-0 items-center gap-1.5 text-slate-500">
                                <i data-lucide="hourglass" class="h-3.5 w-3.5 text-slate-400"></i>
                                Useful lifespan
                            </dt>
                            <dd class="text-right font-semibold text-slate-900">
                                @if(!empty($equipment->equipment_useful_life_years))
                                    {{ (int) $equipment->equipment_useful_life_years }} years
                                @else
                                    {{ $na(null) }} <span class="text-[11px] font-normal text-slate-400">(default 5 yrs)</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </section>

                @include('maintenance-personnel.equipment.partials.lifecycle-sections')
            </main>

            <aside class="space-y-4">
                {{-- Status snapshot --}}
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <div class="border-b border-slate-100 px-4 py-2.5">
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-700">Status snapshot</h2>
                    </div>
                    <div class="grid grid-cols-3 gap-2 p-3">
                        <div class="rounded-lg bg-slate-50 px-2.5 py-2 ring-1 ring-slate-200/60">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Condition</p>
                            <p class="mt-0.5 truncate text-xs font-semibold text-slate-900" title="{{ $na($condition) }}">{{ $na($condition) }}</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-2.5 py-2 ring-1 ring-slate-200/60">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Status</p>
                            <p class="mt-0.5 truncate text-xs font-semibold text-slate-900" title="{{ $na($inventory) }}">{{ $na($inventory) }}</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-2.5 py-2 ring-1 ring-slate-200/60">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Quantity</p>
                            <p class="mt-0.5 truncate text-xs font-semibold text-slate-900">{{ $na($displayQuantity) }}</p>
                        </div>
                    </div>
                </section>

                {{-- QR label --}}
                @if ($hasQr)
                    <section id="qr-label" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
                            <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-700">QR label</h2>
                            <a
                                href="{{ url('/maintenance/equipment/qr-image/' . $equipment->equipment_qr_code) }}"
                                download="{{ $equipment->equipment_qr_code }}.png"
                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#0025cc] hover:underline"
                            >
                                <i data-lucide="download" class="h-3 w-3"></i>
                                Save PNG
                            </a>
                        </div>
                        <div class="flex flex-col items-center p-4">
                            <div class="rounded-lg border border-slate-200 bg-white p-2">
                                <img
                                    src="{{ url('/maintenance/equipment/qr-image/' . $equipment->equipment_qr_code) }}"
                                    alt="Equipment QR code"
                                    class="h-32 w-32 object-contain"
                                >
                            </div>
                            <p class="mt-2 max-w-full break-all text-center font-mono text-[11px] font-semibold tracking-wide text-slate-700">
                                {{ $equipment->equipment_qr_code }}
                            </p>
                        </div>
                    </section>
                @endif

                {{-- Accountable person / property assignment --}}
                @include('maintenance-personnel.equipment.partials.property-assignment-panel')

                {{-- Photo or Icon --}}
                @if (filled($equipment->equipment_image))
                    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                        <div class="border-b border-slate-100 px-4 py-2.5">
                            <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-700">Photo</h2>
                        </div>
                        <div class="p-3.5">
                            <button
                                type="button"
                                onclick="openEquipmentPhotoViewer({{ json_encode(asset('storage/'.$equipment->equipment_image)) }}, {{ json_encode($equipment->equipment_name) }})"
                                class="group relative block w-full overflow-hidden rounded-lg ring-1 ring-slate-200/80"
                                aria-label="View {{ $equipment->equipment_name }} photo fullscreen"
                            >
                                <img
                                    src="{{ asset('storage/'.$equipment->equipment_image) }}"
                                    alt="{{ $equipment->equipment_name }}"
                                    class="max-h-48 w-full object-cover"
                                >
                                <span class="absolute inset-0 flex items-center justify-center bg-slate-950/0 transition group-hover:bg-slate-950/35">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/95 px-2.5 py-1 text-[11px] font-semibold text-slate-800 opacity-0 shadow-sm transition group-hover:opacity-100">
                                        <i data-lucide="expand" class="h-3 w-3"></i>
                                        View full size
                                    </span>
                                </span>
                            </button>
                        </div>
                    </section>
                @else
                    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                        <div class="border-b border-slate-100 px-4 py-2.5">
                            <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-700">Icon</h2>
                        </div>
                        <div class="flex items-center justify-center p-4">
                            <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-slate-50 ring-1 ring-slate-200/80">
                                <span
                                    class="inline-flex h-9 w-9 items-center justify-center [&_svg]:h-full [&_svg]:w-full"
                                    data-equipment-layout-icon="{{ $equipment->equipment_name }}"
                                ></span>
                            </div>
                        </div>
                    </section>
                @endif

                {{-- Stock history --}}
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <div class="border-b border-slate-100 px-4 py-2.5">
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-700">Stock history</h2>
                        <p class="mt-0.5 text-[11px] text-slate-500">Quantity changes recorded here; on-hand is the sum of movements.</p>
                    </div>
                    <div class="px-4 py-2.5">
                        @if (($stockMovements ?? collect())->isEmpty())
                            <p class="py-1.5 text-xs text-slate-500">No stock movements yet.</p>
                        @else
                            <ul class="divide-y divide-slate-100">
                                @foreach ($stockMovements as $movement)
                                    <li class="flex items-start justify-between gap-3 py-2">
                                        <div class="min-w-0">
                                            <p class="text-xs font-medium text-slate-900">
                                                {{ \App\Support\EquipmentStockLedger::LABELS[$movement->movement_type] ?? $movement->movement_type }}
                                                <span class="font-semibold {{ (int) $movement->quantity_delta < 0 ? 'text-rose-700' : 'text-emerald-700' }}">
                                                    {{ (int) $movement->quantity_delta > 0 ? '+' : '' }}{{ (int) $movement->quantity_delta }}
                                                </span>
                                            </p>
                                            @if (filled($movement->remarks))
                                                <p class="mt-0.5 text-[11px] leading-4 text-slate-500">{{ $movement->remarks }}</p>
                                            @endif
                                        </div>
                                        <p class="shrink-0 text-[11px] text-slate-400">
                                            {{ \Carbon\Carbon::parse($movement->created_at)->format('M d, Y g:i A') }}
                                        </p>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </section>
            </aside>
        </div>
    </div>

    @if (($canTransferItem ?? false) && ! ($hasPendingTransfer ?? false))
        {{-- Deploy / Transfer Equipment Modal --}}
        <div
            id="equipmentViewTransferModal"
            class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4 backdrop-blur-[1px]"
            onclick="if (event.target === this) closeEquipmentViewTransferModal()"
        >
            <div class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="equipmentViewTransferTitle">
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 px-6 py-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-[#0025cc] ring-1 ring-blue-100">
                            <i data-lucide="{{ ($isStorageItem ?? false) ? 'send' : 'arrow-right-left' }}" class="h-4 w-4"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 id="equipmentViewTransferTitle" class="text-base font-semibold tracking-tight text-slate-950">
                                {{ ($isStorageItem ?? false) ? 'Deploy equipment' : 'Transfer equipment' }}
                            </h2>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ ($isStorageItem ?? false) ? 'Send this item from storage to a room, person, or department.' : 'Move this item to another room, person, or department.' }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        onclick="closeEquipmentViewTransferModal()"
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                        aria-label="Close"
                    >
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                <form action="{{ $transferActionUrl }}" method="POST" class="flex min-h-0 flex-1 flex-col">
                    @csrf
                    <input type="hidden" name="equipment_id" value="{{ $equipment->equipment_id }}">

                    <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                        <div class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-slate-50/70">
                            <div class="flex items-center justify-between gap-4 px-4 py-2.5">
                                <span class="shrink-0 text-xs text-slate-500">Equipment</span>
                                <span class="min-w-0 truncate text-right text-xs font-semibold text-slate-950">{{ $equipment->equipment_name }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4 px-4 py-2.5">
                                <span class="shrink-0 text-xs text-slate-500">Asset tag</span>
                                <span class="min-w-0 truncate text-right font-mono text-xs font-medium text-slate-800">{{ $na($displayAssetTag) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4 px-4 py-2.5">
                                <span class="shrink-0 text-xs text-slate-500">Current room</span>
                                <span class="min-w-0 truncate text-right text-xs font-medium text-slate-800">{{ $na($equipment->room_name) }}</span>
                            </div>
                        </div>

                        @include('maintenance-personnel.equipment.partials.inventory-transfer-destination', [
                            'prefix' => 'equipmentViewTransfer',
                            'roomOptions' => $transferRoomOptions ?? collect(),
                            'roomPlaceholder' => ($isStorageItem ?? false) ? 'Select classroom / lab / office' : 'Select destination room',
                        ])

                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-4">
                                <label for="equipmentViewTransferRemarks" class="text-sm font-medium text-slate-700">Remarks</label>
                                <span class="text-xs text-slate-400">Optional</span>
                            </div>
                            <textarea
                                id="equipmentViewTransferRemarks"
                                name="remarks"
                                rows="3"
                                placeholder="{{ ($isStorageItem ?? false) ? 'e.g. Deployed for room use' : 'Why is it moving?' }}"
                                class="w-full resize-none rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                            ></textarea>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-6 py-3.5">
                        <button
                            type="button"
                            onclick="closeEquipmentViewTransferModal()"
                            class="inline-flex h-9 items-center justify-center rounded-lg px-4 text-xs font-semibold text-slate-600 transition hover:bg-slate-200/60 hover:text-slate-950"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-[#0025cc] px-4 text-xs font-semibold text-white transition hover:bg-blue-800"
                        >
                            <i data-lucide="{{ ($isStorageItem ?? false) ? 'send' : 'arrow-right-left' }}" class="h-3.5 w-3.5"></i>
                            {{ ($transferNeedsApproval ?? false) ? 'Send for approval' : (($isStorageItem ?? false) ? 'Deploy equipment' : 'Transfer equipment') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <script>
        function openEquipmentViewTransferModal() {
            const modal = document.getElementById('equipmentViewTransferModal');
            if (!modal) return;
            if (typeof resetInventoryTransferDestination === 'function') {
                resetInventoryTransferDestination('equipmentViewTransfer');
            }
            if (typeof setInventoryTransferQuantity === 'function') {
                setInventoryTransferQuantity(
                    'equipmentViewTransfer',
                    @json($equipment->equipment_tracking_mode ?? 'Individual'),
                    @json((int) ($equipment->equipment_quantity ?? 1))
                );
            }
            const remarks = document.getElementById('equipmentViewTransferRemarks');
            if (remarks) remarks.value = '';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
        }

        function closeEquipmentViewTransferModal() {
            const modal = document.getElementById('equipmentViewTransferModal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeEquipmentViewTransferModal();
        });

        (function () {
            const btn = document.getElementById('equipmentProfileMoreBtn');
            const menu = document.getElementById('equipmentProfileMoreMenu');

            function closeMenu() {
                if (!menu || !btn) return;
                menu.classList.add('hidden');
                btn.setAttribute('aria-expanded', 'false');
            }

            if (btn && menu) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const isOpen = !menu.classList.contains('hidden');
                    if (isOpen) {
                        closeMenu();
                        return;
                    }
                    menu.classList.remove('hidden');
                    const rect = btn.getBoundingClientRect();
                    const menuWidth = menu.offsetWidth || 192;
                    const left = Math.max(8, Math.min(window.innerWidth - menuWidth - 8, rect.right - menuWidth));
                    const top = Math.min(window.innerHeight - (menu.offsetHeight || 160) - 8, rect.bottom + 6);
                    menu.style.left = left + 'px';
                    menu.style.top = top + 'px';
                    btn.setAttribute('aria-expanded', 'true');
                    if (window.lucide && typeof window.lucide.createIcons === 'function') {
                        window.lucide.createIcons();
                    }
                });

                document.addEventListener('click', function (e) {
                    if (!menu.contains(e.target) && e.target !== btn) {
                        closeMenu();
                    }
                });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') closeMenu();
                });
                window.addEventListener('scroll', closeMenu, true);
                window.addEventListener('resize', closeMenu);
            }

            document.addEventListener('click', function (e) {
                const copyBtn = e.target.closest('[data-copy-text]');
                if (!copyBtn) return;
                const text = copyBtn.getAttribute('data-copy-text') || '';
                if (!text) return;
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(function () {
                        const label = copyBtn.querySelector('[data-copy-label]');
                        if (label) {
                            const orig = label.textContent;
                            label.textContent = 'Copied';
                            setTimeout(function () { label.textContent = orig; }, 1400);
                        }
                    });
                }
            });
        })();
    </script>

    @include('layouts.partials.equipment-layout-icons')
    @include('layouts.partials.equipment-photo-viewer')

@endsection
