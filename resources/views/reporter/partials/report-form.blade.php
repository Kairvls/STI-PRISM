{{-- ══════════════════════════════════════════════════════════════
     MAINTENANCE REPORT FORM  ·  PaAyo Dark Theme
     reporter/partials/report-form.blade.php
══════════════════════════════════════════════════════════════ --}}

<style>
    /* ── TOKENS ─────────────────────────────────────────────── */
    .rf-input {
        width: 100%;
        background: #ffffff;
        border: 1px solid #eceff4;
        border-radius: 16px;
        padding: 11px 14px;
        font-size: 14px;
        color: #1a1a2e;
        font-family: "Plus Jakarta Sans", "Inter", sans-serif;
        outline: none;
        transition:
            border-color 0.2s,
            background 0.2s,
            box-shadow 0.2s;
        appearance: none;
        -webkit-appearance: none;
    }
    .rf-input::placeholder {
        color: #9aa1b5;
    }
    .rf-input:focus {
        border-color: #0025cc;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(0, 37, 204, 0.1);
    }
    .rf-input option {
        background: #f7f7f8;
        color: #0f172a;
    }

    .details-textarea {
        background: #ffffff;
        border: 1px solid #eceff4;
    }

    .details-textarea:focus {
        border-color: #0025cc;
        box-shadow: 0 0 0 4px rgba(0, 37, 204, 0.1);
    }

    .rf-label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        color: #000000;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    /* ── SELECT ARROW ── */
    .rf-select-wrap {
        position: relative;
    }
    .rf-select-wrap::after {
        content: "";
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        width: 0;
        height: 0;
        border-left: 5px solid transparent;
        border-right: 5px solid transparent;
        border-top: 5px solid #8892a4;
        pointer-events: none;
    }
    .rf-select-wrap.rf-picker-ready::after {
        display: none;
    }

    .rf-select-wrap.rf-picker-ready .rf-native-select {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
        pointer-events: none;
    }

    .rf-picker-trigger {
        width: 100%;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        background: #ffffff;
        border: 1px solid rgba(41, 71, 240, 0.15);
        border-radius: 14px;
        padding: 0 14px;
        font-size: 14px;
        font-weight: 500;
        color: #0f172a;
        font-family: "Inter", sans-serif;
        cursor: pointer;
        text-align: left;
    }

    .rf-picker-trigger.is-placeholder {
        color: #4a5568;
    }

    .rf-picker-trigger:disabled {
        opacity: .55;
        cursor: not-allowed;
    }

    .rf-picker-trigger svg {
        width: 18px;
        height: 18px;
        color: #0025cc;
        flex-shrink: 0;
        transition: transform 0.2s ease;
    }

    .rf-picker-label {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        min-width: 0;
    }

    .rf-picker-overlay {
        position: fixed;
        inset: 0;
        z-index: 100050;
        display: none;
        align-items: flex-end;
        justify-content: center;
        background: rgba(11, 18, 32, 0.7);
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
        padding: 12px;
        padding-bottom: calc(12px + env(safe-area-inset-bottom));
        overscroll-behavior: contain;
        touch-action: manipulation;
    }

    .rf-picker-overlay.is-open {
        display: flex !important;
    }

    .rf-picker-overlay[hidden] {
        display: none !important;
    }

    .rf-picker-sheet {
        width: min(100%, 480px);
        max-height: min(78dvh, 640px);
        background: #fff;
        border-radius: 24px 24px 18px 18px;
        box-shadow: 0 28px 70px rgba(15, 23, 42, .22);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        font-family: "Plus Jakarta Sans", "Inter", sans-serif;
        isolation: isolate;
    }

    @media (min-width: 768px) {
        .rf-picker-overlay {
            align-items: center;
        }
        .rf-picker-sheet {
            border-radius: 24px;
        }
    }

    .rf-picker-head {
        position: relative;
        flex: 0 0 auto;
        padding: 18px 52px 12px 18px;
        border-bottom: 1px solid #e8ecf4;
        background: #fff;
    }

    .rf-picker-dismiss {
        position: absolute;
        top: 14px;
        right: 14px;
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e8ecf4;
        border-radius: 12px;
        background: #fff;
        color: #6b7280;
        cursor: pointer;
        transition: background .2s ease, border-color .2s ease, color .2s ease;
    }

    .rf-picker-dismiss:hover {
        background: #f3f6ff;
        border-color: #dbe3ff;
        color: #1a1a2e;
    }

    .rf-picker-kicker {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .16em;
        text-transform: uppercase;
        color: #0025cc;
        margin-bottom: 4px;
    }

    .rf-picker-title {
        font-size: 1.15rem;
        font-weight: 800;
        color: #1a1a2e;
    }

    .rf-picker-hint {
        margin-top: 4px;
        color: #94a3b8;
        font-size: 12px;
        font-weight: 500;
        line-height: 1.35;
    }

    .rf-picker-hint:empty,
    .rf-picker-hint[hidden] {
        display: none !important;
    }

    /* Body = search + chips + scrollable list (mirrors Flutter Column) */
    .rf-picker-body {
        flex: 1 1 auto;
        min-height: 0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: #fff;
    }

    .rf-picker-search {
        flex: 0 0 auto;
        margin: 14px 18px 0;
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f3f6ff;
        border: 1px solid #e8ecf4;
        border-radius: 18px;
        padding: 0 16px;
        min-height: 56px;
        height: 56px;
    }

    .rf-picker-search input {
        width: 100%;
        border: 0;
        background: transparent;
        outline: none;
        font-size: 16px;
        color: #1a1a2e;
    }

    .rf-picker-categories {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        flex: 0 0 48px;
        height: 48px;
        max-height: 48px;
        gap: 8px;
        margin: 0;
        padding: 10px 16px 4px;
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: none;
        -ms-overflow-style: none;
        background: #fff;
        box-sizing: border-box;
    }

    .rf-picker-categories::-webkit-scrollbar {
        display: none;
    }

    .rf-picker-categories[hidden] {
        display: none !important;
        flex-basis: 0 !important;
        height: 0 !important;
        max-height: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        overflow: hidden !important;
    }

    .rf-picker-chip {
        flex: 0 0 auto;
        height: 34px;
        padding: 0 12px;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        background: #fff;
        color: #475569;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
        cursor: pointer;
    }

    .rf-picker-chip.is-active {
        border-color: #0025cc;
        background: #eef2ff;
        color: #0025cc;
    }

    .rf-picker-list {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
        padding: 8px 10px;
        background: #fff;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .rf-picker-list::-webkit-scrollbar {
        width: 0;
        height: 0;
        display: none;
        background: transparent;
    }

    .rf-picker-item {
        width: 100%;
        display: flex;
        align-items: center;
        text-align: left;
        background: transparent;
        border: 0;
        border-radius: 14px;
        padding: 14px 12px;
        cursor: pointer;
        color: #1a1a2e;
        font-size: 14px;
        font-weight: 600;
        line-height: 1.35;
    }

    .rf-picker-item:hover,
    .rf-picker-item.is-active {
        background: #f3f6ff;
    }

    .rf-picker-item.is-active {
        color: #0025cc;
    }

    .rf-picker-check {
        display: none;
    }

    .rf-picker-empty {
        text-align: center;
        color: #6b7280;
        font-size: 13px;
        font-weight: 600;
        padding: 28px 12px;
    }

    .rf-picker-close {
        flex: 0 0 auto;
        margin: 10px 18px 22px;
        min-height: 56px;
        height: 56px;
        border: 0;
        border-radius: 999px;
        background: #0025cc;
        color: #fff;
        font-weight: 800;
        font-size: 17px;
        cursor: pointer;
    }

    /* ── Photo source sheet (PRISM mobile Take photo / Gallery) ── */
    .rf-photo-source-overlay {
        position: fixed;
        inset: 0;
        z-index: 100055;
        display: none;
        align-items: flex-end;
        justify-content: center;
        background: rgba(11, 18, 32, 0.55);
        padding: 0;
        overscroll-behavior: contain;
    }

    .rf-photo-source-overlay.is-open {
        display: flex !important;
    }

    .rf-photo-source-overlay[hidden] {
        display: none !important;
    }

    .rf-photo-source-sheet {
        width: 100%;
        max-width: 480px;
        background: #fff;
        border-radius: 12px 12px 0 0;
        padding: 12px 16px calc(20px + env(safe-area-inset-bottom));
        box-shadow: 0 -12px 40px rgba(15, 23, 42, 0.18);
        font-family: "Plus Jakarta Sans", "Inter", sans-serif;
    }

    .rf-photo-source-handle {
        width: 40px;
        height: 4px;
        margin: 0 auto 16px;
        border-radius: 999px;
        background: #e2e8f0;
    }

    .rf-photo-source-tile {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 12px;
        text-align: left;
        padding: 14px;
        border: 0;
        border-radius: 12px;
        background: #f3f4f6;
        color: #1a1a2e;
        cursor: pointer;
        box-sizing: border-box;
        transition: background 0.15s ease;
        -webkit-tap-highlight-color: transparent;
    }

    .rf-photo-source-tile + .rf-photo-source-tile {
        margin-top: 10px;
    }

    .rf-photo-source-tile:hover,
    .rf-photo-source-tile:active {
        background: #eef2ff;
    }

    .rf-photo-source-icon {
        width: 40px;
        height: 40px;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        background: #fff;
        color: #0025cc;
    }

    .rf-photo-source-copy {
        flex: 1 1 auto;
        min-width: 0;
    }

    .rf-photo-source-title {
        display: block;
        font-size: 14.5px;
        font-weight: 700;
        color: #1a1a2e;
        line-height: 1.3;
    }

    .rf-photo-source-sub {
        display: block;
        margin-top: 2px;
        font-size: 12.5px;
        font-weight: 500;
        color: #6b7280;
        line-height: 1.3;
    }

    .rf-photo-source-chevron {
        flex-shrink: 0;
        color: #cbd5e1;
    }

    /* Must NOT use display:none — browsers block file picker on hidden inputs */
    .rf-file-input-native {
        position: fixed !important;
        left: 0 !important;
        top: 0 !important;
        width: 1px !important;
        height: 1px !important;
        opacity: 0 !important;
        overflow: hidden !important;
        z-index: -1 !important;
        pointer-events: none !important;
    }

    /* ── Proof image preview (PRISM mobile) ── */
    .rf-proof-preview {
        position: relative;
        width: 100%;
        height: 140px;
        border-radius: 20px;
        overflow: hidden;
        border: 1.5px solid #0025cc;
        background: #f3f6ff;
    }

    .rf-proof-preview[hidden] {
        display: none !important;
    }

    .rf-proof-preview-tap {
        display: block;
        width: 100%;
        height: 100%;
        padding: 0;
        border: 0;
        background: transparent;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .rf-proof-preview-tap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .rf-proof-preview-hint {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        padding: 6px 8px;
        background: rgba(0, 0, 0, 0.45);
        color: #fff;
        font-size: 11.5px;
        font-weight: 600;
        text-align: center;
        line-height: 1.3;
        pointer-events: none;
    }

    .rf-proof-preview-remove {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 2;
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: rgba(239, 68, 68, 0.15);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #ef4444;
        cursor: pointer;
        padding: 0;
    }

    .upload-zone[hidden] {
        display: none !important;
    }

    /* Fullscreen proof viewer */
    .rf-proof-fs {
        position: fixed;
        inset: 0;
        z-index: 100080;
        display: none;
        background: #000;
        font-family: "Plus Jakarta Sans", "Inter", sans-serif;
    }

    .rf-proof-fs.is-open {
        display: block !important;
    }

    .rf-proof-fs[hidden] {
        display: none !important;
    }

    .rf-proof-fs-close {
        position: absolute;
        top: calc(8px + env(safe-area-inset-top));
        left: 8px;
        z-index: 2;
        width: 44px;
        height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        border: 1.5px solid rgba(255, 255, 255, 0.9);
        background: rgba(0, 0, 0, 0.55);
        color: #fff;
        cursor: pointer;
        box-shadow:
            0 2px 10px rgba(0, 0, 0, 0.45),
            0 0 6px rgba(255, 255, 255, 0.25);
    }

    .rf-proof-fs-stage {
        position: absolute;
        inset: 0;
        overflow: hidden;
        touch-action: none;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .rf-proof-fs-stage img {
        max-width: 100%;
        max-height: 100%;
        width: auto;
        height: auto;
        object-fit: contain;
        transform-origin: center center;
        user-select: none;
        -webkit-user-drag: none;
        pointer-events: none;
    }

    .rf-proof-fs-hint {
        position: absolute;
        left: 0;
        right: 0;
        bottom: calc(20px + env(safe-area-inset-bottom));
        z-index: 2;
        text-align: center;
        color: rgba(255, 255, 255, 0.7);
        font-size: 12.5px;
        font-weight: 500;
        pointer-events: none;
    }

    /* ── Employee ID icon (PRISM mobile face_retouching_natural) ── */
    .rf-employee-id-wrap {
        position: relative;
        width: 100%;
    }

    .rf-employee-id-icon {
        position: absolute;
        left: 13px;
        top: 50%;
        transform: translateY(-50%);
        width: 20px;
        height: 20px;
        color: #0025cc;
        pointer-events: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        z-index: 1;
    }

    .rf-employee-id-icon svg {
        width: 20px;
        height: 20px;
        display: block;
        fill: currentColor;
    }

    .rf-employee-id-input.rf-input {
        padding-left: 42px !important;
    }

    .rf-employee-id-input.rf-input::placeholder {
        color: #9aa1b5;
        font-weight: 500;
        letter-spacing: 0.4px;
        text-transform: uppercase;
    }

    #reportModal .rf-employee-id-input.rf-input.is-error {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
    }

    /* ── Scan equipment QR (mirrors PRISM mobile) ── */
    .rf-qr-card {
        display: flex;
        width: 100%;
        align-items: center;
        gap: 14px;
        padding: 14px 16px;
        border-radius: 20px;
        border: 1px solid #0025cc;
        background: #0025cc;
        color: #fff;
        text-align: left;
        transition: background-color 0.15s ease, transform 0.1s ease;
    }

    .rf-qr-card:hover:not(.is-locked) {
        background: #001fad;
    }

    .rf-qr-card:active {
        transform: scale(0.99);
    }

    .rf-qr-card.is-locked {
        border-color: #e2e8f0;
        background: #f1f5f9;
        color: #64748b;
    }

    .rf-qr-card-icon {
        display: inline-flex;
        height: 44px;
        width: 44px;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.14);
        color: #fde047;
    }

    .rf-qr-card.is-locked .rf-qr-card-icon {
        background: #fff;
        color: #94a3b8;
    }

    .rf-qr-card-text {
        display: flex;
        min-width: 0;
        flex: 1;
        flex-direction: column;
        gap: 2px;
    }

    .rf-qr-card-title {
        font-family: "Outfit", sans-serif;
        font-size: 15px;
        font-weight: 700;
    }

    .rf-qr-card-sub {
        font-size: 12.5px;
        line-height: 1.35;
        color: rgba(255, 255, 255, 0.82);
    }

    .rf-qr-card.is-locked .rf-qr-card-sub {
        color: #94a3b8;
    }

    .rf-qr-card-arrow {
        flex-shrink: 0;
        opacity: 0.85;
    }

    .rf-qr-notice {
        margin-top: 8px;
        border-radius: 12px;
        background: #ecfdf5;
        padding: 8px 12px;
        font-size: 12.5px;
        font-weight: 600;
        color: #047857;
    }

    .rf-qr-divider {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 14px 0;
        font-size: 12px;
        font-weight: 600;
        color: #94a3b8;
    }

    .rf-qr-divider::before,
    .rf-qr-divider::after {
        content: "";
        height: 1px;
        flex: 1;
        background: #e2e8f0;
    }

    .rf-qr-overlay {
        position: fixed;
        inset: 0;
        z-index: 100090;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        background: rgba(15, 23, 42, 0.6);
    }

    .rf-qr-overlay[hidden] {
        display: none;
    }

    .rf-qr-sheet {
        width: 100%;
        max-width: 460px;
        max-height: 100dvh;
        overflow-y: auto;
        border-radius: 24px 24px 0 0;
        background: #fff;
        padding: 18px 18px calc(18px + env(safe-area-inset-bottom));
    }

    @media (min-width: 640px) {
        .rf-qr-overlay {
            align-items: center;
        }

        .rf-qr-sheet {
            border-radius: 24px;
        }
    }

    .rf-qr-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
    }

    .rf-qr-title {
        font-family: "Outfit", sans-serif;
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
    }

    .rf-qr-sub {
        margin-top: 2px;
        font-size: 13px;
        color: #64748b;
    }

    .rf-qr-close {
        display: inline-flex;
        height: 38px;
        width: 38px;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        color: #475569;
    }

    #rfCheckOverlay [hidden] {
        display: none !important;
    }

    .rf-check-kicker {
        margin-bottom: 2px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #0025cc;
    }

    .rf-check-progress {
        display: flex;
        gap: 6px;
        margin: 2px 0 16px;
    }

    .rf-check-progress span {
        flex: 1;
        height: 4px;
        border-radius: 999px;
        background: #e2e8f0;
        transition: background-color 0.2s ease;
    }

    .rf-check-progress span.is-done {
        background: #0025cc;
    }

    .rf-check-progress span.is-current {
        background: rgba(0, 37, 204, 0.35);
    }

    .rf-check-count {
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
    }

    .rf-check-question {
        margin-top: 6px;
        font-family: "Outfit", sans-serif;
        font-size: 20px;
        font-weight: 700;
        line-height: 1.3;
        color: #0f172a;
    }

    .rf-check-hint {
        margin-top: 12px;
        display: flex;
        align-items: flex-start;
        gap: 8px;
        border: 1px solid #eef1f6;
        border-radius: 14px;
        background: #f8fafc;
        padding: 10px 12px;
        font-size: 13px;
        line-height: 1.45;
        color: #475569;
    }

    .rf-check-hint svg {
        flex-shrink: 0;
        margin-top: 1px;
        color: #d97706;
    }

    .rf-check-actions {
        margin-top: 18px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .rf-check-btn {
        display: inline-flex;
        min-height: 48px;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-radius: 14px;
        padding: 10px 14px;
        font-size: 14px;
        font-weight: 700;
        transition: transform 0.1s ease, background-color 0.15s ease, border-color 0.15s ease;
    }

    .rf-check-btn:active {
        transform: scale(0.98);
    }

    .rf-check-btn.is-primary {
        background: #0025cc;
        color: #fff;
    }

    .rf-check-btn.is-primary:hover {
        background: #001db3;
    }

    .rf-check-btn.is-fixed {
        border: 1px solid #bbf7d0;
        background: #f0fdf4;
        color: #15803d;
    }

    .rf-check-btn.is-fixed:hover {
        background: #dcfce7;
    }

    .rf-check-btn.is-plain {
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #0f172a;
    }

    .rf-check-btn.is-plain:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
    }

    .rf-check-btn.is-danger {
        background: #dc2626;
        color: #fff;
    }

    .rf-check-btn.is-danger:hover {
        background: #b91c1c;
    }

    .rf-check-foot {
        margin-top: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        font-size: 12px;
        color: #94a3b8;
    }

    .rf-check-link {
        flex-shrink: 0;
        font-size: 13px;
        font-weight: 700;
        color: #0025cc;
    }

    .rf-check-result {
        padding: 6px 4px 2px;
        text-align: center;
    }

    .rf-check-result-icon {
        margin: 0 auto 12px;
        display: flex;
        height: 56px;
        width: 56px;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
    }

    .rf-check-result.is-fixed .rf-check-result-icon {
        background: #dcfce7;
        color: #16a34a;
    }

    .rf-check-result.is-danger .rf-check-result-icon {
        background: #fee2e2;
        color: #dc2626;
    }

    .rf-check-result-title {
        font-family: "Outfit", sans-serif;
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
    }

    .rf-check-result-text {
        margin-top: 6px;
        font-size: 14px;
        line-height: 1.5;
        color: #475569;
    }

    .rf-check-result .rf-check-btn {
        margin-top: 18px;
        width: 100%;
    }

    .rf-qr-stage {
        position: relative;
        overflow: hidden;
        border-radius: 18px;
        background: #0f172a;
        aspect-ratio: 1 / 1;
    }

    .rf-qr-stage #rfQrReader,
    .rf-qr-stage #rfQrReader video {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover;
    }

    .rf-qr-stage-empty {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 24px;
        text-align: center;
        font-size: 13px;
        color: #cbd5e1;
    }

    .rf-qr-stage-empty[hidden] {
        display: none;
    }

    .rf-qr-status {
        margin-top: 12px;
        font-size: 13px;
        color: #475569;
    }

    .rf-qr-error {
        margin-top: 8px;
        border-radius: 12px;
        background: #fef2f2;
        padding: 10px 12px;
        font-size: 13px;
        font-weight: 600;
        color: #b91c1c;
    }

    .rf-qr-photo-btn {
        display: flex;
        width: 100%;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-top: 14px;
        padding: 12px 14px;
        border-radius: 14px;
        border: 1px solid #cbd5e1;
        background: #fff;
        font-size: 14px;
        font-weight: 600;
        color: #0f172a;
        cursor: pointer;
    }

    .rf-qr-photo-btn:hover {
        background: #f8fafc;
    }

    .rf-picker-trigger.is-open {
        border-color: #0025cc;
        box-shadow: 0 0 0 4px rgba(0, 37, 204, 0.1);
    }

    .rf-picker-trigger.is-open svg {
        transform: rotate(180deg);
        transition: transform 0.2s ease;
    }

    .rf-picker-trigger svg {
        transition: transform 0.2s ease;
    }

    .rf-dropdown-menu {
        position: fixed;
        z-index: 100060;
        display: none;
        box-sizing: border-box;
        background: #fff;
        border: 1px solid #e8ecf4;
        border-radius: 16px;
        box-shadow: 0 18px 48px rgba(15, 23, 42, 0.16);
        overflow: hidden;
        padding: 6px;
        font-family: "Plus Jakarta Sans", "Inter", sans-serif;
    }

    .rf-dropdown-search {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 2px 2px 6px;
        padding: 0 10px;
        height: 40px;
        border-radius: 12px;
        background: #f8fafc;
        border: 1px solid #e8ecf4;
    }

    .rf-dropdown-search input {
        flex: 1;
        min-width: 0;
        border: 0;
        background: transparent;
        outline: none;
        font-family: inherit;
        font-size: 13px;
        font-weight: 600;
        color: #1a1a2e;
    }

    .rf-dropdown-search input::placeholder {
        color: #94a3b8;
        font-weight: 500;
    }

    .rf-dropdown-search svg {
        color: #0025cc;
        flex-shrink: 0;
    }

    .rf-dropdown-menu.is-open {
        display: block !important;
    }

    .rf-dropdown-menu[hidden] {
        display: none !important;
    }

    .rf-dropdown-list {
        max-height: calc(44px * 5);
        overflow-y: auto;
        overflow-x: hidden;
        scroll-behavior: smooth;
        overscroll-behavior: contain;
        scrollbar-width: thin;
        scrollbar-color: #c7d2fe #f8fafc;
    }

    .rf-dropdown-list::-webkit-scrollbar {
        width: 8px;
    }

    .rf-dropdown-list::-webkit-scrollbar-track {
        background: transparent;
    }

    .rf-dropdown-list::-webkit-scrollbar-thumb {
        background: #c7d2fe;
        border-radius: 999px;
        border: 2px solid #fff;
    }

    .rf-dropdown-item {
        width: 100%;
        min-height: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        text-align: left;
        background: transparent;
        border: 0;
        border-radius: 12px;
        padding: 0 12px;
        cursor: pointer;
        color: #1a1a2e;
        font-size: 13.5px;
        font-weight: 600;
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        transition: background 0.15s ease, color 0.15s ease;
    }

    .rf-dropdown-item.rf-dropdown-item--equipment {
        height: auto;
        min-height: 58px;
        align-items: flex-start;
        flex-direction: column;
        justify-content: center;
        gap: 4px;
        padding: 11px 14px;
        white-space: normal;
        overflow: visible;
    }

    .rf-equipment-option-main {
        display: block;
        width: 100%;
        font-size: 13.5px;
        font-weight: 600;
        line-height: 1.4;
        color: inherit;
    }

    .rf-equipment-option-sub {
        display: block;
        width: 100%;
        font-size: 11.5px;
        font-weight: 600;
        line-height: 1.35;
        color: #b45309;
    }

    .rf-dropdown-item.is-active .rf-equipment-option-sub,
    .rf-picker-item.is-active .rf-equipment-option-sub {
        color: #fde68a;
    }

    .rf-picker-item.rf-picker-item--equipment {
        height: auto;
        min-height: 0;
        align-items: flex-start;
        flex-direction: column;
        justify-content: center;
        gap: 4px;
        padding: 14px 12px;
        white-space: normal;
        margin: 0;
    }

    .rf-picker-item.rf-picker-item--equipment:has(.rf-equipment-option-sub) {
        background: #fffbeb;
    }

    .rf-picker-item.rf-picker-item--equipment.is-active:has(.rf-equipment-option-sub),
    .rf-picker-item.rf-picker-item--equipment.is-active {
        background: #f3f6ff;
    }

    .rf-dropdown-item:hover {
        background: #f3f6ff;
        color: #0025cc;
    }

    .rf-dropdown-item.is-active {
        background: #0025cc;
        color: #fff;
    }

    .rf-dropdown-item.is-active:hover {
        background: #001ca3;
        color: #fff;
    }

    .rf-dropdown-empty {
        text-align: center;
        color: #6b7280;
        font-size: 13px;
        padding: 16px 12px;
    }

    .rf-option-tip {
        position: fixed;
        z-index: 100070;
        max-width: min(360px, calc(100vw - 24px));
        padding: 8px 10px;
        border-radius: 12px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        background: #ffffff;
        color: #334155;
        font-size: 12px;
        font-weight: 500;
        line-height: 1.45;
        white-space: normal;
        word-break: break-word;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.14);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transform: translateY(4px);
        transition:
            opacity 0.12s ease,
            transform 0.12s ease,
            visibility 0.12s ease;
    }

    .rf-option-tip.is-visible {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    /* ── REPORTER INFO BOX ── */
    .reporter-box {
        background: rgba(41, 71, 240, 0.05);
        border: 1px solid rgba(41, 71, 240, 0.15);
        border-radius: 16px;
        padding: 16px 18px;
    }

    /* ── PRIORITY RADIO CARD ── */
    .priority-card {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px 16px;
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.07);
        background: rgba(255, 255, 255, 0.03);
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .priority-card:hover {
        border-color: rgba(255, 255, 255, 0.15);
        background: rgba(255, 255, 255, 0.06);
    }
    .priority-card.p-non-urgent {
        border-color: rgba(52, 211, 153, 0.35);
        background: rgba(52, 211, 153, 0.07);
    }
    .priority-card.p-urgent {
        border-color: rgba(239, 68, 68, 0.35);
        background: rgba(239, 68, 68, 0.07);
    }
    .priority-title {
        font-family: "Outfit", sans-serif;
        font-weight: 700;
        font-size: 0.9rem;
        color: #f0f2f8;
    }
    .priority-card.p-non-urgent .priority-title {
        color: #34d399;
    }
    .priority-card.p-urgent .priority-title {
        color: #ef4444;
    }
    .priority-desc {
        font-size: 0.73rem;
        color: #b6b6b6;
        margin-top: 3px;
        line-height: 1.4;
    }

    .rf-eq-item {
        position: relative;
        cursor: default;
    }

    .rf-eq-tip {
        position: absolute;
        left: 12px;
        bottom: calc(100% + 8px);
        z-index: 40;
        max-width: min(320px, 80vw);
        padding: 8px 10px;
        border-radius: 12px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        background: #ffffff;
        color: #334155;
        font-size: 12px;
        font-weight: 500;
        line-height: 1.45;
        white-space: normal;
        word-break: break-word;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.14);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transform: translateY(4px);
        transition:
            opacity 0.15s ease,
            transform 0.15s ease,
            visibility 0.15s ease;
    }

    .rf-eq-item:hover .rf-eq-tip,
    .rf-eq-item:focus-within .rf-eq-tip {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    @keyframes rfEqItemIn {
        from {
            background: #eef2ff;
            box-shadow: 0 0 0 3px rgba(41, 71, 240, 0.35);
        }
        to {
            box-shadow: 0 0 0 0 rgba(41, 71, 240, 0);
        }
    }

    .rf-eq-item.is-new {
        animation: rfEqItemIn 1.2s ease-out;
    }

    .rf-eq-details {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        overflow-wrap: anywhere;
    }

    /* ── EQUIPMENT TO REPORT (right column, desktop only) ── */
    #reportModal .rf-aside-eq {
        display: none;
    }

    .rf-aside-eq-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .rf-aside-eq-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        height: 24px;
        padding: 0 8px;
        border-radius: 999px;
        background: #eef2ff;
        color: #2947f0;
        font-size: 12px;
        font-weight: 700;
    }

    .rf-aside-eq-empty {
        margin: 0;
        padding: 14px;
        border: 1.5px dashed #cbd5e1;
        border-radius: 14px;
        background: #f8fafc;
        color: #64748b;
        font-size: 0.78rem;
        line-height: 1.45;
    }

    .rf-aside-eq-empty strong {
        color: #2947f0;
    }

    @media (min-width: 1024px) {
        #reportModal .rf-aside-eq {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        #reportModal .rf-aside-eq #selectedEquipmentList {
            margin-bottom: 0;
        }

        #reportModal .rf-aside-eq .rf-eq-item {
            flex-wrap: wrap;
            align-items: flex-start;
            row-gap: 8px;
            background: #fff;
        }

        #reportModal .rf-aside-eq .rf-eq-item > .min-w-0 {
            flex: 1 1 100%;
        }

        #reportModal .rf-aside-eq .rf-eq-item .truncate {
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
            overflow-wrap: anywhere;
        }

        #reportModal .rf-aside-eq .rf-eq-photo {
            margin-right: auto;
        }

        #reportModal .rf-aside-eq .rf-eq-tip {
            display: none;
        }
    }

    .rf-submit-btn {
        background: linear-gradient(135deg, #f0b429, #e8920a);
        color: #080c18;
        font-family: "Outfit", sans-serif;
        font-size: 0.95rem;
        border: 0;
        cursor: pointer;
    }
    .rf-submit-btn:hover {
        background: linear-gradient(135deg, #e8920a, #c67a05);
    }

    .rf-cancel-btn {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.09);
        color: #a7aab9;
        font-size: 0.9rem;
        cursor: pointer;
    }
    .rf-cancel-btn:hover {
        background: rgba(255, 255, 255, 0.09);
        color: #f0f2f8;
    }

    .rf-close-desktop {
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #a7aab9;
        cursor: pointer;
    }
    .rf-close-desktop:hover {
        color: #f0f2f8;
        background: rgba(255, 255, 255, 0.1);
    }

    /* ── ISSUE TAG ── */
    .issue-btn {
        background: #fef3c7;
        border: 1.5px solid rgba(240, 180, 41, 0.45);
        color: #b45309;
        padding: 8px 16px;
        border-radius: 999px;
        font-size: 12.5px;
        font-weight: 600;
        white-space: nowrap;
        flex-shrink: 0;
        transition: all 0.2s ease;
        font-family: "Inter", sans-serif;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .issue-btn:hover {
        background: #fde68a;
        border: 1.5px solid #f0b429;
        color: #92400e;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(240, 180, 41, 0.2);
    }

    .issue-btn.active {
        background: #f0b429;
        border: 1.5px solid #e8920a;
        color: #080c18;
        box-shadow: 0 4px 12px rgba(240, 180, 41, 0.3);
    }

    .issue-clear {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 16px;
        height: 16px;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.14);
        color: inherit;
        font-size: 11px;
        font-weight: 800;
        line-height: 1;
        cursor: pointer;
        flex-shrink: 0;
    }

    .issue-action-btn {
        width: 36px;
        height: 36px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    /* LEFT & RIGHT BUTTONS */
    .issue-action-btn.yellow {
        background: #fef3c7;
        border: 1.5px solid rgba(240, 180, 41, 0.45);
        color: #b45309;
    }

    .issue-action-btn.yellow:hover {
        background: #f0b429;
        border-color: #f0b429;
        color: #080c18;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(240, 180, 41, 0.25);
    }

    /* CLEAR BUTTON */
    .issue-action-btn.red {
        background: #fef2f2;
        border: 1.5px solid rgba(239, 68, 68, 0.18);
        color: #ef4444;
    }

    .issue-action-btn.red:hover {
        background: #ef4444;
        border-color: #ef4444;
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
    }

    /* SELECTED ISSUE CONTAINER */
    .selected-issue-container {
        background: #fff8e6;
        border: 1px solid #f0b429;
        border-radius: 14px;
        padding: 12px;
    }

    /* SELECTED ISSUE BADGE */
    .selected-issue-pill {
        display: inline-flex;
        align-items: center;

        background: #f0b429;
        color: #080c18;

        padding: 8px 14px;

        border-radius: 999px;

        font-size: 13px;
        font-weight: 600;
    }

    /* ── PER-EQUIPMENT PROOF PHOTOS ── */
    .rf-eq-photo {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
    }
    .rf-eq-photo-add {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        border: 1px dashed #94a3b8;
        border-radius: 8px;
        background: #fff;
        padding: 4px 8px;
        font-size: 11px;
        font-weight: 700;
        color: #2947f0;
        white-space: nowrap;
        transition: border-color 0.15s ease, background 0.15s ease;
    }
    .rf-eq-photo-add:hover {
        border-color: #2947f0;
        background: #eef2ff;
    }
    .rf-eq-photo-thumb {
        width: 40px;
        height: 40px;
        padding: 0;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
        cursor: zoom-in;
    }
    .rf-eq-photo-thumb img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .rf-eq-photo-remove {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 999px;
        background: #f1f5f9;
        color: #64748b;
        font-size: 13px;
        font-weight: 700;
        line-height: 1;
    }
    .rf-eq-photo-remove:hover {
        background: #fee2e2;
        color: #dc2626;
    }

    #issueCarousel {
        margin-bottom: 16px;
    }

    /* ── SCROLL HIDE ── */
    #issueCarousel::-webkit-scrollbar {
        display: none;
    }
    #issueCarousel {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    /* =====================================================
    LEFT PANEL VISIBLE SCROLLBAR
    ===================================================== */

    .report-form-scroll {
        overflow-y: auto;

        /* Firefox */
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f8fafc;
    }

    @media (max-width: 1023px) {
        #reportModal .report-form-scroll {
            overflow-y: visible !important;
            overflow-x: hidden !important;
        }
    }

    /* Chrome, Edge, Safari */
    .report-form-scroll::-webkit-scrollbar {
        width: 8px;
    }

    .report-form-scroll::-webkit-scrollbar-track {
        background: #f8fafc;
    }

    .report-form-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 999px;
        border: 2px solid #f8fafc;
    }

    .report-form-scroll::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .issue-placeholder {
        width: 100%;
        min-height: 48px;
        height: auto;
        box-sizing: border-box;
        border: 1.5px dashed rgba(98, 98, 100, 0.61);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6b6c6e;
        font-size: 12px;
        font-weight: 600;
        line-height: 1.4;
        text-align: center;
        padding: 10px 14px;
        white-space: normal;
    }

    /* ── Premium landing match (web + mobile) ── */
    #reportModal {
        background: rgba(11, 18, 32, 0.7) !important;
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
        overflow: hidden !important;
        align-items: center;
        padding: 24px !important;
    }

    #reportModal > div {
        max-width: 1080px;
        max-height: 100%;
        min-height: 0;
        width: 100%;
        display: flex;
        flex-direction: column;
    }

    /* Desktop optional full-screen expand: full width, height follows content, vertically centered */
    #reportModal.is-expanded {
        align-items: center !important;
        justify-content: center !important;
        padding: 0 !important;
    }

    #reportModal.is-expanded > div,
    #reportModal.is-expanded .report-modal-wrap {
        max-width: 100% !important;
        width: 100% !important;
        height: auto !important;
        max-height: 100dvh !important;
        align-self: center;
        margin-top: auto;
        margin-bottom: auto;
    }

    #reportModal.is-expanded .report-form-frame,
    #reportModal.is-expanded #reportForm,
    #reportModal.is-expanded .report-form-shell {
        max-width: 100% !important;
        width: 100% !important;
        height: auto !important;
        max-height: 100dvh !important;
    }

    #reportModal.is-expanded .report-form-shell {
        border-radius: 0 !important;
        overflow: hidden !important;
    }

    #reportModal.is-expanded .report-form-grid {
        min-height: 0;
        height: auto;
        max-height: 100dvh;
    }

    #reportModal.is-expanded .report-form-scroll,
    #reportModal.is-expanded .report-form-aside {
        max-height: 100dvh;
        overflow-y: auto;
    }

    .rf-expand-btn {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        background: #fff;
        border: 1px solid #e8ecf4;
        color: #6b7280;
        cursor: pointer;
    }

    .rf-expand-btn:hover {
        color: #1a1a2e;
        background: #f3f6ff;
        border-color: #dbe3ff;
    }

    @media (max-width: 767px) {
        .rf-expand-btn {
            display: none !important;
        }
    }

    #reportForm,
    #reportModal .report-form-frame {
        max-height: 100%;
        min-height: 0;
        width: 100%;
        display: flex;
        flex-direction: column;
    }

    #reportModal .report-form-shell {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        max-height: 100%;
        min-height: 0;
        display: flex;
        flex-direction: column;
        background:
            radial-gradient(ellipse 70% 55% at 0% 0%, rgba(199, 216, 255, .42), transparent 58%),
            #ffffff !important;
        border: 1px solid rgba(255, 255, 255, .95) !important;
        box-shadow:
            0 32px 80px rgba(15, 23, 42, 0.14),
            0 2px 0 rgba(255, 255, 255, .75) inset !important;
        font-family: "Plus Jakarta Sans", sans-serif !important;
        border-radius: 32px !important;
    }

    #reportModal .report-form-grid {
        position: relative;
        z-index: 1;
        min-height: 0;
        flex: 1 1 auto;
    }

    #reportModal #issueCarousel::-webkit-scrollbar {
        width: 0 !important;
        height: 0 !important;
        display: none !important;
    }

    #reportModal .report-form-scroll {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
        scrollbar-gutter: auto;
    }

    #reportModal .report-form-aside-body {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
        scrollbar-gutter: auto;
    }

    #reportModal .report-form-scroll::-webkit-scrollbar,
    #reportModal .report-form-aside-body::-webkit-scrollbar {
        width: 8px;
    }

    #reportModal .report-form-scroll::-webkit-scrollbar-track,
    #reportModal .report-form-aside-body::-webkit-scrollbar-track {
        background: transparent;
    }

    #reportModal .report-form-scroll::-webkit-scrollbar-thumb,
    #reportModal .report-form-aside-body::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 999px;
        border: 2px solid transparent;
        background-clip: padding-box;
    }

    #reportModal .report-form-scroll {
        background: transparent !important;
        border-right: 0 !important;
        padding: 32px 28px !important;
        min-width: 0;
    }

    .rf-kicker {
        display: inline-flex;
        align-items: center;
        background: #fff200;
        color: #1a1a2e;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
        border-radius: 999px;
        padding: 4px 9px;
        margin-bottom: 8px;
        box-shadow: 0 8px 18px rgba(255, 242, 0, .28);
    }

    .rf-header-icon {
        width: 44px !important;
        height: 44px !important;
        border-radius: 14px !important;
        background: #0025cc !important;
        border: 0 !important;
        color: #fff;
        box-shadow: 0 8px 18px rgba(0, 37, 204, 0.22);
    }

    .rf-header-icon i,
    .rf-header-icon svg {
        color: #fff !important;
        stroke: #fff;
        width: 20px;
        height: 20px;
    }

    #reportModal .report-form-scroll h2 {
        font-family: "Plus Jakarta Sans", sans-serif !important;
        font-size: 1.7rem !important;
        color: #1a1a2e !important;
        letter-spacing: -0.04em;
        font-weight: 800 !important;
    }

    #reportModal .report-form-scroll h2 + p {
        color: #6b7280 !important;
        font-size: 0.9rem !important;
    }

    .rf-label {
        color: #94a3b8;
        font-weight: 700;
        letter-spacing: .12em;
    }

    .rf-input,
    .rf-picker-trigger {
        height: 52px !important;
        background: #ffffff;
        border: 1px solid #eceff4;
        border-radius: 16px;
        font-family: "Plus Jakarta Sans", sans-serif;
        color: #1a1a2e;
        box-shadow: none;
    }

    .rf-input::placeholder,
    .rf-picker-trigger.is-placeholder {
        color: #9aa1b5;
    }

    .rf-input:focus,
    .details-textarea:focus,
    .rf-picker-trigger:focus,
    .rf-picker-trigger.is-open {
        border-color: #0025cc;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(0, 37, 204, 0.1);
    }

    #problemDescription {
        min-height: 108px !important;
        height: auto !important;
        background: #ffffff;
        border: 1px solid #eceff4;
        border-radius: 16px;
        box-sizing: border-box;
    }

    .rf-select-wrap::after {
        border-top-color: #0025cc;
    }

    .reporter-box {
        background: #f3f6ff;
        border: 0;
    }

    .issue-placeholder {
        border: 1px solid #eceff4;
        color: #94a3b8;
        background: #ffffff;
        font-weight: 500;
        min-height: 52px;
        flex-shrink: 0;
    }

    #reportModal #issueCarousel {
        flex-wrap: nowrap !important;
        overflow-x: auto !important;
        overflow-y: hidden !important;
        cursor: grab !important;
        gap: 8px;
        width: 100%;
        min-width: 0;
        max-width: 100%;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .issue-btn {
        flex-shrink: 0;
        white-space: nowrap;
        background: #f3f6ff;
        border: 1px solid #e0e7ff;
        color: #0025cc;
        box-shadow: none;
        transform: none !important;
    }

    .issue-btn:hover {
        background: #eef2ff;
        border-color: #c7d2fe;
        color: #0025cc;
        transform: none !important;
        box-shadow: none;
    }

    .issue-btn.active {
        background: #fff200;
        border-color: transparent;
        color: #1a1a2e;
        box-shadow: 0 8px 18px rgba(255, 242, 0, .28);
    }

    .issue-action-btn.yellow,
    .issue-action-btn.red {
        background: #f4f5f7;
        border: 1px solid #eceff1;
        color: #9aa3b2;
        box-shadow: none;
        transform: none;
    }

    .issue-action-btn.yellow:hover,
    .issue-action-btn.red:hover {
        background: #eceef2;
        border-color: #e6e8ec;
        color: #6b7280;
        box-shadow: none;
        transform: none;
    }

    #clearIssueBtn {
        display: none !important;
    }

    .report-form-aside {
        background: linear-gradient(180deg, rgba(243, 246, 255, .9) 0%, #f8f9fd 100%) !important;
        border-top: 1px solid #e8ecf4 !important;
        padding: 32px 28px !important;
    }

    .report-form-aside .rf-label {
        color: #94a3b8 !important;
    }

    .priority-card {
        background: #fff;
        border: 1px solid #e8ecf4;
        border-radius: 18px;
        box-shadow: none;
        padding: 14px 16px;
    }

    .priority-card:hover {
        border-color: rgba(0, 37, 204, 0.16);
        background: #fff;
        box-shadow: 0 10px 24px rgba(0, 37, 204, 0.06);
    }

    .priority-card.p-non-urgent {
        border-color: #0025cc;
        background: #f3f6ff;
        box-shadow: 0 0 0 4px rgba(0, 37, 204, 0.08);
    }

    .priority-card.p-urgent {
        border-color: #ef4444;
        background: #fef2f2;
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.12);
    }

    .priority-title {
        font-family: "Plus Jakarta Sans", sans-serif;
        color: #1a1a2e;
    }

    .priority-card.p-non-urgent .priority-title {
        color: #0025cc;
    }

    .priority-card.p-urgent .priority-title {
        color: #dc2626;
    }

    .priority-desc {
        color: #6b7280;
    }

    .priority-card input {
        accent-color: #0025cc;
    }

    .priority-card.p-urgent input {
        accent-color: #ef4444;
    }

    .upload-zone {
        background: #fff;
        border: 1.5px dashed #c8d4f5;
        border-radius: 20px;
        padding: 22px 16px;
    }

    .upload-zone:hover {
        border-color: #0025cc;
        background: #f3f6ff;
    }

    .upload-zone.uploaded {
        border-style: solid;
        border-color: #0025cc;
        background: #f3f6ff;
    }

    .report-form-aside .upload-label {
        color: #334155 !important;
    }

    .report-form-aside .upload-hint {
        color: #9aa1b5 !important;
    }

    .rf-close-desktop,
    .rf-close-mobile,
    .rf-help-btn,
    .rf-expand-btn {
        width: 36px !important;
        height: 36px !important;
        border-radius: 12px !important;
        background: #fff !important;
        border: 1px solid #e8ecf4 !important;
        color: #6b7280 !important;
    }

    .rf-close-desktop:hover,
    .rf-close-mobile:hover,
    .rf-help-btn:hover,
    .rf-expand-btn:hover {
        color: #1a1a2e !important;
        background: #f3f6ff !important;
        border-color: #dbe3ff !important;
    }

    .rf-submit-btn {
        min-height: 52px;
        background: #0025cc !important;
        color: #fff !important;
        font-family: "Plus Jakarta Sans", sans-serif !important;
        font-weight: 600;
        border-radius: 999px !important;
        box-shadow: 0 12px 28px rgba(0, 37, 204, 0.18);
    }

    .rf-submit-btn:hover {
        background: #001ca3 !important;
        box-shadow: 0 16px 32px rgba(0, 37, 204, 0.28);
    }

    .rf-cancel-btn {
        background: transparent !important;
        border: 0 !important;
        color: #6b7280 !important;
        border-radius: 999px !important;
        font-weight: 600;
        min-height: 0;
        padding-top: 8px !important;
        padding-bottom: 4px !important;
    }

    .rf-cancel-btn:hover {
        background: transparent !important;
        color: #1a1a2e !important;
    }

    @media (min-width: 768px) {
        #reportModal {
            padding: 24px 28px !important;
        }
    }

    @media (min-width: 1024px) {
        .report-form-aside {
            border-top: 0 !important;
            border-left: 1px solid rgba(232, 236, 244, .9) !important;
        }

        #reportModal .report-form-shell,
        #reportModal .report-form-grid {
            max-height: calc(100dvh - 48px);
        }

        #reportModal .report-form-grid {
            height: auto !important;
            align-items: stretch;
            grid-template-rows: minmax(0, 1fr);
        }

        #reportModal .report-form-scroll {
            overflow-y: auto !important;
            overflow-x: hidden !important;
            min-height: 0 !important;
            height: auto !important;
            max-height: calc(100dvh - 48px) !important;
            display: flex !important;
            flex-direction: column;
            align-self: stretch;
            overscroll-behavior: contain;
            padding: 32px 28px 0 !important;
        }

        #reportModal .report-form-scroll > :not(.rf-details-block) {
            flex-shrink: 0;
        }

        #reportModal .rf-details-block {
            flex: 1 0 auto;
            min-height: 140px;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
        }

        #reportModal .rf-add-block {
            padding-bottom: 32px;
        }

        #reportModal .rf-details-field {
            flex: 1 1 auto;
            min-height: 140px;
            position: relative;
            display: flex;
        }

        #reportModal #problemDescription {
            flex: 1 1 auto;
            width: 100%;
            min-height: 140px !important;
            height: auto !important;
            resize: none !important;
        }

        #reportModal .report-form-aside {
            overflow: hidden !important;
            min-height: 0 !important;
            height: auto !important;
            max-height: calc(100dvh - 48px) !important;
            align-self: stretch;
            padding: 32px 0 20px !important;
        }

        #reportModal .report-form-aside-head,
        #reportModal .report-form-aside-actions {
            padding-left: 28px;
            padding-right: 28px;
        }

        #reportModal .report-form-aside-body {
            overflow-y: auto;
            overflow-x: hidden;
            min-height: 0;
            flex: 1 1 auto;
            overscroll-behavior: contain;
            padding: 0 28px 8px;
            scrollbar-gutter: auto;
        }

        #reportModal .report-form-aside-actions {
            flex-shrink: 0;
        }
    }

    @media (max-width: 1023px) {
        #reportModal .report-form-shell {
            overflow-y: auto !important;
            overflow-x: hidden !important;
            max-height: calc(100dvh - 16px);
            height: auto !important;
            min-height: 0;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }

        #reportModal .report-form-grid {
            display: flex !important;
            flex-direction: column !important;
            flex: none !important;
            min-height: auto !important;
            height: auto !important;
        }

        #reportModal .report-form-scroll,
        #reportModal .report-form-aside,
        #reportModal .report-form-aside-body {
            overflow: visible !important;
            height: auto !important;
            max-height: none !important;
            min-height: auto !important;
            flex: none !important;
        }

        #reportModal .report-form-scroll {
            border-right: 0 !important;
        }

        #reportModal .report-form-aside {
            border-top: 1px solid #e8ecf4 !important;
        }

        #reportModal .report-form-aside-actions {
            position: static !important;
            margin: 0 !important;
            background: transparent !important;
            padding-top: 0 !important;
        }
    }

    #reportModal .rf-add-equip-btn {
        width: 100%;
        min-height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border: 1.5px solid #c7d2fe;
        border-radius: 16px;
        background: #eef2ff;
        color: #0025cc;
        font-size: 14px;
        font-weight: 700;
        white-space: nowrap;
        cursor: pointer;
        transition: background-color 0.15s ease, border-color 0.15s ease, opacity 0.15s ease, transform 0.1s ease;
    }

    #reportModal .rf-add-equip-btn:hover {
        background: #e0e7ff;
        border-color: #0025cc;
    }

    #reportModal .rf-add-equip-btn:active {
        transform: scale(0.99);
    }

    /* Still clickable so the reporter is told what's missing. */
    #reportModal .rf-add-equip-btn.is-waiting {
        opacity: 0.45;
    }

    #reportModal .rf-add-equip-btn.hidden {
        display: none !important;
    }

    #reportModal .rf-add-hint {
        margin: 8px 0 0;
        text-align: center;
        font-size: 12px;
        color: #94a3b8;
    }

    @media (max-width: 767px) {
        #reportModal {
            align-items: stretch;
            overflow: hidden !important;
            padding: 0 !important;
        }

        #reportModal > div,
        #reportModal .report-modal-wrap,
        #reportForm,
        #reportModal .report-form-frame {
            max-height: 100dvh;
            height: auto;
            min-height: 0;
        }

        #reportModal .report-form-frame {
            padding-left: 0 !important;
            padding-right: 0 !important;
            max-width: 100%;
        }

        #reportModal .report-form-shell {
            max-height: 100dvh;
            height: auto;
            min-height: 0;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            scrollbar-width: thin;
            border-radius: 0 !important;
            padding-bottom: env(safe-area-inset-bottom);
        }

        #reportModal .report-form-scroll {
            overflow: visible !important;
            max-height: none;
            height: auto !important;
            min-height: auto !important;
            padding: 20px 20px 12px !important;
        }

        #reportModal .rf-header {
            margin-bottom: 1.25rem !important;
            gap: 12px;
        }

        #reportModal .report-form-scroll h2 {
            font-size: 1.25rem !important;
            line-height: 1.15 !important;
        }

        #reportModal .report-form-scroll h2 + p {
            font-size: 0.82rem !important;
        }

        .report-form-aside {
            padding: 16px 20px calc(20px + env(safe-area-inset-bottom)) !important;
            overflow: visible !important;
            height: auto !important;
            min-height: auto !important;
            gap: 1rem !important;
        }

        #reportModal .report-form-aside-body {
            gap: 1rem !important;
        }

        #reportModal .report-form-aside-actions {
            position: static !important;
            margin: 0 !important;
            background: transparent !important;
            padding: 8px 0 calc(8px + env(safe-area-inset-bottom)) !important;
        }

        #reportModal .rf-input,
        #reportModal .rf-picker-trigger {
            height: 48px !important;
            font-size: 16px !important;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }

        #reportModal #problemDescription {
            min-height: 120px !important;
            font-size: 16px !important;
            width: 100%;
        }

        #reportModal .priority-card {
            padding: 12px 14px;
        }

        #reportModal .upload-zone {
            min-height: 120px;
        }

        /* Mobile: PRISM_MOBILE Report an issue layout (structure only) */
        #reportModal .rf-loc-equip-card {
            gap: 0 !important;
            margin-bottom: 10px !important;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #fff;
            overflow: hidden;
        }

        #reportModal .rf-loc-block {
            padding: 12px 14px;
            border-bottom: 1px solid #e5e7eb;
        }

        #reportModal .rf-equip-block {
            padding: 12px 14px;
        }

        #reportModal .rf-equip-field-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        #reportModal .rf-equip-field-control {
            min-width: 0;
            flex: 1 1 auto;
        }

        #reportModal .rf-loc-equip-row {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        #reportModal .rf-loc-equip-icon {
            display: inline-flex !important;
            height: 36px;
            width: 36px;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #f3f4f6;
            color: #64748b;
        }

        #reportModal .rf-loc-equip-hint {
            display: block;
            margin-bottom: 2px;
            font-size: 10px;
            font-weight: 600;
            color: #94a3b8;
            line-height: 1.2;
        }

        /* No second border inside Location / Equipment — outer card only (PRISM_MOBILE) */
        #reportModal .rf-loc-equip-control {
            min-width: 0;
            flex: 1 1 auto;
        }

        #reportModal .rf-loc-equip-control .rf-select-wrap,
        #reportModal .rf-loc-equip-control .rf-input {
            width: 100%;
        }

        #reportModal .rf-loc-equip-card .rf-input,
        #reportModal .rf-loc-equip-card .rf-picker-trigger,
        #reportModal .rf-loc-equip-card .details-textarea {
            height: auto !important;
            min-height: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            color: #9ca3af !important;
        }

        #reportModal .rf-loc-equip-card .rf-picker-trigger:not(.is-placeholder) {
            color: #0f172a !important;
        }

        #reportModal .rf-loc-equip-card .rf-input:focus,
        #reportModal .rf-loc-equip-card .details-textarea:focus,
        #reportModal .rf-loc-equip-card .rf-picker-trigger:focus,
        #reportModal .rf-loc-equip-card .rf-picker-trigger.is-open {
            border: 0 !important;
            box-shadow: none !important;
            background: transparent !important;
            outline: none !important;
        }

        #reportModal .rf-loc-equip-card .rf-select-wrap::after {
            display: none !important;
        }

        #reportModal .rf-loc-equip-card .rf-picker-trigger {
            display: flex !important;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            width: 100%;
            text-align: left;
        }

        #reportModal .rf-loc-equip-card .rf-picker-label {
            flex: 1 1 auto;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        #reportModal .rf-loc-equip-card .rf-picker-trigger > i,
        #reportModal .rf-loc-equip-card .rf-picker-trigger > svg {
            flex-shrink: 0;
            width: 32px !important;
            height: 32px !important;
            padding: 7px;
            border-radius: 10px;
            background: #f3f4f6;
            color: #64748b !important;
            stroke: #64748b !important;
            box-sizing: border-box;
        }

        #reportModal .rf-loc-equip-card #equipmentManualInput {
            color: #0f172a !important;
            font-weight: 600 !important;
        }

        #reportModal .rf-equip-instruction {
            margin: 0 !important;
            padding: 12px 14px;
            border-radius: 12px;
            background: #f3f4f6;
            color: #6b7280 !important;
            font-size: 12px !important;
            line-height: 1.45 !important;
        }

        #reportModal .rf-header {
            flex-wrap: wrap;
        }

        #reportModal .rf-header .rf-kicker {
            order: -1;
            width: auto;
            max-width: max-content;
            flex: 0 0 auto;
            align-self: flex-start;
            margin-bottom: 10px;
        }

        /* Section titles — match Location & equipment (PRISM_MOBILE) */
        #reportModal .rf-loc-equip-mobile-title,
        #reportModal .rf-mobile-section-title,
        #reportModal .rf-section-title {
            display: block !important;
            margin-bottom: 8px !important;
            color: #0f172a !important;
            font-size: 13px !important;
            font-weight: 700 !important;
            text-transform: none !important;
            letter-spacing: 0 !important;
            line-height: 1.25 !important;
        }

        #reportModal .rf-issues-block {
            margin-top: 16px !important;
        }

        #reportModal .rf-issues-head {
            margin-bottom: 8px !important;
        }

        #reportModal .rf-issues-panel {
            display: flex !important;
            min-height: 112px;
            padding: 14px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
            box-sizing: border-box;
        }

        #reportModal .rf-issues-panel:has(.issue-btn) {
            display: flex !important;
            flex-wrap: nowrap !important;
            overflow-x: auto !important;
            align-items: stretch;
            gap: 8px;
            padding: 10px;
            min-height: 0;
        }

        #reportModal .issue-placeholder {
            width: 100%;
            min-height: 84px;
            margin: 0;
            padding: 8px 10px;
            border: 0 !important;
            border-radius: 0;
            background: transparent !important;
            display: flex !important;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #9ca3af !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            line-height: 1.4;
            text-align: center;
            white-space: normal;
        }

        #reportModal .issue-placeholder-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
        }

        #reportModal .issue-placeholder-icon svg {
            width: 18px;
            height: 18px;
        }

        #reportModal .rf-details-block {
            margin-top: 20px !important;
        }

        #reportModal .rf-details-head {
            display: block !important;
            margin-bottom: 8px !important;
        }

        #reportModal .rf-details-hint {
            margin: 4px 0 0;
            color: #9ca3af;
            font-size: 12px;
            font-weight: 500;
            line-height: 1.35;
        }

        #reportModal #problemDescription {
            min-height: 132px !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 12px !important;
            background: #fff !important;
            padding: 14px 40px 14px 14px !important;
            font-size: 15px !important;
            line-height: 1.5 !important;
            box-shadow: none !important;
        }

        #reportModal #problemDescription::placeholder {
            color: #9ca3af;
        }

        #reportModal #equipmentDropdownContainer,
        #reportModal #equipmentManualContainer {
            width: 100%;
            min-width: 0;
        }

        #reportModal .rf-details-block,
        #reportModal .rf-details-field {
            width: 100%;
            min-width: 0;
        }

        #reportModal .issue-btn {
            font-size: 13px;
            padding: 10px 14px;
        }
    }

    @media (max-width: 480px) {
        #reportModal .report-form-scroll {
            padding: 18px 16px 10px !important;
        }

        .report-form-aside {
            padding: 14px 16px calc(18px + env(safe-area-inset-bottom)) !important;
        }

        #reportModal .report-form-aside-actions {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        #reportModal .rf-header-icon {
            width: 40px !important;
            height: 40px !important;
        }

        #reportModal .rf-kicker {
            font-size: 9px;
            margin-bottom: 6px;
        }

        #reportModal .rf-label {
            font-size: 10px;
        }
    }

    @media (min-width: 768px) {
        #reportModal .rf-loc-equip-mobile-title {
            display: none !important;
        }

        #reportModal .rf-mobile-section-title,
        #reportModal .rf-section-title {
            color: #94a3b8 !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.08em !important;
            line-height: inherit !important;
        }

        #reportModal .rf-loc-equip {
            display: grid;
            grid-template-columns: 1fr 1fr;
            column-gap: 1.25rem;
            row-gap: 0.65rem;
        }

        #reportModal .rf-loc-equip-card {
            display: contents;
            border: 0;
            background: transparent;
            overflow: visible;
            margin: 0 !important;
        }

        #reportModal .rf-qr-wrap {
            grid-column: 1 / -1;
            grid-row: 1;
        }

        #reportModal .rf-loc-block {
            grid-column: 1;
            grid-row: 2;
            padding: 0;
            border: 0;
        }

        #reportModal .rf-equip-block {
            grid-column: 2;
            grid-row: 2;
            padding: 0;
        }

        #reportModal .rf-equip-field-row {
            display: flex;
            align-items: stretch;
            gap: 0.5rem;
        }

        #reportModal .rf-loc-equip-row {
            display: block;
        }

        #reportModal .rf-loc-equip-icon,
        #reportModal .rf-loc-equip-hint {
            display: none !important;
        }

        #reportModal #selectedEquipmentList,
        #reportModal #selectedEquipmentInputs,
        #reportModal .rf-equip-instruction,
        #reportModal #equipmentError {
            grid-column: 1 / -1;
        }

        #reportModal .rf-equip-instruction {
            padding: 0;
            background: transparent;
            font-size: 11px !important;
            color: #94a3b8 !important;
        }

        #reportModal .rf-details-head {
            display: flex !important;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        #reportModal .rf-details-hint {
            margin: 0;
            color: #94a3b8;
            font-size: 12px;
        }

        #reportModal .rf-issues-panel {
            min-height: 0;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
        }

        #reportModal .issue-placeholder {
            flex-direction: row;
            gap: 8px;
            min-height: 52px;
            border: 1px solid #eceff4 !important;
            border-radius: 16px;
            background: #fff !important;
            padding: 10px 14px;
        }
    }
