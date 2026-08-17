{{-- ══════════════════════════════════════════════════════════════
     MAINTENANCE REPORT FORM  ·  PRISM Dark Theme
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
    }

    .rf-picker-overlay {
        position: fixed;
        inset: 0;
        z-index: 80;
        display: none;
        align-items: flex-end;
        justify-content: center;
        background: rgba(11, 18, 32, 0.7);
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
        padding: 12px;
    }

    .rf-picker-overlay.is-open {
        display: flex;
    }

    .rf-picker-sheet {
        width: min(100%, 480px);
        max-height: min(78vh, 640px);
        background: #fff;
        border-radius: 24px 24px 18px 18px;
        box-shadow: 0 28px 70px rgba(15, 23, 42, .22);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        font-family: "Plus Jakarta Sans", "Inter", sans-serif;
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
        padding: 18px 18px 12px;
        border-bottom: 1px solid #e8ecf4;
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

    .rf-picker-search {
        margin: 14px 18px 0;
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f3f6ff;
        border: 1px solid #e8ecf4;
        border-radius: 18px;
        padding: 0 18px;
        min-height: 64px;
        height: 64px;
    }

    .rf-picker-search input {
        width: 100%;
        border: 0;
        background: transparent;
        outline: none;
        font-size: 16px;
        color: #1a1a2e;
    }

    .rf-picker-list {
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        padding: 8px 10px 16px;
        min-height: 120px;
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
        padding: 28px 12px;
    }

    .rf-picker-close {
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

    /* ── UPLOAD ZONE ── */
    .upload-zone {
        border: 1.5px dashed rgba(255, 255, 255, 0.12);
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        background: rgba(255, 255, 255, 0.02);
        cursor: pointer;
        transition: all 0.2s ease;
        display: block;
    }
    .upload-zone:hover {
        border-color: rgba(240, 180, 41, 0.4);
        background: rgba(240, 180, 41, 0.04);
    }
    .upload-zone.uploaded {
        border-color: rgba(52, 211, 153, 0.4);
        background: rgba(52, 211, 153, 0.05);
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
        overflow-y: auto;
        overflow-x: hidden;
        align-items: flex-start;
        padding: 36px 28px !important;
    }

    #reportModal > div {
        margin-top: auto;
        margin-bottom: auto;
        max-width: 1080px;
    }

    #reportModal .report-form-shell {
        position: relative;
        isolation: isolate;
        overflow: hidden;
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
        height: auto !important;
        max-height: none !important;
    }

    #reportModal .report-form-scroll,
    #reportModal .report-form-aside {
        overflow: visible !important;
        height: auto !important;
        max-height: none !important;
        scrollbar-gutter: auto !important;
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
    }

    #reportModal .report-form-scroll::-webkit-scrollbar,
    #reportModal .report-form-aside::-webkit-scrollbar,
    #reportModal #issueCarousel::-webkit-scrollbar {
        width: 0 !important;
        height: 0 !important;
        display: none !important;
    }

    #reportModal .report-form-scroll {
        background: transparent !important;
        border-right: 0 !important;
        padding: 36px 36px 32px !important;
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
    .details-textarea:focus {
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
        padding: 28px 28px 32px !important;
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
        border-color: #fff200;
        background: #fffce6;
        box-shadow: 0 0 0 4px rgba(255, 242, 0, 0.18);
    }

    .priority-title {
        font-family: "Plus Jakarta Sans", sans-serif;
        color: #1a1a2e;
    }

    .priority-card.p-non-urgent .priority-title {
        color: #0025cc;
    }

    .priority-card.p-urgent .priority-title {
        color: #1a1a2e;
    }

    .priority-desc {
        color: #6b7280;
    }

    .priority-card input {
        accent-color: #0025cc;
    }

    .priority-card.p-urgent input {
        accent-color: #ca8a04;
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
    .rf-close-mobile {
        width: 36px !important;
        height: 36px !important;
        border-radius: 12px !important;
        background: #fff !important;
        border: 1px solid #e8ecf4 !important;
        color: #6b7280 !important;
    }

    .rf-close-desktop:hover,
    .rf-close-mobile:hover {
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
            padding: 36px 28px !important;
        }
    }

    @media (min-width: 1024px) {
        .report-form-aside {
            border-top: 0 !important;
            border-left: 1px solid rgba(232, 236, 244, .9) !important;
        }
    }

    @media (max-width: 767px) {
        #reportModal {
            align-items: flex-start;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            padding: 8px !important;
        }

        #reportModal .report-form-shell {
            max-height: calc(100dvh - 16px);
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            -ms-overflow-style: none;
            border-radius: 24px !important;
        }

        #reportModal .report-form-shell::-webkit-scrollbar {
            width: 0;
            display: none;
        }

        #reportModal .report-form-scroll {
            overflow: visible !important;
            max-height: none;
            padding: 20px 18px 12px !important;
        }

        .report-form-aside {
            padding: 16px 18px 22px !important;
        }

        #reportModal .report-form-scroll h2 {
            font-size: 1.35rem !important;
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
</style>

