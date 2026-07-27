{{-- ===================================================== --}}
{{-- MESSAGING MODAL --}}
{{-- Shared across all authenticated modules --}}
{{-- ===================================================== --}}
<div
    id="messageToastContainer"
    class="fixed bottom-5 right-5 z-[10000] flex w-[340px]
           max-w-[calc(100vw-2rem)] flex-col gap-2"
></div>

<div id="messagingModal" class="hidden" aria-hidden="true">
    {{-- Backdrop --}}
    <div id="messagingModalBackdrop" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm opacity-0 transition-opacity duration-300 ease-out">
        {{-- Modal Container --}}
        <div id="messagingModalContainer" class="relative mx-4 w-full max-w-[960px] h-[78vh] max-h-[660px] bg-white rounded-2xl shadow-[0_25px_60px_rgba(0,0,0,0.25)] overflow-hidden scale-[0.95] opacity-0 transition-all duration-300 ease-out flex">

            {{-- ===================================== --}}
            {{-- LEFT PANEL --}}
            {{-- ===================================== --}}
            <aside class="w-full md:w-[340px] lg:w-[360px] shrink-0 border-r border-gray-200 flex flex-col bg-white">

                {{-- Header --}}
                <div class="p-4 border-b border-gray-100">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Messages</h2>
                        <p class="text-[11px] text-gray-500 mt-0.5">Your conversations</p>
                    </div>

                    {{-- Tabs --}}
                    <div class="flex mt-3 gap-1 bg-gray-100 p-1 rounded-lg">
                        <button type="button" onclick="switchModalTab('conversations')" id="modalTabConversations" class="flex-1 py-1.5 text-xs font-semibold rounded-md transition bg-white text-gray-900 shadow-sm">
                            Conversations
                        </button>
                        <button type="button" onclick="switchModalTab('users')" id="modalTabUsers" class="flex-1 py-1.5 text-xs font-semibold rounded-md transition text-gray-500 hover:text-gray-900">
                            Users
                        </button>
                    </div>
                </div>

                {{--=====================================--}}
                {{-- CONVERSATIONS SECTION --}}
                {{--=====================================--}}
                <div id="modalConversationsSection" class="flex-1 flex flex-col overflow-hidden">
                    {{-- Search Conversations --}}
                    <div class="p-3 pb-2">
                        <div class="relative">
                            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400"></i>
                            <input
                                type="text"
                                id="modalConversationSearch"
                                placeholder="Search conversations..."
                                class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2 pl-10 pr-4 text-sm text-gray-900 outline-none focus:border-gray-900 focus:ring-4 focus:ring-gray-100 transition-all duration-200"
                                autocomplete="off" />
                        </div>
                    </div>

                    {{-- Conversations List --}}
                    <div id="modalConversationsContainer" class="flex-1 overflow-y-auto px-3 pb-3">
                        <div id="modalConversationsList" class="divide-y divide-gray-100"></div>
                        <div id="modalConversationsEmpty" class="hidden p-6 text-center">
                            <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 mb-3">
                                <i data-lucide="message-circle" class="h-6 w-6"></i>
                            </div>
                            <p class="text-sm font-semibold text-gray-800">No conversations yet</p>
                            <p class="text-xs text-gray-500 mt-1">Switch to the Users tab to start chatting</p>
                        </div>
                    </div>
                </div>

                {{--=====================================--}}
                {{-- USERS SECTION --}}
                {{--=====================================--}}
                <div id="modalUsersSection" class="hidden flex-1 flex flex-col overflow-hidden">
                    {{-- Search Users --}}
                    <div class="p-3 pb-2">
                        <div class="relative">
                            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400"></i>
                            <input
                                type="text"
                                id="modalUserSearch"
                                placeholder="Search users by name or role..."
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-4 text-sm text-gray-900 outline-none focus:border-gray-900 focus:ring-4 focus:ring-gray-100 transition-all duration-200"
                                autocomplete="off" />
                        </div>
                    </div>

                    {{-- Users Grid --}}
                    <div id="modalUsersContainer" class="messaging-user-scroll flex-1 overflow-y-auto px-3 pb-3">
                        <div id="modalUsersList" class="flex flex-col gap-0.5"></div>
                        <div id="modalUsersEmpty" class="hidden py-12 text-center">
                            <div class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-400 mb-2">
                                <i data-lucide="users" class="h-5 w-5"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-700">No users found</p>
                            <p class="text-xs text-gray-500 mt-1">Try a different search term</p>
                        </div>
                    </div>
                </div>
            </aside>

            {{-- ===================================== --}}
            {{-- RIGHT PANEL --}}
            {{-- ===================================== --}}
            <main id="modalChatArea" class="hidden md:flex flex-1 flex-col min-h-0 bg-white">

                {{-- Chat Header --}}
                <div id="modalChatHeader" class="hidden shrink-0 p-4 border-b border-gray-100 flex items-center gap-3">
                    <div id="modalChatAvatar" class="h-9 w-9 rounded-full bg-gradient-to-br from-emerald-100 to-emerald-200 flex items-center justify-center text-xs font-semibold text-emerald-700"></div>
                    <div class="min-w-0">
                        <h3 id="modalChatTitle" class="text-sm font-semibold text-gray-900 truncate"></h3>
                        <p id="modalChatSubtitle" class="text-xs text-gray-500 truncate"></p>
                    </div>
                </div>

                {{-- Conversation Container --}}
                <div id="modalConversationContainer" class="flex flex-col flex-1 overflow-hidden min-h-0">

                    {{-- Empty state --}}
                    <div id="modalChatEmptyState" class="flex items-center justify-center flex-1">
                        <div class="text-center p-8">
                            <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-gray-400 mb-4">
                                <i data-lucide="messages-square" class="h-8 w-8"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Select a conversation</h3>
                            <p class="text-sm text-gray-500 mt-1 max-w-sm">Choose a conversation from the list to view messages and start chatting.</p>
                        </div>
                    </div>

                    {{-- ====================================== --}}
                    {{-- MESSAGES AREA --}}
                    {{-- ====================================== --}}

                    <div
                        id="modalMessagesContainer"
                        class="hidden flex flex-col flex-1 overflow-y-auto min-h-0 gap-2.5 px-4 py-3"
                    >
                        {{-- Messages are inserted here by JavaScript --}}


                        {{-- ====================================== --}}
                        {{-- TYPING INDICATOR --}}
                        {{-- ALWAYS STAYS AFTER THE LAST MESSAGE --}}
                        {{-- ====================================== --}}

                        <div
                            id="modalTypingIndicator"
                            class="typing-indicator-wrapper shrink-0"
                        >
                            <div class="flex justify-start">

                                <div
                                    class="inline-flex items-center gap-1
                                        rounded-2xl rounded-bl-md
                                        bg-gray-100 px-4 py-3"
                                >
                                    <span
                                        class="typing-dot h-1.5 w-1.5 rounded-full bg-gray-500"
                                    ></span>

                                    <span
                                        class="typing-dot h-1.5 w-1.5 rounded-full bg-gray-500"
                                    ></span>

                                    <span
                                        class="typing-dot h-1.5 w-1.5 rounded-full bg-gray-500"
                                    ></span>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>

                {{-- Composer --}}
                <div id="modalComposer" class="hidden shrink-0 border-t border-gray-100">
                    <div class="p-4">
                        {{-- ====================================== --}}
                        {{-- REPLY PREVIEW --}}
                        {{-- Shows when replying to a message --}}
                        {{-- ====================================== --}}

                        <div
                            id="modalReplyPreview"
                            class="hidden mb-3"
                        >
                            <div
                                class="flex items-center gap-3 rounded-xl
                                    border border-gray-200 bg-gray-50
                                    px-3 py-2.5"
                            >
                                <div class="w-1 self-stretch rounded-full bg-gray-900"></div>

                                <div class="min-w-0 flex-1">

                                    <p
                                        id="modalReplyName"
                                        class="text-xs font-semibold text-gray-900"
                                    >
                                        Replying to
                                    </p>

                                    <p
                                        id="modalReplyText"
                                        class="mt-0.5 truncate text-xs text-gray-500"
                                    ></p>

                                </div>

                                <button
                                    type="button"
                                    id="modalCancelReply"
                                    class="flex h-7 w-7 shrink-0 items-center
                                        justify-center rounded-full
                                        text-gray-400 transition
                                        hover:bg-gray-200 hover:text-gray-700"
                                    title="Cancel reply"
                                >
                                    <i data-lucide="x" class="h-4 w-4"></i>
                                </button>

                            </div>
                        </div>
                        <form id="modalMessageForm" class="flex items-end gap-2">
                            <textarea
                                id="modalMessageInput"
                                rows="1"
                                placeholder="Type a message..."
                                class="flex-1 rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-900 outline-none focus:border-gray-900 focus:ring-4 focus:ring-gray-100 transition-all duration-200 resize-none"
                                style="min-height: 42px; max-height: 120px;"></textarea>
                            <input type="file" id="modalAttachmentInput" class="hidden" />
                            <button
                                type="button"
                                id="modalAttachmentButton"
                                class="h-10 w-10 shrink-0 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-900"
                                title="Attach file">
                                <i data-lucide="paperclip" class="h-4 w-4"></i>
                            </button>
                            <button
                                type="submit"
                                class="h-10 w-10 shrink-0 flex items-center justify-center rounded-lg bg-gray-900 text-white transition hover:bg-gray-800 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                                id="modalSendButton">
                                <i data-lucide="send" class="h-4 w-4"></i>
                            </button>
                        </form>
                        <div id="modalAttachmentPreview" class="hidden mt-2 p-2 rounded-lg bg-gray-50 border border-gray-200">
                            <div class="flex items-center gap-2">
                                <div id="modalAttachmentIcon" class="h-8 w-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-600">
                                    <i data-lucide="file" class="h-4 w-4"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p id="modalAttachmentName" class="text-xs font-medium text-gray-900 truncate"></p>
                                    <p id="modalAttachmentSize" class="text-[10px] text-gray-500"></p>
                                </div>
                                <button type="button" id="modalAttachmentRemove" class="h-6 w-6 shrink-0 flex items-center justify-center rounded-full hover:bg-gray-200 text-gray-500 transition">
                                    <i data-lucide="x" class="h-3 w-3"></i>
                                </button>
                            </div>
                            <div id="modalAttachmentProgress" class="hidden mt-2 h-1 rounded-full bg-gray-200 overflow-hidden">
                                <div id="modalAttachmentProgressBar" class="h-full rounded-full bg-gray-900 transition-all duration-200" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            {{-- ===================================== --}}
            {{-- CLOSE BUTTON --}}
            {{-- ===================================== --}}
            <button type="button" onclick="closeMessagingModal()" class="absolute top-4 right-4 z-10 inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-gray-500 shadow-sm border border-gray-200 transition hover:bg-gray-100 hover:text-gray-900">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>

        </div>
    </div>
</div>


{{-- ====================================== --}}
{{-- PINNED MESSAGES MODAL --}}
{{-- ====================================== --}}

<div
    id="pinnedMessagesModal"
    class="fixed inset-0 z-[9999] hidden"
>
    {{-- BACKDROP --}}
    <div
        id="pinnedMessagesBackdrop"
        class="absolute inset-0 bg-black/50"
    ></div>

    <div
        class="relative flex min-h-full items-center justify-center p-4"
    >
        <div
            class="relative flex max-h-[620px] w-full max-w-xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
        >

            {{-- HEADER --}}
            <div
                class="flex shrink-0 items-center justify-between border-b border-gray-200 px-5 py-4"
            >
                <h3 class="text-lg font-semibold text-gray-900">
                    Pinned messages
                </h3>

                <button
                    type="button"
                    id="closePinnedMessagesModal"
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition hover:bg-gray-200 hover:text-gray-900"
                >
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            {{-- PINNED LIST --}}
            <div
                id="pinnedMessagesList"
                class="flex-1 overflow-y-auto p-5"
            >
            </div>

        </div>
    </div>
</div>