</style>


<style>
    #issueCarousel {
        cursor: grab;

        user-select: none;

        -webkit-user-select: none;

        overflow-x: auto;

        scroll-behavior: auto;
    }

    #issueCarousel:active {
        cursor: grabbing;
    }

    .issue-btn {
        pointer-events: auto;
    }

    .issue-btn {
        transition:
            background 0.25s ease,
            border-color 0.25s ease,
            transform 0.15s ease;
    }

    .issue-btn:hover {
        transform: translateY(-2px);
    }

    .issue-btn:active {
        transform: scale(0.96);
    }

    /* =====================================================
    SUBMITTING REPORT POPUP
    ===================================================== */

    .modern-submitting-popup {
        border: 1px solid #e5e7eb !important;

        border-radius: 16px !important;

        box-shadow:
            0 24px 60px rgba(15, 23, 42, 0.12) !important;
    }


    /* =====================================================
    TITLE
    ===================================================== */

    .modern-submitting-title {
        padding: 0 !important;

        margin: 0 !important;

        font-family: 'Poppins', sans-serif !important;

        font-size: 1.25rem !important;

        font-weight: 700 !important;

        letter-spacing: -0.025em !important;

        color: #111827 !important;
    }


    /* =====================================================
    MESSAGE
    ===================================================== */

    .modern-submitting-content {
        padding: 0 !important;

        margin: 0.65rem 0 0 !important;

        font-family: 'Inter', sans-serif !important;

        font-size: 0.9rem !important;

        line-height: 1.6 !important;

        color: #6b7280 !important;
    }

    .swal-submitting-message {
        max-width: 320px;

        margin: 0 auto;
    }


    /* =====================================================
    LOADING SPINNER
    ===================================================== */

    .modern-submitting-loader {
        width: 34px !important;

        height: 34px !important;

        margin: 1.25rem auto 0 !important;

        border-width: 3px !important;

        border-color:
            #e2e8f0
            #e2e8f0
            #111827
            #111827 !important;
    }


    /* =====================================================
    MOBILE
    ===================================================== */

    @media (max-width: 480px) {

        .modern-submitting-popup {
            width: calc(100% - 32px) !important;

            padding: 1.5rem !important;
        }

    }

    /* =====================================================
    PAAYO SWAL (success / error)
    ===================================================== */

    .swal2-container .swal2-popup.paayo-swal {
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        width: 400px !important;
        max-width: calc(100vw - 32px) !important;
        border-radius: 28px !important;
        border: 1px solid #e8ecf4 !important;
        background:
            radial-gradient(ellipse 80% 50% at 50% 0%, rgba(199, 216, 255, .45), transparent 62%),
            #ffffff !important;
        box-shadow: 0 32px 80px rgba(15, 23, 42, .18) !important;
        padding: 2.1rem 1.75rem 1.5rem !important;
    }

    .swal2-container .swal2-popup.paayo-swal .swal2-title,
    .paayo-swal-heading {
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        font-size: 1.4rem !important;
        font-weight: 800 !important;
        letter-spacing: -.035em !important;
        color: #1a1a2e !important;
        line-height: 1.2 !important;
        margin: 0 0 8px !important;
        padding: 0 !important;
    }

    .swal2-container .swal2-popup.paayo-swal .swal2-html-container,
    .paayo-swal-text {
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        color: #6b7280 !important;
        font-size: .95rem !important;
        line-height: 1.55 !important;
        margin: 0 auto !important;
        max-width: 300px;
    }

    .swal2-container .swal2-popup.paayo-swal .swal2-actions {
        margin: 22px 0 0 !important;
        width: 100% !important;
    }

    .swal2-container .swal2-popup.paayo-swal .swal2-confirm,
    .paayo-swal-btn {
        background: #0025cc !important;
        color: #fff !important;
        border: 0 !important;
        border-radius: 999px !important;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        font-weight: 600 !important;
        font-size: .95rem !important;
        padding: 12px 32px !important;
        min-width: 140px !important;
        box-shadow: 0 12px 28px rgba(0, 37, 204, .24) !important;
    }

    .swal2-container .swal2-popup.paayo-swal .swal2-confirm:hover,
    .paayo-swal-btn:hover {
        background: #001ca3 !important;
    }

    .swal2-container .swal2-popup.paayo-swal .swal2-cancel,
    .paayo-swal-cancel {
        background: #f1f5f9 !important;
        color: #334155 !important;
        border: 0 !important;
        border-radius: 999px !important;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        font-weight: 600 !important;
        font-size: .95rem !important;
        padding: 12px 28px !important;
        min-width: 120px !important;
        box-shadow: none !important;
    }

    .swal2-container .swal2-popup.paayo-swal .swal2-cancel:hover,
    .paayo-swal-cancel:hover {
        background: #e2e8f0 !important;
    }

    .swal2-container .swal2-popup.paayo-swal .swal2-icon.swal2-warning,
    .swal2-container .swal2-popup.paayo-swal .paayo-icon-warning {
        background: #fffbeb !important;
        color: #d97706 !important;
        box-shadow: 0 12px 24px rgba(217, 119, 6, .12);
    }

    .swal2-container .swal2-popup.paayo-swal .swal2-icon {
        border: 0 !important;
        width: 58px !important;
        height: 58px !important;
        margin: 0 auto 1.05rem !important;
        border-radius: 18px !important;
        box-shadow: 0 12px 24px rgba(0, 37, 204, .18);
    }

    .swal2-container .swal2-popup.paayo-swal .swal2-icon.swal2-success,
    .swal2-container .swal2-popup.paayo-swal .paayo-icon-success {
        background: #0025cc !important;
        color: #fff !important;
    }

    .swal2-container .swal2-popup.paayo-swal .swal2-icon.swal2-error,
    .swal2-container .swal2-popup.paayo-swal .paayo-icon-error {
        background: #fef2f2 !important;
        color: #dc2626 !important;
        box-shadow: 0 12px 24px rgba(220, 38, 38, .12);
    }

    .swal2-container .swal2-popup.paayo-swal .swal2-success-ring,
    .swal2-container .swal2-popup.paayo-swal .swal2-success-circular-line-left,
    .swal2-container .swal2-popup.paayo-swal .swal2-success-circular-line-right,
    .swal2-container .swal2-popup.paayo-swal .swal2-success-fix {
        display: none !important;
    }

    .swal2-container .swal2-popup.paayo-swal .swal2-icon svg {
        width: 26px !important;
        height: 26px !important;
    }

    .swal2-container .swal2-popup.paayo-swal .modern-success-progress {
        background: #0025cc !important;
        height: 3px !important;
    }

    .paayo-swal-in {
        animation: paayoSwalIn .28s ease;
    }

    @keyframes paayoSwalIn {
        from { opacity: 0; transform: translateY(18px) scale(.97); }
        to { opacity: 1; transform: none; }
    }

    /* ── Guided tour ── */
    .rf-tour-sep {
        margin: 0 4px;
        color: #cbd5e1;
    }

    .rf-tour-link {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 0;
        border: 0;
        background: none;
        vertical-align: bottom;
        white-space: nowrap;
        color: #0025cc;
        font-size: inherit;
        font-weight: 700;
        cursor: pointer;
    }

    .rf-tour-link:hover {
        text-decoration: underline;
    }

    @media (max-width: 767px) {
        .rf-tour-sep {
            display: none;
        }

        .rf-tour-link {
            display: flex;
            margin-top: 2px;
        }
    }

    .driver-popover.rf-tour-popover {
        max-width: 320px;
        padding: 18px 18px 14px;
        border-radius: 16px;
        font-family: "Plus Jakarta Sans", sans-serif;
        color: #0f172a;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.28);
    }

    .rf-tour-popover .driver-popover-title {
        font-family: "Outfit", sans-serif;
        font-size: 16px;
        font-weight: 800;
        line-height: 1.3;
        color: #0f172a;
    }

    .rf-tour-popover .driver-popover-description {
        font-size: 13.5px;
        line-height: 1.55;
        color: #475569;
    }

    .rf-tour-popover .rf-tour-tip {
        display: block;
        margin-top: 8px;
        font-size: 11.5px;
        color: #94a3b8;
    }

    .rf-tour-popover .driver-popover-footer {
        gap: 8px;
        margin-top: 14px;
    }

    .rf-tour-popover .driver-popover-progress-text {
        font-size: 12px;
        font-weight: 600;
        color: #94a3b8;
    }

    .rf-tour-popover .driver-popover-navigation-btns {
        align-items: center;
    }

    .rf-tour-popover .driver-popover-footer-btn {
        padding: 7px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
        color: #334155;
        font-size: 12.5px;
        font-weight: 700;
        text-shadow: none;
    }

    .rf-tour-popover .driver-popover-footer-btn.driver-popover-next-btn {
        border-color: #0025cc;
        background: #0025cc;
        color: #fff;
    }

    .rf-tour-popover .driver-popover-footer-btn.driver-popover-next-btn:hover {
        background: #001fad;
    }

    .rf-tour-popover .driver-popover-prev-btn.driver-popover-btn-disabled {
        display: none !important;
    }

    .rf-tour-popover .rf-tour-skip {
        margin-right: auto;
        padding: 0 6px 0 0;
        border: 0;
        background: none;
        color: #94a3b8;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
    }

    .rf-tour-popover .rf-tour-skip:hover {
        color: #475569;
    }

    /* ── Phase 1: employee ID + PIN gate ── */
    #reportForm[hidden],
    #reporterGate[hidden],
    #reporterGate [hidden] {
        display: none !important;
    }

    .rf-gate {
        width: 100%;
        max-width: 440px;
        margin: 0 auto;
    }

    .rf-gate-card {
        position: relative;
        background:
            radial-gradient(ellipse 80% 60% at 0% 0%, rgba(199, 216, 255, .42), transparent 60%),
            #ffffff;
        border: 1px solid rgba(255, 255, 255, .95);
        border-radius: 28px;
        box-shadow: 0 32px 80px rgba(15, 23, 42, 0.14);
        padding: 30px 28px 26px;
        font-family: "Plus Jakarta Sans", "Inter", sans-serif;
    }

    .rf-gate-close {
        position: absolute;
        top: 18px;
        right: 18px;
        width: 36px;
        height: 36px;
        border-radius: 12px;
        background: #fff;
        border: 1px solid #e8ecf4;
        color: #6b7280;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .rf-gate-close:hover {
        color: #1a1a2e;
        background: #f3f6ff;
        border-color: #dbe3ff;
    }

    .rf-gate-title {
        font-size: 1.5rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: #1a1a2e;
        line-height: 1.15;
    }

    .rf-gate-sub {
        margin-top: 6px;
        color: #6b7280;
        font-size: 0.86rem;
        line-height: 1.5;
    }

    .rf-gate-pin.rf-input {
        text-align: center;
        font-size: 1.35rem;
        font-weight: 800;
        letter-spacing: 0.55em;
        padding-left: calc(48px + 0.55em) !important;
        padding-right: 48px !important;
    }

    .rf-gate-pin.rf-input::placeholder {
        letter-spacing: 0.55em;
        color: #c5cad6;
    }

    .rf-gate-pin.rf-input.is-error {
        border-color: #ef4444;
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.12);
    }

    .rf-gate-pin::-ms-reveal,
    .rf-gate-pin::-ms-clear {
        display: none;
    }

    .rf-gate-pin-wrap {
        position: relative;
    }

    .rf-gate-pin-toggle {
        position: absolute;
        top: 50%;
        right: 6px;
        transform: translateY(-50%);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 10px;
        color: #8a93a6;
        transition: color 0.15s ease, background-color 0.15s ease;
    }

    .rf-gate-pin-toggle:hover {
        color: #374151;
        background: #f3f5f9;
    }

    .rf-gate-pin-toggle:focus-visible {
        outline: 2px solid #0025cc;
        outline-offset: 1px;
    }

    .rf-gate-msg {
        display: none;
        margin-top: 12px;
        border-radius: 14px;
        padding: 10px 12px;
        font-size: 0.82rem;
        font-weight: 600;
        line-height: 1.45;
    }

    .rf-gate-msg.is-error {
        display: block;
        background: #fef2f2;
        color: #b91c1c;
    }

    .rf-gate-msg.is-info {
        display: block;
        background: #f3f6ff;
        color: #1e3a8a;
    }

    .rf-gate-msg.is-warn {
        display: block;
        background: #fffbeb;
        color: #92400e;
    }

    .rf-gate-link {
        border: 0;
        background: none;
        padding: 0;
        color: #0025cc;
        font-size: 0.82rem;
        font-weight: 700;
        cursor: pointer;
    }

    .rf-gate-link:hover {
        text-decoration: underline;
    }

    .rf-gate-note {
        margin-top: 18px;
        padding-top: 14px;
        border-top: 1px solid #eef1f6;
        color: #9aa1b5;
        font-size: 0.76rem;
        line-height: 1.5;
    }

    .rf-gate .rf-submit-btn:disabled {
        opacity: 0.6;
        cursor: wait;
    }

    .rf-reporter-card {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        padding: 10px 6px 10px 12px;
        border-radius: 12px;
        background: #f3f6ff;
    }

    .rf-reporter-card-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        width: 38px;
        height: 38px;
        border-radius: 999px;
        background: #ecfdf5;
        color: #059669;
    }

    .rf-reporter-card-name {
        margin: 0;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        font-size: 15px;
        font-weight: 700;
        line-height: 1.3;
        color: #1a1a2e;
    }

    .rf-reporter-card-sub {
        margin: 2px 0 0;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        font-size: 12px;
        font-weight: 500;
        color: #6b7280;
    }

    @media (max-width: 767px) {
        .rf-gate {
            max-width: 100%;
        }

        .rf-gate-card {
            min-height: 100dvh;
            border-radius: 0;
            padding: 28px 22px calc(26px + env(safe-area-inset-bottom));
        }
    }
