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

                <a
                    href="{{ $equipmentBack['url'] ?? url('/maintenance/equipment/all') }}"
                    class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                >
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Back to {{ $equipmentBack['label'] ?? 'All Equipment' }}
                </a>
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
