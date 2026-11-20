@extends('layouts.president-layout')

@section('title', 'Security Settings')

@section('content')
<div class="admin-page mx-auto max-w-3xl">
    @include('president.profile._header')
    @include('partials.account.microsoft-security-panel', ['user' => $user])
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) window.lucide.createIcons();
    });
</script>
@endsection
