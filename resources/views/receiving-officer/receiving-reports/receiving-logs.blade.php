@extends('layouts.receiving-layout')

@section('content')

<div class="admin-page space-y-6">
    <div>
        <h1 class="admin-page-title">Receiving Logs</h1>
        <p class="admin-page-subtitle">Audit trail of inspections, accepted reports, inventory updates, and returns for correction.</p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Timestamp</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Action</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Reference</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Officer</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr>
                        <td class="px-5 py-4 text-sm text-gray-500">Aug 10, 2026 2:14 PM</td>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">Receiving report saved</td>
                        <td class="px-5 py-4 text-sm text-gray-700">RIS 00000038</td>
                        <td class="px-5 py-4 text-sm text-gray-700">Receiving Officer</td>
                        <td class="px-5 py-4 text-sm text-gray-500">Items complete. Inventory updated.</td>
                    </tr>
                    <tr>
                        <td class="px-5 py-4 text-sm text-gray-500">Aug 10, 2026 2:12 PM</td>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">Physical validation passed</td>
                        <td class="px-5 py-4 text-sm text-gray-700">RIS 00000038</td>
                        <td class="px-5 py-4 text-sm text-gray-700">Receiving Officer</td>
                        <td class="px-5 py-4 text-sm text-gray-500">Quantity, model, and serial numbers matched.</td>
                    </tr>
                    <tr>
                        <td class="px-5 py-4 text-sm text-gray-500">Aug 02, 2026 11:06 AM</td>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">Returned for correction</td>
                        <td class="px-5 py-4 text-sm text-gray-700">RIS 00000033</td>
                        <td class="px-5 py-4 text-sm text-gray-700">Receiving Officer</td>
                        <td class="px-5 py-4 text-sm text-gray-500">2 toner cartridges missing from delivery.</td>
                    </tr>
                    <tr>
                        <td class="px-5 py-4 text-sm text-gray-500">Aug 02, 2026 10:58 AM</td>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">Remarks added</td>
                        <td class="px-5 py-4 text-sm text-gray-700">RIS 00000033</td>
                        <td class="px-5 py-4 text-sm text-gray-700">Receiving Officer</td>
                        <td class="px-5 py-4 text-sm text-gray-500">PO quantity does not match delivered quantity.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
