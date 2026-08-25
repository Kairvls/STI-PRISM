@extends('layouts.receiving-layout')

@section('title', 'Profile settings')

@section('content')

<div class="admin-page mx-auto max-w-3xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="admin-page-title">Profile settings</h1>
            <p class="admin-page-subtitle">Manage how your Receiving Officer account appears in PRISM.</p>
        </div>

        @include('partials.account.settings-nav', [
            'profileUrl' => route('receiving.profile'),
            'securityUrl' => route('receiving.security'),
            'active' => 'profile',
        ])
    </div>

    @include('partials.account.profile-settings-panel', [
        'user' => $user,
        'roleLabel' => 'Receiving Officer',
    ])
</div>

@endsection
