@extends ("layouts.maintenance-layout")

@section ("title", "QR Code Tools")

@section ("content")
    <div class="space-y-6">
        <div>
            <h1 class="text-4xl font-black text-slate-900">QR Code Tools</h1>

            <p class="text-slate-500">Generate and manage equipment QR codes.</p>
        </div>

        <div
            class="overflow-hidden mt-6 mb-6 rounded-lg border-t border-b border-slate-300 bg-gray-100 shadow-sm"
        >
            <div
                class="grid grid-cols-1 divide-y divide-slate-200 md:grid-cols-2 md:divide-y-0 xl:grid-cols-[380px_1fr_1fr_1fr] "
            >
                <!-- Total Equipment -->
                <div class="flex items-center justify-between px-8 py-6">

                    <!-- Left Content -->
                    <div class="flex flex-col">
                        <p class="text-sm font-medium text-slate-500">
                            Total Equipment
                        </p>

                        <h2 class="mt-2 text-5xl font-medium text-slate-900">
                            800
                        </h2>

                        <p class="mt-3 text-sm">
                            <span class="font-semibold text-emerald-500">
                                +12.45%
                            </span>

                            <span class="text-slate-500">
                                From last month
                            </span>
                        </p>
                    </div>

                    <!-- Right Graph -->
                    <div class="ml-6 h-20 w-40 shrink-0">
                        <svg
                            viewBox="0 0 300 100"
                            class="h-full w-full"
                            fill="none"
                        >
                            <path
                                d="M0 62
                                L35 28
                                L62 58
                                L82 52
                                L112 82
                                L162 82
                                L200 42
                                L232 64
                                L270 64
                                L300 18"
                                stroke="#3b82f6"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />

                            <path
                                d="M0 62
                                L35 28
                                L62 58
                                L82 52
                                L112 82
                                L162 82
                                L200 42
                                L232 64
                                L270 64
                                L300 18
                                L300 100
                                L0 100 Z"
                                fill="#3b82f6"
                                fill-opacity=".08"
                            />
                        </svg>
                    </div>

                </div>

                <!-- Active -->
                <div class="relative flex flex-col justify-between px-8 py-7">

                    <span
                        class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"
                    ></span>

                    <p class="text-md font-medium text-slate-600">
                        Active
                    </p>

                    <h2 class="text-5xl font-medium text-slate-900">
                        330
                    </h2>

                    <p class="text-base">
                        <span class="font-semibold text-emerald-500">
                            +8.32%
                        </span>

                        <span class="text-slate-500">
                            From last month
                        </span>
                    </p>
                </div>

                <!-- Under Maintenance -->
                <div class="relative flex flex-col justify-between px-8 py-7">

                    <span
                        class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"
                    ></span>

                    <p class="text-md font-medium text-slate-600">
                        Under Maintenance
                    </p>

                    <h2 class="text-5xl font-medium text-slate-900">
                        8
                    </h2>

                    <p class="text-base">
                        <span class="font-semibold text-red-500">
                            -4.67%
                        </span>

                        <span class="text-slate-500">
                            From last month
                        </span>
                    </p>
                </div>

                <!-- Disposed -->
                <div class="relative flex flex-col justify-between px-8 py-7">

                    <span
                        class="absolute left-0 top-8 hidden h-[68%] border-l border-slate-200 xl:block"
                    ></span>

                    <p class="text-md font-medium text-slate-600">
                        Disposed
                    </p>

                    <h2 class="text-5xl font-medium text-slate-900">
                        23
                    </h2>

                    <p class="text-base">
                        <span class="font-semibold text-emerald-500">
                            +2.15%
                        </span>

                        <span class="text-slate-500">
                            From last month
                        </span>
                    </p>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- EQUIPMENT QR CODE LIST --}}
        {{-- ========================================================= --}}

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">

            {{-- ===================================================== --}}
            {{-- HEADER --}}
            {{-- ===================================================== --}}

            <div
                class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4
                    sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-3">

                    {{-- HEADER ICON --}}
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center
                            rounded-lg bg-slate-100 text-slate-600"
                    >
                        <i data-lucide="qr-code" class="h-4 w-4"></i>
                    </div>


                    {{-- HEADER TEXT --}}
                    <div>

                        <h2 class="text-sm font-semibold text-slate-900">
                            Equipment QR Codes
                        </h2>

                        <p class="mt-0.5 text-xs text-slate-400">
                            Manage and generate equipment identification codes
                        </p>

                    </div>

                </div>


                {{-- TOTAL COUNT --}}
                <div
                    class="inline-flex w-fit items-center gap-2
                        rounded-lg border border-slate-200
                        bg-slate-50 px-3 py-2
                        text-xs font-medium text-slate-500"
                >
                    <i
                        data-lucide="package"
                        class="h-3.5 w-3.5"
                    ></i>

                    {{ $equipment->count() }} total
                </div>

            </div>



            {{-- ===================================================== --}}
            {{-- TABLE --}}
            {{-- ===================================================== --}}

            <div class="overflow-x-auto">

                <table class="w-full min-w-[850px] text-left">

                    {{-- ================================================= --}}
                    {{-- TABLE HEADER --}}
                    {{-- ================================================= --}}

                    <thead class="border-b border-slate-200 bg-slate-50/70">

                        <tr
                            class="text-[10px] font-semibold uppercase
                                tracking-[0.08em] text-slate-400"
                        >

                            <th class="px-5 py-3">
                                Equipment
                            </th>

                            <th class="px-5 py-3">
                                Category
                            </th>

                            <th class="px-5 py-3">
                                QR Code
                            </th>

                            <th class="w-16 px-5 py-3 text-right">
                                Actions
                            </th>

                        </tr>

                    </thead>



                    {{-- ================================================= --}}
                    {{-- TABLE BODY --}}
                    {{-- ================================================= --}}

                    <tbody class="divide-y divide-slate-100">

                        @forelse ($equipment as $item)

                            @php
                                // CHECK IF EQUIPMENT HAS A QR CODE
                                $hasQrCode =
                                    !empty($item->equipment_qr_code);
                            @endphp


                            <tr
                                class="group transition-colors
                                    hover:bg-slate-50/70"
                            >

                                {{-- ===================================== --}}
                                {{-- EQUIPMENT --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <div class="flex items-center gap-3">

                                        {{-- EQUIPMENT ICON --}}
                                        <div
                                            class="flex h-9 w-9 shrink-0
                                                items-center justify-center
                                                rounded-lg border border-slate-200
                                                bg-white text-slate-400"
                                        >
                                            <i
                                                data-lucide="monitor-cog"
                                                class="h-4 w-4"
                                            ></i>
                                        </div>


                                        {{-- EQUIPMENT NAME --}}
                                        <div class="min-w-0">

                                            <p
                                                class="max-w-[260px] truncate
                                                    text-sm font-semibold
                                                    text-slate-800"
                                            >
                                                {{ $item->equipment_name }}
                                            </p>


                                            <p
                                                class="mt-0.5 text-[11px]
                                                    text-slate-400"
                                            >
                                                Equipment record
                                            </p>

                                        </div>

                                    </div>

                                </td>



                                {{-- ===================================== --}}
                                {{-- CATEGORY --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    <span
                                        class="inline-flex rounded-md
                                            bg-slate-100 px-2 py-1
                                            text-[11px] font-medium
                                            text-slate-600"
                                    >
                                        {{
                                            $item->equipment_category_name
                                                ?? "Uncategorized"
                                        }}
                                    </span>

                                </td>



                                {{-- ===================================== --}}
                                {{-- QR CODE STATUS --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4">

                                    @if ($hasQrCode)

                                        <div class="flex items-center gap-3">

                                            {{-- STATUS ICON --}}
                                            <div
                                                class="flex h-8 w-8 shrink-0
                                                    items-center justify-center
                                                    rounded-lg bg-emerald-50
                                                    text-emerald-600"
                                            >
                                                <i
                                                    data-lucide="qr-code"
                                                    class="h-3.5 w-3.5"
                                                ></i>
                                            </div>


                                            {{-- QR INFORMATION --}}
                                            <div class="min-w-0">

                                                <div
                                                    class="flex items-center gap-1.5
                                                        text-[11px] font-medium
                                                        text-emerald-700"
                                                >
                                                    <span
                                                        class="h-1.5 w-1.5
                                                            rounded-full
                                                            bg-emerald-500"
                                                    ></span>

                                                    Generated
                                                </div>


                                                <p
                                                    class="mt-1 max-w-[260px]
                                                        truncate font-mono
                                                        text-[10px]
                                                        text-slate-400"
                                                    title="{{ $item->equipment_qr_code }}"
                                                >
                                                    {{ $item->equipment_qr_code }}
                                                </p>

                                            </div>

                                        </div>


                                    @else

                                        <div class="flex items-center gap-3">

                                            {{-- STATUS ICON --}}
                                            <div
                                                class="flex h-8 w-8 shrink-0
                                                    items-center justify-center
                                                    rounded-lg bg-slate-100
                                                    text-slate-400"
                                            >
                                                <i
                                                    data-lucide="qr-code"
                                                    class="h-3.5 w-3.5"
                                                ></i>
                                            </div>


                                            <div>

                                                <div
                                                    class="flex items-center gap-1.5
                                                        text-[11px] font-medium
                                                        text-slate-500"
                                                >
                                                    <span
                                                        class="h-1.5 w-1.5
                                                            rounded-full
                                                            bg-slate-300"
                                                    ></span>

                                                    Not generated
                                                </div>


                                                <p
                                                    class="mt-1 text-[10px]
                                                        text-slate-400"
                                                >
                                                    Generate a QR code to continue
                                                </p>

                                            </div>

                                        </div>

                                    @endif

                                </td>



                                {{-- ===================================== --}}
                                {{-- ACTIONS --}}
                                {{-- ===================================== --}}

                                <td class="px-5 py-4 text-right">

                                    <div
                                        class="relative inline-block"
                                        x-data="{ open: false }"
                                    >

                                        {{-- ACTION MENU BUTTON --}}
                                        <button
                                            type="button"

                                            @click="open = !open"

                                            @click.outside="open = false"

                                            class="flex h-8 w-8 items-center
                                                justify-center rounded-lg
                                                text-slate-400 transition
                                                hover:bg-slate-200/70
                                                hover:text-slate-700"
                                        >
                                            <i
                                                data-lucide="ellipsis"
                                                class="h-4 w-4"
                                            ></i>
                                        </button>



                                        {{-- ACTION DROPDOWN --}}
                                        <div
                                            x-cloak

                                            x-show="open"

                                            x-transition.origin.top.right

                                            class="absolute right-0 top-10 z-50
                                                w-44 overflow-hidden
                                                rounded-xl
                                                border border-slate-200
                                                bg-white p-1.5
                                                text-left
                                                shadow-lg shadow-slate-900/10"
                                        >

                                            {{-- ================================= --}}
                                            {{-- PREVIEW --}}
                                            {{-- ================================= --}}

                                            @if ($hasQrCode)

                                                <button
                                                    type="button"

                                                    @click="
                                                        open = false;

                                                        openQrModal(
                                                            @js($item->equipment_name),
                                                            @js($item->equipment_qr_code)
                                                        );
                                                    "

                                                    class="flex w-full items-center
                                                        gap-2.5 rounded-lg
                                                        px-3 py-2
                                                        text-xs font-medium
                                                        text-slate-600
                                                        transition
                                                        hover:bg-slate-50
                                                        hover:text-slate-900"
                                                >
                                                    <i
                                                        data-lucide="eye"
                                                        class="h-3.5 w-3.5"
                                                    ></i>

                                                    Preview QR code
                                                </button>

                                            @endif



                                            {{-- ================================= --}}
                                            {{-- GENERATE / REGENERATE --}}
                                            {{-- ================================= --}}

                                            <form
                                                method="POST"

                                                action="/maintenance/equipment/qr/generate/{{ $item->equipment_id }}"
                                            >

                                                @csrf


                                                <button
                                                    type="submit"

                                                    class="flex w-full items-center
                                                        gap-2.5 rounded-lg
                                                        px-3 py-2
                                                        text-xs font-medium
                                                        text-slate-600
                                                        transition
                                                        hover:bg-slate-50
                                                        hover:text-slate-900"
                                                >
                                                    <i
                                                        data-lucide="{{
                                                            $hasQrCode
                                                                ? "refresh-cw"
                                                                : "qr-code"
                                                        }}"
                                                        class="h-3.5 w-3.5"
                                                    ></i>


                                                    {{
                                                        $hasQrCode
                                                            ? "Regenerate QR"
                                                            : "Generate QR"
                                                    }}

                                                </button>

                                            </form>



                                            {{-- ================================= --}}
                                            {{-- QR ACTIONS --}}
                                            {{-- ================================= --}}

                                            @if ($hasQrCode)

                                                <div
                                                    class="my-1
                                                        border-t border-slate-100"
                                                ></div>


                                                {{-- PRINT --}}
                                                <button
                                                    type="button"

                                                    @click="
                                                        open = false;

                                                        openQrModal(
                                                            @js($item->equipment_name),
                                                            @js($item->equipment_qr_code)
                                                        );

                                                        $nextTick(() => {
                                                            printQr();
                                                        });
                                                    "

                                                    class="flex w-full items-center
                                                        gap-2.5 rounded-lg
                                                        px-3 py-2
                                                        text-xs font-medium
                                                        text-slate-600
                                                        transition
                                                        hover:bg-slate-50
                                                        hover:text-slate-900"
                                                >
                                                    <i
                                                        data-lucide="printer"
                                                        class="h-3.5 w-3.5"
                                                    ></i>

                                                    Print QR code
                                                </button>



                                                {{-- DOWNLOAD --}}
                                                <button
                                                    type="button"

                                                    @click="
                                                        open = false;

                                                        openQrModal(
                                                            @js($item->equipment_name),
                                                            @js($item->equipment_qr_code)
                                                        );

                                                        $nextTick(() => {
                                                            downloadQr();
                                                        });
                                                    "

                                                    class="flex w-full items-center
                                                        gap-2.5 rounded-lg
                                                        px-3 py-2
                                                        text-xs font-medium
                                                        text-slate-600
                                                        transition
                                                        hover:bg-slate-50
                                                        hover:text-slate-900"
                                                >
                                                    <i
                                                        data-lucide="download"
                                                        class="h-3.5 w-3.5"
                                                    ></i>

                                                    Download QR code
                                                </button>

                                            @endif

                                        </div>

                                    </div>

                                </td>

                            </tr>


                        @empty

                            {{-- ========================================= --}}
                            {{-- EMPTY STATE --}}
                            {{-- ========================================= --}}

                            <tr>

                                <td
                                    colspan="4"
                                    class="px-5 py-16 text-center"
                                >

                                    <div class="mx-auto max-w-xs">

                                        <div
                                            class="mx-auto flex h-11 w-11
                                                items-center justify-center
                                                rounded-xl bg-slate-100
                                                text-slate-400"
                                        >
                                            <i
                                                data-lucide="qr-code"
                                                class="h-5 w-5"
                                            ></i>
                                        </div>


                                        <h3
                                            class="mt-3 text-sm font-semibold
                                                text-slate-700"
                                        >
                                            No equipment available
                                        </h3>


                                        <p
                                            class="mt-1 text-xs leading-5
                                                text-slate-400"
                                        >
                                            Equipment records will appear here
                                            when they are added to the inventory.
                                        </p>

                                    </div>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </section>
    </div>

    <!-- ===================================================== -->
    <!-- QR PREVIEW MODAL -->
    <!-- ===================================================== -->

    <div
        id="qrModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 p-4 backdrop-blur-[2px]"
    >
        <!-- ===================================== -->
        <!-- QR CODE MODAL -->
        <!-- ===================================== -->
        <div
            class="w-full max-w-sm overflow-hidden rounded-2xl border border-black/5 bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)]"
        >
            <!-- ===================================== -->
            <!-- MODAL HEADER -->
            <!-- ===================================== -->
            <div class="flex items-start justify-between gap-6 px-6 pb-4 pt-6">
                <div class="min-w-0">
                    <p
                        class="text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400"
                    >
                        Equipment QR Code
                    </p>

                    <h2
                        id="qrEquipmentName"
                        class="mt-1.5 truncate text-lg font-semibold tracking-tight text-slate-950"
                    >
                        QR Code
                    </h2>
                </div>

                <!-- CLOSE BUTTON -->
                <button
                    type="button"
                    onclick="closeQrModal()"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-900"
                    aria-label="Close modal"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <!-- ===================================== -->
            <!-- QR CODE CONTENT -->
            <!-- ===================================== -->
            <div class="border-y border-slate-100 px-6 py-6">
                <div
                    class="flex flex-col items-center"
                >
                    <!-- QR PREVIEW -->
                    <div
                        id="qrPreview"
                        class="flex min-h-[220px] w-full items-center justify-center rounded-xl border border-slate-200 bg-white p-4"
                    ></div>

                    <!-- QR CODE VALUE -->
                    <div class="mt-4 w-full text-center">
                        <p class="text-xs text-slate-400">
                            Equipment identifier
                        </p>

                        <div
                            id="qrCodeText"
                            class="mt-1 break-all font-mono text-sm font-medium text-slate-700"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- ===================================== -->
            <!-- MODAL FOOTER -->
            <!-- ===================================== -->
            <div class="flex justify-end px-6 py-4">
                <button
                    type="button"
                    onclick="closeQrModal()"
                    class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                >
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        function openQrModal(equipmentName, qrCode) {
            document.getElementById("qrEquipmentName").innerText =
                equipmentName;

            document.getElementById("qrCodeText").innerText = qrCode;

            document.getElementById("qrPreview").innerHTML = `
        <img
            src="/maintenance/equipment/qr-image/${qrCode}"
            class="w-64 h-64">
    `;

            document.getElementById("qrModal").classList.remove("hidden");

            document.getElementById("qrModal").classList.add("flex");
        }

        function closeQrModal() {
            document.getElementById("qrModal").classList.add("hidden");

            document.getElementById("qrModal").classList.remove("flex");
        }

        function printQr() {
            const qrImage = document.getElementById("qrPreview").innerHTML;

            const qrCode = document.getElementById("qrCodeText").innerText;

            const printWindow = window.open("", "", "width=800,height=600");

            printWindow.document.write(`
        <html>
        <head>

            <title>
                ${qrCode}
            </title>

            <style>

                body{
                    display:flex;
                    justify-content:center;
                    align-items:center;
                    flex-direction:column;
                    height:100vh;
                    font-family:Arial;
                }

                img{
                    width:300px;
                }

            </style>

        </head>

        <body>

            ${qrImage}

            <h2>
                ${qrCode}
            </h2>

        </body>

        </html>
    `);

            printWindow.document.close();

            printWindow.focus();

            printWindow.print();
        }

        function downloadQr() {
            const img = document.querySelector("#qrPreview img");

            const link = document.createElement("a");

            link.href = img.src;

            link.download =
                document.getElementById("qrCodeText").innerText + ".svg";

            link.click();
        }
    </script>

@endsection
