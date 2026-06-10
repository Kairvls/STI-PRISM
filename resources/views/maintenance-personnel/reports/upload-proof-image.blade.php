@extends('layouts.maintenance-layout')

@section('title', 'Upload Proof Image')

@section('content')

<div class="max-w-5xl mx-auto">

    <div class="mb-8">

        <h1 class="text-3xl font-extrabold">
            Upload Proof Images
        </h1>

    </div>

    <div class="bg-[#1E293B] rounded-3xl p-8">

        <form action="#"
              method="POST"
              enctype="multipart/form-data">

            @csrf

            <div class="mb-6">

                <label class="block mb-3 font-semibold">

                    Before Repair Image

                </label>

                <input
                    type="file"
                    class="w-full bg-[#0F172A] border border-white/10 rounded-2xl p-4">

            </div>

            <div class="mb-6">

                <label class="block mb-3 font-semibold">

                    During Repair Image

                </label>

                <input
                    type="file"
                    class="w-full bg-[#0F172A] border border-white/10 rounded-2xl p-4">

            </div>

            <div class="mb-6">

                <label class="block mb-3 font-semibold">

                    After Repair Image

                </label>

                <input
                    type="file"
                    class="w-full bg-[#0F172A] border border-white/10 rounded-2xl p-4">

            </div>

            <button
                class="bg-purple-600 hover:bg-purple-700 px-8 py-4 rounded-2xl font-bold">

                Upload Images

            </button>

        </form>

    </div>

</div>

@endsection