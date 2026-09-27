{{-- Admin theme: slate base + soft light blue & yellow accents --}}
<style>
    :root {
        --admin-brand: #475569;
        --admin-brand-soft: #5b6b7c;
        --admin-brand-hover: #334155;
        --admin-accent-soft: #eff6ff;
        --admin-accent-mid: #dbeafe;
        --admin-accent-ink: #3b82f6;
        --admin-accent-ink-strong: #1e40af;
        --admin-yellow-soft: #fffbeb;
        --admin-yellow-mid: #fef3c7;
        --admin-yellow-ink: #d97706;
        --mp-toast-blue: #60a5fa;
    }

    body.pp-layout {
        --mp-toast-blue: #60a5fa;
    }

    .admin-btn-primary {
        background: #64748b !important;
    }
    .admin-btn-primary:hover {
        background: #475569 !important;
    }

    .admin-stat-card-icon {
        background: var(--admin-accent-soft) !important;
        color: #3b82f6 !important;
    }

    /*
     | Pages that render an .admin-keep-colors element inside <main> opt out
     | of the remaps below and keep their original (Maintenance) colours.
     |
     | Soft accent remap:
     | - blues / skies  → light blue
     | - ambers / yellows → soft yellow
     | - other chromatics stay muted slate
     */
    main:not(:has(.admin-keep-colors)) .bg-sky-50, main:not(:has(.admin-keep-colors)) .bg-blue-50, main:not(:has(.admin-keep-colors)) .bg-indigo-50, main:not(:has(.admin-keep-colors)) .bg-cyan-50,
    main:not(:has(.admin-keep-colors)) .bg-sky-100, main:not(:has(.admin-keep-colors)) .bg-blue-100, main:not(:has(.admin-keep-colors)) .bg-indigo-100, main:not(:has(.admin-keep-colors)) .bg-cyan-100,
    #sidebar .bg-sky-50, #sidebar .bg-blue-50, #sidebar .bg-indigo-50 {
        background-color: #eff6ff !important;
    }

    main:not(:has(.admin-keep-colors)) .bg-amber-50, main:not(:has(.admin-keep-colors)) .bg-yellow-50, main:not(:has(.admin-keep-colors)) .bg-orange-50,
    main:not(:has(.admin-keep-colors)) .bg-amber-100, main:not(:has(.admin-keep-colors)) .bg-yellow-100, main:not(:has(.admin-keep-colors)) .bg-orange-100,
    #sidebar .bg-amber-50 {
        background-color: #fffbeb !important;
    }

    main:not(:has(.admin-keep-colors)) .bg-emerald-50, main:not(:has(.admin-keep-colors)) .bg-green-50, main:not(:has(.admin-keep-colors)) .bg-teal-50, main:not(:has(.admin-keep-colors)) .bg-lime-50,
    main:not(:has(.admin-keep-colors)) .bg-rose-50, main:not(:has(.admin-keep-colors)) .bg-red-50, main:not(:has(.admin-keep-colors)) .bg-pink-50,
    main:not(:has(.admin-keep-colors)) .bg-violet-50, main:not(:has(.admin-keep-colors)) .bg-purple-50,
    main:not(:has(.admin-keep-colors)) .bg-emerald-100, main:not(:has(.admin-keep-colors)) .bg-green-100, main:not(:has(.admin-keep-colors)) .bg-teal-100,
    main:not(:has(.admin-keep-colors)) .bg-rose-100, main:not(:has(.admin-keep-colors)) .bg-red-100, main:not(:has(.admin-keep-colors)) .bg-pink-100,
    main:not(:has(.admin-keep-colors)) .bg-violet-100, main:not(:has(.admin-keep-colors)) .bg-purple-100,
    #sidebar .bg-emerald-50, #sidebar .bg-rose-50 {
        background-color: #f1f5f9 !important;
    }

    main:not(:has(.admin-keep-colors)) .hover\:bg-sky-100:hover, main:not(:has(.admin-keep-colors)) .hover\:bg-blue-100:hover, main:not(:has(.admin-keep-colors)) .hover\:bg-indigo-100:hover {
        background-color: #dbeafe !important;
    }
    main:not(:has(.admin-keep-colors)) .hover\:bg-amber-100:hover {
        background-color: #fef3c7 !important;
    }
    main:not(:has(.admin-keep-colors)) .hover\:bg-emerald-100:hover, main:not(:has(.admin-keep-colors)) .hover\:bg-rose-100:hover,
    main:not(:has(.admin-keep-colors)) .hover\:bg-green-100:hover, main:not(:has(.admin-keep-colors)) .hover\:bg-red-100:hover {
        background-color: #e2e8f0 !important;
    }

    /* Solid CTAs stay slate (except soft sky for primary actions) */
    main:not(:has(.admin-keep-colors)) .bg-amber-600, main:not(:has(.admin-keep-colors)) .bg-amber-700, main:not(:has(.admin-keep-colors)) .bg-yellow-500, main:not(:has(.admin-keep-colors)) .bg-orange-500,
    main:not(:has(.admin-keep-colors)) .bg-emerald-600, main:not(:has(.admin-keep-colors)) .bg-emerald-700, main:not(:has(.admin-keep-colors)) .bg-green-600, main:not(:has(.admin-keep-colors)) .bg-green-700, main:not(:has(.admin-keep-colors)) .bg-teal-600,
    main:not(:has(.admin-keep-colors)) .bg-rose-600, main:not(:has(.admin-keep-colors)) .bg-rose-700, main:not(:has(.admin-keep-colors)) .bg-red-600, main:not(:has(.admin-keep-colors)) .bg-red-700,
    main:not(:has(.admin-keep-colors)) .bg-blue-600, main:not(:has(.admin-keep-colors)) .bg-blue-700,
    main:not(:has(.admin-keep-colors)) .bg-indigo-600, main:not(:has(.admin-keep-colors)) .bg-indigo-700, main:not(:has(.admin-keep-colors)) .bg-violet-600, main:not(:has(.admin-keep-colors)) .bg-purple-600,
    main:not(:has(.admin-keep-colors)) .bg-cyan-600 {
        background-color: #475569 !important;
    }

    main:not(:has(.admin-keep-colors)) .hover\:bg-emerald-700:hover, main:not(:has(.admin-keep-colors)) .hover\:bg-emerald-800:hover,
    main:not(:has(.admin-keep-colors)) .hover\:bg-rose-700:hover, main:not(:has(.admin-keep-colors)) .hover\:bg-blue-700:hover,
    main:not(:has(.admin-keep-colors)) .hover\:bg-indigo-700:hover, main:not(:has(.admin-keep-colors)) .hover\:bg-amber-700:hover,
    main:not(:has(.admin-keep-colors)) .hover\:bg-green-700:hover {
        background-color: #334155 !important;
    }

    main:not(:has(.admin-keep-colors)) .text-sky-500, main:not(:has(.admin-keep-colors)) .text-sky-600, main:not(:has(.admin-keep-colors)) .text-sky-700,
    main:not(:has(.admin-keep-colors)) .text-blue-500, main:not(:has(.admin-keep-colors)) .text-blue-600, main:not(:has(.admin-keep-colors)) .text-blue-700, main:not(:has(.admin-keep-colors)) .text-blue-800,
    main:not(:has(.admin-keep-colors)) .text-indigo-500, main:not(:has(.admin-keep-colors)) .text-indigo-600, main:not(:has(.admin-keep-colors)) .text-indigo-700,
    main:not(:has(.admin-keep-colors)) .text-cyan-500, main:not(:has(.admin-keep-colors)) .text-cyan-600 {
        color: #3b82f6 !important;
    }

    main:not(:has(.admin-keep-colors)) .text-amber-500, main:not(:has(.admin-keep-colors)) .text-amber-600, main:not(:has(.admin-keep-colors)) .text-amber-700, main:not(:has(.admin-keep-colors)) .text-amber-800, main:not(:has(.admin-keep-colors)) .text-amber-900,
    main:not(:has(.admin-keep-colors)) .text-yellow-500, main:not(:has(.admin-keep-colors)) .text-yellow-600, main:not(:has(.admin-keep-colors)) .text-yellow-700,
    main:not(:has(.admin-keep-colors)) .text-orange-500, main:not(:has(.admin-keep-colors)) .text-orange-600 {
        color: #d97706 !important;
    }

    main:not(:has(.admin-keep-colors)) .text-emerald-500, main:not(:has(.admin-keep-colors)) .text-emerald-600, main:not(:has(.admin-keep-colors)) .text-emerald-700, main:not(:has(.admin-keep-colors)) .text-emerald-800, main:not(:has(.admin-keep-colors)) .text-emerald-900,
    main:not(:has(.admin-keep-colors)) .text-green-500, main:not(:has(.admin-keep-colors)) .text-green-600, main:not(:has(.admin-keep-colors)) .text-green-700, main:not(:has(.admin-keep-colors)) .text-teal-500, main:not(:has(.admin-keep-colors)) .text-teal-600, main:not(:has(.admin-keep-colors)) .text-teal-700,
    main:not(:has(.admin-keep-colors)) .text-rose-500, main:not(:has(.admin-keep-colors)) .text-rose-600, main:not(:has(.admin-keep-colors)) .text-rose-700, main:not(:has(.admin-keep-colors)) .text-rose-800, main:not(:has(.admin-keep-colors)) .text-rose-900,
    main:not(:has(.admin-keep-colors)) .text-red-500, main:not(:has(.admin-keep-colors)) .text-red-600, main:not(:has(.admin-keep-colors)) .text-red-700,
    main:not(:has(.admin-keep-colors)) .text-violet-500, main:not(:has(.admin-keep-colors)) .text-violet-600, main:not(:has(.admin-keep-colors)) .text-purple-500, main:not(:has(.admin-keep-colors)) .text-purple-600,
    main:not(:has(.admin-keep-colors)) .text-pink-500, main:not(:has(.admin-keep-colors)) .text-pink-600 {
        color: #475569 !important;
    }

    main:not(:has(.admin-keep-colors)) .hover\:text-blue-700:hover, main:not(:has(.admin-keep-colors)) .hover\:text-sky-700:hover {
        color: #2563eb !important;
    }
    main:not(:has(.admin-keep-colors)) .hover\:text-amber-700:hover {
        color: #b45309 !important;
    }
    main:not(:has(.admin-keep-colors)) .hover\:text-emerald-700:hover, main:not(:has(.admin-keep-colors)) .hover\:text-rose-700:hover {
        color: #0f172a !important;
    }

    main:not(:has(.admin-keep-colors)) .border-sky-200, main:not(:has(.admin-keep-colors)) .border-blue-200, main:not(:has(.admin-keep-colors)) .border-indigo-200, main:not(:has(.admin-keep-colors)) .border-cyan-200 {
        border-color: #bfdbfe !important;
    }
    main:not(:has(.admin-keep-colors)) .border-amber-200, main:not(:has(.admin-keep-colors)) .border-amber-300, main:not(:has(.admin-keep-colors)) .border-yellow-200, main:not(:has(.admin-keep-colors)) .border-yellow-300, main:not(:has(.admin-keep-colors)) .border-orange-200 {
        border-color: #fde68a !important;
    }
    main:not(:has(.admin-keep-colors)) .border-emerald-200, main:not(:has(.admin-keep-colors)) .border-emerald-300, main:not(:has(.admin-keep-colors)) .border-green-200, main:not(:has(.admin-keep-colors)) .border-teal-200,
    main:not(:has(.admin-keep-colors)) .border-rose-200, main:not(:has(.admin-keep-colors)) .border-rose-300, main:not(:has(.admin-keep-colors)) .border-red-200, main:not(:has(.admin-keep-colors)) .border-pink-200,
    main:not(:has(.admin-keep-colors)) .border-violet-200, main:not(:has(.admin-keep-colors)) .border-purple-200 {
        border-color: #e2e8f0 !important;
    }

    main:not(:has(.admin-keep-colors)) .ring-sky-200, main:not(:has(.admin-keep-colors)) .ring-blue-200, main:not(:has(.admin-keep-colors)) .ring-indigo-200 {
        --tw-ring-color: rgba(147, 197, 253, 0.55) !important;
    }
    main:not(:has(.admin-keep-colors)) .ring-amber-200 {
        --tw-ring-color: rgba(253, 230, 138, 0.65) !important;
    }
    main:not(:has(.admin-keep-colors)) .ring-emerald-200, main:not(:has(.admin-keep-colors)) .ring-rose-200 {
        --tw-ring-color: rgba(148, 163, 184, 0.45) !important;
    }

    main:not(:has(.admin-keep-colors)) .border-green-200, main:not(:has(.admin-keep-colors)) .border-emerald-200 {
        border-color: #e2e8f0 !important;
    }
    main:not(:has(.admin-keep-colors)) .bg-green-50 {
        background-color: #f8fafc !important;
    }
    main:not(:has(.admin-keep-colors)) .text-green-700 {
        color: #334155 !important;
    }

    /* Sidebar accents */
    #sidebar .quick-card:hover {
        border-color: #93c5fd !important;
    }
    #sidebar .quick-card i {
        color: #93c5fd !important;
    }
    #sidebar .quick-card.active {
        border-color: #60a5fa !important;
        box-shadow: 0 0 12px rgba(96, 165, 250, 0.22) !important;
    }
    #sidebar .quick-card.active i {
        color: #93c5fd !important;
    }
    #sidebar .menu-notif-dot {
        background: #fbbf24 !important;
    }
    #sidebar .menu-item.active svg {
        color: #fde68a !important;
        stroke: #fde68a !important;
    }

    /* Dashboard icons: blue family + amber family */
    main:not(:has(.admin-keep-colors)) .stat-icon-blue,
    main:not(:has(.admin-keep-colors)) .stat-icon-indigo,
    main:not(:has(.admin-keep-colors)) .stat-icon-sky,
    main:not(:has(.admin-keep-colors)) .stat-icon-violet {
        background: #eff6ff !important;
        color: #3b82f6 !important;
    }
    main:not(:has(.admin-keep-colors)) .stat-icon-amber {
        background: #fffbeb !important;
        color: #d97706 !important;
    }
    main:not(:has(.admin-keep-colors)) .stat-icon-teal,
    main:not(:has(.admin-keep-colors)) .stat-icon-rose {
        background: #f1f5f9 !important;
        color: #475569 !important;
    }
    main:not(:has(.admin-keep-colors)) .stat-change-up {
        background: #eff6ff !important;
        color: #3b82f6 !important;
    }
    main:not(:has(.admin-keep-colors)) .stat-change-warn {
        background: #fffbeb !important;
        color: #d97706 !important;
    }
    main:not(:has(.admin-keep-colors)) .sidebar-dot-blue,
    main:not(:has(.admin-keep-colors)) .sidebar-dot-slate,
    main:not(:has(.admin-keep-colors)) .sidebar-dot-violet,
    main:not(:has(.admin-keep-colors)) .stat-dot-purple,
    main:not(:has(.admin-keep-colors)) .stat-dot-cyan {
        background: #60a5fa !important;
    }
    main:not(:has(.admin-keep-colors)) .sidebar-dot-amber,
    main:not(:has(.admin-keep-colors)) .stat-dot-amber {
        background: #fbbf24 !important;
    }
    main:not(:has(.admin-keep-colors)) .sidebar-dot-emerald,
    main:not(:has(.admin-keep-colors)) .sidebar-dot-teal,
    main:not(:has(.admin-keep-colors)) .sidebar-dot-rose,
    main:not(:has(.admin-keep-colors)) .stat-dot-emerald,
    main:not(:has(.admin-keep-colors)) .stat-dot-rose {
        background: #94a3b8 !important;
    }
    main:not(:has(.admin-keep-colors)) .admin-attention-row-blue {
        border-left-color: #93c5fd !important;
        background: #f8fbff !important;
    }
    main:not(:has(.admin-keep-colors)) .admin-attention-row-yellow {
        border-left-color: #fde68a !important;
        background: #fffdf5 !important;
    }

    main:not(:has(.admin-keep-colors)) .text-\[\#0037c7\],
    main:not(:has(.admin-keep-colors)) .hover\:text-\[\#0037c7\]:hover,
    main:not(:has(.admin-keep-colors)) a.text-blue-600 {
        color: #3b82f6 !important;
    }
    main:not(:has(.admin-keep-colors)) .bg-\[\#0037c7\],
    main:not(:has(.admin-keep-colors)) .border-\[\#0037c7\] {
        background-color: #64748b !important;
        border-color: #64748b !important;
    }
    main:not(:has(.admin-keep-colors)) .focus\:border-\[\#0037c7\]:focus,
    main:not(:has(.admin-keep-colors)) .focus\:ring-\[\#0037c7\]:focus {
        border-color: #93c5fd !important;
        --tw-ring-color: rgba(147, 197, 253, 0.45) !important;
    }
    /* Allow soft sky/amber solid buttons for primary actions */
    main:not(:has(.admin-keep-colors)) .bg-sky-600 {
        background-color: #0ea5e9 !important;
    }
    main:not(:has(.admin-keep-colors)) .hover\:bg-sky-700:hover {
        background-color: #0284c7 !important;
    }
    main:not(:has(.admin-keep-colors)) .hover\:bg-sky-100:hover {
        background-color: #e0f2fe !important;
    }
    main:not(:has(.admin-keep-colors)) .text-sky-700 {
        color: #0369a1 !important;
    }
    main:not(:has(.admin-keep-colors)) .border-sky-200 {
        border-color: #bae6fd !important;
    }
    main:not(:has(.admin-keep-colors)) .bg-blue-500, main:not(:has(.admin-keep-colors)) .bg-sky-500 {
        background-color: #60a5fa !important;
    }
    main:not(:has(.admin-keep-colors)) .bg-amber-500 {
        background-color: #fbbf24 !important;
    }
    main:not(:has(.admin-keep-colors)) .bg-emerald-500, main:not(:has(.admin-keep-colors)) .bg-rose-500, main:not(:has(.admin-keep-colors)) .bg-green-500 {
        background-color: #94a3b8 !important;
    }
</style>
