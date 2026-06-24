<div

    x-data="{

        open:false

    }"

    @open-wizard.window="
        open=true
    "

>

    <div

        x-show="open"

        x-transition

        class="
            fixed
            inset-0
            bg-black/50
            z-50
            flex
            items-center
            justify-center
        "

    >

        <div

            class="
                bg-white
                w-[900px]
                rounded-3xl
                p-8
            "

        >

            <div
                class="
                    flex
                    justify-between
                    items-center
                    mb-8
                "
            >

                <h2
                    class="
                        text-3xl
                        font-bold
                    "
                >
                    Campus Configuration Wizard
                </h2>

                <button
                    @click="open=false"
                    class="text-2xl"
                >
                    ✕
                </button>

            </div>

            <div class="grid grid-cols-4 gap-4">

                <div class="bg-blue-50 p-5 rounded-2xl">

                    <div class="font-bold">
                        Step 1
                    </div>

                    <div>
                        Building
                    </div>

                </div>

                <div class="bg-yellow-50 p-5 rounded-2xl">

                    <div class="font-bold">
                        Step 2
                    </div>

                    <div>
                        Floors
                    </div>

                </div>

                <div class="bg-orange-50 p-5 rounded-2xl">

                    <div class="font-bold">
                        Step 3
                    </div>

                    <div>
                        Rooms
                    </div>

                </div>

                <div class="bg-green-50 p-5 rounded-2xl">

                    <div class="font-bold">
                        Step 4
                    </div>

                    <div>
                        Review
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>