{{-- Shared Admin design tokens aligned with Maintenance Personnel --}}
<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800;900&display=swap"
    rel="stylesheet"
/>
<style>
    :root {
        --admin-brand: #0037c7;
        --admin-brand-soft: rgba(0, 55, 199, 0.85);
        --admin-brand-hover: rgba(0, 44, 155, 0.85);
        --admin-page-bg: #f1f5f9;
        --admin-card-radius: 18px;
        --admin-section-gap: 24px;
    }

    /* Scope to admin main content area — beat global Poppins * rule */
    #main-content,
    #main-content *,
    .admin-page,
    .admin-page *,
    .admin-dashboard,
    .admin-dashboard * {
        font-family: "Inter", sans-serif;
    }

    .admin-page-title,
    .admin-dashboard .dashboard-title,
    .admin-stat-card-value,
    .page-header h1 {
        font-family: "Outfit", sans-serif;
    }

    /* Page header pattern (matches MP equipment/index) */
    .admin-page-title {
        font-size: 2.25rem;
        line-height: 1.1;
        font-weight: 900;
        color: #0f172a;
        letter-spacing: -0.02em;
    }

    .admin-page-subtitle {
        margin-top: 0.25rem;
        font-size: 0.875rem;
        color: #64748b;
    }

    /* Primary CTA */
    .admin-btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border-radius: 0.75rem;
        background: var(--admin-brand-soft);
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: #fff;
        transition: background 0.15s ease;
    }

    .admin-btn-primary:hover {
        background: var(--admin-brand-hover);
    }

    /* White content card */
    .admin-surface-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: var(--admin-card-radius);
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
    }

    /* Compact horizontal stat card (MP style) */
    .admin-stat-card {
        min-width: 0;
        height: 76px;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
        transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
    }

    .admin-stat-card:hover {
        transform: translateY(-1px);
        border-color: #d1d5db;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
    }

    .admin-stat-card-icon {
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        background: #eff6ff;
        color: var(--admin-brand);
    }

    .admin-stat-card-label {
        font-size: 0.75rem;
        font-weight: 500;
        color: #64748b;
    }

    .admin-stat-card-value {
        font-family: "Outfit", sans-serif;
        font-size: 1.5rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.1;
    }
    /* Legacy page-header stubs used by some admin screens */
    .page-header h1 {
        font-family: "Outfit", sans-serif;
        font-size: 2.25rem;
        line-height: 1.1;
        font-weight: 900;
        color: #0f172a;
        letter-spacing: -0.02em;
    }

    .page-header p {
        margin-top: 0.25rem;
        font-size: 0.875rem;
        color: #64748b;
    }
</style>
