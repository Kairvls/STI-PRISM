@php
    $showChatFab = $showChatFab ?? true;
@endphp

<style>
    .chat-log {
        flex: 1;
        min-height: 180px;
        max-height: none;
        overflow-y: auto;
        scroll-behavior: smooth;
    }

    .chat-row {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        max-width: 100%;
    }

    .chat-row.bot {
        justify-content: flex-start;
    }

    .chat-row.user {
        justify-content: flex-end;
    }

    .chat-avatar {
        width: 30px;
        height: 30px;
        border-radius: 999px;
        overflow: hidden;
        flex-shrink: 0;
        background: #eef3ff;
        border: 1px solid #dfe7ff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .chat-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: 22% 50%;
        transform: scale(1.55);
        transform-origin: 22% 50%;
    }

    .chat-bubble {
        width: fit-content;
        max-width: min(78%, 240px);
        border-radius: 18px;
        padding: 10px 13px;
        font-size: .9rem;
        line-height: 1.5;
        word-break: break-word;
    }

    .chat-bubble.bot {
        background: #f3f6ff;
        color: var(--ink);
        border: 1px solid #dfe7ff;
        border-bottom-left-radius: 6px;
        max-width: min(82%, 260px);
    }

    .chat-bubble.user {
        background: var(--blue);
        color: #fff;
        margin-left: 0;
        border-bottom-right-radius: 6px;
    }

    .chat-chip {
        border: 1px solid var(--line);
        background: #fff;
        color: var(--ink);
        border-radius: 999px;
        padding: 10px 14px;
        font-size: .82rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .2s ease, border-color .2s ease, transform .2s ease;
    }

    .chat-chip:hover {
        background: #f8faff;
        border-color: #cfd8ee;
        transform: translateY(-1px);
    }

    .chip-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .chip-list .chat-chip-extra {
        display: none;
    }

    .chip-list.is-expanded .chat-chip-extra {
        display: inline-flex;
    }

    .chip-more {
        border: 1px dashed #c5d0ea;
        background: #f8faff;
        color: var(--blue);
    }

    .chip-list.is-expanded .chip-more {
        display: none;
    }

    .chip-wrap {
        position: relative;
        padding-right: 28px;
    }

    .chip-close {
        display: none;
        position: absolute;
        top: 0;
        right: 0;
        width: 26px;
        height: 26px;
        border: 0;
        border-radius: 999px;
        background: #eef3ff;
        color: #64748b;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 1;
        transition: background .2s ease, color .2s ease, transform .2s ease;
    }

    .chip-close:hover {
        background: #dbe6ff;
        color: var(--blue);
        transform: scale(1.04);
    }

    .chip-panel.is-started.is-showing .chip-close,
    .chip-wrap:has(.chip-list.is-expanded) .chip-close {
        display: inline-flex;
    }

    .chip-panel {
        flex-shrink: 0;
    }

    .chip-reveal {
        display: none;
        width: 100%;
        justify-content: center;
        border: 1px dashed #c5d0ea;
        background: #f8faff;
        color: var(--blue);
        border-radius: 999px;
        padding: 10px 14px;
        font-size: .82rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .2s ease, border-color .2s ease, transform .2s ease;
    }

    .chip-reveal:hover {
        background: #eef3ff;
        border-color: #b8c8ff;
        transform: translateY(-1px);
    }

    .chip-panel.is-started .chip-wrap {
        display: none;
    }

    .chip-panel.is-started .chip-reveal {
        display: inline-flex;
    }

    .chip-panel.is-started.is-showing .chip-wrap {
        display: block;
    }

    .chip-panel.is-started.is-showing .chip-reveal {
        display: none;
    }

    .chat-form {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }

    .chat-input {
        flex: 1;
        min-width: 0;
        width: auto;
        border-radius: 16px;
        border: 1px solid var(--line);
        padding: 12px 14px;
        font-size: .92rem;
        outline: none;
        transition: border-color .2s ease, box-shadow .2s ease;
    }

    .chat-input:focus {
        border-color: #b8c8ff;
        box-shadow: 0 0 0 4px rgba(0, 37, 204, .08);
    }

    .chat-send {
        flex: 0 0 auto;
        width: 44px;
        height: 44px;
        border: 0;
        border-radius: 999px;
        background: var(--blue);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background .2s ease, transform .2s ease, box-shadow .2s ease;
    }

    .chat-send:hover {
        background: var(--blue-dark);
        box-shadow: 0 8px 18px rgba(0, 37, 204, .24);
        transform: translateY(-1px);
    }

    .chat-fab {
        position: fixed;
        right: 24px;
        bottom: 24px;
        width: 64px;
        height: 64px;
        border: 0;
        border-radius: 999px;
        background: var(--blue);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 20px 40px rgba(0, 37, 204, .28);
        cursor: pointer;
        z-index: 60;
        transition: transform .2s ease, background .2s ease;
    }

    .chat-fab:hover {
        background: var(--blue-dark);
        transform: translateY(-2px) scale(1.03);
    }

    .chat-widget {
        position: fixed;
        right: 24px;
        bottom: 100px;
        width: min(340px, calc(100vw - 24px));
        max-height: calc(100vh - 140px);
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 24px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, .18);
        overflow: hidden;
        z-index: 60;
        display: none;
        flex-direction: column;
        overscroll-behavior: contain;
    }

    .chat-widget.is-open {
        display: flex;
    }

    .chat-widget.chat-widget--from-cta {
        bottom: 24px;
    }

    .chat-widget-header {
        background: linear-gradient(135deg, #0025cc 0%, #2143da 100%);
        color: #fff;
    }

    .chat-widget-body {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 0;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        overflow-y: auto;
        scroll-behavior: smooth;
        overscroll-behavior: contain;
        scrollbar-width: thin;
        scrollbar-color: #c9cfde transparent;
    }

    .chat-widget-body::-webkit-scrollbar {
        width: 6px;
    }

    .chat-widget-body::-webkit-scrollbar-thumb {
        background: #c9cfde;
        border-radius: 6px;
    }

    @media (max-width: 640px) {
        .chat-fab {
            right: 16px;
            bottom: 16px;
            width: 58px;
            height: 58px;
        }

        .chat-widget {
            right: 12px;
            left: 12px;
            bottom: 86px;
            width: auto;
        }

        .chat-widget.chat-widget--from-cta {
            bottom: 12px;
        }
    }
</style>

<div id="faqChatWidget" class="chat-widget {{ $showChatFab ? '' : 'chat-widget--from-cta' }} hidden" aria-hidden="true">
    <div class="chat-widget-header px-5 py-4">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="font-extrabold text-base">Reporter Assistant</div>
                <p class="text-sm mt-1 mb-0 text-blue-100">
                    Ask about report flow, employee ID verification, registration, and report details.
                </p>
            </div>
            <button id="faqChatClose" type="button" class="w-9 h-9 rounded-full border-0 bg-white/12 text-white inline-flex items-center justify-center cursor-pointer">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <div class="chat-widget-body p-4">
        <div id="faqChatLog" class="chat-log space-y-4 mb-4 pr-1">
            <div class="chat-row bot">
                <div class="chat-avatar" aria-hidden="true">
                    <img src="{{ asset('image/paayo_logo_original.png') }}" alt="">
                </div>
                <div class="chat-bubble bot">
                    Hi. I’m the PaAyo reporter assistant. Ask me about registration, employee ID, making a report, or what happens after you submit. I stay on campus reporting, so I won’t answer unrelated questions.
                </div>
            </div>
        </div>

        <div id="chipPanel" class="chip-panel mb-4">
            <button type="button" id="chipRevealBtn" class="chip-reveal">Click to see another suggestion</button>
            <div class="chip-wrap">
                <button type="button" id="chipCloseBtn" class="chip-close" aria-label="Hide suggestions">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
                <div id="chipList" class="chip-list">
                    <button type="button" class="chat-chip" data-question="How do I register?">How do I register?</button>
                    <button type="button" class="chat-chip" data-question="Why is my employee ID not verified?">Why is my employee ID not verified?</button>
                    <button type="button" class="chat-chip" data-question="What reporter details do I put?">What reporter details do I put?</button>
                    <button type="button" class="chat-chip chat-chip-extra" data-question="What type of report?">What type of report?</button>
                    <button type="button" class="chat-chip chat-chip-extra" data-question="Can I submit only description or suggested issue?">Suggested issue or description?</button>
                    <button type="button" class="chat-chip chat-chip-extra" data-question="What is the report flow after submission?">What is the report flow?</button>
                    <button type="button" id="chipMoreBtn" class="chat-chip chip-more">+3 suggestions</button>
                </div>
            </div>
        </div>

        <form id="faqChatForm" class="chat-form">
            <input
                id="faqChatInput"
                type="text"
                class="chat-input"
                placeholder="Type your question..."
                autocomplete="off"
            >
            <button type="submit" class="chat-send" aria-label="Send question">
                <i data-lucide="send" class="w-4 h-4"></i>
            </button>
        </form>
    </div>
</div>

@if ($showChatFab)
    <button id="faqChatToggle" type="button" class="chat-fab" aria-label="Open chatbot">
        <i data-lucide="message-circle-more" class="w-7 h-7"></i>
    </button>
@endif

<script>
    (function () {
        const faqChatWidget = document.getElementById('faqChatWidget');
        const faqChatToggle = document.getElementById('faqChatToggle');
        const faqChatClose = document.getElementById('faqChatClose');
        const faqChatLog = document.getElementById('faqChatLog');
        const faqChatForm = document.getElementById('faqChatForm');
        const faqChatInput = document.getElementById('faqChatInput');
        const faqChatChips = document.querySelectorAll('[data-question]');
        const chipPanel = document.getElementById('chipPanel');
        const chipList = document.getElementById('chipList');
        const chipMoreBtn = document.getElementById('chipMoreBtn');
        const chipCloseBtn = document.getElementById('chipCloseBtn');
        const chipRevealBtn = document.getElementById('chipRevealBtn');
        const faqChatWidgetBody = document.querySelector('.chat-widget-body');

        function collapseSuggestionsAfterChat() {
            if (!chipPanel) return;
            chipPanel.classList.add('is-started');
            chipPanel.classList.remove('is-showing');
            if (chipList) {
                chipList.classList.remove('is-expanded');
            }
        }

        function showSuggestions() {
            if (!chipPanel) return;
            chipPanel.classList.add('is-showing');
        }

        function hideSuggestions() {
            if (!chipPanel) return;
            chipPanel.classList.remove('is-showing');
            if (chipList) {
                chipList.classList.remove('is-expanded');
            }
        }

        const botAvatarUrl = @json(asset('image/paayo_logo_original.png'));

        function addChatMessage(role, text) {
            const row = document.createElement('div');
            row.className = 'chat-row ' + role;

            if (role === 'bot') {
                const avatar = document.createElement('div');
                avatar.className = 'chat-avatar';
                avatar.setAttribute('aria-hidden', 'true');
                const img = document.createElement('img');
                img.src = botAvatarUrl;
                img.alt = '';
                avatar.appendChild(img);
                row.appendChild(avatar);
            }

            const bubble = document.createElement('div');
            bubble.className = 'chat-bubble ' + role;
            bubble.textContent = text;
            row.appendChild(bubble);
            faqChatLog.appendChild(row);
            faqChatLog.scrollTo({ top: faqChatLog.scrollHeight, behavior: 'smooth' });
        }

        function getFaqBotReply(question) {
            const raw = question.trim();
            const q = raw.toLowerCase()
                .replace(/[?!.,]/g, ' ')
                .replace(/\s+/g, ' ')
                .replace(/maintenanc(?:e)?/g, 'maintenance')
                .replace(/personel/g, 'personnel')
                .replace(/making a report/g, 'make report')
                .replace(/making report/g, 'make report')
                .replace(/make a report/g, 'make report')
                .trim();

            const has = function () {
                for (let i = 0; i < arguments.length; i += 1) {
                    if (q.includes(arguments[i])) return true;
                }
                return false;
            };

            const score = function (words) {
                let n = 0;
                for (let i = 0; i < words.length; i += 1) {
                    if (q.includes(words[i])) n += 1;
                }
                return n;
            };

            const greetingOnly = /^(hi|hello|hey|yo|sup|good morning|good afternoon|good evening|hi po|hello po)$/.test(q);
            if (greetingOnly || q === 'hey there') {
                return 'Hi. I can help with PaAyo reporting: registration, employee ID, how to submit a concern, and what happens after. What do you need?';
            }

            if (/^(thanks|thank you|ty|tysm|salamat|ok|okay|noted|got it)$/.test(q)) {
                return 'You’re welcome. If you need anything else about PaAyo reporting, just ask.';
            }

            if (has('who are you', 'what are you', 'your name', 'are you ai', 'are you a bot', 'what can you do')) {
                return 'I’m the PaAyo reporter assistant for STI College Ormoc. I can explain how reporters register, how employee ID verification works, how to file a report, and the flow after submission. I do not answer unrelated questions.';
            }

            const offTopic = has(
                'weather', 'recipe', 'cook', 'homework', 'assignment', 'solve this',
                'capital of', 'who won', 'celebrity', 'girlfriend', 'boyfriend',
                'joke', 'poem', 'song lyrics', 'crypto', 'bitcoin', 'stock',
                'movie', 'netflix', 'game cheat', 'hack', 'password of'
            ) || /^(what is 2\+2|tell me a joke)$/.test(q);

            if (offTopic) {
                return 'I only help with PaAyo campus reporting. Ask about registration, employee ID, making a report, status after submit, or Campus Helpdesk vs PaAyo.';
            }

            const intents = [
                {
                    n: score(['what is paayo', 'about paayo', 'this system', 'what is this', 'paayo for', 'purpose']) + (has('paayo') && has('what', 'about', 'system') ? 2 : 0),
                    reply: 'PaAyo is STI College Ormoc’s campus procurement and maintenance platform. Reporters file classroom, facility, AV, or computer concerns online. Maintenance then inspects the report, and urgent replacements can continue through purchaser, approval, receiving, and inventory.'
                },
                {
                    n: score(['register', 'registration', 'first time', 'new reporter', 'how to start', 'sign up', 'create account', 'email link']),
                    reply: 'If you are a first-time reporter, or your employee ID is not in PaAyo yet, enter your email on the landing page. PaAyo sends a link. Open it and fill your reporter details. After that, you submit reports using your employee ID. This is not a staff login account.'
                },
                {
                    n: score(['reporter detail', 'my details', 'first name', 'last name', 'contact number', 'contact no', 'personal info']),
                    reply: 'Reporter details are about you, not the damage report. On the registration form, enter employee ID, first name, optional middle name, last name, type (Faculty or Staff), and contact number. Your email is already filled from the verified link.'
                },
                {
                    n: score(['employee id', 'verified', 'verify', 'recognized', 'not found', 'unknown id', 'id not']),
                    reply: 'Your employee ID is verified only if your reporter record already exists. Enter it on the report form and PaAyo shows your name. If it is not recognized, register first or wait until maintenance personnel record your employee ID.'
                },
                {
                    n: score(['did not receive', 'didn\'t receive', 'no email', 'expired', 'link expired', 'spam']) + (has('email') && has('link', 'receive', 'inbox') ? 2 : 0),
                    reply: 'Check your inbox and spam for the PaAyo registration link. That link opens the reporter details form. If it expired, enter your email again on the landing page to request a new one.'
                },
                {
                    n: score(['make report', 'how to report', 'file a report', 'submit a concern', 'report form', 'fill up', 'what to put', 'required', 'necessary fields']),
                    reply: 'To make a report you need: registered employee ID, location, equipment, the issue (suggested issue or description, or both), and type of report (Non-Urgent or Urgent). Name, Faculty/Staff, and contact number come from registration, so you do not enter them again. An image is optional.'
                },
                {
                    n: score(['suggested issue', 'description', 'both empty', 'either one']),
                    reply: 'On the report form you may fill the suggested issue only, the description only, or both. You cannot leave both empty. At least one is required before submitting.'
                },
                {
                    n: score(['image', 'picture', 'photo', 'upload', 'attach', 'camera']),
                    reply: 'An image is optional. You can submit without a picture. Attach one only if it helps maintenance see the problem.'
                },
                {
                    n: score(['urgent', 'non urgent', 'non-urgent', 'priority', 'type of report']),
                    reply: 'Type of report is the urgency you choose: Non-Urgent for a minor repair concern, or Urgent if it needs immediate maintenance attention.'
                },
                {
                    n: score(['after submission', 'after submit', 'what happens', 'what happen', 'report flow', 'next step', 'then what', 'who handles', 'status', 'pending', 'processing', 'resolved', 'inspect']),
                    reply: 'After you submit, the report is recorded as Submitted and goes to maintenance for inspection. If they can repair it, they handle it there. If it needs replacement, it can continue to purchaser, approval, receiving, and inventory until it is marked Resolved.'
                },
                {
                    n: score(['maintenance', 'personnel', 'office see', 'walk in', 'verbal']) + (has('submit', 'before', 'need', 'see') ? 1 : 0),
                    reply: 'You must submit the report first. Maintenance personnel only see it in their system after it is submitted. Until you click submit, the concern is not sent to them. You do not need to walk in for it to enter PaAyo.'
                },
                {
                    n: score(['track', 'follow up', 'follow-up', 'where is my report', 'update on my', 'already submitted']),
                    reply: 'Once submitted, the report is already in the maintenance system. Maintenance inspects it first. You can follow up with maintenance personnel if you need a status update.'
                },
                {
                    n: score(['how long', 'how many days', 'when will it be fixed', 'eta', 'how soon']),
                    reply: 'PaAyo records the report as soon as you submit it. Repair or replacement time depends on inspection and, if needed, purchaser and approval steps. Follow up with maintenance for timing.'
                },
                {
                    n: score(['edit', 'change after', 'cancel', 'delete report', 'wrong report']),
                    reply: 'Submit only when the needed fields are complete. After submission, maintenance already receives it. If something was encoded wrongly, contact maintenance personnel. Do not submit a second report for the same concern unless they ask you to.'
                },
                {
                    n: score(['inactive', 'disabled', 'cannot submit', 'can\'t submit', 'access disabled']),
                    reply: 'If the form says reporting access is disabled, your reporter record is inactive. You cannot submit until maintenance personnel enable it. Contact the maintenance office if that looks wrong.'
                },
                {
                    n: score(['staff login', 'sign in', 'signin', 'log in', 'login', 'dashboard', 'account']),
                    reply: 'Reporters do not need a staff login to submit a report. Use Make Report after registration. Staff Sign In is only for internal roles: maintenance, purchaser, accounting, receiving, admin, or president.'
                },
                {
                    n: score(['helpdesk', 'elms', 'password', 'forgot password', 'sti support', 'school portal']),
                    reply: 'Use Campus Helpdesk for account access, sign-in help, or STI support outside PaAyo. PaAyo is only for campus maintenance reports, not portal login or ELMS problems.'
                },
                {
                    n: score(['who can report', 'teacher', 'faculty', 'staff can', 'allowed to report', 'student']),
                    reply: 'Teachers, whether faculty or staff, can report after their reporter record exists. First-time users register their details, then submit using employee ID. Staff office logins are separate and not required for reporting.'
                },
                {
                    n: score(['qr', 'scan', 'qr code']),
                    reply: 'QR scanning is for maintenance personnel to open and update equipment records. Reporters do not need to scan a QR code to submit a concern. Use Make Report, verify your employee ID, then fill the needed fields.'
                },
                {
                    n: score(['purchaser', 'replacement', 'procurement', 'inventory', 'approval', 'receiving', 'liquidation']),
                    reply: 'If inspection shows the item needs replacement, maintenance can pass it to the purchaser. From there it can move through approval, funding, receiving, liquidation, and inventory update until the report is resolved.'
                },
                {
                    n: score(['sti', 'ormoc', 'campus', 'college', 'where is this for']),
                    reply: 'PaAyo is built for STI College Ormoc campus maintenance: rooms, equipment, and damage reports. Use Make Report for campus concerns. Use Campus Helpdesk for school-account issues that are outside this system.'
                }
            ];

            intents.sort(function (a, b) { return b.n - a.n; });
            if (intents[0].n >= 1) {
                return intents[0].reply;
            }

            if (has('report', 'paayo', 'maintenance', 'employee', 'register', 'submit', 'form')) {
                return 'I can help with that in PaAyo terms. Try asking one of these: How do I register? Why is my employee ID not verified? What do I fill in the report? What happens after I submit?';
            }

            return 'I can answer PaAyo questions, and a few nearby ones like Campus Helpdesk vs reporting. I don’t cover unrelated topics. Ask about registration, employee ID, making a report, or the flow after submission.';
        }

        function askFaqBot(question) {
            const trimmedQuestion = question.trim();
            if (trimmedQuestion === '') return;

            addChatMessage('user', trimmedQuestion);
            collapseSuggestionsAfterChat();
            const reply = getFaqBotReply(trimmedQuestion);

            window.setTimeout(() => {
                addChatMessage('bot', reply);
            }, 420);
        }

        function openFaqChat() {
            faqChatWidget.classList.add('is-open');
            faqChatWidget.classList.remove('hidden');
            faqChatWidget.setAttribute('aria-hidden', 'false');
            document.body.classList.add('chat-open');
            faqChatInput.focus();
            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        function closeFaqChat() {
            faqChatWidget.classList.remove('is-open');
            faqChatWidget.classList.add('hidden');
            faqChatWidget.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('chat-open');
        }

        window.openFaqChat = openFaqChat;
        window.closeFaqChat = closeFaqChat;

        if (faqChatToggle) {
            faqChatToggle.addEventListener('click', function () {
                const isHidden = !faqChatWidget.classList.contains('is-open');
                if (isHidden) {
                    openFaqChat();
                } else {
                    closeFaqChat();
                }
            });
        }

        faqChatClose.addEventListener('click', closeFaqChat);

        faqChatForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const question = faqChatInput.value;
            askFaqBot(question);
            faqChatInput.value = '';
        });

        faqChatChips.forEach((chip) => {
            chip.addEventListener('click', function () {
                const question = this.getAttribute('data-question') || '';
                openFaqChat();
                askFaqBot(question);
            });
        });

        if (chipRevealBtn) {
            chipRevealBtn.addEventListener('click', function () {
                showSuggestions();
            });
        }

        if (chipMoreBtn && chipList) {
            chipMoreBtn.addEventListener('click', function () {
                chipList.classList.add('is-expanded');
            });
        }

        if (chipCloseBtn && chipList) {
            chipCloseBtn.addEventListener('click', function () {
                if (chipPanel && chipPanel.classList.contains('is-started')) {
                    hideSuggestions();
                    if (chipRevealBtn) {
                        chipRevealBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                    return;
                }

                chipList.classList.remove('is-expanded');
            });
        }

        function canScrollVertically(element) {
            return element && element.scrollHeight > element.clientHeight;
        }

        function handleWidgetWheel(event) {
            const deltaY = event.deltaY;
            const inChatLog = event.target.closest('#faqChatLog');
            const scroller = (inChatLog && canScrollVertically(faqChatLog))
                ? faqChatLog
                : faqChatWidgetBody;

            if (!scroller) {
                event.preventDefault();
                return;
            }

            const atTop = scroller.scrollTop <= 0 && deltaY < 0;
            const atBottom = scroller.scrollTop + scroller.clientHeight >= scroller.scrollHeight - 1 && deltaY > 0;

            if (atTop || atBottom || !canScrollVertically(scroller)) {
                event.preventDefault();
            }
        }

        faqChatWidget.addEventListener('wheel', handleWidgetWheel, { passive: false });

        if (window.lucide) {
            window.lucide.createIcons();
        }
    })();
</script>
