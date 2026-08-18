<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PaAyo Reporter FAQ</title>
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
                radial-gradient(ellipse 60% 45% at 10% 8%, rgba(196, 214, 255, 0.35), transparent 70%),
                radial-gradient(ellipse 35% 28% at 92% 10%, rgba(255, 242, 0, 0.10), transparent 65%),
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
            font-weight: 600;
            font-size: .875rem;
            border-radius: 999px;
            border: 1px solid var(--line);
            padding: 9px 16px;
            text-decoration: none;
            transition: background .2s ease, border-color .2s ease;
        }

        .btn-soft:hover {
            background: #f8faff;
            border-color: #d7deee;
        }

        .hero-card {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--line);
            background: #fff;
            box-shadow: 0 8px 28px rgba(15, 23, 42, .04);
        }

        .hero-card::before {
            content: "";
            position: absolute;
            width: 280px;
            height: 280px;
            border-radius: 999px;
            right: -90px;
            top: -140px;
            background: radial-gradient(circle, rgba(0,37,204,.08) 0%, rgba(0,37,204,0) 70%);
            pointer-events: none;
        }

        .hero-card::after {
            content: "";
            position: absolute;
            width: 240px;
            height: 240px;
            border-radius: 999px;
            left: -80px;
            bottom: -150px;
            background: radial-gradient(circle, rgba(255,242,0,.12) 0%, rgba(255,242,0,0) 72%);
            pointer-events: none;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: clamp(1.35rem, 2.1vw, 1.75rem);
            font-weight: 600;
            letter-spacing: -0.025em;
            line-height: 1.3;
            margin: 14px 0 0;
            max-width: 22ch;
        }

        .hero-subtitle {
            max-width: 52ch;
            font-size: .9375rem;
            line-height: 1.6;
            font-weight: 400;
        }

        details summary {
            list-style: none;
        }

        details summary::-webkit-details-marker {
            display: none;
        }
    </style>
