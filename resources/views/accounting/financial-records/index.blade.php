@extends('layouts.accounting-layout')

@section('title', 'Financial Records')

@section('content')
<div class="acc-page fade-in">
    <div class="acc-page-header">
        <div>
            <p class="acc-page-kicker">Records</p>
            <h1 class="acc-page-title">Financial Records</h1>
            <p class="acc-page-subtitle">Processed Accounting documents live under History.</p>
        </div>
        <a href="/accounting/history" class="acc-btn acc-btn-primary">Open History</a>
    </div>
    <div class="acc-empty">This page redirects to History in the live routes. Use History for processed ATP, Request Checks, and liquidations.</div>
</div>
@endsection
