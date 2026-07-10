<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Print Preview
    </title>


    <style>

        /* ===================================== */
        /* RESET */
        /* ===================================== */

        * {
            box-sizing: border-box;
        }


        html,
        body {
            margin: 0;
            padding: 0;
        }


        /* ===================================== */
        /* SCREEN PRINT PREVIEW */
        /* ===================================== */

        body {

            min-height: 100vh;

            margin: 0;

            background: #f8fafc;

            font-family:
                Inter,
                Arial,
                Helvetica,
                sans-serif;

            color: #0f172a;

        }


        .print-preview {

            min-height: 100vh;

        }


        /* ===================================== */
        /* TOOLBAR */
        /* ===================================== */

        .preview-toolbar {

            position: fixed;

            top: 0;
            left: 0;
            right: 0;

            z-index: 20;

            height: 72px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 32px;

            border-bottom: 1px solid #e2e8f0;

            background: rgba(255, 255, 255, 0.92);

            backdrop-filter: blur(12px);

        }


        .toolbar-left {

            display: flex;
            align-items: center;

            gap: 14px;

        }


        .icon-button {

            width: 38px;
            height: 38px;

            display: flex;
            align-items: center;
            justify-content: center;

            border: 1px solid #e2e8f0;

            border-radius: 10px;

            background: #ffffff;

            color: #475569;

            font-size: 18px;

            cursor: pointer;

        }

        .hover\:bg-gray-100:hover {
            background-color: #f1f5f9;
        }


        .preview-eyebrow {

            margin: 0;

            color: #94a3b8;

            font-size: 10px;
            font-weight: 700;

            letter-spacing: 0.12em;

            text-transform: uppercase;

        }


        .preview-title {

            margin: 3px 0 0;

            font-size: 17px;
            font-weight: 700;

        }


        .print-button {

            min-height: 40px;

            padding: 0 18px;

            border: 0;

            border-radius: 10px;

            background: #0f172a;

            color: #ffffff;

            font-size: 13px;
            font-weight: 600;

            cursor: pointer;

        }


        /* ===================================== */
        /* WORKSPACE */
        /* ===================================== */

        .preview-workspace {

            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding:
                112px
                24px
                48px;

        }


        .preview-content {

            width: 100%;
            max-width: 760px;

            display: flex;
            flex-direction: column;
            align-items: center;

        }


        /* ===================================== */
        /* PREVIEW HEADING */
        /* ===================================== */

        .preview-heading {

            text-align: center;

        }


        .status-badge {

            width: fit-content;

            margin: 0 auto 16px;

            display: flex;
            align-items: center;

            gap: 7px;

            padding:
                6px
                10px;

            border: 1px solid #e2e8f0;

            border-radius: 999px;

            background: #ffffff;

            color: #64748b;

            font-size: 11px;
            font-weight: 600;

        }


        .status-dot {

            width: 6px;
            height: 6px;

            border-radius: 999px;

            background: #10b981;

        }


        .preview-heading h2 {

            margin: 0;

            font-size: 24px;
            font-weight: 700;

            letter-spacing: -0.02em;

        }


        .preview-heading p {

            margin: 8px 0 0;

            color: #64748b;

            font-size: 13px;

        }


        

        /* ===================================== */
        /* LABEL STAGE */
        /* COMPACT CONTAINER AROUND LABEL */
        /* ===================================== */

        .label-stage {

            width: fit-content;

            margin-top: 32px;

            display: flex;
            align-items: center;
            justify-content: center;

            padding:
                32px
                36px;

            border: 1px solid #e2e8f0;

            border-radius: 20px;

            background: #ffffff;

            box-shadow:
                0 20px 50px
                rgba(15, 23, 42, 0.06);

        }


        /* ===================================== */
        /* PREVIEW METADATA */
        /* ===================================== */

        .preview-meta {

            margin-top: 20px;

            display: flex;
            align-items: center;

            gap: 10px;

            color: #94a3b8;

            font-size: 11px;

        }


        .preview-meta strong {

            color: #475569;

            font-weight: 600;

        }


        .meta-divider {

            width: 1px;
            height: 14px;

            margin: 0 6px;

            background: #e2e8f0;

        }

        /* ===================================== */
        /* PHYSICAL LABEL */
        /* 80MM × 40MM */
        /* ===================================== */

        .qr-label {

            width: 80mm;
            height: 40mm;

            display: grid;

            grid-template-columns:
                40mm
                1fr;

            overflow: hidden;

            border: 1px solid #0f172a;

            background: #ffffff;

        }


        /* ===================================== */
        /* QR SECTION */
        /* ===================================== */

        .qr-section {

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 3mm;

            border-right: 1px solid #0f172a;

        }


        .qr-section img {

            display: block;

            width: 34mm;
            height: 34mm;

            max-width: 34mm;
            max-height: 34mm;

            object-fit: contain;

        }


        /* ===================================== */
        /* LABEL INFORMATION */
        /* ===================================== */

        .label-information {

            min-width: 0;

            display: flex;
            flex-direction: column;

            justify-content: center;

            padding: 4mm;

        }


        /* ===================================== */
        /* SYSTEM NAME */
        /* ===================================== */

        .system-name {

            margin: 0;

            font-size: 7pt;
            font-weight: 700;

            letter-spacing: 0.08em;

            text-transform: uppercase;

        }


        /* ===================================== */
        /* EQUIPMENT NAME */
        /* ===================================== */

        .equipment-name {

            margin:
                3mm
                0
                0;

            overflow: hidden;

            font-size: 13pt;
            font-weight: 700;

            line-height: 1.15;

            overflow-wrap: anywhere;

        }


        /* ===================================== */
        /* QR ID */
        /* ===================================== */

        .qr-id-label {
            margin:
                auto
                0
                0;

            font-size: 6.5pt;

            color: #64748b;

            text-transform: uppercase;
        }


        .qr-id {
            margin:
                1mm
                0
                0;

            font-family:
                "Courier New",
                monospace;

            font-size: 8pt;
            font-weight: 700;

            letter-spacing: 0.04em;

            overflow-wrap: anywhere;
        }

        /* ===================================== */
        /* PRINT PREVIEW PAGE */
        /* ===================================== */

        .print-preview {
            width: 100%;
            min-height: 100vh;
        }


        .preview-toolbar {
            position: fixed;

            top: 0;
            left: 0;
            right: 0;

            height: 76px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 32px;

            border-bottom: 1px solid #e2e8f0;

            background: #ffffff;
        }


        .preview-eyebrow {
            margin: 0;

            font-size: 11px;
            font-weight: 700;

            color: #94a3b8;

            letter-spacing: 0.12em;

            text-transform: uppercase;
        }


        .preview-title {
            margin: 4px 0 0;

            font-size: 20px;
        }


        .preview-actions {
            display: flex;
            align-items: center;

            gap: 10px;
        }


        .back-button,
        .print-button {
            min-height: 40px;

            padding: 0 18px;

            border-radius: 8px;

            font-size: 14px;
            font-weight: 600;

            cursor: pointer;
        }


        .back-button {
            border: 1px solid #cbd5e1;

            background: #ffffff;

            color: #334155;
        }


        .print-button {
            border: 1px solid #0f172a;

            background: #0f172a;

            color: #ffffff;
        }


        /* ===================================== */
        /* LABEL PREVIEW AREA */
        /* ===================================== */

        .preview-area {
            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding-top: 76px;
        }


        /* ===================================== */
        /* PRINT SETTINGS */
        /* ===================================== */

        /* ===================================== */
        /* PHYSICAL LABEL PAGE SIZE */
        /* ===================================== */

        @page {
            size: 80mm 40mm;
            margin: 0;
        }


        /* ===================================== */
        /* PRINT MODE */
        /* ===================================== */

        @media print {

            /* ===================================== */
            /* RESET DOCUMENT */
            /* ===================================== */

            html,
            body {
                width: 80mm !important;
                height: 40mm !important;

                min-width: 80mm !important;
                min-height: 40mm !important;

                margin: 0 !important;
                padding: 0 !important;

                overflow: hidden !important;

                background: #ffffff !important;
            }


            body {
                display: block !important;
            }


            /* ===================================== */
            /* HIDE SCREEN TOOLBAR */
            /* ===================================== */

            .preview-toolbar {
                display: none !important;
            }


            /* ===================================== */
            /* RESET SCREEN PREVIEW CONTAINERS */
            /* ===================================== */

            .print-preview,
            .preview-area {
                position: static !important;

                width: 80mm !important;
                height: 40mm !important;

                min-width: 80mm !important;
                min-height: 40mm !important;

                margin: 0 !important;
                padding: 0 !important;

                display: block !important;

                overflow: hidden !important;
            }


            /* ===================================== */
            /* PHYSICAL LABEL */
            /* ===================================== */

            .qr-label {
                position: absolute !important;

                top: 0 !important;
                left: 0 !important;

                width: 80mm !important;
                height: 40mm !important;

                margin: 0 !important;

                overflow: hidden !important;

                border: 1px solid #000000 !important;

                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

        }

    </style>

</head>


<body>


    <div class="print-preview">


        {{-- ===================================== --}}
        {{-- TOP TOOLBAR --}}
        {{-- ===================================== --}}

        <header class="preview-toolbar">

            <div class="toolbar-left">

                <button
                    type="button"
                    class="icon-button"
                    onclick="window.close()"
                    aria-label="Close print preview"
                >
                    <
                </button>


                <div>

                    <p class="preview-eyebrow">
                        QR Code Tools
                    </p>

                    <h1 class="preview-title">
                        Print Equipment Label
                    </h1>

                </div>

            </div>


            <button
                type="button"
                class="print-button"
                onclick="window.print()"
            >
                Print QR Code
            </button>

        </header>



        {{-- ===================================== --}}
        {{-- PREVIEW WORKSPACE --}}
        {{-- ===================================== --}}

        <main class="preview-workspace">


            <div class="preview-content">


                {{-- ===================================== --}}
                {{-- STATUS --}}
                {{-- ===================================== --}}

                <div class="preview-heading">

                    <div class="status-badge">

                        <span class="status-dot"></span>

                        Ready to print

                    </div>


                    <h2>
                        Equipment QR Label
                    </h2>


                    <p>
                        Preview of the physical equipment label.
                    </p>

                </div>



                {{-- ===================================== --}}
                {{-- LABEL STAGE --}}
                {{-- ===================================== --}}

                <div class="label-stage">


                    {{-- ===================================== --}}
                    {{-- PHYSICAL LABEL --}}
                    {{-- DO NOT CHANGE SIZE --}}
                    {{-- ===================================== --}}

                    <section class="qr-label">

                        <div class="qr-section">

                            <img
                                src="{{ url(
                                    '/maintenance/equipment/qr-image/' .
                                    $equipment->equipment_qr_code
                                ) }}"

                                alt="QR Code for {{ $equipment->equipment_name }}"
                            >

                        </div>


                        <div class="label-information">

                            <p class="system-name">

                                {{ $equipment->building_name ?? 'Campus' }} Equipment

                            </p>


                            <h2 class="equipment-name">

                                {{ $equipment->equipment_name }}

                            </h2>


                            <p class="qr-id-label">
                                QR ID
                            </p>


                            <p class="qr-id">

                                {{ $equipment->equipment_qr_code }}

                            </p>

                        </div>

                    </section>

                </div>



                {{-- ===================================== --}}
                {{-- LABEL INFORMATION --}}
                {{-- ===================================== --}}

                <div class="preview-meta">

                    <span>
                        Label size
                    </span>

                    <strong>
                        80 × 40 mm
                    </strong>

                    <span class="meta-divider"></span>

                    <span>
                        QR ID
                    </span>

                    <strong>
                        {{ $equipment->equipment_qr_code }}
                    </strong>

                </div>


            </div>


        </main>


    </div>

    {{-- ===================================== --}}
    {{-- AUTO OPEN PRINT DIALOG --}}
    {{-- WAIT FOR QR IMAGE TO FINISH LOADING --}}
    {{-- ===================================== --}}

    <script>

        window.addEventListener('load', function () {

            // =====================================
            // GET QR IMAGE
            // =====================================

            const qrImage =
                document.querySelector('.qr-section img');


            // =====================================
            // OPEN PRINT DIALOG
            // =====================================

            function openPrintDialog() {

                setTimeout(function () {

                    window.print();

                }, 150);

            }


            // =====================================
            // IMAGE ALREADY LOADED
            // =====================================

            if (
                qrImage
                && qrImage.complete
                && qrImage.naturalWidth > 0
            ) {

                openPrintDialog();

                return;

            }


            // =====================================
            // WAIT FOR IMAGE TO LOAD
            // =====================================

            if (qrImage) {

                qrImage.addEventListener(
                    'load',
                    openPrintDialog,
                    { once: true }
                );

                return;

            }


            // =====================================
            // FALLBACK
            // =====================================

            openPrintDialog();

        });

    </script>


</body>

</html>