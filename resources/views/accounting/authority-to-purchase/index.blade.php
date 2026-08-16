@extends('layouts.accounting-layout')

@section('content')

<div
    x-data="{
        viewOpen: false,
        selectedAtp: null,
        reviseOpen: false,
        rejectOpen: false,
        remarksAtp: null,

        openView(id) {
            this.selectedAtp = id;
            this.viewOpen = true;
        },

        openRevise(id) {
            this.remarksAtp = id;
            this.reviseOpen = true;
            this.rejectOpen = false;
        },

        openReject(id) {
            this.remarksAtp = id;
            this.rejectOpen = true;
            this.reviseOpen = false;
        }
    }"
    class="space-y-6 p-6"
>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div>
        <h2 class="text-2xl font-semibold text-slate-900">Authority to Purchase</h2>
        <p class="text-sm text-slate-600">Review submitted ATP records from Purchaser.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <a href="{{ route('accounting.atp.index', ['status' => 'submitted']) }}" class="rounded-xl border border-gray-200 bg-white p-5 {{ $filter === 'submitted' ? 'ring-2 ring-slate-900' : '' }}">
            <p class="text-sm font-medium text-gray-500">Submitted</p>
            <p class="mt-3 text-3xl font-semibold text-gray-900">{{ $counts['submitted'] }}</p>
        </a>
        <a href="{{ route('accounting.atp.index', ['status' => 'approved']) }}" class="rounded-xl border border-gray-200 bg-white p-5 {{ $filter === 'approved' ? 'ring-2 ring-slate-900' : '' }}">
            <p class="text-sm font-medium text-gray-500">Approved</p>
            <p class="mt-3 text-3xl font-semibold text-gray-900">{{ $counts['approved'] }}</p>
        </a>
        <a href="{{ route('accounting.atp.index', ['status' => 'rejected']) }}" class="rounded-xl border border-gray-200 bg-white p-5 {{ $filter === 'rejected' ? 'ring-2 ring-slate-900' : '' }}">
            <p class="text-sm font-medium text-gray-500">Rejected</p>
            <p class="mt-3 text-3xl font-semibold text-gray-900">{{ $counts['rejected'] }}</p>
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <table class="w-full min-w-[900px] text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">ATP No.</th>
                    <th class="px-4 py-3">RIS</th>
                    <th class="px-4 py-3">Supplier</th>
                    <th class="px-4 py-3">Submitted</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($atps as $atp)
                    @php
                        $isSubmitted = $atp->authority_purchase_status === 'Pending' && $atp->authority_purchase_submitted_at;
                    @endphp
                    <tr>
                        <td class="px-4 py-4 font-medium text-slate-900">{{ $atp->authority_purchase_form_number ?? 'ATP-'.$atp->authority_purchase_id }}</td>
                        <td class="px-4 py-4 text-gray-600">{{ $atp->ris_form_number ?? 'RIS-'.$atp->authority_purchase_ris_id }}</td>
                        <td class="px-4 py-4 text-gray-600">
                            {{ $atp->supplier_store_type === 'Physical Store' ? ($atp->company_name ?? 'Physical supplier') : ($atp->shop_name ?? 'Online supplier') }}
                        </td>
                        <td class="px-4 py-4 text-gray-600">
                            {{ $atp->authority_purchase_submitted_at ? \Carbon\Carbon::parse($atp->authority_purchase_submitted_at)->format('M d, Y') : '—' }}
                        </td>
                        <td class="px-4 py-4">
                            @if($atp->authority_purchase_status === 'Approved')
                                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">Approved</span>
                            @elseif($atp->authority_purchase_status === 'Rejected')
                                <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">Rejected</span>
                            @else
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">Submitted</span>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="openView({{ $atp->authority_purchase_id }})" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700">View</button>
                                @if($isSubmitted)
                                    <form method="POST" action="{{ route('accounting.atp.approve', $atp->authority_purchase_id) }}">
                                        @csrf
                                        <button type="submit" onclick="return confirm('Approve this ATP?')" class="rounded-lg bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700">Approve</button>
                                    </form>
                                    <button type="button" @click="openRevise({{ $atp->authority_purchase_id }})" class="rounded-lg border border-amber-300 px-3 py-2 text-xs font-medium text-amber-700">Revise</button>
                                    <button type="button" @click="openReject({{ $atp->authority_purchase_id }})" class="rounded-lg border border-red-300 px-3 py-2 text-xs font-medium text-red-700">Reject</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-500">No ATP records in this queue.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $atps->links() }}</div>

    @foreach($atps as $atp)
        @php
            $items = $atpItems->get($atp->authority_purchase_id, collect());
            $atpTotal = $items->sum('atp_amount');
            $isSubmitted = $atp->authority_purchase_status === 'Pending' && $atp->authority_purchase_submitted_at;
        @endphp

        <div x-show="viewOpen && selectedAtp === {{ $atp->authority_purchase_id }}" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-black/40" @click="viewOpen = false"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div @click.stop class="relative w-full max-w-5xl rounded-2xl bg-white shadow-xl">
                    <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                        <div>
                            <h3 class="text-xl font-semibold text-slate-900">{{ $atp->authority_purchase_form_number }}</h3>
                            <p class="mt-1 text-sm text-gray-500">RIS: {{ $atp->ris_form_number ?? 'RIS-'.$atp->authority_purchase_ris_id }}</p>
                        </div>
                        <button type="button" @click="viewOpen = false" class="rounded-lg p-2 text-gray-400">✕</button>
                    </div>
                    <div class="max-h-[70vh] overflow-y-auto bg-gray-100 p-8">
                        <div class="mx-auto w-[210mm] bg-white p-10 shadow">
                            <div class="text-center">
                                <div class="text-lg font-bold">STI COLLEGE ORMOC, INC.</div>
                                <div class="mt-4 text-xl font-bold">AUTHORITY TO PURCHASE</div>
                            </div>
                            <div class="mt-8"><strong>To:</strong>
                                {{ $atp->supplier_store_type === 'Physical Store' ? $atp->company_name : $atp->shop_name }}
                            </div>
                            <table class="mt-5 w-full border-collapse border border-black text-sm">
                                <thead>
                                    <tr>
                                        <th class="border border-black p-2">Quantity</th>
                                        <th class="border border-black p-2">Unit</th>
                                        <th class="border border-black p-2">Description</th>
                                        <th class="border border-black p-2">Unit Price</th>
                                        <th class="border border-black p-2">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($items as $item)
                                        <tr>
                                            <td class="border border-black text-center">{{ $item->atp_quantity }}</td>
                                            <td class="border border-black text-center">{{ $item->atp_unit }}</td>
                                            <td class="border border-black px-2">{{ $item->atp_description }}</td>
                                            <td class="border border-black px-2 text-right">{{ number_format((float) $item->atp_unit_price, 2) }}</td>
                                            <td class="border border-black px-2 text-right">{{ number_format((float) $item->atp_amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="border border-black p-4 text-center">No items</td></tr>
                                    @endforelse
                                    @if($items->isNotEmpty())
                                        <tr>
                                            <td colspan="4" class="border border-black pr-4 text-right font-bold">TOTAL</td>
                                            <td class="border border-black px-2 text-right font-bold">{{ number_format((float) $atpTotal, 2) }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                            <div class="mt-10 flex justify-between">
                                <div>
                                    <div class="font-semibold">RECEIVED BY:</div>
                                    <div class="mt-8 border-b border-black text-center">{{ $atp->authority_purchase_received_by_name }}</div>
                                </div>
                                <div class="text-center">
                                    <div>Authorized By</div>
                                    <div class="mt-8 border-b border-black">{{ $atp->authority_purchase_authorized_by_signature }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if($isSubmitted)
                        <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">
                            <form method="POST" action="{{ route('accounting.atp.approve', $atp->authority_purchase_id) }}">
                                @csrf
                                <button type="submit" class="h-10 rounded-lg bg-emerald-50 px-5 text-sm font-medium text-emerald-700">Approve</button>
                            </form>
                            <button type="button" @click="openRevise({{ $atp->authority_purchase_id }})" class="h-10 rounded-lg border border-amber-300 px-5 text-sm font-medium text-amber-700">Request Revision</button>
                            <button type="button" @click="openReject({{ $atp->authority_purchase_id }})" class="h-10 rounded-lg border border-red-300 px-5 text-sm font-medium text-red-700">Reject</button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach

    <div x-show="reviseOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4">
        <div @click.stop class="w-full max-w-lg rounded-2xl bg-white p-6">
            <h3 class="text-lg font-semibold">Request revision</h3>
            <p class="mt-1 text-sm text-gray-500">The Purchaser can edit and resubmit this ATP.</p>
            @foreach($atps as $atp)
                <form x-show="remarksAtp === {{ $atp->authority_purchase_id }}" method="POST" action="{{ route('accounting.atp.revise', $atp->authority_purchase_id) }}" class="mt-4 space-y-3">
                    @csrf
                    <textarea name="remarks" required rows="4" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="What needs to be revised?"></textarea>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="reviseOpen = false" class="h-10 rounded-lg border px-4 text-sm">Cancel</button>
                        <button type="submit" class="h-10 rounded-lg bg-amber-600 px-4 text-sm text-white">Send back</button>
                    </div>
                </form>
            @endforeach
        </div>
    </div>

    <div x-show="rejectOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4">
        <div @click.stop class="w-full max-w-lg rounded-2xl bg-white p-6">
            <h3 class="text-lg font-semibold">Reject ATP</h3>
            @foreach($atps as $atp)
                <form x-show="remarksAtp === {{ $atp->authority_purchase_id }}" method="POST" action="{{ route('accounting.atp.reject', $atp->authority_purchase_id) }}" class="mt-4 space-y-3">
                    @csrf
                    <textarea name="remarks" required rows="4" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Rejection reason"></textarea>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="rejectOpen = false" class="h-10 rounded-lg border px-4 text-sm">Cancel</button>
                        <button type="submit" class="h-10 rounded-lg bg-red-600 px-4 text-sm text-white">Reject</button>
                    </div>
                </form>
            @endforeach
        </div>
    </div>
</div>

@endsection
