@extends('layouts.admin-layout')

@section('page-title', 'Canvass Review · '.\App\Support\CanvassWorkflow::displayNumber($canvass))
@section('page-subtitle', 'Approve one winning supplier & brand offer per item, or return items with a note.')

@section('content')
@php
    $portalPrefix = \App\Support\AdminPortal::prefix();
    $badge = \App\Support\CanvassWorkflow::statusBadge((string) $canvass->canvass_status);
@endphp

<div class="mx-auto max-w-5xl space-y-5 pb-10">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a
            href="{{ route($portalPrefix.'.canvass-review.index') }}"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
        >
            <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
            <span>Back to Canvass Review</span>
        </a>

        <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $badge['class'] }}">
            {{ $badge['label'] }}
        </span>
    </div>

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">
            <p class="font-semibold">Please complete your review:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header Summary --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900">{{ \App\Support\CanvassWorkflow::displayNumber($canvass) }}</h2>
                <p class="mt-1 text-xs text-slate-500">
                    Submitted by <span class="font-semibold text-slate-800">{{ $canvass->owner_name ?: 'Purchaser' }}</span>
                    @if ($canvass->canvass_submitted_at)
                        on {{ \Carbon\Carbon::parse($canvass->canvass_submitted_at)->format('M d, Y h:i A') }}
                    @endif
                    @if (! empty($canvass->canvass_request_no))
                        · Request No.: <span class="font-semibold text-blue-700">{{ $canvass->canvass_request_no }}</span>
                    @endif
                </p>
                @if (! empty($canvass->canvass_purpose))
                    <p class="mt-2 text-sm text-slate-700"><span class="font-semibold">Purpose:</span> {{ $canvass->canvass_purpose }}</p>
                @endif
            </div>
            <div class="text-right">
                <p class="text-[11px] font-semibold uppercase text-slate-400">Lowest Est. Total</p>
                <p class="text-lg font-bold text-slate-900">₱{{ number_format((float) $lowestTotalSum, 2) }}</p>
            </div>
        </div>

        @if ($attachments->isNotEmpty() || ! empty($canvass->canvass_purchaser_signature))
            <div class="mt-4 grid grid-cols-1 gap-4 border-t border-slate-100 pt-4 md:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold text-slate-700">Supporting Attachments</p>
                    @if ($attachments->isEmpty())
                        <p class="mt-1 text-xs text-slate-400">None</p>
                    @else
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($attachments as $att)
                                <a
                                    href="{{ route($portalPrefix.'.canvass-review.attachments.download', $att->canvass_attachment_id) }}"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-50"
                                >
                                    <i data-lucide="paperclip" class="h-3.5 w-3.5"></i>
                                    <span class="max-w-[200px] truncate">{{ $att->canvass_attachment_original_name }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-700">Purchaser Signature</p>
                    @if (! empty($canvass->canvass_purchaser_signature))
                        <img src="{{ $canvass->canvass_purchaser_signature }}" alt="Purchaser signature" class="mt-2 max-h-16 rounded border border-slate-200 bg-slate-50 p-1.5 object-contain">
                    @else
                        <p class="mt-1 text-xs text-slate-400">None</p>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <form
        method="POST"
        action="{{ $canAct ? route($portalPrefix.'.canvass-review.decide', $canvass->canvass_id) : '#' }}"
        class="space-y-5"
    >
        @csrf

        @foreach ($items as $idx => $item)
            @php
                $itemId = (int) $item->canvass_item_id;
                $defaultOfferId = old("decisions.{$itemId}.offer_id")
                    ?: ($item->canvass_item_selected_offer_id ?: optional($item->comparison_offers->first())->canvass_offer_id);
                $defaultAction = old("decisions.{$itemId}.action")
                    ?: ($item->canvass_item_status === 'Returned' ? 'return' : 'approve');
                $defaultNote = old("decisions.{$itemId}.note", $item->canvass_item_review_note ?? '');
            @endphp

            <div
                x-data="{ action: @js($defaultAction) }"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
            >
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50 px-5 py-3.5">
                    <div>
                        <span class="text-xs font-bold uppercase text-blue-600">Item {{ $idx + 1 }}</span>
                        <h3 class="text-sm font-bold text-slate-900">
                            {{ $item->canvass_item_name ?: $item->catalog_item_name }}
                            <span class="font-normal text-slate-500">· Qty {{ $item->canvass_item_quantity }} {{ $item->uom_name ?: 'unit(s)' }}</span>
                        </h3>
                    </div>

                    @if ($canAct)
                        <div class="inline-flex rounded-xl border border-slate-200 bg-white p-0.5 text-xs font-semibold">
                            <label class="cursor-pointer rounded-lg px-3 py-1.5 transition" :class="action === 'approve' ? 'bg-emerald-600 text-white' : 'text-slate-600'">
                                <input type="radio" name="decisions[{{ $itemId }}][action]" value="approve" x-model="action" class="sr-only">
                                Approve an offer
                            </label>
                            <label class="cursor-pointer rounded-lg px-3 py-1.5 transition" :class="action === 'return' ? 'bg-amber-500 text-white' : 'text-slate-600'">
                                <input type="radio" name="decisions[{{ $itemId }}][action]" value="return" x-model="action" class="sr-only">
                                Return item
                            </label>
                        </div>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-xs">
                        <thead class="bg-white text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                            <tr>
                                @if ($canAct)
                                    <th class="w-12 px-4 py-2.5 text-center">Pick</th>
                                @endif
                                <th class="px-4 py-2.5">Supplier</th>
                                <th class="px-4 py-2.5">Brand</th>
                                <th class="px-4 py-2.5">Sold As</th>
                                <th class="px-4 py-2.5 text-right">Offer Price</th>
                                <th class="px-4 py-2.5 text-center">Offer Units</th>
                                <th class="px-5 py-2.5 text-right">Estimated Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($item->comparison_offers as $offer)
                                <tr class="{{ $offer->is_selected ? 'bg-emerald-50' : ($offer->is_lowest ? 'bg-blue-50/40' : '') }}">
                                    @if ($canAct)
                                        <td class="px-4 py-3 text-center">
                                            <input
                                                type="radio"
                                                name="decisions[{{ $itemId }}][offer_id]"
                                                value="{{ $offer->canvass_offer_id }}"
                                                @checked((int) $defaultOfferId === (int) $offer->canvass_offer_id)
                                                :disabled="action !== 'approve'"
                                                class="h-4 w-4 text-emerald-600 focus:ring-emerald-500"
                                            >
                                        </td>
                                    @endif
                                    <td class="px-4 py-3 font-semibold text-slate-900">
                                        {{ $offer->supplier_display_name }}
                                        @if ($offer->supplier_is_blacklisted)
                                            <span class="ml-1 rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-semibold text-rose-700">Blacklisted</span>
                                        @elseif (! empty($offer->supplier_caution_risk))
                                            <span class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800">Caution</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-800">
                                        {{ $offer->brand_name ?: '—' }}
                                        @if ($offer->brand_avoid)
                                            <span class="ml-1 rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-semibold text-rose-700">Avoid brand</span>
                                        @endif
                                    </td>
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

                @if ($canAct)
                    <div class="border-t border-slate-100 bg-slate-50/60 px-5 py-3">
                        <label class="mb-1 block text-xs font-semibold text-slate-700">
                            <span x-show="action === 'return'">Reason for returning this item <span class="text-rose-600">*</span></span>
                            <span x-show="action === 'approve'">Item note (optional)</span>
                        </label>
                        <input
                            type="text"
                            name="decisions[{{ $itemId }}][note]"
                            value="{{ $defaultNote }}"
                            :required="action === 'return'"
                            placeholder="Add note for Purchaser..."
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 outline-none focus:border-blue-500"
                        >
                    </div>
                @endif
            </div>
        @endforeach

        @if ($canAct)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <label class="mb-1.5 block text-xs font-semibold text-slate-700">Overall review note (optional)</label>
                <textarea
                    name="canvass_review_note"
                    rows="2"
                    placeholder="Optional summary note for the Purchaser..."
                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-blue-500"
                >{{ old('canvass_review_note', $canvass->canvass_review_note ?? '') }}</textarea>

                <div class="mt-4 flex items-center justify-end gap-3">
                    <a
                        href="{{ route($portalPrefix.'.canvass-review.index') }}"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </a>
                    <button
                        type="submit"
                        class="rounded-xl bg-[#0025cc] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#001db3]"
                    >
                        Finish review
                    </button>
                </div>
            </div>
        @endif
    </form>
</div>
@endsection
