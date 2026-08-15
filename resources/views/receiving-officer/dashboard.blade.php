@extends('layouts.receiving-layout')

@section('content')

<div class="admin-page space-y-6">
    <div>
        <h1 class="admin-page-title">Dashboard</h1>
        <p class="admin-page-subtitle">Review pending deliveries, validate items, and keep receiving records complete.</p>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <a href="/receiving/reports" class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm transition hover:border-gray-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Pending Receiving Reports</p>
            <p class="mt-3 font-['Outfit'] text-3xl font-bold text-amber-600">3</p>
            <p class="mt-1 text-xs text-gray-400">Awaiting inspection</p>
        </a>
        <a href="/receiving/delivered-items" class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm transition hover:border-gray-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Delivered Items</p>
            <p class="mt-3 font-['Outfit'] text-3xl font-bold text-slate-900">12</p>
            <p class="mt-1 text-xs text-gray-400">Received this month</p>
        </a>
        <a href="/receiving/supplier-records" class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm transition hover:border-gray-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Supplier Records</p>
            <p class="mt-3 font-['Outfit'] text-3xl font-bold text-slate-900">6</p>
            <p class="mt-1 text-xs text-gray-400">Active suppliers</p>
        </a>
        <a href="/receiving/history" class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm transition hover:border-gray-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Delivery History</p>
            <p class="mt-3 font-['Outfit'] text-3xl font-bold text-emerald-600">9</p>
            <p class="mt-1 text-xs text-gray-400">Completed deliveries</p>
        </a>
        <a href="/receiving/logs" class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm transition hover:border-gray-300">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Receiving Logs</p>
            <p class="mt-3 font-['Outfit'] text-3xl font-bold text-slate-900">18</p>
            <p class="mt-1 text-xs text-gray-400">Audit entries</p>
        </a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-900">Recent pending reports</h2>
            <p class="mt-1 text-xs text-gray-500">Items waiting for physical validation</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">RIS Ref</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Received Items</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Quantity</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Supplier</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">00000042</td>
                        <td class="px-5 py-4 text-sm text-gray-700">Epson L3250 Printer</td>
                        <td class="px-5 py-4 text-sm text-gray-700">2</td>
                        <td class="px-5 py-4 text-sm text-gray-700">TechSource PH</td>
                        <td class="px-5 py-4"><span class="rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Pending Inspection</span></td>
                    </tr>
                    <tr>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">00000044</td>
                        <td class="px-5 py-4 text-sm text-gray-700">HDMI Cable 5m</td>
                        <td class="px-5 py-4 text-sm text-gray-700">10</td>
                        <td class="px-5 py-4 text-sm text-gray-700">OfficeLink Trading</td>
                        <td class="px-5 py-4"><span class="rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Pending Inspection</span></td>
                    </tr>
                    <tr>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">00000046</td>
                        <td class="px-5 py-4 text-sm text-gray-700">Dell Monitor 24"</td>
                        <td class="px-5 py-4 text-sm text-gray-700">4</td>
                        <td class="px-5 py-4 text-sm text-gray-700">PC Express</td>
                        <td class="px-5 py-4"><span class="rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Pending Inspection</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
