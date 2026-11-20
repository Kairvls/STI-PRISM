@extends('layouts.admin-layout')

@section('title', 'Borrow fees')

@section('content')

<div class="admin-page mx-auto max-w-3xl space-y-6">
    <div>
        <h1 class="admin-page-title">Borrow fees</h1>
        <p class="admin-page-subtitle">Late fee per day and the flat damage fee used when equipment is returned.</p>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.settings.borrow-fees.update') }}" class="space-y-5">
            @csrf

            <label class="block text-sm font-semibold text-slate-700">
                Late fee per day
                <input
                    type="number"
                    name="late_fee_per_day"
                    required
                    min="0"
                    max="999999.99"
                    step="0.01"
                    value="{{ old('late_fee_per_day', number_format($rates['late_fee_per_day'] ?? 0, 2, '.', '')) }}"
                    class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-slate-400 focus:ring-slate-300"
                >
                <span class="mt-1 block text-xs font-normal text-slate-500">Charged for each day past the expected return date.</span>
                @error('late_fee_per_day')
                    <span class="mt-1 block text-xs font-normal text-red-600">{{ $message }}</span>
                @enderror
            </label>

            <label class="block text-sm font-semibold text-slate-700">
                Damage fee
                <input
                    type="number"
                    name="damage_fee"
                    required
                    min="0"
                    max="999999.99"
                    step="0.01"
                    value="{{ old('damage_fee', number_format($rates['damage_fee'] ?? 0, 2, '.', '')) }}"
                    class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-slate-400 focus:ring-slate-300"
                >
                <span class="mt-1 block text-xs font-normal text-slate-500">Added when the item is marked damaged during the manual inspection.</span>
                @error('damage_fee')
                    <span class="mt-1 block text-xs font-normal text-red-600">{{ $message }}</span>
                @enderror
            </label>

            <button
                type="submit"
                class="rounded-xl bg-[#0025cc] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#001fad]"
            >
                Save fees
            </button>
        </form>
    </div>
</div>

@endsection
