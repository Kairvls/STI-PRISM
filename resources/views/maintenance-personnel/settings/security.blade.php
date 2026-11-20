@extends('layouts.maintenance-layout')

@section('title', 'Security Settings')

@section('content')
<div class="mx-auto max-w-3xl">
    @include('maintenance-personnel.settings._header')
    @include('partials.account.microsoft-security-panel', ['user' => $user])
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) window.lucide.createIcons();
    });
</script>
@endsection
