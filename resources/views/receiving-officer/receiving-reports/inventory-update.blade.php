@extends('layouts.receiving-layout')

@section('content')

<div class="admin-page space-y-6">
    <div>
        <h1 class="admin-page-title">Inventory Update</h1>
        <p class="admin-page-subtitle">Stock created when a receiving report is accepted.</p>
    </div>

    @include('layouts.partials.receiving-query-error')

    <div class="overflow-hidden rounded-[18px] border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-5 py-4">
            @include('layouts.partials.receiving-filters')
        </div>
        <table class="w-full text-left">
            <thead class="border-b border-gray-200 bg-gray-50">
                <tr>
                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Item</th>
                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Qty added</th>
                    <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($items as $item)
                    <tr>
                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $item->receiving_report_item_article ?? $item->receiving_item_description ?? '—' }}</td>
                        <td class="px-5 py-4 text-sm text-gray-700">{{ $item->receiving_report_item_quantity ?? $item->receiving_item_quantity ?? '—' }}</td>
                        <td class="px-5 py-4 text-sm text-gray-500">{{ \Carbon\Carbon::parse($item->receiving_report_date ?? $item->receiving_report_created_at)->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-5 py-16 text-center text-sm text-gray-400">Waiting for an accepted delivery. Inventory lines appear after you accept an approved ATP.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