</style>

@php
    $pinReporter = \App\Support\ReporterPins::ready()
        ? \App\Support\ReporterPins::sessionReporter(request(), false)
        : null;
@endphp

{{-- Always open on phase 1; session resume to phase 2 is intentionally disabled --}}
<div id="reporterGate" class="rf-gate">
    <div class="rf-gate-card">
        <button type="button" class="rf-gate-close" onclick="closeReportModal()" aria-label="Close">
            <i data-lucide="x" class="h-4 w-4"></i>
        </button>

        <div class="rf-header-icon mb-4 flex items-center justify-center">
            <i data-lucide="shield-check" class="h-5 w-5"></i>
        </div>
        {{-- VERIFY --}}
        <form id="reporterGateForm" novalidate>
            <h2 class="rf-gate-title">Verify it's you</h2>

            <div class="mt-6">
                <label class="rf-label" for="gateEmployeeId">Employee ID</label>
                <div class="rf-employee-id-wrap">
                    <span class="rf-employee-id-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" focusable="false">
                            <circle cx="9" cy="13" r="1.25"/>
                            <path d="m20.77 8.58-.92 2.01c.09.46.15.93.15 1.41 0 4.41-3.59 8-8 8s-8-3.59-8-8c0-.05.01-.1 0-.14 2.6-.98 4.69-2.99 5.74-5.55A10 10 0 0 0 17.5 10c.45 0 .89-.04 1.33-.1l-.6-1.32-.88-1.93-1.93-.88-2.79-1.27 2.79-1.27.71-.32A9.86 9.86 0 0 0 12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10c0-1.47-.33-2.87-.9-4.13l-.33.71z"/>
                            <circle cx="15" cy="13" r="1.25"/>
                            <path d="M20.6 5.6 19.5 8l-1.1-2.4L16 4.5l2.4-1.1L19.5 1l1.1 2.4L23 4.5z"/>
                        </svg>
                    </span>
                    <input
                        type="text"
                        id="gateEmployeeId"
                        placeholder="OMC00127F"
                        class="rf-input rf-employee-id-input"
                        autocomplete="username"
                        autocapitalize="characters"
                        spellcheck="false"
                        maxlength="20"
                        required
                    />
                </div>
            </div>

            <div class="mt-4">
                <div class="flex items-center justify-between">
                    <label class="rf-label" for="gatePin">PIN</label>
                    <button type="button" class="rf-gate-link mb-2" id="gateForgotLink">Forgot PIN?</button>
                </div>
                <div class="rf-gate-pin-wrap">
                    <input
                        type="password"
                        id="gatePin"
                        inputmode="numeric"
                        autocomplete="current-password"
                        maxlength="6"
                        placeholder="••••••"
                        class="rf-input rf-gate-pin"
                        required
                    />
                    <button type="button" id="gatePinToggle" class="rf-gate-pin-toggle" aria-label="Show PIN" title="Show PIN" aria-pressed="false">
                        <span data-pin-icon="show"><i data-lucide="eye" class="h-5 w-5"></i></span>
                        <span data-pin-icon="hide" hidden><i data-lucide="eye-off" class="h-5 w-5"></i></span>
                    </button>
                </div>
            </div>

            <p id="gateMessage" class="rf-gate-msg" role="alert"></p>

            <button type="submit" id="gateSubmitBtn" class="rf-submit-btn mt-6 inline-flex w-full items-center justify-center gap-2">
                <span id="gateSubmitText">Continue</span>
                <i data-lucide="arrow-right" class="h-4 w-4"></i>
            </button>

            <p class="rf-gate-note">No PIN yet? Check your email or ask the system administrator.</p>
        </form>

        {{-- FORGOT PIN --}}
        <form id="reporterForgotForm" novalidate hidden>
            <h2 class="rf-gate-title">Forgot your PIN?</h2>
            <p class="rf-gate-sub">We'll email you a link to set a new PIN.</p>

            <div class="mt-6">
                <label class="rf-label" for="forgotEmployeeId">Employee ID</label>
                <input
                    type="text"
                    id="forgotEmployeeId"
                    placeholder="OMC00127F"
                    class="rf-input"
                    autocapitalize="characters"
                    spellcheck="false"
                    maxlength="20"
                    required
                />
            </div>

            <p id="forgotMessage" class="rf-gate-msg" role="status"></p>

            <button type="submit" id="forgotSubmitBtn" class="rf-submit-btn mt-6 inline-flex w-full items-center justify-center gap-2">
                <span id="forgotSubmitText">Send reset link</span>
            </button>

            <div class="mt-4 text-center">
                <button type="button" class="rf-gate-link" id="forgotBackLink">Back to verify</button>
            </div>

            <p class="rf-gate-note">No email on file? Ask the system administrator to reset your PIN.</p>
        </form>
    </div>
</div>

<form
    method="POST"
    action="/store-report"
    enctype="multipart/form-data"
    id="reportForm"
    hidden
    data-verified-employee-id=""
