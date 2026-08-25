@extends('layouts.receiving-layout')

@section('title', 'Security settings')

@section('content')

<div class="admin-page mx-auto max-w-3xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="admin-page-title">Security settings</h1>
            <p class="admin-page-subtitle">Password and session controls for your Receiving Officer account.</p>
        </div>

        @include('partials.account.settings-nav', [
            'profileUrl' => route('receiving.profile'),
            'securityUrl' => route('receiving.security'),
            'active' => 'security',
        ])
    </div>

    @include('partials.account.security-settings-panel', [
        'user' => $user,
    ])
</div>

@endsection
