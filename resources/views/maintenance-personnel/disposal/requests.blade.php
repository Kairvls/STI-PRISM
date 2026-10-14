@extends('layouts.maintenance-layout')

@section('title', 'Disposal')

@section('content')
    @php
        $baseUrl = $isAdminPortal ? '/admin/disposal/requests' : '/maintenance/disposal/requests';
        $tabs = [
            'Pending' => ['Pending', 'clock'],
            'Approved' => ['Approved', 'check-circle-2'],
            'Rejected' => ['Rejected', 'x-circle'],
            'Cancelled' => ['Cancelled', 'ban'],
        ];
        $statusStyles = [
            'Pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'Approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'Rejected' => 'bg-rose-50 text-rose-700 ring-rose-200',
            'Cancelled' => 'bg-slate-100 text-slate-600 ring-slate-200',
        ];
        $formatDate = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('M j, Y g:i A') : '—';
    @endphp

    <div class="space-y-6">
        @include('maintenance-personnel.disposal.partials.tabs')

        <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            <i data-lucide="shield-check" class="mt-0.5 h-4 w-4 shrink-0 text-slate-400"></i>
            <p>
                @if ($isAdminPortal)
                    Disposals asked for by Maintenance wait here. The equipment only moves to Disposal when you approve.
                @else
                    Your disposals need the Administrator's approval. The equipment stays where it is until the request is approved.
                @endif
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a
                href="{{ $baseUrl }}?status=all"
                class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-[13px] font-semibold transition {{ $status === null ? 'bg-[#0025cc] text-white' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
            >
                <i data-lucide="list" class="h-4 w-4"></i>
                All
                <span class="rounded-md px-1.5 py-0.5 text-[11px] font-bold {{ $status === null ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ array_sum($counts) }}</span>
            </a>
            @foreach ($tabs as $key => [$label, $icon])
                <a
                    href="{{ $baseUrl }}?status={{ $key }}"
                    class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-[13px] font-semibold transition {{ $status === $key ? 'bg-[#0025cc] text-white' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
                >
                    <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
                    {{ $label }}
                    <span class="rounded-md px-1.5 py-0.5 text-[11px] font-bold {{ $status === $key ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $counts[$key] ?? 0 }}</span>
                </a>
            @endforeach
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Requested</th>
                            <th class="px-5 py-3">Equipment</th>
                            <th class="px-5 py-3">Disposal details</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($requests as $row)
                            @php
                                $items = $row->items;
                                $isPending = $row->disposal_request_status === 'Pending';
                                $isMine = (int) $row->disposal_request_requested_by === (int) auth()->id();
                            @endphp
                            <tr class="align-top">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-900">{{ $row->requester_name ?? 'Unknown user' }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $formatDate($row->disposal_request_requested_at) }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-900">
                                        {{ $items->count() }} item{{ $items->count() === 1 ? '' : 's' }}
                                    </p>
                                    <ul class="mt-1 space-y-0.5 text-xs text-slate-600">
                                        @foreach ($items->take(4) as $item)
                                            <li>
                                                <a href="{{ url('/maintenance/equipment/view/'.$item->equipment_id) }}" class="hover:text-[#0025cc] hover:underline">{{ $item->equipment_name }}</a>
                                                <span class="text-slate-400">· {{ $item->equipment_asset_tag ?: 'No tag' }} · {{ $item->room_name ?? '—' }}</span>
                                            </li>
                                        @endforeach
                                        @if ($items->count() > 4)
                                            <li class="text-slate-400">+{{ $items->count() - 4 }} more</li>
                                        @endif
                                    </ul>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="max-w-xs text-sm text-slate-800">{{ $row->disposal_request_reason }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $row->disposal_request_method ?: 'No method' }}
                                        @if ($row->disposal_request_location)
                                            · {{ $row->disposal_request_location }}
                                        @endif
                                        @if ($row->disposal_request_residual_value !== null)
                                            · ₱{{ number_format((float) $row->disposal_request_residual_value, 2) }} residual
                                        @endif
                                    </p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusStyles[$row->disposal_request_status] ?? $statusStyles['Cancelled'] }}">
                                        {{ $row->disposal_request_status }}
                                    </span>
                                    @if (! $isPending)
                                        <p class="mt-1.5 text-xs text-slate-500">
                                            {{ $row->disposal_request_status === 'Cancelled' ? 'Cancelled' : 'By '.($row->reviewer_name ?? 'Administrator') }}
                                            · {{ $formatDate($row->disposal_request_reviewed_at) }}
                                        </p>
                                    @endif
                                    @if ($row->disposal_request_review_note)
                                        <p class="mt-1 max-w-xs text-xs text-slate-600">Note: {{ $row->disposal_request_review_note }}</p>
                                    @endif
                                    @if ($row->disposal_request_status === 'Approved' && $row->disposal_request_result)
                                        <p class="mt-1 max-w-xs text-xs text-emerald-700">{{ $row->disposal_request_result }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        @if ($isPending && $isAdminPortal)
                                            <form method="POST" action="{{ $baseUrl.'/'.$row->disposal_request_id.'/approve' }}" onsubmit="return confirm('Approve this disposal? The equipment will be moved to Disposal now.')">
                                                @csrf
                                                <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white transition hover:bg-blue-800">
                                                    <i data-lucide="check" class="h-4 w-4"></i>
                                                    Approve
                                                </button>
                                            </form>
                                            <button
                                                type="button"
                                                onclick="openRejectDisposalRequest({{ (int) $row->disposal_request_id }})"
                                                class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-rose-50 px-3 text-xs font-semibold text-rose-700 transition hover:bg-rose-100"
                                            >
                                                <i data-lucide="x" class="h-4 w-4"></i>
                                                Reject
                                            </button>
                                        @elseif ($isPending && $isMine)
                                            <form method="POST" action="{{ $baseUrl.'/'.$row->disposal_request_id.'/cancel' }}" onsubmit="return confirm('Cancel this disposal request?')">
                                                @csrf
                                                <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-slate-100 px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-200">
                                                    <i data-lucide="ban" class="h-4 w-4"></i>
                                                    Cancel request
                                                </button>
                                            </form>
                                        @elseif ($isPending)
                                            <span class="text-xs text-slate-400">Waiting for the Administrator</span>
                                        @else
                                            <span class="text-xs text-slate-400">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">
                                    No {{ $status ? strtolower($status) : '' }} disposal requests.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($requests->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">{{ $requests->links() }}</div>
            @endif
        </div>
    </div>

    @if ($isAdminPortal)
        <div
            id="rejectDisposalRequestModal"
            class="fixed inset-0 z-50 hidden items-center justify-center bg-[#0b1220]/70 p-4"
            onclick="if (event.target === this) closeRejectDisposalRequest()"
        >
            <form id="rejectDisposalRequestForm" method="POST" class="w-full max-w-md overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]">
                @csrf
                <div class="px-6 pb-5 pt-6">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-rose-50 text-rose-600">
                        <i data-lucide="x-circle" class="h-4 w-4"></i>
                    </div>
                    <h2 class="text-lg font-semibold tracking-tight text-slate-950">Reject disposal</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">The equipment stays where it is. The requester is notified with your reason.</p>
                </div>
                <div class="border-y border-slate-100 px-6 py-5">
                    <label for="rejectDisposalRequestNote" class="mb-2 block text-sm font-medium text-slate-700">Reason <span class="text-red-500">*</span></label>
                    <textarea id="rejectDisposalRequestNote" name="note" rows="3" required maxlength="1000" placeholder="e.g. Still repairable, send it to maintenance first" class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-slate-400 focus:ring-4 focus:ring-slate-100"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 px-6 py-4">
                    <button type="button" onclick="closeRejectDisposalRequest()" class="h-10 rounded-xl px-4 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="h-10 rounded-xl bg-rose-600 px-5 text-sm font-medium text-white transition hover:bg-rose-700">Reject disposal</button>
                </div>
            </form>
        </div>

        <script>
            function openRejectDisposalRequest(id) {
                const modal = document.getElementById('rejectDisposalRequestModal');
                document.getElementById('rejectDisposalRequestForm').action = @json($baseUrl) + '/' + id + '/reject';
                document.getElementById('rejectDisposalRequestNote').value = '';
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                if (window.lucide) window.lucide.createIcons();
            }

            function closeRejectDisposalRequest() {
                const modal = document.getElementById('rejectDisposalRequestModal');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        </script>
    @endif
@endsection
