@extends ("layouts.maintenance-layout")

@section ("title", "Repair Remarks")

@section ("content")
    <div>
        <div class="rounded-3xl bg-[#1E293B] p-8">
            <form action="#" method="POST">
                @csrf

                <textarea
                    rows="8"
                    class="w-full resize-none rounded-2xl border border-white/10 bg-[#0F172A] px-5 py-4"
                    placeholder="Enter repair remarks..."
                ></textarea>

                <button
                    class="mt-6 rounded-2xl bg-blue-600 px-8 py-4 font-bold hover:bg-blue-700"
                >
                    Save Repair Remarks
                </button>
            </form>
        </div>
    </div>

@endsection
