{{-- Accounting design system — scoped to Accounting pages only --}}
<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800;900&display=swap"
    rel="stylesheet"
/>
<style>
    :root {
        --acc-brand: #0037c7;
        --acc-brand-soft: rgba(0, 55, 199, 0.85);
        --acc-primary: #ffd400;
        --acc-page-bg: #f1f5f9;
        --acc-card: #ffffff;
        --acc-ink: #0f172a;
        --acc-muted: #64748b;
        --acc-border: #e2e8f0;
        --acc-radius: 14px;
        --acc-radius-lg: 16px;
        --acc-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    /* Beat global Poppins on Accounting content */
    main,
    main *,
    .acc-page,
    .acc-page * {
        font-family: "Inter", sans-serif;
    }

    .acc-page-title,
    .acc-stat-value,
    .acc-panel-title {
        font-family: "Outfit", sans-serif;
    }

    /* Tighter main canvas on Accounting (laptop-friendly) */
    main.flex-1 {
        padding: 1.125rem 1.35rem !important;
        background: var(--acc-page-bg) !important;
    }

    @media (min-width: 1280px) {
        main.flex-1 {
            padding: 1.25rem 1.5rem !important;
        }
    }

    .fade-in { animation: accFade .28s ease both; }
    .slide-up { animation: accUp .32s ease both; }
    .card-hover { transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
    .card-hover:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(15, 23, 42, .06); }
    @keyframes accFade { from { opacity: 0 } to { opacity: 1 } }
    @keyframes accUp { from { opacity: 0; transform: translateY(6px) } to { opacity: 1; transform: none } }
    @keyframes accModal { from { opacity: 0; transform: translateY(8px) scale(.98) } to { opacity: 1; transform: none } }
    @keyframes accSpin { to { transform: rotate(360deg) } }

    /* ---------- Page chrome ---------- */
    .acc-page { max-width: 100%; }

    .acc-page-header {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        justify-content: space-between;
        gap: 0.75rem 1rem;
        margin-bottom: 0.875rem;
    }

    .acc-page-kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.6875rem;
        font-weight: 600;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #94a3b8;
    }

    .acc-page-kicker::before {
        content: "";
        width: 0.4rem;
        height: 0.4rem;
        border-radius: 999px;
        background: var(--acc-primary);
    }

    .acc-page-title {
        margin-top: 0.15rem;
        font-size: 1.625rem;
        line-height: 1.15;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: var(--acc-ink);
    }

    .acc-page-subtitle {
        margin-top: 0.2rem;
        font-size: 0.8125rem;
        line-height: 1.45;
        color: var(--acc-muted);
        max-width: 40rem;
    }

    .acc-back {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: #64748b;
        text-decoration: none;
    }

    .acc-back:hover { color: var(--acc-ink); }

    /* ---------- Surfaces ---------- */
    .acc-card {
        background: var(--acc-card);
        border: 1px solid var(--acc-border);
        border-radius: var(--acc-radius-lg);
        box-shadow: var(--acc-shadow);
        overflow: hidden;
    }

    .acc-panel {
        background: var(--acc-card);
        border: 1px solid var(--acc-border);
        border-radius: var(--acc-radius-lg);
        box-shadow: var(--acc-shadow);
        padding: 0.875rem 1rem;
    }

    .acc-panel-title {
        font-size: 0.8125rem;
        font-weight: 700;
        color: var(--acc-ink);
        line-height: 1.2;
    }

    .acc-panel-sub {
        margin-top: 0.1rem;
        font-size: 0.6875rem;
        color: #94a3b8;
    }

    /* ---------- Stat cards ---------- */
    .acc-stat-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
    }

    @media (min-width: 1280px) {
        .acc-stat-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }

    .acc-stat-card {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-height: 72px;
        padding: 0.75rem 0.9rem;
        padding-right: 3.25rem;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: var(--acc-radius);
        box-shadow: var(--acc-shadow);
        text-decoration: none;
        color: inherit;
        transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }

    a.acc-stat-card:hover {
        transform: translateY(-1px);
        border-color: #d1d5db;
        box-shadow: 0 6px 16px rgba(15, 23, 42, .06);
    }

    .acc-stat-label {
        font-size: 0.6875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #64748b;
    }

    .acc-stat-label.is-warn { color: #b45309; }
    .acc-stat-label.is-ok { color: #047857; }
    .acc-stat-label.is-info { color: #0369a1; }

    .acc-stat-value {
        margin-top: 0.2rem;
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--acc-ink);
        line-height: 1.1;
        letter-spacing: -0.02em;
    }

    .acc-stat-hint {
        margin-top: 0.15rem;
        font-size: 0.6875rem;
        color: #94a3b8;
    }

    .acc-stat-icon {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        width: 2.25rem;
        height: 2.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.75rem;
    }

    .acc-stat-icon svg { width: 1rem; height: 1rem; }
    .acc-stat-icon.is-warn { background: #fffbeb; color: #d97706; }
    .acc-stat-icon.is-ok { background: #ecfdf5; color: #059669; }
    .acc-stat-icon.is-info { background: #f0f9ff; color: #0284c7; }

    .acc-mini-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.625rem;
        margin-top: 0.75rem;
    }

    @media (min-width: 1024px) {
        .acc-mini-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }

    .acc-mini-card {
        background: #fff;
        border: 1px solid var(--acc-border);
        border-radius: 12px;
        padding: 0.625rem 0.75rem;
        box-shadow: var(--acc-shadow);
    }

    .acc-mini-card p:first-child {
        font-size: 0.6875rem;
        font-weight: 500;
        color: #64748b;
    }

    .acc-mini-card p:last-child {
        margin-top: 0.15rem;
        font-family: "Outfit", sans-serif;
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--acc-ink);
        line-height: 1.1;
    }

    /* ---------- Filters / search ---------- */
    .acc-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
    }

    .acc-search {
        height: 2.25rem;
        min-width: 12rem;
        max-width: 18rem;
        width: 100%;
        border-radius: 0.625rem;
        border: 1px solid var(--acc-border);
        background: #fff;
        padding: 0 0.75rem;
        font-size: 0.8125rem;
        color: var(--acc-ink);
        outline: none;
    }

    .acc-search:focus {
        border-color: #cbd5e1;
        box-shadow: 0 0 0 3px rgba(0, 55, 199, 0.08);
    }

    .acc-select {
        height: 2.25rem;
        border-radius: 0.625rem;
        border: 1px solid var(--acc-border);
        background: #fff;
        padding: 0 0.625rem;
        font-size: 0.8125rem;
        color: var(--acc-ink);
    }

    .acc-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 0.375rem;
        margin: 0.625rem 0 0.75rem;
    }

    .acc-chip {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 0.3rem 0.7rem;
        font-size: 0.6875rem;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid var(--acc-border);
        background: #fff;
        color: #475569;
        transition: background .12s ease, color .12s ease, border-color .12s ease;
    }

    .acc-chip:hover { background: #f8fafc; color: var(--acc-ink); }

    .acc-chip.is-active {
        background: #0f172a;
        border-color: #0f172a;
        color: #fff;
    }

    /* ---------- Tables ---------- */
    .acc-table-wrap {
        overflow: auto;
        background: #fff;
        border: 1px solid var(--acc-border);
        border-radius: var(--acc-radius-lg);
        box-shadow: var(--acc-shadow);
    }

    .acc-table {
        width: 100%;
        border-collapse: collapse;
    }

    .acc-table thead {
        background: #f8fafc;
        border-bottom: 1px solid var(--acc-border);
    }

    .acc-table th {
        padding: 0.55rem 0.85rem;
        text-align: left;
        font-size: 0.625rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #64748b;
        white-space: nowrap;
    }

    .acc-table td {
        padding: 0.6rem 0.85rem;
        font-size: 0.8125rem;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .acc-table tbody tr { transition: background .12s ease; }
    .acc-table tbody tr:hover { background: #f8fafc; }
    .acc-table tbody tr:last-child td { border-bottom: 0; }

    .acc-table .acc-ref {
        font-weight: 600;
        color: var(--acc-ink);
    }

    .acc-table .acc-money {
        text-align: right;
        font-variant-numeric: tabular-nums;
        font-weight: 600;
        color: var(--acc-ink);
        white-space: nowrap;
    }

    .acc-table .acc-muted { color: #64748b; }

    .acc-row-link {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--acc-brand);
        text-decoration: none;
    }

    .acc-row-link:hover { text-decoration: underline; }

    .acc-link {
        color: var(--acc-brand);
        font-weight: 600;
        text-decoration: none;
    }

    .acc-link:hover { text-decoration: underline; }

    /* ---------- Buttons ---------- */
    .acc-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.4rem;
    }

    .acc-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        height: 2.25rem;
        border-radius: 0.625rem;
        padding: 0 0.875rem;
        font-size: 0.8125rem;
        font-weight: 600;
        border: 1px solid transparent;
        cursor: pointer;
        transition: background .12s ease, border-color .12s ease, color .12s ease, transform .12s ease;
        white-space: nowrap;
    }

    .acc-btn:active { transform: scale(0.98); }

    .acc-btn-approve { background: #059669; color: #fff; }
    .acc-btn-approve:hover { background: #047857; }

    .acc-btn-revise {
        background: #fffbeb;
        color: #92400e;
        border-color: #fcd34d;
    }
    .acc-btn-revise:hover { background: #fef3c7; }

    .acc-btn-funds { background: #111827; color: #fff; }
    .acc-btn-funds:hover { background: #030712; }

    .acc-btn-ghost {
        background: #fff;
        color: #374151;
        border-color: #e5e7eb;
    }
    .acc-btn-ghost:hover { background: #f8fafc; }

    .acc-btn-primary {
        background: var(--acc-brand-soft);
        color: #fff;
    }
    .acc-btn-primary:hover { background: rgba(0, 44, 155, 0.85); }

    /* ---------- Review layout ---------- */
    .acc-review-head {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #e2e8f0;
        position: sticky;
        top: 0;
        z-index: 5;
        background: var(--acc-page-bg);
    }

    .acc-review-grid {
        display: grid;
        gap: 0.75rem;
        grid-template-columns: minmax(0, 1fr);
        align-items: start;
    }

    @media (min-width: 1200px) {
        .acc-review-grid {
            grid-template-columns: minmax(0, 1fr) 260px;
        }
    }

    .acc-side-stack {
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
    }

    @media (min-width: 1200px) {
        .acc-side-stack {
            position: sticky;
            top: 4.5rem;
            max-height: calc(100vh - 6rem);
            overflow: auto;
        }
    }

    /* ---------- Document viewer (fit to screen) ---------- */
    .acc-viewer {
        background: #e8edf3;
        border: 1px solid var(--acc-border);
        border-radius: var(--acc-radius-lg);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        min-height: 280px;
        height: calc(100vh - 210px);
        max-height: calc(100vh - 180px);
    }

    @media (max-width: 1199px) {
        .acc-viewer {
            height: auto;
            max-height: none;
            min-height: 360px;
        }
    }

    .acc-viewer-stage {
        flex: 1 1 auto;
        min-height: 0;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0.5rem;
        position: relative;
    }

    .acc-viewer-fit {
        transform-origin: center center;
        will-change: transform;
    }

    .acc-viewer-fit .rfc-print-sheet,
    .acc-viewer-fit .liq-print-sheet,
    .acc-viewer-fit .acc-paper {
        margin: 0 auto;
        max-width: none !important;
        box-shadow: 0 8px 28px rgba(15, 23, 42, 0.12);
    }

    /* Compact ATP paper inside viewer */
    .acc-paper {
        width: 210mm;
        max-width: none;
        background: #fff;
        padding: 1.5rem 1.75rem;
        color: #0f172a;
    }

    .acc-paper-title {
        text-align: center;
    }

    .acc-paper-title .org {
        font-size: 1rem;
        font-weight: 700;
    }

    .acc-paper-title .doc {
        margin-top: 0.15rem;
        font-size: 0.8125rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        color: #334155;
    }

    .acc-paper dl {
        display: grid;
        gap: 0.625rem 1rem;
        margin-top: 1rem;
        font-size: 0.8125rem;
    }

    @media (min-width: 480px) {
        .acc-paper dl { grid-template-columns: 1fr 1fr; }
    }

    .acc-paper dt {
        font-size: 0.625rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #94a3b8;
    }

    .acc-paper dd { margin-top: 0.1rem; font-weight: 500; }

    .acc-paper table {
        width: 100%;
        margin-top: 1rem;
        font-size: 0.75rem;
        border-collapse: collapse;
    }

    .acc-paper thead { background: #f8fafc; }
    .acc-paper th {
        padding: 0.35rem 0.5rem;
        text-align: left;
        font-size: 0.625rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #64748b;
        border-bottom: 1px solid #e2e8f0;
    }
    .acc-paper td {
        padding: 0.35rem 0.5rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .acc-attachments {
        margin-top: 0.625rem;
        background: #fff;
        border: 1px solid var(--acc-border);
        border-radius: var(--acc-radius);
        padding: 0.75rem 0.875rem;
    }

    .acc-attachments h3 {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--acc-ink);
    }

    .acc-attachments ul {
        margin-top: 0.4rem;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .acc-attachments a {
        font-size: 0.8125rem;
        font-weight: 500;
        color: var(--acc-brand);
        text-decoration: none;
    }

    .acc-attachments a:hover { text-decoration: underline; }

    /* ---------- Modals / revise box ---------- */
    .acc-modal {
        animation: accModal .2s ease both;
        border-radius: var(--acc-radius);
        border: 1px solid #fcd34d;
        background: #fffbeb;
        padding: 0.875rem 1rem;
        margin-bottom: 0.75rem;
    }

    .acc-modal h3 {
        font-size: 0.8125rem;
        font-weight: 700;
        color: #78350f;
    }

    .acc-modal textarea {
        margin-top: 0.5rem;
        width: 100%;
        border-radius: 0.625rem;
        border: 1px solid #fde68a;
        background: #fff;
        padding: 0.625rem 0.75rem;
        font-size: 0.8125rem;
        resize: vertical;
        min-height: 4.5rem;
        max-height: 40vh;
    }

    .acc-backdrop { background: rgba(15, 23, 42, .45); }

    /* ---------- Empty / flash / notes ---------- */
    .acc-empty {
        border: 1px dashed #e2e8f0;
        background: #f8fafc;
        border-radius: 0.75rem;
        padding: 1.75rem 1rem;
        text-align: center;
        font-size: 0.8125rem;
        color: #94a3b8;
    }

    .acc-note {
        border-radius: 0.75rem;
        padding: 0.625rem 0.75rem;
        font-size: 0.75rem;
        line-height: 1.4;
    }

    .acc-note-ok {
        border: 1px solid #a7f3d0;
        background: #ecfdf5;
        color: #065f46;
        font-weight: 500;
    }

    .acc-note-info {
        border: 1px solid #bae6fd;
        background: #f0f9ff;
        color: #0c4a6e;
    }

    .acc-flash {
        margin-bottom: 0.75rem;
        border-radius: 0.75rem;
        padding: 0.625rem 0.875rem;
        font-size: 0.8125rem;
        font-weight: 500;
    }

    .acc-flash-ok {
        border: 1px solid #a7f3d0;
        background: #ecfdf5;
        color: #065f46;
    }

    .acc-flash-err {
        border: 1px solid #fecdd3;
        background: #fff1f2;
        color: #9f1239;
    }

    /* ---------- Feed / queue list ---------- */
    .acc-feed-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.625rem 0.875rem;
        text-decoration: none;
        color: inherit;
        border-bottom: 1px solid #f1f5f9;
        transition: background .12s ease;
    }

    .acc-feed-item:last-child { border-bottom: 0; }
    .acc-feed-item:hover { background: #f8fafc; }

    .acc-feed-type {
        width: 4.5rem;
        flex-shrink: 0;
        font-size: 0.625rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #94a3b8;
    }

    .acc-feed-money {
        width: 5.5rem;
        text-align: right;
        font-size: 0.8125rem;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
        color: var(--acc-ink);
        flex-shrink: 0;
    }

    .acc-feed-action {
        width: 5rem;
        text-align: right;
        font-size: 0.6875rem;
        font-weight: 600;
        color: var(--acc-brand);
        flex-shrink: 0;
    }

    .acc-activity-item {
        padding: 0.5rem 0;
        border-bottom: 1px solid #f8fafc;
    }

    .acc-activity-item:last-child { border-bottom: 0; }

    /* ---------- Related docs / history ---------- */
    .acc-chain-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        border: 1px solid #f1f5f9;
        border-radius: 0.5rem;
        padding: 0.4rem 0.55rem;
    }

    .acc-chain-item p:first-child {
        font-size: 0.5625rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #94a3b8;
    }

    .acc-chain-item p:last-child {
        font-size: 0.75rem;
        font-weight: 500;
        color: var(--acc-ink);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 9rem;
    }

    .acc-timeline-dot {
        margin-top: 0.35rem;
        width: 0.5rem;
        height: 0.5rem;
        border-radius: 999px;
        flex-shrink: 0;
    }

    /* ---------- Pagination spacing ---------- */
    .acc-pagination { margin-top: 0.75rem; }

    /* ---------- Notifications list ---------- */
    .acc-notif-item {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .acc-notif-item:last-child { border-bottom: 0; }

    @media (max-width: 900px) {
        .acc-feed-action { display: none; }
        .acc-feed-money { width: 4.5rem; font-size: 0.75rem; }
        .acc-feed-type { width: 3.25rem; }
        .acc-page-header { align-items: flex-start; }
        .acc-search { max-width: none; }
    }

    /* Laptop density tweaks */
    @media (max-height: 820px) {
        .acc-page-title { font-size: 1.375rem; }
        .acc-stat-card { min-height: 64px; }
        .acc-stat-value { font-size: 1.35rem; }
        .acc-viewer {
            height: calc(100vh - 190px);
            max-height: calc(100vh - 160px);
        }
        .acc-review-head { margin-bottom: 0.5rem; padding-bottom: 0.5rem; }
    }

    @media print {
        aside, nav, header, .acc-actions, .acc-back, .acc-side-stack, .acc-modal, .acc-page-subtitle {
            display: none !important;
        }
        main.flex-1 {
            padding: 0 !important;
            background: #fff !important;
        }
        .acc-viewer {
            height: auto !important;
            max-height: none !important;
            border: 0;
            box-shadow: none;
        }
        .acc-viewer-stage {
            overflow: visible !important;
            height: auto !important;
        }
        .acc-viewer-fit {
            transform: none !important;
            width: auto !important;
            height: auto !important;
            margin: 0 !important;
        }
    }
</style>

<script>
    (function () {
        function fitAccountingDocuments() {
            document.querySelectorAll('.acc-viewer').forEach(function (viewer) {
                var stage = viewer.querySelector('.acc-viewer-stage');
                var fit = viewer.querySelector('.acc-viewer-fit');
                if (!stage || !fit) return;

                fit.style.transform = 'none';
                fit.style.width = 'auto';
                fit.style.height = 'auto';
                fit.style.margin = '0';

                var sheet = fit.querySelector('.rfc-print-sheet, .liq-print-sheet, .acc-paper') || fit.firstElementChild;
                if (!sheet) return;

                var pad = 16;
                var availW = Math.max(140, stage.clientWidth - pad);
                var availH = Math.max(140, stage.clientHeight - pad);
                var docW = Math.max(sheet.scrollWidth, sheet.offsetWidth);
                var docH = Math.max(sheet.scrollHeight, sheet.offsetHeight);
                if (!docW || !docH) return;

                var scale = Math.min(availW / docW, availH / docH, 1);
                scale = Math.max(0.28, Math.round(scale * 1000) / 1000);

                fit.style.width = docW + 'px';
                fit.style.height = docH + 'px';
                fit.style.transform = 'scale(' + scale + ')';
                fit.style.margin = ((docH * (scale - 1)) / 2) + 'px ' + ((docW * (scale - 1)) / 2) + 'px';
            });
        }

        window.fitAccountingDocuments = fitAccountingDocuments;

        function scheduleFit() {
            requestAnimationFrame(function () {
                fitAccountingDocuments();
                setTimeout(fitAccountingDocuments, 80);
                setTimeout(fitAccountingDocuments, 320);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', scheduleFit);
        } else {
            scheduleFit();
        }

        window.addEventListener('resize', function () {
            clearTimeout(window.__accFitTimer);
            window.__accFitTimer = setTimeout(fitAccountingDocuments, 80);
        });

        if (window.lucide) {
            try { lucide.createIcons(); } catch (e) {}
        }
    })();
</script>
