@extends ("layouts.maintenance-layout")

@section ("title", "Upload Proof Image")

@section ("content")
    <div class="mx-auto max-w-5xl">
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold">Upload Proof Images</h1>
        </div>

        <div class="rounded-3xl bg-[#1E293B] p-8">
            <form action="#" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-6">
                    <label class="mb-3 block font-semibold">
                        Before Repair Image
                    </label>

                    <input
                        type="file"
                        class="w-full rounded-2xl border border-white/10 bg-[#0F172A] p-4"
                    />
                </div>

                <div class="mb-6">
                    <label class="mb-3 block font-semibold">
                        During Repair Image
                    </label>

                    <input
                        type="file"
                        class="w-full rounded-2xl border border-white/10 bg-[#0F172A] p-4"
                    />
                </div>

                <div class="mb-6">
                    <label class="mb-3 block font-semibold">
                        After Repair Image
                    </label>

                    <input
                        type="file"
                        class="w-full rounded-2xl border border-white/10 bg-[#0F172A] p-4"
                    />
                </div>

                <button
                    class="rounded-2xl bg-purple-600 px-8 py-4 font-bold hover:bg-purple-700"
                >
                    Upload Images
                </button>
            </form>
        </div>
    </div>

@endsection
