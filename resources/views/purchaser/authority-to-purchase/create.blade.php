@extends('layouts.purchaser-layout')

@section('page-title', 'New Authority to Purchase')
@section('page-subtitle', 'Create an ATP from an approved RIS')

@section('content')

<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">New Authority to Purchase</h2>
            <p class="text-sm text-slate-600">Select an approved RIS, choose a supplier, then draft line items for the ATP.</p>
        </div>

        <a href="{{ route('purchaser.atp.index') }}" class="h-10 rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700">Back to ATP list</a>
    </div>

    @if($eligibleRis->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-sm text-slate-600">
            No approved RIS are currently available for Authority to Purchase creation. Approve a RIS first or check the RIS dashboard.
        </div>
    @else
        <form method="POST" action="{{ route('purchaser.atp.store') }}" class="space-y-6 rounded-lg border border-gray-200 bg-white p-6">
            @csrf

            <div class="grid gap-4 lg:grid-cols-2">
                <div>
                    <label class="text-xs font-medium text-gray-500">Approved RIS</label>
                    <select
                        name="authority_purchase_ris_id"
                        required
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                    >
                        <option value="">Select approved RIS</option>
                        @foreach($eligibleRis as $ris)
                            <option value="{{ $ris->ris_id }}"
                                {{ old('authority_purchase_ris_id', $selectedRisId ?? '') == $ris->ris_id ? 'selected' : '' }}
                            >
                                {{ $ris->ris_form_number ?? 'RIS-'.$ris->ris_id }}
                                - {{ $ris->equipment_name ?? $ris->report_unlisted_equipment_name ?? 'Equipment' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs font-medium text-gray-500">Supplier</label>
                    <select
                        name="authority_purchase_supplier_id"
                        required
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                    >
                        <option value="">Select supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->supplier_id }}">
                                @if($supplier->supplier_store_type === 'Physical Store')
                                    {{ $supplier->company_name ?? 'Physical supplier #' . $supplier->supplier_id }}
                                @else
                                    {{ $supplier->shop_name ?? 'Online supplier #' . $supplier->supplier_id }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <div>
                    <label class="text-xs font-medium text-gray-500">Purchase date</label>
                    <input
                        type="date"
                        name="authority_purchase_date"
                        value="{{ old('authority_purchase_date', now()->format('Y-m-d')) }}"
                        required
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                    >
                </div>

                <div>
                    <label class="text-xs font-medium text-gray-500">Received by</label>
                    <input
                        type="text"
                        name="authority_purchase_received_by_name"
                        value="{{ old('authority_purchase_received_by_name') }}"
                        required
                        placeholder="Receiver name"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                    >
                </div>

                <div>
                    <label class="text-xs font-medium text-gray-500">Reference PO / PR</label>
                    <input
                        type="text"
                        name="authority_purchase_reference_po_no"
                        value="{{ old('authority_purchase_reference_po_no') }}"
                        placeholder="PO or PR number"
                        class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                    >
                </div>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500">ATP Items</label>
                <div class="mt-2 overflow-x-auto rounded-lg border border-gray-200">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-3 py-2 text-left">Description</th>
                                <th class="px-3 py-2 text-left">Qty</th>
                                <th class="px-3 py-2 text-left">Unit</th>
                                <th class="px-3 py-2 text-left">Unit price</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($itemIndex = 0; $itemIndex < 8; $itemIndex++)
                                <tr>
                                    <td class="border-t border-gray-200 p-2">
                                        <input
                                            type="text"
                                            name="items[{{ $itemIndex }}][description]"
                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                        >
                                    </td>
                                    <td class="border-t border-gray-200 p-2">
                                        <input
                                            type="number"
                                            name="items[{{ $itemIndex }}][quantity]"
                                            min="1"
                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                        >
                                    </td>
                                    <td class="border-t border-gray-200 p-2">
                                        <input
                                            type="text"
                                            name="items[{{ $itemIndex }}][unit]"
                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                        >
                                    </td>
                                    <td class="border-t border-gray-200 p-2">
                                        <input
                                            type="number"
                                            name="items[{{ $itemIndex }}][unit_price]"
                                            min="0"
                                            step="0.01"
                                            class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                        >
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('purchaser.atp.index') }}" class="h-10 rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700">Cancel</a>
                <button type="submit" class="h-10 rounded-lg bg-blue-600 px-5 text-sm font-medium text-white">Save Draft</button>
            </div>
        </form>
    @endif
</div>

@endsection