>
    @csrf

    <div class="report-form-frame mx-auto w-full max-w-6xl px-0 sm:px-2">
        <div
            class="report-form-shell overflow-hidden rounded-3xl"
            style="
                background: #0d1120;
                border: 1px solid rgba(255, 255, 255, 0.09);
                box-shadow: 0 40px 100px rgba(0, 0, 0, 0.7);
                font-family: &quot;Inter&quot;, sans-serif;
            "
        >
            <div
                class="
                    report-form-grid
                    grid
                    grid-cols-1
                    lg:grid-cols-12
                "
            >
                {{-- ══════════════════ LEFT PANEL ══════════════════ --}}
                <div
                    class="
                        report-form-scroll
                        max-lg:min-h-0
                        max-lg:overflow-visible
                        lg:overflow-y-auto
                        bg-white
                        p-6
                        sm:p-7

                        lg:col-span-8
                        lg:h-full
                        lg:min-h-0
                        lg:p-9
                    "
                    style="border-right: 1px solid rgba(255, 255, 255, 0.07);"
                >
                    {{-- HEADER --}}
                    <div class="rf-header mb-7 flex items-center gap-4">
                        <div
                            class="rf-header-icon w-13 h-13 flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl"
                            style="
                                background: rgba(54, 41, 240, 0.12);
                                border: 1px solid rgba(41, 61, 240, 0.2);
                            "
                        >
                            <i
                                data-lucide="clipboard-pen"
                                class="h-6 w-6"
                                style="color: #2947f0"
                            ></i>
                        </div>

                        <div class="flex-1">
                            <div class="rf-kicker">Campus report</div>
                            <h2
                                style="
                                    font-family: &quot;Outfit&quot;, sans-serif;
                                    font-weight: 800;
                                    font-size: 1.5rem;
                                    color: #0f172a;
                                    line-height: 1.1;
                                "
                            >
                                Maintenance Report
                            </h2>
                            <p
                                style="
                                    color: #656568;
                                    font-size: 0.8rem;
                                    margin-top: 3px;
                                "
                            >
                                <button type="button" class="rf-tour-link" data-report-tour-start>
                                    <i data-lucide="circle-help" class="h-3.5 w-3.5"></i>
                                    How to report
                                </button>
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rf-help-btn flex h-9 w-9 items-center justify-center rounded-xl lg:hidden"
                            data-report-tour-start
                            title="How to report"
                            aria-label="How to report"
                        >
                            <i data-lucide="circle-help" class="h-4 w-4"></i>
                        </button>

                        {{-- CLOSE (mobile) --}}
                        <button
                            type="button"
                            onclick="requestCloseReportModal()"
                            class="rf-close-mobile flex h-9 w-9 items-center justify-center rounded-xl lg:hidden"
                            aria-label="Close"
                        >
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>

                    {{-- VERIFIED REPORTER: one row, same as the mobile app --}}
                    @php
                        $cardEmployeeId = $pinReporter->reporter_employee_id ?? '';
                        $cardName = trim((string) ($pinReporter->reporter_full_name ?? ''));
                    @endphp
                    <div class="mb-5">
                        <input
                            type="hidden"
                            id="employeeIdInput"
                            name="report_reporter_employee_id"
                            value="{{ $cardEmployeeId }}"
                        />

                        <div id="rfReporterCard" class="rf-reporter-card" data-employee-id="{{ $cardEmployeeId }}" data-name="{{ $cardName }}">
                            <span class="rf-reporter-card-icon" aria-hidden="true">
                                <i data-lucide="shield-check" class="h-5 w-5"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p id="rfReporterCardName" class="rf-reporter-card-name">{{ $cardName !== '' ? $cardName : $cardEmployeeId }}</p>
                                <p id="rfReporterCardSub" class="rf-reporter-card-sub">{{ $cardName !== '' ? $cardEmployeeId.' · ' : '' }}Verified with PIN</p>
                            </div>
                        </div>

                        {{-- EMPLOYEE ERROR --}}
                        <p
                            id="employeeError"
                            style="
                                color: #ef4444;
                                font-size: 0.82rem;
                                font-weight: 600;
                                margin-top: 8px;
                                display: none;
                            "
                        >Employee ID not recognized.</p>
                    </div>

                    {{-- REPORTER INFO BOX + PROPERTY-ASSIGNED EQUIPMENT --}}
                    <div id="reporterInfoBox" class="hidden">
                        <div id="assignedEquipmentBox" class="mb-5 hidden rounded-xl border border-[#2947f0]/15 bg-[#f3f6ff] px-3.5 py-3">
                            <p class="mb-1 text-[11px] font-semibold uppercase tracking-[0.08em] text-[#2947f0]">
                                Your assigned equipment
                            </p>
                            <p class="mb-2.5 text-[11px] leading-snug text-slate-500">
                                Detected from your employee ID’s property assignment (including computer sets). Tap one to report it — these units stay out of the shared location list.
                            </p>
                            <div id="assignedEquipmentList" class="flex flex-wrap gap-2"></div>
                        </div>
                    </div>

                    {{-- ===================================================== --}}
                    {{-- INACTIVE REPORTER WARNING --}}
                    {{-- SHOWS WHEN REPORTER CANNOT SUBMIT REPORTS --}}
                    {{-- ===================================================== --}}

                    <div
                        id="inactiveReporterBox"
                        class="mb-5 hidden rounded-2xl border border-red-200 bg-red-50 p-4"
                    >
                        <div class="flex items-start gap-3">

                            <div
                                class="flex h-10 w-10 shrink-0 items-center
                                    justify-center rounded-xl
                                    bg-red-100 text-red-600"
                            >
                                <i
                                    data-lucide="user-x"
                                    class="h-5 w-5"
                                ></i>
                            </div>

                            <div class="min-w-0">

                                <p class="text-sm font-bold text-red-800">
                                    Reporting Access Disabled
                                </p>

                                <p class="mt-1 text-sm leading-5 text-red-600">
                                    This reporter account is inactive and cannot
                                    submit maintenance reports.
                                </p>

                                <p class="mt-2 text-xs text-red-500">
                                    Please contact the system administrator if you
                                    believe this is a mistake.
                                </p>

                            </div>

                        </div>
                    </div>

                    <div
                        id="pendingReporterBox"
                        class="mb-5 hidden rounded-2xl border border-amber-200 bg-amber-50 p-4"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center
                                    justify-center rounded-xl
                                    bg-amber-100 text-amber-700"
                            >
                                <i data-lucide="hourglass" class="h-5 w-5"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-amber-900">
                                    Waiting for approval
                                </p>
                                <p class="mt-1 text-sm leading-5 text-amber-800">
                                    This employee ID has an application waiting for maintenance confirmation.
                                </p>
                                <p class="mt-2 text-xs text-amber-700">
                                    You can submit reports after they confirm you are faculty or staff.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- LOCATION + EQUIPMENT (mobile layout mirrors PRISM_MOBILE Report an issue) --}}
                    <div class="rf-loc-equip mb-5">
                        <label class="rf-label rf-loc-equip-mobile-title rf-mobile-section-title mb-2 hidden">Location &amp; equipment</label>

                        {{-- SCAN EQUIPMENT QR (fills Location + Equipment) --}}
                        <div class="rf-qr-wrap">
                        <button
                            type="button"
                            id="qrScanCard"
                            class="rf-qr-card is-locked"
                            aria-disabled="true"
                        >
                            <span class="rf-qr-card-icon" aria-hidden="true">
                                <i data-lucide="scan-qr-code" class="h-5 w-5"></i>
                            </span>
                            <span class="rf-qr-card-text">
                                <span class="rf-qr-card-title">Scan equipment QR</span>
                                <span class="rf-qr-card-sub" id="qrScanCardSub">Enter your Employee ID first</span>
                            </span>
                            <i data-lucide="chevron-right" class="rf-qr-card-arrow h-5 w-5" aria-hidden="true"></i>
                        </button>
                        <p id="qrScanNotice" class="rf-qr-notice hidden" role="status"></p>
                        <div class="rf-qr-divider" aria-hidden="true"><span>or choose manually</span></div>
                        </div>

                        <div class="rf-loc-equip-card mb-3 grid grid-cols-1 gap-5 md:mb-0 md:grid-cols-2">
                            {{-- LOCATION --}}
                            <div class="rf-loc-block min-w-0">
                                <div class="mb-2 hidden items-center justify-between md:flex">
                                    <label class="rf-label" style="margin-bottom: 0">Location <span class="text-red-500">*</span></label>
                                </div>

                                <div class="rf-loc-equip-row">
                                    <span class="rf-loc-equip-icon md:hidden" aria-hidden="true">
                                        <i data-lucide="map-pin" class="h-4 w-4"></i>
                                    </span>
                                    <div class="rf-loc-equip-control min-w-0 flex-1">
                                        <span class="rf-loc-equip-hint md:hidden">Tap to choose</span>
                                        <div
                                            id="roomDropdownContainer"
                                            class="rf-select-wrap"
                                        >
                                            <select
                                                name="report_room_id"
                                                id="roomSelect"
                                                class="rf-input details-textarea rf-native-select"
                                                data-picker-title="Select location"
                                                data-picker-search="Search rooms"
                                                style="
                                                    height: 48px;
                                                    padding-right: 36px;
                                                    color: #0f172a;
                                                    cursor: pointer;
                                                "
                                            >
                                                <option value="">Select Location</option>
                                                @foreach ($rooms as $room)
                                                    <option value="{{ $room->room_id }}">
                                                        {{ $room->floor_level }} - {{ $room->room_name }} - Eq.{{ (int) ($room->equipment_count ?? 0) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <p id="locationError" class="mt-1 hidden text-[14px] text-red-500">Please select a location.</p>
                            </div>

                            {{-- EQUIPMENT --}}
                            <div class="rf-equip-block min-w-0">
                                <div class="mb-2 hidden items-center justify-between md:flex">
                                    <label class="rf-label" style="margin-bottom: 0">Equipment <span class="text-red-500">*</span></label>
                                </div>

                                <div class="rf-equip-field-row flex items-stretch gap-2">
                                    <div class="rf-equip-field-control min-w-0 flex-1">
                                        <div id="equipmentDropdownContainer">
                                            <div class="rf-loc-equip-row">
                                                <span class="rf-loc-equip-icon md:hidden" aria-hidden="true">
                                                    <i data-lucide="monitor" class="h-4 w-4"></i>
                                                </span>
                                                <div class="rf-loc-equip-control min-w-0 flex-1">
                                                    <span class="rf-loc-equip-hint md:hidden">Tap to choose</span>
                                                    <div class="rf-select-wrap" style="width: 100%">
                                                        <select
                                                            id="equipmentSelect"
                                                            class="rf-input details-textarea rf-native-select"
                                                            data-picker-title="Select equipment"
                                                            data-picker-search="Search equipment"
                                                            style="
                                                                height: 48px;
                                                                padding-right: 36px;
                                                                cursor: pointer;
                                                                width: 100%;
                                                            "
                                                        >
                                                            <option value="">Select Equipment</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="equipmentManualContainer" class="hidden">
                                            <div class="rf-loc-equip-row">
                                                <span class="rf-loc-equip-icon md:hidden" aria-hidden="true">
                                                    <i data-lucide="keyboard" class="h-4 w-4"></i>
                                                </span>
                                                <div class="rf-loc-equip-control min-w-0 flex-1">
                                                    <span class="rf-loc-equip-hint md:hidden">Type equipment name</span>
                                                    <input
                                                        type="text"
                                                        id="equipmentManualInput"
                                                        placeholder="Enter equipment name manually..."
                                                        class="rf-input details-textarea"
                                                        style="height: 48px; width: 100%"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            id="selectedEquipmentList"
                            class="mb-3 flex flex-col gap-2"
                        ></div>
                        <div id="selectedEquipmentInputs"></div>
                    </div>

                    {{-- SUGGESTED ISSUES --}}
                    <div class="rf-issues-block mt-3">
                        <div
                            class="rf-issues-head mb-2 flex items-center justify-between gap-2"
                        >
                            <label class="rf-label rf-section-title rf-mobile-section-title" style="margin-bottom: 0">
                                Suggested issues
                                <span id="issueCount">(0)</span>
                            </label>

                            <div
                                id="issueControls"
                                class="flex hidden items-center gap-2"
                            >
                                <button
                                    type="button"
                                    id="scrollLeftBtn"
                                    class="issue-action-btn yellow"
                                >
                                    <i
                                        data-lucide="chevron-left"
                                        class="h-4 w-4"
                                    >
                                    </i>
                                </button>

                                <button
                                    type="button"
                                    id="scrollRightBtn"
                                    class="issue-action-btn yellow"
                                >
                                    <i
                                        data-lucide="chevron-right"
                                        class="h-4 w-4"
                                    >
                                    </i>
                                </button>

                                <button
                                    type="button"
                                    id="clearIssueBtn"
                                    onclick="clearSuggestedIssue()"
                                    class="issue-action-btn red hidden"
                                >
                                    <i data-lucide="x" class="h-4 w-4"> </i>
                                </button>
                            </div>
                        </div>

                        <div
                            id="issueCarousel"
                            class="rf-issues-panel flex gap-2 overflow-x-auto scroll-smooth"
                        >
                            <div
                                id="issuePlaceholder"
                                class="issue-placeholder"
                            >
                                <span class="issue-placeholder-icon" aria-hidden="true">
                                    <i data-lucide="info" class="h-4 w-4"></i>
                                </span>
                                <span class="issue-placeholder-text">Select equipment first to see suggested issues.</span>
                            </div>
                        </div>

                        <p
                            id="issueError"
                            class="hidden"
                            style="
                                color: red;
                                font-size: 14px;
                                margin-top: 8px;
                                margin-bottom: 8px;
                            "
                        >Please select a suggested issue or provide additional details.</p>

                        <input
                            type="hidden"
                            id="suggestedIssueInput"
                            name="report_suggested_issue"
                        />
                    </div>

                    <!-- PROBLEM DESCRIPTION WRAPPER -->
                    <div class="rf-details-block mt-4">
                        <div class="rf-details-head mb-2">
                            <label
                                class="rf-label rf-section-title rf-mobile-section-title"
                                style="margin-bottom: 0"
                            >
                                Additional details
                            </label>

                            <p class="rf-details-hint">
                                For the selected equipment · optional if an issue is picked
                            </p>
                        </div>

                        <div class="rf-details-field" style="position: relative">
                            <textarea
                                id="problemDescription"
                                rows="4"
                                disabled
                                placeholder="Select equipment first, then describe its problem"
                                data-placeholder-ready="Describe this equipment's problem (mention sparks, smoke, or exposed wires if any)"
                                data-placeholder-idle="Select equipment first, then describe its problem"
                                class="rf-input details-textarea"
                                style="
                                    resize: none;
                                    min-height: 140px;
                                    padding: 16px 40px 16px 16px;
                                    line-height: 1.6;
                                "
                            ></textarea>

                            <div
                                id="clearDescriptionWrapper"
                                class="hidden"
                                style="
                                    position: absolute;
                                    top: 10px;
                                    right: 8px;
                                    z-index: 20;
                                "
                            >
                                <button
                                    type="button"
                                    id="clearDescriptionBtn"
                                    onclick="clearProblemDescription()"
                                    style="
                                        width: 24px;
                                        height: 24px;
                                        border-radius: 999px;
                                        border: none;
                                        background: rgba(239, 68, 68, 0.12);
                                        color: #ef4444;
                                        cursor: pointer;
                                        font-size: 12px;
                                        font-weight: 700;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                    "
                                >
                                    ✕
                                </button>
                            </div>
                        </div>

                        <p
                            id="detailsError"
                            class="hidden"
                            style="
                                color: red;
                                font-size: 14px;
                                margin-top: 8px;
                            "
                        >Please select a suggested issue or provide additional details.</p>
                    </div>

                    {{-- ADD TO REPORT: last step for each item, after its issue and details --}}
                    <div class="rf-add-block mt-4">
                        <p id="equipmentError" class="mb-2 hidden text-[14px] text-red-500">Please add at least one equipment.</p>

                        <button
                            type="button"
                            id="addEquipmentBtn"
                            class="rf-add-equip-btn is-waiting"
                            aria-disabled="true"
                        >
                            <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
                            <span class="rf-add-equip-label">Add to report</span>
                        </button>
                        <button
                            type="button"
                            id="addManualEquipmentBtn"
                            class="rf-add-equip-btn is-waiting hidden"
                            aria-disabled="true"
                        >
                            <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
                            <span class="rf-add-equip-label">Add to report</span>
                        </button>

                        <p id="rfAddHint" class="rf-add-hint">Pick the equipment first.</p>
                    </div>
                </div>
                {{-- This is the single, correct closing tag for the LEFT PANEL grid column --}}

                {{-- ══════════════════ RIGHT PANEL ══════════════════ --}}
                <!-- RIGHT PANEL -->
                <!-- SAME FIXED HEIGHT AS LEFT PANEL -->
                <!-- ACTION BUTTONS STAY AT THE BOTTOM -->

                <div
                    class="
                        report-form-aside
                        min-h-0
                        overflow-y-auto

                        flex
                        flex-col
                        gap-6

                        p-6
                        sm:p-7

                        lg:col-span-4
                        lg:h-full
                    "
                    style="
                        background: rgba(255, 255, 255, 0.015);
                        border-top: 1px solid rgba(255, 255, 255, 0.07);
                    "
                >
                    {{-- CLOSE (desktop) --}}
                    <div class="report-form-aside-head hidden items-center justify-end gap-2 lg:flex">
                        <button
                            type="button"
                            class="rf-help-btn flex h-8 w-8 items-center justify-center rounded-lg transition"
                            data-report-tour-start
                            title="How to report"
                            aria-label="How to report"
                        >
                            <i data-lucide="circle-help" class="h-4 w-4"></i>
                        </button>
                        <button
                            type="button"
                            id="reportModalExpandBtn"
                            onclick="toggleReportModalExpand()"
                            class="rf-expand-btn inline-flex h-8 w-8 items-center justify-center rounded-lg transition"
                            title="Full screen"
                            aria-label="Full screen"
                        >
                            <i data-lucide="maximize-2" id="reportModalExpandIcon" class="h-4 w-4"></i>
                        </button>
                        <button
                            type="button"
                            onclick="requestCloseReportModal()"
                            class="rf-close-desktop flex h-8 w-8 items-center justify-center rounded-lg transition"
                            aria-label="Close"
                        >
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>

                    {{-- EQUIPMENT TO REPORT (desktop: the added list lives here; phones keep it under the picker) --}}
                    <div id="rfAsideEquipmentPanel" class="report-form-aside-body rf-aside-eq">
                        <div class="rf-aside-eq-head">
                            <span class="rf-label" style="margin-bottom: 0">Equipment to report</span>
                            <span id="rfAsideEquipmentCount" class="rf-aside-eq-count">0</span>
                        </div>
                        <p id="rfAsideEquipmentEmpty" class="rf-aside-eq-empty">
                            No equipment added yet. Pick a location, equipment, and issue, then tap <strong>Add to report</strong>.
                        </p>
                        <div id="rfAsideEquipmentSlot"></div>
                    </div>

                    {{-- ACTIONS --}}
                    <div class="report-form-aside-actions mt-auto flex flex-col gap-2 pt-2">
                        <button
                            type="submit"
                            id="submitReportBtn"
                            class="rf-submit-btn flex w-full items-center justify-center gap-2 rounded-2xl py-4 font-bold transition"
                        >
                            <i data-lucide="send" class="h-4 w-4"></i>
                            Submit Report
                        </button>

                        <button
                            type="button"
                            onclick="requestCloseReportModal()"
                            class="rf-cancel-btn w-full rounded-2xl py-4 font-semibold transition"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<div id="rfPickerOverlay" class="rf-picker-overlay">
    <div class="rf-picker-sheet" role="dialog" aria-modal="true" aria-labelledby="rfPickerTitle">
        <div class="rf-picker-head">
            <div class="rf-picker-kicker">Choose an option</div>
            <div class="rf-picker-title" id="rfPickerTitle">Select</div>
            <div class="rf-picker-hint" id="rfPickerHint" hidden></div>
            <button type="button" class="rf-picker-dismiss" id="rfPickerDismiss" aria-label="Close">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>
        <div class="rf-picker-body">
            <div class="rf-picker-search">
                <i data-lucide="search" class="h-4 w-4" style="color:#0025cc;"></i>
                <input type="search" id="rfPickerSearch" placeholder="Search" autocomplete="off">
            </div>
            <div class="rf-picker-categories" id="rfPickerCategories" hidden></div>
            <div class="rf-picker-list" id="rfPickerList"></div>
        </div>
        <button type="button" class="rf-picker-close" id="rfPickerClose">Done</button>
    </div>
</div>

<div id="rfPhotoSourceOverlay" class="rf-photo-source-overlay" hidden>
    <div class="rf-photo-source-sheet" role="dialog" aria-modal="true" aria-label="Upload proof image">
        <div class="rf-photo-source-handle" aria-hidden="true"></div>
        {{-- Labels keep the user gesture so the native picker is allowed --}}
        <label class="rf-photo-source-tile" id="rfPhotoSourceCamera" for="proofImageCameraInput">
            <span class="rf-photo-source-icon" aria-hidden="true">
                <i data-lucide="camera" class="h-5 w-5"></i>
            </span>
            <span class="rf-photo-source-copy">
                <span class="rf-photo-source-title">Take photo</span>
                <span class="rf-photo-source-sub">Use your camera</span>
            </span>
            <i data-lucide="chevron-right" class="rf-photo-source-chevron h-5 w-5" aria-hidden="true"></i>
        </label>
        <label class="rf-photo-source-tile" id="rfPhotoSourceGallery" for="proofImageGalleryInput">
            <span class="rf-photo-source-icon" aria-hidden="true">
                <i data-lucide="image" class="h-5 w-5"></i>
            </span>
            <span class="rf-photo-source-copy">
                <span class="rf-photo-source-title">Choose from gallery</span>
                <span class="rf-photo-source-sub">Pick an existing image</span>
            </span>
            <i data-lucide="chevron-right" class="rf-photo-source-chevron h-5 w-5" aria-hidden="true"></i>
        </label>
    </div>
</div>

{{-- Outside overlay + not display:none so camera/gallery pickers work on mobile --}}
<input
    type="file"
    id="proofImageCameraInput"
    class="rf-file-input-native"
    accept="image/*"
    capture="environment"
    tabindex="-1"
    aria-hidden="true"
/>
<input
    type="file"
    id="proofImageGalleryInput"
    class="rf-file-input-native"
    accept="image/*"
    tabindex="-1"
    aria-hidden="true"
/>

<div id="rfProofFullscreen" class="rf-proof-fs" hidden>
    <button type="button" class="rf-proof-fs-close" id="rfProofFsClose" aria-label="Close preview">
        <i data-lucide="x" class="h-5 w-5"></i>
    </button>
    <div class="rf-proof-fs-stage" id="rfProofFsStage">
        <img id="rfProofFsImg" alt="Proof image" />
    </div>
    <div class="rf-proof-fs-hint">Pinch to zoom</div>
</div>

<div id="rfDropdownMenu" class="rf-dropdown-menu" hidden>
    <div class="rf-dropdown-search" id="rfDropdownSearchWrap" hidden>
        <i data-lucide="search" class="h-4 w-4"></i>
        <input type="search" id="rfDropdownSearch" placeholder="Search rooms" autocomplete="off">
    </div>
    <div class="rf-dropdown-list" id="rfDropdownList"></div>
</div>
<div id="rfOptionTip" class="rf-option-tip" hidden></div>

{{-- QUICK CHECKS (after picking a suggested issue, before the item is added) --}}
<div id="rfCheckOverlay" class="rf-qr-overlay" hidden>
    <div class="rf-qr-sheet" role="dialog" aria-modal="true" aria-labelledby="rfCheckTitle">
        <div class="rf-qr-head">
            <div class="min-w-0">
                <p class="rf-check-kicker">Quick checks</p>
                <p class="rf-qr-title" id="rfCheckTitle">Let's try a few things first</p>
                <p class="rf-qr-sub" id="rfCheckSub"></p>
            </div>
            <button type="button" class="rf-qr-close" id="rfCheckClose" aria-label="Cancel quick checks">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <div id="rfCheckStep">
            <div class="rf-check-progress" id="rfCheckProgress" aria-hidden="true"></div>
            <p class="rf-check-count" id="rfCheckCount"></p>
            <p class="rf-check-question" id="rfCheckQuestion"></p>
            <p class="rf-check-hint" id="rfCheckHint" hidden>
                <i data-lucide="lightbulb" class="h-4 w-4"></i>
                <span id="rfCheckHintText"></span>
            </p>
            <div class="rf-check-actions" id="rfCheckActions"></div>
            <div class="rf-check-foot">
                <button type="button" class="rf-check-link" id="rfCheckBack">Back</button>
                <span>If one fixes it, you won't need to report.</span>
            </div>
        </div>

        <div class="rf-check-result is-fixed" id="rfCheckFixed" hidden>
            <div class="rf-check-result-icon"><i data-lucide="check" class="h-7 w-7"></i></div>
            <p class="rf-check-result-title">Great, it works now</p>
            <p class="rf-check-result-text">No report needed for this equipment. Thanks for checking first.</p>
            <button type="button" class="rf-check-btn is-primary" id="rfCheckFixedDone">Done</button>
        </div>

        <div class="rf-check-result is-danger" id="rfCheckDanger" hidden>
            <div class="rf-check-result-icon"><i data-lucide="triangle-alert" class="h-7 w-7"></i></div>
            <p class="rf-check-result-title">Report this right away</p>
            <p class="rf-check-result-text" id="rfCheckDangerText"></p>
            <button type="button" class="rf-check-btn is-danger" id="rfCheckDangerAdd">Add to report</button>
        </div>
    </div>
</div>

{{-- QR SCANNER SHEET (reporter scan-to-fill) --}}
<div id="rfQrOverlay" class="rf-qr-overlay" hidden>
    <div class="rf-qr-sheet" role="dialog" aria-modal="true" aria-labelledby="rfQrTitle">
        <div class="rf-qr-head">
            <div>
                <p class="rf-qr-title" id="rfQrTitle">Scan equipment QR</p>
                <p class="rf-qr-sub">Point the camera at the QR label on the equipment.</p>
            </div>
            <button type="button" class="rf-qr-close" id="rfQrClose" aria-label="Close scanner">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <div class="rf-qr-stage" id="rfQrStage">
            <div id="rfQrReader"></div>
            <div class="rf-qr-stage-empty" id="rfQrStageEmpty" hidden>
                <i data-lucide="camera-off" class="h-7 w-7"></i>
                <p id="rfQrStageEmptyText">Live camera is not available on this connection.</p>
            </div>
        </div>

        <p class="rf-qr-status" id="rfQrStatus" role="status">Starting camera…</p>
        <p class="rf-qr-error" id="rfQrError" role="alert" hidden></p>

        <label class="rf-qr-photo-btn" for="rfQrPhotoInput">
            <i data-lucide="camera" class="h-4 w-4"></i>
            <span>Take a photo of the QR instead</span>
        </label>
        <div id="rfQrFileReader" hidden></div>
    </div>
</div>
<input
    type="file"
    id="rfQrPhotoInput"
    class="rf-file-input-native"
    accept="image/*"
    capture="environment"
    tabindex="-1"
    aria-hidden="true"
/>

<script>
    /* ── RE-RENDER ICONS ── */
    let reporterVerified = false;

    let selectedSuggestedIssue = "";

    if (typeof lucide !== "undefined") lucide.createIcons();

    function hideIssueOrDetailsErrors() {
        const issueError = document.getElementById("issueError");
        const detailsError = document.getElementById("detailsError");
        const details = document.getElementById("problemDescription");

        if (issueError) {
            issueError.classList.add("hidden");
        }

        if (detailsError) {
            detailsError.classList.add("hidden");
        }

        if (details) {
            details.style.borderColor = "";
        }
    }

    /* ─────────────────────────────────────────────────────────
   SUGGESTED ISSUE AUTO-FILL
───────────────────────────────────────────────────────── */
    const descriptionTextarea = document.getElementById("problemDescription");
    const clearDescriptionBtn = document.getElementById("clearDescriptionBtn");
    const clearDescriptionWrapper = document.getElementById(
        "clearDescriptionWrapper",
    );

    descriptionTextarea.addEventListener("input", function () {
        updateClearButtonVisibility();

        if (this.value.trim() !== "") {
            hideIssueOrDetailsErrors();
        }
    });

    /*
|--------------------------------------------------------------------------
| REMOVE ACTIVE IF DESCRIPTION CLEARED
|--------------------------------------------------------------------------


descriptionTextarea.addEventListener('input', function () {

    if(this.value.trim() === ''){

        document.querySelectorAll('.issue-btn')
            .forEach(btn =>
                btn.classList.remove('active')
            );

    }

});*/

    /*descriptionTextarea.addEventListener(
    'input',
    function(){

        const text =
            this.value.trim();

        let matched = false;

        document
            .querySelectorAll('.issue-btn')
            .forEach(btn => {

                if(
                    btn.innerText.trim() === text
                ){

                    btn.classList.add(
                        'active'
                    );

                    matched = true;

                }
                else{

                    btn.classList.remove(
                        'active'
                    );

                }

            });

        if(!matched){

            document
                .querySelectorAll('.issue-btn')
                .forEach(btn =>
                    btn.classList.remove(
                        'active'
                    )
                );

        }

    }
);*/

    /*
descriptionTextarea.addEventListener(
    'input',
    function(){

        if(
            this.value.trim() !== ''
        ){

            document
                .querySelectorAll('.issue-btn')
                .forEach(btn =>
                    btn.classList.remove('active')
                );

        }

    }
);*/

    /* ─────────────────────────────────────────────────────────
   ISSUE CAROUSEL SCROLL
───────────────────────────────────────────────────────── */
    const issueCarousel = document.getElementById("issueCarousel");

    document.getElementById("scrollLeftBtn").addEventListener("click", () => {
        issueCarousel.scrollBy({ left: -280, behavior: "smooth" });
    });
    document.getElementById("scrollRightBtn").addEventListener("click", () => {
        issueCarousel.scrollBy({ left: 280, behavior: "smooth" });
    });

    /*
|--------------------------------------------------------------------------
| PREMIUM MOMENTUM DRAG CAROUSEL
|--------------------------------------------------------------------------
*/

    let isDragging = false;
    let startX = 0;
    let startScrollLeft = 0;
    let velocity = 0;
    let lastX = 0;
    let animationFrame;

    issueCarousel.style.cursor = "grab";

    issueCarousel.addEventListener("pointerdown", (e) => {
        isDragging = true;

        issueCarousel.style.cursor = "grabbing";

        cancelAnimationFrame(animationFrame);

        startX = e.clientX;

        lastX = e.clientX;

        startScrollLeft = issueCarousel.scrollLeft;

        velocity = 0;
    });

    window.addEventListener("pointermove", (e) => {
        if (!isDragging) return;

        e.preventDefault();

        const dx = e.clientX - startX;

        issueCarousel.scrollLeft = startScrollLeft - dx;

        velocity = e.clientX - lastX;

        lastX = e.clientX;
    });

    window.addEventListener("pointerup", () => {
        if (!isDragging) return;

        isDragging = false;

        issueCarousel.style.cursor = "grab";

        momentumScroll();
    });

    function momentumScroll() {
        issueCarousel.scrollLeft -= velocity * 4;

        velocity *= 0.95;

        if (Math.abs(velocity) > 0.5) {
            animationFrame = requestAnimationFrame(momentumScroll);
        }
    }

    /*
|--------------------------------------------------------------------------
| TOUCH SWIPE SUPPORT
|--------------------------------------------------------------------------
*/

    let touchStartX = 0;
    let touchScrollLeft = 0;

    issueCarousel.addEventListener("touchstart", (e) => {
        touchStartX = e.touches[0].pageX;

        touchScrollLeft = issueCarousel.scrollLeft;
    });

    issueCarousel.addEventListener("touchmove", (e) => {
        const touchX = e.touches[0].pageX;

        const walk = touchX - touchStartX;

        issueCarousel.scrollLeft = touchScrollLeft - walk;
    });

    /* ─────────────────────────────────────────────────────────
   FILE UPLOAD — mirrors PRISM_MOBILE report_screen.pickImage()
   takePhoto() / chooseFromGallery() → pickImageFromSource()
───────────────────────────────────────────────────────── */
    function isMobileReportViewport() {
        return window.matchMedia("(max-width: 767px)").matches;
    }

    /** Index in selectedEquipmentItems that the next picked photo belongs to. */
    let photoTargetIndex = null;

    function attachPhotoToTarget(file) {
        const index = photoTargetIndex;
        photoTargetIndex = null;
        const item = index === null ? null : selectedEquipmentItems[index];
        if (!item || !file) return null;

        if (item.photoUrl) URL.revokeObjectURL(item.photoUrl);
        item.photo = file;
        item.photoUrl = URL.createObjectURL(file);
        delete item.photoDataUrl;

        const reader = new FileReader();
        reader.onload = function () {
            if (selectedEquipmentItems[index] === item && typeof reader.result === "string") {
                item.photoDataUrl = reader.result;
                persistReportDraft();
            }
        };
        reader.readAsDataURL(file);

        renderSelectedEquipment();
        return file;
    }

    function removeItemPhoto(index) {
        const item = selectedEquipmentItems[index];
        if (!item) return;
        if (item.photoUrl) URL.revokeObjectURL(item.photoUrl);
        delete item.photo;
        delete item.photoUrl;
        delete item.photoDataUrl;
        renderSelectedEquipment();
    }

    function revokeItemPhotos(items) {
        (items || []).forEach(function (item) {
            if (item && item.photoUrl) URL.revokeObjectURL(item.photoUrl);
        });
    }

    /** Manual photos are keyed by the item's position among manual entries, matching report_equipment_manuals[]. */
    function buildReportFormData(formEl, items) {
        const data = new FormData(formEl);
        let manualIndex = 0;

        (items || []).forEach(function (item) {
            const key = item.type === "id"
                ? "report_equipment_images[" + item.id + "]"
                : "report_equipment_manual_images[" + manualIndex + "]";
            if (item.type !== "id") manualIndex += 1;
            if (item.photo) data.append(key, item.photo, item.photo.name || "equipment-photo.jpg");
        });

        return data;
    }

    function showProofImageFullscreen(url) {
        const fs = document.getElementById("rfProofFullscreen");
        const fsImg = document.getElementById("rfProofFsImg");
        if (!fs || !fsImg || !url) return;

        fsImg.src = url;
        fs.hidden = false;
        fs.classList.add("is-open");
        document.body.style.overflow = "hidden";
        resetProofFsTransform();
        if (window.lucide) lucide.createIcons();
    }

    function closeProofFullscreen() {
        const fs = document.getElementById("rfProofFullscreen");
        const fsImg = document.getElementById("rfProofFsImg");
        if (fs) {
            fs.hidden = true;
            fs.classList.remove("is-open");
        }
        if (fsImg) fsImg.removeAttribute("src");
        document.body.style.overflow = "";
        resetProofFsTransform();
    }

    let proofFsScale = 1;
    let proofFsX = 0;
    let proofFsY = 0;

    function applyProofFsTransform() {
        const fsImg = document.getElementById("rfProofFsImg");
        if (!fsImg) return;
        fsImg.style.transform =
            "translate(" +
            proofFsX +
            "px, " +
            proofFsY +
            "px) scale(" +
            proofFsScale +
            ")";
    }

    function resetProofFsTransform() {
        proofFsScale = 1;
        proofFsX = 0;
        proofFsY = 0;
        applyProofFsTransform();
    }

    function initProofFullscreenGestures() {
        const stage = document.getElementById("rfProofFsStage");
        const fs = document.getElementById("rfProofFullscreen");
        const closeBtn = document.getElementById("rfProofFsClose");
        if (!stage || !fs) return;

        let pointers = new Map();
        let pinchStartDist = 0;
        let pinchStartScale = 1;
        let panStartX = 0;
        let panStartY = 0;
        let originX = 0;
        let originY = 0;

        function pointerDistance() {
            const pts = Array.from(pointers.values());
            if (pts.length < 2) return 0;
            const dx = pts[0].x - pts[1].x;
            const dy = pts[0].y - pts[1].y;
            return Math.hypot(dx, dy);
        }

        stage.addEventListener("pointerdown", function (e) {
            stage.setPointerCapture(e.pointerId);
            pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
            if (pointers.size === 1) {
                panStartX = e.clientX;
                panStartY = e.clientY;
                originX = proofFsX;
                originY = proofFsY;
            } else if (pointers.size === 2) {
                pinchStartDist = pointerDistance();
                pinchStartScale = proofFsScale;
            }
        });

        stage.addEventListener("pointermove", function (e) {
            if (!pointers.has(e.pointerId)) return;
            pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });

            if (pointers.size === 2 && pinchStartDist > 0) {
                const dist = pointerDistance();
                proofFsScale = Math.min(
                    4,
                    Math.max(1, pinchStartScale * (dist / pinchStartDist)),
                );
                if (proofFsScale <= 1.01) {
                    proofFsScale = 1;
                    proofFsX = 0;
                    proofFsY = 0;
                }
                applyProofFsTransform();
            } else if (pointers.size === 1 && proofFsScale > 1) {
                proofFsX = originX + (e.clientX - panStartX);
                proofFsY = originY + (e.clientY - panStartY);
                applyProofFsTransform();
            }
        });

        function endPointer(e) {
            pointers.delete(e.pointerId);
            if (pointers.size < 2) pinchStartDist = 0;
            if (pointers.size === 1) {
                const remaining = pointers.values().next().value;
                panStartX = remaining.x;
                panStartY = remaining.y;
                originX = proofFsX;
                originY = proofFsY;
            }
            if (proofFsScale <= 1) {
                proofFsScale = 1;
                proofFsX = 0;
                proofFsY = 0;
                applyProofFsTransform();
            }
        }

        stage.addEventListener("pointerup", endPointer);
        stage.addEventListener("pointercancel", endPointer);
        stage.addEventListener("pointerleave", endPointer);

        stage.addEventListener(
            "wheel",
            function (e) {
                if (!fs.classList.contains("is-open")) return;
                e.preventDefault();
                const delta = e.deltaY > 0 ? -0.12 : 0.12;
                proofFsScale = Math.min(4, Math.max(1, proofFsScale + delta));
                if (proofFsScale === 1) {
                    proofFsX = 0;
                    proofFsY = 0;
                }
                applyProofFsTransform();
            },
            { passive: false },
        );

        if (closeBtn) {
            closeBtn.addEventListener("click", function (e) {
                e.preventDefault();
                closeProofFullscreen();
            });
        }

        document.addEventListener("keydown", function (e) {
            if (
                e.key === "Escape" &&
                fs.classList.contains("is-open")
            ) {
                closeProofFullscreen();
            }
        });
    }

    function validateProofFile(file) {
        const maxSize = 10 * 1024 * 1024;

        if (!file) return false;

        const type = String(file.type || "").toLowerCase();
        const name = String(file.name || "").toLowerCase();
        const allowedTypes = ["image/jpeg", "image/png", "image/webp", "image/jpg"];
        const allowedExt = /\.(jpe?g|png|webp)$/i.test(name);
        const looksLikeImage =
            allowedTypes.includes(type) ||
            type.startsWith("image/") ||
            (!type && allowedExt);

        if (!looksLikeImage) {
            alert(
                "Unsupported file format. Please upload a PNG, JPG, JPEG, or WEBP image.",
            );
            return false;
        }

        if (file.size > maxSize) {
            alert(
                "File size exceeds the 10MB limit. Please upload a smaller image.",
            );
            return false;
        }

        return true;
    }

    /**
     * Match Flutter ImagePicker: imageQuality 55, maxWidth/maxHeight 1280.
     */
    function compressProofImage(file) {
        return new Promise(function (resolve) {
            if (!file) {
                resolve(file);
                return;
            }

            const maxWidth = 1280;
            const maxHeight = 1280;
            const quality = 0.55;
            const objectUrl = URL.createObjectURL(file);
            const img = new Image();

            img.onload = function () {
                URL.revokeObjectURL(objectUrl);

                let width = img.naturalWidth || img.width;
                let height = img.naturalHeight || img.height;

                if (!width || !height) {
                    resolve(file);
                    return;
                }

                const scale = Math.min(1, maxWidth / width, maxHeight / height);
                width = Math.max(1, Math.round(width * scale));
                height = Math.max(1, Math.round(height * scale));

                const canvas = document.createElement("canvas");
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext("2d");
                if (!ctx) {
                    resolve(file);
                    return;
                }

                ctx.drawImage(img, 0, 0, width, height);

                const type = String(file.type || "").toLowerCase();
                const outputType =
                    type === "image/png" || type === "image/webp"
                        ? type
                        : "image/jpeg";

                canvas.toBlob(
                    function (blob) {
                        if (!blob) {
                            resolve(file);
                            return;
                        }

                        let name = file.name || "proof-image.jpg";
                        if (outputType === "image/jpeg" && !/\.jpe?g$/i.test(name)) {
                            name = name.replace(/\.[^.]+$/, "") + ".jpg";
                            if (name === ".jpg") name = "proof-image.jpg";
                        }

                        resolve(
                            new File([blob], name, {
                                type: outputType,
                                lastModified: Date.now(),
                            }),
                        );
                    },
                    outputType,
                    quality,
                );
            };

            img.onerror = function () {
                URL.revokeObjectURL(objectUrl);
                resolve(file);
            };

            img.src = objectUrl;
        });
    }

    /**
     * Open native camera or gallery picker (ImageSource.camera / gallery).
     */
    function openNativeImagePicker(source) {
        return new Promise(function (resolve) {
            const cameraInput = document.getElementById("proofImageCameraInput");
            const galleryInput = document.getElementById("proofImageGalleryInput");
            const input = source === "camera" ? cameraInput : galleryInput;

            if (!input) {
                resolve(null);
                return;
            }

            let settled = false;

            function finish(file) {
                if (settled) return;
                settled = true;
                input.removeEventListener("change", onChange);
                input.value = "";
                resolve(file || null);
            }

            function onChange() {
                const file =
                    input.files && input.files[0] ? input.files[0] : null;
                finish(file);
            }

            input.value = "";
            input.addEventListener("change", onChange, { once: true });

            try {
                input.click();
            } catch (err) {
                finish(null);
            }
        });
    }

    /**
     * Mirror: picker.pickImage(source:, imageQuality: 55, maxWidth: 1280, maxHeight: 1280)
     */
    async function pickImageFromSource(source) {
        const raw = await openNativeImagePicker(source);
        if (!raw) return null;

        if (!validateProofFile(raw)) {
            photoTargetIndex = null;
            return null;
        }

        const compressed = await compressProofImage(raw);
        return attachPhotoToTarget(compressed);
    }

    /** Mirror PRISM_MOBILE ImageSource.camera */
    function takePhoto() {
        return pickImageFromSource("camera");
    }

    /** Mirror PRISM_MOBILE ImageSource.gallery */
    function chooseFromGallery() {
        return pickImageFromSource("gallery");
    }

    async function applyPickedProofFile(rawFile) {
        if (!rawFile || !validateProofFile(rawFile)) {
            photoTargetIndex = null;
            return null;
        }
        const compressed = await compressProofImage(rawFile);
        return attachPhotoToTarget(compressed);
    }

    (function initProofImagePicker() {
        const cameraInput = document.getElementById("proofImageCameraInput");
        const galleryInput = document.getElementById("proofImageGalleryInput");
        const overlay = document.getElementById("rfPhotoSourceOverlay");

        function closePhotoSourceSheet() {
            if (!overlay) return;
            overlay.hidden = true;
            overlay.classList.remove("is-open");
        }

        function openPhotoSourceSheet() {
            if (!overlay) {
                chooseFromGallery();
                return;
            }
            overlay.hidden = false;
            overlay.classList.add("is-open");
            if (window.lucide) lucide.createIcons();
        }

        /** Mirror PRISM_MOBILE report_screen.pickImage() */
        function pickImage() {
            if (document.activeElement && document.activeElement.blur) {
                document.activeElement.blur();
            }

            if (isMobileReportViewport()) {
                openPhotoSourceSheet();
                return;
            }

            chooseFromGallery();
        }

        function onNativeSourcePicked(rawFile) {
            closePhotoSourceSheet();
            applyPickedProofFile(rawFile);
        }

        window.openItemPhotoPicker = function (index) {
            if (!selectedEquipmentItems[index]) return;
            photoTargetIndex = index;
            pickImage();
        };

        initProofFullscreenGestures();

        // Labels use for="…" so the browser opens the picker from the same tap.
        // Do not hide the sheet in this click handler — that can cancel the picker.
        if (cameraInput) {
            cameraInput.addEventListener("change", function () {
                const file =
                    cameraInput.files && cameraInput.files[0]
                        ? cameraInput.files[0]
                        : null;
                cameraInput.value = "";
                onNativeSourcePicked(file);
            });
        }

        if (galleryInput) {
            galleryInput.addEventListener("change", function () {
                const file =
                    galleryInput.files && galleryInput.files[0]
                        ? galleryInput.files[0]
                        : null;
                galleryInput.value = "";
                onNativeSourcePicked(file);
            });
        }

        if (overlay) {
            overlay.addEventListener("click", function (e) {
                if (e.target === overlay) closePhotoSourceSheet();
            });
        }

        document.addEventListener("keydown", function (e) {
            if (
                e.key === "Escape" &&
                overlay &&
                overlay.classList.contains("is-open")
            ) {
                closePhotoSourceSheet();
            }
        });

        const reportModal = document.getElementById("reportModal");
        if (reportModal) {
            const hideObserver = new MutationObserver(function () {
                if (reportModal.classList.contains("hidden")) {
                    closePhotoSourceSheet();
                    closeProofFullscreen();
                }
            });
            hideObserver.observe(reportModal, {
                attributes: true,
                attributeFilter: ["class"],
            });
        }

        window.rfPickImage = pickImage;
        window.rfTakePhoto = takePhoto;
        window.rfChooseFromGallery = chooseFromGallery;
    })();

    /* ─────────────────────────────────────────────────────────
   ROOM INPUT TOGGLE
───────────────────────────────────────────────────────── 
const toggleRoomBtn         = document.getElementById('toggleRoomInput');
const roomDropdown          = document.getElementById('roomDropdownContainer');
const roomManual            = document.getElementById('roomManualContainer');
let   roomManualMode        = false;

toggleRoomBtn.addEventListener('click', function () {
    roomManualMode = !roomManualMode;
    if (roomManualMode) {
        roomDropdown.classList.add('hidden');
        roomManual.classList.remove('hidden');
        this.innerText = 'Use location list instead';
    } else {
        roomDropdown.classList.remove('hidden');
        roomManual.classList.add('hidden');
        this.innerText = 'Other Location?';
    }
}); */

    /* ─────────────────────────────────────────────────────────
   EQUIPMENT INPUT TOGGLE
───────────────────────────────────────────────────────── */

    const toggleEquipmentBtn = document.getElementById("toggleEquipmentInput");
    const toggleEquipmentBtnDesktop = document.getElementById(
        "toggleEquipmentInputDesktop",
    );

    const equipmentDropdown = document.getElementById(
        "equipmentDropdownContainer",
    );

    const equipmentManual = document.getElementById("equipmentManualContainer");

    const equipmentSelect = document.getElementById("equipmentSelect");

    const equipmentManualInput = document.getElementById(
        "equipmentManualInput",
    );

    const addEquipmentBtn = document.getElementById("addEquipmentBtn");

    const addManualEquipmentBtn = document.getElementById(
        "addManualEquipmentBtn",
    );

    const selectedEquipmentList = document.getElementById(
        "selectedEquipmentList",
    );

    const selectedEquipmentInputs = document.getElementById(
        "selectedEquipmentInputs",
    );

    // Desktop shows the added equipment in the right column; smaller screens keep it under the picker.
    (function placeSelectedEquipmentList() {
        const asideSlot = document.getElementById("rfAsideEquipmentSlot");
        if (!selectedEquipmentList || !selectedEquipmentInputs || !asideSlot || !window.matchMedia) return;

        const desktopQuery = window.matchMedia("(min-width: 1024px)");
        const place = () => {
            if (desktopQuery.matches) {
                if (selectedEquipmentList.parentElement !== asideSlot) asideSlot.appendChild(selectedEquipmentList);
            } else if (selectedEquipmentList.nextElementSibling !== selectedEquipmentInputs) {
                selectedEquipmentInputs.parentElement.insertBefore(selectedEquipmentList, selectedEquipmentInputs);
            }
        };

        place();
        if (desktopQuery.addEventListener) {
            desktopQuery.addEventListener("change", place);
        } else if (desktopQuery.addListener) {
            desktopQuery.addListener(place);
        }
    })();

    let lastRenderedEquipmentCount = 0;

    function syncAsideEquipmentSummary() {
        const count = selectedEquipmentItems.length;
        const countEl = document.getElementById("rfAsideEquipmentCount");
        const emptyEl = document.getElementById("rfAsideEquipmentEmpty");
        if (countEl) countEl.textContent = String(count);
        if (emptyEl) emptyEl.classList.toggle("hidden", count > 0);

        if (count > lastRenderedEquipmentCount && selectedEquipmentList?.lastElementChild) {
            const newest = selectedEquipmentList.lastElementChild;
            newest.classList.add("is-new");
            if (selectedEquipmentList.parentElement?.id === "rfAsideEquipmentSlot") {
                newest.scrollIntoView({ block: "nearest", behavior: "smooth" });
            }
        }
        lastRenderedEquipmentCount = count;
    }

    let equipmentManualMode = false;
    let lastSelectedEquipment = "";

    // "Add to report" looks ready once the item has equipment plus an issue or details.
    function syncAddButtonState() {
        const picked = equipmentManualMode
            ? !!(equipmentManualInput && equipmentManualInput.value.trim())
            : !!(equipmentSelect && equipmentSelect.value);
        const described = !!getSelectedSuggestedIssue() || !!getAdditionalDetails();
        const ready = picked && described;

        [addEquipmentBtn, addManualEquipmentBtn].forEach(function (btn) {
            if (!btn) return;
            btn.classList.toggle("is-waiting", !ready);
            btn.setAttribute("aria-disabled", ready ? "false" : "true");
        });

        const hint = document.getElementById("rfAddHint");
        if (hint) {
            hint.textContent = picked
                ? "Pick a suggested issue or describe the problem."
                : "Pick the equipment first.";
            hint.classList.toggle("hidden", ready);
        }
    }

    ["input", "change", "click"].forEach(function (type) {
        document.addEventListener(type, function (event) {
            if (event.target.closest && event.target.closest("#reportModal")) {
                setTimeout(syncAddButtonState, 0);
            }
        });
    });

    /** @type {Array<{type: 'id'|'manual', id?: string, name: string, issue: string, details?: string, roomId?: string, roomLabel?: string, openReportTicket?: string, checks?: Array, photo?: File, photoUrl?: string, photoDataUrl?: string}>} */
    let selectedEquipmentItems = [];

    /** @type {Record<string, unknown>[]} */
    let roomEquipmentCache = [];

    const REPORT_DRAFT_KEY = "paayo_report_draft_v1";

    function dataUrlToFile(dataUrl, filename) {
        try {
            const parts = String(dataUrl || "").split(",");
            if (parts.length < 2) return null;
            const mimeMatch = parts[0].match(/:(.*?);/);
            const mime = (mimeMatch && mimeMatch[1]) || "image/jpeg";
            const binary = atob(parts[1]);
            const len = binary.length;
            const bytes = new Uint8Array(len);
            for (let i = 0; i < len; i++) bytes[i] = binary.charCodeAt(i);
            return new File([bytes], filename || "photo.jpg", { type: mime });
        } catch (_) {
            return null;
        }
    }

    function serializeReportDraftItem(item) {
        return {
            type: item.type === "manual" ? "manual" : "id",
            id: item.id ? String(item.id) : undefined,
            name: String(item.name || ""),
            issue: String(item.issue || ""),
            details: String(item.details || ""),
            checks: Array.isArray(item.checks) ? item.checks : [],
            openReportTicket: item.openReportTicket ? String(item.openReportTicket) : "",
            roomId: item.roomId ? String(item.roomId) : "",
            roomLabel: item.roomLabel ? String(item.roomLabel) : "",
            photoDataUrl: item.photoDataUrl ? String(item.photoDataUrl) : null,
        };
    }

    function persistReportDraft() {
        try {
            const roomSelect = document.getElementById("roomSelect");
            const payload = {
                items: selectedEquipmentItems.map(serializeReportDraftItem),
                roomId: roomSelect ? String(roomSelect.value || "") : "",
                savedAt: Date.now(),
            };
            sessionStorage.setItem(REPORT_DRAFT_KEY, JSON.stringify(payload));
        } catch (_) {
            /* quota / private mode */
        }
    }

    function clearReportDraft() {
        try {
            sessionStorage.removeItem(REPORT_DRAFT_KEY);
        } catch (_) {}
    }

    window.clearReportDraft = clearReportDraft;

    function restoreReportDraft() {
        let raw = null;
        try {
            raw = sessionStorage.getItem(REPORT_DRAFT_KEY);
        } catch (_) {
            return false;
        }
        if (!raw) return false;

        let payload = null;
        try {
            payload = JSON.parse(raw);
        } catch (_) {
            clearReportDraft();
            return false;
        }

        const items = Array.isArray(payload && payload.items) ? payload.items : [];
        revokeItemPhotos(selectedEquipmentItems);
        selectedEquipmentItems = items
            .filter(function (row) {
                return row && row.name;
            })
            .map(function (row, index) {
                const next = {
                    type: row.type === "manual" ? "manual" : "id",
                    name: String(row.name || ""),
                    issue: String(row.issue || ""),
                    details: String(row.details || ""),
                    checks: Array.isArray(row.checks) ? row.checks : [],
                    openReportTicket: row.openReportTicket ? String(row.openReportTicket) : "",
                    roomId: row.roomId ? String(row.roomId) : "",
                    roomLabel: row.roomLabel ? String(row.roomLabel) : "",
                };
                if (next.type === "id" && row.id) {
                    next.id = String(row.id);
                }
                if (row.photoDataUrl) {
                    next.photoDataUrl = String(row.photoDataUrl);
                    const file = dataUrlToFile(next.photoDataUrl, "draft-photo-" + index + ".jpg");
                    if (file) {
                        next.photo = file;
                        next.photoUrl = URL.createObjectURL(file);
                    }
                }
                return next;
            });

        const roomSelect = document.getElementById("roomSelect");
        const restoreRoomId = String((payload && payload.roomId) || "");
        if (roomSelect && restoreRoomId) {
            roomSelect.value = restoreRoomId;
            if (roomSelect.value !== restoreRoomId && selectedEquipmentItems.length) {
                const fromItem = selectedEquipmentItems
                    .map(function (item) {
                        return String(item.roomId || "");
                    })
                    .find(Boolean);
                if (fromItem) roomSelect.value = fromItem;
            }
            roomSelect.dispatchEvent(new Event("change", { bubbles: true }));
        }

        renderSelectedEquipment();
        syncAddButtonState();
        return selectedEquipmentItems.length > 0;
    }

    window.restoreReportDraft = restoreReportDraft;

    function getCurrentRoomMeta() {
        const roomSelect = document.getElementById("roomSelect");
        if (!roomSelect || !roomSelect.value) {
            return { roomId: "", roomLabel: "" };
        }
        const option = roomSelect.options[roomSelect.selectedIndex];
        return {
            roomId: String(roomSelect.value),
            roomLabel: option ? option.textContent.trim() : "",
        };
    }

    function syncEquipmentModeUi() {
        if (addEquipmentBtn) {
            addEquipmentBtn.classList.toggle("hidden", equipmentManualMode);
        }
        if (addManualEquipmentBtn) {
            addManualEquipmentBtn.classList.toggle("hidden", !equipmentManualMode);
        }
        syncAddButtonState();
        if (toggleEquipmentBtn) {
            toggleEquipmentBtn.classList.toggle("is-on", equipmentManualMode);
            toggleEquipmentBtn.setAttribute(
                "aria-pressed",
                equipmentManualMode ? "true" : "false",
            );
            const label = toggleEquipmentBtn.querySelector("span:not(.rf-unlisted-switch)");
            if (label) {
                label.textContent = equipmentManualMode
                    ? "Using manual equipment"
                    : "Equipment not listed";
            }
        }
        if (toggleEquipmentBtnDesktop) {
            toggleEquipmentBtnDesktop.textContent = equipmentManualMode
                ? "Back to equipment list"
                : "Equipment not listed?";
        }
    }

    function setEquipmentManualMode(nextManual) {
        document.getElementById("equipmentError")?.classList.add("hidden");
        setSelectTriggerBorder(equipmentSelect, "");
        if (equipmentManualInput) {
            equipmentManualInput.style.borderColor = "";
        }

        equipmentManualMode = !!nextManual;

        if (equipmentManualMode) {
            if (equipmentSelect?.value) {
                lastSelectedEquipment = equipmentSelect.value;
            }
            if (equipmentSelect) {
                equipmentSelect.value = "";
            }
            equipmentDropdown?.classList.add("hidden");
            equipmentManual?.classList.remove("hidden");
            document
                .querySelectorAll(".issue-btn")
                .forEach((btn) => btn.classList.remove("active"));
            loadGenericSuggestions();
        } else {
            equipmentDropdown?.classList.remove("hidden");
            equipmentManual?.classList.add("hidden");
            if (equipmentManualInput) {
                equipmentManualInput.value = "";
            }
            document
                .querySelectorAll(".issue-btn")
                .forEach((btn) => btn.classList.remove("active"));

            if (lastSelectedEquipment) {
                equipmentSelect.value = lastSelectedEquipment;
                fetch(`/get-suggestions/${lastSelectedEquipment}`)
                    .then((response) => response.json())
                    .then((data) => {
                        issueCarousel.innerHTML = "";
                        data.forEach((issue) => {
                            issueCarousel.innerHTML += `
                            <button
                                type="button"
                                class="issue-btn">
                                ${issue}
                            </button>
                        `;
                        });
                        bindIssueButtons();
                        updateIssueCount();
                    });
            } else if (selectedEquipmentItems.length === 0) {
                showIssuePlaceholder();
            }
        }

        syncEquipmentModeUi();
        syncDetailsAvailability();
    }

    function getAddedEquipmentIds() {
        return new Set(
            selectedEquipmentItems
                .filter(function (item) {
                    return item.type === "id" && item.id;
                })
                .map(function (item) {
                    return String(item.id);
                }),
        );
    }

    function rebuildEquipmentSelect() {
        if (!equipmentSelect) {
            return;
        }

        const addedIds = getAddedEquipmentIds();
        const previousValue = equipmentSelect.value;

        equipmentSelect.innerHTML = "";
        const placeholder = document.createElement("option");
        placeholder.value = "";
        placeholder.textContent = "Select Equipment";
        equipmentSelect.appendChild(placeholder);

        roomEquipmentCache.forEach(function (equipment) {
            const equipmentId = String(equipment.equipment_id || "");
            if (!equipmentId || addedIds.has(equipmentId)) {
                return;
            }

            const option = document.createElement("option");
            option.value = equipmentId;
            option.textContent = formatEquipmentPrimaryLabel(equipment);
            option.dataset.openReport = String(
                equipment.open_report_ticket_code || "",
            );
            option.dataset.searchText = formatEquipmentSearchText(equipment);
            option.dataset.categoryId = String(
                equipment.equipment_category_id || "",
            );
            option.dataset.categoryName = String(
                equipment.equipment_category_name || "",
            );
            option.dataset.computerSetId = String(
                equipment.computer_set_id || "",
            );
            option.dataset.computerSetTag = String(
                equipment.computer_set_tag || "",
            );
            option.dataset.computerSetName = String(
                equipment.computer_set_name || "",
            );
            option.dataset.computerSetRole = String(
                equipment.computer_set_member_role || "",
            );
            equipmentSelect.appendChild(option);
        });

        if (
            previousValue &&
            !addedIds.has(String(previousValue)) &&
            equipmentSelect.querySelector('option[value="' + previousValue + '"]')
        ) {
            equipmentSelect.value = previousValue;
        } else {
            equipmentSelect.value = "";
            lastSelectedEquipment = "";
        }

        syncEquipmentSelectTrigger();
    }

    function formatEquipmentPrimaryLabel(equipment) {
        const name = String(equipment.equipment_name || "Equipment").trim();
        const parts = [];

        const setTag = String(equipment.computer_set_tag || "").trim();
        const assetTag = String(equipment.equipment_asset_tag || "").trim();
        const serial = String(equipment.equipment_serial_number || "").trim();
        const brandModel = [equipment.equipment_brand_name, equipment.equipment_model]
            .map((part) => String(part || "").trim())
            .filter(Boolean)
            .join(" ");
        const zone = String(equipment.equipment_placement_zone || "").trim();
        // Holding = in-room UI staging; Unassigned = legacy. Skip both so items read as deployed.
        const zoneLower = zone.toLowerCase();
        const showZone = zone && zoneLower !== "holding" && zoneLower !== "unassigned";

        if (setTag) {
            parts.push(setTag);
        }

        if (assetTag) {
            parts.push("Tag: " + assetTag);
        } else if (serial) {
            parts.push("SN: " + serial);
        }

        if (brandModel) {
            parts.push(brandModel);
        }

        if (showZone) {
            parts.push(zone);
        }

        if (parts.length === 0 && equipment.equipment_id) {
            parts.push("#" + equipment.equipment_id);
        }

        return parts.length ? name + " · " + parts.join(" · ") : name;
    }

    function formatEquipmentSearchText(equipment) {
        const primary = formatEquipmentPrimaryLabel(equipment);
        const openReport = String(equipment.open_report_ticket_code || "").trim();
        const setTag = String(equipment.computer_set_tag || "").trim();
        const setName = String(equipment.computer_set_name || "").trim();
        const extras = [setTag, setName, openReport ? "Open report: " + openReport : ""]
            .filter(Boolean)
            .join(" ");

        return extras ? primary + " " + extras : primary;
    }

    function formatEquipmentLabel(equipment) {
        return formatEquipmentPrimaryLabel(equipment);
    }

    function equipmentOptionMarkup(option) {
        const main = option.textContent.trim();
        const openReport = option.dataset.openReport || "";

        if (!openReport) {
            return '<span class="rf-equipment-option-main"></span>';
        }

        return (
            '<span class="rf-equipment-option-main"></span>' +
            '<span class="rf-equipment-option-sub">Open report: ' +
            openReport +
            "</span>"
        );
    }

    function fillEquipmentOptionMarkup(container, option) {
        const mainEl = container.querySelector(".rf-equipment-option-main");
        if (mainEl) {
            mainEl.textContent = option.textContent.trim();
        }
    }

    function syncEquipmentSelectTrigger() {
        const trigger = document.querySelector(
            '.rf-picker-trigger[data-for="equipmentSelect"]',
        );
        if (!trigger) {
            return;
        }

        const option = equipmentSelect.options[equipmentSelect.selectedIndex];
        const label = option
            ? option.textContent.trim()
            : "Select Equipment";
        const labelEl = trigger.querySelector(".rf-picker-label");
        if (labelEl) {
            labelEl.textContent = label || "Select Equipment";
        }
        trigger.classList.toggle("is-placeholder", !equipmentSelect.value);
        syncDetailsAvailability();
    }

    function getSelectedSuggestedIssue() {
        const input = document.getElementById("suggestedIssueInput");
        return input ? input.value.trim() : "";
    }

    function getAdditionalDetails() {
        const input = document.getElementById("problemDescription");
        return input ? input.value.trim() : "";
    }

    function renderSelectedEquipment() {
        if (!selectedEquipmentList || !selectedEquipmentInputs) {
            return;
        }

        selectedEquipmentList.innerHTML = "";
        selectedEquipmentInputs.innerHTML = "";

        const multiRoom =
            new Set(
                selectedEquipmentItems
                    .map(function (item) {
                        return String(item.roomId || "");
                    })
                    .filter(Boolean),
            ).size > 1;

        selectedEquipmentItems.forEach((item, index) => {
            const uniqueness =
                item.type === "id"
                    ? item.name + (item.id ? " · ID #" + item.id : "")
                    : item.name + " (manual entry)";
            const tipLines = [uniqueness];
            if (item.roomLabel) {
                tipLines.push("Location: " + item.roomLabel);
            }
            tipLines.push("Issue: " + (item.issue || "Other"));
            if (item.details) {
                tipLines.push("Details: " + item.details);
            }
            const tipText = tipLines.join("\n");

            const row = document.createElement("div");
            row.className =
                "rf-eq-item flex items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2";
            row.setAttribute("tabindex", "0");
            row.setAttribute("aria-label", tipText.replace(/\n/g, ". "));
            const photoMarkup = item.photoUrl
                ? `
                    <button type="button" class="rf-eq-photo-thumb" data-view-photo="${index}" aria-label="View photo" title="View photo">
                        <img alt="" />
                    </button>
                    <button type="button" class="rf-eq-photo-remove" data-remove-photo="${index}" aria-label="Remove photo" title="Remove photo">&times;</button>
                `
                : `
                    <button type="button" class="rf-eq-photo-add" data-add-photo="${index}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                        Add photo
                    </button>
                `;
            row.innerHTML = `
                <div class="rf-eq-tip" data-eq-tip></div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-800" data-eq-name></p>
                    <p class="mt-0.5 truncate text-xs text-slate-500" data-eq-issue></p>
                    <p class="text-[10px] uppercase tracking-wide text-slate-400">${item.type === "manual" ? "Manual entry" : "Listed equipment"}</p>
                </div>
                <div class="rf-eq-photo">${photoMarkup}</div>
                <button
                    type="button"
                    data-remove-equipment="${index}"
                    class="shrink-0 rounded-lg bg-rose-50 px-2 py-1 text-[11px] font-bold text-rose-600 hover:bg-rose-100"
                >
                    Remove
                </button>
            `;
            if (item.photoUrl) {
                row.querySelector("[data-view-photo] img").src = item.photoUrl;
            }
            const tipEl = row.querySelector("[data-eq-tip]");
            tipEl.textContent = "";
            tipText.split("\n").forEach((line, lineIndex) => {
                if (lineIndex > 0) {
                    tipEl.appendChild(document.createElement("br"));
                }
                tipEl.appendChild(document.createTextNode(line));
            });
            row.querySelector("[data-eq-name]").textContent = item.name;
            row.querySelector("[data-eq-name]").setAttribute("title", item.name);
            row.querySelector("[data-eq-issue]").textContent =
                "Issue: " + (item.issue || "Other");
            if (item.details) {
                const detailsNote = document.createElement("p");
                detailsNote.className = "rf-eq-details mt-0.5 text-xs italic text-slate-600";
                detailsNote.textContent = "“" + item.details + "”";
                detailsNote.setAttribute("title", item.details);
                row.querySelector("[data-eq-issue]").after(detailsNote);
            }
            const triedChecks = (item.checks || []).filter((entry) => entry.answer === "not_fixed").length;
            if (triedChecks > 0) {
                const checksNote = document.createElement("p");
                checksNote.className = "mt-0.5 truncate text-[11px] text-slate-500";
                checksNote.textContent = "Tried " + triedChecks + " quick check" + (triedChecks === 1 ? "" : "s") + " first";
                row.querySelector(".min-w-0").appendChild(checksNote);
            }
            if (multiRoom && item.roomLabel) {
                const roomNote = document.createElement("p");
                roomNote.className = "mt-0.5 truncate text-[11px] text-slate-400";
                roomNote.textContent = item.roomLabel;
                roomNote.setAttribute("title", item.roomLabel);
                row.querySelector(".min-w-0").appendChild(roomNote);
            }
            if (item.openReportTicket) {
                const openNote = document.createElement("p");
                openNote.className = "mt-0.5 truncate text-xs font-medium text-amber-700";
                openNote.textContent =
                    "Already reported in " +
                    item.openReportTicket +
                    " — maintenance will see it flagged as priority.";
                row.querySelector(".min-w-0").appendChild(openNote);
            }
            selectedEquipmentList.appendChild(row);


            if (item.type === "id") {
                const idInput = document.createElement("input");
                idInput.type = "hidden";
                idInput.name = "report_equipment_ids[]";
                idInput.value = item.id;
                selectedEquipmentInputs.appendChild(idInput);

                const issueInput = document.createElement("input");
                issueInput.type = "hidden";
                issueInput.name = "report_equipment_issues[]";
                issueInput.value = item.issue || "";
                selectedEquipmentInputs.appendChild(issueInput);

                const detailsInput = document.createElement("input");
                detailsInput.type = "hidden";
                detailsInput.name = "report_equipment_details[]";
                detailsInput.value = item.details || "";
                selectedEquipmentInputs.appendChild(detailsInput);

                const checksInput = document.createElement("input");
                checksInput.type = "hidden";
                checksInput.name = "report_equipment_checks[]";
                checksInput.value = item.checks && item.checks.length ? JSON.stringify(item.checks) : "";
                selectedEquipmentInputs.appendChild(checksInput);
            } else {
                const nameInput = document.createElement("input");
                nameInput.type = "hidden";
                nameInput.name = "report_equipment_manuals[]";
                nameInput.value = item.name;
                selectedEquipmentInputs.appendChild(nameInput);

                const issueInput = document.createElement("input");
                issueInput.type = "hidden";
                issueInput.name = "report_equipment_manual_issues[]";
                issueInput.value = item.issue || "";
                selectedEquipmentInputs.appendChild(issueInput);

                const roomInput = document.createElement("input");
                roomInput.type = "hidden";
                roomInput.name = "report_equipment_manual_rooms[]";
                roomInput.value = item.roomId || "";
                selectedEquipmentInputs.appendChild(roomInput);

                const detailsInput = document.createElement("input");
                detailsInput.type = "hidden";
                detailsInput.name = "report_equipment_manual_details[]";
                detailsInput.value = item.details || "";
                selectedEquipmentInputs.appendChild(detailsInput);
            }
        });

        persistReportDraft();

        selectedEquipmentList
            .querySelectorAll("[data-remove-equipment]")
            .forEach((btn) => {
                btn.addEventListener("click", function () {
                    const idx = Number(this.getAttribute("data-remove-equipment"));
                    revokeItemPhotos(selectedEquipmentItems.splice(idx, 1));
                    renderSelectedEquipment();
                    rebuildEquipmentSelect();
                });
            });

        selectedEquipmentList.querySelectorAll("[data-add-photo]").forEach((btn) => {
            btn.addEventListener("click", function (e) {
                e.preventDefault();
                e.stopPropagation();
                window.openItemPhotoPicker?.(Number(this.getAttribute("data-add-photo")));
            });
        });

        selectedEquipmentList.querySelectorAll("[data-view-photo]").forEach((btn) => {
            btn.addEventListener("click", function (e) {
                e.preventDefault();
                e.stopPropagation();
                const item = selectedEquipmentItems[Number(this.getAttribute("data-view-photo"))];
                if (item && item.photoUrl) showProofImageFullscreen(item.photoUrl);
            });
        });

        selectedEquipmentList.querySelectorAll("[data-remove-photo]").forEach((btn) => {
            btn.addEventListener("click", function (e) {
                e.preventDefault();
                e.stopPropagation();
                removeItemPhoto(Number(this.getAttribute("data-remove-photo")));
            });
        });

        syncAsideEquipmentSummary();
    }

    async function fetchIssueChecks(equipmentId, issue) {
        try {
            const response = await fetch(
                `/get-issue-checks/${encodeURIComponent(equipmentId)}?issue=${encodeURIComponent(issue)}`,
                { headers: { Accept: "application/json" }, credentials: "same-origin" },
            );
            if (!response.ok) return [];
            const data = await response.json();
            return Array.isArray(data.checks) ? data.checks : [];
        } catch (_) {
            return [];
        }
    }

    /**
     * Walks the reporter through an issue's quick checks.
     * Resolves { status: "done" | "fixed" | "cancelled", answers: [{ id, answer }] }.
     */
    const reportQuickChecks = (function () {
        const overlay = document.getElementById("rfCheckOverlay");
        if (!overlay) return null;

        const els = {
            sub: document.getElementById("rfCheckSub"),
            step: document.getElementById("rfCheckStep"),
            progress: document.getElementById("rfCheckProgress"),
            count: document.getElementById("rfCheckCount"),
            question: document.getElementById("rfCheckQuestion"),
            hint: document.getElementById("rfCheckHint"),
            hintText: document.getElementById("rfCheckHintText"),
            actions: document.getElementById("rfCheckActions"),
            back: document.getElementById("rfCheckBack"),
            fixed: document.getElementById("rfCheckFixed"),
            danger: document.getElementById("rfCheckDanger"),
            dangerText: document.getElementById("rfCheckDangerText"),
        };
        let state = null;

        function csrfToken() {
            return document.querySelector('#reportForm input[name="_token"]')?.value || "";
        }

        function showPanel(panel) {
            els.step.hidden = panel !== "step";
            els.fixed.hidden = panel !== "fixed";
            els.danger.hidden = panel !== "danger";
        }

        function render() {
            const check = state.checks[state.index];
            showPanel("step");

            els.progress.innerHTML = state.checks
                .map((_, i) => `<span class="${i < state.index ? "is-done" : i === state.index ? "is-current" : ""}"></span>`)
                .join("");
            els.count.textContent = `Step ${state.index + 1} of ${state.checks.length}`;
            els.question.textContent = check.question;
            els.hint.hidden = !check.hint;
            els.hintText.textContent = check.hint || "";
            els.back.style.visibility = state.index === 0 ? "hidden" : "";

            const buttons = check.kind === "question"
                ? [["Yes", "yes", "is-plain"], ["No", "no", "is-plain"]]
                : [["That fixed it", "fixed", "is-fixed"], ["Still not working", "not_fixed", "is-primary"]];

            els.actions.innerHTML = "";
            buttons.forEach(([label, answer, tone]) => {
                const button = document.createElement("button");
                button.type = "button";
                button.className = "rf-check-btn " + tone;
                button.textContent = label;
                button.addEventListener("click", () => answer_(answer));
                els.actions.appendChild(button);
            });
            els.actions.querySelector("button:last-child")?.focus({ preventScroll: true });
        }

        function answer_(answer) {
            const check = state.checks[state.index];
            state.answers[state.index] = { id: check.id, answer: answer };

            if (answer === "fixed") {
                fetch("/reporter/self-fixed", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": csrfToken(),
                    },
                    credentials: "same-origin",
                    body: JSON.stringify({ equipment_id: state.equipmentId, check_id: check.id }),
                }).catch(() => {});
                showPanel("fixed");
                document.getElementById("rfCheckFixedDone").focus({ preventScroll: true });
                return;
            }

            if (check.safety && answer === "yes") {
                els.dangerText.textContent = (check.hint ? check.hint + " " : "")
                    + "Maintenance will get this report as Critical.";
                showPanel("danger");
                document.getElementById("rfCheckDangerAdd").focus({ preventScroll: true });
                return;
            }

            if (state.index + 1 < state.checks.length) {
                state.index += 1;
                render();
                return;
            }

            finish("done");
        }

        function finish(status) {
            if (!state) return;
            const answers = state.answers.slice(0, state.index + 1).filter(Boolean);
            const resolve = state.resolve;
            state = null;
            overlay.hidden = true;
            document.removeEventListener("keydown", onKeydown, true);
            resolve({ status: status, answers: status === "cancelled" ? [] : answers });
        }

        function onKeydown(e) {
            if (e.key === "Escape") {
                e.preventDefault();
                e.stopPropagation();
                finish("cancelled");
            }
        }

        document.getElementById("rfCheckClose").addEventListener("click", () => finish("cancelled"));
        document.getElementById("rfCheckFixedDone").addEventListener("click", () => finish("fixed"));
        document.getElementById("rfCheckDangerAdd").addEventListener("click", () => finish("done"));
        els.back.addEventListener("click", () => {
            if (!state || state.index === 0) return;
            state.index -= 1;
            render();
        });
        overlay.addEventListener("click", (e) => {
            if (e.target === overlay) finish("cancelled");
        });

        return {
            open(context) {
                return new Promise((resolve) => {
                    state = {
                        equipmentId: context.equipmentId,
                        checks: context.checks,
                        index: 0,
                        answers: [],
                        resolve: resolve,
                    };
                    els.sub.textContent = context.name + " · " + context.issue;
                    overlay.hidden = false;
                    document.addEventListener("keydown", onKeydown, true);
                    if (window.lucide) window.lucide.createIcons();
                    render();
                });
            },
        };
    })();

    function resetListedEquipmentPicker() {
        clearProblemDescription();
        renderSelectedEquipment();
        rebuildEquipmentSelect();
        lastSelectedEquipment = "";
        equipmentSelect.value = "";
        syncEquipmentSelectTrigger();
        clearSuggestedIssue();
        showIssuePlaceholder();
        syncAddButtonState();
    }

    let addingListedEquipment = false;

    async function addListedEquipment() {
        if (addingListedEquipment) return;

        const equipmentId = equipmentSelect.value;
        const option = equipmentSelect.options[equipmentSelect.selectedIndex];
        const equipmentName = option ? option.textContent.trim() : "";
        const selectedIssue = getSelectedSuggestedIssue();
        const details = getAdditionalDetails();
        const roomMeta = getCurrentRoomMeta();
        const roomSelect = document.getElementById("roomSelect");

        document.getElementById("equipmentError").classList.add("hidden");
        setSelectTriggerBorder(equipmentSelect, "");
        hideIssueOrDetailsErrors();

        if (!roomMeta.roomId) {
            document.getElementById("locationError")?.classList.remove("hidden");
            if (roomSelect) {
                setSelectTriggerBorder(roomSelect, "#dc2626");
            }
            return;
        }

        if (!equipmentId) {
            const err = document.getElementById("equipmentError");
            err.classList.remove("hidden");
            err.innerText = "Please select equipment, then a suggested issue or additional details, then Add to report.";
            setSelectTriggerBorder(equipmentSelect, "#dc2626");
            return;
        }

        if (!selectedIssue && !details) {
            const err = document.getElementById("equipmentError");
            err.classList.remove("hidden");
            err.innerText = "Please select a suggested issue or provide additional details before adding this equipment.";
            document.getElementById("issueError")?.classList.remove("hidden");
            return;
        }

        if (
            selectedEquipmentItems.some(
                (item) => item.type === "id" && String(item.id) === String(equipmentId),
            )
        ) {
            const err = document.getElementById("equipmentError");
            err.classList.remove("hidden");
            err.innerText = "That equipment is already added.";
            return;
        }

        const equipmentFromCache = roomEquipmentCache.find(function (equipment) {
            return String(equipment.equipment_id || "") === String(equipmentId);
        });
        const openReportTicket = equipmentFromCache?.open_report_ticket_code || "";
        const itemName = equipmentName || `Equipment #${equipmentId}`;

        let checkAnswers = [];
        if (selectedIssue && reportQuickChecks) {
            addingListedEquipment = true;
            try {
                const checks = await fetchIssueChecks(equipmentId, selectedIssue);
                if (checks.length) {
                    const outcome = await reportQuickChecks.open({
                        equipmentId: equipmentId,
                        name: itemName,
                        issue: selectedIssue,
                        checks: checks,
                    });
                    if (outcome.status === "cancelled") return;
                    if (outcome.status === "fixed") {
                        resetListedEquipmentPicker();
                        return;
                    }
                    checkAnswers = outcome.answers;
                }
            } finally {
                addingListedEquipment = false;
            }
        }

        selectedEquipmentItems.push({
            type: "id",
            id: String(equipmentId),
            name: itemName,
            issue: selectedIssue,
            details: details,
            checks: checkAnswers,
            openReportTicket: openReportTicket,
            roomId: roomMeta.roomId,
            roomLabel: roomMeta.roomLabel,
        });

        resetListedEquipmentPicker();
    }

    function addManualEquipment() {
        const name = equipmentManualInput.value.trim();
        const selectedIssue = getSelectedSuggestedIssue();
        const details = getAdditionalDetails();
        const roomMeta = getCurrentRoomMeta();
        const roomSelect = document.getElementById("roomSelect");

        document.getElementById("equipmentError").classList.add("hidden");
        equipmentManualInput.style.borderColor = "";
        hideIssueOrDetailsErrors();

        if (!roomMeta.roomId) {
            document.getElementById("locationError")?.classList.remove("hidden");
            if (roomSelect) {
                setSelectTriggerBorder(roomSelect, "#dc2626");
            }
            return;
        }

        if (!name) {
            const err = document.getElementById("equipmentError");
            err.classList.remove("hidden");
            err.innerText = "Please enter an equipment name.";
            equipmentManualInput.style.borderColor = "#dc2626";
            return;
        }

        if (!selectedIssue && !details) {
            const err = document.getElementById("equipmentError");
            err.classList.remove("hidden");
            err.innerText = "Please select a suggested issue or provide additional details before adding this equipment.";
            document.getElementById("issueError")?.classList.remove("hidden");
            return;
        }

        if (
            selectedEquipmentItems.some(
                (item) =>
                    item.type === "manual" &&
                    item.name.toLowerCase() === name.toLowerCase(),
            )
        ) {
            const err = document.getElementById("equipmentError");
            err.classList.remove("hidden");
            err.innerText = "That equipment name is already added.";
            return;
        }

        selectedEquipmentItems.push({
            type: "manual",
            name,
            issue: selectedIssue,
            details: details,
            roomId: roomMeta.roomId,
            roomLabel: roomMeta.roomLabel,
        });

        clearProblemDescription();
        renderSelectedEquipment();
        equipmentManualInput.value = "";
        syncDetailsAvailability();
        clearSuggestedIssue();
        loadGenericSuggestions();
        syncAddButtonState();
    }

    if (addEquipmentBtn) {
        addEquipmentBtn.addEventListener("click", addListedEquipment);
    }

    if (addManualEquipmentBtn) {
        addManualEquipmentBtn.addEventListener("click", addManualEquipment);
    }

    if (equipmentManualInput) {
        equipmentManualInput.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                addManualEquipment();
            }
        });
    }

    toggleEquipmentBtn?.addEventListener("click", function () {
        setEquipmentManualMode(!equipmentManualMode);
    });
    toggleEquipmentBtnDesktop?.addEventListener("click", function () {
        setEquipmentManualMode(!equipmentManualMode);
    });
    syncEquipmentModeUi();

    function issuePlaceholderHtml(message) {
        const text =
            message ||
            "Select equipment first to see suggested issues.";
        return `
            <div id="issuePlaceholder" class="issue-placeholder">
                <span class="issue-placeholder-icon" aria-hidden="true">
                    <i data-lucide="info" class="h-4 w-4"></i>
                </span>
                <span class="issue-placeholder-text">${text}</span>
            </div>
        `;
    }

    function refreshIssuePlaceholderIcons() {
        if (window.lucide && typeof window.lucide.createIcons === "function") {
            window.lucide.createIcons();
        }
    }

    function showIssuePlaceholder(message) {
        issueCarousel.innerHTML = issuePlaceholderHtml(message);
        refreshIssuePlaceholderIcons();
        updateIssueCount();
        toggleIssueControls();
    }

    function toggleIssueControls() {
        const controls = document.getElementById("issueControls");

        const issueCount = document.querySelectorAll(".issue-btn").length;

        if (issueCount > 0) {
            controls.classList.remove("hidden");
        } else {
            controls.classList.add("hidden");
        }
    }

    /*
|--------------------------------------------------------------------------
| GENERIC SUGGESTIONS
|--------------------------------------------------------------------------
*/

    function loadGenericSuggestions() {
        issueCarousel.innerHTML = `

        <button type="button" class="issue-btn">
            Not Functioning
        </button>

        <button type="button" class="issue-btn">
            Physical Damage
        </button>

        <button type="button" class="issue-btn">
            Missing Parts
        </button>

        <button type="button" class="issue-btn">
            Needs Inspection
        </button>

        <button type="button" class="issue-btn">
            Needs Replacement
        </button>

        <button type="button" class="issue-btn">
            Cannot Operate
        </button>

        <button type="button" class="issue-btn">
            Electrical Issue
        </button>

        <!-- Changed from "Issues" to "Issue" -->
        <button type="button" class="issue-btn">
            Connectivity Issue 
        </button>

        <button type="button" class="issue-btn">
            Power Failure
        </button>

        <button type="button" class="issue-btn">
            Malfunctioning Component
        </button>

    `;

        updateIssueCount();

        toggleIssueControls();

        bindIssueButtons();
    }

    // AUTO SUGGESTIOn

    function getIssueLabel(btn) {
        return Array.from(btn.childNodes)
            .filter(function (node) {
                return node.nodeType === Node.TEXT_NODE;
            })
            .map(function (node) {
                return node.textContent.trim();
            })
            .join(" ")
            .trim();
    }

    function syncIssueClearUi() {
        document.querySelectorAll(".issue-clear").forEach(function (el) {
            el.remove();
        });

        const active = document.querySelector(".issue-btn.active");
        const clearBtn = document.getElementById("clearIssueBtn");

        if (active) {
            const x = document.createElement("span");
            x.className = "issue-clear";
            x.setAttribute("role", "button");
            x.setAttribute("aria-label", "Clear suggested issue");
            x.textContent = "×";
            active.appendChild(x);
            if (clearBtn) clearBtn.classList.remove("hidden");
        } else if (clearBtn) {
            clearBtn.classList.add("hidden");
        }
    }

    function bindIssueButtons() {
        document.querySelectorAll(".issue-btn").forEach((btn) => {
            btn.addEventListener("click", function (e) {
                if (e.target.closest(".issue-clear")) {
                    e.preventDefault();
                    e.stopPropagation();
                    clearSuggestedIssue();
                    return;
                }

                const newIssue = getIssueLabel(this);

                document
                    .querySelectorAll(".issue-btn")
                    .forEach((b) => b.classList.remove("active"));

                this.classList.add("active");

                hideIssueOrDetailsErrors();

                selectedSuggestedIssue = newIssue;

                document.getElementById("suggestedIssueInput").value = newIssue;
                syncIssueClearUi();
            });
        });
    }

    function updateIssuePlaceholder() {
        const placeholder = document.getElementById("issuePlaceholder");
        if (!placeholder) {
            return;
        }

        const issueButtons = document.querySelectorAll(".issue-btn");

        if (issueButtons.length > 0) {
            placeholder.classList.add("hidden");
        } else {
            placeholder.classList.remove("hidden");
        }
    }

    function updateClearButtonVisibility() {
        if (descriptionTextarea.value.trim() !== "") {
            clearDescriptionWrapper.classList.remove("hidden");
        } else {
            clearDescriptionWrapper.classList.add("hidden");
        }
    }

    /*
|--------------------------------------------------------------------------
| CLEAR PROBLEM DESCRIPTION
|--------------------------------------------------------------------------
*/

    function clearProblemDescription() {
        descriptionTextarea.value = "";

        clearDescriptionWrapper.classList.add("hidden");
    }

    //SUGGESTED ISSUE
    function clearSuggestedIssue() {
        selectedSuggestedIssue = "";

        document.getElementById("suggestedIssueInput").value = "";

        document
            .querySelectorAll(".issue-btn")
            .forEach((btn) => btn.classList.remove("active"));

        syncIssueClearUi();
    }

    let reportFormIsLocked = false;

    // Details describe one equipment, so they only open once one is picked.
    function syncDetailsAvailability() {
        const details = document.getElementById("problemDescription");
        if (!details) return;

        const picked = equipmentManualMode
            ? !!(equipmentManualInput && equipmentManualInput.value.trim())
            : !!(equipmentSelect && equipmentSelect.value);
        const enabled = picked && !reportFormIsLocked;

        if (!enabled && details.value) {
            details.value = "";
            updateClearButtonVisibility();
        }

        details.disabled = !enabled;
        details.placeholder = enabled
            ? details.dataset.placeholderReady
            : details.dataset.placeholderIdle;
        details.style.opacity = enabled ? "" : "0.55";
        details.style.cursor = enabled ? "" : "not-allowed";

        const clearBtn = document.getElementById("clearDescriptionBtn");
        if (clearBtn) clearBtn.disabled = !enabled;
    }

    // =====================================================
    // LOCK OR UNLOCK REPORT FORM HERE
    // EMPLOYEE ID AND CANCEL / CLOSE BUTTONS STAY USABLE
    // =====================================================

    function setReportFormLocked(locked) {
        reportFormIsLocked = !!locked;

        // =====================================================
        // LOCATION
        // =====================================================

        const roomSelect =
            document.getElementById("roomSelect");


        // =====================================================
        // EQUIPMENT
        // =====================================================

        const equipmentSelect =
            document.getElementById("equipmentSelect");

        const equipmentManualInput =
            document.getElementById("equipmentManualInput");

        const toggleEquipmentBtn =
            document.getElementById("toggleEquipmentInput");


        // =====================================================
        // ADDITIONAL DETAILS
        // =====================================================

        const problemDescription =
            document.getElementById("problemDescription");

        const clearDescriptionBtn =
            document.getElementById("clearDescriptionBtn");


        // =====================================================
        // SUGGESTED ISSUE CONTROLS
        // =====================================================

        const issueCarousel =
            document.getElementById("issueCarousel");

        const issueControls =
            document.getElementById("issueControls");


        // =====================================================
        // SUBMIT BUTTON
        // =====================================================

        const submitReportBtn =
            document.getElementById("submitReportBtn");


        // =====================================================
        // DISABLE NORMAL FORM INPUTS HERE
        // =====================================================

        roomSelect.disabled = locked;

        equipmentSelect.disabled = locked;

        equipmentManualInput.disabled = locked;

        if (toggleEquipmentBtn) {
            toggleEquipmentBtn.disabled = locked;
        }
        if (toggleEquipmentBtnDesktop) {
            toggleEquipmentBtnDesktop.disabled = locked;
        }

        const proofCameraInput = document.getElementById("proofImageCameraInput");
        const proofGalleryInput = document.getElementById("proofImageGalleryInput");
        if (proofCameraInput) proofCameraInput.disabled = locked;
        if (proofGalleryInput) proofGalleryInput.disabled = locked;

        submitReportBtn.disabled = locked;


        // =====================================================
        // LOCK SUGGESTED ISSUE CAROUSEL HERE
        // THIS BLOCKS CLICKING AND DRAGGING
        // =====================================================

        issueCarousel.style.pointerEvents =
            locked ? "none" : "";

        issueCarousel.style.opacity =
            locked ? "0.45" : "1";


        // =====================================================
        // LOCK ISSUE CONTROL BUTTONS HERE
        // LEFT, RIGHT, CLEAR
        // =====================================================

        issueControls.style.pointerEvents =
            locked ? "none" : "";

        issueControls.style.opacity =
            locked ? "0.45" : "1";


        // =====================================================
        // LOCK PER-EQUIPMENT PHOTO BUTTONS HERE
        // =====================================================

        document
            .querySelectorAll("#selectedEquipmentList .rf-eq-photo")
            .forEach(function (slot) {
                slot.style.pointerEvents = locked ? "none" : "";
                slot.style.opacity = locked ? "0.45" : "1";
            });


        // =====================================================
        // DISABLED VISUAL STATE HERE
        // =====================================================

        const disabledElements = [

            roomSelect,

            equipmentSelect,

            equipmentManualInput,

            toggleEquipmentBtn,

            toggleEquipmentBtnDesktop,

        ];


        disabledElements.forEach(function (element) {

            if (!element) {
                return;
            }


            if (locked) {

                element.style.opacity = "0.55";

                element.style.cursor = "not-allowed";

            } else {

                element.style.opacity = "";

                element.style.cursor = "";

            }

        });


        // =====================================================
        // SUBMIT BUTTON VISUAL STATE HERE
        // =====================================================

        submitReportBtn.style.opacity =
            locked ? "0.45" : "1";

        submitReportBtn.style.cursor =
            locked ? "not-allowed" : "pointer";

        document.querySelectorAll(".rf-picker-trigger").forEach(function (btn) {
            const select = document.getElementById(btn.dataset.for);
            btn.disabled = locked || !select || select.disabled;
        });

        syncDetailsAvailability();
    }

    function setSelectTriggerBorder(select, color) {
        if (!select) return;
        select.style.borderColor = color || "";
        const trigger = document.querySelector(
            '.rf-picker-trigger[data-for="' + select.id + '"]',
        );
        if (trigger) {
            trigger.style.borderColor = color || "";
        }
    }

    function enhanceModernSelects() {
        const overlay = document.getElementById("rfPickerOverlay");
        const titleEl = document.getElementById("rfPickerTitle");
        const hintEl = document.getElementById("rfPickerHint");
        const searchEl = document.getElementById("rfPickerSearch");
        const categoriesEl = document.getElementById("rfPickerCategories");
        const listEl = document.getElementById("rfPickerList");
        const closeEl = document.getElementById("rfPickerClose");
        const dismissEl = document.getElementById("rfPickerDismiss");
        const dropdownMenu = document.getElementById("rfDropdownMenu");
        const dropdownList = document.getElementById("rfDropdownList");
        const dropdownSearchWrap = document.getElementById("rfDropdownSearchWrap");
        const dropdownSearchEl = document.getElementById("rfDropdownSearch");
        const optionTip = document.getElementById("rfOptionTip");
        let activeCategoryFilter = "";
        let activeFilterMode = "category";
        if (!overlay || !dropdownMenu || !dropdownList) return;

        // Mount outside #reportModal so overflow:hidden and transforms don't clip fixed pickers.
        [overlay, dropdownMenu, optionTip].forEach(function (el) {
            if (el && el.parentElement !== document.body) {
                document.body.appendChild(el);
            }
        });

        const ITEM_HEIGHT = 44;
        const VISIBLE_ITEMS = 5;
        let activeSelect = null;

        function hideOptionTip() {
            if (!optionTip) return;
            optionTip.classList.remove("is-visible");
            optionTip.hidden = true;
            optionTip.textContent = "";
        }

        function showOptionTip(anchor, text) {
            if (!optionTip || !anchor || !text) return;

            optionTip.hidden = false;
            optionTip.textContent = text;
            optionTip.classList.add("is-visible");

            const rect = anchor.getBoundingClientRect();
            const tipRect = optionTip.getBoundingClientRect();
            let left = rect.left;
            let top = rect.top - tipRect.height - 8;

            if (top < 8) {
                top = rect.bottom + 8;
            }

            if (left + tipRect.width > window.innerWidth - 8) {
                left = Math.max(8, window.innerWidth - tipRect.width - 8);
            }

            if (left < 8) {
                left = 8;
            }

            optionTip.style.left = left + "px";
            optionTip.style.top = top + "px";
        }

        function bindUniquenessTip(item, text) {
            // Prefer custom tip over native title (avoids double tooltips).
            item.removeAttribute("title");
            item.setAttribute("aria-label", text);

            item.addEventListener("mouseenter", function () {
                showOptionTip(item, text);
            });

            item.addEventListener("mouseleave", hideOptionTip);
            item.addEventListener("focus", function () {
                showOptionTip(item, text);
            });
            item.addEventListener("blur", hideOptionTip);
        }

        function selectIsEquipment(select) {
            return select && select.id === "equipmentSelect";
        }

        function isMobilePicker() {
            return window.matchMedia("(max-width: 767px)").matches;
        }

        function placeholderText(select) {
            const first = select.querySelector('option[value=""]');
            return first ? first.textContent.trim() : "Select";
        }

        function selectedLabel(select) {
            const option = select.options[select.selectedIndex];
            if (!option || option.value === "") {
                return placeholderText(select);
            }
            return option.textContent.trim();
        }

        function triggerFor(select) {
            return document.querySelector('.rf-picker-trigger[data-for="' + select.id + '"]');
        }

        function syncTrigger(select) {
            const trigger = triggerFor(select);
            if (!trigger) return;
            const label = selectedLabel(select);
            trigger.querySelector(".rf-picker-label").textContent = label;
            trigger.classList.toggle("is-placeholder", !select.value);
            trigger.disabled = select.disabled;
        }

        function setTriggerOpen(select, open) {
            const trigger = select ? triggerFor(select) : null;
            if (trigger) {
                trigger.classList.toggle("is-open", open);
                trigger.setAttribute("aria-expanded", open ? "true" : "false");
            }
        }

        function closeDropdown() {
            dropdownMenu.classList.remove("is-open");
            dropdownMenu.hidden = true;
            dropdownList.innerHTML = "";
            if (dropdownSearchWrap) dropdownSearchWrap.hidden = true;
            if (dropdownSearchEl) dropdownSearchEl.value = "";
            setTriggerOpen(activeSelect, false);
        }

        function closeSheet() {
            if (activeSelect) {
                setTriggerOpen(activeSelect, false);
            }
            overlay.classList.remove("is-open");
            overlay.hidden = true;
            if (searchEl) searchEl.value = "";
            activeCategoryFilter = "";
            activeFilterMode = "category";
            if (hintEl) {
                hintEl.hidden = true;
                hintEl.textContent = "";
            }
            if (categoriesEl) {
                categoriesEl.innerHTML = "";
                categoriesEl.hidden = true;
            }
        }

        function closePicker() {
            closeDropdown();
            closeSheet();
            hideOptionTip();
            activeSelect = null;
        }

        function equipmentCategoryShortLabel(name) {
            const n = String(name || "").trim().toLowerCase();
            if (!n) return "Other";
            if (n.includes("audio") || n === "ave") return "AVE";
            if (n.includes("computer") || n === "ce") return "CE";
            if (n.includes("furniture")) return "Furniture";
            return String(name).trim();
        }

        function collectEquipmentCategories(select) {
            const map = new Map();
            Array.from(select.options).forEach(function (option) {
                if (!option.value) return;
                const id = String(option.dataset.categoryId || "").trim();
                const name = String(option.dataset.categoryName || "").trim();
                if (!id && !name) return;
                const key = id || name;
                if (!map.has(key)) {
                    map.set(key, {
                        id: id,
                        name: name || "Other",
                        label: equipmentCategoryShortLabel(name),
                    });
                }
            });
            return Array.from(map.values()).sort(function (a, b) {
                return a.label.localeCompare(b.label);
            });
        }

        function collectComputerSets(select) {
            const map = new Map();
            let hasUnassigned = false;
            Array.from(select.options).forEach(function (option) {
                if (!option.value) return;
                const setId = String(option.dataset.computerSetId || "").trim();
                const setTag = String(option.dataset.computerSetTag || "").trim();
                if (!setId || !setTag) {
                    hasUnassigned = true;
                    return;
                }
                if (!map.has(setId)) {
                    map.set(setId, {
                        id: setId,
                        tag: setTag,
                        name: String(option.dataset.computerSetName || "").trim(),
                        label: setTag,
                    });
                }
            });
            const sets = Array.from(map.values()).sort(function (a, b) {
                return a.tag.localeCompare(b.tag);
            });
            return { sets: sets, hasUnassigned: hasUnassigned };
        }

        function renderCategoryChips(select) {
            if (!categoriesEl) return;
            categoriesEl.innerHTML = "";
            activeCategoryFilter = "";
            activeFilterMode = "category";

            if (!selectIsEquipment(select)) {
                categoriesEl.hidden = true;
                return;
            }

            const setInfo = collectComputerSets(select);
            if (setInfo.sets.length > 0) {
                activeFilterMode = "computer_set";
                categoriesEl.hidden = false;

                function makeSetChip(label, value) {
                    const chip = document.createElement("button");
                    chip.type = "button";
                    chip.className = "rf-picker-chip" + (activeCategoryFilter === value ? " is-active" : "");
                    chip.textContent = label;
                    chip.dataset.category = value;
                    chip.title = label;
                    chip.addEventListener("click", function () {
                        activeCategoryFilter = value;
                        categoriesEl.querySelectorAll(".rf-picker-chip").forEach(function (btn) {
                            btn.classList.toggle("is-active", btn.dataset.category === value);
                        });
                        if (hintEl) {
                            if (value && value !== "__other__") {
                                hintEl.hidden = false;
                                hintEl.textContent = "Showing equipment inside " + label;
                            } else if (value === "__other__") {
                                hintEl.hidden = false;
                                hintEl.textContent = "Equipment not in a computer set";
                            } else {
                                hintEl.hidden = true;
                                hintEl.textContent = "";
                            }
                        }
                        renderSheetList(select, searchEl ? searchEl.value : "");
                    });
                    return chip;
                }

                categoriesEl.appendChild(makeSetChip("All", ""));
                setInfo.sets.forEach(function (set) {
                    categoriesEl.appendChild(makeSetChip(set.label, "set:" + set.id));
                });
                if (setInfo.hasUnassigned) {
                    categoriesEl.appendChild(makeSetChip("Other", "__other__"));
                }
                const allChip = categoriesEl.querySelector('.rf-picker-chip[data-category=""]');
                if (allChip) allChip.classList.add("is-active");
                return;
            }

            const categories = collectEquipmentCategories(select);
            if (categories.length < 2) {
                categoriesEl.hidden = true;
                return;
            }

            categoriesEl.hidden = false;

            function makeChip(label, value) {
                const chip = document.createElement("button");
                chip.type = "button";
                chip.className = "rf-picker-chip" + (activeCategoryFilter === value ? " is-active" : "");
                chip.textContent = label;
                chip.dataset.category = value;
                chip.addEventListener("click", function () {
                    activeCategoryFilter = value;
                    categoriesEl.querySelectorAll(".rf-picker-chip").forEach(function (btn) {
                        btn.classList.toggle("is-active", btn.dataset.category === value);
                    });
                    renderSheetList(select, searchEl ? searchEl.value : "");
                });
                return chip;
            }

            categoriesEl.appendChild(makeChip("All", ""));
            categories.forEach(function (cat) {
                categoriesEl.appendChild(makeChip(cat.label, cat.id || cat.name));
            });
            const allChip = categoriesEl.querySelector('.rf-picker-chip[data-category=""]');
            if (allChip) allChip.classList.add("is-active");
        }

        function optionList(select, query) {
            const q = (query || "").trim().toLowerCase();
            return Array.from(select.options).filter(function (option) {
                if (option.value === "") return false;
                if (selectIsEquipment(select) && activeCategoryFilter) {
                    if (activeFilterMode === "computer_set") {
                        const setId = String(option.dataset.computerSetId || "").trim();
                        if (activeCategoryFilter === "__other__") {
                            if (setId) return false;
                        } else if (activeCategoryFilter.indexOf("set:") === 0) {
                            const want = activeCategoryFilter.slice(4);
                            if (setId !== want) return false;
                        }
                    } else {
                        const catId = String(option.dataset.categoryId || "").trim();
                        const catName = String(option.dataset.categoryName || "").trim();
                        if (catId !== activeCategoryFilter && catName !== activeCategoryFilter) {
                            return false;
                        }
                    }
                }
                const text = (
                    option.dataset.searchText || option.textContent
                ).trim();
                return !q || text.toLowerCase().includes(q);
            });
        }

        function dropdownItemHeight(select) {
            return selectIsEquipment(select) ? 62 : ITEM_HEIGHT;
        }

        function renderEquipmentPickerItem(item, option) {
            item.className += " rf-dropdown-item--equipment";
            item.innerHTML = equipmentOptionMarkup(option);
            fillEquipmentOptionMarkup(item, option);
        }

        function renderEquipmentSheetItem(item, option) {
            item.className += " rf-picker-item--equipment";
            item.innerHTML = equipmentOptionMarkup(option);
            fillEquipmentOptionMarkup(item, option);
        }

        function bindOptionClick(item, select, option, closeAfter) {
            item.addEventListener("click", function () {
                select.value = option.value;
                select.dispatchEvent(new Event("change", { bubbles: true }));
                syncTrigger(select);
                if (closeAfter) closePicker();
            });
        }

        function renderSheetList(select, query) {
            const options = optionList(select, query);
            listEl.innerHTML = "";
            hideOptionTip();

            options.forEach(function (option) {
                const text = option.textContent.trim();
                const item = document.createElement("button");
                item.type = "button";
                item.className = "rf-picker-item" + (option.value === select.value ? " is-active" : "");
                if (selectIsEquipment(select)) {
                    renderEquipmentSheetItem(item, option);
                } else {
                    item.innerHTML = "<span></span>";
                    item.querySelector("span").textContent = text;
                }
                if (selectIsEquipment(select)) {
                    item.dataset.showTip = "1";
                    bindUniquenessTip(item, text);
                }
                bindOptionClick(item, select, option, true);
                listEl.appendChild(item);
            });

            if (options.length === 0) {
                let emptyMsg = "No matches. Try another search.";
                if (selectIsEquipment(select) && activeCategoryFilter && !(query || "").trim()) {
                    emptyMsg = activeFilterMode === "computer_set"
                        ? (activeCategoryFilter === "__other__"
                            ? "No equipment outside a computer set."
                            : "No equipment inside this computer set.")
                        : "No equipment in this category.";
                }
                listEl.innerHTML = '<div class="rf-picker-empty">' + emptyMsg + "</div>";
            }

            if (window.lucide) lucide.createIcons();
        }

        function renderDropdownList(select, query) {
            const options = optionList(select, query);
            dropdownList.innerHTML = "";
            hideOptionTip();

            options.forEach(function (option) {
                const text = option.textContent.trim();
                const item = document.createElement("button");
                item.type = "button";
                item.className = "rf-dropdown-item" + (option.value === select.value ? " is-active" : "");
                if (selectIsEquipment(select)) {
                    renderEquipmentPickerItem(item, option);
                } else {
                    item.textContent = text;
                }
                if (selectIsEquipment(select)) {
                    item.dataset.showTip = "1";
                    bindUniquenessTip(item, text);
                } else {
                    item.title = text;
                }
                bindOptionClick(item, select, option, true);
                dropdownList.appendChild(item);
            });

            if (options.length === 0) {
                dropdownList.innerHTML = '<div class="rf-dropdown-empty">' +
                    ((query || "").trim()
                        ? "No matches. Try another search."
                        : "No options available.") +
                    "</div>";
            }
        }

        function dropdownSearchEnabled(select) {
            return !!(select && select.dataset.pickerSearch);
        }

        function positionDropdown(trigger) {
            const rect = trigger.getBoundingClientRect();
            const select = activeSelect;
            const itemHeight = select ? dropdownItemHeight(select) : ITEM_HEIGHT;
            const searchHeight = dropdownSearchWrap && !dropdownSearchWrap.hidden ? 46 : 0;
            const maxHeight = itemHeight * VISIBLE_ITEMS;
            const spaceBelow = window.innerHeight - rect.bottom - 10;
            const spaceAbove = rect.top - 10;
            const openUp = spaceBelow < Math.min(maxHeight, itemHeight * 3) + searchHeight && spaceAbove > spaceBelow;
            const available = Math.max(itemHeight, (openUp ? spaceAbove : spaceBelow) - searchHeight);

            dropdownMenu.style.width = rect.width + "px";
            dropdownMenu.style.left = Math.max(8, rect.left) + "px";
            dropdownList.style.maxHeight = Math.min(maxHeight, available) + "px";

            if (openUp) {
                dropdownMenu.style.top = "auto";
                dropdownMenu.style.bottom = (window.innerHeight - rect.top + 6) + "px";
            } else {
                dropdownMenu.style.bottom = "auto";
                dropdownMenu.style.top = (rect.bottom + 6) + "px";
            }
        }

        function openSheet(select) {
            activeSelect = select;
            titleEl.textContent = select.dataset.pickerTitle || placeholderText(select);
            if (hintEl) {
                if (selectIsEquipment(select)) {
                    hintEl.textContent = "Hold an item to see full details";
                    hintEl.hidden = false;
                } else {
                    hintEl.textContent = "";
                    hintEl.hidden = true;
                }
            }
            searchEl.placeholder = select.dataset.pickerSearch || "Search";
            searchEl.value = "";
            overlay.hidden = false;
            overlay.classList.add("is-open");
            setTriggerOpen(select, true);
            renderCategoryChips(select);
            renderSheetList(select, "");
            if (listEl) listEl.scrollTop = 0;
            if (window.lucide) lucide.createIcons();
            // Avoid auto-focus on mobile — keyboard resize was instantly closing the sheet.
            if (!isMobilePicker()) {
                setTimeout(function () { searchEl.focus({ preventScroll: true }); }, 50);
            }
        }

        function openDropdown(select) {
            const trigger = triggerFor(select);
            if (!trigger) return;
            activeSelect = select;
            setTriggerOpen(select, true);

            if (dropdownSearchWrap && dropdownSearchEl) {
                const showSearch = dropdownSearchEnabled(select);
                dropdownSearchWrap.hidden = !showSearch;
                dropdownSearchEl.placeholder = select.dataset.pickerSearch || "Search";
                dropdownSearchEl.value = "";
            }

            renderDropdownList(select, "");
            dropdownMenu.hidden = false;
            dropdownMenu.classList.add("is-open");
            positionDropdown(trigger);

            if (dropdownSearchEnabled(select) && dropdownSearchEl) {
                setTimeout(function () {
                    dropdownSearchEl.focus({ preventScroll: true });
                }, 30);
            }

            if (window.lucide) lucide.createIcons();

            const active = dropdownList.querySelector(".is-active");
            if (active) {
                active.scrollIntoView({ block: "nearest", behavior: "smooth" });
            }
        }

        if (dropdownList) {
            dropdownList.addEventListener("scroll", hideOptionTip, { passive: true });
        }

        function openPicker(select) {
            if (select.disabled) return;
            const alreadyOpen = activeSelect === select && (
                dropdownMenu.classList.contains("is-open") || overlay.classList.contains("is-open")
            );
            closePicker();
            if (alreadyOpen) return;

            if (isMobilePicker()) {
                openSheet(select);
            } else {
                openDropdown(select);
            }
        }

        [document.getElementById("roomSelect"), document.getElementById("equipmentSelect")].forEach(function (select) {
            if (!select) return;
            const wrap = select.closest(".rf-select-wrap");
            if (!wrap || wrap.querySelector(".rf-picker-trigger")) return;

            wrap.classList.add("rf-picker-ready");
            const trigger = document.createElement("button");
            trigger.type = "button";
            trigger.className = "rf-picker-trigger is-placeholder";
            trigger.dataset.for = select.id;
            trigger.setAttribute("aria-haspopup", "listbox");
            trigger.setAttribute("aria-expanded", "false");
            trigger.innerHTML = '<span class="rf-picker-label"></span><i data-lucide="chevron-down"></i>';
            wrap.appendChild(trigger);
            syncTrigger(select);

            trigger.addEventListener("click", function (e) {
                e.preventDefault();
                e.stopPropagation();
                openPicker(select);
            });

            new MutationObserver(function () {
                syncTrigger(select);
                if (activeSelect === select && dropdownMenu.classList.contains("is-open")) {
                    renderDropdownList(select, dropdownSearchEl ? dropdownSearchEl.value : "");
                    positionDropdown(trigger);
                }
            }).observe(select, { childList: true, subtree: true, attributes: true });
        });

        searchEl.addEventListener("input", function () {
            if (activeSelect) renderSheetList(activeSelect, searchEl.value);
        });

        if (dropdownSearchEl) {
            dropdownSearchEl.addEventListener("input", function () {
                if (activeSelect && dropdownMenu.classList.contains("is-open")) {
                    renderDropdownList(activeSelect, dropdownSearchEl.value);
                }
            });

            dropdownSearchEl.addEventListener("keydown", function (e) {
                if (e.key === "Escape") {
                    e.stopPropagation();
                    closePicker();
                }
            });
        }

        closeEl.addEventListener("click", function (e) {
            e.stopPropagation();
            closePicker();
        });

        if (dismissEl) {
            dismissEl.addEventListener("click", function (e) {
                e.stopPropagation();
                closePicker();
            });
        }

        overlay.addEventListener("click", function (e) {
            if (e.target === overlay) closePicker();
        });

        overlay.querySelector(".rf-picker-sheet")?.addEventListener("click", function (e) {
            e.stopPropagation();
        });

        document.addEventListener("click", function (e) {
            if (!dropdownMenu.classList.contains("is-open")) return;
            if (dropdownMenu.contains(e.target)) return;
            if (e.target.closest(".rf-picker-trigger")) return;
            closePicker();
        });

        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape") closePicker();
        });

        window.addEventListener("resize", function () {
            if (overlay.classList.contains("is-open")) {
                return;
            }
            if (dropdownMenu.classList.contains("is-open") && activeSelect) {
                const trigger = triggerFor(activeSelect);
                if (trigger) positionDropdown(trigger);
                return;
            }
            closePicker();
        });

        document.querySelectorAll(
            ".report-form-scroll, .report-form-aside-body, .report-form-shell, #reportModal .report-modal-wrap"
        ).forEach(function (scroller) {
            scroller.addEventListener("scroll", function () {
                if (overlay.classList.contains("is-open")) return;
                closePicker();
            }, { passive: true });
        });

        if (window.lucide) lucide.createIcons();

        window.closeReportPickers = closePicker;

        const reportModalEl = document.getElementById("reportModal");
        if (reportModalEl) {
            new MutationObserver(function () {
                if (reportModalEl.classList.contains("hidden")) {
                    closePicker();
                }
            }).observe(reportModalEl, { attributes: true, attributeFilter: ["class"] });
        }
    }

    /* ─────────────────────────────────────────────────────────
   REPORT SUBMISSION INTELLIGENCE LAYER
───────────────────────────────────────────────────────── */
    document.addEventListener("DOMContentLoaded", function () {
        const employeeInput = document.getElementById("employeeIdInput");

        const reporterBox = document.getElementById("reporterInfoBox");

        const employeeError = document.getElementById("employeeError");

        const roomSelect = document.getElementById("roomSelect");

        const equipSelect = document.getElementById("equipmentSelect");

        const equipmentManualInput = document.getElementById(
            "equipmentManualInput",
        );

        enhanceModernSelects();

        /*
    |--------------------------------------------------------------------------
    | CLEAR EMPLOYEE ERROR WHILE TYPING
    |--------------------------------------------------------------------------
    */
        employeeInput.addEventListener("input", function () {
            employeeError.style.display = "none";
            employeeInput.classList.remove("is-error");
            this.style.borderColor = "";
        });

        /*
    |--------------------------------------------------------------------------
    | CLEAR LOCATION ERROR
    |--------------------------------------------------------------------------
    */
        roomSelect.addEventListener("change", function () {
            setSelectTriggerBorder(this, "");

            document.getElementById("locationError").classList.add("hidden");
        });

        /*
    |--------------------------------------------------------------------------
    | CLEAR EQUIPMENT ERROR
    |--------------------------------------------------------------------------
    */
        equipSelect.addEventListener("change", function () {
            setSelectTriggerBorder(this, "");

            document.getElementById("equipmentError").classList.add("hidden");
            syncDetailsAvailability();
        });

        /*
    |--------------------------------------------------------------------------
    | CLEAR MANUAL EQUIPMENT ERROR
    |--------------------------------------------------------------------------
    */
        if (equipmentManualInput) {
            equipmentManualInput.addEventListener("input", function () {
                this.style.borderColor = "";

                document
                    .getElementById("equipmentError")
                    .classList.add("hidden");
                syncDetailsAvailability();
            });
        }

        /*
    |--------------------------------------------------------------------------
    | CLEAR SUGGESTED ISSUE ERROR
    |--------------------------------------------------------------------------
    */
        document.addEventListener("click", function (e) {
            if (e.target.classList.contains("issue-btn")) {
                hideIssueOrDetailsErrors();
            }
        });

        // =====================================================
        // EMPLOYEE LIVE LOOKUP
        // CHECK ACTIVE, INACTIVE, OR UNKNOWN REPORTER
        // =====================================================

        const inactiveReporterBox =
            document.getElementById("inactiveReporterBox");

        const pendingReporterBox =
            document.getElementById("pendingReporterBox");

        const submitReportBtn =
            document.getElementById("submitReportBtn");


        function setReporterCard(employeeId, name) {
            const card = document.getElementById("rfReporterCard");
            const nameEl = document.getElementById("rfReporterCardName");
            const subEl = document.getElementById("rfReporterCardSub");
            if (!card || !nameEl || !subEl) return;

            const fullName = String(name || "").trim();
            card.dataset.employeeId = employeeId;
            card.dataset.name = fullName;
            nameEl.textContent = fullName || employeeId;
            subEl.textContent = fullName ? employeeId + " · Verified with PIN" : "Verified with PIN";
        }

        // =====================================================
        // EMPLOYEE LIVE LOOKUP
        // ONLY INACTIVE REPORTERS LOCK THE FORM
        // =====================================================

        employeeInput.addEventListener("input", function () {

            const upper = this.value.toUpperCase();
            if (this.value !== upper) {
                const start = this.selectionStart;
                const end = this.selectionEnd;
                this.value = upper;
                try {
                    this.setSelectionRange(start, end);
                } catch (_) {}
            }

            const id = this.value.trim();

            const card = document.getElementById("rfReporterCard");
            if (card && card.dataset.employeeId !== id) {
                setReporterCard(id, "");
            }


            // =====================================================
            // RESET CURRENT REPORTER STATE HERE
            // =====================================================

            reporterVerified = false;

            reporterBox.classList.add("hidden");

            inactiveReporterBox.classList.add("hidden");

            if (pendingReporterBox) {
                pendingReporterBox.classList.add("hidden");
            }

            employeeError.style.display = "none";

            clearAssignedEquipment();


            // =====================================================
            // IMPORTANT
            // KEEP FORM UNLOCKED BY DEFAULT
            // =====================================================

            setReportFormLocked(false);


            // =====================================================
            // EMPTY OR INCOMPLETE EMPLOYEE ID
            // KEEP FORM UNLOCKED
            // =====================================================

            if (id.length < 8) {

                return;

            }


            // =====================================================
            // LOOKUP REPORTER HERE
            // =====================================================

            fetch(`/get-reporter/${id}`)

                .then((response) => response.json())

                .then((data) => {


                    // =====================================================
                    // REPORTER DOES NOT EXIST
                    // KEEP FORM UNLOCKED
                    // =====================================================

                    if (!data || !data.reporter_full_name) {

                        reporterVerified = false;

                        reporterBox.classList.add("hidden");

                        inactiveReporterBox.classList.add("hidden");

                        if (pendingReporterBox) {
                            pendingReporterBox.classList.add("hidden");
                        }

                        // Don't show unrecognized error while typing — only on Submit.
                        employeeError.style.display = "none";


                        // KEEP FORM UNLOCKED
                        setReportFormLocked(false);

                        return;

                    }


                    if (data.reporter_status === "Pending Approval") {
                        reporterVerified = false;
                        reporterBox.classList.add("hidden");
                        inactiveReporterBox.classList.add("hidden");
                        if (pendingReporterBox) {
                            pendingReporterBox.classList.remove("hidden");
                        }
                        employeeError.style.display = "none";
                        setReportFormLocked(true);
                        if (typeof lucide !== "undefined") {
                            lucide.createIcons();
                        }
                        return;
                    }

                    // =====================================================
                    // REPORTER IS INACTIVE
                    // THIS IS THE ONLY STATE THAT LOCKS THE FORM
                    // =====================================================

                    if (data.reporter_status !== "Active") {

                        reporterVerified = false;

                        reporterBox.classList.add("hidden");

                        inactiveReporterBox.classList.remove("hidden");

                        if (pendingReporterBox) {
                            pendingReporterBox.classList.add("hidden");
                        }

                        employeeError.style.display = "none";


                        // LOCK FORM HERE
                        setReportFormLocked(true);


                        if (typeof lucide !== "undefined") {

                            lucide.createIcons();

                        }

                        return;

                    }


                    // =====================================================
                    // REPORTER IS ACTIVE
                    // KEEP FORM UNLOCKED
                    // =====================================================

                    reporterVerified = true;

                    reporterBox.classList.remove("hidden");

                    inactiveReporterBox.classList.add("hidden");

                    if (pendingReporterBox) {
                        pendingReporterBox.classList.add("hidden");
                    }

                    employeeError.style.display = "none";


                    setReporterCard(id, data.reporter_full_name);


                    // KEEP FORM UNLOCKED
                    setReportFormLocked(false);

                    loadAssignedEquipment(id);

                })


                // =====================================================
                // LOOKUP REQUEST FAILED
                // KEEP FORM UNLOCKED
                // =====================================================

                .catch((error) => {

                    reporterVerified = false;

                    reporterBox.classList.add("hidden");

                    inactiveReporterBox.classList.add("hidden");

                    if (pendingReporterBox) {
                        pendingReporterBox.classList.add("hidden");
                    }

                    // Don't show verify errors while typing — only on Submit.
                    employeeError.style.display = "none";


                    // KEEP FORM UNLOCKED
                    setReportFormLocked(false);

                });

        });

        /* EQUIPMENT ASSIGNED TO THE VERIFIED REPORTER (read-only quick pick) */
        function clearAssignedEquipment() {
            const box = document.getElementById("assignedEquipmentBox");
            const list = document.getElementById("assignedEquipmentList");
            if (list) list.innerHTML = "";
            if (box) box.classList.add("hidden");
        }

        function loadAssignedEquipment(employeeId) {
            fetch(`/get-reporter/${encodeURIComponent(employeeId)}/assigned-equipment`, {
                headers: { Accept: "application/json" },
            })
                .then((response) => (response.ok ? response.json() : []))
                .then((items) => {
                    if (employeeInput.value.trim() !== employeeId || !reporterVerified) return;
                    renderAssignedEquipment(Array.isArray(items) ? items : []);
                })
                .catch(() => clearAssignedEquipment());
        }

        function renderAssignedEquipment(items) {
            const box = document.getElementById("assignedEquipmentBox");
            const list = document.getElementById("assignedEquipmentList");
            if (!box || !list) return;

            list.innerHTML = "";
            const roomIds = new Set(Array.from(roomSelect.options).map((option) => option.value));
            const pickable = items.filter((item) => {
                const roomId = String(item.room_id || "");
                return roomId !== "" && (roomIds.has(roomId) || roomIds.size === 0);
            });

            pickable.forEach((item) => {
                const chip = document.createElement("button");
                chip.type = "button";
                chip.className =
                    "inline-flex max-w-full items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-left text-xs text-slate-700 transition hover:border-[#2947f0]/40 hover:bg-[#2947f0]/5";

                const name = document.createElement("span");
                name.className = "truncate font-semibold";
                const setTag = String(item.computer_set_tag || "").trim();
                name.textContent = setTag
                    ? `${item.equipment_name || "Equipment"} · ${setTag}`
                    : (item.equipment_name || "Equipment");

                const room = document.createElement("span");
                room.className = "truncate text-slate-400";
                room.textContent = item.room_name || "";

                chip.append(name, room);
                chip.addEventListener("click", () => pickAssignedEquipment(item));
                list.appendChild(chip);
            });

            box.classList.toggle("hidden", pickable.length === 0);
        }

        function syncRoomSelectTrigger() {
            const trigger = document.querySelector('.rf-picker-trigger[data-for="roomSelect"]');
            const label = trigger?.querySelector(".rf-picker-label");
            if (!trigger || !label) return;
            const option = roomSelect.options[roomSelect.selectedIndex];
            label.textContent = option ? option.textContent.trim() : "Select Location";
            trigger.classList.toggle("is-placeholder", !roomSelect.value);
        }

        function ensureAssignedEquipmentInSelect(item) {
            const equipmentId = String(item.equipment_id || "");
            if (!equipmentId || !equipSelect) return false;

            if (Array.from(equipSelect.options).some((option) => option.value === equipmentId)) {
                return true;
            }

            const cached = Array.isArray(roomEquipmentCache) ? roomEquipmentCache : [];
            if (!cached.some((row) => String(row.equipment_id) === equipmentId)) {
                roomEquipmentCache = cached.concat([{
                    equipment_id: item.equipment_id,
                    equipment_name: item.equipment_name,
                    equipment_asset_tag: item.equipment_asset_tag || "",
                    equipment_serial_number: item.equipment_serial_number || "",
                    equipment_category_id: item.equipment_category_id || "",
                    computer_set_id: item.computer_set_id || null,
                    computer_set_tag: item.computer_set_tag || "",
                    computer_set_name: item.computer_set_name || "",
                    computer_set_member_role: item.computer_set_member_role || "",
                    open_report_ticket_code: "",
                }]);
            }

            if (typeof rebuildEquipmentSelect === "function") {
                rebuildEquipmentSelect();
            }

            return Array.from(equipSelect.options).some((option) => option.value === equipmentId);
        }

        function pickAssignedEquipment(item) {
            const roomId = String(item.room_id);
            const equipmentId = String(item.equipment_id);

            const alreadyAdded = selectedEquipmentItems.some(
                (added) => added.type === "id" && String(added.id) === equipmentId,
            );
            if (alreadyAdded) {
                const err = document.getElementById("equipmentError");
                err.classList.remove("hidden");
                err.innerText = "That equipment is already added.";
                return;
            }

            if (equipmentManualMode) {
                setEquipmentManualMode(false);
            }

            // Person-held units are omitted from the shared room list — inject them when picking from property assignment.
            if (!Array.from(roomSelect.options).some((option) => option.value === roomId)) {
                const opt = document.createElement("option");
                opt.value = roomId;
                opt.textContent = item.room_name || ("Location #" + roomId);
                roomSelect.appendChild(opt);
                if (typeof syncRoomSelectTrigger === "function") {
                    // trigger label sync after value set below
                }
            }

            const selectEquipment = () => {
                if (!ensureAssignedEquipmentInSelect(item)) {
                    return false;
                }
                equipSelect.value = equipmentId;
                equipSelect.dispatchEvent(new Event("change", { bubbles: true }));
                syncEquipmentSelectTrigger();
                equipSelect.closest(".rf-equip-block")?.scrollIntoView({ behavior: "smooth", block: "center" });
                return true;
            };

            if (roomSelect.value === roomId && selectEquipment()) return;

            roomSelect.value = roomId;
            roomSelect.dispatchEvent(new Event("change", { bubbles: true }));
            syncRoomSelectTrigger();

            let attempts = 0;
            const timer = setInterval(() => {
                attempts += 1;
                // Room fetch overwrites cache — re-inject after load settles.
                if (selectEquipment() || attempts >= 40 || roomSelect.value !== roomId) {
                    clearInterval(timer);
                    if (roomSelect.value === roomId) {
                        selectEquipment();
                    }
                }
            }, 100);
        }

        /* SCAN EQUIPMENT QR → fill Location + Equipment (reporter must be verified) */
        (function () {
            const qrCard = document.getElementById("qrScanCard");
            const qrCardSub = document.getElementById("qrScanCardSub");
            const qrNotice = document.getElementById("qrScanNotice");
            const overlay = document.getElementById("rfQrOverlay");
            const statusEl = document.getElementById("rfQrStatus");
            const errorEl = document.getElementById("rfQrError");
            const stageEmpty = document.getElementById("rfQrStageEmpty");
            const stageEmptyText = document.getElementById("rfQrStageEmptyText");
            const photoInput = document.getElementById("rfQrPhotoInput");
            if (!qrCard || !overlay) return;

            const libUrl = @json(asset('vendor/html5-qrcode/html5-qrcode.min.js'));
            let libPromise = null;
            let liveScanner = null;
            let fileScanner = null;
            let liveRunning = false;
            let busy = false;

            function loadLib() {
                if (window.Html5Qrcode) return Promise.resolve();
                if (!libPromise) {
                    libPromise = new Promise((resolve, reject) => {
                        const script = document.createElement("script");
                        script.src = libUrl;
                        script.onload = resolve;
                        script.onerror = () => {
                            libPromise = null;
                            reject(new Error("load"));
                        };
                        document.head.appendChild(script);
                    });
                }
                return libPromise;
            }

            let checkingReporter = false;

            function isReporterReady() {
                return reporterVerified && !reporterBox.classList.contains("hidden");
            }

            function syncCard() {
                if (checkingReporter) return;
                const ready = isReporterReady();
                qrCard.classList.toggle("is-locked", !ready);
                qrCard.setAttribute("aria-disabled", ready ? "false" : "true");
                qrCardSub.textContent = ready
                    ? "Fills in the location and equipment for you"
                    : "Enter your Employee ID first";
            }

            function showEmployeeIdError(message, scrollTarget) {
                employeeError.innerText = message;
                employeeError.style.display = "block";
                employeeInput.classList.add("is-error");
                employeeInput.style.borderColor = "";
                (scrollTarget || document.getElementById("rfReporterCard") || employeeInput)
                    .scrollIntoView({ behavior: "smooth", block: "center" });
            }

            function showReporterStateError() {
                const pendingBox = document.getElementById("pendingReporterBox");
                const inactiveBox = document.getElementById("inactiveReporterBox");
                if (pendingBox && !pendingBox.classList.contains("hidden")) {
                    showEmployeeIdError(
                        "This Employee ID is still waiting for approval, so it can't scan equipment yet.",
                        pendingBox,
                    );
                    return true;
                }
                if (inactiveBox && !inactiveBox.classList.contains("hidden")) {
                    showEmployeeIdError(
                        "This reporter account is inactive and can't scan equipment.",
                        inactiveBox,
                    );
                    return true;
                }
                return false;
            }

            function waitForReporterLookup(timeoutMs) {
                return new Promise((resolve) => {
                    const started = Date.now();
                    const timer = setInterval(() => {
                        const pendingBox = document.getElementById("pendingReporterBox");
                        const inactiveBox = document.getElementById("inactiveReporterBox");
                        const settled =
                            isReporterReady() ||
                            (pendingBox && !pendingBox.classList.contains("hidden")) ||
                            (inactiveBox && !inactiveBox.classList.contains("hidden"));
                        if (settled || Date.now() - started >= timeoutMs) {
                            clearInterval(timer);
                            resolve();
                        }
                    }, 100);
                });
            }

            async function handleCardClick() {
                if (isReporterReady()) {
                    openScanner();
                    return;
                }
                if (checkingReporter) return;

                const id = employeeInput.value.trim();
                if (!id) {
                    showEmployeeIdError("Enter your Employee ID first to scan an equipment QR.");
                    return;
                }
                if (showReporterStateError()) return;
                if (id.length < 8) {
                    showEmployeeIdError("Employee ID not recognized. Check it and try again.");
                    return;
                }

                checkingReporter = true;
                qrCardSub.textContent = "Checking your Employee ID…";
                let data = null;
                try {
                    const response = await fetch(`/get-reporter/${encodeURIComponent(id)}`, {
                        headers: { Accept: "application/json" },
                    });
                    data = response.ok ? await response.json() : null;
                } catch (_) {
                    data = null;
                }
                if (data && data.reporter_full_name) {
                    await waitForReporterLookup(3000);
                }
                checkingReporter = false;
                syncCard();

                if (employeeInput.value.trim() !== id) return;
                if (isReporterReady()) {
                    openScanner();
                    return;
                }
                if (showReporterStateError()) return;
                if (data && data.reporter_status === "Pending Approval") {
                    showEmployeeIdError("This Employee ID is still waiting for approval, so it can't scan equipment yet.");
                } else if (data && data.reporter_full_name && data.reporter_status !== "Active") {
                    showEmployeeIdError("This reporter account is inactive and can't scan equipment.");
                } else {
                    showEmployeeIdError("Employee ID not recognized. Check it and try again.");
                }
            }

            function setStatus(text) {
                statusEl.textContent = text || "";
                statusEl.hidden = !text;
            }

            function showError(text) {
                errorEl.textContent = text;
                errorEl.hidden = false;
            }

            function hideError() {
                errorEl.hidden = true;
                errorEl.textContent = "";
            }

            function showStageMessage(text) {
                stageEmptyText.textContent = text;
                stageEmpty.hidden = false;
                setStatus("");
            }

            function showNotice(text, isError) {
                qrNotice.textContent = text;
                qrNotice.classList.remove("hidden");
                qrNotice.style.background = isError ? "#fef2f2" : "";
                qrNotice.style.color = isError ? "#b91c1c" : "";
            }

            function hideNotice() {
                qrNotice.classList.add("hidden");
                qrNotice.textContent = "";
            }

            function stopLive() {
                if (!liveScanner || !liveRunning) return Promise.resolve();
                liveRunning = false;
                return liveScanner
                    .stop()
                    .then(() => liveScanner.clear())
                    .catch(() => {});
            }

            function startLive() {
                const canLive =
                    window.isSecureContext &&
                    navigator.mediaDevices &&
                    typeof navigator.mediaDevices.getUserMedia === "function";
                if (!canLive) {
                    showStageMessage(
                        "The live camera needs a secure (https) connection. Use “Take a photo of the QR instead” below.",
                    );
                    return;
                }

                if (!liveScanner) {
                    liveScanner = new Html5Qrcode("rfQrReader", { verbose: false });
                }

                liveScanner
                    .start(
                        { facingMode: "environment" },
                        {
                            fps: 10,
                            qrbox: (width, height) => {
                                const size = Math.floor(Math.min(width, height) * 0.7);
                                return { width: size, height: size };
                            },
                        },
                        onDecoded,
                        () => {},
                    )
                    .then(() => {
                        liveRunning = true;
                        if (overlay.hidden) {
                            stopLive();
                            return;
                        }
                        setStatus("Point the camera at the equipment QR code.");
                    })
                    .catch(() => {
                        showStageMessage(
                            "Camera access was blocked. Allow camera permission for this site, or take a photo of the QR instead.",
                        );
                    });
            }

            function openScanner() {
                if (!isReporterReady()) return;
                hideError();
                hideNotice();
                stageEmpty.hidden = true;
                busy = false;
                setStatus("Starting camera…");
                overlay.hidden = false;
                if (typeof lucide !== "undefined") lucide.createIcons();

                loadLib()
                    .then(startLive)
                    .catch(() =>
                        showStageMessage("The QR scanner could not load. Check your connection and try again."),
                    );
            }

            function closeScanner() {
                stopLive();
                overlay.hidden = true;
                busy = false;
            }

            function resumeScanning() {
                busy = false;
                if (liveScanner && liveRunning) {
                    try {
                        liveScanner.resume();
                    } catch (_) {}
                }
            }

            function normalizeCode(raw) {
                let code = String(raw || "").trim();
                if (/^https?:\/\//i.test(code)) {
                    try {
                        const parts = new URL(code).pathname.split("/").filter(Boolean);
                        if (parts.length) code = decodeURIComponent(parts[parts.length - 1]);
                    } catch (_) {}
                }
                return code;
            }

            function onDecoded(text) {
                if (busy) return;
                busy = true;
                if (liveScanner && liveRunning) {
                    try {
                        liveScanner.pause(true);
                    } catch (_) {}
                }
                lookup(text);
            }

            function lookup(raw) {
                const code = normalizeCode(raw);
                if (!code) {
                    showError("That QR code is empty.");
                    setTimeout(resumeScanning, 1500);
                    return;
                }

                hideError();
                setStatus("Checking " + code + "…");

                fetch(`/api/report-equipment/qr/${encodeURIComponent(code)}`, {
                    headers: { Accept: "application/json" },
                })
                    .then(async (response) => {
                        let data = null;
                        try {
                            data = await response.json();
                        } catch (_) {}
                        if (response.status === 429) {
                            throw new Error("Too many scans. Wait a minute, then try again.");
                        }
                        if (!response.ok || !data || !data.success || !data.equipment) {
                            throw new Error((data && data.message) || "No equipment matches this QR code.");
                        }
                        return data.equipment;
                    })
                    .then((equipment) => {
                        const already = selectedEquipmentItems.some(
                            (item) => item.type === "id" && String(item.id) === String(equipment.equipment_id),
                        );
                        if (already) {
                            throw new Error("That equipment is already added.");
                        }
                        closeScanner();
                        applyEquipment(equipment);
                    })
                    .catch((error) => {
                        showError(error && error.message ? error.message : "This QR code could not be checked.");
                        setStatus(liveRunning ? "Scan another QR code." : "");
                        setTimeout(resumeScanning, 1500);
                    });
            }

            function applyEquipment(equipment) {
                const roomId = String(equipment.room_id);
                const equipmentId = String(equipment.equipment_id);
                if (!Array.from(roomSelect.options).some((option) => option.value === roomId)) {
                    roomSelect.add(new Option(equipment.location || "Room", roomId));
                }

                pickAssignedEquipment({ room_id: roomId, equipment_id: equipmentId });

                const label =
                    (equipment.equipment_name || "Equipment") +
                    (equipment.location ? " · " + equipment.location : "");
                let tries = 0;
                const timer = setInterval(() => {
                    tries += 1;
                    if (String(equipSelect.value) === equipmentId) {
                        clearInterval(timer);
                        showNotice("Filled from QR: " + label + ". Pick an issue or add details, then tap Add to report.", false);
                        if (navigator.vibrate) navigator.vibrate(60);
                    } else if (tries >= 50) {
                        clearInterval(timer);
                        showNotice("Found " + label + ", but it could not be selected. Choose it manually.", true);
                    }
                }, 100);
            }

            photoInput.addEventListener("change", function () {
                const file = this.files && this.files[0];
                this.value = "";
                if (!file) return;

                hideError();
                setStatus("Reading the photo…");
                busy = true;

                loadLib()
                    .then(stopLive)
                    .then(() => {
                        if (!fileScanner) {
                            fileScanner = new Html5Qrcode("rfQrFileReader", { verbose: false });
                        }
                        return fileScanner.scanFile(file, false);
                    })
                    .then((text) => lookup(text))
                    .catch(() => {
                        busy = false;
                        setStatus("");
                        showError("No QR code found in that photo. Try again closer, with the whole label in frame.");
                    });
            });

            qrCard.addEventListener("click", handleCardClick);
            document.getElementById("rfQrClose")?.addEventListener("click", closeScanner);
            overlay.addEventListener("click", (event) => {
                if (event.target === overlay) closeScanner();
            });
            document.addEventListener("keydown", (event) => {
                if (event.key === "Escape" && !overlay.hidden) closeScanner();
            });
            document.getElementById("addEquipmentBtn")?.addEventListener("click", hideNotice);

            new MutationObserver(syncCard).observe(reporterBox, {
                attributes: true,
                attributeFilter: ["class"],
            });
            employeeInput.addEventListener("input", syncCard);

            const modal = document.getElementById("reportModal");
            if (modal) {
                new MutationObserver(() => {
                    if (modal.classList.contains("hidden") && !overlay.hidden) closeScanner();
                }).observe(modal, { attributes: true, attributeFilter: ["class"] });
            }

            syncCard();
        })();

        /* ROOM → EQUIPMENT FILTER (keep already-added items across locations) */
        roomSelect.addEventListener("change", function () {
            document.getElementById("locationError").classList.add("hidden");

            clearSuggestedIssue();

            hideIssueOrDetailsErrors();

            lastSelectedEquipment = "";
            roomEquipmentCache = [];

            const roomId = this.value;

            if (equipmentManualMode) {
                document.getElementById("equipmentManualInput").value = "";

                document
                    .querySelectorAll(".issue-btn")
                    .forEach((btn) => btn.classList.remove("active"));

                loadGenericSuggestions();
            }

            equipSelect.innerHTML =
                '<option value="">Select Equipment</option>';
            if (!roomId) {
                lastSelectedEquipment = "";

                equipSelect.value = "";
                syncEquipmentSelectTrigger();

                clearSuggestedIssue();

                showIssuePlaceholder();

                return;
            }
            fetch(`/get-equipment/${roomId}`)
                .then((r) => r.json())
                .then((data) => {
                    roomEquipmentCache = Array.isArray(data) ? data : [];
                    rebuildEquipmentSelect();
                });
        });

        /* AUTO SUGGESTION */
        equipSelect.addEventListener("change", function () {
            lastSelectedEquipment = this.value;

            const equipmentId = this.value;

            if (!equipmentId) {
                showIssuePlaceholder();

                clearSuggestedIssue();

                return;
            }

            fetch(`/get-suggestions/${equipmentId}`)
                .then((response) => response.json())
                .then((data) => {
                    if (data.length === 0) {
                        showIssuePlaceholder();
                    } else {
                        issueCarousel.innerHTML = "";

                        data.forEach((issue) => {
                            issueCarousel.innerHTML += `
                            <button
                                type="button"
                                class="issue-btn">
                                ${issue}
                            </button>
                        `;
                        });
                    }

                    updateIssueCount();

                    toggleIssueControls();

                    bindIssueButtons();
                });
        });
    });
