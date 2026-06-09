@extends('layouts.admin-layout')

@section('title', 'Admin Dashboard')

@section('content')

<h1 class="text-3xl font-bold mb-6">
    Admin Dashboard
</h1>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">

    <div class="bg-white p-5 rounded-2xl shadow text-black">
        Total Users
    </div>

    <div class="bg-white p-5 rounded-2xl shadow text-black">
        Maintenance Personnel
    </div>

    <div class="bg-white p-5 rounded-2xl shadow text-black">
        Purchasers
    </div>

    <div class="bg-white p-5 rounded-2xl shadow text-black">
        Pending Requests
    </div>

</div>

@endsection