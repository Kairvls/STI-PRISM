@extends('layouts.receiving-layout')

@section('content')

<div class="px-1 pb-8">
    <div class="mb-7">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Receiving</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-950">Delivered Items</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-500">Items already physically received and accepted into inventory.</p>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Items this month</p>
            <p class="mt-3 font-['Outfit'] text-3xl font-bold text-slate-900">12</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total value</p>
            <p class="mt-3 font-['Outfit'] text-3xl font-bold text-slate-900">₱86,150</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Suppliers</p>
            <p class="mt-3 font-['Outfit'] text-3xl font-bold text-slate-900">4</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Item</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Brand / Model</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Qty</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Serial No.</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Supplier</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Condition</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Received</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">Wireless Keyboard</td>
                        <td class="px-5 py-4 text-sm text-gray-700">Logitech MK270</td>
                        <td class="px-5 py-4 text-sm text-gray-700">6</td>
                        <td class="px-5 py-4 text-sm text-gray-700">LG-MK270-001</td>
                        <td class="px-5 py-4 text-sm text-gray-700">OfficeLink Trading</td>
                        <td class="px-5 py-4"><span class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Good</span></td>
                        <td class="px-5 py-4 text-sm text-gray-500">Aug 10, 2026</td>
                    </tr>
                    <tr>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">UPS 650VA</td>
                        <td class="px-5 py-4 text-sm text-gray-700">APC BV650</td>
                        <td class="px-5 py-4 text-sm text-gray-700">3</td>
                        <td class="px-5 py-4 text-sm text-gray-700">APC-650-8821</td>
                        <td class="px-5 py-4 text-sm text-gray-700">PC Express</td>
                        <td class="px-5 py-4"><span class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Good</span></td>
                        <td class="px-5 py-4 text-sm text-gray-500">Aug 08, 2026</td>
                    </tr>
                    <tr>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">Network Switch 24-port</td>
                        <td class="px-5 py-4 text-sm text-gray-700">TP-Link TL-SG1024D</td>
                        <td class="px-5 py-4 text-sm text-gray-700">1</td>
                        <td class="px-5 py-4 text-sm text-gray-700">TPL-1024-4410</td>
                        <td class="px-5 py-4 text-sm text-gray-700">TechSource PH</td>
                        <td class="px-5 py-4"><span class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Good</span></td>
                        <td class="px-5 py-4 text-sm text-gray-500">Aug 05, 2026</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