</script>

<script>
    function updateIssueCount() {
        const count = document.querySelectorAll(".issue-btn").length;

        document.getElementById("issueCount").textContent = `(${count})`;

        toggleIssueControls();
    }
</script>

<link rel="stylesheet" href="{{ asset('vendor/driver.js/driver.css') }}">
<script src="{{ asset('vendor/driver.js/driver.js.iife.js') }}"></script>
<script>
    (function reportFormTour() {
        const modal = document.getElementById("reportModal");
        const SEEN_KEY = "paayo_report_tour_seen";
        let tour = null;

        if (!modal) return;

        function hasSeenTour() {
            try {
                return window.localStorage.getItem(SEEN_KEY) === "1";
            } catch (e) {
                return false;
            }
        }

        function markTourSeen() {
            try {
                window.localStorage.setItem(SEEN_KEY, "1");
            } catch (e) {}
        }

        function isShown(el) {
            if (!el) return false;
            const rect = el.getBoundingClientRect();
            return rect.width > 0 && rect.height > 0 && getComputedStyle(el).visibility !== "hidden";
        }

        function firstShown(selectors) {
            for (const selector of selectors) {
                const el = modal.querySelector(selector);
                if (isShown(el)) return el;
            }
            return null;
        }

        function buildSteps() {
            const desktop = window.matchMedia("(min-width: 1024px)").matches;
            const list = modal.querySelector("#selectedEquipmentList");
            const listHasItems = list && list.children.length > 0;

            const defs = [
                {
                    targets: ["#rfReporterCard"],
                    title: "You're verified",
                    text: "Your employee ID was confirmed with your PIN. You're ready to file this report.",
                    tip: true,
                },
                {
                    targets: ["#assignedEquipmentBox"],
                    optional: true,
                    title: "Your equipment",
                    text: "These are assigned to you. Tap one to report it right away.",
                },
                {
                    targets: ["#qrScanCard"],
                    title: "Scan the QR sticker",
                    text: "Fastest way: scan the QR sticker on the equipment. Location and equipment fill in for you.",
                },
                {
                    targets: [".rf-loc-equip-card", ".rf-loc-block"],
                    title: "Or choose manually",
                    text: "Pick the location, then the equipment. You can switch rooms anytime and your list is kept.",
                },
                {
                    targets: [".rf-issues-block"],
                    title: "Suggested issues",
                    text: "Pick the problem that matches. Suggestions appear after you choose equipment.",
                },
                {
                    targets: [".rf-details-block"],
                    title: "Additional details",
                    text: "Unlocks once equipment is picked. Describe what's wrong with that one item. Optional if you picked an issue.",
                },
                {
                    targets: ["#addEquipmentBtn", "#addManualEquipmentBtn"],
                    title: "Add it to your report",
                    text: "Saves the equipment with its issue and details. You may be asked a few quick checks first. Repeat for each item, even from other rooms.",
                },
                {
                    targets: desktop ? ["#rfAsideEquipmentPanel"] : (listHasItems ? ["#selectedEquipmentList"] : []),
                    title: "Your list",
                    text: (desktop ? "Added equipment shows up here." : "Added equipment shows up under the picker.")
                        + " Each item can have one photo as proof (optional).",
                },
                {
                    targets: ["#submitReportBtn"],
                    title: "Submit",
                    text: "Sends everything as one report. Priority is set automatically, so there's nothing else to fill in.",
                },
            ];

            return defs.reduce((steps, def) => {
                const element = firstShown(def.targets);
                if (!element && def.optional) return steps;

                const description = def.tip
                    ? def.text + '<span class="rf-tour-tip">Tap anywhere outside this bubble to continue.</span>'
                    : def.text;

                steps.push({
                    ...(element ? { element } : {}),
                    popover: { title: def.title, description },
                });
                return steps;
            }, []);
        }

        function startReportTour() {
            if (!window.driver?.js?.driver || modal.classList.contains("hidden")) return;
            if (tour) tour.destroy();

            markTourSeen();

            tour = window.driver.js.driver({
                steps: buildSteps(),
                showProgress: true,
                showButtons: ["next", "previous"],
                nextBtnText: "Next",
                prevBtnText: "Back",
                doneBtnText: "Done",
                overlayClickBehavior: "nextStep",
                disableActiveInteraction: true,
                overlayOpacity: 0.6,
                stagePadding: 6,
                stageRadius: 14,
                popoverClass: "rf-tour-popover",
                onPopoverRender(popover, { driver }) {
                    if (!driver.hasNextStep()) return;
                    const skip = document.createElement("button");
                    skip.type = "button";
                    skip.className = "rf-tour-skip";
                    skip.textContent = "Skip";
                    skip.addEventListener("click", () => driver.destroy());
                    popover.footerButtons.insertBefore(skip, popover.footerButtons.firstChild);
                },
                onDestroyed() {
                    tour = null;
                },
            });
            tour.drive();
        }

        window.startReportTour = startReportTour;

        document.addEventListener("click", (e) => {
            const trigger = e.target.closest?.("[data-report-tour-start]");
            if (!trigger || !modal.contains(trigger)) return;
            e.preventDefault();
            startReportTour();
        });

        // The highlighted field is non-interactive during the tour, so a click on it lands on the page body.
        window.addEventListener("click", (e) => {
            if (!tour?.isActive()) return;
            const target = e.target;
            if (target.closest?.(".driver-popover") || target.closest?.(".driver-overlay")) return;
            e.preventDefault();
            e.stopPropagation();
            tour.moveNext();
        }, true);

        // Escape should only end the tour, not close the report modal underneath it.
        window.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && tour?.isActive()) e.stopPropagation();
        }, true);

        // Scrolling inside the modal does not reach window, so keep the highlight in place manually.
        modal.addEventListener("scroll", () => tour?.refresh(), true);

        new MutationObserver(() => {
            if (modal.classList.contains("hidden")) {
                if (tour) tour.destroy();
                return;
            }
            if (!hasSeenTour()) {
                window.setTimeout(() => {
                    if (!modal.classList.contains("hidden") && !tour && !hasSeenTour()) startReportTour();
                }, 450);
            }
        }).observe(modal, { attributes: true, attributeFilter: ["class"] });
    })();
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function paayoSwal(options) {
        const tone = options.tone || "success";
        const swalIcon =
            tone === "error" ? "error" : (tone === "warning" ? "warning" : (tone === "info" ? "info" : "success"));
        const icons = {
            success:
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
            error:
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/></svg>',
            warning:
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>',
            info:
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
        };

        return Swal.fire({
            icon: swalIcon,
            iconHtml: icons[tone] || icons.success,
            title: options.title || "",
            text: options.html ? undefined : (options.text || ""),
            html: options.html || undefined,
            showConfirmButton: options.showConfirmButton !== false,
            showCancelButton: !!options.showCancelButton,
            confirmButtonText: options.confirmText || "OK",
            cancelButtonText: options.cancelText || "Cancel",
            reverseButtons: !!options.reverseButtons,
            focusCancel: !!options.focusCancel,
            buttonsStyling: false,
            backdrop: "rgba(11, 18, 32, 0.7)",
            width: 400,
            timer: options.timer,
            timerProgressBar: !!options.timer,
            allowOutsideClick: options.allowOutsideClick !== false,
            showClass: { popup: "swal2-show paayo-swal-in" },
            customClass: {
                popup: "paayo-swal",
                title: "paayo-swal-heading",
                htmlContainer: "paayo-swal-text",
                confirmButton: "paayo-swal-btn",
                cancelButton: "paayo-swal-cancel",
                icon: "paayo-icon-" + tone,
                timerProgressBar: "modern-success-progress",
            },
        });
    }

    /**
     * Phase 2 X / Cancel: confirm, then return to phase 1 (PIN verify) — do not close the modal.
     * Phase 1 X: closes the whole report modal.
     */
    window.requestCloseReportModal = async function () {
        const gate = document.getElementById("reporterGate");
        const reportForm = document.getElementById("reportForm");
        const inPhase2 = !!(reportForm && !reportForm.hidden && gate && gate.hidden);

        if (!inPhase2) {
            if (typeof closeReportModal === "function") closeReportModal();
            return;
        }

        const result = await paayoSwal({
            tone: "warning",
            title: "Leave this report?",
            text: "You'll need to verify your employee ID again. Any equipment you've added won't be submitted & saved.",
            confirmText: "Leave",
            showCancelButton: true,
            cancelText: "Cancel",
            reverseButtons: true,
            focusCancel: true,
            allowOutsideClick: false,
        });

        if (result.isConfirmed && typeof window.reporterGateReset === "function") {
            window.reporterGateReset();
        }
    };

    document
        .getElementById("reportForm")
        .addEventListener("submit", async function (e) {
            e.preventDefault();

            const form = this;
            const employeeInput = document.getElementById("employeeIdInput");
            const employeeError = document.getElementById("employeeError");
            const roomSelect = document.getElementById("roomSelect");
            const equipmentSelect = document.getElementById("equipmentSelect");
            const equipmentManualInput = document.getElementById(
                "equipmentManualInput",
            );
            const roomId = roomSelect.value;
            const pendingReporterBox =
                document.getElementById("pendingReporterBox");

            document.getElementById("locationError").classList.add("hidden");
            document.getElementById("equipmentError").classList.add("hidden");
            hideIssueOrDetailsErrors();
            employeeError.style.display = "none";
            setSelectTriggerBorder(roomSelect, "");
            setSelectTriggerBorder(equipmentSelect, "");
            if (equipmentManualInput) {
                equipmentManualInput.style.borderColor = "";
            }
            employeeInput.classList.remove("is-error");
            employeeInput.style.borderColor = "";

            if (!selectedEquipmentItems.length) {
                document
                    .getElementById("equipmentError")
                    .classList.remove("hidden");
                document.getElementById("equipmentError").innerText =
                    "Please add at least one equipment.";
                if (equipmentManualMode) {
                    equipmentManualInput.style.borderColor = "#dc2626";
                    equipmentManualInput.focus();
                } else {
                    setSelectTriggerBorder(equipmentSelect, "#dc2626");
                    equipmentSelect.focus();
                }
                return;
            }

            // Backfill room for any legacy items, then require every item to have a location.
            selectedEquipmentItems.forEach(function (item) {
                if (!item.roomId && roomId) {
                    item.roomId = String(roomId);
                    const option = roomSelect.options[roomSelect.selectedIndex];
                    item.roomLabel = option ? option.textContent.trim() : item.roomLabel || "";
                }
            });

            const itemsMissingRoom = selectedEquipmentItems.filter(
                (item) => !String(item.roomId || "").trim(),
            );

            if (itemsMissingRoom.length > 0) {
                document
                    .getElementById("locationError")
                    .classList.remove("hidden");
                setSelectTriggerBorder(roomSelect, "#dc2626");
                roomSelect.focus();
                return;
            }

            const itemsMissingIssue = selectedEquipmentItems.filter(
                (item) =>
                    !String(item.issue || "").trim() &&
                    !String(item.details || "").trim(),
            );

            if (itemsMissingIssue.length > 0) {
                document
                    .getElementById("equipmentError")
                    .classList.remove("hidden");
                document.getElementById("equipmentError").innerText =
                    "Each equipment in the list needs a suggested issue or details. Remove incomplete items and add them again.";
                return;
            }

            const unsavedDetails = document.getElementById("problemDescription");
            if (unsavedDetails && unsavedDetails.value.trim()) {
                document
                    .getElementById("equipmentError")
                    .classList.remove("hidden");
                document.getElementById("equipmentError").innerText =
                    "You wrote details but didn't add that equipment yet. Tap Add to include it, or clear the details.";
                unsavedDetails.focus();
                return;
            }

            const suggestedIssueInput = document.getElementById(
                "suggestedIssueInput",
            );
            if (suggestedIssueInput && selectedEquipmentItems.length > 0) {
                suggestedIssueInput.value =
                    selectedEquipmentItems[0].issue || "";
            }

            if (!reporterVerified) {
                if (
                    pendingReporterBox &&
                    !pendingReporterBox.classList.contains("hidden")
                ) {
                    pendingReporterBox.scrollIntoView({
                        behavior: "smooth",
                        block: "center",
                    });
                    return;
                }

                employeeError.innerText = "Employee ID not recognized.";
                employeeError.style.display = "block";
                employeeInput.classList.add("is-error");
                employeeInput.style.borderColor = "";
                document.getElementById("rfReporterCard")?.scrollIntoView({ behavior: "smooth", block: "center" });
                return;
            }

            Swal.fire({
                title: "Submitting report",
                html: `
                    <div class="swal-submitting-message">
                        Processing your maintenance report...
                    </div>
                `,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                background: "#ffffff",
                color: "#111827",
                width: "420px",
                padding: "1.75rem",
                backdrop: `rgba(15, 23, 42, 0.35)`,
                customClass: {
                    popup: "modern-submitting-popup",
                    title: "modern-submitting-title",
                    htmlContainer: "modern-submitting-content",
                    loader: "modern-submitting-loader",
                },
                didOpen: () => {
                    Swal.showLoading();
                },
            });

            const originalItems = selectedEquipmentItems.slice();
            const originalRoomId = roomSelect.value;

            try {
                const csrfToken = form.querySelector('input[name="_token"]')?.value;
                const payload = buildReportFormData(form, originalItems);
                // Items from every room go on one ticket; its main room is the first item's.
                payload.set("report_room_id", String(originalItems[0].roomId || roomId));

                const response = await fetch(form.action, {
                    method: "POST",
                    body: payload,
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": csrfToken || "",
                    },
                    credentials: "same-origin",
                });

                const contentType = response.headers.get("content-type") || "";
                const data = contentType.includes("application/json")
                    ? await response.json().catch(() => ({}))
                    : {};

                if (data.pin_required) {
                    await paayoSwal({
                        tone: "error",
                        title: "Verify again",
                        text: String(data.message || "Enter your employee ID and PIN again."),
                        confirmText: "OK",
                    });
                    if (typeof window.reporterGateReset === "function") {
                        window.reporterGateReset();
                    }
                    return;
                }

                if (!response.ok || data.success !== true) {
                    const validationMessage =
                        data.message ||
                        (data.errors
                            ? Object.values(data.errors).flat()[0]
                            : null) ||
                        (!contentType.includes("application/json")
                            ? "Server did not return a JSON save result (HTTP " +
                              response.status +
                              "). The report was probably not saved."
                            : null) ||
                        "Could not submit the report. Please try again.";

                    await paayoSwal({
                        tone: "error",
                        title: "Submit failed",
                        text: String(validationMessage),
                        confirmText: "Try again",
                    });
                    return;
                }

                revokeItemPhotos(originalItems);
                selectedEquipmentItems = [];
                renderSelectedEquipment();

                const successText =
                    data.message ||
                    (data.ticket_code
                        ? "Maintenance report " + data.ticket_code + " submitted successfully."
                        : "Report submitted successfully.");

                await paayoSwal({
                    tone: "success",
                    title: data.merged ? "Update added" : "Report submitted",
                    text: successText,
                    confirmText: "OK",
                    timer: 4000,
                });

                form.reset();
                roomEquipmentCache = [];
                rebuildEquipmentSelect();
                reporterVerified = false;
                clearSuggestedIssue();
                showIssuePlaceholder();
                if (typeof window.reporterGateReset === "function") {
                    window.reporterGateReset();
                }
                if (typeof closeReportModal === "function") {
                    closeReportModal();
                }
            } catch (error) {
                selectedEquipmentItems = originalItems;
                renderSelectedEquipment();
                roomSelect.value = originalRoomId;
                await paayoSwal({
                    tone: "error",
                    title: "Submit failed",
                    text: "Network or server error while submitting. Check your connection and try again.",
                    confirmText: "Try again",
                });
            }
        });
