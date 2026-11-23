@extends($procurementLayout ?? 'layouts.purchaser-layout')

@section('page-title', \App\Support\CanvassWorkflow::displayNumber($canvass))
@section('page-subtitle', 'Canvass quotation comparison & approval summary.')

@section('content')
@php
    $routePrefix = $pp ?? 'purchaser';
    $badge = \App\Support\CanvassWorkflow::statusBadge((string) $canvass->canvass_status);
    $editable = \App\Support\CanvassWorkflow::canEdit($canvass);
    $canCreateRis = \App\Support\CanvassWorkflow::canConvertToRis($canvass);
@endphp

<div class="mx-auto max-w-5xl space-y-5 pb-10">
    {{-- Header Actions --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a
            href="{{ route($routePrefix.'.canvass.index') }}"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
        >
            <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
            <span>Back to Canvasses</span>
        </a>

        <div class="flex flex-wrap items-center gap-2">
            @if ($editable)
                <a
                    href="{{ route($routePrefix.'.canvass.edit', $canvass->canvass_id) }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-blue-300 bg-white px-4 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-50"
                >
                    <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                    <span>Edit Canvass</span>
                </a>
            @endif

            @if ($canCreateRis)
                <a
                    href="{{ route($routePrefix.'.ris.index', ['from_canvass' => $canvass->canvass_id]) }}"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700"
                >
                    <i data-lucide="file-plus-2" class="h-4 w-4"></i>
                    <span>Create RIS from Canvass</span>
                </a>
            @elseif (! empty($canvass->canvass_ris_id))
                <a
                    href="{{ route($routePrefix.'.ris.index', ['view_ris' => $canvass->canvass_ris_id]) }}"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-teal-600 px-4 py-2 text-xs font-semibold text-white hover:bg-teal-700"
                >
                    <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                    <span>Open Linked RIS {{ $canvass->linked_ris_number ?: '#'.$canvass->canvass_ris_id }}</span>
                </a>
            @endif
        </div>
    </div>

    {{-- Summary Card --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h2 class="text-lg font-bold text-slate-900">{{ \App\Support\CanvassWorkflow::displayNumber($canvass) }}</h2>
                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $badge['class'] }}">
                        {{ $badge['label'] }}
                    </span>
                    @if ($canvass->canvass_urgency === 'Urgent')
                        <span class="rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-700">Urgent</span>
                    @endif
                </div>
                <p class="mt-1 text-xs text-slate-500">
                    @if (! empty($canvass->canvass_request_no))
                        Request No.: <span class="font-semibold text-blue-700">{{ $canvass->canvass_request_no }}</span> ·
                    @endif
                    Submitted: {{ $canvass->canvass_submitted_at ? \Carbon\Carbon::parse($canvass->canvass_submitted_at)->format('M d, Y h:i A') : 'Draft' }}
                    @if (! empty($canvass->reviewer_name))
                        · Reviewed by {{ $canvass->reviewer_name }}
                    @endif
                </p>
                @if (! empty($canvass->canvass_purpose))
                    <p class="mt-2 text-sm text-slate-700"><span class="font-semibold">Purpose:</span> {{ $canvass->canvass_purpose }}</p>
                @endif
            </div>

            <div class="flex gap-4 text-right">
                <div>
                    <p class="text-[11px] font-semibold uppercase text-slate-400">Lowest Est. Total</p>
                    <p class="text-lg font-bold text-slate-900">₱{{ number_format((float) $lowestTotalSum, 2) }}</p>
                </div>
                @if ($approvedTotalSum > 0)
                    <div class="border-l border-slate-200 pl-4">
                        <p class="text-[11px] font-semibold uppercase text-emerald-600">Approved Total</p>
                        <p class="text-lg font-bold text-emerald-700">₱{{ number_format((float) $approvedTotalSum, 2) }}</p>
                    </div>
                @endif
            </div>
        </div>

        @if (! empty($canvass->canvass_review_note))
            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-700">
                <span class="font-semibold">School Administrator review note:</span> {{ $canvass->canvass_review_note }}
            </div>
        @endif
    </div>

    {{-- Per-Item Quotation Comparison --}}
    @foreach ($items as $idx => $item)
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-slate-50 px-5 py-3.5">
                <div>
                    <span class="text-xs font-bold uppercase text-blue-600">Item {{ $idx + 1 }}</span>
                    <h3 class="text-sm font-bold text-slate-900">
                        {{ $item->canvass_item_name ?: $item->catalog_item_name }}
                        <span class="font-normal text-slate-500">· {{ $item->canvass_item_quantity }} {{ $item->uom_name ?: 'unit(s)' }}</span>
                    </h3>
                </div>
                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $item->canvass_item_status === 'Approved' ? 'bg-emerald-100 text-emerald-800' : ($item->canvass_item_status === 'Returned' ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-700') }}">
                    {{ $item->canvass_item_status }}
                </span>
            </div>

            @if (! empty($item->canvass_item_review_note))
                <div class="border-b border-amber-100 bg-amber-50/70 px-5 py-2.5 text-xs text-amber-900">
                    <span class="font-semibold">Review note:</span> {{ $item->canvass_item_review_note }}
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-xs">
                    <thead class="bg-white text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-2.5">Supplier</th>
                            <th class="px-4 py-2.5">Brand</th>
                            <th class="px-4 py-2.5">Sold As</th>
                            <th class="px-4 py-2.5 text-right">Offer Price</th>
                            <th class="px-4 py-2.5 text-center">Offer Units</th>
                            <th class="px-5 py-2.5 text-right">Estimated Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($item->comparison_offers as $offer)
                            <tr class="{{ $offer->is_selected ? 'bg-emerald-50/80' : ($offer->is_lowest ? 'bg-blue-50/40' : '') }}">
                                <td class="px-5 py-3 font-medium text-slate-900">
                                    {{ $offer->supplier_display_name }}
                                    @if ($offer->supplier_is_blacklisted)
                                        <span class="ml-1 rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-semibold text-rose-700">Blacklisted</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-800">{{ $offer->brand_name ?: '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $offer->offer_uom_name ?: 'Unit' }}
                                    @if ((float) $offer->offer_units_per_pack != 1.0)
                                        <span class="text-slate-400">({{ rtrim(rtrim(number_format((float) $offer->offer_units_per_pack, 2), '0'), '.') }}/{{ $offer->offer_uom_name }})</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-slate-800">₱{{ number_format((float) $offer->offer_unit_price, 2) }}</td>
                                <td class="px-4 py-3 text-center text-slate-700">{{ $offer->computed_offer_units }}</td>
                                <td class="px-5 py-3 text-right font-bold text-slate-900">
                                    ₱{{ number_format((float) $offer->computed_total, 2) }}
                                    @if ($offer->is_selected)
                                        <span class="ml-1.5 rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-semibold text-white">Approved</span>
                                    @elseif ($offer->is_lowest)
                                        <span class="ml-1.5 rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold text-blue-700">Lowest</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    {{-- Attachments & Purchaser Signature --}}
    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Supporting Attachments</h3>
            @if ($attachments->isEmpty())
                <p class="mt-2 text-xs text-slate-400">No files attached.</p>
            @else
                <ul class="mt-3 space-y-2">
                    @foreach ($attachments as $att)
                        <li class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs">
                            <a href="{{ route($routePrefix.'.canvass.attachments.download', $att->canvass_attachment_id) }}" class="truncate font-semibold text-blue-700 hover:underline">
                                {{ $att->canvass_attachment_original_name }}
                            </a>
                            <span class="ml-2 shrink-0 text-slate-400">{{ number_format(($att->canvass_attachment_size ?? 0) / 1024, 1) }} KB</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Purchaser Signature</h3>
            @if (! empty($canvass->canvass_purchaser_signature))
                <div class="mt-3 flex flex-col items-center rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                    <img src="{{ $canvass->canvass_purchaser_signature }}" alt="Purchaser signature" class="max-h-20 object-contain">
                    <p class="mt-2 text-xs font-semibold text-slate-700">{{ $canvass->owner_name ?: 'Purchaser' }}</p>
                </div>
            @else
                <p class="mt-2 text-xs text-slate-400">No signature attached yet.</p>
            @endif
        </div>
    </div>
</div>
@endsection
