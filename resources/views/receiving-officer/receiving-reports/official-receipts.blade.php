@extends('layouts.receiving-layout')

@section('content')

<div class="admin-page space-y-6">
    <div>
        <h1 class="admin-page-title">Official Receipts</h1>
        <p class="admin-page-subtitle">OR numbers recorded when a delivery is accepted.</p>
    </div>

    @include('layouts.partials.receiving-query-error')

    <div data-ro-table data-ro-default-filter="all" class="overflow-hidden rounded-[18px] border border-gray-200 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.03)]">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Official receipts</h2>
                        <p class="mt-1 text-xs text-gray-500">Receipt numbers captured during inspection.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @include('layouts.partials.receiving-export-pdf', ['exportSection' => 'receipts'])
                        <div class="receiving-total-count rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">
                            {{ $rows->count() }} total
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-end">
                    @include('layouts.partials.receiving-filters', ['searchId' => 'receivingOrSearch', 'placeholder' => 'Search OR, RIS, ATP, supplier...'])
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">OR</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">RIS / ATP</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Supplier</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                        @php
                            $rowSearch = trim(implode(' ', [
                                $row->official_receipt ?? '',
                                $row->ris_form_number ?? '',
                                $row->authority_purchase_form_number ?? '',
                                $row->supplier_name ?? '',
                            ]));
                        @endphp
                        <tr data-ro-status="all" data-ro-search="{{ $rowSearch }}">
                            <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $row->official_receipt }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->ris_form_number ?: $row->authority_purchase_form_number }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $row->supplier_name }}</td>
                            <td class="px-5 py-4 text-sm text-gray-500">{{ $row->received_at ? \Carbon\Carbon::parse($row->received_at)->format('M d, Y') : '—' }}</td>
                        </tr>
                    @empty
                    @endforelse
                    <tr class="receiving-empty-row" @if($rows->count()) style="display:none" @endif>
                        <td colspan="4" class="px-5 py-16 text-center text-sm text-gray-400">Waiting for an accepted delivery with an official receipt number.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @include('layouts.partials.receiving-table-pager')
    </div>
</div>

@endsection
