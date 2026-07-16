@extends('layouts.president-layout')

@section('title', 'President Dashboard')

@section('content')

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">

    <div>

        <p class="text-sm font-medium text-gray-500">

            President Overview

        </p>

        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-gray-900">

            President Dashboard

        </h1>

        <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-500">

            Review approval requests, monitor decisions, and stay updated with the latest notifications.

        </p>

    </div>

    <div class="flex items-center gap-2">

        <a href="/president/approvals" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 text-sm font-semibold text-white transition hover:bg-gray-800">

            <i data-lucide="clipboard-check" class="h-4 w-4"></i>

            Review Approvals

        </a>

    </div>

</div>

<div class="mt-7 grid grid-cols-1 gap-4 md:grid-cols-4">

    {{-- Pending approvals --}}

    <a href="/president/approvals" class="group rounded-xl border border-gray-200 bg-white p-5 transition hover:border-gray-300 hover:shadow-sm">

        <div class="flex items-start justify-between gap-4">

            <div>

                <p class="text-sm font-medium text-gray-500">Pending Approvals</p>

                <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">{{ $pendingApprovalsCount ?? 0 }}</p>

                <p class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">

                    <span>Needs your decision</span>

                    <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>

                </p>

            </div>

            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-700">

                <i data-lucide="clock-3" class="h-5 w-5"></i>

            </div>

        </div>

    </a>

    {{-- Approved decisions --}}

    <a href="/president/reports/approved" class="group rounded-xl border border-gray-200 bg-white p-5 transition hover:border-gray-300 hover:shadow-sm">

        <div class="flex items-start justify-between gap-4">

            <div>

                <p class="text-sm font-medium text-gray-500">Approved Decisions</p>

                <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">{{ $approvedDecisionsCount ?? 0 }}</p>

                <p class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">

                    <span>Successful approvals</span>

                    <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>

                </p>

            </div>

            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">

                <i data-lucide="circle-check-big" class="h-5 w-5"></i>

            </div>

        </div>

    </a>

    {{-- Rejected decisions --}}

    <a href="/president/reports/rejected" class="group rounded-xl border border-gray-200 bg-white p-5 transition hover:border-gray-300 hover:shadow-sm">

        <div class="flex items-start justify-between gap-4">

            <div>

                <p class="text-sm font-medium text-gray-500">Rejected Decisions</p>

                <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">{{ $rejectedDecisionsCount ?? 0 }}</p>

                <p class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">

                    <span>Review rejection reasons</span>

                    <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>

                </p>

            </div>

            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-50 text-rose-700">

                <i data-lucide="x-circle" class="h-5 w-5"></i>

            </div>

        </div>

    </a>

    {{-- Notifications --}}

    <a href="/president/notifications" class="group rounded-xl border border-gray-200 bg-white p-5 transition hover:border-gray-300 hover:shadow-sm">

        <div class="flex items-start justify-between gap-4">

            <div>

                <p class="text-sm font-medium text-gray-500">Notifications</p>

                <p class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">{{ $notificationsCount ?? 0 }}</p>

                <p class="mt-5 flex items-center gap-1.5 text-xs font-medium text-gray-500">

                    <span>Actionable updates</span>

                    <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"></i>

                </p>

            </div>

            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-700">

                <i data-lucide="bell" class="h-5 w-5"></i>

            </div>

        </div>

    </a>

</div>

