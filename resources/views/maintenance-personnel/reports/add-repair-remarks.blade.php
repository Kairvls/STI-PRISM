@extends('layouts.maintenance-layout')

@section('title', 'Repair Remarks')

@section('content')

<div class="max-w-5xl mx-auto">

    <div class="mb-8">

        <h1 class="text-3xl font-extrabold">
            Repair Remarks
        </h1>

    </div>

    <div class="bg-[#1E293B] rounded-3xl p-8">

        <form action="#"
              method="POST">

            @csrf

            <textarea
                rows="8"
                class="w-full bg-[#0F172A] border border-white/10 rounded-2xl px-5 py-4 resize-none"
                placeholder="Enter repair remarks..."></textarea>

            <button
                class="bg-blue-600 hover:bg-blue-700 px-8 py-4 rounded-2xl font-bold mt-6">

                Save Repair Remarks

            </button>

        </form>

    </div>

</div>

@endsection