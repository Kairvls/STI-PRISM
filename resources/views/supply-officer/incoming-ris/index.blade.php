@extends('layouts.supply-officer-layout')

@section('title', 'Incoming RIS')

@section('content')
@php
    $total = method_exists($records, 'total') ? $records->total() : $records->count();
@endphp

<div class="overflow-hidden rounded-xl border border-gray-200">
    <div class="border-b border-gray-100 px-5 py-5">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex items-center gap-3">
                <h2 class="text-base font-semibold text-gray-950">Records</h2>
                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-500">
                    {{ $total }}
                </span>
            </div>

            <form method="GET" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                    </svg>
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search RIS, Request No., or purpose..."
                        class="box-border h-9 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm leading-none text-gray-700 outline-none transition focus:border-gray-300 focus:bg-white sm:w-72"
                    >
                </div>

                <button
                    type="submit"
                    class="box-border inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#0025cc] px-4 text-[13px] font-semibold leading-none text-white transition hover:bg-blue-800"
                >
                    <i data-lucide="search" class="h-4 w-4 shrink-0"></i>
                    Search
                </button>

                @if (filled($search))
                    <a
                        href="{{ route('supply-officer.incoming-ris') }}"
                        class="box-border inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 px-4 text-sm font-medium leading-none text-gray-600 transition hover:bg-gray-50"
                    >
                        Clear
                    </a>
                @endif
            </form>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-sm">
            <thead class="bg-gray-50/70">
                <tr class="border-b border-gray-100">
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">RIS</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Purpose</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Sent by</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Received</th>
                    <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse ($records as $ris)
                    <tr class="transition hover:bg-gray-50/70">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500">
                                    <i data-lucide="package-open" class="h-4 w-4"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">
                                        {{ \App\Support\RisWorkflow::formNumber($ris) }}
                                    </p>
                                    @if (filled($ris->ris_request_no ?? null))
                                        <div class="mt-0.5">
                                            @include('partials.procurement-request-no', [
                                                'requestNo' => $ris->ris_request_no,
                                                'inline' => true,
                                                'showLabel' => false,
                                            ])
                                        </div>
                                    @else
                                        <p class="mt-0.5 text-xs text-gray-400">No Request No.</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-gray-600">
                            {{ \Illuminate\Support\Str::limit($ris->ris_purpose_description ?? '—', 80) }}
                        </td>
                        <td class="px-5 py-4 text-gray-600">
                            {{ $ris->sent_by_name ?? 'Purchaser' }}
                        </td>
                        <td class="whitespace-nowrap px-5 py-4 text-gray-600">
                            @if ($ris->ris_sent_to_supply_at)
                                {{ \Carbon\Carbon::parse($ris->ris_sent_to_supply_at)->format('M j, Y') }}
                                <p class="mt-0.5 text-xs text-gray-400">
                                    {{ \Carbon\Carbon::parse($ris->ris_sent_to_supply_at)->format('g:i A') }}
                                </p>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap items-center justify-end gap-1.5">
                                <button
                                    type="button"
                                    data-ris-preview
                                    data-preview-url="{{ route('supply-officer.dashboard.preview-ris', $ris->ris_id) }}"
                                    data-atp-url="{{ route('supply-officer.atp.index', ['selected_ris' => $ris->ris_id]) }}"
                                    data-preview-title="{{ \App\Support\RisWorkflow::formNumber($ris) }}"
                                    class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                                >
                                    <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                    View Canvas + RIS
                                </button>
                                <a
                                    href="{{ route('supply-officer.atp.index', ['selected_ris' => $ris->ris_id]) }}"
                                    class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#0025cc] px-3 text-xs font-semibold text-white transition hover:bg-[#001db3]"
                                >
                                    <i data-lucide="file-plus-2" class="h-3.5 w-3.5"></i>
                                    Create ATP
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-gray-400">
                                <i data-lucide="package-open" class="h-5 w-5"></i>
                            </div>
                            <p class="mt-4 font-medium text-gray-700">No approved RIS waiting for ATP</p>
                            <p class="mt-1 text-sm text-gray-400">Purchasers must send Canvas + RIS here first.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (method_exists($records, 'hasPages') && $records->hasPages())
        <div class="border-t border-gray-100 px-5 py-4">
            {{ $records->links('pagination.president') }}
        </div>
    @endif
</div>

<div id="incomingRisPreview" class="fixed inset-0 z-[200] hidden items-start justify-center overflow-y-auto bg-black/50 p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="incomingRisPreviewTitle">
    <div class="my-auto w-full max-w-6xl overflow-hidden rounded-xl bg-white shadow-2xl">
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div class="min-w-0">
                <h2 id="incomingRisPreviewTitle" class="truncate text-lg font-semibold text-slate-950"></h2>
                <p class="mt-0.5 text-sm text-slate-500">Approved Canvas + RIS</p>
            </div>
            <button type="button" id="incomingRisPreviewClose" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>
        <div id="incomingRisPreviewBody" class="max-h-[72vh] overflow-auto bg-slate-50"></div>
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4">
            <button type="button" id="incomingRisPreviewDismiss" class="text-sm font-medium text-slate-500 hover:text-slate-900">Close</button>
            <a id="incomingRisPreviewAtp" href="#" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#0025cc] px-4 text-sm font-semibold text-white hover:bg-[#001db3]">
                <i data-lucide="file-plus-2" class="h-4 w-4"></i>
                Create ATP
            </a>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('incomingRisPreview');
        const title = document.getElementById('incomingRisPreviewTitle');
        const body = document.getElementById('incomingRisPreviewBody');
        const atpLink = document.getElementById('incomingRisPreviewAtp');
        if (!modal || !body || !atpLink) return;

        const skeleton = '<div class="space-y-3 p-5" aria-hidden="true">'
            + '<div class="h-8 w-1/3 animate-pulse rounded-md bg-slate-200"></div>'
            + '<div class="h-40 animate-pulse rounded-lg bg-slate-200"></div>'
            + '<div class="h-24 animate-pulse rounded-lg bg-slate-200"></div>'
            + '</div>';

        function closePreview() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            body.innerHTML = '';
            document.body.style.overflow = '';
        }

        async function openPreview(button) {
            title.textContent = button.dataset.previewTitle || 'Canvas + RIS';
            atpLink.href = button.dataset.atpUrl || '#';
            body.innerHTML = skeleton;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
            if (window.lucide) lucide.createIcons();

            try {
                const response = await fetch(button.dataset.previewUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                });
                if (!response.ok) throw new Error('Preview failed');
                body.innerHTML = await response.text();
                if (window.lucide) lucide.createIcons();
            } catch (error) {
                body.innerHTML = '<p class="px-5 py-10 text-center text-sm text-red-600">Could not load this Canvas + RIS.</p>';
            }
        }

        document.querySelectorAll('[data-ris-preview]').forEach((button) => {
            button.addEventListener('click', () => openPreview(button));
        });

        document.getElementById('incomingRisPreviewClose')?.addEventListener('click', closePreview);
        document.getElementById('incomingRisPreviewDismiss')?.addEventListener('click', closePreview);
        modal.addEventListener('click', (event) => {
            if (event.target === modal) closePreview();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) closePreview();
        });
    })();
</script>
@endsection