<div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">

    {{-- President Procurement Packet --}}

    <section class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-6">

        <div class="flex items-start justify-between gap-4">

            <div>

                <h2 class="text-sm font-semibold text-gray-900">President Procurement Packet</h2>

                <p class="mt-1 text-sm text-gray-500">RIS and procurement documents you need to review before signing decisions.</p>

            </div>



        </div>

        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">

            {{-- RIS --}}

            <div class="rounded-lg bg-gray-50 p-4">

                <p class="text-xs font-semibold text-gray-900">RIS</p>

                <p class="mt-1 text-xs text-gray-500">Request Information Sheet (procurement request details).</p>

                <a href="/president/approvals" class="mt-3 inline-flex items-center gap-2 text-xs font-semibold text-gray-900 transition hover:text-gray-700">

                    Review RIS

                    <i data-lucide="arrow-right" class="h-4 w-4"></i>

                </a>

            </div>

            {{-- Attached Documents --}}

            <div class="rounded-lg bg-gray-50 p-4">

                <p class="text-xs font-semibold text-gray-900">Attached Documents</p>

                <p class="mt-1 text-xs text-gray-500">Supporting files uploaded with the procurement request.</p>

                <a href="/president/approvals" class="mt-3 inline-flex items-center gap-2 text-xs font-semibold text-gray-900 transition hover:text-gray-700">

                    View Attachments

                    <i data-lucide="arrow-right" class="h-4 w-4"></i>

                </a>

            </div>

            {{-- Budget Proposal --}}

            <div class="rounded-lg bg-gray-50 p-4">

                <p class="text-xs font-semibold text-gray-900">Budget Proposal</p>

                <p class="mt-1 text-xs text-gray-500">Budget allocation and funding details for the request.</p>

                <a href="/president/approvals" class="mt-3 inline-flex items-center gap-2 text-xs font-semibold text-gray-900 transition hover:text-gray-700">

                    Review Budget

                    <i data-lucide="arrow-right" class="h-4 w-4"></i>

                </a>

            </div>

            {{-- Supplier Comparison --}}

            <div class="rounded-lg bg-gray-50 p-4">

                <p class="text-xs font-semibold text-gray-900">Supplier Comparison</p>

                <p class="mt-1 text-xs text-gray-500">Comparison of supplier offers and related notes.</p>

                <div class="mt-3 flex items-center gap-2">

                    <span class="inline-flex items-center rounded-lg bg-white px-3 py-1 text-[11px] font-semibold text-slate-600 border border-slate-200">

                        {{ $pendingApprovalsCount ?? 0 }} pending supplier comparison connection(s)

                    </span>

                    <a href="/president/reports/approved" class="inline-flex items-center gap-2 text-xs font-semibold text-gray-900 transition hover:text-gray-700">

                        View Decisions

                        <i data-lucide="arrow-right" class="h-4 w-4"></i>

                    </a>

                </div>

            </div>

            {{-- Technical Justification --}}

            <div class="rounded-lg bg-gray-50 p-4 sm:col-span-2">

                <p class="text-xs font-semibold text-gray-900">Technical Justification</p>

                <p class="mt-1 text-xs text-gray-500">Technical basis for the chosen equipment/supply.</p>

                <a href="/president/approvals/history" class="mt-3 inline-flex items-center gap-2 text-xs font-semibold text-gray-900 transition hover:text-gray-700">

                    Review Justifications

                    <i data-lucide="arrow-right" class="h-4 w-4"></i>

                </a>

            </div>

        </div>

    </section>

    {{-- Decision Summary --}}

    <aside class="rounded-xl border border-gray-200 bg-white p-6">

        <h2 class="text-sm font-semibold text-gray-900">Decision Summary</h2>

        <p class="mt-1 text-sm text-gray-500">Quick access to all approved and rejected procurement decisions.</p>

        <div class="mt-4 flex items-center gap-2">

            <a href="/president/reports/approved" class="inline-flex h-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 text-xs font-semibold text-emerald-800 transition hover:bg-emerald-100">

                Approved

            </a>

            <a href="/president/reports/rejected" class="inline-flex h-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 px-3 text-xs font-semibold text-rose-800 transition hover:bg-rose-100">

                Rejected

            </a>

        </div>

        <div class="mt-5 rounded-lg bg-gray-50 p-4">

            <p class="text-xs font-semibold text-gray-900">Monthly decision reports</p>

            <p class="mt-1 text-xs text-gray-500">Review aggregated decision trends for better oversight.</p>

            <a href="/president/reports/monthly-summary" class="mt-3 inline-flex items-center gap-2 text-xs font-semibold text-gray-900 transition hover:text-gray-700">

                View Monthly Summary

                <i data-lucide="arrow-right" class="h-4 w-4"></i>

            </a>

        </div>

        <div class="mt-4 rounded-lg border border-dashed border-gray-200 bg-gray-50 p-5">

            <p class="text-xs font-semibold text-gray-900">Recent Activity</p>

            <p class="mt-1 text-xs text-gray-500">You currently have {{ $notificationsCount ?? 0 }} notification(s) requiring attention.</p>

            <a href="/president/notifications" class="mt-3 inline-flex items-center gap-2 text-xs font-semibold text-gray-900 transition hover:text-gray-700">

                Go to Notifications

                <i data-lucide="arrow-right" class="h-4 w-4"></i>

            </a>

        </div>

    </aside>

</div>

<script>
    // Ensure lucide icons render on this page.
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) {
            lucide.createIcons();
        }
    });
</script>

@endsection

