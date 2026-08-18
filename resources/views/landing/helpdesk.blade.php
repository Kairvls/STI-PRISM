<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PaAyo Campus Helpdesk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico?v=1') }}">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --blue: #0025cc;
            --blue-dark: #001ca3;
            --yellow: #fff200;
            --ink: #1a1a2e;
            --muted: #6b7280;
            --line: #e8ecf4;
            --soft: #f3f6ff;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background:
                radial-gradient(ellipse 58% 45% at 12% 8%, rgba(196, 214, 255, 0.34), transparent 70%),
                radial-gradient(ellipse 32% 24% at 88% 10%, rgba(255, 242, 0, 0.12), transparent 65%),
                #ffffff;
            color: var(--ink);
        }

        .btn-blue {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--blue);
            color: #fff;
            font-weight: 700;
            border-radius: 999px;
            border: 0;
            padding: 12px 24px;
            text-decoration: none;
            transition: background .2s ease, box-shadow .2s ease, transform .2s ease;
        }

        .btn-blue:hover {
            background: var(--blue-dark);
            box-shadow: 0 12px 28px rgba(0, 37, 204, .24);
            transform: translateY(-2px);
        }

        .btn-soft {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #fff;
            color: var(--ink);
            font-weight: 700;
            border-radius: 999px;
            border: 1px solid var(--line);
            padding: 12px 24px;
            text-decoration: none;
            transition: background .2s ease, box-shadow .2s ease, transform .2s ease;
        }

        .btn-soft:hover {
            background: #f8faff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .08);
            transform: translateY(-2px);
        }

    </style>
</head>
<body>
    <section class="pt-10 md:pt-14 pb-16 md:pb-20">
        <div class="max-w-[1180px] mx-auto px-5 lg:px-6">
            <div class="rounded-[30px] border border-white/70 bg-white/90 p-6 md:p-8 shadow-[0_24px_70px_rgba(15,23,42,.08)] backdrop-blur">
                <div class="max-w-2xl">
                        <div class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-extrabold uppercase tracking-[0.14em]" style="background:#fff8c6; color:#7c6400;">
                            <i data-lucide="life-buoy" class="w-4 h-4"></i>
                            Campus Helpdesk
                        </div>
                        <h1 class="mt-4 text-3xl md:text-[2.8rem] font-extrabold leading-tight" style="color:var(--ink);">
                            Help and support links for login, access, and STI services
                        </h1>
                        <p class="mt-4 text-sm md:text-base leading-relaxed max-w-2xl" style="color:var(--muted);">
                            Use this page as the dedicated helpdesk tab for concerns outside the PaAyo report form, such as account access, sign-in help, and other STI support pages.
                        </p>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6 mt-8 md:mt-10">
                <section class="rounded-3xl bg-white border p-6 md:p-8 shadow-[0_14px_40px_rgba(15,23,42,.06)]" style="border-color:var(--line);">
                    <div class="inline-flex items-center gap-2 mb-4 px-3 py-1.5 rounded-full text-xs font-bold tracking-[0.12em] uppercase" style="background:#eef3ff; color:var(--blue);">
                        <i data-lucide="log-in" class="w-4 h-4"></i>
                        Account Access
                    </div>
                    <h2 class="text-2xl font-extrabold mb-2">STI Campus Helpdesk portal</h2>
                    <p class="text-sm md:text-base mb-6" style="color:var(--muted);">
                        This is only a reference for account access concerns, lost password help, sign-in questions, and support requests that are outside the PaAyo reporting flow.
                    </p>
                    <div class="rounded-2xl p-4 text-sm" style="background:var(--soft); color:var(--muted);">
                        Reference only. This page is shown as a support example and is not part of the PaAyo system workflow.
                    </div>
                </section>

                <section class="rounded-3xl bg-white border p-6 md:p-8 shadow-[0_14px_40px_rgba(15,23,42,.06)]" style="border-color:var(--line);">
                    <div class="inline-flex items-center gap-2 mb-4 px-3 py-1.5 rounded-full text-xs font-bold tracking-[0.12em] uppercase" style="background:#eef3ff; color:var(--blue);">
                        <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                        eLMS Support
                    </div>
                    <h2 class="text-2xl font-extrabold mb-2">STI eLMS FAQ and guidance</h2>
                    <p class="text-sm md:text-base mb-6" style="color:var(--muted);">
                        This is only a reference example for common login and account concerns shown in your screenshots. It is not connected to PaAyo and is not part of the reporting process.
                    </p>
                    <div class="rounded-2xl p-4 text-sm" style="background:var(--soft); color:var(--muted);">
                        Reference only. Kept here to show the type of outside support resource users may already know about.
                    </div>
                </section>

                <section class="rounded-3xl bg-white border p-6 md:p-8 shadow-[0_14px_40px_rgba(15,23,42,.06)] md:col-span-2" style="border-color:var(--line);">
                    <h2 class="text-2xl font-extrabold mb-3">When to use this page</h2>
                    <div class="grid md:grid-cols-3 gap-4">
                        <article class="rounded-2xl p-5" style="background:var(--soft);">
                            <div class="font-extrabold mb-2" style="color:var(--blue);">Use PaAyo FAQ</div>
                            <p class="text-sm m-0" style="color:var(--muted);">For registration steps, employee ID verification, reporting flow, and how to submit a concern in PaAyo.</p>
                        </article>
                        <article class="rounded-2xl p-5" style="background:var(--soft);">
                            <div class="font-extrabold mb-2" style="color:var(--blue);">Use Campus Helpdesk</div>
                            <p class="text-sm m-0" style="color:var(--muted);">For login concerns, password help, access issues, and STI support matters outside the report form.</p>
                        </article>
                        <article class="rounded-2xl p-5" style="background:var(--soft);">
                            <div class="font-extrabold mb-2" style="color:var(--blue);">Use the Landing Page</div>
                            <p class="text-sm m-0" style="color:var(--muted);">To start reporter registration, open the report form, or access the office login actions inside PaAyo.</p>
                        </article>
                    </div>
                </section>
            </div>
        </div>
    </section>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
