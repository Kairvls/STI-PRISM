@extends('layouts.accounting-layout')

@section('title', 'Financial Records')

@section('content')
<div class="acc-page fade-in">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm leading-6 text-gray-500">Processed Accounting documents live under History.</p>
        </div>
        <a href="/accounting/history" class="acc-btn acc-btn-primary">Open History</a>
    </div>
    <div class="mt-4 acc-empty">Use History for processed ATP, Request Checks, and liquidations.</div>
</div>
@endsection