</head>
<body>
    <section class="pt-10 md:pt-14 pb-16 md:pb-20">
        <div class="max-w-[1180px] mx-auto px-5 lg:px-6 faq-main">
            <div class="hero-card rounded-3xl px-6 py-6 md:px-8 md:py-7">
                <div class="hero-content flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                    <div class="max-w-2xl">
                        <div class="inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em]" style="background:#eef3ff; color:var(--blue);">
                            <i data-lucide="circle-help" class="w-3.5 h-3.5"></i>
                            Reporter FAQ
                        </div>
                        <h1 class="hero-title" style="color:var(--ink);">
                            Answers for registration, reporting, and the PaAyo report flow
                        </h1>
                        <p class="hero-subtitle mt-3" style="color:var(--muted);">
                            This page helps reporters understand how to start a report, how registration works, what information is needed, and what happens after submission.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3 items-center">
                        <a href="{{ url('/') }}" class="btn-soft">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            Back to Home
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 mt-8 md:mt-10">
                <section class="rounded-3xl bg-white border p-6 md:p-8 shadow-[0_14px_40px_rgba(15,23,42,.06)]" style="border-color:var(--line);">
                    <h2 class="text-lg font-semibold mb-2" style="color:var(--ink);">First-time reporter registration</h2>
                    <p class="text-sm md:text-base mb-6" style="color:var(--muted);">
                        Teachers, whether faculty or staff, need to register first if they are new to the system or if maintenance personnel have not yet recorded their employee ID in PaAyo.
                    </p>
                    <div class="space-y-3">
                        <details class="rounded-2xl border p-4" style="border-color:var(--line);" open>
                            <summary class="flex items-center justify-between gap-3 cursor-pointer font-semibold">
                                <span>When do I need to register first?</span>
                                <i data-lucide="chevron-down" class="w-4 h-4"></i>
                            </summary>
                            <p class="mt-3 text-sm leading-relaxed" style="color:var(--muted);">
                                Register first if this is your first time using the system, or if your employee ID is still not recognized because maintenance personnel have not yet recorded it in the reporter list.
                            </p>
                        </details>
                        <details class="rounded-2xl border p-4" style="border-color:var(--line);" open>
                            <summary class="flex items-center justify-between gap-3 cursor-pointer font-semibold">
                                <span>How does the email registration work?</span>
                                <i data-lucide="chevron-down" class="w-4 h-4"></i>
                            </summary>
                            <p class="mt-3 text-sm leading-relaxed" style="color:var(--muted);">
                                Start by entering your email address on the landing page. The system sends a link to your email, and when you click that link it opens a form where you enter your details so your reporter record can be created.
                            </p>
                        </details>
                    </div>
                </section>

                <section class="rounded-3xl bg-white border p-6 md:p-8 shadow-[0_14px_40px_rgba(15,23,42,.06)]" style="border-color:var(--line);">
                    <h2 class="text-lg font-semibold mb-2" style="color:var(--ink);">Simple reporting flow</h2>
                    <p class="text-sm md:text-base mb-6" style="color:var(--muted);">
                        The report form is designed to stay fast and simple, so reporters only provide the most important information needed for verification and maintenance action.
                    </p>
                    <div class="grid md:grid-cols-3 gap-4 mb-4">
                        <article class="rounded-2xl p-5" style="background:var(--soft);">
                            <div class="text-xs font-bold uppercase tracking-[0.12em] mb-2" style="color:var(--blue);">Step 1</div>
                            <h3 class="font-extrabold mb-2">Enter your registered employee ID</h3>
                            <p class="text-sm m-0" style="color:var(--muted);">Once your reporter record already exists, you only need to enter your employee ID and the system verifies it by showing your name.</p>
                        </article>
                        <article class="rounded-2xl p-5" style="background:var(--soft);">
                            <div class="text-xs font-bold uppercase tracking-[0.12em] mb-2" style="color:var(--blue);">Step 2</div>
                            <h3 class="font-extrabold mb-2">Fill only the key report fields</h3>
                            <p class="text-sm m-0" style="color:var(--muted);">Choose the location, equipment, urgency level, and then describe the concern in the simplest way possible.</p>
                        </article>
                        <article class="rounded-2xl p-5" style="background:var(--soft);">
                            <div class="text-xs font-bold uppercase tracking-[0.12em] mb-2" style="color:var(--blue);">Step 3</div>
                            <h3 class="font-extrabold mb-2">Submit with or without an image</h3>
                            <p class="text-sm m-0" style="color:var(--muted);">Images are optional, so you can still submit quickly even if you do not upload a picture.</p>
                        </article>
                    </div>
                    <div class="rounded-2xl border p-5" style="border-color:var(--line);">
                        <h3 class="font-extrabold mb-3">What fields do I need to complete?</h3>
                        <p class="text-sm leading-relaxed m-0" style="color:var(--muted);">
                            After employee ID verification, choose the location and equipment. For the issue details, you may provide a suggested issue, a description, or both, but you cannot leave both empty. You also choose whether the concern is urgent or non-urgent. The image upload is optional.
                        </p>
                    </div>
                </section>

                <section class="rounded-3xl bg-white border p-6 md:p-8 shadow-[0_14px_40px_rgba(15,23,42,.06)]" style="border-color:var(--line);">
                    <h2 class="text-lg font-semibold mb-2" style="color:var(--ink);">What happens after submission</h2>
                    <p class="text-sm md:text-base mb-6" style="color:var(--muted);">
                        After submission, the report continues through the actual office process while keeping the form easy for the reporter.
                    </p>
                    <div class="grid md:grid-cols-4 gap-4">
                        <article class="rounded-2xl border p-5" style="border-color:var(--line);">
                            <div class="font-extrabold mb-2" style="color:var(--blue);">1. Submitted</div>
                            <p class="text-sm m-0" style="color:var(--muted);">The concern is recorded immediately after the simple reporter form is completed.</p>
                        </article>
                        <article class="rounded-2xl border p-5" style="border-color:var(--line);">
                            <div class="font-extrabold mb-2" style="color:var(--blue);">2. Inspected</div>
                            <p class="text-sm m-0" style="color:var(--muted);">Maintenance personnel inspect the issue and decide whether it can be repaired or needs replacement handling.</p>
                        </article>
                        <article class="rounded-2xl border p-5" style="border-color:var(--line);">
                            <div class="font-extrabold mb-2" style="color:var(--blue);">3. Processed</div>
                            <p class="text-sm m-0" style="color:var(--muted);">If needed, the concern can move through purchaser, approval, receiving, and inventory-related steps.</p>
                        </article>
                        <article class="rounded-2xl border p-5" style="border-color:var(--line);">
                            <div class="font-extrabold mb-2" style="color:var(--blue);">4. Resolved</div>
                            <p class="text-sm m-0" style="color:var(--muted);">The report is marked resolved once the issue is completed or the replacement workflow is finished.</p>
                        </article>
                    </div>
                </section>

                <section class="rounded-3xl bg-white border p-6 md:p-8 shadow-[0_14px_40px_rgba(15,23,42,.06)]" style="border-color:var(--line);">
                    <h2 class="text-lg font-semibold mb-2" style="color:var(--ink);">Login questions</h2>
                    <div class="space-y-3 mt-6">
                        <details class="rounded-2xl border p-4" style="border-color:var(--line);" open>
                            <summary class="flex items-center justify-between gap-3 cursor-pointer font-semibold">
                                <span>Do reporters need a staff login?</span>
                                <i data-lucide="chevron-down" class="w-4 h-4"></i>
                            </summary>
                            <p class="mt-3 text-sm leading-relaxed" style="color:var(--muted);">
                                No. Reporter registration is separate from staff account access. Staff login is only for internal office roles such as maintenance, purchaser, accounting, receiving, admin, or president.
                            </p>
                        </details>
                        <details class="rounded-2xl border p-4" style="border-color:var(--line);">
                            <summary class="flex items-center justify-between gap-3 cursor-pointer font-semibold">
                                <span>When should I use Campus Helpdesk?</span>
                                <i data-lucide="chevron-down" class="w-4 h-4"></i>
                            </summary>
                            <p class="mt-3 text-sm leading-relaxed" style="color:var(--muted);">
                                Use Campus Helpdesk for account access concerns, sign-in assistance, or STI support matters that are outside the normal reporting flow in PaAyo.
                            </p>
                        </details>
                    </div>
                </section>

            </div>
        </div>
    </section>

    @include('landing.partials.faq-chatbot')
</body>
</html>
