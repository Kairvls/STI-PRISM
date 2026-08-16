@extends ("layouts.maintenance-layout")

@section ("title", "Replacement Recommendation")

@section ("content")
    <div>
        <div class="rounded-3xl bg-[#1E293B] p-8">
            <form action="#" method="POST">
                @csrf

                <textarea
                    rows="8"
                    class="w-full resize-none rounded-2xl border border-white/10 bg-[#0F172A] px-5 py-4"
                    placeholder="Enter replacement recommendation..."
                ></textarea>

                <button
                    class="mt-6 rounded-2xl bg-orange-500 px-8 py-4 font-bold hover:bg-orange-600"
                >
                    Save Recommendation
                </button>
            </form>
        </div>
    </div>

@endsection
