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
                                <a
                                    href="{{ route('supply-officer.incoming-ris.show', $ris->ris_id) }}"
                                    target="_blank"
                                    class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                                >
                                    <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                    View Canvas + RIS
                                </a>
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
@endsection
