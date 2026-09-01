@extends('layouts.purchaser-layout')

@section('page-title', 'Urgent Reports')
@section('page-subtitle', 'Reports that need immediate purchasing attention.')

@section('content')
<div>
    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-5 flex items-baseline justify-end gap-2">
        <span class="text-4xl font-black tracking-tight text-slate-950">
            {{ $reports->total() }}
        </span>
        <span class="text-xs font-medium uppercase tracking-[0.16em] text-slate-400">
            Urgent Reports
        </span>
    </div>

    @include('components.tables.reports-table', [
        'reports' => $reports,
        'context' => 'purchaser-urgent',
    ])
</div>
@endsection
