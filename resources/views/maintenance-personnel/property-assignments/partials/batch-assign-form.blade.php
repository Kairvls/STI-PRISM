@php
    $itemIds = $items->pluck('equipment_id')->map(fn ($id) => (string) $id)->values();
    $oldSelected = collect(old('equipment_ids', []))->map(fn ($id) => (string) $id)->intersect($itemIds)->values();
    $fieldClass = 'h-10 w-full rounded-xl border-0 bg-slate-50 px-3 text-sm outline-none ring-1 ring-slate-200/80 focus:bg-white focus:ring-2 focus:ring-slate-900/10';
@endphp

<form
    method="POST"
    action="{{ route('maintenance.property-assignments.batch') }}"
    x-data="{ selected: @js($oldSelected), all: @js($itemIds) }"
>
    @csrf

    <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-2.5 text-xs">
        <label class="inline-flex cursor-pointer items-center gap-2 font-semibold text-slate-600">
            <input
                type="checkbox"
                class="rounded border-slate-300 text-[#0025cc]"
                :checked="selected.length === all.length"
                :indeterminate="selected.length > 0 && selected.length < all.length"
                @change="selected = $event.target.checked ? [...all] : []"
            >
            Select all
        </label>
        <span class="text-slate-500" x-text="selected.length + ' of ' + all.length + ' selected'"></span>
    </div>

    <ul class="max-h-[28rem] divide-y divide-slate-100 overflow-y-auto">
        @foreach ($items as $item)
            <li>
                <label class="flex cursor-pointer items-center gap-3 px-5 py-3 text-sm hover:bg-slate-50/80">
                    <input
                        type="checkbox"
                        name="equipment_ids[]"
                        value="{{ $item->equipment_id }}"
                        x-model="selected"
                        class="rounded border-slate-300 text-[#0025cc]"
                    >
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold text-slate-800">{{ $item->equipment_name }}</span>
                        <span class="block truncate text-xs text-slate-500">
                            {{ implode(' · ', array_filter([
                                $showRoom ? ($item->room_name ?? null) : null,
                                $item->equipment_category_name,
                                trim(($item->equipment_brand_name ?? '').' '.($item->equipment_model ?? '')) ?: null,
                                $item->equipment_asset_tag,
                                $item->equipment_serial_number,
                                $item->equipment_condition_status,
                            ])) ?: '—' }}
                        </span>
                    </span>
                    <a
                        href="{{ \App\Support\EquipmentViewReturn::viewUrl((int) $item->equipment_id, request()->fullUrl()) }}"
                        class="shrink-0 text-xs font-semibold text-slate-400 hover:text-slate-700"
                        @click.stop
                    >
                        Details
                    </a>
                </label>
            </li>
        @endforeach
    </ul>

    <div class="grid gap-3 border-t border-slate-100 bg-slate-50/60 px-5 py-4 md:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_auto] md:items-end">
        @if ($fixedPerson)
            <input type="hidden" name="custodian_id" value="{{ $fixedPerson->custodian_id }}">
            <div>
                <span class="mb-1 block text-xs font-medium text-slate-500">Accountable person</span>
                <p class="flex h-10 items-center rounded-xl bg-white px-3 text-sm font-semibold text-slate-800 ring-1 ring-slate-200/80">
                    {{ $fixedPerson->custodian_full_name }}
                </p>
            </div>
        @else
            <label class="block">
                <span class="mb-1 flex items-center justify-between text-xs font-medium text-slate-500">
                    Accountable person
                    <a href="{{ route('maintenance.property-assignments.people.create', ['room' => $roomId ?? null]) }}" class="font-semibold text-[#0025cc] hover:underline">+ Add person</a>
                </span>
                <select name="custodian_id" required data-searchable="1" data-search-placeholder="Search name, position, or ID…" class="{{ $fieldClass }}">
                    <option value="">Choose a person</option>
                    @foreach ($people as $personOption)
                        <option value="{{ $personOption->custodian_id }}" @selected((string) old('custodian_id') === (string) $personOption->custodian_id)>
                            {{ \App\Support\Custodians::optionLabel($personOption) }}
                        </option>
                    @endforeach
                </select>
            </label>
        @endif

        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Document no. (optional)</span>
            <input type="text" name="document_no" maxlength="64" value="{{ old('document_no') }}" placeholder="One number for the whole set" class="{{ $fieldClass }}">
        </label>

        <button
            type="submit"
            class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-[#001fad] disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="selected.length === 0"
        >
            <i data-lucide="user-plus" class="h-4 w-4"></i>
            <span x-text="selected.length > 0 ? 'Assign ' + selected.length + (selected.length === 1 ? ' item' : ' items') : 'Assign selected'">Assign selected</span>
        </button>
    </div>
</form>
