@extends ("layouts.maintenance-layout")

@section ("title", "Add Findings")

@section ("content")
    <div class="mx-auto max-w-5xl">
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold">Maintenance Findings</h1>
        </div>

        <div class="rounded-3xl bg-[#1E293B] p-8">
            <form action="#" method="POST">
                @csrf

                <div class="mb-6">
                    <label class="mb-3 block font-semibold">
                        Findings Description
                    </label>

                    <textarea
                        rows="8"
                        class="w-full resize-none rounded-2xl border border-white/10 bg-[#0F172A] px-5 py-4"
                        placeholder="Enter maintenance findings..."
                    ></textarea>
                </div>

                <button
                    class="rounded-2xl bg-yellow-500 px-8 py-4 font-bold text-black hover:bg-yellow-600"
                >
                    Save Findings
                </button>
            </form>
        </div>
    </div>

@endsection
