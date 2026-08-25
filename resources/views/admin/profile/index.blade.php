@extends('layouts.admin-layout')

@section('title', 'Profile settings')

@section('content')

<div class="admin-page mx-auto max-w-3xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="admin-page-title">Profile settings</h1>
            <p class="admin-page-subtitle">Manage how your Administrator account appears in PRISM.</p>
        </div>

        @include('partials.account.settings-nav', [
            'profileUrl' => route('admin.profile'),
            'securityUrl' => route('admin.security'),
            'active' => 'profile',
        ])
    </div>

    @include('partials.account.profile-settings-panel', [
        'user' => $user,
        'roleLabel' => 'Administrator',
    ])
</div>

@endsection
