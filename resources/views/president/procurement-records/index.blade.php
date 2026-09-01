@extends('layouts.president-layout')

@section('title', 'Procurement Records')

@section('content')
@php use App\Support\ProcurementPaymentPath; @endphp

@include('partials.procurement-records-ui')

<div class="pr-module fade-in">
    <div class="pr-hero">
        <p class="pr-hero-kicker">Decision Reports</p>
        <h1 class="pr-hero-title">Compiled Records</h1>
        <p class="pr-hero-sub">Records forwarded by Accounting for presidential archive and reference.</p>
    </div>

    <div class="pr-surface">
        @if($packages instanceof \Illuminate\Pagination\LengthAwarePaginator ? $packages->count() : count($packages))
            <div class="pr-list">
                @foreach($packages as $pkg)
                    <a href="{{ route('president.procurement-records.show', $pkg->package_id) }}" class="pr-list-row">
                        <div class="pr-list-main">
                            <div class="pr-list-ref">{{ $pkg->authority_purchase_form_number ?? '—' }}</div>
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
                                    <i data-lucide="calendar" class="h-3 w-3"></i>
                                    {{ optional(\Carbon\Carbon::parse($pkg->package_forwarded_to_president_at))->format('M d, Y') ?? '—' }}
                                </span>
                            </div>
                        </div>
                        <span class="pr-list-action">
                            View
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
                <p class="pr-empty-title">No records yet</p>
                <p class="pr-empty-sub">Forwarded procurement records will appear here.</p>
            </div>
        @endif
    </div>

    @if($packages instanceof \Illuminate\Pagination\LengthAwarePaginator && $packages->hasPages())
        <div class="mt-4">{{ $packages->links('pagination.president') }}</div>
    @endif
</div>
@endsection
