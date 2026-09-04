@extends('layouts.purchaser-layout')

@section('page-title', 'Urgent Reports')
@section('page-subtitle', 'Reports that need immediate purchasing attention.')

@section('content')
<div>
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
