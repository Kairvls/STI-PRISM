@php
    $sets = $computerSets ?? collect();
@endphp

@php
    $setOptions = $computerSetOptions ?? collect();
@endphp
<form method="GET" action="{{ url()->current() }}" data-auto-filter class="border-b border-slate-200 px-5 py-4">
    <input type="hidden" name="tab" value="sets">
    <div class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1fr)_220px_180px_160px_auto]">
        <div class="relative">
            <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search set tag, name, room, or QR..."
                class="h-9 w-full cursor-pointer rounded-xl border-0 bg-slate-50 pl-10 pr-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 placeholder:text-slate-400 transition hover:bg-white hover:ring-slate-300 focus:bg-white focus:ring-2 focus:ring-slate-900/10"
            >
        </div>
        <select
            name="computer_set"
            data-searchable="1"
            data-search-placeholder="Search computer set…"
            class="h-9 cursor-pointer rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 transition hover:bg-white hover:ring-slate-300 focus:bg-white focus:ring-2 focus:ring-slate-900/10"
        >
            <option value="">All computer sets</option>
            @foreach ($setOptions as $option)
                <option value="{{ $option->computer_set_id }}" @selected((string) request('computer_set') === (string) $option->computer_set_id)>
                    {{ $option->computer_set_name ?: $option->computer_set_tag }}
                    @if (filled($option->computer_set_name) && filled($option->computer_set_tag))
                        ({{ $option->computer_set_tag }})
                    @endif
                </option>
            @endforeach
        </select>
        <select name="room" class="h-9 cursor-pointer rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 transition hover:bg-white hover:ring-slate-300 focus:bg-white focus:ring-2 focus:ring-slate-900/10">
            <option value="">All Locations</option>
            @foreach ($rooms as $room)
                <option value="{{ $room->room_id }}" @selected((string) request('room') === (string) $room->room_id)>
                    {{ $room->room_name }}
                </option>
            @endforeach
        </select>
        <select name="qr_status" class="h-9 cursor-pointer rounded-xl border-0 bg-slate-50 px-3.5 text-sm text-slate-900 outline-none ring-1 ring-slate-200/80 transition hover:bg-white hover:ring-slate-300 focus:bg-white focus:ring-2 focus:ring-slate-900/10">
            <option value="">All QR Status</option>
            <option value="generated" @selected(request('qr_status') === 'generated')>Generated</option>
            <option value="not_generated" @selected(request('qr_status') === 'not_generated')>Not generated</option>
        </select>
        <button type="submit" class="inline-flex h-9 cursor-pointer items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white transition hover:bg-[#001fad]">
            <i data-lucide="filter" class="h-4 w-4"></i>
            Apply
        </button>
    </div>
