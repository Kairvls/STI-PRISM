@extends('layouts.receiving-layout')

@section('content')

<div class="px-1 pb-8">
    <div class="mb-7">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Receiving</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-950">Delivery History</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-500">Completed receiving reports after items were validated and inventory was updated.</p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Date</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">RIS Ref</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Items</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Supplier</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">OR / AP</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Inventory</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Result</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr>
                        <td class="px-5 py-4 text-sm text-gray-500">Aug 10, 2026</td>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">00000038</td>
                        <td class="px-5 py-4 text-sm text-gray-700">Wireless Keyboard × 6</td>
                        <td class="px-5 py-4 text-sm text-gray-700">OfficeLink Trading</td>
                        <td class="px-5 py-4 text-sm text-gray-700">OR-88301 / ATP-00015</td>
                        <td class="px-5 py-4 text-sm text-gray-700">Stock updated</td>
                        <td class="px-5 py-4"><span class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Accepted</span></td>
                    </tr>
                    <tr>
                        <td class="px-5 py-4 text-sm text-gray-500">Aug 08, 2026</td>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">00000036</td>
                        <td class="px-5 py-4 text-sm text-gray-700">UPS 650VA × 3</td>
                        <td class="px-5 py-4 text-sm text-gray-700">PC Express</td>
                        <td class="px-5 py-4 text-sm text-gray-700">OR-88288 / ATP-00014</td>
                        <td class="px-5 py-4 text-sm text-gray-700">Stock updated</td>
                        <td class="px-5 py-4"><span class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Accepted</span></td>
                    </tr>
                    <tr>
                        <td class="px-5 py-4 text-sm text-gray-500">Aug 02, 2026</td>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">00000033</td>
                        <td class="px-5 py-4 text-sm text-gray-700">Toner Cartridge × 4</td>
                        <td class="px-5 py-4 text-sm text-gray-700">TechSource PH</td>
                        <td class="px-5 py-4 text-sm text-gray-700">OR-88210 / ATP-00012</td>
                        <td class="px-5 py-4 text-sm text-gray-700">Not updated</td>
                        <td class="px-5 py-4"><span class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">Returned</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