</script>

<script>
    /* ─────────────────────────────────────────────────────────
   PHASE 1 (employee ID + PIN) → PHASE 2 (report form)
───────────────────────────────────────────────────────── */
    document.addEventListener("DOMContentLoaded", function () {
        const gate = document.getElementById("reporterGate");
        const reportForm = document.getElementById("reportForm");
        if (!gate || !reportForm) return;

        const verifyForm = document.getElementById("reporterGateForm");
        const forgotForm = document.getElementById("reporterForgotForm");
        const gateId = document.getElementById("gateEmployeeId");
        const gatePin = document.getElementById("gatePin");
        const gateMessage = document.getElementById("gateMessage");
        const gateSubmitBtn = document.getElementById("gateSubmitBtn");
        const gateSubmitText = document.getElementById("gateSubmitText");
        const forgotId = document.getElementById("forgotEmployeeId");
        const forgotMessage = document.getElementById("forgotMessage");
        const forgotSubmitBtn = document.getElementById("forgotSubmitBtn");
        const forgotSubmitText = document.getElementById("forgotSubmitText");
        const employeeInput = document.getElementById("employeeIdInput");
        const reportModalEl = document.getElementById("reportModal");

        const csrfToken = () => reportForm.querySelector('input[name="_token"]')?.value || "";

        async function postJson(url, body) {
            const response = await fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": csrfToken(),
                },
                credentials: "same-origin",
                body: JSON.stringify(body || {}),
            });
            const data = await response.json().catch(() => ({}));
            return { ok: response.ok, status: response.status, data };
        }

        function errorText(result, fallback) {
            if (result.status === 419) {
                return "This page expired. Refresh the page and try again.";
            }
            const data = result.data || {};
            return data.message
                || (data.errors ? Object.values(data.errors).flat()[0] : "")
                || fallback;
        }

        function setMessage(el, text, tone) {
            el.className = "rf-gate-msg" + (text ? " is-" + (tone || "error") : "");
            el.textContent = text || "";
        }

        function refreshIcons() {
            if (typeof lucide !== "undefined") lucide.createIcons();
        }

        const pinToggle = document.getElementById("gatePinToggle");
        const pinWrap = gatePin.closest(".rf-gate-pin-wrap");

        const PIN_SHAKE_FRAMES = Array.from({ length: 25 }, (_, i) => {
            const t = i / 24;
            return { transform: `translateX(${(Math.sin(t * Math.PI * 6) * 10 * (1 - t)).toFixed(2)}px)` };
        });

        function shakePin() {
            gatePin.classList.add("is-error");
            if (navigator.vibrate) navigator.vibrate(40);
            if (!pinWrap.animate || window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;
            pinWrap.animate(PIN_SHAKE_FRAMES, { duration: 420, easing: "linear" });
        }

        function setPinVisible(visible) {
            gatePin.type = visible ? "text" : "password";
            const label = visible ? "Hide PIN" : "Show PIN";
            pinToggle.setAttribute("aria-label", label);
            pinToggle.title = label;
            pinToggle.setAttribute("aria-pressed", visible ? "true" : "false");
            pinToggle.querySelector('[data-pin-icon="show"]').hidden = visible;
            pinToggle.querySelector('[data-pin-icon="hide"]').hidden = !visible;
        }

        pinToggle.addEventListener("mousedown", (e) => e.preventDefault());
        pinToggle.addEventListener("click", function () {
            setPinVisible(gatePin.type === "password");
            gatePin.focus();
            const end = gatePin.value.length;
            gatePin.setSelectionRange(end, end);
        });

        const PHASE2_RESUME_KEY = "paayo_report_phase2";

        function markPhase2Resume(employeeId) {
            try {
                sessionStorage.setItem(PHASE2_RESUME_KEY, String(employeeId || "1"));
            } catch (_) {}
        }

        function clearPhase2Resume() {
            try {
                sessionStorage.removeItem(PHASE2_RESUME_KEY);
            } catch (_) {}
            if (typeof window.clearReportDraft === "function") {
                window.clearReportDraft();
            }
        }

        window.clearReportPhase2Mark = clearPhase2Resume;

        function showVerifyStep() {
            forgotForm.hidden = true;
            verifyForm.hidden = false;
        }

        function showPhase1(clearId) {
            clearPhase2Resume();
            reportForm.hidden = true;
            gate.hidden = false;
            showVerifyStep();
            gatePin.value = "";
            gatePin.classList.remove("is-error");
            setPinVisible(false);
            setMessage(gateMessage, "");
            setMessage(forgotMessage, "");
            if (clearId) gateId.value = "";
            if (employeeInput && employeeInput.value !== "") {
                employeeInput.value = "";
                employeeInput.dispatchEvent(new Event("input", { bubbles: true }));
            }
            refreshIcons();
        }

        function enterPhase2(employeeId) {
            gate.hidden = true;
            reportForm.hidden = false;
            gatePin.value = "";
            setPinVisible(false);
            setMessage(gateMessage, "");
            if (employeeInput) {
                employeeInput.value = employeeId;
                employeeInput.dispatchEvent(new Event("input", { bubbles: true }));
            }
            markPhase2Resume(employeeId);
            refreshIcons();
        }

        window.reporterGateReset = function () {
            postJson("/reporter/exit").catch(() => {});
            showPhase1(true);
        };

        gateId.addEventListener("input", function () {
            this.value = this.value.toUpperCase().replace(/\s+/g, "");
            gatePin.classList.remove("is-error");
            setMessage(gateMessage, "");
        });

        gatePin.addEventListener("input", function () {
            this.value = this.value.replace(/\D/g, "").slice(0, 6);
            this.classList.remove("is-error");
            setMessage(gateMessage, "");
        });

        forgotId.addEventListener("input", function () {
            this.value = this.value.toUpperCase().replace(/\s+/g, "");
        });

        verifyForm.addEventListener("submit", async function (e) {
            e.preventDefault();
            const employeeId = gateId.value.trim();
            const pin = gatePin.value.trim();

            if (!employeeId) {
                setMessage(gateMessage, "Enter your employee ID.");
                gateId.focus();
                return;
            }
            if (!/^\d{6}$/.test(pin)) {
                setMessage(gateMessage, "Enter your 6-digit PIN.");
                shakePin();
                gatePin.focus();
                return;
            }

            gateSubmitBtn.disabled = true;
            gateSubmitText.textContent = "Checking…";
            try {
                const result = await postJson("/reporter/verify", { employee_id: employeeId, pin: pin });
                if (result.ok && result.data.success) {
                    enterPhase2(result.data.reporter_employee_id);
                    return;
                }
                const code = result.data && result.data.code;
                setMessage(
                    gateMessage,
                    errorText(result, "Could not verify. Please try again."),
                    code === "pending" ? "warn" : "error",
                );
                gatePin.value = "";
                if (code === "incorrect" || code === "locked") shakePin();
                gatePin.focus();
            } catch (_) {
                setMessage(gateMessage, "Network error. Check your connection and try again.");
            } finally {
                gateSubmitBtn.disabled = false;
                gateSubmitText.textContent = "Continue";
            }
        });

        document.getElementById("gateForgotLink").addEventListener("click", function () {
            verifyForm.hidden = true;
            forgotForm.hidden = false;
            forgotId.value = gateId.value.trim();
            setMessage(forgotMessage, "");
            forgotId.focus();
        });

        document.getElementById("forgotBackLink").addEventListener("click", function () {
            if (!gateId.value.trim()) gateId.value = forgotId.value.trim();
            showVerifyStep();
            (gateId.value ? gatePin : gateId).focus();
        });

        forgotForm.addEventListener("submit", async function (e) {
            e.preventDefault();
            const employeeId = forgotId.value.trim();
            if (!employeeId) {
                setMessage(forgotMessage, "Enter your employee ID.");
                forgotId.focus();
                return;
            }

            forgotSubmitBtn.disabled = true;
            forgotSubmitText.textContent = "Sending…";
            try {
                const result = await postJson("/reporter/pin/forgot", { employee_id: employeeId });
                setMessage(
                    forgotMessage,
                    result.ok ? result.data.message : errorText(result, "Could not send the link. Please try again."),
                    result.ok ? "info" : "error",
                );
            } catch (_) {
                setMessage(forgotMessage, "Network error. Check your connection and try again.");
            } finally {
                forgotSubmitBtn.disabled = false;
                forgotSubmitText.textContent = "Send reset link";
            }
        });

        // Focus phase-1 fields when the modal opens on the gate (Make Report handles reset).
        function onModalOpen() {
            if (!gate.hidden && window.matchMedia("(min-width: 768px)").matches) {
                setTimeout(() => (gateId.value ? gatePin : gateId).focus(), 80);
            }
        }

        if (reportModalEl) {
            let wasHidden = reportModalEl.classList.contains("hidden");
            new MutationObserver(function () {
                const hidden = reportModalEl.classList.contains("hidden");
                if (wasHidden && !hidden) onModalOpen();
                wasHidden = hidden;
            }).observe(reportModalEl, { attributes: true, attributeFilter: ["class"] });
        }

        // Browser refresh while in phase 2: reopen modal on the report form.
        (async function tryResumePhase2AfterRefresh() {
            let marked = null;
            try {
                marked = sessionStorage.getItem(PHASE2_RESUME_KEY);
            } catch (_) {
                return;
            }
            if (!marked) return;

            try {
                const response = await fetch("/reporter/session", {
                    headers: { Accept: "application/json" },
                    credentials: "same-origin",
                });
                const data = await response.json();
                if (!data || !data.success || !data.reporter_employee_id) {
                    clearPhase2Resume();
                    return;
                }
                enterPhase2(data.reporter_employee_id);
                if (typeof openReportModal === "function") {
                    openReportModal({ resume: true });
                }
                if (typeof window.restoreReportDraft === "function") {
                    window.restoreReportDraft();
                }
            } catch (_) {
                clearPhase2Resume();
            }
        })();
    });
</script>
