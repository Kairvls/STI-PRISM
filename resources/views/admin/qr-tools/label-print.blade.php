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
            max-width: var(--sheet-width, 210mm);
            margin: 0 auto 20px;
            text-align: center;
        }

        .preview-intro h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .preview-intro h2 .sheet-label {
            font-weight: 700;
        }

        .paper-size-select {
            appearance: none;
            -webkit-appearance: none;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M3 4.5L6 8l3-3.5'/%3E%3C/svg%3E") no-repeat right 10px center;
            padding: 4px 28px 4px 10px;
            color: #0f172a;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: -0.02em;
            outline: none;
            cursor: pointer;
            line-height: 1.3;
        }

        .paper-size-select:focus {
            border-color: #94a3b8;
        }

        .preview-intro p {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        .preview-intro .fit-warning {
            color: #b45309;
        }

        .print-paper-tip {
            display: none;
            max-width: var(--sheet-width, 210mm);
            margin: 0 auto 16px;
            padding: 10px 14px;
            border: 1px solid #fde68a;
            border-radius: 10px;
            background: #fffbeb;
            color: #92400e;
            font-size: 12px;
            line-height: 1.45;
            text-align: center;
        }

        .print-paper-tip.is-visible {
            display: block;
        }

        .print-paper-tip strong {
            font-weight: 700;
        }

        /* Label sheet size is driven by --sheet-width / --sheet-height (paper selector) */
        .label-sheet {
            width: var(--sheet-width, 210mm);
            min-height: var(--sheet-height, 297mm);
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
            transition: width 0.2s ease, min-height 0.2s ease;
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

        @media print {
            html,
            body {
                width: var(--sheet-width, 210mm) !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
            }

            .preview-toolbar,
            .preview-intro,
            .print-paper-tip {
                display: none !important;
            }

            .preview-workspace {
                padding: 0 !important;
            }

            .label-sheet {
                width: var(--sheet-width, 210mm);
                min-height: var(--sheet-height, 297mm);
                height: var(--sheet-height, 297mm);
                margin: 0;
                padding: 8mm;
                border: 0;
                box-shadow: none;
                page-break-after: always;
                break-after: page;
                overflow: hidden;
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
    {{-- Updated by JS so the print dialog gets the selected paper size --}}
    <style id="dynamicPageStyle">
        @page {
            size: A4;
            margin: 0;
        }
    </style>
</head>
<body>
    @php
        $labelCount = $equipments->count();
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
                    <span>Paper</span>
                    <select id="paperSelect" aria-label="Paper size">
                        <option value="a4" selected>A4</option>
                        <option value="short">Short (Letter)</option>
                        <option value="long">Long (Folio)</option>
                        <option value="legal">Legal</option>
                    </select>
                </label>

                <label class="copies-control">
                    <span>Copies each</span>
                    <select id="copiesSelect" aria-label="Copies of each label">
                        @for ($i = 1; $i <= 16; $i++)
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
                    <span id="labelCountText">
                        {{ $labelCount }} label{{ $labelCount === 1 ? '' : 's' }}
                    </span>
                    <span aria-hidden="true">·</span>
                    <label class="sheet-label" for="paperSelectIntro">
                        <select
                            id="paperSelectIntro"
                            class="paper-size-select"
                            aria-label="Paper size for preview"
                        >
                            <option value="a4" selected>A4 sheet</option>
                            <option value="short">Short bond paper</option>
                            <option value="long">Long bond paper</option>
                            <option value="legal">Legal bond paper</option>
                        </select>
                    </label>
                </h2>
                <p id="fitSummary">
                    Up to 14 labels fit on each page (2 × 7).
                    Increase copies to fill unused space on the sheet.
                </p>
            </div>

            <p id="printPaperTip" class="print-paper-tip" role="note"></p>

            <div id="labelSheets"></div>

            {{-- Source labels cloned into the selected paper grid --}}
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
            const LABEL_W_MM = 80;
            const LABEL_H_MM = 40;
            const GAP_MM = 4;
            const PAD_MM = 8;
            const COLS = 2;

            // Preview uses real PH sizes. @page uses CSS named sizes Chrome/Edge
            // recognize in the print dialog (custom Folio often stays stuck on A4).
            const PAPER_SIZES = {
                a4: {
                    id: 'a4',
                    name: 'A4',
                    // Named size so the print dialog Paper size can switch off A4.
                    pageSize: 'A4',
                    dialogLabel: 'A4',
                    widthMm: 210,
                    heightMm: 297,
                },
                short: {
                    id: 'short',
                    name: 'Short',
                    pageSize: 'letter',
                    dialogLabel: 'Letter',
                    widthMm: 215.9,
                    heightMm: 279.4,
                },
                long: {
                    id: 'long',
                    name: 'Long',
                    // Folio (8.5×13) is not a CSS named size and is missing from
                    // many drivers (e.g. Print to PDF). Use Legal so the dialog
                    // can leave A4; layout still uses true Folio height below.
                    pageSize: 'legal',
                    dialogLabel: 'Legal (or Folio / 8.5×13 if listed)',
                    widthMm: 215.9,
                    heightMm: 330.2,
                },
                legal: {
                    id: 'legal',
                    name: 'Legal',
                    pageSize: 'legal',
                    dialogLabel: 'Legal',
                    widthMm: 215.9,
                    heightMm: 355.6,
                },
            };

            const sheetsEl = document.getElementById('labelSheets');
            const sourceEl = document.getElementById('labelSource');
            const copiesSelect = document.getElementById('copiesSelect');
            const paperSelect = document.getElementById('paperSelect');
            const paperSelectIntro = document.getElementById('paperSelectIntro');
            const fitSummary = document.getElementById('fitSummary');
            const printPaperTip = document.getElementById('printPaperTip');
            const pageStyleEl = document.getElementById('dynamicPageStyle');
            const root = document.documentElement;

            function rowsForHeight(heightMm) {
                const usable = heightMm - PAD_MM * 2;
                let rows = 0;

                while (true) {
                    const next = rows + 1;
                    const needed = next * LABEL_H_MM + Math.max(0, next - 1) * GAP_MM;
                    if (needed <= usable + 0.01) {
                        rows = next;
                    } else {
                        break;
                    }
                }

                return Math.max(1, rows);
            }

            function colsForWidth(widthMm) {
                const usable = widthMm - PAD_MM * 2;
                let cols = 0;

                while (true) {
                    const next = cols + 1;
                    const needed = next * LABEL_W_MM + Math.max(0, next - 1) * GAP_MM;
                    if (needed <= usable + 0.01) {
                        cols = next;
                    } else {
                        break;
                    }
                }

                return Math.max(1, Math.min(COLS, cols));
            }

            function getPaper() {
                return PAPER_SIZES[paperSelect.value] || PAPER_SIZES.a4;
            }

            function getLabelsPerPage(paper) {
                const cols = colsForWidth(paper.widthMm);
                const rows = rowsForHeight(paper.heightMm);
                return {
                    cols: cols,
                    rows: rows,
                    total: cols * rows,
                };
            }

            function syncPaperSelects(value) {
                paperSelect.value = value;
                paperSelectIntro.value = value;
            }

            function applyPaperStyles(paper) {
                root.style.setProperty('--sheet-width', paper.widthMm + 'mm');
                root.style.setProperty('--sheet-height', paper.heightMm + 'mm');

                // Use CSS named sizes only (A4 / letter / legal). Custom mm sizes
                // are ignored by Chromium for the Paper size dropdown and it stays on A4.
                pageStyleEl.textContent =
                    '@page { size: ' + paper.pageSize + '; margin: 0; }';
            }

            function updatePrintTip(paper) {
                if (!printPaperTip) {
                    return;
                }

                printPaperTip.innerHTML =
                    'When the print window opens, open <strong>More settings</strong> and set <strong>Paper size</strong> to <strong>' +
                    paper.dialogLabel +
                    '</strong>. Browsers often leave this on A4 even after you pick a different paper here.';
                printPaperTip.classList.add('is-visible');
            }

            function updateCopiesOptions(maxCopies) {
                const current = Number(copiesSelect.value) || 1;
                const previousMax = copiesSelect.options.length;
                const nextMax = Math.max(1, maxCopies);

                if (previousMax === nextMax) {
                    if (current > nextMax) {
                        copiesSelect.value = String(nextMax);
                    }
                    return;
                }

                copiesSelect.innerHTML = '';
                for (let i = 1; i <= nextMax; i += 1) {
                    const option = document.createElement('option');
                    option.value = String(i);
                    option.textContent = String(i);
                    copiesSelect.appendChild(option);
                }

                copiesSelect.value = String(Math.min(current, nextMax));
            }

            function updateFitSummary(paper, fit) {
                const canFitTwoCols = fit.cols >= 2;
                let text =
                    'Up to ' +
                    fit.total +
                    ' labels fit on each page (' +
                    fit.cols +
                    ' × ' +
                    fit.rows +
                    ').';

                if (!canFitTwoCols) {
                    text +=
                        ' This paper is too narrow for the usual 2-column layout.';
                    fitSummary.classList.add('fit-warning');
                } else {
                    text += ' Increase copies to fill unused space on the sheet.';
                    fitSummary.classList.remove('fit-warning');
                }

                fitSummary.textContent = text;
            }

            function buildSheets() {
                const paper = getPaper();
                const fit = getLabelsPerPage(paper);
                const labelsPerPage = fit.total;
                const copies = Math.max(
                    1,
                    Math.min(labelsPerPage, Number(copiesSelect.value) || 1)
                );

                applyPaperStyles(paper);
                updatePrintTip(paper);
                updateCopiesOptions(labelsPerPage);
                updateFitSummary(paper, fit);
                copiesSelect.value = String(copies);

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
                    sheet.style.gridTemplateColumns =
                        'repeat(' + fit.cols + ', ' + LABEL_W_MM + 'mm)';

                    rendered.slice(i, i + labelsPerPage).forEach(function (label) {
                        sheet.appendChild(label);
                    });

                    sheetsEl.appendChild(sheet);
                }
            }

            function onPaperChange(value) {
                syncPaperSelects(value);
                buildSheets();
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

            paperSelect.addEventListener('change', function () {
                onPaperChange(paperSelect.value);
            });

            paperSelectIntro.addEventListener('change', function () {
                onPaperChange(paperSelectIntro.value);
            });

            copiesSelect.addEventListener('change', buildSheets);

            // Re-apply right before print so @page matches the current selection.
            window.addEventListener('beforeprint', function () {
                applyPaperStyles(getPaper());
            });

            window.addEventListener('load', function () {
                buildSheets();
                openPrintWhenReady();
            });
        })();
    </script>
</body>
</html>
