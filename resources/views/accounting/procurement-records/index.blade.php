@extends('layouts.accounting-layout')

@section('title', 'Compiled Procurement Records')

@section('content')
@php use App\Support\ProcurementPaymentPath; @endphp

@include('partials.procurement-records-ui')

<div class="pr-module acc-page fade-in">
    <div class="pr-hero">
        <p class="pr-hero-kicker">Transactions</p>
        <h1 class="pr-hero-title">Compiled Records</h1>
        <p class="pr-hero-sub">Review purchaser-submitted document checklists and forward to President for records.</p>
    </div>

    <div class="pr-surface">
        @if($packages instanceof \Illuminate\Pagination\LengthAwarePaginator ? $packages->count() : count($packages))
            <div class="pr-list">
                @foreach($packages as $pkg)
                    @php
                        $status = str_replace('_', ' ', ucfirst($pkg->package_status));
                        $statusClass = match ($pkg->package_status) {
                            'submitted_to_accounting' => 'pr-badge--amber',
                            'forwarded_to_president' => 'pr-badge--green',
                            default => '',
                        };
                    @endphp
                    <a href="{{ route('accounting.procurement-records.show', $pkg->package_id) }}" class="pr-list-row">
                        <div class="pr-list-main">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="pr-list-ref">{{ $pkg->authority_purchase_form_number ?? '—' }}</span>
                                <span class="pr-badge {{ $statusClass }}">{{ $status }}</span>
                            </div>
                            <div class="pr-list-meta">
                                <span>
                                    <i data-lucide="clipboard-list" class="h-3 w-3"></i>
                                    RIS {{ $pkg->ris_form_number ?? '—' }}
                                </span>
                                <span>
                                    <i data-lucide="git-branch" class="h-3 w-3"></i>
                                    {{ ProcurementPaymentPath::label($pkg->package_payment_path) }}
                                </span>
                                <span>
                                    <i data-lucide="clock" class="h-3 w-3"></i>
                                    {{ optional(\Carbon\Carbon::parse($pkg->package_submitted_to_accounting_at))->format('M d, Y g:i A') ?? '—' }}
                                </span>
                            </div>
                        </div>
                        <span class="pr-list-action">
                            Review
                            <i data-lucide="chevron-right"></i>
                        </span>
                    </a>
                @endforeach
            </div>
        @else
            <div class="pr-empty">
                <div class="pr-empty-icon">
                    <i data-lucide="folder-open"></i>
                </div>
                <p class="pr-empty-title">No compiled records yet</p>
                <p class="pr-empty-sub">Purchaser-submitted packages will appear here for review.</p>
            </div>
        @endif
    </div>

    @if($packages instanceof \Illuminate\Pagination\LengthAwarePaginator && $packages->hasPages())
        <div class="mt-4">{{ $packages->links('pagination.president') }}</div>
    @endif
</div>
@endsection
