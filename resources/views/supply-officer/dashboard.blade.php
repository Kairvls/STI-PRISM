@extends('layouts.supply-officer-layout')

@section('title', 'Dashboard')

@section('content')
@php
    use Illuminate\Support\Str;
    $firstName = Str::of(trim((string) ($user->user_full_name ?? '')) ?: 'there')->before(' ');
@endphp

<div class="space-y-6">
    <header>
        <p class="text-xs text-slate-500">{{ now()->format('l, F j, Y') }}</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">Hello, {{ $firstName }}</h1>
        <p class="mt-1 text-sm text-slate-500">Create Authority to Purchase from Purchaser-sent RIS, then submit to the School Administrator.</p>
    </header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('supply-officer.incoming-ris') }}" class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-blue-200 hover:bg-blue-50/40">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Incoming RIS</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums text-slate-900">{{ number_format($incomingRis) }}</p>
            <p class="mt-1 text-xs text-slate-500">Ready for ATP</p>
        </a>
        <a href="{{ route('supply-officer.atp.index', ['status' => 'draft']) }}" class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-blue-200 hover:bg-blue-50/40">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">ATP drafts</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums text-slate-900">{{ number_format($atpDraft) }}</p>
            <p class="mt-1 text-xs text-slate-500">Not yet submitted</p>
        </a>
        <a href="{{ route('supply-officer.atp.index', ['status' => 'pending']) }}" class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-amber-200 hover:bg-amber-50/40">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">With School Admin</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums text-slate-900">{{ number_format($atpPending) }}</p>
            <p class="mt-1 text-xs text-slate-500">Awaiting approval</p>
        </a>
        <a href="{{ route('supply-officer.atp.index', ['status' => 'approved']) }}" class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-emerald-200 hover:bg-emerald-50/40">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">To release</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums text-slate-900">{{ number_format($atpToRelease) }}</p>
            <p class="mt-1 text-xs text-slate-500">Approved — pass back to Purchaser</p>
        </a>
    </div>
</div>
@endsection
