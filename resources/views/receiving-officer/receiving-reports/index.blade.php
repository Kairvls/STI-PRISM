@extends('layouts.receiving-layout')

@section('content')

@php
    $checklist = [
        'Quantity of items',
        'Product Model',
        'Brand',
        'Specifications',
        'Item Condition',
        'Physical Damage Inspection',
        'Serial Number',
        'Warranty Information',
        'Total Price',
        'Purchase Order Match',
        'Supplier Information',
        'Delivered Item Completeness',
    ];
@endphp

<div class="admin-page space-y-6">
    <div>
        <h1 class="admin-page-title">Pending Receiving Reports</h1>
        <p class="admin-page-subtitle">Review delivery documents, physically validate items, then accept or return the report.</p>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Pending</p>
            <p class="mt-3 font-['Outfit'] text-3xl font-bold text-amber-600">3</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Ready to Accept</p>
            <p class="mt-3 font-['Outfit'] text-3xl font-bold text-emerald-600">1</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">For Correction</p>
            <p class="mt-3 font-['Outfit'] text-3xl font-bold text-rose-600">1</p>
        </div>
    </div>

    <div class="mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-900">Receiving Officer Review</h2>
            <p class="mt-1 text-xs text-gray-500">Received items, quantity, supplier, purchase details, official receipts, AP and RIS references</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">RIS Ref</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Received Items</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Qty</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Supplier</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Purchase Details</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Official Receipt</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">AP Ref</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">00000042</td>
                        <td class="px-5 py-4 text-sm text-gray-700">Epson L3250 Printer</td>
                        <td class="px-5 py-4 text-sm text-gray-700">2</td>
                        <td class="px-5 py-4 text-sm text-gray-700">TechSource PH</td>
                        <td class="px-5 py-4 text-sm text-gray-700">PO-2026-118 · ₱18,400.00</td>
                        <td class="px-5 py-4 text-sm text-gray-700">OR-88421</td>
                        <td class="px-5 py-4 text-sm text-gray-700">ATP-00019</td>
                        <td class="px-5 py-4"><span class="rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Pending</span></td>
                    </tr>
                    <tr>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">00000044</td>
                        <td class="px-5 py-4 text-sm text-gray-700">HDMI Cable 5m</td>
                        <td class="px-5 py-4 text-sm text-gray-700">10</td>
                        <td class="px-5 py-4 text-sm text-gray-700">OfficeLink Trading</td>
                        <td class="px-5 py-4 text-sm text-gray-700">PO-2026-121 · ₱3,250.00</td>
                        <td class="px-5 py-4 text-sm text-gray-700">OR-88455</td>
                        <td class="px-5 py-4 text-sm text-gray-700">ATP-00021</td>
                        <td class="px-5 py-4"><span class="rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Pending</span></td>
                    </tr>
                    <tr>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">00000046</td>
                        <td class="px-5 py-4 text-sm text-gray-700">Dell Monitor 24"</td>
                        <td class="px-5 py-4 text-sm text-gray-700">4</td>
                        <td class="px-5 py-4 text-sm text-gray-700">PC Express</td>
                        <td class="px-5 py-4 text-sm text-gray-700">PO-2026-124 · ₱32,000.00</td>
                        <td class="px-5 py-4 text-sm text-gray-700">OR-88490</td>
                        <td class="px-5 py-4 text-sm text-gray-700">ATP-00022</td>
                        <td class="px-5 py-4"><span class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">Mismatch</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-900">Physical Validation</h2>
            <p class="mt-1 text-xs text-gray-500">Confirm the items against the purchase before saving the receiving report.</p>
            <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                @foreach($checklist as $item)
                    <label class="flex items-center gap-2 rounded-lg border border-gray-100 px-3 py-2 text-sm text-gray-700">
                        <input type="checkbox" class="rounded border-gray-300 text-slate-900 focus:ring-slate-900">
                        {{ $item }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-900">Items correct and complete?</h2>
            <p class="mt-1 text-xs text-gray-500">Accept to save records and update inventory, or return with remarks.</p>

            <div class="mt-5 space-y-3">
                <button type="button" class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                    Yes — Add receiving report
                </button>
                <p class="text-xs text-gray-400">Saves receiving records, updates inventory stocks, and writes delivery history.</p>
            </div>

            <div class="mt-6 border-t border-gray-100 pt-5">
                <label class="block text-sm font-medium text-gray-700">Receiving remarks</label>
                <textarea rows="4" class="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-rose-200" placeholder="Describe missing items, damage, or mismatches..."></textarea>
                <button type="button" class="mt-3 w-full rounded-lg border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100">
                    No — Return for correction
                </button>
            </div>
        </div>
    </div>
</div>

@endsection
