@extends('layouts.maintenance-layout')

@section('title', 'Add Findings')

@section('content')

<div class="max-w-5xl mx-auto">

    <div class="mb-8">

        <h1 class="text-3xl font-extrabold">
            Maintenance Findings
        </h1>

    </div>

    <div class="bg-[#1E293B] rounded-3xl p-8">

        <form action="#"
              method="POST">

            @csrf

            <div class="mb-6">

                <label class="block mb-3 font-semibold">

                    Findings Description

                </label>

                <textarea
                    rows="8"
                    class="w-full bg-[#0F172A] border border-white/10 rounded-2xl px-5 py-4 resize-none"
                    placeholder="Enter maintenance findings..."></textarea>

            </div>

            <button
                class="bg-yellow-500 hover:bg-yellow-600 text-black px-8 py-4 rounded-2xl font-bold">

                Save Findings

            </button>

        </form>

    </div>

</div>

@endsection