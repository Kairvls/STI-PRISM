<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print QR Labels</title>

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            background: #ffffff;
            font-family: Inter, Arial, Helvetica, sans-serif;
            color: #0f172a;
        }

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
            gap: 16px;
            padding: 0 24px;
            border-bottom: 1px solid #e2e8f0;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(12px);
        }

        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
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
            flex-shrink: 0;
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

        .toolbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .copies-control {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0 12px;
            height: 40px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #ffffff;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        .copies-control select {
            border: 0;
            background: transparent;
            color: #0f172a;
            font-size: 13px;
            font-weight: 700;
            outline: none;
            cursor: pointer;
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

        .preview-workspace {
            min-height: 100vh;
            padding: 104px 24px 48px;
        }

        .preview-intro {
            max-width: 210mm;
            margin: 0 auto 20px;
            text-align: center;
        }

        .preview-intro h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .preview-intro p {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        /* A4 sheet: 2 columns × 80×40mm labels ≈ 14 per page */
        .label-sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 24px;
            padding: 8mm;
            display: grid;
            grid-template-columns: repeat(2, 80mm);
            grid-auto-rows: 40mm;
            gap: 4mm;
            justify-content: center;
            align-content: start;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.06);
        }

        .qr-label {
            width: 80mm;
            height: 40mm;
            display: grid;
            grid-template-columns: 40mm 1fr;
            overflow: hidden;
            border: 1px solid #0f172a;
            background: #ffffff;
            break-inside: avoid;
            page-break-inside: avoid;
        }

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

        .label-information {
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4mm;
        }

        .system-name {
            margin: 0;
            font-size: 7pt;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .equipment-name {
            margin: 3mm 0 0;
            overflow: hidden;
            font-size: 13pt;
            font-weight: 700;
            line-height: 1.15;
            overflow-wrap: anywhere;
        }

        .qr-id-label {
            margin: auto 0 0;
            font-size: 6.5pt;
            color: #64748b;
            text-transform: uppercase;
        }

        .qr-id {
            margin: 1mm 0 0;
            font-family: "Courier New", monospace;
            font-size: 8pt;
            font-weight: 700;
            letter-spacing: 0.04em;
            overflow-wrap: anywhere;
        }

        .label-template {
            display: none;
        }

        @page {
            size: A4;
            margin: 0;
        }

        @media print {
            html,
            body {
                width: auto !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
            }

            .preview-toolbar,
            .preview-intro {
                display: none !important;
            }

            .preview-workspace {
                padding: 0 !important;
            }

            .label-sheet {
                width: 210mm;
                min-height: 297mm;
                margin: 0;
                padding: 8mm;
                border: 0;
                box-shadow: none;
                page-break-after: always;
                break-after: page;
            }

            .label-sheet:last-child {
                page-break-after: auto;
                break-after: auto;
            }

            .qr-label {
                border: 1px solid #000000 !important;
            }
        }
    </style>
</head>
<body>
    @php
        $labelCount = $equipments->count();
        $labelsPerPage = 14;
    @endphp

    <div class="print-preview">
        <header class="preview-toolbar">
            <div class="toolbar-left">
                <button
                    type="button"
                    class="icon-button"
                    onclick="window.close()"
                    aria-label="Close print preview"
                >
                    &lt;
                </button>

                <div>
                    <p class="preview-eyebrow">QR Code Tools</p>
                    <h1 class="preview-title">
                        Print Equipment Label{{ $labelCount === 1 ? '' : 's' }}
                    </h1>
                </div>
            </div>

            <div class="toolbar-right">
                <label class="copies-control">
                    <span>Copies each</span>
                    <select id="copiesSelect" aria-label="Copies of each label">
                        @for ($i = 1; $i <= 14; $i++)
                            <option value="{{ $i }}" @selected($i === 1)>{{ $i }}</option>
                        @endfor
                    </select>
                </label>

                <button
                    type="button"
                    class="print-button"
                    onclick="window.print()"
                >
                    Print QR Code{{ $labelCount === 1 ? '' : 's' }}
                </button>
            </div>
        </header>

        <main class="preview-workspace">
            <div class="preview-intro">
                <h2>
                    {{ $labelCount }} label{{ $labelCount === 1 ? '' : 's' }} · A4 sheet
                </h2>
                <p>
                    Up to {{ $labelsPerPage }} labels fit on each page (2 × 7).
                    Increase copies to fill unused space on the sheet.
                </p>
            </div>

            <div id="labelSheets"></div>

            {{-- Source labels cloned into the A4 grid --}}
            <div id="labelSource" class="label-template" aria-hidden="true">
                @foreach ($equipments as $equipment)
                    <section
                        class="qr-label"
                        data-qr-code="{{ $equipment->equipment_qr_code }}"
                    >
                        <div class="qr-section">
                            <img
                                src="{{ url('/maintenance/equipment/qr-image/' . $equipment->equipment_qr_code) }}"
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

                            <p class="qr-id-label">QR ID</p>

                            <p class="qr-id">
                                {{ $equipment->equipment_qr_code }}
                            </p>
                        </div>
                    </section>
                @endforeach
            </div>
        </main>
    </div>

    <script>
        (function () {
            const labelsPerPage = {{ $labelsPerPage }};
            const sheetsEl = document.getElementById('labelSheets');
            const sourceEl = document.getElementById('labelSource');
            const copiesSelect = document.getElementById('copiesSelect');

            function buildSheets() {
                const copies = Math.max(1, Math.min(14, Number(copiesSelect.value) || 1));
                const sourceLabels = Array.from(sourceEl.querySelectorAll('.qr-label'));
                const rendered = [];

                sourceLabels.forEach(function (label) {
                    for (let i = 0; i < copies; i += 1) {
                        rendered.push(label.cloneNode(true));
                    }
                });

                sheetsEl.innerHTML = '';

                for (let i = 0; i < rendered.length; i += labelsPerPage) {
                    const sheet = document.createElement('div');
                    sheet.className = 'label-sheet';

                    rendered.slice(i, i + labelsPerPage).forEach(function (label) {
                        sheet.appendChild(label);
                    });

                    sheetsEl.appendChild(sheet);
                }
            }

            function openPrintWhenReady() {
                const images = Array.from(document.querySelectorAll('#labelSheets img'));

                if (!images.length) {
                    setTimeout(function () {
                        window.print();
                    }, 150);
                    return;
                }

                let remaining = images.length;

                function maybePrint() {
                    remaining -= 1;
                    if (remaining <= 0) {
                        setTimeout(function () {
                            window.print();
                        }, 150);
                    }
                }

                images.forEach(function (img) {
                    if (img.complete && img.naturalWidth > 0) {
                        maybePrint();
                        return;
                    }

                    img.addEventListener('load', maybePrint, { once: true });
                    img.addEventListener('error', maybePrint, { once: true });
                });
            }

            copiesSelect.addEventListener('change', buildSheets);

            window.addEventListener('load', function () {
                buildSheets();
                openPrintWhenReady();
            });
        })();
    </script>
</body>
</html>
