<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>
        {{ $equipment->equipment_qr_code }} Label
    </title>


    <style>

        /* ===================================== */
        /* PDF PAGE */
        /* ===================================== */

        @page {

            size: 80mm 40mm;

            margin: 0;

        }


        /* ===================================== */
        /* REMOVE DEFAULT DOCUMENT SPACING */
        /* DO NOT SET WIDTH OR HEIGHT HERE */
        /* ===================================== */

        html,
        body {

            margin: 0;

            padding: 0;

            font-family:
                DejaVu Sans,
                Arial,
                sans-serif;

            color: #0f172a;

        }


        /* ===================================== */
        /* LABEL TABLE */
        /* SLIGHTLY SMALLER THAN PDF PAGE */
        /* PREVENTS DOMPDF ROUNDING OVERFLOW */
        /* ===================================== */

        .qr-label {

            width: 79mm;

            height: 39mm;

            margin: 0;

            padding: 0;

            border-collapse: collapse;

            table-layout: fixed;

            border: 0.25mm solid #cbd5e1;

            box-sizing: border-box;

        }


        /* ===================================== */
        /* REMOVE DEFAULT CELL SPACING */
        /* ===================================== */

        .qr-label td {

            margin: 0;

            padding: 0;

        }


        /* ===================================== */
        /* QR SECTION */
        /* ===================================== */

        .qr-section {

            width: 39mm;

            height: 39mm;

            text-align: center;

            vertical-align: middle;

            border-right:
                0.3mm solid #0f172a;

        }


        /* ===================================== */
        /* QR IMAGE */
        /* ===================================== */

        .qr-image {

            display: block;

            width: 33mm;

            height: 33mm;

            margin:
                0
                auto;

        }


        /* ===================================== */
        /* INFORMATION SECTION */
        /* ===================================== */

        .label-information {

            height: 39mm;

            vertical-align: middle;

        }


        /* ===================================== */
        /* INNER INFORMATION WRAPPER */
        /* PADDING GOES HERE INSTEAD OF TD */
        /* ===================================== */

        .information-content {

            padding:
                3mm
                3.5mm;

        }


        /* ===================================== */
        /* BUILDING NAME */
        /* ===================================== */

        .system-name {

            margin: 0;

            padding: 0;

            font-size: 6pt;

            font-weight: bold;

            line-height: 1.2;

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

            padding: 0;

            font-size: 10pt;

            font-weight: bold;

            line-height: 1.15;

        }


        /* ===================================== */
        /* QR ID LABEL */
        /* ===================================== */

        .qr-id-label {

            margin:
                4mm
                0
                0;

            padding: 0;

            font-size: 5pt;

            color: #64748b;

            text-transform: uppercase;

        }


        /* ===================================== */
        /* QR ID VALUE */
        /* ===================================== */

        .qr-id {

            margin:
                0.8mm
                0
                0;

            padding: 0;

            font-size: 6.5pt;

            font-weight: bold;

            font-family:
                DejaVu Sans Mono,
                monospace;

        }

    </style>

</head>


<body>

    {{-- ===================================== --}}
    {{-- SINGLE PHYSICAL LABEL --}}
    {{-- ===================================== --}}

    <table
        class="qr-label"
        cellspacing="0"
        cellpadding="0"
    >

        <tr>

            {{-- ===================================== --}}
            {{-- QR CODE --}}
            {{-- ===================================== --}}

            <td class="qr-section">

                <img
                    src="{{ $qrImage }}"
                    class="qr-image"
                    alt="QR Code"
                >

            </td>


            {{-- ===================================== --}}
            {{-- EQUIPMENT INFORMATION --}}
            {{-- ===================================== --}}

            <td class="label-information">

                <div class="information-content">

                    <p class="system-name">

                        {{ $equipment->building_name ?? 'Campus' }}
                        Equipment

                    </p>


                    <p class="equipment-name">

                        {{ $equipment->equipment_name }}

                    </p>


                    <p class="qr-id-label">

                        QR ID

                    </p>


                    <p class="qr-id">

                        {{ $equipment->equipment_qr_code }}

                    </p>

                </div>

            </td>

        </tr>

    </table>

</body>

</html>