@extends('layouts.maintenance-layout')

@section('title', 'Replacement Recommendation')

@section('content')

<div class="max-w-5xl mx-auto">

    <div class="mb-8">

        <h1 class="text-3xl font-extrabold">
            Replacement Recommendation
        </h1>

    </div>

    <div class="bg-[#1E293B] rounded-3xl p-8">

        <form action="#"
              method="POST">

            @csrf

            <textarea
                rows="8"
                class="w-full bg-[#0F172A] border border-white/10 rounded-2xl px-5 py-4 resize-none"
                placeholder="Enter replacement recommendation..."></textarea>

            <button
                class="bg-orange-500 hover:bg-orange-600 px-8 py-4 rounded-2xl font-bold mt-6">

                Save Recommendation

            </button>

        </form>

    </div>

</div>

@endsection