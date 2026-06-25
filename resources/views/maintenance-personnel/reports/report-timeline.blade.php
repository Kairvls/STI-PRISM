@extends ("layouts.maintenance-layout")

@section ("title", "Report Timeline")

@section ("content")
    <div class="mx-auto max-w-6xl">
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold">Report Timeline</h1>

            <p class="mt-2 text-gray-400">Full audit logs and activity history.</p>
        </div>

        <div class="rounded-3xl bg-[#1E293B] p-8">
            <div class="space-y-8">
                <!-- TIMELINE ITEM -->
                <div class="flex gap-5">
                    <div
                        class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-blue-500/20"
                    >
                        <i data-lucide="file-text" class="text-blue-400"></i>
                    </div>

                    <div>
                        <h1 class="text-lg font-bold">Report Submitted</h1>

                        <p class="mt-1 text-gray-400">Submitted by Juan Dela Cruz</p>

                        <p class="mt-2 text-sm text-gray-500">June 10, 2026 08:15 AM</p>
                    </div>
                </div>

                <!-- TIMELINE ITEM -->
                <div class="flex gap-5">
                    <div
                        class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-yellow-500/20"
                    >
                        <i data-lucide="user-check" class="text-yellow-400"></i>
                    </div>

                    <div>
                        <h1 class="text-lg font-bold">Assigned Personnel</h1>

                        <p class="mt-1 text-gray-400">Assigned to Kenn Mehares</p>

                        <p class="mt-2 text-sm text-gray-500">June 10, 2026 09:00 AM</p>
                    </div>
                </div>

                <!-- TIMELINE ITEM -->
                <div class="flex gap-5">
                    <div
                        class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-green-500/20"
                    >
                        <i data-lucide="badge-check" class="text-green-400"></i>
                    </div>

                    <div>
                        <h1 class="text-lg font-bold">Report Resolved</h1>

                        <p class="mt-1 text-gray-400">Air conditioning unit repaired successfully.</p>

                        <p class="mt-2 text-sm text-gray-500">June 11, 2026 02:45 PM</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
