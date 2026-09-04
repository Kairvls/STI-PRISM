@extends('layouts.admin-layout')
@section('content')
<div x-data="{ viewOpen:false, selectedLiq:null, reviseOpen:false, rejectOpen:false, remarksId:null }" class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Liquidation Report Approval</h1>
        <p class="text-sm text-gray-600">Endorse and recommend approval after Accounting has checked the report.</p>
    </div>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <a href="{{ route('admin.liq.index', ['status'=>'pending']) }}" class="rounded-xl border bg-white p-5 {{ $filter==='pending'?'ring-2 ring-slate-900':'' }}"><p class="text-sm text-gray-500">Pending</p><p class="mt-3 text-3xl font-semibold">{{ $counts['pending'] }}</p></a>
        <a href="{{ route('admin.liq.index', ['status'=>'approved']) }}" class="rounded-xl border bg-white p-5 {{ $filter==='approved'?'ring-2 ring-slate-900':'' }}"><p class="text-sm text-gray-500">Approved</p><p class="mt-3 text-3xl font-semibold">{{ $counts['approved'] }}</p></a>
        <a href="{{ route('admin.liq.index', ['status'=>'rejected']) }}" class="rounded-xl border bg-white p-5 {{ $filter==='rejected'?'ring-2 ring-slate-900':'' }}"><p class="text-sm text-gray-500">Rejected</p><p class="mt-3 text-3xl font-semibold">{{ $counts['rejected'] }}</p></a>
    </div>
    <div class="overflow-x-auto overflow-hidden rounded-xl border bg-white">
        <table class="w-full min-w-[1000px] text-sm">
            <thead class="border-b bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-2.5">No.</th>
                    <th class="px-4 py-2.5">RR</th>
                    <th class="px-4 py-2.5">Employee</th>
                    <th class="px-4 py-2.5 text-right">Amount</th>
                    <th class="px-4 py-2.5">Status</th>
                    <th class="px-4 py-2.5">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($reports as $liq)
                    @php $reviewable = $liq->liquidation_report_status === 'Pending Admin Approval' || ($liq->liquidation_report_status === 'Under Review' && $liq->liquidation_report_review_stage === 'admin'); @endphp
                    <tr class="hover:bg-gray-50/80">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $liq->liquidation_report_form_number }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $liq->receiving_report_form_number ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $liq->liquidation_report_employee_name ?: '—' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-gray-900">{{ $liq->liquidation_report_amount_advance !== null ? '₱'.number_format((float)$liq->liquidation_report_amount_advance, 2) : '—' }}</td>
                        <td class="px-4 py-3">@include('accounting.partials.status-badge', ['status' => $liq->liquidation_report_status])</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1.5">
                                <button type="button" @click="selectedLiq={{ $liq->liquidation_report_id }}; viewOpen=true; fetch('{{ route('admin.liq.start-review', $liq->liquidation_report_id) }}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}})" class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs text-gray-700 hover:bg-gray-50">View</button>
                                @if($reviewable)
                                    <form method="POST" action="{{ route('admin.liq.approve', $liq->liquidation_report_id) }}">@csrf<button class="rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs text-emerald-700 hover:bg-emerald-100" onclick="return confirm('Endorse and approve?')">Approve</button></form>
                                    <button type="button" @click="remarksId={{ $liq->liquidation_report_id }}; reviseOpen=true" class="rounded-lg border border-amber-300 px-2.5 py-1.5 text-xs text-amber-700 hover:bg-amber-50">Revise</button>
                                    <button type="button" @click="remarksId={{ $liq->liquidation_report_id }}; rejectOpen=true" class="rounded-lg border border-red-300 px-2.5 py-1.5 text-xs text-red-700 hover:bg-red-50">Reject</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-gray-500">No liquidation reports in this queue.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $reports->links() }}</div>
    @foreach($reports as $liq)
        @php $liqItems = $items->get($liq->liquidation_report_id, collect())->values(); @endphp
        <div x-show="viewOpen && selectedLiq === {{ $liq->liquidation_report_id }}" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-black/40" @click="viewOpen=false"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div @click.stop class="w-full max-w-6xl rounded-2xl bg-white shadow-xl">
                    <div class="flex justify-between border-b px-6 py-5"><h3 class="text-xl font-semibold">{{ $liq->liquidation_report_form_number }}</h3><button type="button" @click="viewOpen=false">✕</button></div>
                    <div class="max-h-[75vh] overflow-y-auto bg-gray-100 p-6">@include('partials.liquidation-report-paper', ['editable'=>false,'liq'=>$liq,'rows'=>$liqItems])</div>
                </div>
            </div>
        </div>
    @endforeach
    <div x-show="reviseOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <form method="POST" :action="'/admin/liquidation-reports/'+remarksId+'/revise'" @click.stop class="w-full max-w-lg rounded-2xl bg-white p-6">@csrf<textarea name="remarks" required rows="4" class="w-full rounded-lg border p-3 text-sm"></textarea><div class="mt-4 flex justify-end gap-2"><button type="button" @click="reviseOpen=false" class="rounded-lg border px-4 py-2 text-sm">Cancel</button><button class="rounded-lg bg-slate-800 px-4 py-2 text-sm text-white">Send back</button></div></form>
    </div>
    <div x-show="rejectOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <form method="POST" :action="'/admin/liquidation-reports/'+remarksId+'/reject'" @click.stop class="w-full max-w-lg rounded-2xl bg-white p-6">@csrf<textarea name="remarks" required rows="4" class="w-full rounded-lg border p-3 text-sm"></textarea><div class="mt-4 flex justify-end gap-2"><button type="button" @click="rejectOpen=false" class="rounded-lg border px-4 py-2 text-sm">Cancel</button><button class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white">Reject</button></div></form>
    </div>
</div>
<style>[x-cloak]{display:none!important}</style>
@endsection