<form
    method="POST"
    action="/store-report"
    enctype="multipart/form-data"
    id="reportForm"
>
    @csrf

    <div class="mx-auto w-full max-w-6xl px-2">
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
                        min-h-0
                        overflow-y-auto
                        bg-white
                        p-6
                        sm:p-7

                        lg:col-span-8
                        lg:h-full
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
                            >Report room, facility, or equipment concerns.</p>
                        </div>

                        {{-- CLOSE (mobile) --}}
                        <button
                            type="button"
                            onclick="closeReportModal()"
                            class="rf-close-mobile flex h-9 w-9 items-center justify-center rounded-xl lg:hidden"
                        >
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>

                    {{-- EMPLOYEE ID --}}
                    <div class="mb-5">
                        <label class="rf-label">Employee ID</label>

                        <div style="position: relative">
                            <span
                                style="
                                    position: absolute;
                                    left: 13px;
                                    top: 50%;
                                    transform: translateY(-50%);
                                    color: #2947f0;
                                    pointer-events: none;
                                "
                            >
                                <i data-lucide="scan-face" class="h-5 w-5"></i>
                            </span>
                            <input
                                type="text"
                                id="employeeIdInput"
                                name="report_reporter_employee_id"
                                value="{{ old('report_reporter_employee_id') }}"
                                placeholder="EMPLOYEE ID"
                                class="rf-input details-textarea"
                                style="padding-left: 42px; height: 48px"
                                required
                            />
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

                    {{-- REPORTER INFO BOX --}}
                    <div id="reporterInfoBox" class="reporter-box mb-5 hidden">
                        <p
                            style="
                                font-size: 11px;
                                color: #2947f0;
                                text-transform: uppercase;
                                letter-spacing: 0.08em;
                                margin-bottom: 6px;
                            "
                        >Reporter Verified</p>

                        <p
                            id="reporterName"
                            style="
                                display: flex;
                                align-items: center;
                                gap: 8px;
                                font-family: &quot;Outfit&quot;, sans-serif;
                                color: #3c3c3f;
                                font-size: 1rem;
                            "
                        ></p>
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
                                    Please contact the maintenance office if you
                                    believe this is a mistake.
                                </p>

                            </div>

                        </div>
                    </div>

                    {{-- LOCATION + EQUIPMENT --}}
                    <div class="mb-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                        {{-- LOCATION --}}
                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <label class="rf-label" style="margin-bottom: 0"
                                    >Location</label
                                >
                            </div>

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
                                            {{ $room->floor_level }} - {{ $room->room_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <p id="locationError" class="mt-1 hidden text-[14px] text-red-500">Please select a location.</p>
                        </div>

                        {{-- EQUIPMENT --}}
                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <label class="rf-label" style="margin-bottom: 0"
                                    >Equipment</label
                                >
                                <button
                                    type="button"
                                    id="toggleEquipmentInput"
                                    style="
                                        font-size: 11px;
                                        font-weight: 700;
                                        color: #0037c7;
                                        background: none;
                                        border: none;
                                        cursor: pointer;
                                        transition: color 0.2s;
                                    "
                                    onmouseover="this.style.color = '#002ea8'"
                                    onmouseout="this.style.color = '#0037C7'"
                                >
                                    Equipment not listed?
                                </button>
                            </div>

                            <div
                                id="equipmentDropdownContainer"
                                class="rf-select-wrap"
                            >
                                <select
                                    name="report_equipment_id"
                                    id="equipmentSelect"
                                    class="rf-input details-textarea rf-native-select"
                                    data-picker-title="Select equipment"
                                    data-picker-search="Search equipment"
                                    style="
                                        height: 48px;
                                        padding-right: 36px;
                                        cursor: pointer;
                                    "
                                >
                                    <option value="">Select Equipment</option>
                                </select>
                            </div>

                            <div id="equipmentManualContainer" class="hidden">
                                <input
                                    type="text"
                                    name="report_equipment_manual"
                                    id="equipmentManualInput"
                                    placeholder="Enter equipment name manually..."
                                    class="rf-input details-textarea"
                                    style="height: 48px"
                                />
                            </div>

                            <p id="equipmentError" class="mt-1 hidden text-[14px] text-red-500">Please enter or select an equipment.</p>
                        </div>
                    </div>

                    {{-- SUGGESTED ISSUES --}}
                    <div>
                        <div
                            class="mb-3 mt-3 flex items-center justify-between"
                        >
                            <label class="rf-label" style="margin-bottom: 0">
                                Suggested Issues
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
                            class="flex gap-2 overflow-x-auto scroll-smooth"
                        >
                            <div
                                id="issuePlaceholder"
                                class="issue-placeholder"
                            >
                                Select a location and choose or type equipment
                                to see suggestions
                            </div>
                        </div>

                        <p
                            id="issueError"
                            class="hidden"
                            style="
                                color: red;
                                font-size: 14px;
                                margin-top: -12px;
                                margin-bottom: 22px;
                            "
                        >Please select a suggested issue.</p>

                        <input
                            type="hidden"
                            id="suggestedIssueInput"
                            name="report_suggested_issue"
                        />
                    </div>

                    <!-- PROBLEM DESCRIPTION WRAPPER -->
                    <div style="margin-top: 4px">
                        <div
                            class="mb-2 flex items-center justify-between gap-4"
                        >
                            <label
                                class="rf-label"
                                style="margin-top: 12px; margin-bottom: 8px"
                            >
                                Additional Details
                            </label>

                            <span class="text-xs text-slate-400">
                                Optional
                            </span>
                        </div>

                        

                        <div style="position: relative">
                            <textarea
                                id="problemDescription"
                                name="report_problem_description"
                                rows="4"
                                placeholder="[Optional] - Provide specific details or context about the issue here..."
                                class="rf-input details-textarea"
                                style="
                                    resize: vertical;
                                    min-height: 140px;
                                    padding: 16px 40px 16px 16px;
                                    line-height: 1.6;
                                "
                                >{{
                                    old(
                                        "report_problem_description",
                                    )
                                }}</textarea
                            >

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
                        scrollbar-gutter: stable;
                    "
                >
                                    {{-- CLOSE (desktop) --}}
                    <div class="hidden justify-end lg:flex">
                        <button
                            type="button"
                            onclick="closeReportModal()"
                            class="rf-close-desktop flex h-8 w-8 items-center justify-center rounded-lg transition"
                        >
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>

                    {{-- PRIORITY LEVEL --}}
                    <div>
                        <label
                            class="rf-label"
                            style="margin-bottom: 14px; color: white"
                            >Priority Level</label
                        >

                        {{-- NON-URGENT --}}
                        <label
                            class="priority-card p-non-urgent mb-3"
                            id="card-non-urgent"
                        >
                            <input
                                type="radio"
                                name="report_urgency_level"
                                value="Non-Urgent"
                                checked
                                class="mt-1 flex-shrink-0"
                                style="accent-color: #34d399"
                                onchange="updatePriorityCards()"
                            />
                            <div>
                                <div class="priority-title">
                                    Non-Urgent
                                </div>
                                <p class="priority-desc">Minor issue or repair concern</p>
                            </div>
                        </label>

                        {{-- URGENT --}}
                        <label class="priority-card" id="card-urgent">
                            <input
                                type="radio"
                                name="report_urgency_level"
                                value="Urgent"
                                class="mt-1 flex-shrink-0"
                                style="accent-color: #ef4444"
                                onchange="updatePriorityCards()"
                            />
                            <div>
                                <div class="priority-title">
                                    Urgent
                                </div>
                                <p class="priority-desc">Immediate maintenance required</p>
                            </div>
                        </label>
                    </div>

                    {{-- UPLOAD PROOF --}}
                    <div>
                        <label
                            class="rf-label"
                            style="margin-bottom: 10px; color: white"
                            >Upload Proof Image</label
                        >

                        <label
                            for="proofImageInput"
                            class="upload-zone"
                            id="uploadZone"
                            style="position: relative"
                        >
                            <i
                                data-lucide="image-plus"
                                class="mx-auto mb-2 h-7 w-7"
                                style="color: #2947f0"
                            ></i>
                            <div
                                id="uploadLabel"
                                class="upload-label"
                                style="
                                    color: #a7aab9;
                                    font-size: 0.8rem;
                                    font-weight: 600;
                                "
                            >
                                Click to upload photo <br />
                                (Optional)
                            </div>
                            <div
                                id="removeFileBtn"
                                class="hidden"
                                style="
                                    position: absolute;
                                    top: 10px;
                                    right: 10px;
                                    z-index: 20;
                                "
                            >
                                <button
                                    type="button"
                                    onclick="
                                        event.preventDefault();
                                        removeSelectedFile();
                                    "
                                    class="flex items-center justify-center transition"
                                    style="
                                        width: 30px;
                                        height: 30px;
                                        border-radius: 999px;
                                        background: rgba(239, 68, 68, 0.15);
                                        border: 1px solid rgba(239, 68, 68, 0.3);
                                        color: #ef4444;
                                        font-size: 14px;
                                        font-weight: 700;
                                    "
                                    title="Remove file"
                                >
                                    <i data-lucide="x" class="h-4 w-4"></i>
                                </button>
                            </div>
                            <div
                                class="upload-hint"
                                style="
                                    color: #777777;
                                    font-size: 0.7rem;
                                    margin-top: 3px;
                                "
                            >
                                PNG, JPG, JPEG, WEBP up to 10MB
                            </div>
                            <input
                                type="file"
                                id="proofImageInput"
                                name="report_uploaded_image"
                                accept=".jpg,.jpeg,.png,.webp"
                                class="hidden"
                                onchange="handleFileSelect(this)"
                            />
                        </label>
                    </div>

                    {{-- ACTIONS --}}
                    <div class="mt-auto flex flex-col gap-2 pt-2">
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
                            onclick="closeReportModal()"
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
        </div>
        <div class="rf-picker-search">
            <i data-lucide="search" class="h-4 w-4" style="color:#0025cc;"></i>
            <input type="search" id="rfPickerSearch" placeholder="Search" autocomplete="off">
        </div>
        <div class="rf-picker-list" id="rfPickerList"></div>
        <button type="button" class="rf-picker-close" id="rfPickerClose">Done</button>
    </div>
</div>

<script>
    /* ── RE-RENDER ICONS ── */
    let reporterVerified = false;

    let selectedSuggestedIssue = "";

    if (typeof lucide !== "undefined") lucide.createIcons();

    /* ─────────────────────────────────────────────────────────
   PRIORITY CARD VISUAL STATE
───────────────────────────────────────────────────────── */
    function updatePriorityCards() {
        const radios = document.querySelectorAll(
            'input[name="report_urgency_level"]',
        );
        const cardNon = document.getElementById("card-non-urgent");
        const cardUrg = document.getElementById("card-urgent");

        radios.forEach((r) => {
            if (r.value === "Non-Urgent") {
                cardNon.classList.toggle("p-non-urgent", r.checked);
            }
            if (r.value === "Urgent") {
                cardUrg.classList.toggle("p-urgent", r.checked);
            }
        });
    }

    updatePriorityCards();

    /* ─────────────────────────────────────────────────────────
   SUGGESTED ISSUE AUTO-FILL
───────────────────────────────────────────────────────── */
    const descriptionTextarea = document.querySelector(
        'textarea[name="report_problem_description"]',
    );
    const clearDescriptionBtn = document.getElementById("clearDescriptionBtn");
    const clearDescriptionWrapper = document.getElementById(
        "clearDescriptionWrapper",
    );

    descriptionTextarea.addEventListener("input", updateClearButtonVisibility);

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
   FILE UPLOAD PREVIEW
───────────────────────────────────────────────────────── */
    function handleFileSelect(input) {
        const file = input.files[0];

        const label = document.getElementById("uploadLabel");

        const zone = document.getElementById("uploadZone");

        if (!file) return;

        const allowedTypes = ["image/jpeg", "image/png", "image/webp"];

        const maxSize = 10 * 1024 * 1024;

        /*
    |--------------------------------------------------------------------------
    | INVALID FILE TYPE
    |--------------------------------------------------------------------------
    */

        if (!allowedTypes.includes(file.type)) {
            alert(
                "Unsupported file format. Please upload a PNG, JPG, JPEG, or WEBP image.",
            );

            input.value = "";

            label.innerText = "Click to upload photo";

            label.style.color = "#8892a4";

            zone.classList.remove("uploaded");

            return;
        }

        /*
    |--------------------------------------------------------------------------
    | FILE TOO LARGE
    |--------------------------------------------------------------------------
    */

        if (file.size > maxSize) {
            alert(
                "File size exceeds the 10MB limit. Please upload a smaller image.",
            );

            input.value = "";

            label.innerText = "Click to upload photo";

            label.style.color = "#8892a4";

            zone.classList.remove("uploaded");

            return;
        }

        /*
    |--------------------------------------------------------------------------
    | VALID FILE
    |--------------------------------------------------------------------------
    */

        label.textContent = "🔵✓ " + file.name;

        label.style.color = "#34d399";

        zone.classList.add("uploaded");

        document.getElementById("removeFileBtn").classList.remove("hidden");

        const icon = zone.querySelector("i");

        if (icon) {
            icon.style.color = "#34d399";
        }
    }

    function removeSelectedFile() {
        const input = document.getElementById("proofImageInput");

        const label = document.getElementById("uploadLabel");

        const zone = document.getElementById("uploadZone");

        const removeBtn = document.getElementById("removeFileBtn");

        input.value = "";

        label.textContent = "Click to upload photo";

        label.style.color = "#8892a4";

        zone.classList.remove("uploaded");

        removeBtn.classList.add("hidden");

        const icon = zone.querySelector("i");

        if (icon) {
            icon.style.color = "#8892a4";
        }
    }

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

    const equipmentDropdown = document.getElementById(
        "equipmentDropdownContainer",
    );

    const equipmentManual = document.getElementById("equipmentManualContainer");

    const equipmentSelect = document.getElementById("equipmentSelect");

    const equipmentManualInput = document.getElementById(
        "equipmentManualInput",
    );

    let equipmentManualMode = false;

    let lastSelectedEquipment = "";

    toggleEquipmentBtn.addEventListener("click", function () {
        document.getElementById("equipmentError").classList.add("hidden");

        equipmentSelect.style.borderColor = "";

        equipmentManualInput.style.borderColor = "";

        equipmentManualMode = !equipmentManualMode;

        /*
        |--------------------------------------------------------------------------
        | SWITCH TO MANUAL EQUIPMENT
        |--------------------------------------------------------------------------
        */

        if (equipmentManualMode) {
            if (equipmentSelect.value) {
                lastSelectedEquipment = equipmentSelect.value;
            }

            equipmentSelect.value = "";

            equipmentDropdown.classList.add("hidden");

            equipmentManual.classList.remove("hidden");

            this.innerText = "Back to equipment list";

            document
                .querySelectorAll(".issue-btn")
                .forEach((btn) => btn.classList.remove("active"));

            loadGenericSuggestions();
        } else {
            /*
        |--------------------------------------------------------------------------
        | SWITCH BACK TO EQUIPMENT LIST
        |--------------------------------------------------------------------------
        */
            equipmentDropdown.classList.remove("hidden");

            equipmentManual.classList.add("hidden");

            equipmentManualInput.value = "";

            this.innerText = "Equipment not listed?";

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
            } else {
                issueCarousel.innerHTML = `
                    <div id="issuePlaceholder" class="issue-placeholder">
                        Select a location and choose or type equipment to see suggestions
                    </div>
                `;

                updateIssueCount();

                toggleIssueControls();
            }
        }
    });

    function showIssuePlaceholder() {
        issueCarousel.innerHTML = `
        <div id="issuePlaceholder" class="issue-placeholder">
            Select a location and choose or type equipment to see suggestions
        </div>
    `;

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

                document.getElementById("issueError").classList.add("hidden");

                selectedSuggestedIssue = newIssue;

                document.getElementById("suggestedIssueInput").value = newIssue;
                syncIssueClearUi();
            });
        });
    }

    function updateIssuePlaceholder() {
        const placeholder = document.getElementById("issuePlaceholder");

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

    // =====================================================
    // LOCK OR UNLOCK REPORT FORM HERE
    // EMPLOYEE ID AND CANCEL / CLOSE BUTTONS STAY USABLE
    // =====================================================

    function setReportFormLocked(locked) {

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
        // PRIORITY RADIOS
        // =====================================================

        const priorityRadios =
            document.querySelectorAll(
                'input[name="report_urgency_level"]'
            );


        // =====================================================
        // PROOF IMAGE
        // =====================================================

        const proofImageInput =
            document.getElementById("proofImageInput");

        const uploadZone =
            document.getElementById("uploadZone");

        const removeFileBtn =
            document.getElementById("removeFileBtn");


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

        toggleEquipmentBtn.disabled = locked;

        problemDescription.disabled = locked;

        clearDescriptionBtn.disabled = locked;

        proofImageInput.disabled = locked;

        submitReportBtn.disabled = locked;


        // =====================================================
        // DISABLE PRIORITY RADIOS HERE
        // =====================================================

        priorityRadios.forEach(function (radio) {

            radio.disabled = locked;

        });


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
        // LOCK UPLOAD ZONE HERE
        // =====================================================

        uploadZone.style.pointerEvents =
            locked ? "none" : "";

        uploadZone.style.opacity =
            locked ? "0.45" : "1";


        // =====================================================
        // LOCK REMOVE FILE BUTTON HERE
        // =====================================================

        removeFileBtn.style.pointerEvents =
            locked ? "none" : "";


        // =====================================================
        // DISABLED VISUAL STATE HERE
        // =====================================================

        const disabledElements = [

            roomSelect,

            equipmentSelect,

            equipmentManualInput,

            toggleEquipmentBtn,

            problemDescription,

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
        // PRIORITY CARD VISUAL STATE HERE
        // =====================================================

        document
            .querySelectorAll(".priority-card")
            .forEach(function (card) {

                card.style.pointerEvents =
                    locked ? "none" : "";

                card.style.opacity =
                    locked ? "0.45" : "1";

                card.style.cursor =
                    locked ? "not-allowed" : "pointer";

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
    }

    function enhanceModernSelects() {
        if (!window.matchMedia("(max-width: 767px)").matches) {
            return;
        }
        const overlay = document.getElementById("rfPickerOverlay");
        const titleEl = document.getElementById("rfPickerTitle");
        const searchEl = document.getElementById("rfPickerSearch");
        const listEl = document.getElementById("rfPickerList");
        const closeEl = document.getElementById("rfPickerClose");
        if (!overlay) return;

        let activeSelect = null;

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

        function syncTrigger(select) {
            const trigger = document.querySelector('.rf-picker-trigger[data-for="' + select.id + '"]');
            if (!trigger) return;
            const label = selectedLabel(select);
            trigger.querySelector(".rf-picker-label").textContent = label;
            trigger.classList.toggle("is-placeholder", !select.value);
            trigger.disabled = select.disabled;
        }

        function closePicker() {
            overlay.classList.remove("is-open");
            overlay.hidden = true;
            activeSelect = null;
            searchEl.value = "";
        }

        function renderList(select, query) {
            const q = (query || "").trim().toLowerCase();
            const options = Array.from(select.options);
            listEl.innerHTML = "";
            let shown = 0;

            options.forEach(function (option) {
                if (option.value === "") return;
                const text = option.textContent.trim();
                if (q && !text.toLowerCase().includes(q)) return;
                shown += 1;

                const item = document.createElement("button");
                item.type = "button";
                item.className = "rf-picker-item" + (option.selected && option.value === select.value ? " is-active" : "");
                item.innerHTML = '<span>' + text.replace(/</g, "&lt;") + '</span>';
                item.addEventListener("click", function () {
                    select.value = option.value;
                    select.dispatchEvent(new Event("change", { bubbles: true }));
                    syncTrigger(select);
                    closePicker();
                });
                listEl.appendChild(item);
            });

            if (shown === 0) {
                listEl.innerHTML = '<div class="rf-picker-empty">No matches. Try another search.</div>';
            }

            if (window.lucide) lucide.createIcons();
        }

        function openPicker(select) {
            if (select.disabled) return;
            activeSelect = select;
            titleEl.textContent = select.dataset.pickerTitle || placeholderText(select);
            searchEl.placeholder = select.dataset.pickerSearch || "Search";
            searchEl.value = "";
            overlay.hidden = false;
            overlay.classList.add("is-open");
            renderList(select, "");
            setTimeout(function () { searchEl.focus(); }, 50);
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
            trigger.innerHTML = '<span class="rf-picker-label"></span><i data-lucide="chevron-down"></i>';
            wrap.appendChild(trigger);
            syncTrigger(select);

            trigger.addEventListener("click", function () {
                openPicker(select);
            });

            new MutationObserver(function () {
                syncTrigger(select);
            }).observe(select, { childList: true, subtree: true, attributes: true });
        });

        searchEl.addEventListener("input", function () {
            if (activeSelect) renderList(activeSelect, searchEl.value);
        });

        closeEl.addEventListener("click", closePicker);
        overlay.addEventListener("click", function (e) {
            if (e.target === overlay) closePicker();
        });

        if (window.lucide) lucide.createIcons();
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

            this.style.borderColor = "";
        });

        /*
    |--------------------------------------------------------------------------
    | CLEAR LOCATION ERROR
    |--------------------------------------------------------------------------
    */
        roomSelect.addEventListener("change", function () {
            this.style.borderColor = "";

            document.getElementById("locationError").classList.add("hidden");
        });

        /*
    |--------------------------------------------------------------------------
    | CLEAR EQUIPMENT ERROR
    |--------------------------------------------------------------------------
    */
        equipSelect.addEventListener("change", function () {
            this.style.borderColor = "";

            document.getElementById("equipmentError").classList.add("hidden");
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
            });
        }

        /*
    |--------------------------------------------------------------------------
    | CLEAR SUGGESTED ISSUE ERROR
    |--------------------------------------------------------------------------
    */
        document.addEventListener("click", function (e) {
            if (e.target.classList.contains("issue-btn")) {
                document.getElementById("issueError").classList.add("hidden");
            }
        });

        // =====================================================
        // EMPLOYEE LIVE LOOKUP
        // CHECK ACTIVE, INACTIVE, OR UNKNOWN REPORTER
        // =====================================================

        const inactiveReporterBox =
            document.getElementById("inactiveReporterBox");

        const submitReportBtn =
            document.getElementById("submitReportBtn");


        // =====================================================
        // EMPLOYEE LIVE LOOKUP
        // ONLY INACTIVE REPORTERS LOCK THE FORM
        // =====================================================

        employeeInput.addEventListener("input", function () {

            const id = this.value.trim();


            // =====================================================
            // RESET CURRENT REPORTER STATE HERE
            // =====================================================

            reporterVerified = false;

            reporterBox.classList.add("hidden");

            inactiveReporterBox.classList.add("hidden");

            employeeError.style.display = "none";


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

                        employeeError.innerText =
                            "Employee ID not recognized.";

                        employeeError.style.display = "block";


                        // KEEP FORM UNLOCKED
                        setReportFormLocked(false);

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

                    employeeError.style.display = "none";


                    document.getElementById("reporterName").innerHTML = `

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="#1877F2"
                            flex-shrink="0">

                            <circle
                                cx="12"
                                cy="12"
                                r="12">
                            </circle>

                            <path
                                d="M10.2 15.3L6.9 12l1.4-1.4 1.9 1.9 5.5-5.5 1.4 1.4z"
                                fill="white">
                            </path>

                        </svg>

                        <span>
                            ${data.reporter_full_name}
                        </span>
                    `;


                    // KEEP FORM UNLOCKED
                    setReportFormLocked(false);

                })


                // =====================================================
                // LOOKUP REQUEST FAILED
                // KEEP FORM UNLOCKED
                // =====================================================

                .catch((error) => {

                    reporterVerified = false;

                    reporterBox.classList.add("hidden");

                    inactiveReporterBox.classList.add("hidden");

                    employeeError.innerText =
                        "Unable to verify Employee ID.";

                    employeeError.style.display = "block";


                    // KEEP FORM UNLOCKED
                    setReportFormLocked(false);

                });

        });

        /* ROOM → EQUIPMENT FILTER */
        roomSelect.addEventListener("change", function () {
            document.getElementById("locationError").classList.add("hidden");

            clearSuggestedIssue();

            document.getElementById("issueError").classList.add("hidden");

            lastSelectedEquipment = "";

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

                clearSuggestedIssue();

                showIssuePlaceholder();

                return;
            }
            fetch(`/get-equipment/${roomId}`)
                .then((r) => r.json())
                .then((data) => {
                    data.forEach((e) => {
                        equipSelect.innerHTML += `<option value="${e.equipment_id}">${e.equipment_name}</option>`;
                    });
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document
        .getElementById("reportForm")
        .addEventListener("submit", function (e) {
            const employeeInput = document.getElementById("employeeIdInput");

            const employeeError = document.getElementById("employeeError");

            const roomSelect = document.getElementById("roomSelect");

            const equipmentSelect = document.getElementById("equipmentSelect");

            const equipmentManualInput = document.getElementById(
                "equipmentManualInput",
            );

            const roomId = roomSelect.value;

            const equipmentId = equipmentSelect.value;

            const equipmentManual = equipmentManualInput.value.trim();

            const selectedIssue = document
                .getElementById("suggestedIssueInput")
                .value.trim();

            /*
        |--------------------------------------------------------------------------
        | RESET ALL ERRORS
        |--------------------------------------------------------------------------
        */
            document.getElementById("locationError").classList.add("hidden");

            document.getElementById("equipmentError").classList.add("hidden");

            document.getElementById("issueError").classList.add("hidden");

            employeeError.style.display = "none";

            /*
        |--------------------------------------------------------------------------
        | RESET BORDERS
        |--------------------------------------------------------------------------
        */
            roomSelect.style.borderColor = "";

            equipmentSelect.style.borderColor = "";

            if (equipmentManualInput) {
                equipmentManualInput.style.borderColor = "";
            }

            employeeInput.style.borderColor = "";

            /*
        |--------------------------------------------------------------------------
        | STEP 1 : LOCATION REQUIRED
        |--------------------------------------------------------------------------
        */
            if (!roomId) {
                document
                    .getElementById("locationError")
                    .classList.remove("hidden");

                roomSelect.style.borderColor = "#dc2626";

                roomSelect.focus();

                e.preventDefault();

                return;
            }

            /*
        |--------------------------------------------------------------------------
        | STEP 2 : EQUIPMENT REQUIRED
        |--------------------------------------------------------------------------
        */
            if (!equipmentManualMode && !equipmentId) {
                document
                    .getElementById("equipmentError")
                    .classList.remove("hidden");

                equipmentSelect.style.borderColor = "#dc2626";

                equipmentSelect.focus();

                e.preventDefault();

                return;
            }

            /*
        |--------------------------------------------------------------------------
        | STEP 3 : MANUAL EQUIPMENT REQUIRED
        |--------------------------------------------------------------------------
        */
            if (equipmentManualMode && equipmentManual === "") {
                document
                    .getElementById("equipmentError")
                    .classList.remove("hidden");

                equipmentManualInput.style.borderColor = "#dc2626";

                equipmentManualInput.focus();

                e.preventDefault();

                return;
            }

            /*
        |--------------------------------------------------------------------------
        | STEP 4 : SUGGESTED ISSUE REQUIRED
        |--------------------------------------------------------------------------
        */
            if (selectedIssue === "") {
                document
                    .getElementById("issueError")
                    .classList.remove("hidden");

                e.preventDefault();

                return;
            }

            /*
        |--------------------------------------------------------------------------
        | STEP 5 : EMPLOYEE ID VALIDATION
        |--------------------------------------------------------------------------
        */
            if (!reporterVerified) {
                employeeError.innerText = "Employee ID not recognized.";

                employeeError.style.display = "block";

                employeeInput.style.borderColor = "#dc2626";

                employeeInput.focus();

                e.preventDefault();

                return;
            }

            /*
        |--------------------------------------------------------------------------
        | LOADING MODAL
        |--------------------------------------------------------------------------
        */
            Swal.fire({

                // =====================================================
                // CONTENT
                // =====================================================

                title: 'Submitting report',

                html: `
                    <div class="swal-submitting-message">
                        Processing your maintenance report...
                    </div>
                `,


                // =====================================================
                // BEHAVIOR
                // =====================================================

                allowOutsideClick: false,

                allowEscapeKey: false,

                showConfirmButton: false,


                // =====================================================
                // APPEARANCE
                // =====================================================

                background: '#ffffff',

                color: '#111827',

                width: '420px',

                padding: '1.75rem',

                backdrop: `
                    rgba(15, 23, 42, 0.35)
                `,


                // =====================================================
                // CUSTOM CLASSES
                // =====================================================

                customClass: {

                    popup: 'modern-submitting-popup',

                    title: 'modern-submitting-title',

                    htmlContainer: 'modern-submitting-content',

                    loader: 'modern-submitting-loader',

                },


                // =====================================================
                // SHOW LOADING INDICATOR
                // =====================================================

                didOpen: () => {

                    Swal.showLoading();

                },

            });
        });
</script>
