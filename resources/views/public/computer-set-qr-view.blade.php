<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $computerSet->computer_set_name ?: $computerSet->computer_set_tag }} · Computer set</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100">
<div class="mx-auto max-w-5xl p-6">
    <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Computer set</p>
        <h1 class="mt-2 text-3xl font-black text-slate-900">
            {{ $computerSet->computer_set_name ?: $computerSet->computer_set_tag }}
        </h1>
        <p class="mt-2 text-sm text-slate-500">
            Scan this set label to see what’s inside. Each piece of equipment still has its own QR code.
        </p>

        <div class="mt-6 grid gap-4 text-sm text-slate-800 md:grid-cols-2">
            <div>
                <span class="block text-xs font-semibold uppercase tracking-wide text-slate-400">Set tag</span>
                <span class="mt-1 block font-semibold">{{ $computerSet->computer_set_tag ?: '—' }}</span>
            </div>
            <div>
                <span class="block text-xs font-semibold uppercase tracking-wide text-slate-400">Set QR</span>
                <span class="mt-1 block font-semibold">{{ $computerSet->computer_set_qr_code ?: '—' }}</span>
            </div>
            <div>
                <span class="block text-xs font-semibold uppercase tracking-wide text-slate-400">Room</span>
                <span class="mt-1 block font-semibold">{{ $computerSet->room_name ?: 'No room' }}</span>
            </div>
            <div>
                <span class="block text-xs font-semibold uppercase tracking-wide text-slate-400">Status</span>
                <span class="mt-1 block font-semibold">{{ $computerSet->computer_set_status ?: 'Active' }}</span>
            </div>
            @if (!empty($computerSet->computer_set_placement_zone))
                <div>
                    <span class="block text-xs font-semibold uppercase tracking-wide text-slate-400">Placement</span>
                    <span class="mt-1 block font-semibold">{{ $computerSet->computer_set_placement_zone }}</span>
                </div>
            @endif
        </div>

        <div class="mt-10">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold text-slate-900">Equipment in this set</h2>
                <span class="text-xs font-semibold text-slate-400">{{ $members->count() }} {{ Str::plural('item', $members->count()) }}</span>
            </div>

            <div class="mt-4 divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200">
                @forelse ($members as $member)
                    <div class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">
                                {{ $member->equipment_name ?: 'Equipment' }}
                                @if (!empty($member->member_role) && $member->member_role !== 'other')
                                    <span class="ml-1 text-xs font-medium text-slate-400">· {{ \App\Support\ComputerSets::ROLES[$member->member_role] ?? $member->member_role }}</span>
                                @endif
                            </p>
                            <p class="mt-0.5 truncate text-xs text-slate-500">
                                {{ collect([
                                    $member->equipment_asset_tag ? 'Tag '.$member->equipment_asset_tag : null,
                                    $member->equipment_serial_number ? 'Serial '.$member->equipment_serial_number : null,
                                    $member->equipment_category_name ?? null,
                                ])->filter()->implode(' · ') ?: 'No asset tag / serial' }}
                            </p>
                            <p class="mt-1 truncate text-xs font-semibold text-slate-700">
                                @if (filled($member->equipment_qr_code))
                                    QR {{ $member->equipment_qr_code }}
                                @else
                                    QR not generated
                                @endif
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <span class="rounded-full bg-slate-50 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600 ring-1 ring-slate-200">
                                {{ $member->equipment_inventory_status ?: 'Status not set' }}
                            </span>
                            @if (filled($member->equipment_qr_code))
                                <a
                                    href="{{ url('/equipment/'.$member->equipment_qr_code) }}"
                                    class="rounded-lg bg-[#0025cc] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#001fad]"
                                >
                                    Open unit
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-10 text-center text-sm text-slate-400">No equipment linked to this computer set yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
</body>
</html>
