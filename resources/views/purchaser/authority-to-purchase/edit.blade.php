@extends('layouts.purchaser-layout')

@section('page-title', 'Edit Authority to Purchase')
@section('page-subtitle', 'Update an ATP draft before submission')

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
            <h2 class="text-2xl font-semibold text-slate-900">Edit ATP Draft</h2>
            <p class="text-sm text-slate-600">Update the draft before submitting it for approval.</p>
        </div>
        <a href="{{ route('purchaser.atp.show', $atp->authority_purchase_id) }}" class="h-10 rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700">Back to details</a>
    </div>

    <form method="POST" action="{{ route('purchaser.atp.update', $atp->authority_purchase_id) }}" class="space-y-6 rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @method('PUT')

        <div class="grid gap-4 lg:grid-cols-2">
            <div>
                <label class="text-xs font-medium text-gray-500">Approved RIS</label>
                <input type="text" disabled value="{{ $atp->authority_purchase_ris_id ? 'RIS-'.$atp->authority_purchase_ris_id : '—' }}" class="mt-1 h-10 w-full rounded-lg border border-gray-300 bg-gray-100 px-3 text-sm text-gray-700">
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500">Supplier</label>
                <select
                    name="authority_purchase_supplier_id"
                    required
                    class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                >
                    @foreach($suppliers as $supplier)
                        <option
                            value="{{ $supplier->supplier_id }}"
                            {{ $supplier->supplier_id === $atp->authority_purchase_supplier_id ? 'selected' : '' }}
                        >
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
                    value="{{ old('authority_purchase_date', $atp->authority_purchase_date) }}"
                    required
                    class="mt-1 h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                >
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500">Received by</label>
                <input
                    type="text"
                    name="authority_purchase_received_by_name"
                    value="{{ old('authority_purchase_received_by_name', $atp->authority_purchase_received_by_name) }}"
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
                    value="{{ old('authority_purchase_reference_po_no', $atp->authority_purchase_reference_po_no) }}"
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
                        @foreach($items as $itemIndex => $item)
                            <tr>
                                <td class="border-t border-gray-200 p-2">
                                    <input
                                        type="text"
                                        name="items[{{ $itemIndex }}][description]"
                                        value="{{ old('items.'.$itemIndex.'.description', $item->atp_description) }}"
                                        class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                    >
                                </td>
                                <td class="border-t border-gray-200 p-2">
                                    <input
                                        type="number"
                                        name="items[{{ $itemIndex }}][quantity]"
                                        value="{{ old('items.'.$itemIndex.'.quantity', $item->atp_quantity) }}"
                                        min="1"
                                        class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                    >
                                </td>
                                <td class="border-t border-gray-200 p-2">
                                    <input
                                        type="text"
                                        name="items[{{ $itemIndex }}][unit]"
                                        value="{{ old('items.'.$itemIndex.'.unit', $item->atp_unit) }}"
                                        class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                    >
                                </td>
                                <td class="border-t border-gray-200 p-2">
                                    <input
                                        type="number"
                                        name="items[{{ $itemIndex }}][unit_price]"
                                        value="{{ old('items.'.$itemIndex.'.unit_price', $item->atp_unit_price) }}"
                                        min="0"
                                        step="0.01"
                                        class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm"
                                    >
                                </td>
                            </tr>
                        @endforeach
                        @for($itemIndex = count($items); $itemIndex < max(8, count($items) + 1); $itemIndex++)
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
            <a href="{{ route('purchaser.atp.show', $atp->authority_purchase_id) }}" class="h-10 rounded-lg border border-gray-300 px-5 text-sm font-medium text-gray-700">Cancel</a>
            <button type="submit" class="h-10 rounded-lg bg-blue-600 px-5 text-sm font-medium text-white">Update Draft</button>
        </div>
    </form>
</div>

@endsection
