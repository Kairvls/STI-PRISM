<div
    x-data="{
        open: false,
    }"
    @open-wizard.window="open = true"
>
    <div
        x-show="open"
        x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
    >
        <div class="w-[900px] rounded-3xl bg-white p-8">
            <div class="mb-8 flex items-center justify-between">
                <h2 class="text-3xl font-bold">Campus Configuration Wizard</h2>

                <button @click="open = false" class="text-2xl">✕</button>
            </div>

            <div class="grid grid-cols-4 gap-4">
                <div class="rounded-2xl bg-blue-50 p-5">
                    <div class="font-bold">Step 1</div>

                    <div>Building</div>
                </div>

                <div class="rounded-2xl bg-yellow-50 p-5">
                    <div class="font-bold">Step 2</div>

                    <div>Floors</div>
                </div>

                <div class="rounded-2xl bg-orange-50 p-5">
                    <div class="font-bold">Step 3</div>

                    <div>Rooms</div>
                </div>

                <div class="rounded-2xl bg-green-50 p-5">
                    <div class="font-bold">Step 4</div>

                    <div>Review</div>
                </div>
            </div>
        </div>
    </div>
</div>