<script>
    (function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const currentUserId = {{ auth()->id() }};
        let currentConversationId = null;
        let messagesPage = 1;
        let isLoadingMessages = false;
        let hasMoreMessages = true;
        let userSearchTimeout = null;
        let selectedAttachment = null;
        let replyingToMessage = null;
        let editingMessageRow = null;
        let activeRealtimeConversationId = null;
        let typingTimeout = null;
        let globalTypingSent = false;
        const remoteTypingTimeouts = new Map();
        let currentConversationUserName = '';
        let currentConversationUser = null;

        // =====================================================
        // USER ONLINE STATUS HEARTBEAT
        //
        // Tells Laravel that the current user is still
        // actively using PRISM.
        // =====================================================

        async function sendUserHeartbeat() {

            try {

                await fetch('/user/heartbeat', {

                    method: 'POST',

                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }

                });

            } catch (error) {

                console.error(
                    'Heartbeat failed:',
                    error
                );
            }
        }


        // =====================================================
        // SYNC MESSAGES THAT ARRIVED WHILE USER WAS OFFLINE
        // =====================================================

        async function syncDeliveredMessages() {

            try {

                const response = await fetch(
                    '/messages/sync-delivered',
                    {
                        method: 'POST',

                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    }
                );

                if (!response.ok) {

                    console.error(
                        'Delivery sync failed:',
                        response.status
                    );

                    return;
                }

                const data = await response.json();

                console.log(
                    'Delivery sync complete:',
                    data.delivered_count
                );

            } catch (error) {

                console.error(
                    'Delivery sync failed:',
                    error
                );
            }
        }


        // =====================================================
        // USER JUST LOADED PRISM
        // =====================================================

        sendUserHeartbeat();

        syncDeliveredMessages();

        // =====================================================
        // USER'S INTERNET CONNECTION RETURNS
        // =====================================================

        window.addEventListener(
            'online',
            function () {

                sendUserHeartbeat();

                syncDeliveredMessages();
            }
        );


        // =====================================================
        // SEND AGAIN EVERY 60 SECONDS
        // =====================================================

        setInterval(
            sendUserHeartbeat,
            60000
        );

        // =====================================================
        // LIVE CONVERSATION PREVIEW TIME
        // 59m becomes 1h automatically without refreshing.
        // =====================================================
        setInterval(
            refreshConversationRelativeTimes,
            60000
        );

        // =====================================================
        // FORMAT USER ONLINE STATUS
        // =====================================================

        function formatUserActivity(lastActiveAt) {

            if (!lastActiveAt) {
                return 'Offline';
            }

            const lastActive = new Date(lastActiveAt);
            const now = new Date();

            const diffMilliseconds =
                now - lastActive;

            const diffMinutes =
                Math.floor(
                    diffMilliseconds / 60000
                );


            // =========================================
            // ACTIVE WITHIN LAST 2 MINUTES
            // =========================================

            if (diffMinutes <= 2) {
                return 'Active now';
            }


            // =========================================
            // MINUTES AGO
            // =========================================

            if (diffMinutes < 60) {
                return `Active ${diffMinutes} minutes ago`;
            }


            const diffHours =
                Math.floor(
                    diffMinutes / 60
                );


            // =========================================
            // HOURS AGO
            // =========================================

            if (diffHours < 24) {

                return `Active ${diffHours} ${
                diffHours === 1
                    ? 'hour'
                    : 'hours'
            } ago`;
            }


            const diffDays =
                Math.floor(
                    diffHours / 24
                );


            // =========================================
            // YESTERDAY
            // =========================================

            if (diffDays === 1) {
                return 'Active yesterday';
            }


            // =========================================
            // OLDER
            // =========================================

            return `Active ${diffDays} days ago`;
        }

        // =====================================================
        // TOPBAR MESSAGE UNREAD COUNT
        // PLACE ABOVE listenToUserMessagesRealtime()
        // =====================================================

        async function updateTopbarMessageBadge() {

            try {

                const response = await fetch('/messages/unread-count', {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();

                const badge =
                    document.getElementById('topbarMessageBadge');

                if (!badge) {
                    return;
                }

                const count =
                    Number(data.unread_count || 0);


                // =============================================
                // NO UNREAD MESSAGES
                // =============================================

                if (count <= 0) {

                    badge.textContent = '0';

                    badge.classList.add('hidden');

                    badge.classList.remove('flex');

                    return;
                }


                // =============================================
                // SHOW UNREAD COUNT
                // 99+ prevents the badge becoming too large
                // =============================================

                badge.textContent =
                    count > 99
                        ? '99+'
                        : String(count);

                badge.classList.remove('hidden');

                badge.classList.add('flex');

            } catch (error) {

                console.error(
                    'Unable to load message unread count:',
                    error
                );
            }
        }


        // =====================================================
        // MESSAGE TOAST
        // SHOWS WHEN A NEW MESSAGE ARRIVES
        // PLACE ABOVE listenToUserMessagesRealtime()
        // =====================================================

        function showIncomingMessageToast(msg) {

            const container =
                document.getElementById('messageToastContainer');

            if (!container || !msg) {
                return;
            }


            // =============================================
            // DO NOT SHOW TOAST IF USER IS ALREADY
            // LOOKING AT THIS EXACT CONVERSATION
            // =============================================

            const messagingModal =
                document.getElementById('messagingModal');

            const modalIsOpen =
                messagingModal &&
                !messagingModal.classList.contains('hidden');

            if (
                modalIsOpen &&
                Number(currentConversationId) ===
                Number(msg.conversation_id)
            ) {
                return;
            }


            // =============================================
            // GET SENDER NAME
            // =============================================

            const senderName =
                msg.sender?.user_full_name ||
                msg.sender_name ||
                'New message';


            // =============================================
            // MESSAGE PREVIEW
            // =============================================

            let preview =
                msg.message_content || 'Sent you a message';

            if (preview === '[attachment:image]') {
                preview = 'Sent you a photo';
            }

            if (preview === '[attachment:file]') {
                preview = 'Sent you a file';
            }

            if (preview.length > 90) {
                preview =
                    preview.substring(0, 90) + '...';
            }


            // =============================================
            // CREATE TOAST
            // =============================================

            const toast =
                document.createElement('button');

            toast.type = 'button';

            toast.className = `
                w-full rounded-xl border border-gray-200
                bg-white p-4 text-left
                shadow-[0_18px_50px_rgba(0,0,0,0.16)]
                transition duration-200
                hover:bg-gray-50
            `;


            // =============================================
            // SAFE TEXT CONTENT
            // =============================================

            const header =
                document.createElement('div');

            header.className =
                'flex items-center gap-2';


            const icon =
                document.createElement('div');

            icon.className =
                'flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white';

            icon.innerHTML =
                '<i data-lucide="message-circle" class="h-4 w-4"></i>';


            const content =
                document.createElement('div');

            content.className =
                'min-w-0 flex-1';


            const name =
                document.createElement('p');

            name.className =
                'truncate text-sm font-semibold text-gray-900';

            name.textContent =
                senderName;


            const message =
                document.createElement('p');

            message.className =
                'mt-0.5 line-clamp-2 text-xs leading-4 text-gray-500';

            message.textContent =
                preview;


            content.appendChild(name);
            content.appendChild(message);

            header.appendChild(icon);
            header.appendChild(content);

            toast.appendChild(header);


            // =============================================
            // CLICK TO OPEN EXACT CONVERSATION
            // =============================================

            toast.addEventListener('click', async () => {

                toast.remove();


                // =============================================
                // OPEN MESSAGE MODAL
                // =============================================

                openMessagingModal();


                // =============================================
                // DIRECTLY OPEN THE CONVERSATION
                // THAT THIS MESSAGE CAME FROM
                // =============================================

                await openModalConversation(
                    msg.conversation_id
                );


                // =============================================
                // REFRESH UNREAD COUNT
                // =============================================

                await updateTopbarMessageBadge();
            });


            container.appendChild(toast);

            lucideCreateIcons();


            // =============================================
            // REMOVE AFTER 5 SECONDS
            // =============================================

            setTimeout(() => {

                toast.style.opacity = '0';
                toast.style.transform =
                    'translateY(8px)';

                setTimeout(() => {
                    toast.remove();
                }, 200);

            }, 8000);
        }

        // =====================================================
        // GLOBAL USER MESSAGE LISTENER
        //
        // PURPOSE:
        // Receives messages even when their conversation
        // is not currently opened.
        // =====================================================

        function listenToUserMessagesRealtime() {

        

            // =====================================================
            // MAKE SURE LARAVEL ECHO EXISTS
            // =====================================================

            if (!window.Echo) {

                console.error(
                    'Laravel Echo is not available.'
                );

                return;
            }


            // =====================================================
            // LISTEN TO CURRENT USER'S PRIVATE CHANNEL
            // =====================================================

            window.Echo
                .private(`user.${currentUserId}`)
                .listen('.message.sent', (event) => {

                    const msg = event.message;

                    if (!msg) {
                        return;
                    }


                    // =====================================================
                    // IGNORE OWN MESSAGES
                    // =====================================================

                    if (
                        Number(msg.sender_id) ===
                        Number(currentUserId)
                    ) {
                        return;
                    }


                    // =====================================================
                    // MARK MESSAGE AS DELIVERED
                    //
                    // The receiver does NOT need to open
                    // the conversation for this.
                    // =====================================================

                    markMessageAsDelivered(
                        msg.conversation_id,
                        msg.message_id
                    );


                    // =====================================================
                    // UPDATE CONVERSATION LIST
                    //
                    // This updates:
                    // unread count
                    // latest message
                    // latest message time
                    // conversation ordering
                    // =====================================================

                    loadModalConversations();

                    updateTopbarMessageBadge();

                    showIncomingMessageToast(msg);

                })

                .listen('.user.typing', (event) => {

                    handleGlobalTyping(event);

                });
        }

        function lucideCreateIcons() {
            if (window.lucide) {
                lucide.createIcons();
            }
        }

        function getInitials(name) {
            if (!name) return '?';
            return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
        }

        function formatTime(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));

            if (days === 0) {
                return date.toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
            } else if (days === 1) {
                return 'Yesterday';
            } else if (days < 7) {
                return date.toLocaleDateString('en-US', {
                    weekday: 'short'
                });
            } else {
                return date.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric'
                });
            }
        }

        // =====================================================
        // MESSENGER STYLE CONVERSATION RELATIVE TIME
        // Examples: now, 4m, 14h, 2d
        // =====================================================
        function formatConversationRelativeTime(dateString) {
            if (!dateString) return '';

            const date = new Date(dateString);
            const now = new Date();

            if (Number.isNaN(date.getTime())) return '';

            const diffSeconds = Math.max(
                0,
                Math.floor((now - date) / 1000)
            );

            if (diffSeconds < 60) return 'now';

            const diffMinutes = Math.floor(diffSeconds / 60);
            if (diffMinutes < 60) return `${diffMinutes}m`;

            const diffHours = Math.floor(diffMinutes / 60);
            if (diffHours < 24) return `${diffHours}h`;

            const diffDays = Math.floor(diffHours / 24);
            if (diffDays < 7) return `${diffDays}d`;

            const diffWeeks = Math.floor(diffDays / 7);
            if (diffWeeks < 5) return `${diffWeeks}w`;

            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric'
            });
        }

        // =====================================================
        // REFRESH VISIBLE CONVERSATION TIMES EVERY MINUTE
        // =====================================================
        function refreshConversationRelativeTimes() {
            document
                .querySelectorAll('.conversation-relative-time')
                .forEach(element => {
                    const value =
                        formatConversationRelativeTime(
                            element.dataset.createdAt
                        );

                    element.textContent =
                        value ? `· ${value}` : '';
                });
        }


        function formatMessageTime(dateString) {
            if (!dateString) return '';
            return new Date(dateString).toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }

        function formatMessageRelativeTime(dateString) {

            if (!dateString) return '';

            const date = new Date(dateString);
            const now = new Date();

            if (Number.isNaN(date.getTime())) return '';

            const diffSeconds = Math.max(
                0,
                Math.floor((now - date) / 1000)
            );

            if (diffSeconds < 60) {
                return 'now';
            }

            const diffMinutes =
                Math.floor(diffSeconds / 60);

            if (diffMinutes < 60) {
                return `${diffMinutes}m ago`;
            }

            const diffHours =
                Math.floor(diffMinutes / 60);

            if (diffHours < 24) {
                return `${diffHours}h ago`;
            }

            const diffDays =
                Math.floor(diffHours / 24);

            if (diffDays < 7) {
                return `${diffDays}d ago`;
            }

            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric'
            });
        }

        function getMessageStatus(msg) {

            // =========================================
            // SEEN
            // Profile picture will be shown instead
            // =========================================
            if (msg.is_read || msg.read_at) {
                return 'Seen';
            }

            // =========================================
            // DELIVERED
            // =========================================
            if (msg.delivered_at) {
                return 'Delivered';
            }

            // =========================================
            // SENT
            // =========================================
            return 'Sent';
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function getFileIcon(type, extension) {
            const ext = (extension || '').toLowerCase();
            if (type.startsWith('image/')) {
                return {
                    icon: 'image',
                    color: 'text-purple-600 bg-purple-50'
                };
            } else if (type.startsWith('video/')) {
                return {
                    icon: 'film',
                    color: 'text-indigo-600 bg-indigo-50'
                };
            } else if (type.startsWith('audio/')) {
                return {
                    icon: 'music',
                    color: 'text-pink-600 bg-pink-50'
                };
            } else if (type === 'application/pdf') {
                return {
                    icon: 'file-text',
                    color: 'text-red-600 bg-red-50'
                };
            } else if (['doc', 'docx'].includes(ext)) {
                return {
                    icon: 'file-text',
                    color: 'text-blue-600 bg-blue-50'
                };
            } else if (['xls', 'xlsx'].includes(ext)) {
                return {
                    icon: 'file-spreadsheet',
                    color: 'text-emerald-600 bg-emerald-50'
                };
            } else if (['ppt', 'pptx'].includes(ext)) {
                return {
                    icon: 'presentation',
                    color: 'text-orange-600 bg-orange-50'
                };
            } else if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) {
                return {
                    icon: 'archive',
                    color: 'text-yellow-600 bg-yellow-50'
                };
            } else if (ext === 'txt') {
                return {
                    icon: 'file',
                    color: 'text-gray-600 bg-gray-50'
                };
            }
            return {
                icon: 'file',
                color: 'text-gray-500 bg-gray-100'
            };
        }

        window.openMessagingModal = function() {
            const modal = document.getElementById('messagingModal');
            const backdrop = document.getElementById('messagingModalBackdrop');
            const container = document.getElementById('messagingModalContainer');

            if (!modal || !backdrop || !container) return;

            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';

            requestAnimationFrame(() => {
                backdrop.classList.remove('opacity-0');
                container.classList.remove('scale-[0.95]', 'opacity-0');
                container.classList.add('scale-100', 'opacity-100');
            });

            lucideCreateIcons();
            switchModalTab('conversations');
        };

        window.closeMessagingModal = function() {
            const modal = document.getElementById('messagingModal');
            const backdrop = document.getElementById('messagingModalBackdrop');
            const container = document.getElementById('messagingModalContainer');

            if (!modal || !backdrop || !container) return;

            backdrop.classList.add('opacity-0');
            container.classList.remove('scale-100', 'opacity-100');
            container.classList.add('scale-[0.95]', 'opacity-0');

            setTimeout(() => {
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                resetModalChat();
            }, 300);
        };

        function resetModalChat() {
            cancelReply();
            currentConversationId = null;
            messagesPage = 1;
            isLoadingMessages = false;
            hasMoreMessages = true;

            const chatEmpty = document.getElementById('modalChatEmptyState');
            const messagesContainer = document.getElementById('modalMessagesContainer');
            const chatHeader = document.getElementById('modalChatHeader');
            const composer = document.getElementById('modalComposer');
            const chatArea = document.getElementById('modalChatArea');
            const typingIndicator = document.getElementById('modalTypingIndicator');

            if (chatEmpty) chatEmpty.classList.remove('hidden');
            if (messagesContainer) {

                messagesContainer.classList.add('hidden');

                // =============================================
                // REMOVE MESSAGE ELEMENTS ONLY
                // KEEP TYPING INDICATOR ALIVE
                // =============================================

                Array.from(messagesContainer.children)
                    .forEach(child => {

                        if (
                            child.id !==
                            'modalTypingIndicator'
                        ) {
                            child.remove();
                        }

                    });
            }
            if (typingIndicator) {
                typingIndicator.classList.remove(
                    'is-typing'
                );
            }

            remoteTypingTimeouts.forEach(timeout => {
                clearTimeout(timeout);
            });

            remoteTypingTimeouts.clear();
            if (chatHeader) chatHeader.classList.add('hidden');
            if (composer) composer.classList.add('hidden');
            if (chatArea) {
                chatArea.classList.remove('hidden');
                chatArea.classList.add('md:flex');
            }
        }

        window.switchModalTab = function(tab) {
            const conversationsSection = document.getElementById('modalConversationsSection');
            const usersSection = document.getElementById('modalUsersSection');
            const conversationsTab = document.getElementById('modalTabConversations');
            const usersTab = document.getElementById('modalTabUsers');

            if (tab === 'conversations') {
                conversationsSection?.classList.remove('hidden');
                usersSection?.classList.add('hidden');
                conversationsTab?.classList.add('bg-white', 'text-gray-900', 'shadow-sm');
                conversationsTab?.classList.remove('text-gray-500');
                usersTab?.classList.remove('bg-white', 'text-gray-900', 'shadow-sm');
                usersTab?.classList.add('text-gray-500');
                loadModalConversations();
            } else {
                conversationsSection?.classList.add('hidden');
                usersSection?.classList.remove('hidden');
                usersTab?.classList.add('bg-white', 'text-gray-900', 'shadow-sm');
                usersTab?.classList.remove('text-gray-500');
                conversationsTab?.classList.remove('bg-white', 'text-gray-900', 'shadow-sm');
                conversationsTab?.classList.add('text-gray-500');
                loadModalUsers();
            }
        };

        // =====================================================
        // LOAD CONVERSATIONS
        // =====================================================

        async function loadModalConversations() {

            const search =
                document.getElementById('modalConversationSearch')?.value || '';

            const params = new URLSearchParams();

            if (search) {
                params.set('search', search);
            }

            try {

                const response = await fetch(
                    `/messages/conversations?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    }
                );

                if (!response.ok) {

                    console.error(
                        'Failed to load conversations:',
                        response.status
                    );

                    return;
                }

                const result = await response.json();

                // =========================================
                // DEBUG
                // Check what Laravel actually returned
                // =========================================

                console.log(
                    'Conversations response:',
                    result
                );

                // =========================================
                // Laravel pagination object
                //
                // result.data = paginator
                // result.data.data = conversations
                // =========================================

                renderModalConversations(
                    result.data
                );

            } catch (error) {

                console.error(
                    'Conversation loading error:',
                    error
                );
            }
        }

        // =====================================================
        // RENDER CONVERSATIONS
        // =====================================================

        function renderModalConversations(conversations) {

            const container =
                document.getElementById('modalConversationsList');

            const emptyState =
                document.getElementById('modalConversationsEmpty');

            if (!container) {
                return;
            }

            // =========================================
            // GET CONVERSATION ARRAY FROM PAGINATOR
            // =========================================

            const items = conversations?.data || [];

            // =========================================
            // NO CONVERSATIONS
            // =========================================

            if (items.length === 0) {

                container.innerHTML = '';

                emptyState?.classList.remove('hidden');

                return;
            }

            emptyState?.classList.add('hidden');

            // =========================================
            // RENDER CONVERSATION LIST
            // =========================================

            container.innerHTML = items.map(conv => {

                const lastMessage =
                    conv.last_message || {};

                const otherParticipant =
                    conv.participants?.find(
                        p =>
                        Number(p.user?.user_id) !==
                        Number(currentUserId)
                    )?.user || {};

                const name =
                    otherParticipant.user_full_name || 'Unknown';

                const initials =
                    getInitials(name);

                // =========================================
                // ONLINE STATUS FOR CONVERSATION USER
                // =========================================

                const lastActiveAt =
                    otherParticipant.last_active_at;

                let isOnline = false;

                if (lastActiveAt) {

                    const lastActive =
                        new Date(lastActiveAt);

                    const now =
                        new Date();

                    const diffMinutes =
                        Math.floor(
                            (now - lastActive) / 60000
                        );

                    // Same rule as the chat header
                    // Active within 2 minutes = online
                    isOnline = diffMinutes <= 2;
                }

                // =========================================
                // LAST MESSAGE OWNER
                // =========================================

                const lastMessageIsMine =
                    Number(lastMessage.sender_id) ===
                    Number(currentUserId);


                // =========================================
                // UNREAD MESSAGE COUNT
                // =========================================

                const unreadCount =
                    Number(conv.unread_count || 0);


                // =========================================
                // CONVERSATION PREVIEW
                //
                // Messenger style behavior:
                //
                // 1 unread message:
                // Show actual latest message.
                //
                // More than 1 unread:
                // "4 new messages"
                //
                // Own message:
                // "You: Hello"
                //
                // Attachments:
                // "You sent a photo."
                // "Administrator sent a photo."
                // =========================================

                let preview = 'No messages';

                const rawMessage =
                    lastMessage.message_content || '';


                // =========================================
                // MORE THAN ONE UNREAD MESSAGE
                // =========================================

                if (
                    !lastMessageIsMine &&
                    unreadCount > 1
                ) {

                    preview =
                        `${unreadCount} new messages`;

                }


                // =========================================
                // PHOTO
                // =========================================

                else if (
                    rawMessage === '[attachment:image]'
                ) {

                    preview = lastMessageIsMine
                        ? 'You sent a photo.'
                        : `${name} sent a photo.`;

                }


                // =========================================
                // FILE
                // =========================================

                else if (
                    rawMessage === '[attachment:file]'
                ) {

                    preview = lastMessageIsMine
                        ? 'You sent a file.'
                        : `${name} sent a file.`;

                }


                // =========================================
                // NORMAL TEXT MESSAGE
                // =========================================

                else if (rawMessage) {

                    const shortenedMessage =
                        rawMessage.length > 50
                            ? rawMessage.substring(0, 50) + '...'
                            : rawMessage;

                    preview = lastMessageIsMine
                        ? `You: ${shortenedMessage}`
                        : shortenedMessage;
                }

                const time =
                    formatConversationRelativeTime(
                        lastMessage.created_at
                    );

                // =========================================
                // LAST MESSAGE STATUS
                // Only show status if WE sent the message
                // =========================================

                

                let conversationMessageStatus = '';
                let conversationSeenHtml = '';

                if (lastMessageIsMine && lastMessage.message_id) {

                    // =====================================
                    // SEEN
                    // Show receiver profile picture
                    // =====================================

                    if (
                        lastMessage.is_read ||
                        lastMessage.read_at
                    ) {

                        let seenPicture =
                            otherParticipant.user_profile_picture ||
                            otherParticipant.profile_picture ||
                            '';

                        if (seenPicture) {

                            seenPicture = String(seenPicture);

                            if (
                                !/^https?:\/\//i.test(seenPicture) &&
                                !seenPicture.startsWith('/')
                            ) {
                                seenPicture =
                                    `/storage/${seenPicture.replace(/^storage\//, '')}`;
                            }

                            conversationSeenHtml = `
                                <img
                                    src="${escapeHtml(seenPicture)}"
                                    alt="${escapeHtml(name)}"
                                    title="Seen by ${escapeHtml(name)}"
                                    class="h-4 w-4 shrink-0 rounded-full object-cover"
                                    onerror="this.outerHTML='<div class=&quot;h-4 w-4 shrink-0 rounded-full bg-gray-300 flex items-center justify-center text-[7px] font-semibold text-gray-600&quot;>${escapeHtml(initials)}</div>'"
                                >
                            `;

                        } else {

                            conversationSeenHtml = `
                                <div
                                    title="Seen by ${escapeHtml(name)}"
                                    class="h-4 w-4 shrink-0 rounded-full bg-gray-300 flex items-center justify-center text-[7px] font-semibold text-gray-600"
                                >
                                    ${escapeHtml(initials)}
                                </div>
                            `;
                        }

                    }

                    // =====================================
                    // DELIVERED
                    // =====================================

                    else {

                        conversationMessageStatus = '';
                    }
                }

                

                const isActive =
                    Number(conv.conversation_id) ===
                    Number(currentConversationId);

                const activeClass =
                    isActive ? 'bg-gray-100' : '';

                // =========================================
                // CONVERSATION PROFILE PICTURE
                // =========================================

                let conversationAvatarHtml = '';

                let profilePicture =
                    otherParticipant.user_profile_picture ||
                    otherParticipant.profile_picture ||
                    '';

                if (profilePicture) {

                    profilePicture = String(profilePicture);

                    if (
                        !/^https?:\/\//i.test(profilePicture) &&
                        !profilePicture.startsWith('/')
                    ) {
                        profilePicture =
                            `/storage/${profilePicture.replace(/^storage\//, '')}`;
                    }

                    conversationAvatarHtml = `
                        <img
                            src="${escapeHtml(profilePicture)}"
                            alt="${escapeHtml(name)}"
                            class="h-10 w-10 rounded-full object-cover"
                            onerror="this.outerHTML='<div class=&quot;h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center text-xs font-semibold text-gray-600&quot;>${escapeHtml(initials)}</div>'"
                        >
                    `;

                } else {

                    conversationAvatarHtml = `
                        <div
                            class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center text-xs font-semibold text-gray-600"
                        >
                            ${escapeHtml(initials)}
                        </div>
                    `;
                }

                return `
                    <div
                        class="conversation-item group p-3 cursor-pointer transition-all duration-200 hover:bg-gray-50 ${activeClass}"
                        data-id="${conv.conversation_id}"
                    >

                        <div class="flex items-start gap-3">

                            <!-- ========================================= -->
                            <!-- AVATAR WITH ONLINE STATUS DOT -->
                            <!-- ========================================= -->

                            <div class="relative shrink-0">

                                ${conversationAvatarHtml}
                                <span
                                    class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white ${
                                        isOnline
                                            ? 'bg-emerald-500'
                                            : 'bg-gray-400'
                                    }"
                                    title="${
                                        isOnline
                                            ? 'Active now'
                                            : 'Offline'
                                    }"
                                ></span>

                            </div>

                            <div class="flex-1 min-w-0 pt-0.5">

                                <div class="flex items-center justify-between gap-2">

                                    <span
                                        class="text-sm font-semibold text-gray-900 truncate"
                                    >
                                        ${escapeHtml(name)}
                                    </span>



                                </div>

                                <!-- ========================================= -->
                                <!-- MESSAGE PREVIEW + STATUS -->
                                <!-- ========================================= -->

                                <div class="flex items-center justify-between gap-2 mt-0.5">

                                    <!-- ========================================= -->
                                    <!-- MESSAGE PREVIEW + TIME -->
                                    <!-- MESSAGE CAN SHRINK, TIME CANNOT -->
                                    <!-- ========================================= -->

                                    <div class="flex items-center gap-1 flex-1 min-w-0">

                                        <!-- ONLY MESSAGE TEXT GETS TRUNCATED -->
                                        <p
                                            class="conversation-preview text-xs truncate min-w-0 ${
                                                unreadCount > 0
                                                    ? 'font-semibold text-gray-900'
                                                    : 'text-gray-500'
                                            }"
                                            data-conversation-id="${conv.conversation_id}"
                                            data-original-preview="${escapeHtml(preview)}"
                                        >
                                            ${escapeHtml(preview)}
                                        </p>

                                        <!-- TIME ALWAYS STAYS VISIBLE -->
                                        ${
                                            lastMessage.created_at
                                                ? `
                                                    <span
                                                        class="conversation-relative-time shrink-0 whitespace-nowrap text-xs font-normal text-gray-400"
                                                        data-created-at="${escapeHtml(lastMessage.created_at)}"
                                                    >
                                                        · ${time}
                                                    </span>
                                                `
                                                : ''
                                        }

                                    </div>

                                    ${
                                        unreadCount > 0
                                            ? `
                                                <span
                                                    class="shrink-0 h-2.5 w-2.5 rounded-full bg-gray-900"
                                                    title="${unreadCount} unread ${
                                                        unreadCount === 1
                                                            ? 'message'
                                                            : 'messages'
                                                    }"
                                                ></span>
                                            `
                                            : conversationSeenHtml
                                                ? conversationSeenHtml

                                                : conversationMessageStatus
                                                    ? `
                                                        <span
                                                            class="conversation-message-status shrink-0 text-[10px] text-gray-400 font-medium"
                                                            data-message-id="${lastMessage.message_id}"
                                                        >
                                                            ${conversationMessageStatus}
                                                        </span>
                                                    `
                                                    : ''
                                    }

                                </div>

                            </div>

                            <button
                                type="button"
                                onclick="event.stopPropagation(); confirmDeleteConversation(${conv.conversation_id})"
                                class="conversation-delete-btn opacity-0 group-hover:opacity-100 h-7 w-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition"
                            >
                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                            </button>

                        </div>

                    </div>
                `;

            }).join('');

            lucideCreateIcons();
        }

        // =====================================================
        // MARK CONVERSATION AS READ
        //
        // PURPOSE:
        // When the user opens a conversation, all messages
        // received from the other user become read.
        // =====================================================

        async function markConversationAsRead(conversationId) {

            try {

                const response = await fetch(
                    `/messages/conversations/${conversationId}/read`, {
                        method: 'POST',

                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    }
                );


                // =============================================
                // STOP IF REQUEST FAILED
                // =============================================

                if (!response.ok) {

                    console.error(
                        'Failed to mark conversation as read.'
                    );

                    return;
                }


                // =============================================
                // REFRESH LEFT CONVERSATION LIST
                //
                // This removes the unread badge.
                // Example: 1 disappears after opening chat.
                // =============================================

                await loadModalConversations();
                await updateTopbarMessageBadge();

            } catch (error) {

                console.error(
                    'Mark as read error:',
                    error
                );

            }
        }

        async function openModalConversation(conversationId) {
            cancelReply();
            currentConversationId = conversationId;
            messagesPage = 1;
            hasMoreMessages = true;

            document.querySelectorAll('#modalConversationsList .conversation-item').forEach(el => {
                el.classList.toggle('bg-gray-100', el.dataset.id == conversationId);
            });

            const chatEmpty = document.getElementById('modalChatEmptyState');
            const messagesContainer = document.getElementById('modalMessagesContainer');
            const chatHeader = document.getElementById('modalChatHeader');
            const composer = document.getElementById('modalComposer');
            const chatArea = document.getElementById('modalChatArea');

            if (chatEmpty) chatEmpty.classList.add('hidden');
            if (messagesContainer) messagesContainer.classList.remove('hidden');
            if (chatHeader) chatHeader.classList.remove('hidden');
            if (composer) composer.classList.remove('hidden');

            if (chatArea) {
                chatArea.classList.remove('hidden');
                chatArea.classList.add('md:flex');
            }

            const response = await fetch(`/messages/conversations/${conversationId}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) return;

            const data = await response.json();
            const conversation = data.data;

            const otherParticipant =
                conversation.participants?.find(
                    p => p.user?.user_id !== currentUserId
                )?.user || {};

            const name =
                otherParticipant.user_full_name || 'Unknown';

            currentConversationUserName = name;
            currentConversationUser = otherParticipant;

            // =====================================================
            // USER INFORMATION
            // =====================================================

            const role =
                otherParticipant.role?.role_name || '';

            const lastActiveAt =
                otherParticipant.last_active_at;


            // =====================================================
            // ONLINE STATUS
            // =====================================================

            const activityStatus =
                formatUserActivity(
                    lastActiveAt
                );


            // =====================================================
            // UPDATE CHAT HEADER
            // =====================================================

            document.getElementById(
                'modalChatTitle'
            ).textContent = name;

            document.getElementById(
                'modalChatSubtitle'
            ).textContent = activityStatus;

            document.getElementById(
                'modalChatAvatar'
            ).innerHTML = getInitials(name);


            // =====================================================
            // LOAD EXISTING MESSAGES
            // =====================================================

            // =====================================================
            // LOAD EXISTING MESSAGES
            // =====================================================

            await loadModalMessages(
                conversationId
            );


            // =====================================================
            // MARK MESSAGES AS READ
            //
            // This removes the unread badge when the user
            // actually opens the conversation.
            // =====================================================

            await markConversationAsRead(
                conversationId
            );


            // =====================================================
            // START REAL TIME LISTENER
            // =====================================================

            listenToConversationRealtime(
                conversationId
            );
        }

        // =====================================================
        // SEND TYPING STATUS
        // =====================================================

        function sendTypingWhisper(isTyping) {

            if (
                !window.Echo ||
                !currentConversationId
            ) {
                return;
            }


            // =============================================
            // SEND TEMPORARY REALTIME EVENT
            // =============================================

            window.Echo
                .private(
                    `conversation.${currentConversationId}`
                )
                .whisper('typing', {
                    user_id: currentUserId,
                    is_typing: isTyping
                });
        }

        // =====================================================
        // SEND GLOBAL TYPING STATUS TO SERVER
        // =====================================================

        async function sendGlobalTypingStatus(
            conversationId,
            isTyping
        ) {

            if (!conversationId) {
                return;
            }


            try {

                await fetch(
                    `/messages/conversations/${conversationId}/typing`,
                    {
                        method: 'POST',

                        headers: {
                            'Content-Type':
                                'application/json',

                            'Accept':
                                'application/json',

                            'X-CSRF-TOKEN':
                                document.querySelector(
                                    'meta[name="csrf-token"]'
                                )?.content ?? '',
                        },

                        body: JSON.stringify({
                            is_typing: isTyping,
                        }),
                    }
                );

            } catch (error) {

                console.error(
                    'Failed to send global typing status:',
                    error
                );
            }
        }

        // =====================================================
        // DETECT CURRENT USER TYPING
        // =====================================================

        const messageInput =
            document.getElementById('modalMessageInput');

        if (messageInput) {

            messageInput.addEventListener('input', function () {

                if (!currentConversationId) {
                    return;
                }


                // =============================================
                // EMPTY INPUT MEANS USER STOPPED TYPING
                // =============================================

                if (!this.value.trim()) {

                    clearTimeout(typingTimeout);


                    // =========================================
                    // STOP LOCAL CONVERSATION WHISPER
                    // =========================================

                    sendTypingWhisper(false);


                    // =========================================
                    // STOP GLOBAL TYPING INDICATOR
                    //
                    // Only send this if we previously told
                    // Laravel that we started typing.
                    // =========================================

                    if (globalTypingSent) {

                        globalTypingSent = false;

                        sendGlobalTypingStatus(
                            currentConversationId,
                            false
                        );
                    }


                    return;
                }


                // =============================================
                // CURRENT OPEN CONVERSATION
                //
                // Save this because currentConversationId
                // could change before the timeout finishes.
                // =============================================

                const typingConversationId =
                    currentConversationId;


                // =============================================
                // REALTIME WHISPER
                //
                // This can run on every keystroke because
                // it travels through the WebSocket.
                // =============================================

                sendTypingWhisper(true);


                // =============================================
                // GLOBAL TYPING STARTED
                //
                // Only POST once when typing begins.
                // =============================================

                if (!globalTypingSent) {

                    globalTypingSent = true;

                    sendGlobalTypingStatus(
                        typingConversationId,
                        true
                    );
                }


                // =============================================
                // RESET STOP TIMER
                // =============================================

                clearTimeout(typingTimeout);


                // =============================================
                // IF NO KEYSTROKE FOR 1.5 SECONDS,
                // CONSIDER USER FINISHED TYPING
                // =============================================

                typingTimeout = setTimeout(() => {


                    // =========================================
                    // STOP OPEN CHAT WHISPER
                    // =========================================

                    sendTypingWhisper(false);


                    // =========================================
                    // STOP GLOBAL TYPING INDICATOR
                    // =========================================

                    if (globalTypingSent) {

                        globalTypingSent = false;

                        sendGlobalTypingStatus(
                            typingConversationId,
                            false
                        );
                    }


                }, 1500);
            });
        }

        // =====================================================
        // MESSENGER STYLE MESSAGE UI
        // =====================================================

        function getFullMessageDateTime(value) {
            if (!value) return '';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return '';
            return date.toLocaleString('en-US', {
                weekday: 'long',
                month: 'short',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit'
            });
        }

        function getMessageAvatarHtml(msg, senderName) {
            const picture = msg.sender?.user_profile_picture || msg.sender?.profile_picture || '';
            const initials = String(senderName || '?')
                .split(/\s+/).filter(Boolean).slice(0, 2)
                .map(part => part.charAt(0)).join('').toUpperCase() || '?';

            if (picture) {
                let src = String(picture);
                if (!/^https?:\/\//i.test(src) && !src.startsWith('/')) {
                    src = `/storage/${src.replace(/^storage\//, '')}`;
                }
                return `<img src="${escapeHtml(src)}" alt="${escapeHtml(senderName)}" class="h-8 w-8 shrink-0 rounded-full object-cover" onerror="this.outerHTML='<div class=&quot;h-8 w-8 shrink-0 rounded-full bg-gray-200 flex items-center justify-center text-[10px] font-semibold text-gray-600&quot;>${escapeHtml(initials)}</div>'">`;
            }

            return `<div class="h-8 w-8 shrink-0 rounded-full bg-gray-200 flex items-center justify-center text-[10px] font-semibold text-gray-600">${escapeHtml(initials)}</div>`;
        }

        function getMessageMoreMenuHtml(isOwn, isUnsent) {
            const editItem = isOwn && !isUnsent ? `
                <button type="button" class="message-action-item message-edit-btn flex w-full items-center gap-3 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50">
                    <i data-lucide="pencil" class="h-4 w-4"></i><span>Edit</span>
                </button>` : '';
            const destructiveItem = isOwn ? `
                <button type="button" class="message-action-item message-unsend-btn flex w-full items-center gap-3 px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50">
                    <i data-lucide="undo-2" class="h-4 w-4"></i><span>Unsend</span>
                </button>` : `
                <button type="button" class="message-action-item message-remove-btn flex w-full items-center gap-3 px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50">
                    <i data-lucide="trash-2" class="h-4 w-4"></i><span>Remove</span>
                </button>`;
            const forwardItem = !isUnsent ? `
                <button type="button" class="message-action-item message-forward-btn flex w-full items-center gap-3 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50">
                    <i data-lucide="forward" class="h-4 w-4"></i><span>Forward</span>
                </button>` : '';

            return `
                <div class="message-more relative">
                    <button type="button" class="message-more-btn flex h-7 w-7 items-center justify-center rounded-full text-gray-400 opacity-0 transition group-hover:opacity-100 hover:bg-gray-100 hover:text-gray-700" title="More">
                        <i data-lucide="more-vertical" class="h-4 w-4"></i>
                    </button>
                    <div class="message-more-menu absolute bottom-full ${isOwn ? 'left-0' : 'right-0'} z-50 mb-2 hidden min-w-[150px] overflow-hidden rounded-xl border border-gray-200 bg-white py-1 shadow-xl">
                        ${destructiveItem}
                        ${forwardItem}
                        ${editItem}
                        <button type="button" class="message-action-item message-pin-btn flex w-full items-center gap-3 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50">
                            <i data-lucide="pin" class="h-4 w-4"></i><span>Pin</span>
                        </button>
                    </div>
                </div>`;
        }

        // =====================================================
        // SEEN AVATAR FOR OWN MESSAGES
        // Shows the other participant profile beside a message they read.
        // =====================================================
        function getSeenAvatarHtml(msg) {
            if (!(msg.is_read || msg.read_at) || !currentConversationUser) return '';

            const name = currentConversationUser.user_full_name || currentConversationUser.name || currentConversationUserName || 'User';
            const picture = currentConversationUser.user_profile_picture || currentConversationUser.profile_picture || '';
            const initials = getInitials(name);

            if (picture) {
                let src = String(picture);
                if (!/^https?:\/\//i.test(src) && !src.startsWith('/')) {
                    src = `/storage/${src.replace(/^storage\//, '')}`;
                }
                return `<img src="${escapeHtml(src)}" alt="${escapeHtml(name)}" title="Seen by ${escapeHtml(name)}" class="message-seen-avatar h-4 w-4 shrink-0 rounded-full object-cover" onerror="this.outerHTML='<div title=&quot;Seen by ${escapeHtml(name)}&quot; class=&quot;message-seen-avatar h-4 w-4 shrink-0 rounded-full bg-gray-300 flex items-center justify-center text-[7px] font-semibold text-gray-600&quot;>${escapeHtml(initials)}</div>'">`;
            }

            return `<div title="Seen by ${escapeHtml(name)}" class="message-seen-avatar h-4 w-4 shrink-0 rounded-full bg-gray-300 flex items-center justify-center text-[7px] font-semibold text-gray-600">${escapeHtml(initials)}</div>`;
        }

        // =====================================================
        // MESSENGER STYLE SEEN AVATAR
        //
        // Only the latest seen message displays the
        // other participant's profile picture.
        // =====================================================

        function refreshSeenAvatar() {
            refreshMessengerMessageGroups();
            refreshLatestOutgoingStatus();
        }

        // =====================================================
        // MESSENGER STYLE MESSAGE GROUPING
        //
        // Incoming consecutive messages only show the sender
        // avatar beside the final message in that sender group.
        // =====================================================
        function refreshMessengerMessageGroups() {
            const container = document.getElementById('modalMessagesContainer');
            if (!container) return;

            const rows = Array.from(
                container.querySelectorAll('.message-row')
            );

            // Hide every incoming avatar first.
            rows.forEach(row => {
                const avatar = row.querySelector('.message-sender-avatar');
                if (avatar) {
                    avatar.classList.add('invisible');
                }
            });

            rows.forEach((row, index) => {
                if (row.dataset.messageOwn === '1') return;

                const nextRow = rows[index + 1] || null;
                const nextIsSameSender =
                    nextRow &&
                    nextRow.dataset.messageOwn === '0' &&
                    nextRow.dataset.messageSender === row.dataset.messageSender;

                // =================================================
                // REPLY MESSAGE AVATAR RULE
                //
                // A received reply always keeps the sender profile
                // picture beside it, even when several replies from
                // the same sender are consecutive.
                //
                // Normal consecutive messages still use Messenger
                // grouping and show the avatar only on the last one.
                // =================================================
                const isReplyMessage =
                    row.dataset.messageReply === '1';

                if (isReplyMessage || !nextIsSameSender) {
                    row.querySelector('.message-sender-avatar')
                        ?.classList.remove('invisible');
                }
            });

            requestAnimationFrame(() => {
                alignMessageAvatars();
            });
        }

        function alignMessageAvatars() {

            const container =
                document.getElementById('modalMessagesContainer');

            if (!container) return;

            container
                .querySelectorAll(
                    '.message-row[data-message-own="0"]'
                )
                .forEach(row => {

                    const avatar =
                        row.querySelector('.message-sender-avatar');

                    const bubble =
                        row.querySelector('.message-bubble');

                    if (!avatar || !bubble) {
                        return;
                    }

                    // =========================================
                    // RESET FIRST
                    // =========================================

                    avatar.style.transform = '';


                    // =========================================
                    // GET POSITIONS INSIDE THIS MESSAGE ROW
                    // =========================================

                    const rowRect =
                        row.getBoundingClientRect();

                    const bubbleRect =
                        bubble.getBoundingClientRect();

                    const avatarRect =
                        avatar.getBoundingClientRect();


                    // =========================================
                    // FIND CENTER OF ACTUAL MESSAGE BUBBLE
                    // =========================================

                    const bubbleCenter =
                        bubbleRect.top -
                        rowRect.top +
                        (bubbleRect.height / 2);


                    // =========================================
                    // FIND WHERE AVATAR CENTER CURRENTLY IS
                    // =========================================

                    const avatarCenter =
                        avatarRect.top -
                        rowRect.top +
                        (avatarRect.height / 2);


                    // =========================================
                    // MOVE AVATAR SO BOTH CENTERS MATCH
                    // =========================================

                    const offset =
                        bubbleCenter - avatarCenter;

                    avatar.style.transform =
                        `translateY(${offset}px)`;
                });
        }

        // =====================================================
        // LATEST OUTGOING MESSAGE STATUS
        //
        // Sent / Delivered / Seen belongs only to the newest
        // message sent by the current user. Seen is represented
        // by the receiver's profile picture instead of text.
        // =====================================================
        function refreshLatestOutgoingStatus() {

            const container =
                document.getElementById('modalMessagesContainer');

            if (!container) return;


            // =========================================
            // GET ALL OF OUR MESSAGES
            // =========================================
            const ownRows = Array.from(
                container.querySelectorAll(
                    '.message-row[data-message-own="1"][data-message-unsent="0"]'
                )
            );


            // =========================================
            // HIDE OLD STATUSES
            // =========================================
            container
                .querySelectorAll('.message-status-wrapper')
                .forEach(wrapper => {

                    wrapper.classList.add('hidden');
                    wrapper.classList.remove('flex');

                });


            // =========================================
            // REMOVE OLD SEEN AVATARS
            // =========================================
            container
                .querySelectorAll('.message-seen-avatar')
                .forEach(avatar => avatar.remove());


            if (ownRows.length === 0) return;


            // =========================================
            // ONLY LATEST OUTGOING MESSAGE
            // =========================================
            const latestRow =
                ownRows[ownRows.length - 1];

            const wrapper =
                latestRow.querySelector(
                    '.message-status-wrapper'
                );

            const status =
                latestRow.querySelector(
                    '.message-read-status'
                );

            const icon =
                latestRow.querySelector(
                    '.message-status-icon'
                );

            const time =
                latestRow.querySelector(
                    '.message-status-time'
                );


            if (!wrapper || !status) return;


            const currentStatus =
                status.dataset.messageStatus ||
                status.textContent.trim() ||
                'Sent';


            wrapper.classList.remove('hidden');
            wrapper.classList.add('flex');


            // =========================================
            // SEEN
            // Messenger shows receiver profile picture
            // instead of check + text + time
            // =========================================
            if (
                currentStatus.toLowerCase() === 'seen'
            ) {

                status.classList.add('hidden');

                if (icon) {
                    icon.classList.add('hidden');
                }

                if (time) {
                    time.classList.add('hidden');
                }


                if (currentConversationUser) {

                    const fakeMessage = {
                        is_read: true,
                        read_at: new Date().toISOString()
                    };

                    wrapper.insertAdjacentHTML(
                        'beforeend',
                        getSeenAvatarHtml(fakeMessage)
                    );
                }

                return;
            }


            // =========================================
            // SENT / DELIVERED
            // Show check + status + relative time
            // =========================================
            status.classList.remove('hidden');

            if (icon) {
                icon.classList.remove('hidden');
            }

            if (time) {

                time.classList.remove('hidden');

                time.textContent =
                    formatMessageRelativeTime(
                        time.dataset.createdAt
                    );
            }

            status.textContent = currentStatus;

            lucideCreateIcons();
        }

        function renderMessengerMessageRow(msg, isOwn) {
            const senderName = msg.sender?.name || msg.sender?.user_full_name || (isOwn ? 'You' : 'Unknown');
            const attachment = msg.attachment || null;
            const isUnsent = Boolean(msg.is_unsent);
            const isForwarded = Boolean(
                msg.is_forwarded ||
                msg.forwarded_from_message_id ||
                msg.forwarded_message_id ||
                msg.original_message_id ||
                msg.forwarded_from_id ||
                msg.forwarded_from?.message_id ||
                msg.forwarded_message?.message_id
            );
            const fullTime = getFullMessageDateTime(msg.created_at);
            const content = isUnsent ? '' : (msg.message_content || '');
            const displayContent = content === '[attachment:image]' || content === '[attachment:file]' ? '' : content;
            const avatar = isOwn ? '' : getMessageAvatarHtml(msg, senderName);
            const actions = `
                <div
                    class="
                        message-hover-actions
                        flex
                        shrink-0
                        items-center
                        gap-0.5
                        opacity-0
                        transition
                        group-hover:opacity-100
                    "
                >
                    ${getReactionPickerHtml()}

                    <button
                        type="button"
                        class="
                            message-reply-btn
                            flex
                            h-7
                            w-7
                            items-center
                            justify-center
                            rounded-full
                            text-gray-400
                            hover:bg-gray-100
                            hover:text-gray-700
                        "
                        title="Reply"
                    >
                        <i
                            data-lucide="reply"
                            class="h-4 w-4"
                        ></i>
                    </button>

                    ${getMessageMoreMenuHtml(
                        isOwn,
                        isUnsent
                    )}
                </div>
            `;

            // =====================================================
            // MESSENGER STYLE REPLY INDICATOR
            //
            // This is the ONLY "replied to" text.
            // It sits above the quoted/original message.
            // =====================================================
            const replyTargetIsCurrentUser =
                Number(msg.reply_to?.sender_id ?? msg.reply_to?.sender?.user_id) ===
                Number(currentUserId);

            const replyTargetName =
                replyTargetIsCurrentUser
                    ? 'you'
                    : (
                        msg.reply_to?.sender?.user_full_name ||
                        msg.reply_to?.sender?.name ||
                        'a message'
                    );

            const replyEditedLabel = !isUnsent && msg.reply_to ? `
                <div class="mb-1 flex items-center gap-1 px-1 text-[11px] text-gray-500">
                    <i data-lucide="reply" class="h-3.5 w-3.5 shrink-0"></i>
                    <span>${isOwn ? 'You' : escapeHtml(senderName)} replied to ${escapeHtml(replyTargetName)}</span>
                    ${msg.is_edited ? '<span class="mx-1">·</span><span>Edited</span>' : ''}
                </div>` : '';

            const forwardedLabel = !isUnsent && isForwarded ? `
                <div class="mb-1 flex items-center gap-1 px-1 text-[11px] text-gray-500">
                    <i data-lucide="forward" class="h-3 w-3"></i>
                    <span>${isOwn ? 'You forwarded a message' : `${escapeHtml(senderName)} forwarded a message`}</span>
                </div>` : '';

            const editedOnlyLabel = !isUnsent && msg.is_edited && !msg.reply_to ? `
                <div class="mb-1 px-1 text-[11px] text-gray-500">Edited</div>` : '';

            // =====================================================
            // MESSAGE BUBBLE
            // Compact message + bottom-right reaction placement
            // =====================================================

            const bubble = `
                <div
                    class="
                        relative
                        flex
                        w-fit
                        max-w-[70%]
                        flex-col
                        ${isOwn ? 'items-end' : 'items-start'}
                    "
                >

                    ${forwardedLabel}
                    ${replyEditedLabel}
                    ${editedOnlyLabel}


                    <!-- ===================================== -->
                    <!-- REPLY PREVIEW -->
                    <!-- ===================================== -->

                    ${!isUnsent && msg.reply_to
                        ? `
                            <div
                                class="
                                    relative
                                    z-0
                                    w-fit
                                    max-w-full
                                    ${isOwn ? 'self-end' : 'self-start'}
                                "
                            >
                                ${getReplyQuoteHtml(
                                    msg.reply_to,
                                    isOwn
                                )}
                            </div>
                        `
                        : ''
                    }


                    <!-- ===================================== -->
                    <!-- REAL MESSAGE ROW -->
                    <!-- ===================================== -->

                    <div
                        class="
                            message-bubble-line
                            flex
                            w-fit
                            max-w-full
                            items-center
                            gap-1
                            ${isOwn ? 'self-end' : 'self-start'}
                        "
                    >

                        ${isOwn ? actions : ''}


                        <!-- ================================= -->
                        <!-- MESSAGE + REACTION CONTAINER -->
                        <!--
                            IMPORTANT:
                            This stays w-fit so short messages
                            remain compact.

                            The reaction is positioned relative
                            to THIS exact message.
                        -->
                        <!-- ================================= -->

                        <div
                            class="
                                message-content-wrapper
                                relative
                                inline-flex
                                w-fit
                                max-w-full
                                flex-col
                            "
                        >

                            <!-- ============================= -->
                            <!-- ACTUAL MESSAGE BUBBLE -->
                            <!-- ============================= -->

                            <div
                                class="
                                    message-bubble
                                    relative
                                    z-10
                                    inline-flex
                                    w-fit
                                    max-w-full
                                    flex-col

                                    ${isUnsent
                                        ? 'rounded-2xl border border-gray-300 bg-white text-gray-500'
                                        : isOwn
                                            ? 'rounded-2xl rounded-br-sm bg-gray-900 text-white'
                                            : 'rounded-2xl rounded-bl-sm bg-gray-100 text-gray-900'
                                    }

                                    px-3.5
                                    py-2
                                "
                                title="${escapeHtml(fullTime)}"
                            >

                                ${isUnsent
                                    ? `
                                        <p class="text-sm italic">
                                            This message was unsent
                                        </p>
                                    `
                                    : `
                                        ${displayContent
                                            ? `
                                                <span
                                                    class="
                                                        block
                                                        text-left
                                                        text-sm
                                                        leading-snug
                                                        whitespace-pre-wrap
                                                        break-words
                                                    "
                                                >${escapeHtml(displayContent)}</span>
                                            `
                                            : ''
                                        }

                                        ${attachment
                                            ? getAttachmentPreviewHtml(
                                                attachment,
                                                formatMessageTime(
                                                    msg.created_at
                                                )
                                            )
                                            : ''
                                        }
                                    `
                                }

                            </div>


                            <!-- ============================= -->
                            <!-- REACTION -->
                            <!--
                                Anchored to bottom-right of
                                the ACTUAL message.

                                It no longer affects the width
                                of the message bubble.
                            -->
                            <!-- ============================= -->

                            ${isUnsent
                                ? ''
                                : `
                                    <div
                                        class="
                                            message-reactions
                                            absolute
                                            -bottom-3
                                            right-1
                                            z-20
                                            flex
                                            items-center
                                        "
                                    >
                                        ${getMessageReactionsHtml(
                                            msg.reactions
                                        )}
                                    </div>
                                `
                            }

                        </div>


                        ${!isOwn ? actions : ''}

                    </div>


                    <!-- ===================================== -->
                    <!-- SENT / DELIVERED / SEEN -->
                    <!-- ===================================== -->

                    ${isOwn && !isUnsent
                        ? `
                            <div
                                class="
                                    message-status-wrapper
                                    mt-3
                                    hidden
                                    items-center
                                    justify-end
                                    gap-1
                                    text-gray-400
                                "
                            >

                                <!-- ========================= -->
                                <!-- SENT / DELIVERED -->
                                <!-- ========================= -->

                                <span
                                    class="
                                        message-read-status
                                        text-[11px]
                                        font-normal
                                    "
                                    data-message-id="${msg.message_id}"
                                    data-message-status="${escapeHtml(
                                        getMessageStatus(msg)
                                    )}"
                                >
                                    ${getMessageStatus(msg)}
                                </span>


                                <!-- ========================= -->
                                <!-- RELATIVE TIME -->
                                <!-- ========================= -->

                                <span
                                    class="
                                        message-status-time
                                        text-[11px]
                                        font-normal
                                    "
                                    data-created-at="${escapeHtml(
                                        msg.created_at || ''
                                    )}"
                                >
                                    ${formatMessageRelativeTime(
                                        msg.created_at
                                    )}
                                </span>

                            </div>
                        `
                        : ''
                    }

                </div>
            `;

            return `
                <div
                    class="
                        message-row
                        group
                        relative
                        flex
                        ${isOwn
                            ? 'items-center justify-end'
                            : 'items-end justify-start'
                        }
                        gap-1.5
                    "
                    data-message-id="${msg.message_id}"
                    data-message-sender="${escapeHtml(senderName)}"
                    data-message-content="${escapeHtml(content)}"
                    data-message-created-at="${escapeHtml(msg.created_at || '')}"
                    data-message-own="${isOwn ? '1' : '0'}"
                    data-message-reply="${msg.reply_to ? '1' : '0'}"
                    data-message-unsent="${isUnsent ? '1' : '0'}"
                >

                    ${isOwn
                        ? bubble
                        : `
                            <div
                                class="
                                    message-sender-avatar
                                    shrink-0
                                "
                            >
                                ${avatar}
                            </div>

                            ${bubble}
                        `
                    }

                </div>
            `;
        }

        function closeAllMessageMenus(except = null) {
            document.querySelectorAll('.message-more-menu').forEach(menu => {
                if (menu !== except) menu.classList.add('hidden');
            });
        }

        function showMessageActionDialog({ title, text, confirmText, danger = false }) {
            return new Promise(resolve => {
                document.getElementById('messageActionConfirmOverlay')?.remove();
                const overlay = document.createElement('div');
                overlay.id = 'messageActionConfirmOverlay';
                overlay.className = 'fixed inset-0 z-[10000] flex items-center justify-center bg-black/40 p-4';
                overlay.innerHTML = `
                    <div class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl">
                        <h3 class="text-lg font-semibold text-gray-900">${escapeHtml(title)}</h3>
                        <p class="mt-2 text-sm leading-6 text-gray-600">${escapeHtml(text)}</p>
                        <div class="mt-5 flex justify-end gap-2">
                            <button type="button" data-cancel class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100">Cancel</button>
                            <button type="button" data-confirm class="rounded-lg px-4 py-2 text-sm font-semibold ${danger ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-gray-900 text-white hover:bg-black'}">${escapeHtml(confirmText)}</button>
                        </div>
                    </div>`;
                document.body.appendChild(overlay);
                const finish = value => { overlay.remove(); resolve(value); };
                overlay.querySelector('[data-cancel]').onclick = () => finish(false);
                overlay.querySelector('[data-confirm]').onclick = () => finish(true);
                overlay.addEventListener('click', e => { if (e.target === overlay) finish(false); });
            });
        }

        // =====================================================
        // MESSENGER STYLE EDIT IN COMPOSER
        // =====================================================

        function cancelMessageEdit() {
            editingMessageRow = null;
            document.getElementById('modalEditHeader')?.remove();

            const input = document.getElementById('modalMessageInput');
            const messages = document.getElementById('modalMessagesContainer');
            const attachmentButton = document.getElementById('modalAttachmentButton');

            if (input) {
                input.value = '';
                input.placeholder = 'Type a message...';
                input.style.height = 'auto';
            }
            if (messages) {
                messages.classList.remove('opacity-40');
                messages.style.pointerEvents = '';
            }
            if (attachmentButton) attachmentButton.classList.remove('hidden');
        }

        function startEditMessage(row) {
            cancelReply();
            editingMessageRow = row;

            const composer = document.getElementById('modalComposer');
            const form = document.getElementById('modalMessageForm');
            const input = document.getElementById('modalMessageInput');
            const messages = document.getElementById('modalMessagesContainer');
            const attachmentButton = document.getElementById('modalAttachmentButton');

            if (!composer || !form || !input) return;

            document.getElementById('modalEditHeader')?.remove();

            const header = document.createElement('div');
            header.id = 'modalEditHeader';
            header.className = 'mb-2 flex items-center justify-between px-1';
            header.innerHTML = `
                <span class="text-sm font-semibold text-gray-900">Edit message</span>
                <button type="button" id="modalCancelEdit" class="flex h-7 w-7 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100" title="Cancel edit">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>`;
            form.parentNode.insertBefore(header, form);

            input.value = row.dataset.messageContent || '';
            input.placeholder = 'Edit message';
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 120) + 'px';
            input.focus();
            input.setSelectionRange(input.value.length, input.value.length);

            if (messages) {
                messages.classList.add('opacity-40');
                messages.style.pointerEvents = 'none';
            }
            if (attachmentButton) attachmentButton.classList.add('hidden');

            document.getElementById('modalCancelEdit').onclick = cancelMessageEdit;
            lucideCreateIcons();
        }

        async function editMessageFromRow(row) {
            startEditMessage(row);
        }

        async function saveEditedMessage() {
            if (!editingMessageRow) return false;

            const row = editingMessageRow;
            const input = document.getElementById('modalMessageInput');
            const nextContent = input?.value.trim() || '';
            const oldContent = row.dataset.messageContent || '';

            if (!nextContent) return true;
            if (nextContent === oldContent.trim()) {
                cancelMessageEdit();
                return true;
            }

            const response = await fetch(`/messages/conversations/${currentConversationId}/messages/${row.dataset.messageId}/edit`, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ message_content: nextContent })
            });

            if (!response.ok) return true;

            const data = await response.json();
            const updated = data.data;
            updated.sender = updated.sender || {
                user_id: currentUserId,
                user_full_name: row.dataset.messageSender || 'You'
            };

            row.outerHTML = renderMessengerMessageRow(updated, true);
            cancelMessageEdit();
            lucideCreateIcons();
            refreshMessageDateSeparators();
            return true;
        }

        function showUnsendChoiceDialog() {
            return new Promise(resolve => {
                document.getElementById('messageUnsendOverlay')?.remove();

                const overlay = document.createElement('div');
                overlay.id = 'messageUnsendOverlay';
                overlay.className = 'fixed inset-0 z-[10000] flex items-center justify-center bg-black/50 p-4';
                overlay.innerHTML = `
                    <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
                        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                            <h3 class="text-lg font-semibold text-gray-900">Who do you want to unsend this message for?</h3>
                            <button type="button" data-unsend-cancel class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200">
                                <i data-lucide="x" class="h-5 w-5"></i>
                            </button>
                        </div>
                        <div class="p-3">
                            <button type="button" data-unsend-choice="everyone" class="w-full rounded-xl px-4 py-3 text-left hover:bg-gray-50">
                                <p class="text-sm font-semibold text-gray-900">Unsend for everyone</p>
                                <p class="mt-1 text-xs leading-5 text-gray-500">This message will be unavailable to everyone in the chat.</p>
                            </button>
                            <button type="button" data-unsend-choice="you" class="mt-1 w-full rounded-xl px-4 py-3 text-left hover:bg-gray-50">
                                <p class="text-sm font-semibold text-gray-900">Unsend for you</p>
                                <p class="mt-1 text-xs leading-5 text-gray-500">This message will be removed from your account. Other chat members can still see it.</p>
                            </button>
                        </div>
                    </div>`;

                document.body.appendChild(overlay);
                lucideCreateIcons();

                const finish = value => {
                    overlay.remove();
                    resolve(value);
                };

                overlay.querySelector('[data-unsend-cancel]').onclick = () => finish(null);
                overlay.addEventListener('click', event => {
                    if (event.target === overlay) finish(null);
                    const choice = event.target.closest('[data-unsend-choice]');
                    if (choice) finish(choice.dataset.unsendChoice);
                });
            });
        }

        async function unsendMessageFromRow(row) {
            const choice = await showUnsendChoiceDialog();
            if (!choice) return;

            if (choice === 'you') {
                const response = await fetch(`/messages/conversations/${currentConversationId}/messages/${row.dataset.messageId}/remove`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                });
                if (response.ok) row.remove();
                return;
            }

            const response = await fetch(`/messages/conversations/${currentConversationId}/messages/${row.dataset.messageId}/unsend`, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            });
            if (!response.ok) return;

            row.dataset.messageUnsent = '1';
            row.dataset.messageContent = '';
            const bubble = row.querySelector('.message-bubble');
            if (bubble) {
                bubble.className = 'message-bubble relative w-fit max-w-full rounded-2xl border border-gray-300 bg-white text-gray-500 px-3.5 py-2';
                bubble.innerHTML = '<p class="text-sm italic">This message was unsent</p>';
            }
            row.querySelectorAll('.message-reaction-picker, .message-reply-btn, .message-read-status').forEach(el => el.remove());
            const menu = row.querySelector('.message-more-menu');
            if (menu) menu.innerHTML = '<button type="button" class="message-action-item message-pin-btn flex w-full items-center gap-3 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"><i data-lucide="pin" class="h-4 w-4"></i><span>Pin</span></button>';
            lucideCreateIcons();
        }

        async function removeMessageFromRow(row) {
            const confirmed = await showMessageActionDialog({
                title: 'Remove for you',
                text: 'This will remove the message from your devices. Other chat members will still be able to see it.',
                confirmText: 'Remove', danger: false
            });
            if (!confirmed) return;
            const response = await fetch(`/messages/conversations/${currentConversationId}/messages/${row.dataset.messageId}/remove`, {
                method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            });
            if (response.ok) row.remove();
        }

        // =====================================================
        // PINNED MESSAGES MODAL
        // =====================================================

        function openPinnedMessagesModal() {

            const modal =
                document.getElementById('pinnedMessagesModal');

            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');

            loadPinnedMessages();

            lucideCreateIcons();
        }


        function closePinnedMessagesModal() {

            const modal =
                document.getElementById('pinnedMessagesModal');

            if (!modal) {
                return;
            }

            modal.classList.add('hidden');
        }


        // =====================================================
        // LOAD PINNED MESSAGES
        // =====================================================

        async function loadPinnedMessages() {

            if (!currentConversationId) {
                return;
            }

            const container =
                document.getElementById('pinnedMessagesList');

            if (!container) {
                return;
            }

            container.innerHTML = `
                <div class="py-10 text-center text-sm text-gray-400">
                    Loading pinned messages...
                </div>
            `;

            try {

                const response = await fetch(
                    `/messages/conversations/${currentConversationId}/pinned`,
                    {
                        headers: {
                            'Accept': 'application/json'
                        }
                    }
                );

                if (!response.ok) {

                    container.innerHTML = `
                        <div class="py-10 text-center text-sm text-gray-400">
                            Unable to load pinned messages.
                        </div>
                    `;

                    return;
                }

                const result = await response.json();

                const messages =
                    result.data || [];

                if (!messages.length) {

                    container.innerHTML = `
                        <div class="py-10 text-center text-sm text-gray-400">
                            No pinned messages.
                        </div>
                    `;

                    return;
                }

                container.innerHTML =
                    messages.map(message => {

                        const sender =
                            message.sender || {};

                        const senderName =
                            sender.user_full_name ||
                            'User';

                        const initials =
                            getInitials(senderName);

                        let picture =
                            sender.user_profile_picture ||
                            sender.profile_picture ||
                            '';

                        let avatarHtml = '';

                        if (picture) {

                            picture = String(picture);

                            if (
                                !/^https?:\/\//i.test(picture) &&
                                !picture.startsWith('/')
                            ) {
                                picture =
                                    `/storage/${picture.replace(/^storage\//, '')}`;
                            }

                            avatarHtml = `
                                <img
                                    src="${escapeHtml(picture)}"
                                    class="h-8 w-8 shrink-0 rounded-full object-cover"
                                >
                            `;

                        } else {

                            avatarHtml = `
                                <div
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-200 text-[10px] font-semibold text-gray-600"
                                >
                                    ${escapeHtml(initials)}
                                </div>
                            `;
                        }

                        return `
                            <div class="border-b border-gray-100 py-4 last:border-0">

                                <div class="mb-2 flex items-center justify-between gap-3">

                                    <span class="text-xs text-gray-500">
                                        ${escapeHtml(senderName)}
                                    </span>

                                    <span class="text-xs text-gray-400">
                                        ${formatTime(message.created_at)}
                                    </span>

                                </div>

                                <div class="flex items-start gap-2">

                                    ${avatarHtml}

                                    <div
                                        class="max-w-[80%] rounded-2xl bg-gray-100 px-3.5 py-2 text-sm text-gray-900"
                                    >
                                        ${escapeHtml(
                                            message.message_content ||
                                            'Message'
                                        )}
                                    </div>

                                </div>

                            </div>
                        `;

                    }).join('');

            } catch (error) {

                console.error(
                    'Unable to load pinned messages:',
                    error
                );

                container.innerHTML = `
                    <div class="py-10 text-center text-sm text-gray-400">
                        Unable to load pinned messages.
                    </div>
                `;
            }
        }

        // =====================================================
        // PIN / UNPIN MESSAGE
        // =====================================================

        async function pinMessageFromRow(row) {

            if (
                !currentConversationId ||
                !row?.dataset?.messageId
            ) {
                return;
            }


            try {

                const response =
                    await fetch(
                        `/messages/conversations/${currentConversationId}/messages/${row.dataset.messageId}/pin`,
                        {
                            method: 'POST',

                            headers: {
                                'Accept':
                                    'application/json',

                                'X-CSRF-TOKEN':
                                    csrfToken
                            }
                        }
                    );


                if (!response.ok) {

                    console.error(
                        'Unable to pin message:',
                        response.status
                    );

                    return;
                }


                const data =
                    await response.json();


                // =============================================
                // RESET ALL PIN BUTTONS
                //
                // Only one message can currently be pinned
                // for this participant.
                // =============================================

                


                // =============================================
                // CURRENT MESSAGE
                // =============================================

                const label =
                    row.querySelector(
                        '.message-pin-btn span'
                    );

                if (label) {

                    label.textContent =
                        data.is_pinned
                            ? 'Unpin'
                            : 'Pin';
                }


                // =============================================
                // SHOW MESSENGER STYLE SYSTEM NOTICE
                // =============================================

                showPinSystemNotice(
                    row,
                    data.is_pinned
                );

            } catch (error) {

                console.error(
                    'Unable to pin/unpin message:',
                    error
                );
            }
        }

        // =====================================================
        // PIN SYSTEM NOTICE
        // =====================================================

        // =====================================================
        // MESSENGER STYLE PIN SYSTEM NOTICE
        // Always goes at the BOTTOM of the conversation
        // =====================================================

        function showPinSystemNotice(
            row,
            isPinned
        ) {

            const container =
                document.getElementById(
                    'modalMessagesContainer'
                );

            if (!container) {
                return;
            }

            


            // =========================================
            // UPDATE THE ACTUAL MESSAGE PIN LABEL
            // =========================================

            if (row) {

                row
                    .querySelectorAll(
                        '.message-pinned-label'
                    )
                    .forEach(label => {
                        label.remove();
                    });

                if (isPinned) {

                    const bubble =
                        row.querySelector(
                            '.message-bubble'
                        );

                    if (bubble) {

                        bubble.insertAdjacentHTML(
                            'beforebegin',
                            `
                                <div
                                    class="message-pinned-label mb-1 flex items-center gap-1 text-[11px] text-gray-400"
                                >
                                    <i
                                        data-lucide="pin"
                                        class="h-3 w-3"
                                    ></i>

                                    <span>
                                        Pinned
                                    </span>
                                </div>
                            `
                        );
                    }
                }
            }


            // =========================================
            // ADD SYSTEM NOTICE TO BOTTOM
            // =========================================

            const notice =
                document.createElement('div');

            notice.className =
                'pin-system-notice flex justify-center py-3';

            notice.innerHTML = `
                <div
                    class="flex items-center gap-1.5 text-xs text-gray-400"
                >
                    <span>
                        ${
                            isPinned
                                ? 'You pinned a message.'
                                : 'You unpinned a message.'
                        }
                    </span>

                    <button
                        type="button"
                        class="pinned-see-all font-semibold text-gray-900 hover:underline"
                    >
                        See all
                    </button>
                </div>
            `;

            container.appendChild(notice);

            container.scrollTop =
                container.scrollHeight;

            lucideCreateIcons();
        }

        // =====================================================
        // MESSENGER STYLE FORWARD MODAL
        // =====================================================

        function getForwardConversationInfo(conversation) {
            const participants = conversation.participants || [];
            const other = participants.find(p =>
                Number(p.user_id ?? p.user?.user_id) !== Number(currentUserId)
            );
            const user = other?.user || {};
            const name = user.user_full_name || conversation.conversation_name || `Conversation ${conversation.conversation_id}`;
            const picture = user.user_profile_picture || user.profile_picture || '';
            const initials = getInitials(name);
            return { name, picture, initials };
        }

        function showForwardMessageDialog(conversations, sourceMessageId) {
            document.getElementById('messageForwardOverlay')?.remove();

            const overlay = document.createElement('div');
            overlay.id = 'messageForwardOverlay';
            overlay.className = 'fixed inset-0 z-[10000] flex items-center justify-center bg-black/55 p-4';
            overlay.innerHTML = `
                <div class="flex max-h-[640px] w-full max-w-xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                    <div class="relative flex items-center justify-center border-b border-gray-200 px-5 py-4">
                        <h3 class="text-xl font-semibold text-gray-900">Forward</h3>
                        <button type="button" data-close class="absolute right-4 flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                    <div class="px-5 pt-5">
                        <div class="flex items-center gap-2 rounded-full bg-gray-100 px-4 py-2.5">
                            <i data-lucide="search" class="h-5 w-5 text-gray-500"></i>
                            <input data-forward-search type="text" class="w-full bg-transparent text-sm text-gray-900 outline-none" placeholder="Search for people and groups">
                        </div>
                    </div>
                    <div class="px-6 pb-2 pt-5 text-base font-semibold text-gray-800">Recent</div>
                    <div data-forward-list class="min-h-0 flex-1 overflow-y-auto px-4 pb-5"></div>
                </div>`;

            document.body.appendChild(overlay);
            lucideCreateIcons();

            const list = overlay.querySelector('[data-forward-list]');
            const search = overlay.querySelector('[data-forward-search]');
            const states = new Map();

            const render = query => {
                const normalized = String(query || '').trim().toLowerCase();
                const filtered = conversations.filter(conv => {
                    const info = getForwardConversationInfo(conv);
                    return !normalized || info.name.toLowerCase().includes(normalized);
                });

                list.innerHTML = filtered.length ? filtered.map(conv => {
                    const info = getForwardConversationInfo(conv);
                    const state = states.get(Number(conv.conversation_id)) || 'send';
                    let avatar = `<div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gray-200 text-xs font-semibold text-gray-700">${escapeHtml(info.initials)}</div>`;
                    if (info.picture) {
                        let src = String(info.picture);
                        if (!/^https?:\/\//i.test(src) && !src.startsWith('/')) src = `/storage/${src.replace(/^storage\//, '')}`;
                        avatar = `<img src="${escapeHtml(src)}" class="h-11 w-11 shrink-0 rounded-full object-cover" alt="${escapeHtml(info.name)}">`;
                    }

                    const buttonHtml = state === 'undo'
                        ? `<button type="button" data-forward-undo="${conv.conversation_id}" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">Undo</button>`
                        : state === 'sent'
                            ? `<button type="button" disabled class="flex items-center gap-1.5 rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-500"><i data-lucide="check" class="h-4 w-4"></i>Sent</button>`
                            : `<button type="button" data-forward-send="${conv.conversation_id}" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">Send</button>`;

                    return `
                        <div class="forward-conversation-row flex items-center gap-3 rounded-xl px-2 py-2 hover:bg-gray-50" data-name="${escapeHtml(info.name.toLowerCase())}">
                            ${avatar}
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-gray-900">${escapeHtml(info.name)}</p>
                            </div>
                            ${buttonHtml}
                        </div>`;
                }).join('') : `<div class="py-10 text-center text-sm text-gray-500">No conversations found.</div>`;
                lucideCreateIcons();
            };

            const pendingTimers = new Map();

            const beginForward = targetId => {
                targetId = Number(targetId);
                if (states.get(targetId) === 'undo' || states.get(targetId) === 'sent') return;

                states.set(targetId, 'undo');
                render(search.value);

                const timer = setTimeout(async () => {
                    pendingTimers.delete(targetId);

                    const response = await fetch(`/messages/conversations/${currentConversationId}/messages/${sourceMessageId}/forward`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ target_conversation_id: targetId })
                    });

                    states.set(targetId, response.ok ? 'sent' : 'send');
                    render(search.value);
                }, 3000);

                pendingTimers.set(targetId, timer);
            };

            const undoForward = targetId => {
                targetId = Number(targetId);
                const timer = pendingTimers.get(targetId);
                if (timer) clearTimeout(timer);
                pendingTimers.delete(targetId);
                states.set(targetId, 'send');
                render(search.value);
            };

            const close = () => {
                // Closing the modal is not the same as Undo.
                // Any forward already waiting in its 3 second window
                // will still be sent unless Undo was clicked first.
                overlay.remove();
            };

            render('');
            search.focus();
            search.addEventListener('input', () => render(search.value));
            overlay.querySelector('[data-close]').onclick = close;
            overlay.addEventListener('click', event => {
                if (event.target === overlay) close();

                const send = event.target.closest('[data-forward-send]');
                if (send) {
                    beginForward(send.dataset.forwardSend);
                    return;
                }

                const undo = event.target.closest('[data-forward-undo]');
                if (undo) undoForward(undo.dataset.forwardUndo);
            });
        }

        async function forwardMessageFromRow(row) {
            const response = await fetch('/messages/conversations', {
                headers: { 'Accept': 'application/json' }
            });
            if (!response.ok) return;

            const payload = await response.json();
            const conversations = payload.data?.data || payload.data || [];
            const choices = conversations.filter(c =>
                Number(c.conversation_id) !== Number(currentConversationId)
            );

            if (!choices.length) {
                await showMessageActionDialog({
                    title: 'Forward',
                    text: 'There is no other conversation to forward this message to.',
                    confirmText: 'OK'
                });
                return;
            }

            showForwardMessageDialog(choices, row.dataset.messageId);
        }

        async function loadModalMessages(conversationId, append = false) {
            if (isLoadingMessages || !hasMoreMessages) return;
            isLoadingMessages = true;

            const container = document.getElementById('modalMessagesContainer');
            if (!append) {

                // =============================================
                // CLEAR OLD MESSAGES
                // DO NOT DELETE TYPING INDICATOR
                // =============================================

                Array.from(container.children)
                    .forEach(child => {

                        if (
                            child.id !==
                            'modalTypingIndicator'
                        ) {
                            child.remove();
                        }

                    });
            }

            const response = await fetch(`/messages/conversations/${conversationId}/messages?page=${messagesPage}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            if (!response.ok) {
                isLoadingMessages = false;
                return;
            }

            const data = await response.json();
            const messages = data.data;

            // =====================================================
            // NO MESSAGES YET
            // KEEP TYPING INDICATOR ALIVE
            // =====================================================

            if (messages.data.length === 0 && !append) {

                const typingIndicator =
                    document.getElementById('modalTypingIndicator');

                const emptyState =
                    document.createElement('div');

                emptyState.className =
                    'message-empty-state text-center py-12';

                emptyState.innerHTML = `
                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 mb-3">
                        <i data-lucide="message-square" class="h-6 w-6"></i>
                    </div>

                    <p class="text-sm font-semibold text-gray-800">
                        No messages yet
                    </p>

                    <p class="text-xs text-gray-500 mt-1">
                        Send the first message below
                    </p>
                `;

                container.appendChild(emptyState);

                // Keep typing indicator last
                if (typingIndicator) {
                    container.appendChild(typingIndicator);
                }

                lucideCreateIcons();

                isLoadingMessages = false;

                return;
            }

            if (messages.data.length === 0) {
                hasMoreMessages = false;
                isLoadingMessages = false;
                return;
            }

            const userId = {{ auth()->id() }};
            const orderedMessages = [...messages.data].reverse();
            const html = orderedMessages
                .map(msg => renderMessengerMessageRow(msg, Number(msg.sender?.user_id ?? msg.sender_id) === Number(userId)))
                .join('');

            // =====================================================
            // INSERT MESSAGES WITHOUT DESTROYING TYPING INDICATOR
            // =====================================================

            if (append) {

                container.insertAdjacentHTML(
                    'afterbegin',
                    html
                );

            } else {

                const typingIndicator =
                    document.getElementById('modalTypingIndicator');

                // Remove everything except typing indicator
                Array.from(container.children)
                    .forEach(child => {

                        if (
                            child.id !==
                            'modalTypingIndicator'
                        ) {
                            child.remove();
                        }

                    });

                // Insert messages before typing indicator
                if (typingIndicator) {

                    typingIndicator.insertAdjacentHTML(
                        'beforebegin',
                        html
                    );

                } else {

                    container.insertAdjacentHTML(
                        'beforeend',
                        html
                    );
                }
            }

            // Always make typing indicator the final item
            moveTypingIndicatorAfterLastMessage();

            // =====================================================
            // DATE SEPARATORS
            // Rebuild after initial load and after older messages
            // are prepended through pagination.
            // =====================================================

            refreshMessageDateSeparators();

            lucideCreateIcons();
            refreshSeenAvatar();

            scrollToBottom(false, !append);
            isLoadingMessages = false;
        }

        // =====================================================
        // SEND MESSAGE REACTION
        // =====================================================

        async function reactToMessage(
            messageId,
            reaction
        ) {

            if (
                !currentConversationId ||
                !messageId ||
                !reaction
            ) {
                return;
            }


            try {

                const response = await fetch(
                    `/messages/conversations/${currentConversationId}/messages/${messageId}/reaction`,
                    {
                        method: 'POST',

                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },

                        body: JSON.stringify({
                            reaction: reaction,
                        }),
                    }
                );


                if (!response.ok) {

                    console.error(
                        'Reaction request failed:',
                        response.status
                    );

                    return;
                }


                const data =
                    await response.json();


                // =============================================
                // UPDATE OUR OWN SCREEN IMMEDIATELY
                //
                // Other participant gets the Reverb event.
                // =============================================

                updateMessageReactions(
                    data.message_id,
                    data.reactions
                );


            } catch (error) {

                console.error(
                    'Unable to react to message:',
                    error
                );
            }
        }

        // =====================================================
        // UPDATE REACTIONS ON ONE MESSAGE
        // =====================================================

        function updateMessageReactions(
            messageId,
            reactions
        ) {

            const row =
                document.querySelector(
                    `.message-row[data-message-id="${messageId}"]`
                );

            if (!row) {
                return;
            }


            const current =
                row.querySelector(
                    '.message-reactions'
                );

            if (!current) {
                return;
            }


            // =============================================
            // CREATE NEW REACTION HTML
            // =============================================

            const wrapper =
                document.createElement('div');

            wrapper.innerHTML =
                getMessageReactionsHtml(
                    reactions || []
                );


            const replacement =
                wrapper.firstElementChild;

            if (!replacement) {
                return;
            }


            current.replaceWith(
                replacement
            );
        }

        // =====================================================
        // MARK MESSAGE AS DELIVERED
        //
        // Called when this browser actually receives
        // another user's realtime message.
        // =====================================================

        async function markMessageAsDelivered(
            conversationId,
            messageId
        ) {

            try {

                const response = await fetch(
                    `/messages/conversations/${conversationId}/messages/${messageId}/delivered`,
                    {
                        method: 'POST',

                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    }
                );

                if (!response.ok) {

                    console.error(
                        'Failed to mark message as delivered:',
                        response.status
                    );
                }

            } catch (error) {

                console.error(
                    'Delivery receipt error:',
                    error
                );
            }
        }

        // =====================================================
        // KEEP TYPING INDICATOR AFTER THE LAST MESSAGE
        // =====================================================

        function moveTypingIndicatorAfterLastMessage() {

            const container =
                document.getElementById(
                    'modalMessagesContainer'
                );

            const indicator =
                document.getElementById(
                    'modalTypingIndicator'
                );

            if (!container || !indicator) {
                return;
            }

            // =============================================
            // MOVE INDICATOR TO THE VERY END
            // OF THE MESSAGE LIST
            // =============================================

            container.appendChild(indicator);
        }

        // =====================================================
        // SHOW TYPING STATE
        //
        // Updates:
        // 1. Three dot bubble inside currently opened chat
        // 2. Three dot indicator in conversation list
        // =====================================================

        function showConversationTyping(conversationId) {

            // =============================================
            // LEFT CONVERSATION LIST
            // =============================================

            const preview =
                document.querySelector(
                    `.conversation-preview[data-conversation-id="${conversationId}"]`
                );

            if (preview) {

                // Save current preview before replacing it
                if (!preview.dataset.originalPreview) {
                    preview.dataset.originalPreview =
                        preview.textContent.trim();
                }

                preview.innerHTML = `
                    <span class="conversation-typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                `;

                preview.classList.add('text-gray-500');
            }


            // =============================================
            // OPEN CONVERSATION
            // ONLY SHOW BUBBLE IF THIS CHAT IS OPEN
            // =============================================

            if (
                Number(conversationId) ===
                Number(currentConversationId)
            ) {

                const indicator =
                    document.getElementById(
                        'modalTypingIndicator'
                    );

                if (indicator) {

                    // =============================================
                    // ALWAYS PLACE DOTS BELOW LATEST MESSAGE
                    // =============================================

                    moveTypingIndicatorAfterLastMessage();

                    indicator.classList.add(
                        'is-typing'
                    );

                    // =============================================
                    // KEEP TYPING INDICATOR VISIBLE
                    // =============================================

                    requestAnimationFrame(() => {
                        scrollToBottom(true, true);
                    });
                }
            }
        }


        // =====================================================
        // HIDE TYPING STATE
        // =====================================================

        function hideConversationTyping(conversationId) {

            // =============================================
            // LEFT CONVERSATION LIST
            // RESTORE LAST MESSAGE
            // =============================================

            const preview =
                document.querySelector(
                    `.conversation-preview[data-conversation-id="${conversationId}"]`
                );

            if (preview) {

                const originalPreview =
                    preview.dataset.originalPreview || '';

                preview.textContent =
                    originalPreview;
            }


            // =============================================
            // OPEN CONVERSATION
            // =============================================

            if (
                Number(conversationId) ===
                Number(currentConversationId)
            ) {

                const indicator =
                    document.getElementById(
                        'modalTypingIndicator'
                    );

                if (indicator) {

                    indicator.classList.remove(
                        'is-typing'
                    );

                    requestAnimationFrame(() => {
                        scrollToBottom(true, true);
                    });
                }
            }
        }

        // =====================================================
        // MESSAGE REACTION EMOJI
        // =====================================================

        function getReactionEmoji(reaction) {

            const reactions = {
                like: '👍',
                heart: '❤️',
                check: '✓',
            };

            return reactions[reaction] || '';
        }


        // =====================================================
        // MESSAGE REACTIONS
        //
        // Hover:
        // Shows who reacted.
        //
        // Click:
        // Opens Messenger-style reaction details modal.
        // =====================================================

        function getMessageReactionsHtml(reactions = []) {

            if (!Array.isArray(reactions) || reactions.length === 0) {
                return `
                    <div
                        class="message-reactions hidden flex flex-wrap gap-1"
                    ></div>
                `;
            }


            // =============================================
            // GROUP SAME REACTIONS
            // =============================================

            const grouped = {};

            reactions.forEach(reaction => {

                const type = reaction.reaction;

                if (!grouped[type]) {
                    grouped[type] = [];
                }

                grouped[type].push(reaction);
            });


            // =============================================
            // CREATE REACTION CHIPS
            // =============================================

            const html = Object.entries(grouped)
                .map(([type, items]) => {

                    const emoji =
                        getReactionEmoji(type);


                    // =====================================
                    // CHECK IF CURRENT USER REACTED
                    // =====================================

                    const reactedByMe =
                        items.some(
                            item =>
                                Number(item.user_id) ===
                                Number(currentUserId)
                        );


                    // =====================================
                    // NAMES FOR HOVER TOOLTIP
                    // =====================================

                    const names = items
                        .map(item => {

                            if (
                                Number(item.user_id) ===
                                Number(currentUserId)
                            ) {
                                return 'You';
                            }

                            return (
                                item.user?.user_full_name ||
                                item.user?.name ||
                                'User'
                            );

                        })
                        .filter(Boolean);


                    const tooltip =
                        names.join(', ');


                    return `
                        <button
                            type="button"
                            class="
                                message-reaction-chip
                                relative
                                inline-flex
                                items-center
                                gap-1
                                rounded-full
                                border
                                px-2
                                py-0.5
                                text-[11px]
                                transition
                                hover:bg-gray-100
                                ${
                                    reactedByMe
                                        ? 'border-gray-400 bg-gray-100 text-gray-900'
                                        : 'border-gray-200 bg-white text-gray-600'
                                }
                            "
                            data-reaction="${escapeHtml(type)}"
                            data-reactions="${encodeURIComponent(
                                JSON.stringify(items)
                            )}"
                            title="${escapeHtml(tooltip)}"
                        >

                            <span>
                                ${emoji}
                            </span>

                            ${
                                items.length > 1
                                    ? `<span>${items.length}</span>`
                                    : ''
                            }

                        </button>
                    `;
                })
                .join('');


            return `
                <div
                    class="message-reactions flex w-fit flex-wrap gap-1"
                >
                    ${html}
                </div>
            `;
        }

        // =====================================================
        // MESSENGER STYLE REACTION DETAILS MODAL
        // =====================================================

        function showMessageReactionsModal(
            messageId,
            reactions = [],
            selectedReaction = null
        ) {

            // =============================================
            // REMOVE OLD MODAL
            // =============================================

            document
                .getElementById('messageReactionsOverlay')
                ?.remove();


            if (!Array.isArray(reactions)) {
                reactions = [];
            }


            // =============================================
            // CREATE OVERLAY
            // =============================================

            const overlay =
                document.createElement('div');

            overlay.id =
                'messageReactionsOverlay';

            overlay.className =
                'fixed inset-0 z-[10000] flex items-center justify-center bg-black/55 p-4';


            // =============================================
            // COUNT REACTIONS
            // =============================================

            const grouped = {};

            reactions.forEach(item => {

                if (!grouped[item.reaction]) {
                    grouped[item.reaction] = [];
                }

                grouped[item.reaction].push(item);
            });


            // =============================================
            // TABS
            // =============================================

            const tabs = Object.entries(grouped)
                .map(([type, items]) => {

                    const active =
                        selectedReaction === type;

                    return `
                        <button
                            type="button"
                            class="
                                reaction-modal-tab
                                relative
                                px-4
                                py-4
                                text-sm
                                font-semibold
                                ${
                                    active
                                        ? 'text-gray-900'
                                        : 'text-gray-500'
                                }
                            "
                            data-reaction-filter="${escapeHtml(type)}"
                        >

                            ${getReactionEmoji(type)}
                            ${items.length}

                            ${
                                active
                                    ? `
                                        <span
                                            class="
                                                absolute
                                                bottom-0
                                                left-0
                                                right-0
                                                h-0.5
                                                bg-gray-900
                                            "
                                        ></span>
                                    `
                                    : ''
                            }

                        </button>
                    `;
                })
                .join('');


            // =============================================
            // FILTER REACTIONS
            // =============================================

            const filtered =
                selectedReaction
                    ? reactions.filter(
                        item =>
                            item.reaction ===
                            selectedReaction
                    )
                    : reactions;


            // =============================================
            // REACTION USERS
            // =============================================

            const usersHtml =
                filtered.map(item => {

                    const user =
                        item.user || {};

                    const name =
                        Number(item.user_id) ===
                        Number(currentUserId)
                            ? (
                                user.user_full_name ||
                                'You'
                            )
                            : (
                                user.user_full_name ||
                                user.name ||
                                'User'
                            );


                    const isMe =
                        Number(item.user_id) ===
                        Number(currentUserId);


                    // =====================================
                    // PROFILE PICTURE
                    // =====================================

                    let picture =
                        user.user_profile_picture ||
                        user.profile_picture ||
                        '';

                    if (
                        picture &&
                        !/^https?:\/\//i.test(picture) &&
                        !picture.startsWith('/')
                    ) {

                        picture =
                            `/storage/${picture.replace(
                                /^storage\//,
                                ''
                            )}`;
                    }


                    const initials =
                        getInitials(name);


                    const avatar =
                        picture
                            ? `
                                <img
                                    src="${escapeHtml(picture)}"
                                    class="
                                        h-11
                                        w-11
                                        rounded-full
                                        object-cover
                                    "
                                    alt="${escapeHtml(name)}"
                                >
                            `
                            : `
                                <div
                                    class="
                                        flex
                                        h-11
                                        w-11
                                        items-center
                                        justify-center
                                        rounded-full
                                        bg-gray-200
                                        text-xs
                                        font-semibold
                                        text-gray-600
                                    "
                                >
                                    ${escapeHtml(initials)}
                                </div>
                            `;


                    return `
                        <button
                            type="button"
                            class="
                                reaction-modal-user
                                flex
                                w-full
                                items-center
                                gap-3
                                px-5
                                py-3
                                text-left
                                hover:bg-gray-50
                            "
                            data-user-id="${item.user_id}"
                            data-reaction="${escapeHtml(item.reaction)}"
                            data-current-user="${isMe ? '1' : '0'}"
                        >

                            ${avatar}

                            <div
                                class="
                                    min-w-0
                                    flex-1
                                "
                            >

                                <div
                                    class="
                                        truncate
                                        text-sm
                                        font-semibold
                                        text-gray-900
                                    "
                                >
                                    ${escapeHtml(name)}
                                </div>

                                ${
                                    isMe
                                        ? `
                                            <div
                                                class="
                                                    mt-0.5
                                                    text-xs
                                                    text-gray-500
                                                "
                                            >
                                                Click to remove
                                            </div>
                                        `
                                        : ''
                                }

                            </div>

                            <div
                                class="
                                    text-2xl
                                "
                            >
                                ${getReactionEmoji(
                                    item.reaction
                                )}
                            </div>

                        </button>
                    `;

                }).join('');


            // =============================================
            // MODAL HTML
            // =============================================

            overlay.innerHTML = `

                <div
                    class="
                        w-full
                        max-w-[520px]
                        overflow-hidden
                        rounded-2xl
                        bg-white
                        shadow-2xl
                    "
                >

                    <!-- ================================= -->
                    <!-- HEADER -->
                    <!-- ================================= -->

                    <div
                        class="
                            flex
                            h-16
                            items-center
                            justify-between
                            border-b
                            border-gray-200
                            px-5
                        "
                    >

                        <h3
                            class="
                                text-lg
                                font-bold
                                text-gray-900
                            "
                        >
                            Message reactions
                        </h3>


                        <button
                            type="button"
                            id="closeMessageReactionsModal"
                            class="
                                flex
                                h-9
                                w-9
                                items-center
                                justify-center
                                rounded-full
                                bg-gray-100
                                text-gray-600
                                hover:bg-gray-200
                            "
                        >

                            <i
                                data-lucide="x"
                                class="h-5 w-5"
                            ></i>

                        </button>

                    </div>


                    <!-- ================================= -->
                    <!-- TABS -->
                    <!-- ================================= -->

                    <div
                        class="
                            flex
                            border-b
                            border-gray-200
                        "
                    >

                        <button
                            type="button"
                            class="
                                reaction-modal-tab
                                relative
                                px-5
                                py-4
                                text-sm
                                font-semibold
                                ${
                                    !selectedReaction
                                        ? 'text-gray-900'
                                        : 'text-gray-500'
                                }
                            "
                            data-reaction-filter=""
                        >

                            All ${reactions.length}

                            ${
                                !selectedReaction
                                    ? `
                                        <span
                                            class="
                                                absolute
                                                bottom-0
                                                left-0
                                                right-0
                                                h-0.5
                                                bg-gray-900
                                            "
                                        ></span>
                                    `
                                    : ''
                            }

                        </button>

                        ${tabs}

                    </div>


                    <!-- ================================= -->
                    <!-- USERS -->
                    <!-- ================================= -->

                    <div
                        class="
                            max-h-[420px]
                            overflow-y-auto
                            py-2
                        "
                    >

                        ${
                            usersHtml ||
                            `
                                <div
                                    class="
                                        px-5
                                        py-10
                                        text-center
                                        text-sm
                                        text-gray-500
                                    "
                                >
                                    No reactions
                                </div>
                            `
                        }

                    </div>

                </div>
            `;


            document.body.appendChild(
                overlay
            );


            lucideCreateIcons();


            // =============================================
            // CLOSE MODAL
            // =============================================

            overlay
                .querySelector(
                    '#closeMessageReactionsModal'
                )
                ?.addEventListener(
                    'click',
                    () => overlay.remove()
                );


            overlay.addEventListener(
                'click',
                event => {

                    if (event.target === overlay) {
                        overlay.remove();
                    }

                }
            );


            // =============================================
            // FILTER TABS
            // =============================================

            overlay
                .querySelectorAll(
                    '.reaction-modal-tab'
                )
                .forEach(tab => {

                    tab.addEventListener(
                        'click',
                        () => {

                            const filter =
                                tab.dataset
                                    .reactionFilter ||
                                null;

                            showMessageReactionsModal(
                                messageId,
                                reactions,
                                filter
                            );

                        }
                    );

                });


            // =============================================
            // CLICK CURRENT USER = REMOVE REACTION
            // =============================================

            overlay
                .querySelectorAll(
                    '.reaction-modal-user'
                )
                .forEach(row => {

                    row.addEventListener(
                        'click',
                        async () => {

                            if (
                                row.dataset
                                    .currentUser !== '1'
                            ) {
                                return;
                            }


                            const reaction =
                                row.dataset.reaction;


                            // =================================
                            // SAME REACTION = BACKEND REMOVES IT
                            // =================================

                            await reactToMessage(
                                messageId,
                                reaction
                            );


                            // =================================
                            // REMOVE FROM LOCAL MODAL DATA
                            // =================================

                            reactions =
                                reactions.filter(
                                    item =>
                                        Number(item.user_id) !==
                                        Number(currentUserId)
                                );


                            if (
                                reactions.length === 0
                            ) {

                                overlay.remove();

                                return;
                            }


                            showMessageReactionsModal(
                                messageId,
                                reactions,
                                null
                            );
                        }
                    );

                });
        }


        // =====================================================
        // REACTION PICKER
        //
        // Appears beside Reply when hovering message.
        // =====================================================

        // =====================================================
        // REACTION BUTTON + POPUP
        //
        // Normal state:
        // Only shows the smile button beside Reply.
        //
        // When clicked:
        // Opens the 3 reaction choices upward.
        // =====================================================

        function getReactionPickerHtml() {

            return `
                <div
                    class="
                        message-reaction-control
                        relative
                        flex
                        shrink-0
                        items-center
                    "
                >

                    <!-- ====================================== -->
                    <!-- REACTION TRIGGER BUTTON -->
                    <!-- ====================================== -->

                    <button
                        type="button"
                        class="
                            message-reaction-trigger
                            flex
                            h-8 w-8
                            items-center
                            justify-center
                            rounded-full
                            text-gray-400
                            opacity-0
                            transition
                            group-hover:opacity-100
                            hover:bg-gray-100
                            hover:text-gray-700
                        "
                        title="React"
                    >
                        <i
                            data-lucide="smile"
                            class="h-4 w-4"
                        ></i>
                    </button>


                    <!-- ====================================== -->
                    <!-- REACTION POPUP -->
                    <!-- Opens upward like Messenger -->
                    <!-- ====================================== -->

                    <div
                        class="
                            message-reaction-picker
                            absolute
                            bottom-full
                            left-1/2
                            z-50
                            mb-2
                            hidden
                            -translate-x-1/2
                            items-center
                            gap-1
                            whitespace-nowrap
                            rounded-full
                            border
                            border-gray-200
                            bg-white
                            px-2
                            py-1.5
                            shadow-lg
                        "
                    >

                        <button
                            type="button"
                            class="
                                message-reaction-option
                                flex
                                h-8 w-8
                                items-center
                                justify-center
                                rounded-full
                                text-lg
                                transition
                                hover:scale-125
                                hover:bg-gray-100
                            "
                            data-reaction="like"
                            title="Like"
                        >
                            👍
                        </button>

                        <button
                            type="button"
                            class="
                                message-reaction-option
                                flex
                                h-8 w-8
                                items-center
                                justify-center
                                rounded-full
                                text-lg
                                transition
                                hover:scale-125
                                hover:bg-gray-100
                            "
                            data-reaction="heart"
                            title="Heart"
                        >
                            ❤️
                        </button>

                        <button
                            type="button"
                            class="
                                message-reaction-option
                                flex
                                h-8 w-8
                                items-center
                                justify-center
                                rounded-full
                                text-lg
                                transition
                                hover:scale-125
                                hover:bg-gray-100
                            "
                            data-reaction="check"
                            title="Check"
                        >
                            ✓
                        </button>

                    </div>

                </div>
            `;
        }

        // =====================================================
        // CREATE QUOTED REPLY BLOCK
        //
        // Used inside sent and received message bubbles.
        // =====================================================

        // =====================================================
        // JUMP TO ORIGINAL REPLIED MESSAGE
        //
        // Clicking the quoted reply scrolls to the exact original
        // message and briefly highlights it.
        //
        // If the original message is older and is not loaded yet,
        // older message pages are loaded automatically until the
        // target is found or there are no more pages.
        // =====================================================

        async function jumpToOriginalReplyMessage(messageId) {

            if (!messageId || !currentConversationId) {
                return;
            }

            const container =
                document.getElementById('modalMessagesContainer');

            if (!container) {
                return;
            }

            const findTarget = () =>
                container.querySelector(
                    `.message-row[data-message-id="${CSS.escape(String(messageId))}"]`
                );

            let target = findTarget();

            // =================================================
            // ORIGINAL MESSAGE IS NOT CURRENTLY LOADED
            //
            // Keep loading older pages because the chat uses
            // pagination and older messages are prepended.
            // =================================================

            while (
                !target &&
                hasMoreMessages
            ) {

                if (isLoadingMessages) {
                    await new Promise(resolve => setTimeout(resolve, 80));
                    target = findTarget();
                    continue;
                }

                const previousPage = messagesPage;

                messagesPage++;

                await loadModalMessages(
                    currentConversationId,
                    true
                );

                target = findTarget();

                // Safety against an unexpected pagination response.
                if (
                    !target &&
                    messagesPage === previousPage
                ) {
                    break;
                }
            }

            if (!target) {
                return;
            }

            // =================================================
            // SCROLL THE ORIGINAL MESSAGE INTO VIEW
            // =================================================

            target.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            // =================================================
            // TEMPORARY MESSENGER STYLE HIGHLIGHT
            //
            // Highlight the bubble itself when possible.
            // =================================================

            const highlightTarget =
                target.querySelector('.message-bubble') ||
                target;

            highlightTarget.classList.remove(
                'ring-2',
                'ring-gray-400',
                'ring-offset-2',
                'ring-offset-white'
            );

            // Force a reflow so repeated clicks replay the effect.
            void highlightTarget.offsetWidth;

            highlightTarget.classList.add(
                'ring-2',
                'ring-gray-400',
                'ring-offset-2',
                'ring-offset-white'
            );

            setTimeout(() => {
                highlightTarget.classList.remove(
                    'ring-2',
                    'ring-gray-400',
                    'ring-offset-2',
                    'ring-offset-white'
                );
            }, 1800);
        }


        function getReplyQuoteHtml(replyTo, isOwnReply = false) {

            if (!replyTo) {
                return '';
            }

            let message =
                replyTo.message_content ||
                'Message';

            // =============================================
            // FRIENDLY ATTACHMENT TEXT
            // =============================================

            if (message === '[attachment:image]') {
                message = 'Photo';
            }

            if (message === '[attachment:file]') {
                message = 'File';
            }

            // =============================================
            // MESSENGER STYLE QUOTED MESSAGE
            //
            // No sender name here.
            // The single indicator above already says:
            // "Tristan replied to you"
            //
            // This quoted bubble sits ABOVE the new message.
            // =============================================

            return `
                <button
                    type="button"
                    class="
                        reply-quote
                        relative
                        z-0
                        inline-block
                        w-fit
                        max-w-full
                        rounded-2xl
                        ${isOwnReply
                            ? 'bg-gray-200 text-gray-500'
                            : 'bg-gray-200 text-gray-500'}
                        px-4
                        pt-2.5
                        pb-4
                        -mb-3
                        text-left
                        transition
                        hover:bg-gray-300
                    "
                    data-reply-message-id="${replyTo.message_id}"
                    title="View original message"
                >
                    <p class="truncate text-sm leading-snug">
                        ${escapeHtml(message)}
                    </p>
                </button>
            `;
        }

        // =====================================================
        // OPEN CHAT TYPING INDICATOR
        // Shows the 3 dot bubble below the last message
        // =====================================================

        function showRemoteTypingIndicator() {

            const indicator =
                document.getElementById('modalTypingIndicator');

            const messagesContainer =
                document.getElementById('modalMessagesContainer');

            if (!indicator || !messagesContainer) {
                return;
            }

            // Keep indicator after the newest message
            messagesContainer.appendChild(indicator);

            // Show the 3 dot bubble
            indicator.classList.add('is-typing');

            // Scroll down so the indicator is visible
            requestAnimationFrame(() => {
                messagesContainer.scrollTop =
                    messagesContainer.scrollHeight;
            });
        }


        // =====================================================
        // OPEN CHAT TYPING INDICATOR
        // Hides the 3 dot bubble
        // =====================================================

        function hideRemoteTypingIndicator() {

            const indicator =
                document.getElementById('modalTypingIndicator');

            if (!indicator) {
                return;
            }

            indicator.classList.remove('is-typing');
        }

        // =====================================================
        // HANDLE GLOBAL TYPING EVENT
        // =====================================================

        function handleGlobalTyping(event) {

            if (!event) {
                return;
            }


            const conversationId = Number(
                event.conversation_id
            );

            const senderId = Number(
                event.sender_id
            );

            const isTyping =
                event.is_typing === true ||
                event.is_typing === 1 ||
                event.is_typing === '1';


            // =================================================
            // IGNORE OUR OWN EVENT
            // =================================================

            if (senderId === Number(currentUserId)) {
                return;
            }


            // =================================================
            // CLEAR OLD TIMER FOR THIS CONVERSATION ONLY
            // =================================================

            if (
                remoteTypingTimeouts.has(
                    conversationId
                )
            ) {

                clearTimeout(
                    remoteTypingTimeouts.get(
                        conversationId
                    )
                );

                remoteTypingTimeouts.delete(
                    conversationId
                );
            }


            // =================================================
            // USER STOPPED TYPING
            // =================================================

            if (!isTyping) {

                hideConversationTyping(
                    conversationId
                );


                // =============================================
                // ALSO REMOVE CHAT BUBBLE IF THIS CHAT IS OPEN
                // =============================================

                if (
                    Number(currentConversationId) ===
                    conversationId
                ) {

                    hideRemoteTypingIndicator();
                }


                return;
            }


            // =================================================
            // SHOW TYPING IN LEFT CONVERSATION LIST
            // =================================================

            showConversationTyping(
                conversationId
            );


            // =================================================
            // IF THIS CONVERSATION IS CURRENTLY OPEN,
            // ALSO SHOW THE TYPING BUBBLE INSIDE THE CHAT
            // =================================================

            if (
                Number(currentConversationId) ===
                conversationId
            ) {

                showRemoteTypingIndicator();
            }


            // =================================================
            // SAFETY TIMEOUT
            //
            // If the sender closes their browser without sending
            // is_typing = false, remove the indicator anyway.
            // =================================================

            const timeout = setTimeout(() => {

                hideConversationTyping(
                    conversationId
                );


                if (
                    Number(currentConversationId) ===
                    conversationId
                ) {

                    hideRemoteTypingIndicator();
                }


                remoteTypingTimeouts.delete(
                    conversationId
                );

            }, 3000);


            remoteTypingTimeouts.set(
                conversationId,
                timeout
            );
        }

        // =====================================================
        // LISTEN FOR REAL TIME MESSAGES
        // =====================================================

        function listenToConversationRealtime(conversationId) {

            // =================================================
            // DO NOTHING IF ALREADY LISTENING
            // =================================================

            if (
                activeRealtimeConversationId === conversationId
            ) {
                return;
            }


            // =================================================
            // LEAVE PREVIOUS CONVERSATION CHANNEL
            // =================================================

            if (activeRealtimeConversationId !== null) {

                window.Echo.leave(
                    `conversation.${activeRealtimeConversationId}`
                );

            }


            // =================================================
            // SAVE CURRENT CONVERSATION
            // =================================================

            activeRealtimeConversationId = conversationId;


            // =================================================
            // LISTEN TO PRIVATE CONVERSATION CHANNEL
            // =================================================

            window.Echo
                .private(`conversation.${conversationId}`)

                .listen('.message.sent', (event) => {

                    // =========================================
                    // GET MESSAGE FROM EVENT
                    // =========================================

                    const msg = event.message;

                    if (!msg) {
                        return;
                    }


                    // =========================================
                    // MAKE SURE MESSAGE BELONGS TO
                    // CURRENT OPEN CONVERSATION
                    // =========================================

                    if (
                        Number(msg.conversation_id) !==
                        Number(activeRealtimeConversationId)
                    ) {
                        return;
                    }


                    // =========================================
                    // CURRENT USER
                    // =========================================

                    const userId = {{ auth()->id() }};


                    // =========================================
                    // PREVENT OWN MESSAGE DUPLICATE
                    //
                    // toOthers() should already prevent this,
                    // but this gives us another safety check.
                    // =========================================

                    if (
                        Number(msg.sender_id) ===
                        Number(userId)
                    ) {
                        return;
                    }

                    if (remoteTypingTimeouts.has(conversationId)) {

                        clearTimeout(
                            remoteTypingTimeouts.get(
                                conversationId
                            )
                        );

                        remoteTypingTimeouts.delete(
                            conversationId
                        );
                    }

                    hideConversationTyping(
                        conversationId
                    );

                    // =========================================
                    // RECEIVER'S BROWSER GOT THE MESSAGE
                    // =========================================

                    markMessageAsDelivered(
                        conversationId,
                        msg.message_id
                    );



                    // =========================================
                    // MESSAGE CONTAINER
                    // =========================================

                    const container =
                        document.getElementById(
                            'modalMessagesContainer'
                        );

                    if (!container) {
                        return;
                    }


                    // =========================================
                    // REMOVE "NO MESSAGES YET" EMPTY STATE
                    // =========================================

                    const emptyState =
                        container.querySelector(
                            '.message-empty-state, .text-center.py-12'
                        );

                    if (emptyState) {
                        emptyState.remove();
                    }


                    // =========================================
                    // MESSAGE INFORMATION
                    // =========================================

                    const time =
                        formatMessageTime(
                            msg.created_at
                        );

                    const senderName =
                        msg.sender?.user_full_name ||
                        msg.sender?.name ||
                        'Unknown';


                    // =========================================
                    // CREATE RECEIVED MESSAGE BUBBLE
                    // =========================================

                    const html = renderMessengerMessageRow(msg, false);

                    // =========================================
                    // ADD MESSAGE WITHOUT REFRESHING
                    // =========================================

                    const typingIndicator =
                        document.getElementById(
                            'modalTypingIndicator'
                        );

                    if (typingIndicator) {

                        typingIndicator.insertAdjacentHTML(
                            'beforebegin',
                            html
                        );

                    } else {

                        container.insertAdjacentHTML(
                            'beforeend',
                            html
                        );
                    }

                    moveTypingIndicatorAfterLastMessage();


                    // =========================================
                    // REFRESH DATE SEPARATORS
                    // =========================================

                    refreshMessageDateSeparators();


                    // =========================================
                    // REFRESH LUCIDE ICONS
                    // =========================================

                    lucideCreateIcons();
                    refreshMessengerMessageGroups();
                    refreshLatestOutgoingStatus();


                    // =========================================
                    // SCROLL TO NEW MESSAGE
                    // =========================================

                    scrollToBottom(false, true);

                    // =========================================
                    // UPDATE LEFT CONVERSATION LIST
                    // Latest preview
                    // Latest timestamp
                    // Conversation order
                    // Unread badge
                    // =========================================

                    markConversationAsRead(
                        conversationId
                    );

                })

                // =====================================================
                // REALTIME EDIT / UNSEND
                // =====================================================

                .listen('.message.updated', (event) => {
                    const msg = event.message;
                    if (!msg || Number(msg.conversation_id) !== Number(currentConversationId)) return;

                    const row = document.querySelector(`.message-row[data-message-id="${msg.message_id}"]`);
                    if (!row) return;

                    const isOwn = Number(msg.sender_id) === Number(currentUserId);
                    const merged = {
                        message_id: msg.message_id,
                        conversation_id: msg.conversation_id,
                        sender_id: msg.sender_id,
                        sender: {
                            user_id: msg.sender_id,
                            user_full_name: row.dataset.messageSender || (isOwn ? 'You' : 'Unknown')
                        },
                        message_content: msg.message_content,
                        created_at: row.dataset.messageCreatedAt,
                        is_unsent: msg.is_unsent,
                        is_edited: msg.is_edited,
                        reactions: []
                    };

                    row.outerHTML = renderMessengerMessageRow(merged, isOwn);
                    lucideCreateIcons();
                })

                // =====================================================
                // REALTIME MESSAGE REACTION
                // =====================================================

                .listen(
                    '.message.reaction.updated',
                    (event) => {

                        if (!event) {
                            return;
                        }


                        // =============================================
                        // MAKE SURE EVENT BELONGS TO OPEN CHAT
                        // =============================================

                        if (
                            Number(event.conversation_id) !==
                            Number(activeRealtimeConversationId)
                        ) {
                            return;
                        }


                        // =============================================
                        // UPDATE EXACT MESSAGE
                        // =============================================

                        updateMessageReactions(
                            event.message_id,
                            event.reactions || []
                        );
                    }
                )

                // =====================================================
                // REALTIME DELIVERED RECEIPT
                // =====================================================

                .listen('.message.delivered', (event) => {

                    console.log(
                        'Message delivered:',
                        event
                    );

                    // =========================================
                    // UPDATE MESSAGE INSIDE CHAT
                    // =========================================

                    const statusElement =
                        document.querySelector(
                            `.message-read-status[data-message-id="${event.message_id}"]`
                        );

                    if (
                        statusElement &&
                        statusElement.textContent.trim() !== 'Seen'
                    ) {
                        statusElement.dataset.messageStatus = 'Delivered';
                        statusElement.textContent = 'Delivered';
                        refreshLatestOutgoingStatus();
                    }


                    // =========================================
                    // UPDATE LEFT CONVERSATION LIST
                    // =========================================

                    const conversationStatusElement =
                        document.querySelector(
                            `.conversation-message-status[data-message-id="${event.message_id}"]`
                        );

                    if (
                        conversationStatusElement &&
                        conversationStatusElement.textContent.trim() !== 'Seen'
                    ) {
                        conversationStatusElement.textContent =
                            'Delivered';
                    }
                })


                // =====================================================
                // REALTIME SEEN RECEIPTS
                // =====================================================

                .listen('.messages.read', (event) => {

                    console.log(
                        'Messages seen:',
                        event
                    );


                    // =================================================
                    // SAFETY CHECK
                    // =================================================

                    if (!event.message_ids) {
                        return;
                    }


                    // =================================================
                    // UPDATE EACH MESSAGE TO "SEEN"
                    // =================================================

                    event.message_ids.forEach(
                        (messageId) => {

                            // =========================================
                            // UPDATE STATUS INSIDE CHAT
                            // =========================================

                            const statusElement =
                                document.querySelector(
                                    `.message-read-status[data-message-id="${messageId}"]`
                                );

                            if (statusElement) {
                                statusElement.dataset.messageStatus = 'Seen';
                                statusElement.textContent = 'Seen';
                            }


                            // =========================================
                            // UPDATE STATUS IN CONVERSATION LIST
                            // =========================================

                            const conversationStatusElement =
                                document.querySelector(
                                    `.conversation-message-status[data-message-id="${messageId}"]`
                                );

                            if (conversationStatusElement) {
                                conversationStatusElement.textContent = 'Seen';
                            }
                        }
                    );

                    refreshSeenAvatar();
                })

                // =====================================================
                // REALTIME TYPING INDICATOR
                // =====================================================

                .listenForWhisper('typing', (event) => {

                    // =============================================
                    // IGNORE OUR OWN TYPING
                    // =============================================

                    if (
                        Number(event.user_id) ===
                        Number(currentUserId)
                    ) {
                        return;
                    }


                    // =====================================================
                    // OTHER USER STARTED TYPING
                    // =====================================================

                    if (event.is_typing) {

                        showConversationTyping(
                            conversationId
                        );

                        // Show bubble inside currently opened conversation
                        if (
                            Number(currentConversationId) ===
                            Number(conversationId)
                        ) {
                            showRemoteTypingIndicator();
                        }


                        // Clear previous safety timeout
                        if (remoteTypingTimeouts.has(conversationId)) {

                            clearTimeout(
                                remoteTypingTimeouts.get(conversationId)
                            );
                        }


                        // Safety timeout
                        const timeout = setTimeout(() => {

                            hideConversationTyping(
                                conversationId
                            );

                            if (
                                Number(currentConversationId) ===
                                Number(conversationId)
                            ) {
                                hideRemoteTypingIndicator();
                            }

                            remoteTypingTimeouts.delete(
                                conversationId
                            );

                        }, 3000);


                        remoteTypingTimeouts.set(
                            conversationId,
                            timeout
                        );

                        return;
                    }


                    // =====================================================
                    // OTHER USER STOPPED TYPING
                    // =====================================================

                    if (remoteTypingTimeouts.has(conversationId)) {

                        clearTimeout(
                            remoteTypingTimeouts.get(conversationId)
                        );

                        remoteTypingTimeouts.delete(
                            conversationId
                        );
                    }

                    hideConversationTyping(
                        conversationId
                    );

                    if (
                        Number(currentConversationId) ===
                        Number(conversationId)
                    ) {
                        hideRemoteTypingIndicator();
                    }


                    
                })

                

                
        }

        // =====================================================
        // START REPLYING TO MESSAGE
        // =====================================================

        function startReplyToMessage(
            messageId,
            senderName,
            messageContent
        ) {

            replyingToMessage = {
                message_id: Number(messageId),
                sender_name: senderName || 'User',
                message_content:
                    messageContent || 'Message'
            };


            const preview =
                document.getElementById(
                    'modalReplyPreview'
                );

            const name =
                document.getElementById(
                    'modalReplyName'
                );

            const text =
                document.getElementById(
                    'modalReplyText'
                );


            if (name) {

                name.textContent =
                    `Replying to ${replyingToMessage.sender_name}`;
            }


            if (text) {

                text.textContent =
                    replyingToMessage.message_content;
            }


            preview?.classList.remove('hidden');


            // =============================================
            // PUT CURSOR BACK INTO MESSAGE BOX
            // =============================================

            document.getElementById(
                'modalMessageInput'
            )?.focus();


            lucideCreateIcons();
        }


        // =====================================================
        // CANCEL REPLY
        // =====================================================

        function cancelReply() {

            replyingToMessage = null;


            const preview =
                document.getElementById(
                    'modalReplyPreview'
                );

            const name =
                document.getElementById(
                    'modalReplyName'
                );

            const text =
                document.getElementById(
                    'modalReplyText'
                );


            preview?.classList.add('hidden');


            if (name) {
                name.textContent = 'Replying to';
            }


            if (text) {
                text.textContent = '';
            }
        }

        // =====================================================
        // EXPOSE REPLY FUNCTIONS TO MESSAGE BUTTONS
        // =====================================================

        window.startReplyToMessage =
            startReplyToMessage;

        window.cancelReply =
            cancelReply;

        // =====================================================
        // ESCAPE USER TEXT BEFORE ADDING IT TO HTML
        // =====================================================

        function escapeHtml(value) {

            if (!value) {
                return '';
            }

            return String(value)

                .replaceAll('&', '&amp;')

                .replaceAll('<', '&lt;')

                .replaceAll('>', '&gt;')

                .replaceAll('"', '&quot;')

                .replaceAll("'", '&#039;');
        }

        // =====================================================
        // MESSAGE DATE SEPARATORS
        //
        // Examples:
        // Today
        // Yesterday
        // July 24, 2026
        //
        // This rebuilds the separators from the message rows that
        // are currently inside the chat. Because of that, loading
        // older pages will not create duplicate date labels.
        // =====================================================

        function getMessageDateKey(dateString) {

            if (!dateString) {
                return '';
            }

            const date = new Date(dateString);

            if (Number.isNaN(date.getTime())) {
                return '';
            }

            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            return `${year}-${month}-${day}`;
        }


        function getMessageDateLabel(dateString) {

            if (!dateString) {
                return '';
            }

            const messageDate = new Date(dateString);

            if (Number.isNaN(messageDate.getTime())) {
                return '';
            }

            const today = new Date();

            const todayStart = new Date(
                today.getFullYear(),
                today.getMonth(),
                today.getDate()
            );

            const messageStart = new Date(
                messageDate.getFullYear(),
                messageDate.getMonth(),
                messageDate.getDate()
            );

            const differenceInDays = Math.round(
                (todayStart - messageStart) / 86400000
            );

            if (differenceInDays === 0) {
                return 'Today';
            }

            if (differenceInDays === 1) {
                return 'Yesterday';
            }

            return messageDate.toLocaleDateString('en-US', {
                month: 'long',
                day: 'numeric',
                year: 'numeric'
            });
        }


        function refreshMessageDateSeparators() {

            const container =
                document.getElementById('modalMessagesContainer');

            if (!container) {
                return;
            }


            // =================================================
            // REMOVE OLD LABELS FIRST
            // Prevents duplicates after pagination or realtime.
            // =================================================

            container
                .querySelectorAll('.message-date-separator')
                .forEach(separator => separator.remove());


            const messageRows = Array.from(
                container.querySelectorAll('.message-row')
            );

            let previousDateKey = null;


            messageRows.forEach(row => {

                const createdAt =
                    row.dataset.messageCreatedAt || '';

                const currentDateKey =
                    getMessageDateKey(createdAt);

                if (!currentDateKey) {
                    return;
                }


                // =================================================
                // ONLY ADD A LABEL WHEN THE CALENDAR DAY CHANGES
                // =================================================

                if (currentDateKey !== previousDateKey) {

                    const separator =
                        document.createElement('div');

                    separator.className =
                        'message-date-separator flex items-center justify-center py-2';

                    separator.innerHTML = `
                        <span
                            class="
                                rounded-full
                                bg-white
                                px-3
                                py-1
                                text-[11px]
                                font-medium
                                text-gray-400
                            "
                        >
                            ${escapeHtml(
                                getMessageDateLabel(createdAt)
                            )}
                        </span>
                    `;

                    row.before(separator);
                }

                previousDateKey = currentDateKey;
            });
        }


        function scrollToBottom(smooth = false, force = false) {
            const container = document.getElementById('modalMessagesContainer');
            if (!container) return;

            const isNearBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 120;

            if (!force && !isNearBottom) {
                return;
            }

            if (smooth) {
                container.scrollTo({
                    top: container.scrollHeight,
                    behavior: 'smooth'
                });
            } else {
                container.scrollTop = container.scrollHeight;
            }
        }

        async function sendModalMessage(e) {
            e.preventDefault();

            // =============================================
            // EDIT MODE: save the edited message instead
            // of creating a new message.
            // =============================================
            if (editingMessageRow) {
                await saveEditedMessage();
                return;
            }

            const input = document.getElementById('modalMessageInput');
            const content = input?.value.trim();
            if (!content && !selectedAttachment) return;
            if (!currentConversationId) return;

            const form = document.getElementById('modalMessageForm');
            if (!form) return;

            const tempId = 'temp-' + Date.now();
            const time = new Date().toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            const container = document.getElementById('modalMessagesContainer');

            const attachmentPreview = selectedAttachment ? getAttachmentPreviewHtml(selectedAttachment, time, true) : '';

            const tempHtml = `
            <div class="flex justify-end" id="${tempId}" style="animation: messageSlideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1)">
                <div class="max-w-[70%] rounded-2xl rounded-br-md bg-gray-900 text-white px-4 py-2.5 opacity-70">
                    ${content ? `<p class="text-sm whitespace-pre-wrap break-words">${content}</p>` : ''}
                    ${attachmentPreview}
                    <span class="text-[10px] text-gray-400 mt-1 block text-right">${time}</span>
                </div>
            </div>
        `;
            const typingIndicator =
                document.getElementById(
                    'modalTypingIndicator'
                );

            if (typingIndicator) {

                typingIndicator.insertAdjacentHTML(
                    'beforebegin',
                    tempHtml
                );

            } else {

                container.insertAdjacentHTML(
                    'beforeend',
                    tempHtml
                );
            }

            moveTypingIndicatorAfterLastMessage();

            scrollToBottom(true, true);

            const formData = new FormData();

            formData.append(
                'message_content',
                content || ''
            );


            // =====================================================
            // MESSAGE BEING REPLIED TO
            // =====================================================

            if (replyingToMessage?.message_id) {

                formData.append(
                    'reply_to_message_id',
                    replyingToMessage.message_id
                );
            }


            // =====================================================
            // ATTACHMENT
            // =====================================================

            if (selectedAttachment) {

                formData.append(
                    'attachment',
                    JSON.stringify(selectedAttachment)
                );
            }

            const response = await fetch(`/messages/conversations/${currentConversationId}/send`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            });

            if (response.ok) {
                const data = await response.json();
                const temp = document.getElementById(tempId);
                if (temp) temp.remove();

                const msg = data.data;
                const msgTime = formatMessageTime(msg.created_at);
                const attachment = msg.attachment || selectedAttachment;
                const isFirstMessage = container.querySelector('.text-center.py-12') !== null;
                // =====================================================
                // CREATE REAL SENT MESSAGE
                //
                // This replaces the temporary sending bubble after
                // Laravel successfully saves the message.
                // =====================================================

                const realHtml = renderMessengerMessageRow(msg, true);
                const emptyState =
                    container.querySelector(
                        '.message-empty-state, .text-center.py-12'
                    );

                if (emptyState) {
                    emptyState.remove();
                }


                // =====================================================
                // INSERT SENT MESSAGE BEFORE TYPING INDICATOR
                // =====================================================

                const typingIndicator =
                    document.getElementById(
                        'modalTypingIndicator'
                    );

                if (typingIndicator) {

                    typingIndicator.insertAdjacentHTML(
                        'beforebegin',
                        realHtml
                    );

                } else {

                    container.insertAdjacentHTML(
                        'beforeend',
                        realHtml
                    );
                }

                moveTypingIndicatorAfterLastMessage();

                // =====================================================
                // DATE SEPARATORS
                // Also covers the first message sent on a new day.
                // =====================================================

                refreshMessageDateSeparators();

                scrollToBottom(true, true);

                clearSelectedAttachment();

                // =====================================================
                // CLEAR REPLY AFTER SUCCESSFUL SEND
                // =====================================================

                cancelReply();

                input.value = '';
                input.style.height = 'auto';
                input.focus();

                await loadModalConversations();
            } else {
                const temp = document.getElementById(tempId);
                if (temp) temp.remove();
            }
        }

        function isImageAttachment(attachment) {
            if (!attachment) return false;
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
            const type = (attachment.type || '').toLowerCase();
            const ext = (attachment.extension || '').toLowerCase();
            if (type.startsWith('image/')) return true;
            if (imageExtensions.includes(type)) return true;
            if (imageExtensions.includes(ext)) return true;
            return false;
        }

        function getAttachmentPreviewHtml(attachment, time, isTemp = false) {
            if (!attachment) return '';
            const isImage = isImageAttachment(attachment);
            const fileIcon = getFileIcon(attachment.type || '', attachment.extension || '');
            const opacity = isTemp ? 'opacity-80' : '';
            const removeBtn = isTemp ? `
            <button type="button" onclick="this.closest('.attachment-preview').remove()" class="absolute -top-1 -right-1 h-4 w-4 rounded-full bg-gray-900 text-white flex items-center justify-center hover:bg-gray-800 transition" style="animation: none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        ` : '';

            if (isImage && attachment.url) {
                return getImageMessageHtml(attachment, time, isTemp, removeBtn);
            }

            return `
            <div class="attachment-preview relative mt-1.5 flex items-center gap-2 rounded-lg border border-white/10 px-3 py-2 ${opacity}" style="animation: messageSlideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1)">
                <div class="h-8 w-8 rounded-lg ${fileIcon.color} flex items-center justify-center shrink-0">
                    <i data-lucide="${fileIcon.icon}" class="h-4 w-4"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-white truncate">${attachment.name}</p>
                    <p class="text-[10px] text-gray-400">${formatFileSize(attachment.size || 0)}</p>
                </div>
                <a href="${attachment.url}" target="_blank" class="h-7 w-7 shrink-0 flex items-center justify-center rounded-md bg-white/10 text-white hover:bg-white/20 transition" title="Download">
                    <i data-lucide="download" class="h-3.5 w-3.5"></i>
                </a>
                ${removeBtn}
            </div>
        `;
        }

        function getImageMessageHtml(attachment, time, isTemp = false, removeBtn = '') {
            const uniqueId = 'img-' + Date.now() + '-' + Math.random().toString(36).substr(2, 5);
            const url = attachment.url || '';
            const name = attachment.name || 'Image';
            const opacity = isTemp ? 'opacity-70' : '';

            if (isTemp) {
                return `
                <div class="attachment-preview relative ${opacity}" style="animation: messageSlideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1)">
                    <div class="relative inline-block max-w-[280px] md:max-w-[320px] rounded-xl overflow-hidden border border-white/10">
                        <div class="aspect-square bg-gray-700/50 flex items-center justify-center">
                            <i data-lucide="image" class="h-8 w-8 text-gray-500"></i>
                        </div>
                    </div>
                    ${removeBtn}
                </div>
            `;
            }

            return `
            <div class="attachment-preview relative group" style="animation: messageSlideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1)">
                <div class="relative inline-block max-w-[280px] md:max-w-[320px] rounded-xl overflow-hidden border border-white/10 cursor-pointer" onclick="openImagePreview('${url}', '${name.replace(/'/g, "\\'")}')">
                    <div id="${uniqueId}-skeleton" class="absolute inset-0 bg-gray-700/50 animate-pulse rounded-xl"></div>
                    <img
                        src="${url}"
                        alt="${name}"
                        class="block w-full h-auto rounded-xl ${opacity}"
                        style="max-height: 320px; object-fit: contain;"
                        loading="lazy"
                        onload="this.classList.add('image-loaded'); document.getElementById('${uniqueId}-skeleton')?.remove()"
                        onerror="document.getElementById('${uniqueId}-skeleton')?.remove()"
                    />
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-200 rounded-xl flex items-center justify-center">
                        <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                            <div class="flex items-center gap-1">
                                <button type="button" onclick="event.stopPropagation(); openImagePreview('${url}', '${name.replace(/'/g, "\\'")}')" class="h-8 w-8 rounded-full bg-white/90 text-gray-900 flex items-center justify-center hover:bg-white transition shadow-lg" title="Preview">
                                    <i data-lucide="maximize-2" class="h-4 w-4"></i>
                                </button>
                                <a href="${url}" target="_blank" download="${name}" class="h-8 w-8 rounded-full bg-white/90 text-gray-900 flex items-center justify-center hover:bg-white transition shadow-lg" title="Download">
                                    <i data-lucide="download" class="h-4 w-4"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                ${removeBtn}
            </div>
        `;
        }

        function openImagePreview(url, name) {
            let overlay = document.getElementById('imagePreviewOverlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'imagePreviewOverlay';
                overlay.innerHTML = `
                <div id="imagePreviewBackdrop" class="fixed inset-0 z-[99999] bg-black/80 backdrop-blur-sm flex items-center justify-center opacity-0 transition-opacity duration-300 ease-out">
                    <button type="button" id="imagePreviewClose" class="absolute top-4 right-4 z-10 h-10 w-10 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/70 transition border border-white/10">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                    <div id="imagePreviewContainer" class="relative max-w-[90vw] max-h-[85vh] flex items-center justify-center scale-95 opacity-0 transition-all duration-300 ease-out">
                        <a href="${url}" target="_blank" id="imagePreviewDownload" class="absolute top-4 right-16 z-10 h-10 w-10 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/70 transition border border-white/10" title="Download original">
                            <i data-lucide="download" class="h-5 w-5"></i>
                        </a>
                        <img id="imagePreviewImg" src="" alt="" class="max-w-full max-h-[85vh] rounded-lg shadow-2xl object-contain" style="max-width: 90vw;" />
                    </div>
                </div>
            `;
                document.body.appendChild(overlay);
                if (window.lucide) lucide.createIcons();

                overlay.addEventListener('click', (e) => {
                    if (e.target === overlay.querySelector('#imagePreviewBackdrop') || e.target.closest('#imagePreviewClose')) {
                        closeImagePreview();
                    }
                });

                document.addEventListener('keydown', function imagePreviewKeyHandler(e) {
                    if (e.key === 'Escape' && document.getElementById('imagePreviewOverlay')) {
                        closeImagePreview();
                    }
                });
            }

            const backdrop = document.getElementById('imagePreviewBackdrop');
            const container = document.getElementById('imagePreviewContainer');
            const img = document.getElementById('imagePreviewImg');
            const downloadBtn = document.getElementById('imagePreviewDownload');

            img.src = url;
            img.alt = name || 'Image preview';
            if (downloadBtn) downloadBtn.href = url;
            if (downloadBtn) downloadBtn.download = name || 'image';

            backdrop.classList.remove('opacity-0');
            requestAnimationFrame(() => {
                container.classList.remove('scale-95', 'opacity-0');
                container.classList.add('scale-100', 'opacity-100');
            });
        }

        function closeImagePreview() {
            const backdrop = document.getElementById('imagePreviewBackdrop');
            const container = document.getElementById('imagePreviewContainer');
            if (!backdrop || !container) return;

            container.classList.remove('scale-100', 'opacity-100');
            container.classList.add('scale-95', 'opacity-0');
            backdrop.classList.add('opacity-0');

            setTimeout(() => {
                const overlay = document.getElementById('imagePreviewOverlay');
                if (overlay) overlay.remove();
            }, 300);
        }

        window.confirmDeleteConversation = function(conversationId) {
            let overlay = document.getElementById('deleteConfirmOverlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'deleteConfirmOverlay';
                overlay.innerHTML = `
                <div id="deleteConfirmBackdrop" class="fixed inset-0 z-[99999] bg-black/60 backdrop-blur-sm flex items-center justify-center opacity-0 transition-opacity duration-200 ease-out">
                    <div id="deleteConfirmBox" class="relative mx-4 w-full max-w-[400px] bg-white rounded-2xl shadow-[0_25px_60px_rgba(0,0,0,0.3)] p-6 scale-95 opacity-0 transition-all duration-200 ease-out">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                                <i data-lucide="trash-2" class="h-5 w-5 text-red-600"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">Delete Conversation?</h3>
                        </div>
                        <p class="text-sm text-gray-600 leading-relaxed mb-6">
                            This action will permanently remove this conversation and all of its messages. This action cannot be undone.
                        </p>
                        <div class="flex items-center gap-3 justify-end">
                            <button type="button" onclick="closeDeleteConfirm()" class="px-4 py-2.5 text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-150">
                                Cancel
                            </button>
                            <button type="button" id="deleteConfirmButton" class="px-4 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors duration-150 shadow-sm">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            `;
                document.body.appendChild(overlay);
                if (window.lucide) lucide.createIcons();

                overlay.querySelector('#deleteConfirmBackdrop').addEventListener('click', (e) => {
                    if (e.target === overlay.querySelector('#deleteConfirmBackdrop')) {
                        closeDeleteConfirm();
                    }
                });

                overlay.querySelector('#deleteConfirmButton').addEventListener('click', () => {
                    const id = parseInt(overlay.dataset.conversationId || '0');
                    if (id) deleteConversation(id);
                });
            }

            overlay.dataset.conversationId = conversationId;

            const backdrop = document.getElementById('deleteConfirmBackdrop');
            const box = document.getElementById('deleteConfirmBox');

            backdrop.classList.remove('opacity-0');
            requestAnimationFrame(() => {
                box.classList.remove('scale-95', 'opacity-0');
                box.classList.add('scale-100', 'opacity-100');
            });
        }

        window.closeDeleteConfirm = function() {
            const backdrop = document.getElementById('deleteConfirmBackdrop');
            const box = document.getElementById('deleteConfirmBox');
            if (!backdrop || !box) return;

            box.classList.remove('scale-100', 'opacity-100');
            box.classList.add('scale-95', 'opacity-0');
            backdrop.classList.add('opacity-0');

            setTimeout(() => {
                const overlay = document.getElementById('deleteConfirmOverlay');
                if (overlay) overlay.remove();
            }, 200);
        }

        async function deleteConversation(conversationId) {
            closeDeleteConfirm();

            const item = document.querySelector(`.conversation-item[data-id="${conversationId}"]`);
            if (item) {
                item.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                item.style.transform = 'translateX(-20px)';
                item.style.opacity = '0';
                item.style.height = item.offsetHeight + 'px';
                setTimeout(() => {
                    item.style.padding = '0';
                    item.style.margin = '0';
                    item.style.height = '0';
                    item.style.overflow = 'hidden';
                    item.style.border = 'none';
                }, 150);
            }

            if (currentConversationId == conversationId) {
                resetModalChat();
            }

            try {
                const response = await fetch(`/messages/conversations/${conversationId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                if (response.ok) {
                    setTimeout(() => {
                        if (item) item.remove();
                        const list = document.getElementById('modalConversationsList');
                        if (list && list.children.length === 0) {
                            const emptyState = document.getElementById('modalConversationsEmpty');
                            if (emptyState) {
                                emptyState.classList.remove('hidden');
                                emptyState.classList.add('animate-fade-in');
                            }
                        }
                    }, 300);
                } else {
                    if (item) {
                        item.style.transform = '';
                        item.style.opacity = '';
                        item.style.height = '';
                        item.style.padding = '';
                        item.style.margin = '';
                        item.style.overflow = '';
                        item.style.border = '';
                    }
                }
            } catch (error) {
                if (item) {
                    item.style.transform = '';
                    item.style.opacity = '';
                    item.style.height = '';
                    item.style.padding = '';
                    item.style.margin = '';
                    item.style.overflow = '';
                    item.style.border = '';
                }
            }
        }

        function clearSelectedAttachment() {
            selectedAttachment = null;
            const input = document.getElementById('modalAttachmentInput');
            const preview = document.getElementById('modalAttachmentPreview');
            const progress = document.getElementById('modalAttachmentProgress');
            if (input) input.value = '';
            if (preview) preview.classList.add('hidden');
            if (progress) progress.classList.add('hidden');
        }

        async function uploadModalAttachment(file) {
            if (!file || !currentConversationId) return;

            const preview = document.getElementById('modalAttachmentPreview');
            const progressEl = document.getElementById('modalAttachmentProgress');
            const progressBar = document.getElementById('modalAttachmentProgressBar');
            const nameEl = document.getElementById('modalAttachmentName');
            const sizeEl = document.getElementById('modalAttachmentSize');
            const iconContainer = document.getElementById('modalAttachmentIcon');

            if (preview) preview.classList.remove('hidden');
            if (progressEl) progressEl.classList.remove('hidden');

            const validTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/zip',
                'application/x-rar-compressed',
                'text/plain',
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'video/mp4',
                'video/quicktime',
                'audio/mpeg',
                'audio/wav'
            ];

            const maxSize = 25 * 1024 * 1024;

            if (file.size > maxSize) {
                alert('File size must be less than 25MB.');
                clearSelectedAttachment();
                return;
            }

            const fileIcon = getFileIcon(file.type, file.name.split('.').pop());
            if (iconContainer) {
                iconContainer.className = `h-8 w-8 rounded-lg flex items-center justify-center shrink-0 ${fileIcon.color}`;
                iconContainer.innerHTML = `<i data-lucide="${fileIcon.icon}" class="h-4 w-4"></i>`;
                lucideCreateIcons();
            }
            if (nameEl) nameEl.textContent = file.name;
            if (sizeEl) sizeEl.textContent = formatFileSize(file.size);

            const formData = new FormData();
            formData.append('file', file);
            formData.append('conversation_id', currentConversationId);

            try {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '/messages/upload');
                xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                xhr.setRequestHeader('Accept', 'application/json');

                xhr.upload.onprogress = (event) => {
                    if (event.lengthComputable && progressBar) {
                        const percent = (event.loaded / event.total) * 100;
                        progressBar.style.width = percent + '%';
                    }
                };

                const response = await new Promise((resolve, reject) => {
                    xhr.onload = () => resolve({
                        status: xhr.status,
                        data: JSON.parse(xhr.responseText)
                    });
                    xhr.onerror = () => reject(new Error('Upload failed'));
                    xhr.send(formData);
                });

                if (response.status === 201) {
                    selectedAttachment = response.data.data;
                    if (progressBar) progressBar.classList.add('bg-emerald-500');
                    if (progressBar) progressBar.style.width = '100%';
                    setTimeout(() => {
                        if (progressEl) progressEl.classList.add('hidden');
                        if (progressBar) progressBar.classList.remove('bg-emerald-500');
                        if (progressBar) progressBar.style.width = '0%';
                    }, 1000);
                } else {
                    throw new Error(response.data.message || 'Upload failed');
                }
            } catch (error) {
                alert(error.message || 'Failed to upload file.');
                clearSelectedAttachment();
            }
        }

        async function loadModalUsers(search = '') {
            const params = new URLSearchParams();
            if (search) params.set('search', search);

            const response = await fetch(`/messages/users?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            if (!response.ok) return;
            const data = await response.json();
            renderModalUsers(data.data);
        }

        // =====================================================
        // RENDER USERS
        // Shows real online / offline activity status
        // =====================================================

        function renderModalUsers(users) {

            const container =
                document.getElementById('modalUsersList');

            const emptyState =
                document.getElementById('modalUsersEmpty');

            if (!container) return;


            // =====================================================
            // EMPTY STATE
            // =====================================================

            if (!users || users.length === 0) {

                container.innerHTML = '';

                emptyState?.classList.remove('hidden');

                lucideCreateIcons();

                return;
            }

            emptyState?.classList.add('hidden');


            // =====================================================
            // RENDER USER LIST
            // =====================================================

            container.innerHTML = users.map((user, index) => {

                // =================================================
                // GET USER ACTIVITY
                //
                // Examples:
                // Active now
                // Active 5 minutes ago
                // Active yesterday
                // Offline
                // =================================================

                const activityStatus =
                    formatUserActivity(user.last_active_at);


                // =================================================
                // CHECK IF CURRENTLY ONLINE
                // =================================================

                const isOnline =
                    activityStatus === 'Active now';


                // =================================================
                // ONLINE DOT
                //
                // Green = Active now
                // Gray = Offline / previously active
                // =================================================

                const statusDotClass =
                    isOnline
                        ? 'bg-emerald-500'
                        : 'bg-gray-300';


                // =================================================
                // ACTIVITY TEXT COLOR
                // =================================================

                const statusTextClass =
                    isOnline
                        ? 'text-emerald-600'
                        : 'text-gray-400';


                return `

                    <button
                        type="button"
                        data-user-id="${user.user_id}"
                        class="
                            user-row
                            w-full
                            flex
                            items-center
                            gap-3
                            px-3
                            py-2.5
                            rounded-xl
                            text-left
                            cursor-pointer
                            transition-all
                            duration-200
                            ease-out
                            hover:bg-gray-50
                            active:bg-gray-100
                        "
                        style="
                            animation: userCardIn 0.3s
                            cubic-bezier(0.4, 0, 0.2, 1) both;
                            animation-delay: ${index * 25}ms;
                        "
                    >

                        <!-- ===================================== -->
                        <!-- AVATAR -->
                        <!-- ===================================== -->

                        <div class="relative shrink-0">

                            <div
                                class="
                                    h-9
                                    w-9
                                    rounded-full
                                    bg-gradient-to-br
                                    from-gray-100
                                    to-gray-200
                                    flex
                                    items-center
                                    justify-center
                                    text-xs
                                    font-bold
                                    text-gray-700
                                    shadow-sm
                                    ring-2
                                    ring-white
                                "
                            >
                                ${escapeHtml(user.initials)}
                            </div>


                            <!-- ================================= -->
                            <!-- REAL ONLINE / OFFLINE DOT -->
                            <!-- ================================= -->

                            <span
                                class="
                                    absolute
                                    -bottom-0.5
                                    -right-0.5
                                    h-3
                                    w-3
                                    rounded-full
                                    border-2
                                    border-white
                                    shadow-sm
                                    ${statusDotClass}
                                "
                            ></span>

                        </div>


                        <!-- ===================================== -->
                        <!-- USER INFORMATION -->
                        <!-- ===================================== -->

                        <div class="flex-1 min-w-0">

                            <p
                                class="
                                    text-sm
                                    font-semibold
                                    text-gray-900
                                    truncate
                                    leading-tight
                                "
                            >
                                ${escapeHtml(user.name)}
                            </p>


                            <!-- ================================= -->
                            <!-- ROLE -->
                            <!-- ================================= -->

                            <p
                                class="
                                    text-xs
                                    text-gray-500
                                    truncate
                                    leading-tight
                                "
                            >
                                ${escapeHtml(user.role)}
                            </p>


                            <!-- ================================= -->
                            <!-- LAST ACTIVE STATUS -->
                            <!-- ================================= -->

                            <p
                                class="
                                    text-[10px]
                                    truncate
                                    leading-tight
                                    mt-0.5
                                    ${statusTextClass}
                                    ${isOnline ? 'font-medium' : ''}
                                "
                            >
                                ${escapeHtml(activityStatus)}
                            </p>

                        </div>

                    </button>
                `;

            }).join('');


            lucideCreateIcons();
        }
        async function startConversationWithUser(userId) {

            console.log('Clicked user ID:', userId);

            try {

                const response = await fetch('/messages/conversations', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        user_id: userId
                    })
                });

                console.log('Response status:', response.status);

                const data = await response.json();

                console.log('Server response:', data);

                if (!response.ok) {
                    console.error('Failed to create/open conversation:', data);
                    return;
                }

                const conversation = data.data;

                console.log('Conversation:', conversation);

                switchModalTab('conversations');

                await loadModalConversations();

                openModalConversation(conversation.conversation_id);

            } catch (error) {

                console.error('START CONVERSATION ERROR:', error);

            }
        }

        async function markModalAsRead() {
            if (!currentConversationId) return;
            await fetch(`/messages/conversations/${currentConversationId}/read`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            lucideCreateIcons();
            updateTopbarMessageBadge();

            document
                .getElementById('closePinnedMessagesModal')
                ?.addEventListener(
                    'click',
                    closePinnedMessagesModal
                );

            document
                .getElementById('pinnedMessagesBackdrop')
                ?.addEventListener(
                    'click',
                    closePinnedMessagesModal
                );

            const conversationsList = document.getElementById('modalConversationsList');
            if (conversationsList) {
                conversationsList.addEventListener('click', (e) => {
                    const item = e.target.closest('.conversation-item');
                    if (item && !e.target.closest('.conversation-delete-btn')) {
                        const id = item.dataset.id;
                        if (id) openModalConversation(id);
                    }
                });
            }

            const usersList = document.getElementById('modalUsersList');
            if (usersList) {
                usersList.addEventListener('click', (e) => {
                    const row = e.target.closest('.user-row');
                    if (row) {
                        const userId = parseInt(row.getAttribute('data-user-id') || '0');
                        if (userId) startConversationWithUser(userId);
                    }
                });
            }

            const conversationSearch = document.getElementById('modalConversationSearch');
            let searchTimeout;
            if (conversationSearch) {
                conversationSearch.addEventListener('input', () => {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(loadModalConversations, 250);
                });
            }

            // =====================================================
            // CANCEL MESSAGE REPLY
            // =====================================================

            const cancelReplyButton =
                document.getElementById(
                    'modalCancelReply'
                );

            if (cancelReplyButton) {

                cancelReplyButton.addEventListener(
                    'click',
                    cancelReply
                );
            }

            const messageForm = document.getElementById('modalMessageForm');
            if (messageForm) {
                messageForm.addEventListener('submit', sendModalMessage);
            }

            const messageInput = document.getElementById('modalMessageInput');
            if (messageInput) {
                messageInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        messageForm?.dispatchEvent(new Event('submit'));
                    }
                });

                messageInput.addEventListener('input', () => {
                    messageInput.style.height = 'auto';
                    messageInput.style.height = Math.min(messageInput.scrollHeight, 120) + 'px';
                });
            }

            const attachmentButton = document.getElementById('modalAttachmentButton');
            const attachmentInput = document.getElementById('modalAttachmentInput');
            const attachmentRemove = document.getElementById('modalAttachmentRemove');

            if (attachmentButton && attachmentInput) {
                attachmentButton.addEventListener('click', () => {
                    attachmentInput.click();
                });

                attachmentInput.addEventListener('change', (e) => {
                    const file = e.target.files?.[0];
                    if (file) {
                        uploadModalAttachment(file);
                    }
                });
            }

            if (attachmentRemove) {
                attachmentRemove.addEventListener('click', () => {
                    clearSelectedAttachment();
                });
            }

            const messagesContainer =
                document.getElementById('modalMessagesContainer');

            if (messagesContainer) {

                messagesContainer.addEventListener(
                    'click',
                    async function (event) {
                        // =========================================
                        // OPEN / CLOSE REACTION POPUP
                        // =========================================
                        const pinnedSeeAll =
                            event.target.closest(
                                '.pinned-see-all'
                            );

                        if (pinnedSeeAll) {

                            event.preventDefault();

                            openPinnedMessagesModal();

                            return;
                        }

                        const reactionTrigger =
                            event.target.closest(
                                '.message-reaction-trigger'
                            );

                        if (reactionTrigger) {

                            event.preventDefault();
                            event.stopPropagation();

                            const currentControl =
                                reactionTrigger.closest(
                                    '.message-reaction-control'
                                );

                            const currentPicker =
                                currentControl?.querySelector(
                                    '.message-reaction-picker'
                                );

                            if (!currentPicker) {
                                return;
                            }


                            // =====================================
                            // CHECK CURRENT STATE
                            // =====================================

                            const wasOpen =
                                !currentPicker.classList.contains(
                                    'hidden'
                                );


                            // =====================================
                            // CLOSE OTHER OPEN REACTION PICKERS
                            // =====================================

                            messagesContainer
                                .querySelectorAll(
                                    '.message-reaction-picker'
                                )
                                .forEach(picker => {

                                    picker.classList.add('hidden');
                                    picker.classList.remove('flex');

                                });


                            // =====================================
                            // OPEN THIS PICKER
                            // =====================================

                            if (!wasOpen) {

                                currentPicker.classList.remove(
                                    'hidden'
                                );

                                currentPicker.classList.add(
                                    'flex'
                                );
                            }

                            return;
                        }

                        // =========================================
                        // REACTION PICKER BUTTON
                        // =========================================

                        const reactionButton =
                            event.target.closest(
                                '.message-reaction-option'
                            );

                        if (reactionButton) {

                            event.preventDefault();
                            event.stopPropagation();

                            const row =
                                reactionButton.closest(
                                    '.message-row'
                                );

                            if (!row) {
                                return;
                            }

                            const messageId =
                                row.dataset.messageId;

                            const reaction =
                                reactionButton.dataset.reaction;


                            // =====================================
                            // CLOSE PICKER AFTER SELECTING
                            // =====================================

                            const picker =
                                reactionButton.closest(
                                    '.message-reaction-picker'
                                );

                            if (picker) {

                                picker.classList.add('hidden');
                                picker.classList.remove('flex');

                            }


                            // =====================================
                            // SAVE REACTION
                            // =====================================

                            await reactToMessage(
                                messageId,
                                reaction
                            );

                            return;
                        }


                        // =========================================
                        // EXISTING REACTION CHIP
                        // =========================================

                        const reactionChip =
                            event.target.closest(
                                '.message-reaction-chip'
                            );

                        if (reactionChip) {

                            event.preventDefault();
                            event.stopPropagation();


                            const row =
                                reactionChip.closest(
                                    '.message-row'
                                );

                            if (!row) {
                                return;
                            }


                            // =====================================
                            // GET REACTION DATA STORED ON CHIP
                            // =====================================

                            let reactions = [];

                            try {

                                reactions =
                                    JSON.parse(
                                        decodeURIComponent(
                                            reactionChip.dataset
                                                .reactions || '[]'
                                        )
                                    );

                            } catch (error) {

                                console.error(
                                    'Unable to read reaction data:',
                                    error
                                );

                                return;
                            }


                            // =====================================
                            // OPEN REACTION DETAILS
                            // =====================================

                            showMessageReactionsModal(
                                row.dataset.messageId,
                                reactions,
                                null
                            );

                            return;
                        }


                        // =========================================
                        // MESSAGE THREE DOT MENU
                        // =========================================

                        const moreButton = event.target.closest('.message-more-btn');
                        if (moreButton) {
                            event.preventDefault();
                            event.stopPropagation();
                            const menu = moreButton.closest('.message-more')?.querySelector('.message-more-menu');
                            if (menu) {
                                const opening = menu.classList.contains('hidden');
                                closeAllMessageMenus(menu);
                                menu.classList.toggle('hidden', !opening);
                            }
                            return;
                        }

                        const actionRow = event.target.closest('.message-row');
                        if (actionRow && event.target.closest('.message-edit-btn')) { closeAllMessageMenus(); await editMessageFromRow(actionRow); return; }
                        if (actionRow && event.target.closest('.message-unsend-btn')) { closeAllMessageMenus(); await unsendMessageFromRow(actionRow); return; }
                        if (actionRow && event.target.closest('.message-remove-btn')) { closeAllMessageMenus(); await removeMessageFromRow(actionRow); return; }
                        if (actionRow && event.target.closest('.message-pin-btn')) { closeAllMessageMenus(); await pinMessageFromRow(actionRow); return; }
                        if (actionRow && event.target.closest('.message-forward-btn')) { closeAllMessageMenus(); await forwardMessageFromRow(actionRow); return; }

                        // =========================================
                        // CLICK QUOTED REPLY
                        //
                        // Jump to the exact original message and
                        // briefly highlight the corresponding bubble.
                        // =========================================

                        const replyQuote =
                            event.target.closest(
                                '.reply-quote'
                            );

                        if (replyQuote) {

                            event.preventDefault();
                            event.stopPropagation();

                            await jumpToOriginalReplyMessage(
                                replyQuote.dataset.replyMessageId
                            );

                            return;
                        }


                        // =========================================
                        // EXISTING REPLY BUTTON
                        // =========================================

                        const replyButton =
                            event.target.closest(
                                '.message-reply-btn'
                            );

                        if (!replyButton) {
                            return;
                        }


                        const messageRow =
                            replyButton.closest(
                                '.message-row'
                            );

                        if (!messageRow) {
                            return;
                        }


                        startReplyToMessage(
                            messageRow.dataset.messageId,
                            messageRow.dataset.messageSender,
                            messageRow.dataset.messageContent
                        );
                    }
                );
                messagesContainer.addEventListener('scroll', () => {
                    if (messagesContainer.scrollTop === 0 && hasMoreMessages && !isLoadingMessages) {
                        const prevHeight = messagesContainer.scrollHeight;
                        messagesPage++;
                        loadModalMessages(currentConversationId, true).then(() => {
                            messagesContainer.scrollTop = messagesContainer.scrollHeight - prevHeight;
                        });
                    }
                });
            }

            const userSearch = document.getElementById('modalUserSearch');
            if (userSearch) {
                userSearch.addEventListener('input', () => {
                    clearTimeout(userSearchTimeout);
                    userSearchTimeout = setTimeout(() => {
                        loadModalUsers(userSearch.value);
                    }, 250);
                });
            }

            listenToUserMessagesRealtime();
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('.message-more')) closeAllMessageMenus();
        });

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible' && currentConversationId && document.getElementById('messagingModal') && !document.getElementById('messagingModal').classList.contains('hidden')) {
                markModalAsRead();
            }
        });

        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const modal = document.getElementById('messagingModal');
                if (modal && !modal.classList.contains('hidden')) {
                    closeMessagingModal();
                }
            }
        });
    })();
