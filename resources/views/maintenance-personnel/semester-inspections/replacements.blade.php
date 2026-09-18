@extends('layouts.maintenance-layout')

@section('title', 'Replacement Suggestions')

@section('content')
<div class="space-y-6">
    <div class="flex justify-end">
        <a
            href="{{ url('/maintenance/equipment/all') }}"
            class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"
        >
            <i data-lucide="package" class="h-4 w-4"></i>
            Manage equipment
        </a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="divide-y divide-slate-100">
            @forelse ($alerts as $alert)
                @php
                    $yearsLeft = (int) ($alert->years_remaining ?? 0);
                    $suggestion = \App\Support\EquipmentLifecycle::suggestAction(
                        $yearsLeft,
                        $alert->equipment_inventory_status ?? null
                    );
                    $tone = $suggestion['tone'] === 'alert'
                        ? 'bg-rose-50 text-rose-700 ring-rose-100'
                        : 'bg-amber-50 text-amber-700 ring-amber-100';
                    $already = strcasecmp((string) ($alert->equipment_inventory_status ?? ''), 'For Replacement') === 0;
                @endphp
                <div class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-semibold text-slate-950">{{ $alert->equipment_name }}</p>
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 {{ $tone }}">
                                {{ $suggestion['label'] }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $alert->room_name ?: 'No room' }}
                            · Age {{ (int) ($alert->age_years ?? 0) }}y
                            · Lifespan {{ (int) ($alert->useful_life_years ?? $defaultYears) }}y
                            · {{ $suggestion['hint'] }}
                            @if ($alert->equipment_asset_tag)
                                · {{ $alert->equipment_asset_tag }}
                            @endif
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <a
                            href="{{ url('/maintenance/equipment/view/'.$alert->equipment_id) }}"
                            class="inline-flex h-9 items-center rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            View
                        </a>
                        @unless ($already)
                            <form action="{{ url('/maintenance/replacement-suggestions/'.$alert->equipment_id) }}" method="POST"
                                onsubmit="return confirm('Mark this equipment for replacement?')">
                                @csrf
                                <button type="submit" class="inline-flex h-9 items-center gap-2 rounded-xl bg-[#0025cc] px-3 text-sm font-semibold text-white hover:bg-[#001fad]">
                                    <i data-lucide="replace" class="h-4 w-4"></i>
                                    Mark for replacement
                                </button>
                            </form>
                        @endunless
                    </div>
                </div>
            @empty
                <div class="px-5 py-16 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                        <i data-lucide="check-circle" class="h-6 w-6"></i>
                    </div>
                    <p class="mt-4 text-sm font-semibold text-slate-900">No aging equipment needing attention</p>
                    <p class="mt-1 text-sm text-slate-500">Assets will appear here when they near the end of their useful life.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
