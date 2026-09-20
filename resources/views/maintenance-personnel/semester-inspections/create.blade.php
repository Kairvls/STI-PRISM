@extends('layouts.maintenance-layout')

@section('title', 'New Semester Inspection')

@section('content')
@php
    $buildingCount = $buildings->count();
    $singleBuilding = $buildingCount === 1 ? $buildings->first() : null;
    $defaultBuildingId = old(
        'campaign_scope_building_id',
        $singleBuilding?->building_id ?? ''
    );
    $showBuildingScope = $buildingCount > 1;
@endphp

<div
    class="mx-auto max-w-3xl space-y-6"
    x-data="{
        scope: '{{ old('campaign_scope_type', 'campus') }}',
        buildingId: '{{ $defaultBuildingId }}',
        singleBuilding: {{ $singleBuilding ? 'true' : 'false' }}
    }"
>
    <form
        action="{{ url('/maintenance/semester-inspections') }}"
        method="POST"
        class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 sm:p-6"
    >
        @csrf

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm text-slate-600">Campaign title</label>
                <input
                    type="text"
                    name="campaign_title"
                    value="{{ old('campaign_title') }}"
                    required
                    placeholder="e.g. 1st Semester 2026 school equipment check"
                    class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                >
                @error('campaign_title') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm text-slate-600">Semester</label>
                <select
                    name="campaign_semester"
                    required
                    class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                >
                    @foreach ($semesters as $sem)
                        <option value="{{ $sem }}" @selected(old('campaign_semester') === $sem)>{{ $sem }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm text-slate-600">Academic year</label>
                <input
                    type="text"
                    name="campaign_academic_year"
                    value="{{ old('campaign_academic_year', $defaultAcademicYear) }}"
                    placeholder="2025-2026"
                    class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                >
            </div>

            <div>
                <label class="mb-1.5 block text-sm text-slate-600">Start date <span class="text-slate-400">(optional)</span></label>
                <input
                    type="date"
                    name="campaign_start_date"
                    value="{{ old('campaign_start_date') }}"
                    class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                >
            </div>

            <div>
                <label class="mb-1.5 block text-sm text-slate-600">Due date</label>
                <input
                    type="date"
                    name="campaign_due_date"
                    value="{{ old('campaign_due_date') }}"
                    required
                    class="h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                >
                @error('campaign_due_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                <p class="mt-1.5 text-xs text-slate-400">Maintenance and Administrator are notified 7 days before and on the due date.</p>
            </div>
        </div>

        <div class="rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Scope</p>
            <p class="mt-1 text-xs text-slate-500">STI College Ormoc — choose how much of the school to include.</p>

            <div class="mt-3 grid grid-cols-1 gap-3 {{ $showBuildingScope ? 'sm:grid-cols-3' : 'sm:grid-cols-2' }}">
                <label class="flex cursor-pointer items-center gap-3 rounded-xl bg-white px-3 py-3 ring-1 ring-slate-200/80 has-[:checked]:ring-[#0025cc]/40">
                    <input
                        type="radio"
                        name="campaign_scope_type"
                        value="campus"
                        x-model="scope"
                        @checked(old('campaign_scope_type', 'campus') === 'campus')
                        class="text-[#0025cc]"
                    >
                    <span class="text-sm font-medium text-slate-800">Entire campus</span>
                </label>

                @if ($showBuildingScope)
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl bg-white px-3 py-3 ring-1 ring-slate-200/80 has-[:checked]:ring-[#0025cc]/40">
                        <input
                            type="radio"
                            name="campaign_scope_type"
                            value="building"
                            x-model="scope"
                            @checked(old('campaign_scope_type') === 'building')
                            class="text-[#0025cc]"
                        >
                        <span class="text-sm font-medium text-slate-800">One building</span>
                    </label>
                @endif

                <label class="flex cursor-pointer items-center gap-3 rounded-xl bg-white px-3 py-3 ring-1 ring-slate-200/80 has-[:checked]:ring-[#0025cc]/40">
                    <input
                        type="radio"
                        name="campaign_scope_type"
                        value="floor"
                        x-model="scope"
                        @checked(old('campaign_scope_type') === 'floor')
                        class="text-[#0025cc]"
                    >
                    <span class="text-sm font-medium text-slate-800">One floor</span>
                </label>
            </div>

            @if ($singleBuilding)
                <input type="hidden" name="campaign_scope_building_id" :value="scope === 'floor' ? '{{ $singleBuilding->building_id }}' : ''">
            @endif

            <div
                class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2"
                x-show="scope === 'building' || scope === 'floor'"
                x-cloak
            >
                @if ($showBuildingScope)
                    <div x-show="scope === 'building' || scope === 'floor'" x-cloak>
                        <label class="mb-1.5 block text-sm text-slate-600">Building</label>
                        <select
                            name="campaign_scope_building_id"
                            x-model="buildingId"
                            class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm outline-none ring-1 ring-slate-200/80"
                        >
                            <option value="">Select building</option>
                            @foreach ($buildings as $building)
                                <option value="{{ $building->building_id }}">{{ $building->building_name }}</option>
                            @endforeach
                        </select>
                        @error('campaign_scope_building_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div x-show="scope === 'floor'" x-cloak class="{{ $showBuildingScope ? '' : 'sm:col-span-2' }}">
                    <label class="mb-1.5 block text-sm text-slate-600">Floor</label>
                    <select
                        name="campaign_scope_floor_id"
                        class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm outline-none ring-1 ring-slate-200/80"
                    >
                        <option value="">Select floor</option>
                        @foreach ($floors as $floor)
                            <option
                                value="{{ $floor->floor_id }}"
                                @if ($showBuildingScope)
                                    x-show="!buildingId || buildingId == '{{ $floor->floor_building_id }}'"
                                @endif
                                @selected(old('campaign_scope_floor_id') == $floor->floor_id)
                            >
                                @if ($showBuildingScope)
                                    {{ $floor->building_name }} · Floor {{ $floor->floor_level }}
                                @else
                                    Floor {{ $floor->floor_level }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('campaign_scope_floor_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
            @error('campaign_scope_type') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1.5 block text-sm text-slate-600">Notes <span class="text-slate-400">(optional)</span></label>
            <textarea
                name="campaign_notes"
                rows="3"
                class="w-full resize-none rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                placeholder="Special instructions for technicians"
            >{{ old('campaign_notes') }}</textarea>
        </div>

        <label class="flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200/80">
            <input type="hidden" name="activate" value="0">
            <input type="checkbox" name="activate" value="1" checked class="rounded border-slate-300 text-[#0025cc]">
            <span class="text-sm text-slate-700">Activate immediately (start sending due-date reminders)</span>
        </label>

        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ url('/maintenance/semester-inspections') }}" class="inline-flex h-11 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Cancel
            </a>
            <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001fad]">
                <i data-lucide="clipboard-check" class="h-4 w-4"></i>
                Create campaign
            </button>
        </div>
    </form>
</div>
@endsection