</script>

<style>
    /* ======================================
       KEYFRAMES
    ====================================== */

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInScale {
        from {
            opacity: 0;
            transform: scale(0.95);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-10px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(10px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes scaleIn {
        from {
            transform: scale(0.92);
            opacity: 0;
        }

        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    @keyframes badgePop {
        0% {
            transform: scale(0);
        }

        60% {
            transform: scale(1.15);
        }

        100% {
            transform: scale(1);
        }
    }

    @keyframes shimmer {
        0% {
            background-position: -200% 0;
        }

        100% {
            background-position: 200% 0;
        }
    }

    @keyframes conversationSlideIn {
        from {
            opacity: 0;
            transform: translateX(-10px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes userCardIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes inputFocusGlow {
        0% {
            box-shadow: 0 0 0 0 rgba(15, 23, 42, 0.08);
        }

        50% {
            box-shadow: 0 0 0 4px rgba(15, 23, 42, 0.08);
        }

        100% {
            box-shadow: 0 0 0 4px rgba(15, 23, 42, 0.06);
        }
    }

    /* ======================================
       UTILITY CLASSES
    ====================================== */

    .animate-fade-in {
        animation: fadeIn 0.25s ease-out both;
    }

    .animate-fade-in-up {
        animation: fadeInUp 0.3s ease-out both;
    }

    .animate-scale-in {
        animation: fadeInScale 0.3s ease-out both;
    }

    .animate-slide-in-left {
        animation: slideInLeft 0.3s ease-out both;
    }

    .animate-slide-in-right {
        animation: slideInRight 0.3s ease-out both;
    }

    /* ======================================
       MESSAGING MODAL CONTAINER
    ====================================== */

    #messagingModalContainer {
        will-change: transform, opacity;
    }

    /* ======================================
       CONVERSATION LIST
    ====================================== */

    #modalConversationsList .conversation-item {
        transition: background-color 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
        will-change: transform;
    }

    #modalConversationsList .conversation-item:hover {
        transform: translateX(2px);
        box-shadow: inset 2px 0 0 rgba(15, 23, 42, 0.06);
    }

    #modalConversationsList .conversation-item:active {
        transform: scale(0.98);
    }

    /* ======================================
       CONVERSATION DELETE BUTTON
    ====================================== */

    .conversation-delete-btn {
        will-change: transform, opacity, color, background-color;
    }

    .conversation-delete-btn:active {
        transform: scale(0.9) !important;
    }

    /* ======================================
       USER LIST
    ====================================== */

    .user-row {
        will-change: transform, background-color;
    }

    .user-row:hover {
        transform: translateX(2px);
    }

    .user-row:active {
        transform: scale(0.98);
    }

    .user-row .ring-2 {
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .user-row:hover .ring-2 {
        box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.3);
    }

    /* ======================================
       MESSAGE BUBBLES
    ====================================== */

    [style*="messageSlideIn"] {
        will-change: transform, opacity;
        animation: fadeInUp 0.3s cubic-bezier(0.4, 0, 0.2, 1) both;
    }

    .max-w-\[70\%\].rounded-2xl {
        transition: box-shadow 0.2s ease, transform 0.15s ease;
        will-change: transform;
    }

    .max-w-\[70\%\].rounded-2xl:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .bg-gray-900.text-white.max-w-\[70\%\].rounded-2xl:hover {
        box-shadow: 0 2px 12px rgba(15, 23, 42, 0.15);
    }

    .bg-gray-100.text-gray-900.max-w-\[70\%\].rounded-2xl:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    /* ======================================
       MESSAGE COMPOSER
    ====================================== */

    #modalMessageInput {
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
    }

    #modalMessageInput:focus {
        border-color: #111827;
        animation: inputFocusGlow 0.3s ease-out forwards;
        background-color: #ffffff;
    }

    #modalMessageInput::placeholder {
        transition: color 0.2s ease, transform 0.2s ease;
    }

    #modalMessageInput:focus::placeholder {
        transform: translateX(4px);
        color: #9CA3AF;
    }

    #modalSendButton {
        will-change: transform, opacity;
        transition: background-color 0.2s ease, transform 0.15s ease, opacity 0.2s ease;
    }

    #modalSendButton:active:not(:disabled) {
        transform: scale(0.92);
    }

    #modalSendButton:hover:not(:disabled) {
        transform: scale(1.05);
    }

    #modalAttachmentButton {
        will-change: transform;
        transition: background-color 0.2s ease, color 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
    }

    #modalAttachmentButton:hover {
        transform: scale(1.08);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    #modalAttachmentButton:active {
        transform: scale(0.92);
    }

    /* ======================================
       SEARCH BARS
    ====================================== */

    #modalConversationSearch,
    #modalUserSearch {
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
    }

    #modalConversationSearch:focus,
    #modalUserSearch:focus {
        border-color: #111827;
        box-shadow: 0 0 0 4px rgba(15, 23, 42, 0.06);
        background-color: #ffffff;
    }

    #modalConversationSearch::placeholder,
    #modalUserSearch::placeholder {
        transition: color 0.2s ease, transform 0.2s ease;
    }

    #modalConversationSearch:focus::placeholder,
    #modalUserSearch:focus::placeholder {
        transform: translateX(4px);
        color: #9CA3AF;
    }

    /* ======================================
       TAB BUTTONS
    ====================================== */

    #modalTabConversations,
    #modalTabUsers {
        transition: all 0.2s ease;
        will-change: transform, box-shadow;
    }

    #modalTabConversations:active,
    #modalTabUsers:active {
        transform: scale(0.96);
    }

    /* ======================================
       UNREAD BADGE
    ====================================== */

    .shrink-0.inline-flex.h-5 {
        animation: badgePop 0.3s cubic-bezier(0.4, 0, 0.2, 1) both;
        will-change: transform;
    }

    /* ======================================
       EMPTY STATES
    ====================================== */

    #modalChatEmptyState,
    #modalConversationsEmpty,
    #modalUsersEmpty {
        will-change: opacity, transform;
    }

    #modalChatEmptyState:not(.hidden),
    #modalConversationsEmpty:not(.hidden),
    #modalUsersEmpty:not(.hidden) {
        animation: fadeInUp 0.3s ease-out both;
    }

    /* ======================================
       SCROLLBARS
    ====================================== */

    .messaging-user-scroll {
        scrollbar-width: thin;
        scrollbar-color: transparent transparent;
        transition: scrollbar-color 0.3s ease;
    }

    .messaging-user-scroll::-webkit-scrollbar {
        width: 3px;
    }

    .messaging-user-scroll::-webkit-scrollbar-track {
        background: transparent;
        border-radius: 20px;
    }

    .messaging-user-scroll::-webkit-scrollbar-thumb {
        background: transparent;
        border-radius: 20px;
        transition: background 0.3s ease;
    }

    .messaging-user-scroll:hover::-webkit-scrollbar-thumb {
        background: #E2E8F0;
    }

    #modalMessagesContainer {
        scroll-behavior: smooth;
    }

    #modalMessagesContainer::-webkit-scrollbar {
        width: 4px;
    }

    #modalMessagesContainer::-webkit-scrollbar-track {
        background: transparent;
        border-radius: 20px;
    }

    #modalMessagesContainer::-webkit-scrollbar-thumb {
        background: transparent;
        border-radius: 20px;
        transition: background 0.3s ease;
    }

    #modalMessagesContainer:hover::-webkit-scrollbar-thumb {
        background: #E2E8F0;
    }

    #modalMessagesContainer {
        scrollbar-width: thin;
        scrollbar-color: transparent transparent;
    }

    /* ======================================
       ATTACHMENT PREVIEW
    ====================================== */

    #modalAttachmentPreview:not(.hidden) {
        animation: fadeInUp 0.25s ease-out both;
    }

    #modalAttachmentPreview .h-1.rounded-full {
        transition: width 0.3s ease, background-color 0.3s ease;
    }

    /* ======================================
       IMAGE LOADING SKELETON
    ====================================== */

    .animate-pulse {
        background: linear-gradient(90deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.05) 50%, rgba(255, 255, 255, 0) 100%);
        background-size: 200% 100%;
        animation: shimmer 1.5s ease-in-out infinite;
    }

    /* ======================================
       MODAL OPEN / CLOSE TRANSITIONS
    ====================================== */

    #messagingModalBackdrop {
        will-change: opacity;
    }

    /* ======================================
       CONVERSATION DELETE OVERLAY
    ====================================== */

    #deleteConfirmBackdrop {
        will-change: opacity;
    }

    #deleteConfirmBox {
        will-change: transform, opacity;
    }

    /* ======================================
       NEW MESSAGE TAB
    ====================================== */

    #modalTabConversations,
    #modalTabUsers {
        position: relative;
        overflow: hidden;
    }

    #modalTabConversations::after,
    #modalTabUsers::after {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.3);
        opacity: 0;
        transition: opacity 0.2s ease;
        pointer-events: none;
        border-radius: inherit;
    }

    #modalTabConversations:active::after,
    #modalTabUsers:active::after {
        opacity: 1;
    }

    /* ======================================
       CHAT HEADER APPEARANCE
    ====================================== */

    #modalChatHeader:not(.hidden) {
        animation: fadeIn 0.2s ease-out both;
    }

    /* ======================================
       COMPOSER APPEARANCE
    ====================================== */

    #modalComposer:not(.hidden) {
        animation: fadeInUp 0.25s ease-out both;
    }

    /* ======================================
       LOADING EMPTY STATE
    ====================================== */

    .text-center.py-12 {
        animation: fadeInUp 0.3s ease-out both;
    }

    /* ======================================
    TYPING INDICATOR DOT ANIMATION
    ====================================== */

    .typing-dot {
        animation: typingBounce 1.2s infinite ease-in-out;
    }

    .typing-dot:nth-child(2) {
        animation-delay: 0.15s;
    }

    .typing-dot:nth-child(3) {
        animation-delay: 0.3s;
    }

    @keyframes typingBounce {

        0%,
        60%,
        100% {
            transform: translateY(0);
            opacity: 0.4;
        }

        30% {
            transform: translateY(-4px);
            opacity: 1;
        }
    }

    /* ======================================
    CHAT TYPING INDICATOR TRANSITION

    When typing starts:
    Last message smoothly moves upward.

    When typing stops:
    Last message smoothly returns downward.
    ====================================== */

    .typing-indicator-wrapper {
        max-height: 0;
        opacity: 0;
        overflow: hidden;
        padding-bottom: 0;
        margin-top: 0;
        transform: translateY(8px);

        transition:
            max-height 0.25s ease,
            opacity 0.2s ease,
            transform 0.25s ease,
            padding-bottom 0.25s ease,
            margin-top 0.25s ease;

        pointer-events: none;
    }

    .typing-indicator-wrapper.is-typing {
        max-height: 60px;
        opacity: 1;
        padding-bottom: 12px;
        margin-top: 12px;
        transform: translateY(0);
    }


    /* ======================================
    CONVERSATION LIST TYPING DOTS
    ====================================== */

    .conversation-typing-dots {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        height: 14px;
    }

    .conversation-typing-dots span {
        width: 4px;
        height: 4px;
        border-radius: 9999px;
        background: currentColor;
        animation: conversationTypingBounce 1.2s infinite ease-in-out;
    }

    .conversation-typing-dots span:nth-child(2) {
        animation-delay: 0.15s;
    }

    .conversation-typing-dots span:nth-child(3) {
        animation-delay: 0.3s;
    }

    @keyframes conversationTypingBounce {

        0%,
        60%,
        100% {
            transform: translateY(0);
            opacity: 0.4;
        }

        30% {
            transform: translateY(-3px);
            opacity: 1;
        }
    }
</style>