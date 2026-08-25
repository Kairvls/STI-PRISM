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
     | Soft accent remap:
     | - blues / skies  → light blue
     | - ambers / yellows → soft yellow
     | - other chromatics stay muted slate
     */
    main .bg-sky-50, main .bg-blue-50, main .bg-indigo-50, main .bg-cyan-50,
    main .bg-sky-100, main .bg-blue-100, main .bg-indigo-100, main .bg-cyan-100,
    #sidebar .bg-sky-50, #sidebar .bg-blue-50, #sidebar .bg-indigo-50 {
        background-color: #eff6ff !important;
    }

    main .bg-amber-50, main .bg-yellow-50, main .bg-orange-50,
    main .bg-amber-100, main .bg-yellow-100, main .bg-orange-100,
    #sidebar .bg-amber-50 {
        background-color: #fffbeb !important;
    }

    main .bg-emerald-50, main .bg-green-50, main .bg-teal-50, main .bg-lime-50,
    main .bg-rose-50, main .bg-red-50, main .bg-pink-50,
    main .bg-violet-50, main .bg-purple-50,
    main .bg-emerald-100, main .bg-green-100, main .bg-teal-100,
    main .bg-rose-100, main .bg-red-100, main .bg-pink-100,
    main .bg-violet-100, main .bg-purple-100,
    #sidebar .bg-emerald-50, #sidebar .bg-rose-50 {
        background-color: #f1f5f9 !important;
    }

    main .hover\:bg-sky-100:hover, main .hover\:bg-blue-100:hover, main .hover\:bg-indigo-100:hover {
        background-color: #dbeafe !important;
    }
    main .hover\:bg-amber-100:hover {
        background-color: #fef3c7 !important;
    }
    main .hover\:bg-emerald-100:hover, main .hover\:bg-rose-100:hover,
    main .hover\:bg-green-100:hover, main .hover\:bg-red-100:hover {
        background-color: #e2e8f0 !important;
    }

    /* Solid CTAs stay slate (except soft sky for primary actions) */
    main .bg-amber-600, main .bg-amber-700, main .bg-yellow-500, main .bg-orange-500,
    main .bg-emerald-600, main .bg-emerald-700, main .bg-green-600, main .bg-green-700, main .bg-teal-600,
    main .bg-rose-600, main .bg-rose-700, main .bg-red-600, main .bg-red-700,
    main .bg-blue-600, main .bg-blue-700,
    main .bg-indigo-600, main .bg-indigo-700, main .bg-violet-600, main .bg-purple-600,
    main .bg-cyan-600 {
        background-color: #475569 !important;
    }

    main .hover\:bg-emerald-700:hover, main .hover\:bg-emerald-800:hover,
    main .hover\:bg-rose-700:hover, main .hover\:bg-blue-700:hover,
    main .hover\:bg-indigo-700:hover, main .hover\:bg-amber-700:hover,
    main .hover\:bg-green-700:hover {
        background-color: #334155 !important;
    }

    main .text-sky-500, main .text-sky-600, main .text-sky-700,
    main .text-blue-500, main .text-blue-600, main .text-blue-700, main .text-blue-800,
    main .text-indigo-500, main .text-indigo-600, main .text-indigo-700,
    main .text-cyan-500, main .text-cyan-600 {
        color: #3b82f6 !important;
    }

    main .text-amber-500, main .text-amber-600, main .text-amber-700, main .text-amber-800, main .text-amber-900,
    main .text-yellow-500, main .text-yellow-600, main .text-yellow-700,
    main .text-orange-500, main .text-orange-600 {
        color: #d97706 !important;
    }

    main .text-emerald-500, main .text-emerald-600, main .text-emerald-700, main .text-emerald-800, main .text-emerald-900,
    main .text-green-500, main .text-green-600, main .text-green-700, main .text-teal-500, main .text-teal-600, main .text-teal-700,
    main .text-rose-500, main .text-rose-600, main .text-rose-700, main .text-rose-800, main .text-rose-900,
    main .text-red-500, main .text-red-600, main .text-red-700,
    main .text-violet-500, main .text-violet-600, main .text-purple-500, main .text-purple-600,
    main .text-pink-500, main .text-pink-600 {
        color: #475569 !important;
    }

    main .hover\:text-blue-700:hover, main .hover\:text-sky-700:hover {
        color: #2563eb !important;
    }
    main .hover\:text-amber-700:hover {
        color: #b45309 !important;
    }
    main .hover\:text-emerald-700:hover, main .hover\:text-rose-700:hover {
        color: #0f172a !important;
    }

    main .border-sky-200, main .border-blue-200, main .border-indigo-200, main .border-cyan-200 {
        border-color: #bfdbfe !important;
    }
    main .border-amber-200, main .border-amber-300, main .border-yellow-200, main .border-yellow-300, main .border-orange-200 {
        border-color: #fde68a !important;
    }
    main .border-emerald-200, main .border-emerald-300, main .border-green-200, main .border-teal-200,
    main .border-rose-200, main .border-rose-300, main .border-red-200, main .border-pink-200,
    main .border-violet-200, main .border-purple-200 {
        border-color: #e2e8f0 !important;
    }

    main .ring-sky-200, main .ring-blue-200, main .ring-indigo-200 {
        --tw-ring-color: rgba(147, 197, 253, 0.55) !important;
    }
    main .ring-amber-200 {
        --tw-ring-color: rgba(253, 230, 138, 0.65) !important;
    }
    main .ring-emerald-200, main .ring-rose-200 {
        --tw-ring-color: rgba(148, 163, 184, 0.45) !important;
    }

    main .border-green-200, main .border-emerald-200 {
        border-color: #e2e8f0 !important;
    }
    main .bg-green-50 {
        background-color: #f8fafc !important;
    }
    main .text-green-700 {
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
    main .stat-icon-blue,
    main .stat-icon-indigo,
    main .stat-icon-sky,
    main .stat-icon-violet {
        background: #eff6ff !important;
        color: #3b82f6 !important;
    }
    main .stat-icon-amber {
        background: #fffbeb !important;
        color: #d97706 !important;
    }
    main .stat-icon-teal,
    main .stat-icon-rose {
        background: #f1f5f9 !important;
        color: #475569 !important;
    }
    main .stat-change-up {
        background: #eff6ff !important;
        color: #3b82f6 !important;
    }
    main .stat-change-warn {
        background: #fffbeb !important;
        color: #d97706 !important;
    }
    main .sidebar-dot-blue,
    main .sidebar-dot-slate,
    main .sidebar-dot-violet,
    main .stat-dot-purple,
    main .stat-dot-cyan {
        background: #60a5fa !important;
    }
    main .sidebar-dot-amber,
    main .stat-dot-amber {
        background: #fbbf24 !important;
    }
    main .sidebar-dot-emerald,
    main .sidebar-dot-teal,
    main .sidebar-dot-rose,
    main .stat-dot-emerald,
    main .stat-dot-rose {
        background: #94a3b8 !important;
    }
    main .admin-attention-row-blue {
        border-left-color: #93c5fd !important;
        background: #f8fbff !important;
    }
    main .admin-attention-row-yellow {
        border-left-color: #fde68a !important;
        background: #fffdf5 !important;
    }

    main .text-\[\#0037c7\],
    main .hover\:text-\[\#0037c7\]:hover,
    main a.text-blue-600 {
        color: #3b82f6 !important;
    }
    main .bg-\[\#0037c7\],
    main .border-\[\#0037c7\] {
        background-color: #64748b !important;
        border-color: #64748b !important;
    }
    main .focus\:border-\[\#0037c7\]:focus,
    main .focus\:ring-\[\#0037c7\]:focus {
        border-color: #93c5fd !important;
        --tw-ring-color: rgba(147, 197, 253, 0.45) !important;
    }
    /* Allow soft sky/amber solid buttons for primary actions */
    main .bg-sky-600 {
        background-color: #0ea5e9 !important;
    }
    main .hover\:bg-sky-700:hover {
        background-color: #0284c7 !important;
    }
    main .hover\:bg-sky-100:hover {
        background-color: #e0f2fe !important;
    }
    main .text-sky-700 {
        color: #0369a1 !important;
    }
    main .border-sky-200 {
        border-color: #bae6fd !important;
    }
    main .bg-blue-500, main .bg-sky-500 {
        background-color: #60a5fa !important;
    }
    main .bg-amber-500 {
        background-color: #fbbf24 !important;
    }
    main .bg-emerald-500, main .bg-rose-500, main .bg-green-500 {
        background-color: #94a3b8 !important;
    }
</style>
