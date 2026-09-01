@once
<style>
    .pr-module { width: 100%; max-width: 100%; }
    .pr-hero { margin-bottom: 1.5rem; }
    .pr-hero-kicker {
        font-size: 0.6875rem;
        font-weight: 600;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #94a3b8;
    }
    .pr-hero-title {
        margin-top: 0.35rem;
        font-size: 1.5rem;
        font-weight: 700;
        letter-spacing: -0.025em;
        color: #0f172a;
        line-height: 1.2;
    }
    .pr-hero-sub {
        margin-top: 0.4rem;
        font-size: 0.875rem;
        line-height: 1.55;
        color: #64748b;
        max-width: 48rem;
    }

    .pr-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.25rem;
        align-items: start;
    }
    @media (min-width: 1024px) {
        .pr-grid--split { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    .pr-grid .pr-section { margin-bottom: 0; }

    .pr-surface {
        background: #fff;
        border: 1px solid #e8edf3;
        border-radius: 1rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        overflow: hidden;
    }

    .pr-list { display: flex; flex-direction: column; }
    .pr-list-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.25rem;
        text-decoration: none;
        color: inherit;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.15s ease;
    }
    .pr-list-row:last-child { border-bottom: 0; }
    .pr-list-row:hover { background: #f8fafc; }
    .pr-list-main { min-width: 0; flex: 1; }
    .pr-list-ref {
        font-size: 0.9375rem;
        font-weight: 600;
        color: #0f172a;
        letter-spacing: -0.01em;
    }
    .pr-list-meta {
        margin-top: 0.25rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem 0.75rem;
        font-size: 0.75rem;
        color: #64748b;
    }
    .pr-list-meta span { display: inline-flex; align-items: center; gap: 0.3rem; }
    .pr-list-action {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: #2563eb;
    }
    .pr-list-action svg { width: 1rem; height: 1rem; opacity: 0.7; }

    .pr-empty {
        padding: 3.5rem 1.5rem;
        text-align: center;
    }
    .pr-empty-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 3rem;
        height: 3rem;
        border-radius: 999px;
        background: #f8fafc;
        color: #94a3b8;
        margin-bottom: 0.875rem;
    }
    .pr-empty-title {
        font-size: 0.9375rem;
        font-weight: 600;
        color: #334155;
    }
    .pr-empty-sub {
        margin-top: 0.25rem;
        font-size: 0.8125rem;
        color: #94a3b8;
    }

    .pr-detail-head {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem 1.25rem;
        margin-bottom: 1.25rem;
    }
    .pr-back {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #64748b;
        transition: border-color 0.15s ease, color 0.15s ease, background 0.15s ease;
        text-decoration: none;
    }
    .pr-back:hover {
        border-color: #cbd5e1;
        color: #0f172a;
        background: #f8fafc;
    }
    .pr-back svg { width: 1rem; height: 1rem; }

    .pr-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 0.2rem 0.65rem;
        font-size: 0.6875rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        background: #f1f5f9;
        color: #475569;
    }
    .pr-badge--blue { background: #eff6ff; color: #1d4ed8; }
    .pr-badge--green { background: #ecfdf5; color: #047857; }
    .pr-badge--amber { background: #fffbeb; color: #b45309; }

    .pr-checklist { padding: 1.25rem 1.35rem 1.1rem; }
    .pr-checklist-head {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        justify-content: space-between;
        gap: 0.75rem 1rem;
        margin-bottom: 1rem;
    }
    .pr-checklist-title {
        font-size: 0.875rem;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.01em;
    }
    .pr-checklist-sub {
        margin-top: 0.15rem;
        font-size: 0.75rem;
        color: #94a3b8;
    }
    .pr-checklist-count {
        font-size: 0.75rem;
        font-weight: 600;
        color: #64748b;
        white-space: nowrap;
    }
    .pr-progress {
        height: 3px;
        border-radius: 999px;
        background: #f1f5f9;
        overflow: hidden;
        margin-bottom: 0.25rem;
    }
    .pr-progress-bar {
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #2563eb, #3b82f6);
        transition: width 0.3s ease;
    }

    .pr-doc-list { display: flex; flex-direction: column; }
    .pr-doc-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem 1rem;
        padding: 0.9rem 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .pr-doc-row:last-child { border-bottom: 0; padding-bottom: 0; }
    .pr-doc-row:first-child { padding-top: 0.35rem; }

    .pr-doc-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
        flex: 1;
    }
    .pr-doc-icon {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 0.65rem;
        background: #f8fafc;
        color: #64748b;
    }
    .pr-doc-icon.is-ready { background: #ecfdf5; color: #059669; }
    .pr-doc-icon svg { width: 1rem; height: 1rem; }

    .pr-doc-label {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #0f172a;
        line-height: 1.35;
    }
    .pr-doc-number {
        display: block;
        margin-top: 0.1rem;
        font-size: 0.75rem;
        font-weight: 500;
        color: #94a3b8;
    }

    .pr-doc-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.35rem;
    }
    .pr-doc-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.35rem 0.7rem;
        border-radius: 0.55rem;
        border: 1px solid #e8edf3;
        background: #fff;
        font-size: 0.6875rem;
        font-weight: 600;
        color: #475569;
        text-decoration: none;
        transition: border-color 0.15s ease, color 0.15s ease, background 0.15s ease;
    }
    .pr-doc-btn:hover {
        border-color: #cbd5e1;
        color: #0f172a;
        background: #f8fafc;
    }
    .pr-doc-btn--primary {
        border-color: #dbeafe;
        color: #2563eb;
        background: #f8fbff;
    }
    .pr-doc-btn--primary:hover {
        border-color: #bfdbfe;
        color: #1d4ed8;
        background: #eff6ff;
    }
    .pr-doc-btn svg { width: 0.8rem; height: 0.8rem; }

    .pr-doc-empty {
        font-size: 0.75rem;
        color: #cbd5e1;
        font-style: italic;
    }

    .pr-forward-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.55rem 1rem;
        border-radius: 0.75rem;
        border: 0;
        background: #2563eb;
        color: #fff;
        font-size: 0.8125rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.15s ease, transform 0.15s ease;
    }
    .pr-forward-btn:hover { background: #1d4ed8; }
    .pr-forward-btn:active { transform: scale(0.98); }
    .pr-forward-btn svg { width: 1rem; height: 1rem; }

    .pr-section { margin-bottom: 1.25rem; }
    .pr-section-head {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .pr-section-title {
        font-size: 0.875rem;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.01em;
    }
    .pr-section-sub {
        margin-top: 0.15rem;
        font-size: 0.75rem;
        color: #94a3b8;
    }

    .pr-compile-list { display: flex; flex-direction: column; }
    .pr-compile-card {
        padding: 1.15rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .pr-compile-card:last-child { border-bottom: 0; }
    .pr-compile-head {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem 1rem;
    }
    .pr-compile-ref {
        font-size: 0.9375rem;
        font-weight: 600;
        color: #0f172a;
        letter-spacing: -0.01em;
    }
    .pr-compile-meta {
        margin-top: 0.2rem;
        font-size: 0.75rem;
        color: #64748b;
    }
    .pr-submit-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 0.9rem;
        border-radius: 0.65rem;
        border: 0;
        background: #2563eb;
        color: #fff;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.15s ease, transform 0.15s ease;
    }
    .pr-submit-btn:hover { background: #1d4ed8; }
    .pr-submit-btn:active { transform: scale(0.98); }
    .pr-submit-btn svg { width: 0.9rem; height: 0.9rem; }

    .pr-inline-checklist {
        margin-top: 0.85rem;
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
    }
    .pr-inline-check {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        font-size: 0.8125rem;
        color: #475569;
        text-decoration: none;
    }
    a.pr-inline-check:hover { color: #2563eb; }
    .pr-inline-check .pr-check-dot {
        flex-shrink: 0;
        width: 1.1rem;
        height: 1.1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #f1f5f9;
        color: #cbd5e1;
    }
    .pr-inline-check.is-ready .pr-check-dot {
        background: #ecfdf5;
        color: #059669;
    }
    .pr-inline-check .pr-check-dot svg { width: 0.7rem; height: 0.7rem; }

    .pr-list-row--static {
        cursor: default;
    }
    .pr-list-row--static:hover { background: transparent; }

    @media (max-width: 640px) {
        .pr-list-row { align-items: flex-start; flex-direction: column; }
        .pr-doc-row { align-items: flex-start; }
        .pr-doc-actions { width: 100%; }
    }
</style>
@endonce
