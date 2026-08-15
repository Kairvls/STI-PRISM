@extends('layouts.receiving-layout')

@section('content')

<div class="admin-page space-y-6">
    <div>
        <h1 class="admin-page-title">Supplier Records</h1>
        <p class="admin-page-subtitle">Supplier information tied to deliveries, receipts, and purchase orders.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-900">TechSource PH</h2>
            <p class="mt-1 text-xs text-gray-500">IT equipment · Cebu City</p>
            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div><p class="text-xs text-gray-400">Contact</p><p class="text-gray-800">Ana Reyes</p></div>
                <div><p class="text-xs text-gray-400">Phone</p><p class="text-gray-800">032-555-2100</p></div>
                <div><p class="text-xs text-gray-400">Deliveries</p><p class="text-gray-800">8</p></div>
                <div><p class="text-xs text-gray-400">Last delivery</p><p class="text-gray-800">Aug 12, 2026</p></div>
            </dl>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-900">OfficeLink Trading</h2>
            <p class="mt-1 text-xs text-gray-500">Office supplies · Ormoc City</p>
            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div><p class="text-xs text-gray-400">Contact</p><p class="text-gray-800">Mark Dela Cruz</p></div>
                <div><p class="text-xs text-gray-400">Phone</p><p class="text-gray-800">053-555-0199</p></div>
                <div><p class="text-xs text-gray-400">Deliveries</p><p class="text-gray-800">11</p></div>
                <div><p class="text-xs text-gray-400">Last delivery</p><p class="text-gray-800">Aug 10, 2026</p></div>
            </dl>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-900">PC Express</h2>
            <p class="mt-1 text-xs text-gray-500">Computers & peripherals · Tacloban</p>
            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div><p class="text-xs text-gray-400">Contact</p><p class="text-gray-800">Lia Santos</p></div>
                <div><p class="text-xs text-gray-400">Phone</p><p class="text-gray-800">053-555-7741</p></div>
                <div><p class="text-xs text-gray-400">Deliveries</p><p class="text-gray-800">5</p></div>
                <div><p class="text-xs text-gray-400">Last delivery</p><p class="text-gray-800">Aug 08, 2026</p></div>
            </dl>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-900">PowerTech Electrical</h2>
            <p class="mt-1 text-xs text-gray-500">Power equipment · Cebu City</p>
            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div><p class="text-xs text-gray-400">Contact</p><p class="text-gray-800">Jon Villanueva</p></div>
                <div><p class="text-xs text-gray-400">Phone</p><p class="text-gray-800">032-555-0904</p></div>
                <div><p class="text-xs text-gray-400">Deliveries</p><p class="text-gray-800">3</p></div>
                <div><p class="text-xs text-gray-400">Last delivery</p><p class="text-gray-800">Jul 28, 2026</p></div>
            </dl>
        </div>
    </div>
</div>

@endsection
