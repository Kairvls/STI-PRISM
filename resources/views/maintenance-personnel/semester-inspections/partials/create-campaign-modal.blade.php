@php
    $buildings = $campaignForm['buildings'];
    $floors = $campaignForm['floors'];
    $buildingCount = $buildings->count();
    $singleBuilding = $buildingCount === 1 ? $buildings->first() : null;
    $defaultBuildingId = old('campaign_scope_building_id', $singleBuilding?->building_id ?? '');
    $showBuildingScope = $buildingCount > 1;
    $openOnLoad = request()->boolean('create') || ($errors->any() && old('campaign_title') !== null);
    $fieldClass = 'h-11 w-full rounded-xl border-0 bg-slate-50 px-3.5 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10';
@endphp

<div
    id="createCampaignModal"
    x-data="{
        open: {{ $openOnLoad ? 'true' : 'false' }},
        scope: @js(old('campaign_scope_type', 'campus')),
        buildingId: @js((string) $defaultBuildingId),
        show() {
            this.open = true;
            this.$nextTick(() => window.lucide && window.lucide.createIcons());
        },
    }"
    x-init="if (open) $nextTick(() => window.lucide && window.lucide.createIcons())"
    @open-create-campaign.window="show()"
    @keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[1300] flex items-start justify-center overflow-y-auto bg-[#0b1220]/70 p-4"
    @click.self="open = false"
>
    <div class="my-auto flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
        <div class="flex items-start justify-between gap-4 px-6 pt-6">
            <div>
                <h2 class="text-xl font-semibold tracking-tight text-slate-900">New semester campaign</h2>
                <p class="mt-1 text-sm text-slate-500">Every active equipment in the chosen scope is added to the inspection checklist.</p>
            </div>
            <button type="button" @click="open = false" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <form action="{{ \App\Support\SchedulePortal::semesterUrl() }}" method="POST" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="campaignTitle" class="mb-1.5 block text-sm text-slate-600">Campaign title <span class="text-red-500">*</span></label>
                        <input
                            id="campaignTitle"
                            type="text"
                            name="campaign_title"
                            value="{{ old('campaign_title') }}"
                            required
                            maxlength="255"
                            placeholder="e.g. 1st Semester 2026 school equipment check"
                            class="{{ $fieldClass }}"
                        >
                        @error('campaign_title') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="campaignSemester" class="mb-1.5 block text-sm text-slate-600">Semester <span class="text-red-500">*</span></label>
                        <select id="campaignSemester" name="campaign_semester" required class="{{ $fieldClass }}">
                            @foreach ($campaignForm['semesters'] as $sem)
                                <option value="{{ $sem }}" @selected(old('campaign_semester') === $sem)>{{ $sem }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="campaignYear" class="mb-1.5 block text-sm text-slate-600">Academic year</label>
                        <input
                            id="campaignYear"
                            type="text"
                            name="campaign_academic_year"
                            value="{{ old('campaign_academic_year', $campaignForm['defaultAcademicYear']) }}"
                            placeholder="2025-2026"
                            class="{{ $fieldClass }}"
                        >
                    </div>

                    <div>
                        <label for="campaignStart" class="mb-1.5 block text-sm text-slate-600">Start date <span class="text-slate-400">(optional)</span></label>
                        <input id="campaignStart" type="date" name="campaign_start_date" value="{{ old('campaign_start_date') }}" class="{{ $fieldClass }}">
                    </div>

                    <div>
                        <label for="campaignDue" class="mb-1.5 block text-sm text-slate-600">Due date <span class="text-red-500">*</span></label>
                        <input id="campaignDue" type="date" name="campaign_due_date" value="{{ old('campaign_due_date') }}" required class="{{ $fieldClass }}">
                        @error('campaign_due_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <p class="-mt-2 text-xs text-slate-400 sm:col-span-2">Maintenance and Administrator are notified 7 days before and on the due date.</p>
                </div>

                <div class="rounded-2xl bg-slate-50/80 p-4 ring-1 ring-slate-200/80">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Scope <span class="text-red-500">*</span></p>
                    <div class="mt-3 grid grid-cols-1 gap-3 {{ $showBuildingScope ? 'sm:grid-cols-3' : 'sm:grid-cols-2' }}">
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl bg-white px-3 py-3 ring-1 ring-slate-200/80 has-[:checked]:ring-[#0025cc]/40">
                            <input type="radio" name="campaign_scope_type" value="campus" x-model="scope" class="text-[#0025cc]">
                            <span class="text-sm font-medium text-slate-800">Entire campus</span>
                        </label>

                        @if ($showBuildingScope)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl bg-white px-3 py-3 ring-1 ring-slate-200/80 has-[:checked]:ring-[#0025cc]/40">
                                <input type="radio" name="campaign_scope_type" value="building" x-model="scope" class="text-[#0025cc]">
                                <span class="text-sm font-medium text-slate-800">One building</span>
                            </label>
                        @endif

                        <label class="flex cursor-pointer items-center gap-3 rounded-xl bg-white px-3 py-3 ring-1 ring-slate-200/80 has-[:checked]:ring-[#0025cc]/40">
                            <input type="radio" name="campaign_scope_type" value="floor" x-model="scope" class="text-[#0025cc]">
                            <span class="text-sm font-medium text-slate-800">One floor</span>
                        </label>
                    </div>

                    @if ($singleBuilding)
                        <input type="hidden" name="campaign_scope_building_id" :value="scope === 'floor' ? '{{ $singleBuilding->building_id }}' : ''">
                    @endif

                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2" x-show="scope === 'building' || scope === 'floor'" x-cloak>
                        @if ($showBuildingScope)
                            <div>
                                <label for="campaignBuilding" class="mb-1.5 block text-sm text-slate-600">Building</label>
                                <select id="campaignBuilding" name="campaign_scope_building_id" x-model="buildingId" class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm outline-none ring-1 ring-slate-200/80">
                                    <option value="">Select building</option>
                                    @foreach ($buildings as $building)
                                        <option value="{{ $building->building_id }}">{{ $building->building_name }}</option>
                                    @endforeach
                                </select>
                                @error('campaign_scope_building_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        <div x-show="scope === 'floor'" x-cloak class="{{ $showBuildingScope ? '' : 'sm:col-span-2' }}">
                            <label for="campaignFloor" class="mb-1.5 block text-sm text-slate-600">Floor <span class="text-red-500">*</span></label>
                            <select id="campaignFloor" name="campaign_scope_floor_id" class="h-11 w-full rounded-xl border-0 bg-white px-3.5 text-sm outline-none ring-1 ring-slate-200/80">
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
                    <label for="campaignNotes" class="mb-1.5 block text-sm text-slate-600">Notes <span class="text-slate-400">(optional)</span></label>
                    <textarea
                        id="campaignNotes"
                        name="campaign_notes"
                        rows="3"
                        class="w-full resize-none rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10"
                        placeholder="Special instructions for technicians"
                    >{{ old('campaign_notes') }}</textarea>
                </div>

                <label class="flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200/80">
                    <input type="hidden" name="activate" value="0">
                    <input type="checkbox" name="activate" value="1" @checked(old('activate', '1') === '1') class="rounded border-slate-300 text-[#0025cc]">
                    <span class="text-sm text-slate-700">Activate immediately (start sending due-date reminders)</span>
                </label>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 px-6 py-4">
                <button type="button" @click="open = false" class="rounded-xl px-3.5 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">Cancel</button>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#001fa8]">
                    <i data-lucide="clipboard-check" class="h-4 w-4"></i>
                    Create campaign
                </button>
            </div>
        </form>
    </div>
</div>
