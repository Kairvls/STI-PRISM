@extends('layouts.supply-officer-layout')

@section('title', 'Security Settings')

@section('content')
<div class="admin-page mx-auto max-w-3xl">
    @include('supply-officer.profile._header')
    @include('partials.account.microsoft-security-panel', ['user' => $user])
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) window.lucide.createIcons();
    });
</script>
@endsection