</form>

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
        <thead class="bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
            <tr>
                <th class="w-12 px-5 py-3">
                    <input
                        type="checkbox"
                        class="h-4 w-4 cursor-pointer rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                        :checked="allSelectableSelected()"
                        @change="toggleSelectAll($event.target.checked)"
                        aria-label="Select all generated computer set QR codes on this page"
                    >
                </th>
                <th class="px-5 py-3">Computer set</th>
                <th class="px-5 py-3">Location</th>
                <th class="px-5 py-3">Equipments</th>
                <th class="px-5 py-3">QR code</th>
                <th class="px-5 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($sets as $set)
                @php
                    $hasQr = filled($set->computer_set_qr_code ?? null);
                    $setTitle = $set->computer_set_name ?: ($set->computer_set_tag ?: 'Computer set');
                    $location = collect([$set->room_name ?? null, $set->building_name ?? null])->filter()->implode(', ');
                @endphp
                <tr class="hover:bg-slate-50/70">
                    <td class="px-5 py-4">
                        @if ($hasQr)
                            <input
                                type="checkbox"
                                class="h-4 w-4 cursor-pointer rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                                value="{{ $set->computer_set_qr_code }}"
                                :checked="isSelected(@js($set->computer_set_qr_code))"
                                @change="toggleCode(@js($set->computer_set_qr_code), $event.target.checked)"
                                aria-label="Select {{ $setTitle }} for printing"
                            >
                        @else
                            <span class="block h-4 w-4"></span>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        <p class="font-semibold text-slate-900">{{ $setTitle }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $set->computer_set_tag }}</p>
                    </td>
                    <td class="px-5 py-4 text-slate-600">
                        {{ $location !== '' ? $location : 'No room' }}
                    </td>
                    <td class="px-5 py-4">
                        <span class="rounded-full bg-slate-50 px-2.5 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">
                            {{ (int) ($set->member_count ?? 0) }} {{ Str::plural('unit', (int) ($set->member_count ?? 0)) }}
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        @if ($hasQr)
                            <a
                                href="{{ url('/equipment/'.$set->computer_set_qr_code) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="group flex w-full max-w-sm items-center gap-3 rounded-xl text-left transition hover:bg-slate-50"
                            >
                                <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-emerald-100 bg-white p-1.5 shadow-sm transition group-hover:border-emerald-200">
                                    <img
                                        src="{{ url('/maintenance/equipment/qr-image/'.$set->computer_set_qr_code) }}"
                                        alt="QR {{ $set->computer_set_qr_code }}"
                                        class="h-full w-full object-contain"
                                    >
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 text-[12px] font-medium text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Generated
                                    </div>
                                    <p class="mt-1 truncate font-mono text-sm font-semibold text-slate-800 group-hover:text-[#0025cc]">
                                        {{ $set->computer_set_qr_code }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-slate-500">Equipments keep their own QRs · Click to preview</p>
                                </div>
                            </a>
                        @else
                            <span class="text-xs text-slate-400">Generate a set QR to continue</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-center">
                        @if ($hasQr)
                            <div class="relative inline-block" x-data="qrActionMenu()">
                                <button
                                    type="button"
                                    x-ref="trigger"
                                    @click="toggle()"
                                    @click.outside="open = false"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500 transition hover:bg-slate-200 hover:text-slate-700"
                                    data-tooltip="Actions"
                                    aria-label="QR actions"
                                >
                                    <i data-lucide="ellipsis" class="h-4 w-4"></i>
                                </button>

                                <div
                                    x-ref="menu"
                                    x-cloak
                                    x-show="open"
                                    x-transition
                                    class="fixed z-[200] w-48 overflow-hidden rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-lg shadow-slate-900/10"
                                >
                                    <a
                                        href="{{ url('/equipment/'.$set->computer_set_qr_code) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        @click="open = false"
                                        class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                                    >
                                        <i data-lucide="scan" class="h-3.5 w-3.5"></i>
                                        Preview scan
                                    </a>

                                    <button
                                        type="button"
                                        @click="open = false; openQrModal({
                                            kind: 'computer_set',
                                            equipment: @js($setTitle),
                                            assetTag: @js($set->computer_set_tag),
                                            serial: @js(((int) ($set->member_count ?? 0)).' '.Str::plural('unit', (int) ($set->member_count ?? 0))),
                                            room: @js($location !== '' ? $location : 'No room'),
                                            category: 'Computer set',
                                            qrImage: @js(url('/maintenance/equipment/qr-image/'.$set->computer_set_qr_code)),
                                            qrCode: @js($set->computer_set_qr_code)
                                        })"
                                        class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                                    >
                                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                        Preview QR code
                                    </button>

                                    <a
                                        href="{{ url('/admin/qr-tools/'.$set->computer_set_qr_code.'/print') }}"
                                        target="_blank"
                                        @click="open = false"
                                        class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                                    >
                                        <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                                        Print QR
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('admin.qr-tools.generate-set', $set->computer_set_id) }}"
                                        @submit.prevent="open = false; confirmQrGenerate($el, true, @js($setTitle))"
                                    >
                                        @csrf
                                        <button
                                            type="submit"
                                            class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                                        >
                                            <i data-lucide="refresh-cw" class="h-3.5 w-3.5"></i>
                                            Regenerate QR
                                        </button>
                                    </form>

                                    <div class="my-1 border-t border-slate-100"></div>

                                    <a
                                        href="{{ route('admin.computer-sets.show', $set->computer_set_id) }}"
                                        @click="open = false"
                                        class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                                    >
                                        <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                                        Open set
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="inline-flex items-center justify-center gap-1.5">
                                <form
                                    method="POST"
                                    action="{{ route('admin.qr-tools.generate-set', $set->computer_set_id) }}"
                                    @submit.prevent="confirmQrGenerate($el, false, @js($setTitle))"
                                    class="inline-flex"
                                >
                                    @csrf
                                    <button
                                        type="submit"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#0025cc] text-white transition hover:bg-[#001fad]"
                                        data-tooltip="Generate QR"
                                        aria-label="Generate QR"
                                    >
                                        <i data-lucide="qr-code" class="h-4 w-4"></i>
                                    </button>
                                </form>
                                <a
                                    href="{{ route('admin.computer-sets.show', $set->computer_set_id) }}"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-[#0025cc] transition hover:bg-slate-50"
                                    data-tooltip="Open set"
                                    aria-label="Open set"
                                >
                                    <i data-lucide="external-link" class="h-4 w-4"></i>
                                </a>
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-16 text-center text-sm text-slate-400">
                        No computer sets found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($sets instanceof \Illuminate\Contracts\Pagination\Paginator && $sets->hasPages())
    <div class="border-t border-slate-100 px-5 py-4">
        {{ $sets->links() }}
    </div>
@endif
