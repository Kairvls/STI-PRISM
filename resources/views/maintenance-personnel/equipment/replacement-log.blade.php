@extends('layouts.maintenance-layout')

@section('title', 'Replacement Log')

@section('content')
    @php
        $baseUrl = route('admin.equipment.replacements');
        $tabs = [
            \App\Support\ReplacementStock::STOCK_SWAP => ['Swapped from stock', 'package-check'],
            \App\Support\ReplacementStock::PROCUREMENT => ['Procurement request', 'file-text'],
        ];
        $formatDate = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('M j, Y g:i A') : '—';
        $equipmentLink = fn ($id) => url('/maintenance/equipment/view/'.$id);
    @endphp

    <div class="space-y-6">
        <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            <i data-lucide="info" class="mt-0.5 h-4 w-4 shrink-0 text-slate-400"></i>
            <p>
                Every item Maintenance sends "For Replacement" is listed here with how it was fulfilled:
                a unit swapped in from inventory stock, or a procurement request to the Purchaser.
            </p>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ $baseUrl.($search !== '' ? '?search='.urlencode($search) : '') }}"
                    class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-[13px] font-semibold transition {{ $type === null ? 'bg-[#0025cc] text-white' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
                >
                    <i data-lucide="list" class="h-4 w-4"></i>
                    All
                    <span class="rounded-md px-1.5 py-0.5 text-[11px] font-bold {{ $type === null ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ array_sum($counts) }}</span>
                </a>
                @foreach ($tabs as $key => [$label, $icon])
                    <a
                        href="{{ $baseUrl.'?'.http_build_query(array_filter(['type' => $key, 'search' => $search])) }}"
                        class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-[13px] font-semibold transition {{ $type === $key ? 'bg-[#0025cc] text-white' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
                    >
                        <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
                        {{ $label }}
                        <span class="rounded-md px-1.5 py-0.5 text-[11px] font-bold {{ $type === $key ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $counts[$key] ?? 0 }}</span>
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ $baseUrl }}" class="relative w-full sm:w-72">
                @if ($type)
                    <input type="hidden" name="type" value="{{ $type }}" />
                @endif
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                <input
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Asset tag, equipment, staff or report #"
                    class="h-10 w-full rounded-lg border border-slate-200 bg-white pl-9 pr-3 text-sm text-slate-900 outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100"
                />
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">Replaced equipment</th>
                            <th class="px-5 py-3">Replacement equipment</th>
                            <th class="px-5 py-3">Fulfillment</th>
                            <th class="px-5 py-3">Maintenance staff</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($logs as $row)
                            @php
                                $isSwap = $row->replacement_log_fulfillment_type === \App\Support\ReplacementStock::STOCK_SWAP;
                            @endphp
                            <tr class="align-top">
                                <td class="whitespace-nowrap px-5 py-4">
                                    <p class="text-slate-900">{{ $formatDate($row->replacement_log_performed_at) }}</p>
                                    @if ($row->replacement_log_report_id)
                                        <p class="mt-0.5 text-xs text-slate-500">Report #{{ $row->replacement_log_report_id }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @if ($row->replacement_log_replaced_equipment_id)
                                        <a href="{{ $equipmentLink($row->replacement_log_replaced_equipment_id) }}" class="font-semibold text-slate-900 hover:text-[#0025cc] hover:underline">
                                            {{ $row->replacement_log_replaced_label }}
                                        </a>
                                    @else
                                        <p class="font-semibold text-slate-900">{{ $row->replacement_log_replaced_label ?: 'Unlisted equipment' }}</p>
                                    @endif
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        {{ $row->replacement_log_replaced_asset_tag ?: 'No asset tag' }}
                                        @if ($row->to_room_name)
                                            · {{ $row->to_room_name }}
                                        @endif
                                    </p>
                                </td>
                                <td class="px-5 py-4">
                                    @if ($row->replacement_log_replacement_equipment_id)
                                        <a href="{{ $equipmentLink($row->replacement_log_replacement_equipment_id) }}" class="font-semibold text-slate-900 hover:text-[#0025cc] hover:underline">
                                            {{ $row->replacement_log_replacement_label }}
                                        </a>
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            {{ $row->replacement_log_replacement_asset_tag ?: 'No asset tag' }}
                                            @if ($row->from_room_name)
                                                · from {{ $row->from_room_name }}
                                            @endif
                                        </p>
                                    @else
                                        <p class="text-slate-500">Waiting for purchase</p>
                                        @if ($row->replacement_log_procurement_request_id)
                                            <p class="mt-0.5 text-xs text-slate-400">Procurement request #{{ $row->replacement_log_procurement_request_id }}</p>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $isSwap ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200' }}">
                                        {{ $isSwap ? 'Swapped from inventory stock' : 'Procurement request' }}
                                    </span>
                                    @if (! $isSwap && $row->replacement_log_stock_was_available)
                                        <p class="mt-1.5 max-w-[14rem] text-xs text-slate-500">Stock was available; procurement was chosen instead.</p>
                                    @endif
                                    @if ($row->replacement_log_notes)
                                        <p class="mt-1.5 max-w-xs text-xs text-slate-600">{{ $row->replacement_log_notes }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-slate-900">
                                    {{ $row->performed_by_name ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">
                                    No replacements recorded{{ $search !== '' ? ' for "'.$search.'"' : '' }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($logs->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">{{ $logs->links() }}</div>
            @endif
        </div>
    </div>
@endsection
