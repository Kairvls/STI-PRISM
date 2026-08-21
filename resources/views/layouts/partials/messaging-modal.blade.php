{{-- ===================================================== --}}
{{-- MESSAGING MODAL --}}
{{-- Shared across all authenticated modules --}}
{{-- ===================================================== --}}
<div
    id="messagingHoverTooltip"
    class="messaging-hover-tooltip pointer-events-none fixed z-[10080] hidden max-w-[240px] rounded-lg bg-gray-900 px-2.5 py-1.5 text-center text-[11px] font-medium leading-snug text-white shadow-[0_8px_24px_rgba(15,23,42,0.28)]"
    role="tooltip"
></div>


<div id="messagingModal" class="hidden" aria-hidden="true">
    {{-- Backdrop --}}
    <div id="messagingModalBackdrop" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 opacity-0 transition-opacity duration-75">
        {{-- Modal Container --}}
        <div id="messagingModalContainer" class="relative mx-0 sm:mx-4 w-full max-w-[1100px] h-[100dvh] sm:h-[min(86dvh,860px)] max-h-[100dvh] sm:max-h-[calc(100dvh-1.5rem)] bg-white rounded-none sm:rounded-2xl shadow-[0_25px_60px_rgba(0,0,0,0.25)] overflow-hidden scale-[0.98] opacity-0 transition-all duration-150 flex">

            {{-- ===================================== --}}
            {{-- LEFT PANEL --}}
            {{-- ===================================== --}}
            <aside id="modalConversationsPane" class="w-full md:w-[min(360px,38%)] shrink-0 border-r border-gray-200 flex flex-col bg-white min-h-0 min-w-0">

                {{-- Header --}}
                <div class="border-b border-gray-100 p-4 max-md:pr-14">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-base font-bold text-gray-900">Messages</h2>
                            <p class="text-[11px] text-gray-500 mt-0.5">Your conversations</p>
                        </div>
                        <button
                            type="button"
                            id="messagingFullscreenButton"
                            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-900"
                            data-tooltip="Full screen"
                            aria-label="Full screen"
                        >
                            <i data-lucide="maximize-2" class="h-4 w-4"></i>
                        </button>
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
            <main
                id="modalChatArea"
                class="
                    hidden
                    md:flex
                    flex-1
                    min-w-0
                    max-w-full
                    flex-col
                    min-h-0
                    overflow-hidden
                    bg-white
                "
            >

                {{-- Chat Header --}}
                <div id="modalChatHeader" class="hidden shrink-0 border-b border-gray-100 bg-white">

                    {{-- ====================================== --}}
                    {{-- NORMAL CHAT HEADER --}}
                    {{-- Info button opens the right sidebar --}}
                    {{-- Search now lives inside that sidebar --}}
                    {{-- ====================================== --}}
                    <div
                        id="modalChatHeaderNormal"
                        class="flex w-full min-w-0 items-center gap-2 sm:gap-3 py-3 sm:py-4 pl-2 sm:pl-4 pr-3 sm:pr-4"
                    >
                        <button
                            type="button"
                            id="modalChatBackButton"
                            class="mr-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-gray-700 transition hover:bg-gray-100 md:hidden"
                            data-tooltip="Back to conversations"
                            aria-label="Back to conversations"
                        >
                            <i data-lucide="chevron-left" class="h-5 w-5"></i>
                        </button>
                        <div id="modalChatAvatar" class="h-9 w-9 shrink-0 rounded-full bg-gradient-to-br from-emerald-100 to-emerald-200 flex items-center justify-center text-xs font-semibold text-emerald-700"></div>

                        <div class="min-w-0 flex-1">
                            <h3 id="modalChatTitle" class="text-sm font-semibold text-gray-900 truncate"></h3>
                            <p id="modalChatSubtitle" class="text-xs text-gray-500 truncate"></p>
                        </div>

                        {{-- ===================================================== --}}
                        {{-- HEADER ACTIONS: AUDIO CALL / VIDEO CALL / INFO --}}
                        {{-- ===================================================== --}}
                        <div
                            id="modalChatHeaderActions"
                            class="ml-auto flex shrink-0 items-center justify-end gap-1"
                        >
                            <button
                                type="button"
                                id="modalAudioCallButton"
                                class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-900"
                                data-tooltip="Audio call"
                                aria-label="Audio call"
                            >
                                <i data-lucide="phone" class="h-5 w-5"></i>
                            </button>

                            <button
                                type="button"
                                id="modalVideoCallButton"
                                class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-900"
                                data-tooltip="Video call"
                                aria-label="Video call"
                            >
                                <i data-lucide="video" class="h-5 w-5"></i>
                            </button>

                            <button
                                type="button"
                                id="modalConversationInfoButton"
                                class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-900"
                                data-tooltip="Conversation info"
                                aria-label="Conversation info"
                            >
                                <i data-lucide="info" class="h-5 w-5"></i>
                            </button>

                            <button
                                type="button"
                                id="messagingSmartCloseButtonThread"
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-700 transition hover:bg-gray-200 hover:text-gray-900"
                                data-tooltip="Close"
                                aria-label="Close"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M18 6 6 18"></path>
                                    <path d="m6 6 12 12"></path>
                                </svg>
                            </button>

                            <button
                                type="button"
                                id="messagingFullscreenButtonThread"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 md:hidden"
                                data-tooltip="Full screen"
                                aria-label="Full screen"
                            >
                                <i data-lucide="maximize-2" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </div>

                    {{-- ====================================== --}}
                    {{-- SEARCH INSIDE THIS CONVERSATION --}}
                    {{-- Example: projector  2 of 5  ↑ ↓  X --}}
                    {{-- ====================================== --}}
                    <div
                        id="modalConversationSearchBar"
                        class="hidden items-center gap-2 py-3 pl-4 pr-14"
                    >
                        <div class="relative min-w-0 flex-1">
                            <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>

                            <input
                                type="text"
                                id="modalConversationMessageSearch"
                                placeholder="Search in conversation..."
                                autocomplete="off"
                                class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2 pl-9 pr-3 text-sm text-gray-900 outline-none transition focus:border-gray-400 focus:ring-4 focus:ring-gray-100"
                            />
                        </div>

                        <span
                            id="modalConversationSearchCount"
                            class="min-w-[58px] whitespace-nowrap text-center text-xs font-medium text-gray-500"
                        >
                            0 of 0
                        </span>

                        <button
                            type="button"
                            id="modalConversationSearchPrevious"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 disabled:cursor-not-allowed disabled:opacity-30"
                            data-tooltip="Previous result"
                            aria-label="Previous result"
                            disabled
                        >
                            <i data-lucide="chevron-up" class="h-4 w-4"></i>
                        </button>

                        <button
                            type="button"
                            id="modalConversationSearchNext"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 disabled:cursor-not-allowed disabled:opacity-30"
                            data-tooltip="Next result"
                            aria-label="Next result"
                            disabled
                        >
                            <i data-lucide="chevron-down" class="h-4 w-4"></i>
                        </button>

                        
                    </div>
                </div>

                <button
                    type="button"
                    id="modalPinnedBanner"
                    class="hidden w-full shrink-0 border-b border-gray-100 bg-white px-4 py-2 text-left transition hover:bg-gray-50"
                    data-tooltip="View pinned messages"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <i data-lucide="pin" class="h-4 w-4 shrink-0 text-gray-500"></i>
                        <div class="min-w-0 flex-1">
                            <p id="modalPinnedBannerSender" class="truncate text-xs font-semibold text-gray-900"></p>
                            <p id="modalPinnedBannerText" class="truncate text-xs text-gray-500"></p>
                        </div>
                        <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-gray-400"></i>
                    </div>
                </button>

                {{-- Conversation Container --}}
                <div
                    id="modalConversationContainer"
                    class="
                        relative
                        flex
                        min-w-0
                        max-w-full
                        flex-1
                        flex-col
                        overflow-hidden
                        min-h-0
                    "
                >

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
                        class="
                            hidden
                            min-w-0
                            max-w-full
                            flex-1
                            overflow-x-hidden
                            overflow-y-auto
                            min-h-0
                            px-4
                            pt-3
                            pb-8
                            messaging-thread
                        "
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

                    {{-- Loading overlay while the thread is fetched --}}
                    <div
                        id="modalThreadLoading"
                        class="
                            pointer-events-none
                            absolute
                            inset-0
                            z-20
                            hidden
                            items-center
                            justify-center
                            bg-white
                        "
                        aria-hidden="true"
                    >
                        <div
                            class="
                                h-9
                                w-9
                                animate-spin
                                rounded-full
                                border-[3px]
                                border-gray-200
                                border-t-gray-800
                            "
                            role="status"
                            aria-label="Loading messages"
                        ></div>
                    </div>

                    {{-- ===================================================== --}}
                    {{-- SCROLL TO LATEST MESSAGE INDICATOR --}}
                    {{-- Shows after the user scrolls upward --}}
                    {{-- ===================================================== --}}
                    <button
                        type="button"
                        id="modalScrollToLatestButton"
                        class="
                            pointer-events-none
                            absolute
                            bottom-4
                            left-1/2
                            z-30
                            flex
                            h-11
                            w-11
                            -translate-x-1/2
                            translate-y-2
                            items-center
                            justify-center
                            rounded-full
                            border
                            border-gray-200
                            bg-white
                            text-gray-500
                            opacity-0
                            shadow-lg
                            transition-all
                            duration-200
                            hover:bg-gray-50
                            hover:text-gray-900
                        "
                        data-tooltip="Jump to latest message"
                        aria-label="Jump to latest message"
                    >
                        <i data-lucide="arrow-down" class="h-5 w-5"></i>
                    </button>

                </div>

                
                {{-- ===================================================== --}}
                {{-- MESSENGER STYLE COMPOSER --}}
                {{-- ===================================================== --}}

                <div
                    id="modalComposer"
                    class="
                        hidden
                        w-full
                        min-w-0
                        max-w-full
                        shrink-0
                        overflow-hidden
                        border-t
                        border-gray-100
                        bg-white
                    "
                >
                    <div class="w-full min-w-0 max-w-full overflow-hidden px-4 py-3">

                        {{-- ===================================================== --}}
                        {{-- REPLY PREVIEW --}}
                        {{-- ===================================================== --}}

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
                                    data-tooltip="Cancel reply"
                                >
                                    <i data-lucide="x" class="h-4 w-4"></i>
                                </button>

                            </div>
                        </div>


                        {{-- ===================================================== --}}
                        {{-- MESSENGER STYLE COMPOSER --}}
                        {{-- Attachments and textarea share ONE composer box --}}
                        {{-- ===================================================== --}}

                        <form
                            id="modalMessageForm"
                            class="flex w-full min-w-0 items-end gap-2"
                        >

                            {{-- Hidden Windows file picker --}}
                            <input
                                type="file"
                                id="modalAttachmentInput"
                                class="hidden"
                                multiple
                                accept="image/jpeg,image/png,image/gif,image/webp,.txt,.csv,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.odt,.ods,.zip"
                            />


                            {{-- Attachment button --}}
                            <button
                                type="button"
                                id="modalAttachmentButton"
                                class="
                                    flex h-10 w-10 shrink-0
                                    items-center justify-center
                                    rounded-full
                                    text-gray-500
                                    bg-gray-50
                                    transition
                                    hover:bg-gray-100
                                    hover:text-gray-900
                                "
                                data-tooltip="Attach files"
                            >
                                <i
                                    data-lucide="paperclip"
                                    class="h-5 w-5"
                                ></i>
                            </button>


                            {{-- ================================================= --}}
                            {{-- ONE LARGE COMPOSER BOX --}}
                            {{-- ================================================= --}}

                            <div
                                class="
                                    min-w-0
                                    max-w-full
                                    flex-1
                                    overflow-hidden
                                    rounded-2xl
                                    bg-gray-100
                                    px-3
                                    py-2
                                "
                            >

                                {{-- ============================================= --}}
                                {{-- ATTACHMENTS INSIDE COMPOSER --}}
                                {{-- ============================================= --}}

                                <div
                                    id="modalAttachmentPreview"
                                    class="
                                        hidden
                                        w-full
                                        min-w-0
                                        max-w-full
                                        overflow-hidden
                                        pb-2
                                    "
                                >
                                    <div
                                        id="modalAttachmentItems"
                                        class="
                                            flex
                                            min-w-0
                                            max-w-full
                                            items-center
                                            gap-2
                                            overflow-x-auto
                                            overflow-y-hidden
                                            overscroll-x-contain
                                            px-1
                                            pt-2
                                            pb-1
                                        "
                                    ></div>
                                </div>


                                {{-- ============================================= --}}
                                {{-- TEXTAREA BELOW ATTACHMENTS --}}
                                {{-- ============================================= --}}

                                <textarea
                                    id="modalMessageInput"
                                    rows="1"
                                    placeholder="Aa"
                                    class="
                                        block
                                        min-h-[26px]
                                        max-h-[120px]
                                        w-full
                                        resize-none
                                        border-0
                                        bg-transparent
                                        px-1
                                        py-1
                                        text-sm
                                        leading-5
                                        text-gray-900
                                        outline-none
                                        focus:ring-0
                                    "
                                ></textarea>

                            </div>


                            {{-- Like when empty, Send when typing --}}
                            <button
                                type="button"
                                id="modalSendButton"
                                data-composer-mode="like"
                                class="
                                    flex h-10 w-10 shrink-0
                                    items-center justify-center
                                    rounded-full
                                    bg-transparent
                                    text-[#0084FF]
                                    transition
                                    hover:bg-transparent
                                    hover:opacity-80
                                    active:scale-95
                                "
                                data-tooltip="Send a like"
                                aria-label="Send a like"
                            >
                                <svg
                                    data-composer-action="like"
                                    class="messenger-like-icon h-8 w-8"
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                    aria-hidden="true"
                                >
                                    <path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z"/>
                                </svg>
                                <i
                                    data-composer-action="send"
                                    data-lucide="send"
                                    class="hidden h-4 w-4"
                                ></i>
                            </button>

                        </form>

                    </div>
                </div>
            </main>

            {{-- ===================================================== --}}
            {{-- CONVERSATION INFO / MEDIA & FILES RIGHT SIDEBAR --}}
            {{-- ===================================================== --}}
            <aside
                id="modalConversationInfoSidebar"
                class="
                    hidden
                    w-full
                    md:w-[min(330px,34%)]
                    shrink-0
                    flex-col
                    overflow-hidden
                    border-l
                    border-gray-200
                    bg-white
                "
            >
                {{-- ============================================= --}}
                {{-- MAIN INFO VIEW --}}
                {{-- ============================================= --}}
                <div
                    id="modalConversationInfoHome"
                    class="flex min-h-0 flex-1 flex-col"
                >
                    <div
                        class="
                            flex
                            shrink-0
                            items-center
                            justify-between
                            border-b
                            border-gray-100
                            px-4
                            py-3
                        "
                    >
                        <p class="text-sm font-semibold text-gray-900">
                            Conversation info
                        </p>

                        {{-- ===================================================== --}}
                        {{-- NO X HERE --}}
                        {{-- Close this sidebar by clicking the header info icon again. --}}
                        {{-- Hidden compatibility element keeps existing JS safe. --}}
                        {{-- ===================================================== --}}
                        <button
                            type="button"
                            id="modalConversationInfoClose"
                            class="hidden"
                            tabindex="-1"
                            aria-hidden="true"
                        ></button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-4 py-5">

                        {{-- USER --}}
                        {{-- ===================================================== --}}
                        {{-- CONVERSATION INFO PROFILE --}}
                        {{-- Group chats allow picture and name editing --}}
                        {{-- ===================================================== --}}

                        <div class="flex flex-col items-center text-center">

                            {{-- ================================================= --}}
                            {{-- AVATAR / GROUP PROFILE PICTURE --}}
                            {{-- ================================================= --}}

                            <div
                                id="modalConversationInfoAvatarWrapper"
                                class="group relative"
                            >

                                <div
                                    id="modalConversationInfoAvatar"
                                    class="
                                        flex
                                        h-20
                                        w-20
                                        items-center
                                        justify-center
                                        overflow-hidden
                                        rounded-full
                                        bg-gray-200
                                        text-xl
                                        font-semibold
                                        text-gray-600
                                    "
                                ></div>


                                {{-- ================================================= --}}
                                {{-- GROUP PICTURE EDIT OVERLAY --}}
                                {{-- Only shown by JS for group conversations --}}
                                {{-- ================================================= --}}

                                <button
                                    type="button"
                                    id="modalEditGroupImageButton"
                                    class="
                                        hidden
                                        absolute
                                        -bottom-1
                                        -right-1
                                        z-20
                                        h-8
                                        w-8
                                        items-center
                                        justify-center
                                        rounded-full
                                        border-[3px]
                                        border-white
                                        bg-gray-100
                                        text-gray-600
                                        opacity-0
                                        shadow-sm
                                        transition
                                        hover:bg-gray-200
                                        hover:text-gray-900
                                        group-hover:opacity-100
                                    "
                                    data-tooltip="Change group picture"
                                    aria-label="Change group picture"
                                >
                                    <i
                                        data-lucide="pencil"
                                        class="h-4 w-4"
                                    ></i>
                                </button>


                                {{-- ================================================= --}}
                                {{-- HIDDEN FILE PICKER --}}
                                {{-- Clicking pencil opens Windows File Explorer --}}
                                {{-- ================================================= --}}

                                <input
                                    type="file"
                                    id="modalGroupImageInput"
                                    class="hidden"
                                    accept="image/jpeg,image/png,image/webp"
                                />

                            </div>


                            {{-- ================================================= --}}
                            {{-- CONVERSATION / GROUP NAME --}}
                            {{-- ================================================= --}}

                            <div
                                id="modalConversationInfoNameWrapper"
                                class="
                                    group
                                    relative
                                    mt-3
                                    inline-flex
                                    items-center
                                    justify-center
                                    gap-1
                                "
                            >

                                <h3
                                    id="modalConversationInfoName"
                                    class="
                                        text-base
                                        font-semibold
                                        text-gray-900
                                    "
                                >
                                    Conversation
                                </h3>


                                {{-- ================================================= --}}
                                {{-- EDIT GROUP NAME --}}
                                {{-- ================================================= --}}

                                <button
                                    type="button"
                                    id="modalEditGroupNameButton"
                                    class="
                                        hidden
                                        h-7
                                        w-7
                                        items-center
                                        justify-center
                                        rounded-full
                                        text-gray-400
                                        opacity-0
                                        transition
                                        hover:bg-gray-100
                                        hover:text-gray-900
                                        group-hover:opacity-100
                                    "
                                    data-tooltip="Edit group name"
                                    aria-label="Edit group name"
                                >
                                    <i
                                        data-lucide="pencil"
                                        class="h-4 w-4"
                                    ></i>
                                </button>

                            </div>


                            {{-- ================================================= --}}
                            {{-- STATUS / MEMBER COUNT --}}
                            {{-- ================================================= --}}

                            <p
                                id="modalConversationInfoStatus"
                                class="mt-1 text-xs text-gray-500"
                            ></p>

                        </div>

                        {{-- ===================================================== --}}
                        {{-- SEARCH THIS CONVERSATION --}}
                        {{-- Opens a dedicated right sidebar view like Messenger --}}
                        {{-- ===================================================== --}}
                        <div class="mt-5 flex justify-center gap-8">

                            {{-- ===================================================== --}}
                            {{-- GROUP CHAT ONLY: MUTE / UNMUTE --}}
                            {{-- ===================================================== --}}
                            <button
                                type="button"
                                id="modalConversationMuteButton"
                                class="
                                    hidden
                                    group
                                    flex-col
                                    items-center
                                    gap-1.5
                                    text-sm
                                    font-medium
                                    text-gray-700
                                "
                            >
                                <div
                                    class="
                                        flex
                                        h-10
                                        w-10
                                        items-center
                                        justify-center
                                        rounded-full
                                        bg-gray-100
                                        text-gray-600
                                        transition
                                        group-hover:bg-gray-200
                                        group-hover:text-gray-900
                                    "
                                >
                                    <i
                                        id="modalConversationMuteIcon"
                                        data-lucide="bell-off"
                                        class="h-5 w-5"
                                    ></i>
                                </div>

                                <span id="modalConversationMuteLabel">
                                    Mute
                                </span>
                            </button>

                            <button
                                type="button"
                                id="modalConversationSidebarSearchButton"
                                class="
                                    group
                                    flex
                                    flex-col
                                    items-center
                                    gap-1.5
                                    text-sm
                                    font-medium
                                    text-gray-700
                                "
                            >
                                <div
                                    class="
                                        flex
                                        h-10
                                        w-10
                                        items-center
                                        justify-center
                                        rounded-full
                                        bg-gray-100
                                        text-gray-600
                                        transition
                                        group-hover:bg-gray-200
                                        group-hover:text-gray-900
                                    "
                                >
                                    <i data-lucide="search" class="h-5 w-5"></i>
                                </div>

                                <span>Search</span>
                            </button>
                        </div>

                        {{-- ===================================================== --}}
                        {{-- CHAT INFO --}}
                        {{-- Messenger-style section above Media & files --}}
                        {{-- ===================================================== --}}
                        <div class="mt-2 border-t border-gray-100 pt-3">
                            <button
                                type="button"
                                id="modalChatInfoAccordionButton"
                                class="
                                    flex
                                    w-full
                                    items-center
                                    justify-between
                                    rounded-lg
                                    px-2
                                    py-3
                                    text-left
                                    text-sm
                                    font-semibold
                                    text-gray-900
                                    transition
                                    hover:bg-gray-50
                                "
                            >
                                <span>Chat info</span>

                                <i
                                    id="modalChatInfoAccordionChevron"
                                    data-lucide="chevron-up"
                                    class="h-4 w-4"
                                ></i>
                            </button>

                            <div
                                id="modalChatInfoAccordionContent"
                                class="pb-2"
                            >
                                <button
                                    type="button"
                                    id="modalViewPinnedMessagesButton"
                                    class="
                                        flex
                                        w-full
                                        items-center
                                        gap-3
                                        rounded-lg
                                        px-2
                                        py-3
                                        text-left
                                        text-sm
                                        font-semibold
                                        text-gray-700
                                        transition
                                        hover:bg-gray-50
                                    "
                                >
                                    <i data-lucide="pin" class="h-5 w-5"></i>

                                    <span class="flex-1">
                                        View pinned messages
                                    </span>

                                    <span
                                        id="modalConversationPinnedCount"
                                        class="text-xs text-gray-400"
                                    >
                                        0
                                    </span>
                                </button>
                            </div>
                        </div>

                        {{-- ===================================================== --}}
                        {{-- GROUP CHAT ONLY: CHAT MEMBERS --}}
                        {{-- ===================================================== --}}
                        <div
                            id="modalGroupMembersSection"
                            class="mt-2 hidden border-t border-gray-100 pt-3"
                        >
                            <button
                                type="button"
                                id="modalGroupMembersAccordionButton"
                                class="
                                    flex
                                    w-full
                                    items-center
                                    justify-between
                                    rounded-lg
                                    px-2
                                    py-3
                                    text-left
                                    text-sm
                                    font-semibold
                                    text-gray-900
                                    transition
                                    hover:bg-gray-50
                                "
                            >
                                <span>Chat members</span>

                                <i
                                    id="modalGroupMembersAccordionChevron"
                                    data-lucide="chevron-up"
                                    class="h-4 w-4"
                                ></i>
                            </button>

                            <div
                                id="modalGroupMembersAccordionContent"
                                class="pb-2"
                            >
                                <div
                                    id="modalGroupMembersList"
                                    class="space-y-1"
                                ></div>

                                <button
                                    type="button"
                                    id="modalAddGroupPeopleButton"
                                    class="
                                        flex
                                        w-full
                                        items-center
                                        gap-3
                                        rounded-lg
                                        px-2
                                        py-3
                                        text-left
                                        text-sm
                                        font-semibold
                                        text-gray-700
                                        transition
                                        hover:bg-gray-50
                                    "
                                >
                                    <span
                                        class="
                                            flex
                                            h-9
                                            w-9
                                            shrink-0
                                            items-center
                                            justify-center
                                            rounded-full
                                            bg-gray-100
                                        "
                                    >
                                        <i data-lucide="user-plus" class="h-5 w-5"></i>
                                    </span>

                                    <span>Add people</span>
                                </button>
                            </div>
                        </div>

                        {{-- MEDIA AND FILES --}}
                        <div class="mt-2 border-t border-gray-100 pt-3">
                            <button
                                type="button"
                                id="modalMediaFilesAccordionButton"
                                class="
                                    flex
                                    w-full
                                    items-center
                                    justify-between
                                    rounded-lg
                                    px-2
                                    py-3
                                    text-left
                                    text-sm
                                    font-semibold
                                    text-gray-900
                                    transition
                                    hover:bg-gray-50
                                "
                            >
                                <span>Media & files</span>

                                <i
                                    id="modalMediaFilesAccordionChevron"
                                    data-lucide="chevron-up"
                                    class="h-4 w-4"
                                ></i>
                            </button>

                            <div
                                id="modalMediaFilesAccordionContent"
                                class="pb-2"
                            >
                                <button
                                    type="button"
                                    id="modalOpenConversationMedia"
                                    class="
                                        flex
                                        w-full
                                        items-center
                                        gap-3
                                        rounded-lg
                                        px-2
                                        py-3
                                        text-left
                                        text-sm
                                        font-medium
                                        text-gray-700
                                        transition
                                        hover:bg-gray-50
                                    "
                                >
                                    <i data-lucide="image" class="h-5 w-5"></i>

                                    <span class="flex-1">Media</span>

                                    <span
                                        id="modalConversationMediaCount"
                                        class="text-xs text-gray-400"
                                    >
                                        0
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    id="modalOpenConversationFiles"
                                    class="
                                        flex
                                        w-full
                                        items-center
                                        gap-3
                                        rounded-lg
                                        px-2
                                        py-3
                                        text-left
                                        text-sm
                                        font-medium
                                        text-gray-700
                                        transition
                                        hover:bg-gray-50
                                    "
                                >
                                    <i data-lucide="file-text" class="h-5 w-5"></i>

                                    <span class="flex-1">Files</span>

                                    <span
                                        id="modalConversationFileCount"
                                        class="text-xs text-gray-400"
                                    >
                                        0
                                    </span>
                                </button>
                            </div>
                        </div>

                        {{-- ===================================================== --}}
                        {{-- GROUP CHAT: PERSISTENT LEAVE GROUP BUTTON --}}
                        {{-- Shown only when the selected conversation is a group --}}
                        {{-- ===================================================== --}}
                        <div
                            id="modalGroupLeaveSection"
                            class="mt-2 hidden border-t border-gray-100 pt-3"
                        >
                            <button
                                type="button"
                                id="modalGroupLeaveButton"
                                class="
                                    flex
                                    w-full
                                    items-center
                                    gap-3
                                    rounded-lg
                                    px-2
                                    py-3
                                    text-left
                                    text-sm
                                    font-semibold
                                    text-gray-700
                                    transition
                                    hover:bg-gray-50
                                "
                            >
                                <span
                                    class="
                                        flex
                                        h-9
                                        w-9
                                        shrink-0
                                        items-center
                                        justify-center
                                        rounded-full
                                        bg-gray-100
                                    "
                                >
                                    <i data-lucide="log-out" class="h-5 w-5"></i>
                                </span>

                                <span>Leave group</span>
                            </button>
                        </div>

</div>
                </div>

                {{-- ===================================================== --}}
                {{-- SEARCH CONVERSATION VIEW --}}
                {{-- ===================================================== --}}
                <div
                    id="modalConversationSidebarSearchView"
                    class="hidden min-h-0 flex-1 flex-col"
                >
                    {{-- HEADER --}}
                    <div
                        class="
                            flex
                            shrink-0
                            items-center
                            gap-3
                            border-b
                            border-gray-100
                            px-4
                            py-3
                        "
                    >
                        <button
                            type="button"
                            id="modalConversationSidebarSearchBack"
                            class="
                                flex
                                h-8
                                w-8
                                shrink-0
                                items-center
                                justify-center
                                rounded-full
                                text-gray-500
                                transition
                                hover:bg-gray-100
                                hover:text-gray-900
                            "
                            data-tooltip="Back"
                            aria-label="Back"
                        >
                            <i data-lucide="arrow-left" class="h-4 w-4"></i>
                        </button>

                        <p class="text-sm font-semibold text-gray-900">
                            Search
                        </p>
                    </div>

                    {{-- SEARCH INPUT --}}
                    <div class="shrink-0 px-4 pt-4">
                        <div class="relative">
                            <i
                                data-lucide="search"
                                class="
                                    absolute
                                    left-3
                                    top-1/2
                                    h-5
                                    w-5
                                    -translate-y-1/2
                                    text-gray-400
                                "
                            ></i>

                            <input
                                type="text"
                                id="modalConversationSidebarSearchInput"
                                placeholder="Search in conversation"
                                autocomplete="off"
                                class="
                                    w-full
                                    rounded-full
                                    border-0
                                    bg-gray-100
                                    py-2.5
                                    pl-10
                                    pr-10
                                    text-sm
                                    text-gray-900
                                    outline-none
                                    ring-0
                                    placeholder:text-gray-400
                                    focus:ring-2
                                    focus:ring-gray-200
                                "
                            >

                            <button
                                type="button"
                                id="modalConversationSidebarSearchClear"
                                class="
                                    absolute
                                    right-2
                                    top-1/2
                                    hidden
                                    h-7
                                    w-7
                                    -translate-y-1/2
                                    items-center
                                    justify-center
                                    rounded-full
                                    bg-gray-300
                                    text-gray-600
                                    transition
                                    hover:bg-gray-400
                                    hover:text-gray-900
                                "
                                data-tooltip="Clear search"
                            >
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </div>

                    {{-- STATUS BEFORE SEARCH --}}
                    <div
                        id="modalConversationSidebarSearchHint"
                        class="
                            flex
                            flex-1
                            items-start
                            justify-center
                            px-5
                            pt-10
                            text-center
                            text-xs
                            text-gray-500
                        "
                    >
                        Type a word or phrase to search this conversation.
                    </div>

                    {{-- LOADING --}}
                    <div
                        id="modalConversationSidebarSearchLoading"
                        class="
                            hidden
                            px-5
                            pt-8
                            text-center
                            text-xs
                            text-gray-500
                        "
                    >
                        Searching conversation...
                    </div>

                    {{-- RESULTS HEADER --}}
                    <div
                        id="modalConversationSidebarSearchSummary"
                        class="
                            hidden
                            shrink-0
                            items-center
                            justify-between
                            px-4
                            pb-2
                            pt-4
                        "
                    >
                        <p
                            id="modalConversationSidebarSearchCount"
                            class="text-xs font-medium text-gray-500"
                        >
                            0 results
                        </p>
                    </div>

                    {{-- RESULTS --}}
                    <div
                        id="modalConversationSidebarSearchResults"
                        class="
                            hidden
                            flex-1
                            overflow-y-auto
                            px-3
                            pb-4
                        "
                    ></div>
                </div>

                {{-- ============================================= --}}
                {{-- MEDIA / FILES RECORDS VIEW --}}
                {{-- ============================================= --}}
                <div
                    id="modalConversationAssetsView"
                    class="hidden min-h-0 flex-1 flex-col"
                >
                    <div
                        class="
                            flex
                            shrink-0
                            items-center
                            gap-3
                            border-b
                            border-gray-100
                            px-4
                            py-3
                        "
                    >
                        <button
                            type="button"
                            id="modalConversationAssetsBack"
                            class="
                                flex
                                h-8
                                w-8
                                shrink-0
                                items-center
                                justify-center
                                rounded-full
                                text-gray-500
                                transition
                                hover:bg-gray-100
                                hover:text-gray-900
                            "
                            data-tooltip="Back"
                        >
                            <i data-lucide="arrow-left" class="h-4 w-4"></i>
                        </button>

                        <p class="text-sm font-semibold text-gray-900">
                            Media and files
                        </p>
                    </div>

                    {{-- TABS --}}
                    <div
                        class="
                            flex
                            shrink-0
                            gap-5
                            border-b
                            border-gray-100
                            px-4
                            pt-3
                        "
                    >
                        <button
                            type="button"
                            id="modalConversationMediaTab"
                            class="
                                border-b-2
                                border-gray-900
                                px-1
                                pb-3
                                text-sm
                                font-semibold
                                text-gray-900
                            "
                        >
                            Media
                        </button>

                        <button
                            type="button"
                            id="modalConversationFilesTab"
                            class="
                                border-b-2
                                border-transparent
                                px-1
                                pb-3
                                text-sm
                                font-semibold
                                text-gray-500
                            "
                        >
                            Files
                        </button>
                    </div>

                    <div
                        id="modalConversationAssetsLoading"
                        class="hidden px-4 py-8 text-center text-sm text-gray-500"
                    >
                        Loading records...
                    </div>

                    <div
                        id="modalConversationAssetsEmpty"
                        class="hidden px-5 py-12 text-center"
                    >
                        <div
                            class="
                                mx-auto
                                flex
                                h-12
                                w-12
                                items-center
                                justify-center
                                rounded-full
                                bg-gray-100
                                text-gray-400
                            "
                        >
                            <i data-lucide="folder-open" class="h-5 w-5"></i>
                        </div>

                        <p
                            id="modalConversationAssetsEmptyTitle"
                            class="mt-3 text-sm font-semibold text-gray-800"
                        >
                            No media yet
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            Shared attachments in this conversation will appear here.
                        </p>
                    </div>

                    {{-- MEDIA GRID --}}
                    <div
                        id="modalConversationMediaGrid"
                        class="
                            grid
                            flex-1
                            grid-cols-3
                            content-start
                            gap-1
                            overflow-y-auto
                            p-3
                        "
                    ></div>

                    {{-- FILE LIST --}}
                    <div
                        id="modalConversationFilesList"
                        class="
                            hidden
                            flex-1
                            overflow-y-auto
                            p-3
                        "
                    ></div>
                </div>
            </aside>

            {{-- ===================================== --}}
            {{-- CLOSE BUTTON --}}
            {{-- ===================================== --}}
            <div
                id="messagingModalWindowControls"
                class="absolute top-3 right-3 z-30 flex items-center gap-1"
            >
                <button
                    type="button"
                    id="messagingSmartCloseButton"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-800 shadow-sm border border-gray-300 transition hover:bg-gray-200 hover:text-gray-900"
                    data-tooltip="Close"
                    aria-label="Close"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            </div>

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
            class="relative flex max-h-[min(620px,90dvh)] w-full max-w-xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
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

{{-- ===================================================== --}}
{{-- CREATE GROUP CHAT MODAL --}}
{{-- ===================================================== --}}

{{-- ========================================================= --}}
{{-- GROUP CHAT MUTE MODAL --}}
{{-- Opens when the Mute button in the group sidebar is clicked --}}
{{-- ========================================================= --}}
<div
    id="groupMuteModal"
    class="fixed inset-0 z-[99990] hidden items-center justify-center p-4"
>
    <div
        id="groupMuteBackdrop"
        class="absolute inset-0 bg-black/50"
    ></div>

    <div
        class="
            relative
            z-10
            w-full
            max-w-[520px]
            overflow-hidden
            rounded-2xl
            bg-white
            shadow-2xl
        "
    >
        <div
            class="
                flex
                items-center
                justify-between
                border-b
                border-gray-100
                px-5
                py-4
            "
        >
            <h3 class="text-lg font-semibold text-gray-900">
                Notifications for this chat
            </h3>

            <button
                type="button"
                id="groupMuteClose"
                class="
                    flex
                    h-10
                    w-10
                    items-center
                    justify-center
                    rounded-full
                    bg-gray-100
                    text-gray-500
                    transition
                    hover:bg-gray-200
                    hover:text-gray-900
                "
            >
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <div class="px-5 py-5">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-gray-900">
                        Mute this chat?
                    </p>

                    <p
                        id="groupMuteDescription"
                        class="mt-1 text-xs text-gray-500"
                    >
                        You are currently receiving notifications for this chat.
                    </p>
                </div>

                <button
                    type="button"
                    id="groupMuteToggleButton"
                    class="
                        flex
                        shrink-0
                        items-center
                        gap-2
                        rounded-lg
                        bg-gray-100
                        px-4
                        py-2
                        text-sm
                        font-semibold
                        text-gray-700
                        transition
                        hover:bg-gray-200
                    "
                >
                    <i
                        id="groupMuteToggleIcon"
                        data-lucide="bell-off"
                        class="h-4 w-4"
                    ></i>

                    <span id="groupMuteToggleLabel">
                        Mute
                    </span>
                </button>
            </div>

            <div class="my-5 border-t border-gray-100"></div>

            <div>
                <h4 class="text-base font-semibold text-gray-900">
                    Activity
                </h4>

                <div class="mt-3 space-y-1">
                    <label
                        class="
                            flex
                            cursor-pointer
                            items-center
                            justify-between
                            gap-4
                            rounded-lg
                            px-2
                            py-2.5
                            hover:bg-gray-50
                        "
                    >
                        <div>
                            <p class="text-sm font-semibold text-gray-800">
                                Highlights
                            </p>

                            <p class="text-xs text-gray-500">
                                Notifications will be combined when chats are busy.
                            </p>
                        </div>

                        <input
                            type="radio"
                            name="groupNotificationActivity"
                            value="highlights"
                            class="h-5 w-5"
                        >
                    </label>

                    <label
                        class="
                            flex
                            cursor-pointer
                            items-center
                            justify-between
                            gap-4
                            rounded-lg
                            px-2
                            py-2.5
                            hover:bg-gray-50
                        "
                    >
                        <span class="text-sm font-semibold text-gray-800">
                            All Activity
                        </span>

                        <input
                            type="radio"
                            name="groupNotificationActivity"
                            value="all"
                            class="h-5 w-5"
                            checked
                        >
                    </label>

                    <label
                        class="
                            flex
                            cursor-pointer
                            items-center
                            justify-between
                            gap-4
                            rounded-lg
                            px-2
                            py-2.5
                            hover:bg-gray-50
                        "
                    >
                        <span class="text-sm font-semibold text-gray-800">
                            Mentions and replies only
                        </span>

                        <input
                            type="radio"
                            name="groupNotificationActivity"
                            value="mentions"
                            class="h-5 w-5"
                        >
                    </label>

                    <label
                        class="
                            flex
                            cursor-pointer
                            items-center
                            justify-between
                            gap-4
                            rounded-lg
                            px-2
                            py-2.5
                            hover:bg-gray-50
                        "
                    >
                        <span class="text-sm font-semibold text-gray-800">
                            None
                        </span>

                        <input
                            type="radio"
                            name="groupNotificationActivity"
                            value="none"
                            class="h-5 w-5"
                        >
                    </label>
                </div>
            </div>
        </div>

        <div
            class="
                flex
                justify-end
                gap-2
                border-t
                border-gray-100
                px-5
                py-4
            "
        >
            <button
                type="button"
                id="groupMuteCancel"
                class="
                    rounded-lg
                    px-4
                    py-2
                    text-sm
                    font-semibold
                    text-gray-600
                    transition
                    hover:bg-gray-100
                "
            >
                Cancel
            </button>

            <button
                type="button"
                id="groupMuteDone"
                class="
                    rounded-lg
                    bg-gray-900
                    px-5
                    py-2
                    text-sm
                    font-semibold
                    text-white
                    transition
                    hover:bg-gray-800
                "
            >
                Done
            </button>
        </div>
    </div>
</div>


{{-- ========================================================= --}}
{{-- GROUP MEMBER 3-DOT MENU --}}
{{-- Current user: Leave group only --}}
{{-- Other members: Message, Audio call, Video chat --}}
{{-- ========================================================= --}}
<div
    id="groupMemberMenu"
    class="
        fixed
        z-[99995]
        hidden
        w-[250px]
        overflow-hidden
        rounded-xl
        border
        border-gray-200
        bg-white
        p-1.5
        shadow-2xl
    "
></div>


{{-- ========================================================= --}}
{{-- LEAVE GROUP CONFIRMATION MODAL --}}
{{-- ========================================================= --}}
<div
    id="leaveGroupConfirmModal"
    class="fixed inset-0 z-[100000] hidden items-center justify-center p-4"
>
    <div
        id="leaveGroupConfirmBackdrop"
        class="absolute inset-0 bg-black/50"
    ></div>

    <div
        class="
            relative
            z-10
            w-full
            max-w-[500px]
            overflow-hidden
            rounded-2xl
            bg-white
            shadow-2xl
        "
    >
        <div
            class="
                flex
                items-center
                justify-between
                border-b
                border-gray-100
                px-5
                py-4
            "
        >
            <h3 class="text-lg font-semibold text-gray-900">
                Leave group chat?
            </h3>

            <button
                type="button"
                id="leaveGroupConfirmClose"
                class="
                    flex
                    h-10
                    w-10
                    items-center
                    justify-center
                    rounded-full
                    bg-gray-100
                    text-gray-500
                    transition
                    hover:bg-gray-200
                    hover:text-gray-900
                "
            >
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <div class="px-5 py-5">
            <p class="text-sm leading-6 text-gray-600">
                You will stop receiving messages from this conversation and people will see that you left.
            </p>
        </div>

        <div
            class="
                flex
                justify-end
                gap-2
                border-t
                border-gray-100
                px-5
                py-4
            "
        >
            <button
                type="button"
                id="leaveGroupConfirmCancel"
                class="
                    rounded-lg
                    px-5
                    py-2
                    text-sm
                    font-semibold
                    text-gray-600
                    transition
                    hover:bg-gray-100
                "
            >
                Cancel
            </button>

            <button
                type="button"
                id="leaveGroupConfirmSubmit"
                class="
                    rounded-lg
                    bg-gray-900
                    px-5
                    py-2
                    text-sm
                    font-semibold
                    text-white
                    transition
                    hover:bg-gray-800
                "
            >
                Leave group
            </button>
        </div>
    </div>
</div>


{{-- ========================================================= --}}
{{-- ADD PEOPLE TO GROUP MODAL --}}
{{-- ========================================================= --}}
<div
    id="addGroupPeopleModal"
    class="fixed inset-0 z-[100000] hidden items-center justify-center p-4"
>
    <div
        id="addGroupPeopleBackdrop"
        class="absolute inset-0 bg-black/40"
    ></div>

    <div
        class="
            relative
            z-10
            flex
            max-h-[80vh]
            w-full
            max-w-md
            flex-col
            overflow-hidden
            rounded-2xl
            bg-white
            shadow-2xl
        "
    >
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <div>
                <h3 class="text-base font-semibold text-gray-900">
                    Add people
                </h3>
                <p class="mt-0.5 text-xs text-gray-500">
                    Select users to add to this group
                </p>
            </div>

            <button
                type="button"
                id="addGroupPeopleClose"
                class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100"
            >
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <div class="border-b border-gray-100 p-4">
            <div class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3">
                <i data-lucide="search" class="h-4 w-4 text-gray-400"></i>

                <input
                    type="text"
                    id="addGroupPeopleSearch"
                    class="h-10 w-full bg-transparent text-sm text-gray-900 outline-none"
                    placeholder="Search users..."
                >
            </div>
        </div>

        <div
            id="addGroupPeopleList"
            class="min-h-0 flex-1 overflow-y-auto p-3"
        ></div>

        <p
            id="addGroupPeopleError"
            class="hidden px-5 pb-2 text-sm text-red-600"
        ></p>

        <div class="flex justify-end gap-2 border-t border-gray-100 px-5 py-4">
            <button
                type="button"
                id="addGroupPeopleCancel"
                class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100"
            >
                Cancel
            </button>

            <button
                type="button"
                id="addGroupPeopleSubmit"
                class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
            >
                Add
            </button>
        </div>
    </div>
</div>


<div
    id="createGroupChatModal"
    class="fixed inset-0 z-[10060] hidden"
>
    {{-- BACKDROP --}}
    <div
        id="createGroupChatBackdrop"
        class="absolute inset-0 bg-black/50"
    ></div>

    <div class="relative flex min-h-full items-center justify-center p-4">

        <div
            class="
                relative
                flex
                max-h-[620px]
                w-full
                max-w-md
                flex-col
                overflow-hidden
                rounded-2xl
                bg-white
                shadow-2xl
            "
        >

            {{-- HEADER --}}
            <div
                class="
                    flex
                    shrink-0
                    items-center
                    justify-between
                    border-b
                    border-gray-200
                    px-5
                    py-4
                "
            >
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">
                        Create group chat
                    </h3>

                    <p class="mt-0.5 text-xs text-gray-500">
                        Add at least two other people.
                    </p>
                </div>

                <button
                    type="button"
                    id="closeCreateGroupChatModal"
                    class="
                        flex
                        h-9
                        w-9
                        items-center
                        justify-center
                        rounded-full
                        bg-gray-100
                        text-gray-500
                        transition
                        hover:bg-gray-200
                        hover:text-gray-900
                    "
                >
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            {{-- FORM --}}
            <form
                id="createGroupChatForm"
                class="flex min-h-0 flex-1 flex-col"
            >

                {{-- GROUP NAME --}}
                <div class="shrink-0 px-5 pt-5">

                    <label
                        for="createGroupChatName"
                        class="mb-2 block text-xs font-semibold text-gray-700"
                    >
                        Group name
                    </label>

                    <input
                        type="text"
                        id="createGroupChatName"
                        maxlength="255"
                        placeholder="Enter group name"
                        class="
                            w-full
                            rounded-xl
                            border
                            border-gray-200
                            bg-gray-50
                            px-4
                            py-2.5
                            text-sm
                            text-gray-900
                            outline-none
                            transition
                            focus:border-gray-400
                            focus:bg-white
                            focus:ring-4
                            focus:ring-gray-100
                        "
                    >

                </div>

                {{-- MEMBERS --}}
                <div class="min-h-0 flex-1 px-5 pt-5">

                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-xs font-semibold text-gray-700">
                            Members
                        </p>

                        <span
                            id="createGroupSelectedCount"
                            class="text-xs text-gray-400"
                        >
                            0 selected
                        </span>
                    </div>

                    <div
                        id="createGroupMembersList"
                        class="
                            max-h-[300px]
                            overflow-y-auto
                            rounded-xl
                            border
                            border-gray-200
                        "
                    ></div>

                </div>

                {{-- ERROR --}}
                <p
                    id="createGroupChatError"
                    class="hidden shrink-0 px-5 pt-3 text-xs font-medium text-red-600"
                ></p>

                {{-- FOOTER --}}
                <div
                    class="
                        mt-5
                        flex
                        shrink-0
                        justify-end
                        gap-2
                        border-t
                        border-gray-100
                        px-5
                        py-4
                    "
                >
                    <button
                        type="button"
                        id="cancelCreateGroupChat"
                        class="
                            rounded-lg
                            px-4
                            py-2
                            text-sm
                            font-semibold
                            text-gray-600
                            transition
                            hover:bg-gray-100
                        "
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        id="createGroupChatSubmit"
                        class="
                            rounded-lg
                            bg-gray-900
                            px-4
                            py-2
                            text-sm
                            font-semibold
                            text-white
                            transition
                            hover:bg-gray-800
                            disabled:cursor-not-allowed
                            disabled:opacity-50
                        "
                    >
                        Create group
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>


{{-- ===================================================== --}}
{{-- RENAME GROUP MODAL --}}
{{-- Sits above the main messaging modal just like Mute/Add/Leave --}}
{{-- ===================================================== --}}
<div id="renameGroupModal" class="fixed inset-0 z-[10050] hidden items-center justify-center p-4">
    <div id="renameGroupBackdrop" class="absolute inset-0 bg-black/40"></div>
    <div class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <div>
                <h3 class="text-base font-semibold text-gray-900">Name this group</h3>
                <p class="mt-0.5 text-xs text-gray-500">Everyone in the group will see this name</p>
            </div>
            <button type="button" id="renameGroupClose" class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <div class="p-5">
            <input id="renameGroupInput" type="text" maxlength="255" class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-4 text-sm text-gray-900 outline-none focus:border-gray-400 focus:ring-4 focus:ring-gray-100" placeholder="Group name">
            <p id="renameGroupError" class="mt-2 hidden text-sm text-red-600"></p>
        </div>
        <div class="flex justify-end gap-2 border-t border-gray-100 px-5 py-4">
            <button type="button" id="renameGroupCancel" class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100">Cancel</button>
            <button type="button" id="renameGroupSave" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">Save</button>
        </div>
    </div>
</div>


{{-- ===================================================== --}}
{{-- FULL GROUP PICTURE VIEWER --}}
{{-- Opens only when the group has a real custom picture. --}}
{{-- The generated member collage does NOT open this modal. --}}
{{-- ===================================================== --}}

<div
    id="modalGroupPictureViewer"
    class="
        fixed
        inset-0
        z-[9999]
        hidden
        items-center
        justify-center
        bg-black/80
        p-6
    "
    aria-hidden="true"
>
    <button
        type="button"
        id="modalGroupPictureViewerClose"
        class="
            absolute
            right-6
            top-6
            flex
            h-10
            w-10
            items-center
            justify-center
            rounded-full
            bg-white/10
            text-white
            transition
            hover:bg-white/20
        "
        aria-label="Close picture"
        data-tooltip="Close"
    >
        <i data-lucide="x" class="h-6 w-6"></i>
    </button>

    <img
        id="modalGroupPictureViewerImage"
        src=""
        alt="Group picture"
        class="
            max-h-[88vh]
            max-w-[92vw]
            rounded-xl
            object-contain
            shadow-2xl
        "
    >
</div>


{{-- ===================================================== --}}
{{-- PRIVATE AUDIO / VIDEO CALL MODALS --}}
{{-- ===================================================== --}}
<div id="privateIncomingCallModal" class="fixed inset-0 z-[10050] hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-sm rounded-3xl bg-white p-6 text-center shadow-2xl">
        <div id="privateIncomingCallAvatar" class="mx-auto flex h-20 w-20 items-center justify-center overflow-hidden rounded-full bg-gray-100 text-xl font-semibold text-gray-700"></div>
        <h3 id="privateIncomingCallName" class="mt-4 text-lg font-semibold text-gray-900">Incoming call</h3>
        <p id="privateIncomingCallType" class="mt-1 text-sm text-gray-500">Incoming audio call</p>
        <div class="mt-7 flex items-center justify-center gap-8">
            <button type="button" id="privateCallDeclineButton" class="flex flex-col items-center gap-2 text-xs font-medium text-gray-600"><span class="flex h-14 w-14 items-center justify-center rounded-full bg-red-600 text-white"><i data-lucide="phone-off" class="h-6 w-6"></i></span><span>Decline</span></button>
            <button type="button" id="privateCallAcceptButton" class="flex flex-col items-center gap-2 text-xs font-medium text-gray-600"><span class="flex h-14 w-14 items-center justify-center rounded-full bg-green-600 text-white"><i data-lucide="phone" class="h-6 w-6"></i></span><span>Accept</span></button>
        </div>
    </div>
</div>

<div id="privateActiveCallModal" class="fixed inset-0 z-[10040] hidden items-center justify-center bg-gray-950/95 p-4">
    <div class="relative flex h-[min(720px,92vh)] w-full max-w-4xl flex-col overflow-hidden rounded-3xl bg-gray-900 shadow-2xl">
        <div class="absolute left-5 top-5 z-20"><h3 id="privateActiveCallName" class="text-base font-semibold text-white">Call</h3><p id="privateActiveCallStatus" class="mt-0.5 text-sm text-white/60">Calling...</p></div>
        <div class="relative h-full w-full">

            <!-- Remote Video -->
            <video
                id="privateRemoteVideo"
                autoplay
                playsinline
                class="h-full w-full bg-black object-cover transition-opacity duration-300"
            ></video>

            <!-- Camera Off Placeholder -->
            <div
                id="privateRemotePlaceholder"
                class="absolute inset-0 hidden flex-col items-center justify-center bg-gray-900 transition-opacity duration-300"
            >

                <div
                    id="privateRemotePlaceholderAvatar"
                    class="flex h-32 w-32 items-center justify-center overflow-hidden rounded-full bg-gray-700 text-4xl font-semibold text-white shadow-lg"
                ></div>

                <h3
                    id="privateRemotePlaceholderName"
                    class="mt-5 text-xl font-semibold text-white"
                >
                    User
                </h3>

                <p class="mt-2 text-sm text-gray-400">
                    Camera is off
                </p>

            </div>

        </div>
        <div id="privateAudioCallVisual" class="absolute inset-0 flex items-center justify-center bg-gray-900"><div class="text-center"><div id="privateActiveCallAvatar" class="mx-auto flex h-28 w-28 items-center justify-center overflow-hidden rounded-full bg-gray-700 text-3xl font-semibold text-white"></div><p id="privateAudioCallName" class="mt-4 text-lg font-semibold text-white"></p></div></div>
        <video id="privateLocalVideo" autoplay muted playsinline class="absolute bottom-24 right-5 z-20 hidden h-48 w-36 rounded-2xl bg-black object-cover shadow-xl"></video>

        {{-- Active call controls --}}
        <div id="privateCallActiveControls" class="absolute bottom-5 left-1/2 z-30 flex -translate-x-1/2 items-center gap-3 rounded-full bg-black/40 px-4 py-3 backdrop-blur">
            <button type="button" id="privateCallMuteButton" class="flex h-12 w-12 items-center justify-center rounded-full bg-white/15 text-white hover:bg-white/25" data-tooltip="Mute"><i data-lucide="mic" class="h-5 w-5"></i></button>
            <button type="button" id="privateCallCameraButton" class="hidden h-12 w-12 items-center justify-center rounded-full bg-white/15 text-white hover:bg-white/25" data-tooltip="Camera"><i data-lucide="video" class="h-5 w-5"></i></button>
            <button type="button" id="privateCallEndButton" class="flex h-12 w-12 items-center justify-center rounded-full bg-red-600 text-white hover:bg-red-700" data-tooltip="End call"><i data-lucide="phone-off" class="h-5 w-5"></i></button>
        </div>

        {{-- Call ended controls (Messenger-style) --}}
        <div id="privateCallEndedControls" class="absolute bottom-8 left-1/2 z-30 hidden -translate-x-1/2 flex-col items-center">
            <p id="privateCallEndedStatus" class="mb-6 text-sm text-white/70">Call ended</p>
            <div class="flex items-center gap-10">
                <button type="button" id="privateCallRedialButton" class="flex flex-col items-center gap-2 text-xs font-medium text-white">
                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-green-500 text-white shadow-lg hover:bg-green-600"><i data-lucide="phone" class="h-6 w-6"></i></span>
                    <span>Call Again</span>
                </button>
                <button type="button" id="privateCallCloseButton" class="flex flex-col items-center gap-2 text-xs font-medium text-white/80">
                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-white/15 text-white hover:bg-white/25"><i data-lucide="x" class="h-6 w-6"></i></span>
                    <span>Close</span>
                </button>
            </div>
        </div>
    </div>
</div>


<script>
    (function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const currentUserId = {{ auth()->id() }};
        let currentConversationId = null;
        let lastPinNoticeEl = null;
        let lastUnpinNoticeEl = null;
        let pinNoticeExpiresAt = 0;
        let unpinNoticeExpiresAt = 0;
        const PIN_NOTICE_WINDOW_MS = 10000;
        let pendingLeaveConversationId = null;
        let conversationOpenToken = 0;
        let messagesAbortController = null;
        let messagesPage = 1;
        let isLoadingMessages = false;
        let hasMoreMessages = true;
        let threadSettled = false;
        let bottomSnapTimers = [];
        let stickThreadToBottom = true;
        let ignoreProgrammaticScroll = false;
        let threadScrollAnimation = null;
        let userSearchTimeout = null;
        let selectedAttachments = [];
        let attachmentsUploading = 0;
        let isSendingMessage = false;
        let replyingToMessage = null;
        let editingMessageRow = null;
        let activeRealtimeConversationId = null;
        let typingTimeout = null;
        let globalTypingSent = false;
        const remoteTypingTimeouts = new Map();
        let currentConversationUserName = '';
        let currentConversationUser = null;
        let currentConversationType = 'direct';
        let currentConversationData = null;
        // PRIVATE CALL STATE
        let privateCallPeer = null;
        let privateCallLocalStream = null;
        let privateCallId = null;
        let privateCallTargetUserId = null;
        let privateCallTargetName = '';
        let privateCallTargetPicture = '';
        let privateCallType = 'audio';
        let privateCallConversationId = null;
        let privateIncomingOffer = null;
        let privateCallPendingIce = [];
        let privateCallMuted = false;
        let privateCallCameraEnabled = true;

        // =====================================================
        // IMAGE VIEWER STATE
        // Keeps the current message's images together so the
        // preview can move to previous / next like Messenger.
        // =====================================================
        let imagePreviewGallery = [];
        let imagePreviewIndex = 0;
        let imagePreviewMessageId = null;

        // =====================================================
        // CONVERSATION MEDIA & FILES SIDEBAR STATE
        // =====================================================
        let conversationAssets = [];
        let conversationAssetsLoadedFor = null;
        let conversationAssetsActiveTab = 'media';
        let conversationAssetsLoading = false;


        // =====================================================
        // SEARCH INSIDE CURRENT CONVERSATION
        // =====================================================
        let conversationSearchMatches = [];
        let conversationSearchIndex = -1;
        let conversationSearchTimeout = null;
        let conversationSearchLoadingAll = false;
        let conversationSearchLoadPromise = null;

        // =====================================================
        // RIGHT SIDEBAR SEARCH STATE
        // =====================================================
        let conversationSidebarSearchTimeout = null;
        let conversationSidebarSearchRequest = 0;
        let privateCallAnswered = false;
        let privateCallTimeout = null;
        let privateCallEndStateTimeout = null;
        let privateCallEndedRedialType = 'audio';
        let privateCallEndedRedialTargetId = null;
        let privateCallEndedRedialTargetName = '';
        let privateCallEndedRedialTargetPicture = '';
        let privateCallEndedRedialConversationId = null;

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

        const runMessagingIdleWork = () => {
            sendUserHeartbeat();
            syncDeliveredMessages();
        };

        if (window.requestIdleCallback) {
            requestIdleCallback(runMessagingIdleWork, { timeout: 2500 });
        } else {
            setTimeout(runMessagingIdleWork, 400);
        }

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

        setInterval(
            refreshMessageRelativeTimes,
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

        let privateCallTimer = null;
        let privateCallSeconds = 0;

        function startPrivateCallTimer() {

            stopPrivateCallTimer();

            privateCallSeconds = 0;

            updatePrivateCallTimer();

            privateCallTimer = setInterval(() => {

                privateCallSeconds++;

                updatePrivateCallTimer();

            }, 1000);

        }

        function stopPrivateCallTimer() {

            if (privateCallTimer) {

                clearInterval(privateCallTimer);

                privateCallTimer = null;

            }

        }

        function updatePrivateCallTimer() {

            const minutes = String(
                Math.floor(privateCallSeconds / 60)
            ).padStart(2, '0');

            const seconds = String(
                privateCallSeconds % 60
            ).padStart(2, '0');

            document.getElementById(
                'privateActiveCallStatus'
            ).textContent = `${minutes}:${seconds}`;

        }

        function showRemoteCameraPlaceholder() {

            const placeholder =
                document.getElementById('privateRemotePlaceholder');

            const video =
                document.getElementById('privateRemoteVideo');

            if (video) {
                video.classList.add('hidden');
            }

            if (placeholder) {
                placeholder.classList.remove('hidden');
                placeholder.classList.add('flex');
            }

        }

        function hideRemoteCameraPlaceholder() {

            const placeholder =
                document.getElementById('privateRemotePlaceholder');

            const video =
                document.getElementById('privateRemoteVideo');

            if (placeholder) {
                placeholder.classList.remove('flex');
                placeholder.classList.add('hidden');
            }

            if (video) {
                video.classList.remove('hidden');
            }

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

            if (!msg) {
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

            if (isConversationMuted(msg.conversation_id)) {
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

            if (preview === '[attachment:multiple]') {
                preview = 'Sent you attachments';
            }

            if (isLikeStickerContent(preview) || isLikeStickerContent(msg.message_content)) {
                preview = 'Sent a like';
            }

            if (preview.length > 90) {
                preview =
                    preview.substring(0, 90) + '...';
            }

            // PRISM toast card (Maintenance / President layouts)
            if (typeof window.showMpToast === 'function') {
                const result = window.showMpToast(preview, {
                    title: senderName,
                    type: 'info',
                    timer: 8000,
                });

                // Allow click-to-open by attaching once toast exists
                const host = document.getElementById('mp-toast-host');
                const card = host && host.lastElementChild;
                if (card) {
                    card.style.cursor = 'pointer';
                    card.addEventListener('click', async (event) => {
                        if (event.target.closest('.mp-toast-close')) {
                            return;
                        }
                        if (result && typeof result.close === 'function') {
                            result.close();
                        }
                        openMessagingModal();
                        await openModalConversation(msg.conversation_id);
                        await updateTopbarMessageBadge();
                    });
                }
                return;
            }

            const container =
                document.getElementById('messageToastContainer');

            if (!container) {
                return;
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

                    scheduleLoadModalConversations();

                    updateTopbarMessageBadge();

                    showIncomingMessageToast(msg);

                })

                .listen('.user.typing', (event) => {

                    handleGlobalTyping(event);

                });
        }

        function lucideCreateIcons(root) {
            if (!window.lucide) {
                return;
            }

            const protectConvertedIcons = (scope) => {
                if (!scope?.querySelectorAll) {
                    return;
                }

                scope.querySelectorAll('svg[data-lucide]').forEach(svg => {
                    if (!svg.hasAttribute('data-lucide-ready')) {
                        svg.setAttribute(
                            'data-lucide-ready',
                            svg.getAttribute('data-lucide') || ''
                        );
                    }
                    svg.removeAttribute('data-lucide');
                });
            };

            const paint = (scope) => {
                if (!scope) {
                    return;
                }

                protectConvertedIcons(scope);

                const hasPending =
                    (scope.tagName === 'I' && scope.hasAttribute('data-lucide')) ||
                    Boolean(scope.querySelectorAll && scope.querySelector('i[data-lucide]'));

                if (!hasPending) {
                    return;
                }

                try {
                    lucide.createIcons({
                        root: scope.tagName === 'I'
                            ? (scope.parentElement || scope)
                            : scope
                    });
                } catch (error) {
                    try {
                        lucide.createIcons();
                    } catch (ignored) {}
                }
            };

            if (root) {
                paint(root);
                return;
            }

            [
                document.getElementById('messagingModal'),
                document.getElementById('pinnedMessagesModal'),
                document.getElementById('imagePreviewOverlay'),
                document.getElementById('messageForwardOverlay'),
                document.getElementById('messageUnsendOverlay'),
                document.getElementById('messageReactionsOverlay'),
                document.getElementById('messageActionConfirmOverlay'),
                document.getElementById('groupMuteModal'),
                document.getElementById('modalGroupPictureViewer'),
                document.getElementById('renameGroupModal'),
                document.getElementById('createGroupChatModal'),
                document.getElementById('leaveGroupConfirmModal'),
                document.getElementById('addGroupPeopleModal'),
                document.getElementById('privateIncomingCallModal'),
                document.getElementById('privateActiveCallModal'),
            ].filter(Boolean).forEach(paint);

            document.querySelectorAll('i[data-lucide]').forEach(icon => {
                paint(icon.parentElement || icon);
            });
        }

        function getInitials(name) {
            if (!name) return '?';
            return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
        }

        function renderDefaultGroupAvatar(
            participants,
            size = 'large'
        ) {

            // =================================================
            // GET VALID GROUP MEMBERS
            // =================================================

            const members =
                Array.isArray(participants)
                    ? participants
                        .map(participant =>
                            participant?.user || participant
                        )
                        .filter(user =>
                            user &&
                            (
                                user.user_id ||
                                user.user_full_name ||
                                user.name
                            )
                        )
                        .slice(0, 3)
                    : [];


            // =================================================
            // SIZE PRESETS
            //
            // small:
            // Conversation list + chat header
            //
            // large:
            // First glance + conversation info
            // =================================================

            const isLarge =
                size === 'large';

            const containerClass =
                isLarge
                    ? 'h-[88px] w-[112px]'
                    : 'h-[44px] w-[56px]';

            const circleClass =
                isLarge
                    ? 'h-14 w-14'
                    : 'h-7 w-7';

            const textClass =
                isLarge
                    ? 'text-sm'
                    : 'text-[8px]';

            const borderClass =
                isLarge
                    ? 'border-[3px]'
                    : 'border-2';


            // =================================================
            // MEMBER AVATAR
            // =================================================

            function memberAvatar(user, positionClass) {

                const name =
                    user?.user_full_name ||
                    user?.name ||
                    'User';

                const picture =
                    getConversationInfoPictureUrl(user);

                const initials =
                    getInitials(name);


                // =============================================
                // MEMBER HAS PROFILE PICTURE
                // =============================================

                if (picture) {

                    return `
                        <div
                            class="
                                absolute
                                ${positionClass}
                                ${circleClass}
                                ${borderClass}
                                overflow-hidden
                                rounded-full
                                border-white
                                bg-gray-100
                            "
                        >
                            <img
                                src="${escapeHtml(picture)}"
                                alt="${escapeHtml(name)}"
                                class="
                                    h-full
                                    w-full
                                    object-cover
                                "
                                onerror="
                                    this.style.display='none';
                                    this.nextElementSibling.style.display='flex';
                                "
                            >

                            <div
                                style="display:none;"
                                class="
                                    h-full
                                    w-full
                                    items-center
                                    justify-center
                                    bg-gray-100
                                    ${textClass}
                                    font-semibold
                                    text-gray-600
                                "
                            >
                                ${escapeHtml(initials)}
                            </div>
                        </div>
                    `;
                }


                // =============================================
                // NO MEMBER PROFILE PICTURE
                // =============================================

                return `
                    <div
                        class="
                            absolute
                            ${positionClass}
                            ${circleClass}
                            ${borderClass}
                            flex
                            items-center
                            justify-center
                            rounded-full
                            border-white
                            bg-gray-100
                            ${textClass}
                            font-semibold
                            text-gray-600
                        "
                    >
                        ${escapeHtml(initials)}
                    </div>
                `;
            }


            // =================================================
            // FALLBACK
            // =================================================

            if (members.length === 0) {

                return `
                    <div
                        class="
                            ${containerClass}
                            relative
                            shrink-0
                        "
                    >
                        <div
                            class="
                                absolute
                                left-1/2
                                top-1/2
                                ${circleClass}
                                flex
                                -translate-x-1/2
                                -translate-y-1/2
                                items-center
                                justify-center
                                rounded-full
                                bg-gray-100
                                text-gray-500
                            "
                        >
                            <i
                                data-lucide="users"
                                class="${
                                    isLarge
                                        ? 'h-6 w-6'
                                        : 'h-3.5 w-3.5'
                                }"
                            ></i>
                        </div>
                    </div>
                `;
            }


            // =================================================
            // 1 MEMBER
            // =================================================

            if (members.length === 1) {

                return `
                    <div
                        class="
                            ${containerClass}
                            relative
                            shrink-0
                        "
                    >
                        ${memberAvatar(
                            members[0],
                            `
                                left-1/2
                                top-1/2
                                z-10
                                -translate-x-1/2
                                -translate-y-1/2
                            `
                        )}
                    </div>
                `;
            }


            // =================================================
            // 2 MEMBERS
            // =================================================

            if (members.length === 2) {

                return `
                    <div
                        class="
                            ${containerClass}
                            relative
                            shrink-0
                        "
                    >
                        ${memberAvatar(
                            members[0],
                            `
                                left-[8%]
                                bottom-[5%]
                                z-10
                            `
                        )}

                        ${memberAvatar(
                            members[1],
                            `
                                right-[8%]
                                top-[5%]
                                z-20
                            `
                        )}
                    </div>
                `;
            }


            // =================================================
            // 3 MEMBERS
            //
            // Exact same arrangement everywhere:
            //
            //            [ 2 ]
            //
            //      [ 1 ]     [ 3 ]
            //
            // =================================================

            return `
                <div
                    class="
                        ${containerClass}
                        relative
                        shrink-0
                    "
                >
                    ${memberAvatar(
                        members[0],
                        `
                            left-[3%]
                            bottom-[3%]
                            z-10
                        `
                    )}

                    ${memberAvatar(
                        members[1],
                        `
                            left-1/2
                            top-0
                            z-20
                            -translate-x-1/2
                        `
                    )}

                    ${memberAvatar(
                        members[2],
                        `
                            right-[3%]
                            bottom-[3%]
                            z-30
                        `
                    )}
                </div>
            `;
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

        function refreshMessageRelativeTimes() {
            document
                .querySelectorAll('.message-status-time')
                .forEach(element => {
                    element.textContent = formatMessageRelativeTime(
                        element.dataset.createdAt
                    );
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
                return '';
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
            } else if (['xls', 'xlsx', 'csv'].includes(ext)) {
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

        // =====================================================
        // CONVERSATION INFO / MEDIA & FILES
        //
        // This uses the attachments already stored in messages.
        // It loads every message page for the opened conversation,
        // collects images and files, then gives the user one place
        // to browse the complete attachment history.
        // =====================================================

        // =====================================================
        // RIGHT SIDEBAR SEARCH
        //
        // Search was moved into Conversation Info.
        // Results are shown in the right sidebar while the chat
        // remains visible on the left, similar to Messenger.
        // =====================================================

        function escapeConversationSearchRegex(value) {
            return String(value || '')
                .replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }


        function highlightConversationSearchText(text, query) {

            const safeText = escapeHtml(String(text || ''));
            const normalizedQuery = String(query || '').trim();

            if (!normalizedQuery) {
                return safeText;
            }

            const regex = new RegExp(
                `(${escapeConversationSearchRegex(normalizedQuery)})`,
                'ig'
            );

            return safeText.replace(
                regex,
                '<strong class="font-semibold text-gray-900">$1</strong>'
            );
        }


        function getSidebarSearchMessageText(row) {

            if (!row) {
                return '';
            }

            const content =
                String(
                    row.dataset.messageContent || ''
                ).trim();

            const attachmentName =
                String(
                    row.querySelector(
                        '[data-attachment-name]'
                    )?.dataset.attachmentName ||
                    ''
                ).trim();

            if (content) {
                return content;
            }

            if (attachmentName) {
                return attachmentName;
            }

            return 'Attachment';
        }


        function getSidebarSearchSenderName(row) {

            if (!row) {
                return '';
            }

            const isOwn =
                Number(row.dataset.senderId) ===
                Number(currentUserId);

            if (isOwn) {
                return 'You';
            }

            return (
                currentConversationUserName ||
                currentConversationUser?.user_full_name ||
                'User'
            );
        }


        function getSidebarSearchAvatarHtml(row) {

            const isOwn =
                Number(row?.dataset?.senderId) ===
                Number(currentUserId);

            // For your own messages use initials.
            // The other participant uses the same avatar as
            // Conversation Info.
            if (isOwn) {
                return `
                    <div
                        class="
                            flex
                            h-9
                            w-9
                            shrink-0
                            items-center
                            justify-center
                            rounded-full
                            bg-gray-200
                            text-[11px]
                            font-semibold
                            text-gray-600
                        "
                    >
                        You
                    </div>
                `;
            }

            const picture =
                getConversationInfoPictureUrl(
                    currentConversationUser || {}
                );

            if (picture) {
                return `
                    <img
                        src="${escapeHtml(picture)}"
                        alt="${escapeHtml(
                            currentConversationUserName ||
                            'User'
                        )}"
                        class="
                            h-9
                            w-9
                            shrink-0
                            rounded-full
                            object-cover
                        "
                    >
                `;
            }

            return `
                <div
                    class="
                        flex
                        h-9
                        w-9
                        shrink-0
                        items-center
                        justify-center
                        rounded-full
                        bg-gray-200
                        text-[11px]
                        font-semibold
                        text-gray-600
                    "
                >
                    ${escapeHtml(
                        getInitials(
                            currentConversationUserName ||
                            'User'
                        )
                    )}
                </div>
            `;
        }


        function resetConversationSidebarSearch() {

            if (conversationSidebarSearchTimeout) {
                clearTimeout(
                    conversationSidebarSearchTimeout
                );

                conversationSidebarSearchTimeout = null;
            }

            conversationSidebarSearchRequest++;

            const input =
                document.getElementById(
                    'modalConversationSidebarSearchInput'
                );

            const clear =
                document.getElementById(
                    'modalConversationSidebarSearchClear'
                );

            const hint =
                document.getElementById(
                    'modalConversationSidebarSearchHint'
                );

            const loading =
                document.getElementById(
                    'modalConversationSidebarSearchLoading'
                );

            const summary =
                document.getElementById(
                    'modalConversationSidebarSearchSummary'
                );

            const results =
                document.getElementById(
                    'modalConversationSidebarSearchResults'
                );

            if (input) {
                input.value = '';
            }

            clear?.classList.add('hidden');
            clear?.classList.remove('flex');

            hint?.classList.remove('hidden');

            loading?.classList.add('hidden');

            summary?.classList.add('hidden');
            summary?.classList.remove('flex');

            if (results) {
                results.innerHTML = '';
                results.classList.add('hidden');
            }
        }


        function closeConversationSidebarSearchView() {

            const home =
                document.getElementById(
                    'modalConversationInfoHome'
                );

            const searchView =
                document.getElementById(
                    'modalConversationSidebarSearchView'
                );

            searchView?.classList.add('hidden');
            searchView?.classList.remove('flex');

            home?.classList.remove('hidden');

            resetConversationSidebarSearch();
        }


        function openConversationSidebarSearchView() {

            if (!currentConversationId) {
                return;
            }

            const home =
                document.getElementById(
                    'modalConversationInfoHome'
                );

            const assets =
                document.getElementById(
                    'modalConversationAssetsView'
                );

            const searchView =
                document.getElementById(
                    'modalConversationSidebarSearchView'
                );

            const input =
                document.getElementById(
                    'modalConversationSidebarSearchInput'
                );

            // Close Media / Files if it is currently open.
            assets?.classList.add('hidden');
            assets?.classList.remove('flex');

            home?.classList.add('hidden');

            searchView?.classList.remove('hidden');
            searchView?.classList.add('flex');

            resetConversationSidebarSearch();

            lucideCreateIcons();

            requestAnimationFrame(() => {
                input?.focus();
            });
        }


        async function searchConversationFromSidebar(query) {

            const normalizedQuery =
                String(query || '').trim();

            const hint =
                document.getElementById(
                    'modalConversationSidebarSearchHint'
                );

            const loading =
                document.getElementById(
                    'modalConversationSidebarSearchLoading'
                );

            const summary =
                document.getElementById(
                    'modalConversationSidebarSearchSummary'
                );

            const count =
                document.getElementById(
                    'modalConversationSidebarSearchCount'
                );

            const results =
                document.getElementById(
                    'modalConversationSidebarSearchResults'
                );

            if (!results) {
                return;
            }

            if (!normalizedQuery) {

                hint?.classList.remove('hidden');
                loading?.classList.add('hidden');

                summary?.classList.add('hidden');
                summary?.classList.remove('flex');

                results.classList.add('hidden');
                results.innerHTML = '';

                return;
            }

            const requestId =
                ++conversationSidebarSearchRequest;

            hint?.classList.add('hidden');

            loading?.classList.remove('hidden');

            summary?.classList.add('hidden');
            summary?.classList.remove('flex');

            results.classList.add('hidden');
            results.innerHTML = '';

            // =============================================
            // LOAD THE COMPLETE CONVERSATION FIRST
            // =============================================
            await loadAllMessagesForConversationSearch();

            if (
                requestId !==
                conversationSidebarSearchRequest
            ) {
                return;
            }

            const currentInput =
                document.getElementById(
                    'modalConversationSidebarSearchInput'
                );

            if (
                !currentInput ||
                currentInput.value.trim() !==
                    normalizedQuery
            ) {
                return;
            }

            const lowerQuery =
                normalizedQuery.toLowerCase();

            const rows =
                Array.from(
                    document.querySelectorAll(
                        '#modalMessagesContainer .message-row'
                    )
                );

            const matches =
                rows
                    .filter(row => {

                        if (
                            row.dataset.messageUnsent === '1'
                        ) {
                            return false;
                        }

                        const messageText =
                            getSidebarSearchMessageText(row)
                                .toLowerCase();

                        return messageText.includes(
                            lowerQuery
                        );
                    })
                    .reverse();

            loading?.classList.add('hidden');

            summary?.classList.remove('hidden');
            summary?.classList.add('flex');

            results.classList.remove('hidden');

            if (count) {
                count.textContent =
                    `${matches.length > 99 ? '99+' : matches.length} ${
                        matches.length === 1
                            ? 'result'
                            : 'results'
                    }`;
            }

            if (!matches.length) {

                results.innerHTML = `
                    <div
                        class="
                            px-4
                            py-10
                            text-center
                            text-sm
                            text-gray-500
                        "
                    >
                        No messages found for
                        <span class="font-medium text-gray-700">
                            "${escapeHtml(normalizedQuery)}"
                        </span>
                    </div>
                `;

                return;
            }

            results.innerHTML =
                matches
                    .map((row, index) => {

                        const sender =
                            getSidebarSearchSenderName(row);

                        const messageText =
                            getSidebarSearchMessageText(row);

                        const createdAt =
                            row.dataset.createdAt || '';

                        const relativeTime =
                            createdAt
                                ? formatMessageRelativeTime(
                                    createdAt
                                )
                                : '';

                        const messageId =
                            row.dataset.messageId || '';

                        return `
                            <button
                                type="button"
                                data-sidebar-search-message-id="${escapeHtml(
                                    messageId
                                )}"
                                class="
                                    flex
                                    w-full
                                    items-start
                                    gap-3
                                    rounded-xl
                                    px-3
                                    py-3
                                    text-left
                                    transition
                                    hover:bg-gray-100
                                "
                            >
                                ${getSidebarSearchAvatarHtml(row)}

                                <div class="min-w-0 flex-1">
                                    <div
                                        class="
                                            flex
                                            min-w-0
                                            items-baseline
                                            gap-1.5
                                        "
                                    >
                                        <p
                                            class="
                                                truncate
                                                text-sm
                                                font-semibold
                                                text-gray-900
                                            "
                                        >
                                            ${escapeHtml(sender)}
                                        </p>

                                        ${
                                            relativeTime
                                                ? `
                                                    <span
                                                        class="
                                                            shrink-0
                                                            text-xs
                                                            text-gray-400
                                                        "
                                                    >
                                                        · ${escapeHtml(relativeTime)}
                                                    </span>
                                                `
                                                : ''
                                        }
                                    </div>

                                    <p
                                        class="
                                            mt-0.5
                                            line-clamp-2
                                            text-xs
                                            leading-5
                                            text-gray-500
                                        "
                                    >
                                        ${highlightConversationSearchText(
                                            messageText,
                                            normalizedQuery
                                        )}
                                    </p>
                                </div>
                            </button>
                        `;
                    })
                    .join('');
        }


        function queueConversationSidebarSearch() {

            const input =
                document.getElementById(
                    'modalConversationSidebarSearchInput'
                );

            const clear =
                document.getElementById(
                    'modalConversationSidebarSearchClear'
                );

            if (!input) {
                return;
            }

            const query =
                input.value.trim();

            clear?.classList.toggle(
                'hidden',
                !query
            );

            clear?.classList.toggle(
                'flex',
                Boolean(query)
            );

            if (conversationSidebarSearchTimeout) {
                clearTimeout(
                    conversationSidebarSearchTimeout
                );
            }

            if (!query) {
                searchConversationFromSidebar('');
                return;
            }

            conversationSidebarSearchTimeout =
                setTimeout(
                    () =>
                        searchConversationFromSidebar(
                            query
                        ),
                    300
                );
        }


        function jumpToSidebarSearchMessage(messageId) {

            if (!messageId) {
                return;
            }

            const target =
                document.querySelector(
                    `#modalMessagesContainer .message-row[data-message-id="${CSS.escape(
                        String(messageId)
                    )}"]`
                );

            if (!target) {
                return;
            }

            document
                .querySelectorAll(
                    '#modalMessagesContainer .sidebar-search-selected'
                )
                .forEach(row => {
                    row.classList.remove(
                        'sidebar-search-selected'
                    );
                });

            // =====================================================
            // BORDER ONLY THE ACTUAL MESSAGE BUBBLE
            //
            // Do not highlight the full message row.
            // This matches Messenger more closely.
            // =====================================================
            const bubble =
                target.querySelector(
                    '.message-bubble'
                );

            const highlightTarget =
                bubble || target;

            highlightTarget.classList.add(
                'sidebar-search-selected',
                'ring-2',
                'ring-gray-400',
                'ring-offset-1'
            );

            target.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            setTimeout(() => {
                highlightTarget.classList.remove(
                    'sidebar-search-selected',
                    'ring-2',
                    'ring-gray-400',
                    'ring-offset-1'
                );
            }, 2200);
        }


        function getConversationInfoPictureUrl(user) {

            const picture =
                user?.user_profile_picture ||
                user?.profile_picture ||
                '';

            if (!picture) {
                return '';
            }

            let src = String(picture);

            if (
                !/^https?:\/\//i.test(src) &&
                !src.startsWith('/')
            ) {
                src =
                    `/storage/${src.replace(/^storage\//, '')}`;
            }

            return src;
        }



        // =====================================================
        // FULL GROUP PICTURE VIEWER
        //
        // IMPORTANT:
        // Only a real conversation_image can open this.
        // The generated member collage is intentionally ignored.
        // =====================================================

        function normalizeConversationImageUrl(image) {

            if (!image) {
                return '';
            }

            let imageUrl = String(image);

            if (
                !/^https?:\/\//i.test(imageUrl) &&
                !imageUrl.startsWith('/')
            ) {
                imageUrl =
                    `/storage/${imageUrl.replace(
                        /^storage\//,
                        ''
                    )}`;
            }

            return imageUrl;
        }


        function openGroupPictureViewer(imageUrl) {

            if (!imageUrl) {
                return;
            }

            const viewer =
                document.getElementById(
                    'modalGroupPictureViewer'
                );

            const viewerImage =
                document.getElementById(
                    'modalGroupPictureViewerImage'
                );

            if (!viewer || !viewerImage) {
                return;
            }

            viewerImage.src = imageUrl;

            viewer.classList.remove('hidden');
            viewer.classList.add('flex');
            viewer.setAttribute('aria-hidden', 'false');
        }


        function closeGroupPictureViewer() {

            const viewer =
                document.getElementById(
                    'modalGroupPictureViewer'
                );

            const viewerImage =
                document.getElementById(
                    'modalGroupPictureViewerImage'
                );

            if (!viewer) {
                return;
            }

            viewer.classList.add('hidden');
            viewer.classList.remove('flex');
            viewer.setAttribute('aria-hidden', 'true');

            if (viewerImage) {
                viewerImage.src = '';
            }
        }


        // =====================================================
        // VIEWER EVENTS
        // =====================================================

        document
            .getElementById('modalGroupPictureViewerClose')
            ?.addEventListener(
                'click',
                closeGroupPictureViewer
            );


        document
            .getElementById('modalGroupPictureViewer')
            ?.addEventListener(
                'click',
                (event) => {

                    if (
                        event.target.id ===
                        'modalGroupPictureViewer'
                    ) {
                        closeGroupPictureViewer();
                    }
                }
            );


        document.addEventListener(
            'keydown',
            (event) => {

                if (event.key === 'Escape') {
                    closeGroupPictureViewer();
                }
            }
        );


        // =====================================================
        // REFRESH CONVERSATION INFO PROFILE
        // Supports direct and group conversations
        // =====================================================

        function refreshConversationInfoProfile() {

            const conversation =
                currentConversationData || {};

            const avatar =
                document.getElementById(
                    'modalConversationInfoAvatar'
                );

            const name =
                document.getElementById(
                    'modalConversationInfoName'
                );

            const status =
                document.getElementById(
                    'modalConversationInfoStatus'
                );

            const editImageButton =
                document.getElementById(
                    'modalEditGroupImageButton'
                );

            const editNameButton =
                document.getElementById(
                    'modalEditGroupNameButton'
                );

            const participants =
                Array.isArray(conversation.participants)
                    ? conversation.participants
                    : [];


            // =====================================================
            // GROUP CHAT
            // =====================================================

            if (currentConversationType === 'group') {

                const displayName =
                    conversation.conversation_name ||
                    'Group chat';

                const memberCount =
                    participants.length;

                const groupImage =
                    conversation.conversation_image || '';


                // =================================================
                // SHOW EDIT BUTTONS
                // Any current group member can use them
                // =================================================

                if (editImageButton) {

                    editImageButton.classList.remove(
                        'hidden'
                    );

                    editImageButton.classList.add(
                        'flex'
                    );
                }


                if (editNameButton) {

                    editNameButton.classList.remove(
                        'hidden'
                    );

                    editNameButton.classList.add(
                        'flex'
                    );
                }


                // =================================================
                // GROUP NAME
                // =================================================

                if (name) {

                    name.textContent =
                        displayName;
                }


                // =================================================
                // MEMBER COUNT
                // =================================================

                if (status) {

                    status.textContent =
                        `${memberCount} ${
                            memberCount === 1
                                ? 'member'
                                : 'members'
                        }`;
                }


                if (!avatar) {
                    return;
                }


                // =================================================
                // CUSTOM GROUP PICTURE
                // =================================================

                if (groupImage) {

                    let imageUrl =
                        String(groupImage);


                    if (
                        !/^https?:\/\//i.test(imageUrl) &&
                        !imageUrl.startsWith('/')
                    ) {

                        imageUrl =
                            `/storage/${imageUrl.replace(
                                /^storage\//,
                                ''
                            )}`;
                    }


                    avatar.className = `
                        flex
                        h-20
                        w-20
                        cursor-pointer
                        items-center
                        justify-center
                        overflow-hidden
                        rounded-full
                        bg-gray-100
                    `;

                    // =============================================
                    // REAL CUSTOM PICTURE
                    // Click avatar to view the full image.
                    // =============================================

                    avatar.setAttribute(
                        'role',
                        'button'
                    );

                    avatar.setAttribute(
                        'tabindex',
                        '0'
                    );

                    avatar.setAttribute(
                        'title',
                        'View group picture'
                    );

                    avatar.onclick = () => {
                        openGroupPictureViewer(imageUrl);
                    };

                    avatar.onkeydown = (event) => {

                        if (
                            event.key === 'Enter' ||
                            event.key === ' '
                        ) {
                            event.preventDefault();
                            openGroupPictureViewer(imageUrl);
                        }
                    };


                    avatar.innerHTML = `
                        <img
                            src="${escapeHtml(imageUrl)}"
                            alt="${escapeHtml(displayName)}"
                            class="h-full w-full object-cover"
                        >
                    `;

                } else {

                    // =============================================
                    // NO CUSTOM PICTURE
                    // Keep member collage
                    // =============================================

                    avatar.className = `
                        flex
                        h-[92px]
                        w-[112px]
                        items-center
                        justify-center
                        overflow-visible
                    `;

                    // =============================================
                    // GENERATED MEMBER COLLAGE
                    // This is NOT a saved group picture.
                    // Therefore it must not open the image viewer.
                    // =============================================

                    avatar.removeAttribute('role');
                    avatar.removeAttribute('tabindex');
                    avatar.removeAttribute('title');
                    avatar.onclick = null;
                    avatar.onkeydown = null;

                    avatar.innerHTML =
                        renderDefaultGroupAvatar(
                            participants,
                            'large'
                        );
                }


                return;
            }


            // =====================================================
            // DIRECT CHAT
            // =====================================================

            if (editImageButton) {

                editImageButton.classList.add(
                    'hidden'
                );

                editImageButton.classList.remove(
                    'flex'
                );
            }


            if (editNameButton) {

                editNameButton.classList.add(
                    'hidden'
                );

                editNameButton.classList.remove(
                    'flex'
                );
            }


            const user =
                currentConversationUser || {};

            const displayName =
                currentConversationUserName ||
                user.user_full_name ||
                'Conversation';


            if (name) {

                name.textContent =
                    displayName;
            }


            if (status) {

                status.textContent =
                    formatUserActivity(
                        user.last_active_at
                    );
            }


            if (!avatar) {
                return;
            }


            avatar.className = `
                flex
                h-20
                w-20
                items-center
                justify-center
                overflow-hidden
                rounded-full
                bg-gray-200
                text-xl
                font-semibold
                text-gray-600
            `;


            const picture =
                getConversationInfoPictureUrl(
                    user
                );


            if (picture) {

                avatar.innerHTML = `
                    <img
                        src="${escapeHtml(picture)}"
                        alt="${escapeHtml(displayName)}"
                        class="h-full w-full object-cover"
                    >
                `;

            } else {

                avatar.innerHTML = '';

                avatar.textContent =
                    getInitials(
                        displayName
                    );
            }
        }

        const modalEditGroupImageButton =
            document.getElementById(
                'modalEditGroupImageButton'
            );

        const modalGroupImageInput =
            document.getElementById(
                'modalGroupImageInput'
            );


        if (
            modalEditGroupImageButton &&
            modalGroupImageInput
        ) {

            // =================================================
            // CLICK PENCIL
            // Open Windows File Explorer
            // =================================================

            modalEditGroupImageButton.addEventListener(
                'click',
                (event) => {

                    // =============================================
                    // DO NOT TRIGGER THE FULL PICTURE VIEWER
                    // The pencil is only for changing the picture.
                    // =============================================

                    event.preventDefault();
                    event.stopPropagation();

                    if (
                        currentConversationType !==
                        'group'
                    ) {
                        return;
                    }

                    modalGroupImageInput.click();
                }
            );


            // =================================================
            // USER SELECTED IMAGE
            // =================================================

            modalGroupImageInput.addEventListener(
                'change',
                async () => {

                    const file =
                        modalGroupImageInput.files?.[0];

                    if (
                        !file ||
                        !currentConversationId
                    ) {
                        return;
                    }


                    // =============================================
                    // IMAGE TYPES ONLY
                    // =============================================

                    if (!file.type.startsWith('image/')) {

                        showMessageToast?.(
                            'Please select an image.',
                            'error'
                        );

                        modalGroupImageInput.value = '';

                        return;
                    }


                    // =============================================
                    // BUILD UPLOAD
                    // =============================================

                    const formData =
                        new FormData();

                    formData.append(
                        'conversation_image',
                        file
                    );


                    modalEditGroupImageButton.disabled =
                        true;


                    try {

                        const response =
                            await fetch(
                                `/messages/conversations/${currentConversationId}/image`,
                                {
                                    method: 'POST',

                                    headers: {
                                        'X-CSRF-TOKEN':
                                            document
                                                .querySelector(
                                                    'meta[name="csrf-token"]'
                                                )
                                                ?.getAttribute(
                                                    'content'
                                                ) || '',

                                        'Accept':
                                            'application/json',
                                    },

                                    body: formData,
                                }
                            );


                        const data =
                            await response.json();


                        if (!response.ok) {

                            throw new Error(
                                data.message ||
                                'Unable to update group picture.'
                            );
                        }


                        // =============================================
                        // UPDATE CURRENT CONVERSATION DATA
                        // =============================================

                        if (currentConversationData) {

                            currentConversationData
                                .conversation_image =
                                data.conversation_image ||
                                data.conversation_image_url ||
                                '';
                        }


                        // =============================================
                        // UPDATE CONVERSATION INFO IMMEDIATELY
                        // =============================================

                        refreshConversationInfoProfile();


                        // =============================================
                        // UPDATE HEADER + LEFT CONVERSATION LIST
                        //
                        // Reloads UI data only.
                        // This does NOT refresh the browser page.
                        // =============================================

                        await loadModalConversations();


                        // =============================================
                        // RELOAD CURRENT CHAT DATA
                        // So header gets the new image too.
                        // =============================================

                        await openModalConversation(
                            currentConversationId
                        );


                        showMessageToast?.(
                            'Group picture updated.',
                            'success'
                        );

                    } catch (error) {

                        console.error(
                            'Group image update failed:',
                            error
                        );

                        showMessageToast?.(
                            error.message ||
                            'Unable to update group picture.',
                            'error'
                        );

                    } finally {

                        modalEditGroupImageButton.disabled =
                            false;

                        // Allows selecting the same image again later.
                        modalGroupImageInput.value = '';
                    }
                }
            );
        }

        const modalEditGroupNameButton =
            document.getElementById(
                'modalEditGroupNameButton'
            );


        if (modalEditGroupNameButton) {

            modalEditGroupNameButton.addEventListener(
                'click',
                () => {

                    if (
                        currentConversationType !==
                        'group'
                    ) {
                        return;
                    }


                    // =============================================
                    // USE YOUR EXISTING RENAME GROUP MODAL
                    // =============================================

                    openRenameGroupModal();
                }
            );
        }


        function resetConversationAssets() {

            conversationAssets = [];
            conversationAssetsLoadedFor = null;
            conversationAssetsActiveTab = 'media';
            conversationAssetsLoading = false;

            const mediaGrid =
                document.getElementById(
                    'modalConversationMediaGrid'
                );

            const filesList =
                document.getElementById(
                    'modalConversationFilesList'
                );

            if (mediaGrid) {
                mediaGrid.innerHTML = '';
            }

            if (filesList) {
                filesList.innerHTML = '';
            }

            updateConversationAssetCounts();
        }


        function updateConversationAssetCounts() {

            const mediaCount =
                conversationAssets.filter(
                    item => item.kind === 'media'
                ).length;

            const fileCount =
                conversationAssets.filter(
                    item => item.kind === 'file'
                ).length;

            const mediaCountElement =
                document.getElementById(
                    'modalConversationMediaCount'
                );

            const fileCountElement =
                document.getElementById(
                    'modalConversationFileCount'
                );

            if (mediaCountElement) {
                mediaCountElement.textContent =
                    String(mediaCount);
            }

            if (fileCountElement) {
                fileCountElement.textContent =
                    String(fileCount);
            }
        }


        function collectConversationAssetsFromMessage(
            msg,
            seen
        ) {

            if (!msg || msg.is_unsent) {
                return;
            }

            const rawAttachments =
                Array.isArray(msg.attachments)
                    ? msg.attachments
                    : (
                        msg.attachment
                            ? [msg.attachment]
                            : []
                    );

            const attachments =
                normalizeAttachments(rawAttachments);

            attachments.forEach(attachment => {

                const url =
                    String(
                        attachment.url || ''
                    );

                if (!url) {
                    return;
                }

                const key =
                    String(
                        attachment.attachment_id ||
                        attachment.id ||
                        `${msg.message_id || ''}::${url}`
                    );

                if (seen.has(key)) {
                    return;
                }

                seen.add(key);

                conversationAssets.push({
                    key,
                    messageId:
                        msg.message_id || null,
                    createdAt:
                        msg.created_at || null,
                    senderName:
                        msg.sender?.user_full_name ||
                        msg.sender?.name ||
                        '',
                    kind:
                        isImageAttachment(attachment)
                            ? 'media'
                            : 'file',
                    attachment
                });
            });
        }


        async function loadConversationAssets(
            force = false
        ) {

            if (
                !currentConversationId ||
                conversationAssetsLoading
            ) {
                return;
            }

            if (
                !force &&
                Number(conversationAssetsLoadedFor) ===
                    Number(currentConversationId)
            ) {
                renderConversationAssets();
                return;
            }

            conversationAssetsLoading = true;

            const loading =
                document.getElementById(
                    'modalConversationAssetsLoading'
                );

            loading?.classList.remove('hidden');

            conversationAssets = [];

            try {

                // =============================================
                // LOAD EVERY MESSAGE PAGE DIRECTLY
                //
                // This does not depend on what is currently
                // visible in the chat window.
                // =============================================

                let page = 1;
                let keepLoading = true;
                const seen = new Set();

                while (keepLoading) {

                    const response =
                        await fetch(
                            `/messages/conversations/${currentConversationId}/messages?page=${page}`,
                            {
                                headers: {
                                    'Accept':
                                        'application/json'
                                }
                            }
                        );

                    if (!response.ok) {
                        break;
                    }

                    const payload =
                        await response.json();

                    const paginator =
                        payload.data || {};

                    const messages =
                        Array.isArray(paginator.data)
                            ? paginator.data
                            : [];

                    messages.forEach(msg => {
                        collectConversationAssetsFromMessage(
                            msg,
                            seen
                        );
                    });

                    if (!messages.length) {
                        break;
                    }

                    const currentPage =
                        Number(
                            paginator.current_page ||
                            page
                        );

                    const lastPage =
                        Number(
                            paginator.last_page ||
                            currentPage
                        );

                    if (
                        currentPage >= lastPage ||
                        !paginator.next_page_url
                    ) {
                        keepLoading = false;
                    } else {
                        page++;
                    }
                }

                // Newest attachments first.
                conversationAssets.sort(
                    (a, b) =>
                        new Date(b.createdAt || 0) -
                        new Date(a.createdAt || 0)
                );

                conversationAssetsLoadedFor =
                    currentConversationId;

                updateConversationAssetCounts();
                renderConversationAssets();

            } catch (error) {

                console.error(
                    'Unable to load conversation media and files:',
                    error
                );

            } finally {

                conversationAssetsLoading = false;
                loading?.classList.add('hidden');
            }
        }

        document
            .getElementById('privateCallCloseButton')
            .addEventListener('click', () => {

                cleanupPrivateCall();

            });

        document
            .getElementById('privateCallRedialButton')
            .addEventListener('click', () => {

                cleanupPrivateCall();

                if (privateCallType === 'video') {

                    startPrivateVideoCall();

                } else {

                    startPrivateAudioCall();

                }

            });


        function renderConversationAssets() {

            const mediaGrid =
                document.getElementById(
                    'modalConversationMediaGrid'
                );

            const filesList =
                document.getElementById(
                    'modalConversationFilesList'
                );

            const empty =
                document.getElementById(
                    'modalConversationAssetsEmpty'
                );

            const emptyTitle =
                document.getElementById(
                    'modalConversationAssetsEmptyTitle'
                );

            if (!mediaGrid || !filesList) {
                return;
            }

            const isMedia =
                conversationAssetsActiveTab === 'media';

            const records =
                conversationAssets.filter(
                    item =>
                        item.kind ===
                        conversationAssetsActiveTab
                );

            mediaGrid.classList.toggle(
                'hidden',
                !isMedia
            );

            filesList.classList.toggle(
                'hidden',
                isMedia
            );

            if (empty) {
                empty.classList.toggle(
                    'hidden',
                    records.length > 0
                );
            }

            if (emptyTitle) {
                emptyTitle.textContent =
                    isMedia
                        ? 'No media yet'
                        : 'No files yet';
            }

            if (isMedia) {

                mediaGrid.innerHTML =
                    records
                        .map((item, index) => {

                            const attachment =
                                item.attachment;

                            return `
                                <button
                                    type="button"
                                    class="
                                        group
                                        relative
                                        aspect-square
                                        overflow-hidden
                                        rounded-md
                                        bg-gray-100
                                    "
                                    data-conversation-asset-index="${index}"
                                    data-tooltip="${escapeHtml(
                                        attachment.name ||
                                        'Image'
                                    )}"
                                >
                                    <img
                                        src="${escapeHtml(
                                            attachment.url || ''
                                        )}"
                                        alt="${escapeHtml(
                                            attachment.name ||
                                            'Image'
                                        )}"
                                        class="
                                            h-full
                                            w-full
                                            object-cover
                                            transition
                                            duration-200
                                            group-hover:scale-105
                                        "
                                        loading="eager"
                                    >
                                </button>
                            `;
                        })
                        .join('');

            } else {

                filesList.innerHTML =
                    records
                        .map(item => {

                            const attachment =
                                item.attachment;

                            const fileName =
                                attachment.name ||
                                'File';

                            const fileSize =
                                formatFileSize(
                                    Number(
                                        attachment.size || 0
                                    )
                                );

                            const extension =
                                String(
                                    attachment.extension ||
                                    fileName.split('.').pop() ||
                                    ''
                                ).toUpperCase();

                            return `
                                <a
                                    href="${escapeHtml(
                                        attachment.url || '#'
                                    )}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    download="${escapeHtml(
                                        fileName
                                    )}"
                                    class="
                                        flex
                                        items-center
                                        gap-3
                                        rounded-xl
                                        px-3
                                        py-3
                                        transition
                                        hover:bg-gray-50
                                    "
                                >
                                    <div
                                        class="
                                            flex
                                            h-10
                                            w-10
                                            shrink-0
                                            items-center
                                            justify-center
                                            rounded-lg
                                            bg-gray-100
                                            text-gray-600
                                        "
                                    >
                                        <i
                                            data-lucide="file-text"
                                            class="h-5 w-5"
                                        ></i>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="
                                                truncate
                                                text-sm
                                                font-medium
                                                text-gray-900
                                            "
                                        >
                                            ${escapeHtml(fileName)}
                                        </p>

                                        <p
                                            class="
                                                mt-0.5
                                                text-xs
                                                text-gray-500
                                            "
                                        >
                                            ${escapeHtml(
                                                [
                                                    extension,
                                                    fileSize
                                                ]
                                                .filter(Boolean)
                                                .join(' · ')
                                            )}
                                        </p>
                                    </div>

                                    <i
                                        data-lucide="download"
                                        class="
                                            h-4
                                            w-4
                                            shrink-0
                                            text-gray-400
                                        "
                                    ></i>
                                </a>
                            `;
                        })
                        .join('');
            }

            lucideCreateIcons();
        }


        function setConversationAssetsTab(tab) {

            conversationAssetsActiveTab =
                tab === 'file'
                    ? 'file'
                    : 'media';

            const mediaTab =
                document.getElementById(
                    'modalConversationMediaTab'
                );

            const filesTab =
                document.getElementById(
                    'modalConversationFilesTab'
                );

            const mediaActive =
                conversationAssetsActiveTab === 'media';

            mediaTab?.classList.toggle(
                'border-gray-900',
                mediaActive
            );

            mediaTab?.classList.toggle(
                'text-gray-900',
                mediaActive
            );

            mediaTab?.classList.toggle(
                'border-transparent',
                !mediaActive
            );

            mediaTab?.classList.toggle(
                'text-gray-500',
                !mediaActive
            );

            filesTab?.classList.toggle(
                'border-gray-900',
                !mediaActive
            );

            filesTab?.classList.toggle(
                'text-gray-900',
                !mediaActive
            );

            filesTab?.classList.toggle(
                'border-transparent',
                mediaActive
            );

            filesTab?.classList.toggle(
                'text-gray-500',
                mediaActive
            );

            renderConversationAssets();
        }


        async function openConversationAssetsView(
            tab = 'media'
        ) {

            const home =
                document.getElementById(
                    'modalConversationInfoHome'
                );

            const assets =
                document.getElementById(
                    'modalConversationAssetsView'
                );

            home?.classList.add('hidden');

            assets?.classList.remove('hidden');
            assets?.classList.add('flex');

            setConversationAssetsTab(tab);

            await loadConversationAssets();
        }


        function closeConversationAssetsView() {

            const home =
                document.getElementById(
                    'modalConversationInfoHome'
                );

            const assets =
                document.getElementById(
                    'modalConversationAssetsView'
                );

            assets?.classList.add('hidden');
            assets?.classList.remove('flex');

            home?.classList.remove('hidden');
        }


        async function openConversationInfoSidebar() {

            if (!currentConversationId) {
                return;
            }

            const sidebar =
                document.getElementById(
                    'modalConversationInfoSidebar'
                );

            if (!sidebar) {
                return;
            }

            refreshConversationInfoProfile();
            refreshGroupConversationSidebar();

            sidebar.classList.remove('hidden');
            sidebar.classList.add('flex');

            document
                .getElementById('messagingSmartCloseButton')
                ?.classList.add('hidden');
            document
                .getElementById('messagingSmartCloseButtonThread')
                ?.classList.add('hidden');

            const chatHeaderNormal =
                document.getElementById('modalChatHeaderNormal');

            chatHeaderNormal?.classList.remove('pr-14');
            chatHeaderNormal?.classList.add('pr-4');

            closeConversationAssetsView();

            lucideCreateIcons();

            // Preload counts so Media and Files immediately show
            // how many records exist in this conversation.
            await Promise.all([
                loadConversationAssets(),
                updatePinnedMessagesCount()
            ]);
        }


        function closeConversationInfoSidebar() {

            const sidebar =
                document.getElementById(
                    'modalConversationInfoSidebar'
                );

            sidebar?.classList.add('hidden');
            sidebar?.classList.remove('flex');

            document
                .getElementById('messagingSmartCloseButton')
                ?.classList.remove('hidden');
            document
                .getElementById('messagingSmartCloseButtonThread')
                ?.classList.remove('hidden');

            const chatHeaderNormal =
                document.getElementById('modalChatHeaderNormal');

            chatHeaderNormal?.classList.remove('pr-4');
            chatHeaderNormal?.classList.add('pr-14');

            closeConversationAssetsView();
            closeConversationSidebarSearchView();
        }


        // =====================================================
        // GROUP CHAT INFO SIDEBAR
        // Mute, members, add people, and leave group
        // =====================================================

        function getCurrentConversationParticipant() {
            const participants =
                Array.isArray(currentConversationData?.participants)
                    ? currentConversationData.participants
                    : [];

            return participants.find(
                participant =>
                    Number(
                        participant.user_id ??
                        participant.user?.user_id
                    ) === Number(currentUserId)
            ) || null;
        }


        function refreshGroupConversationSidebar() {

            const isGroup =
                currentConversationType === 'group';

            const muteButton =
                document.getElementById(
                    'modalConversationMuteButton'
                );

            const membersSection =
                document.getElementById(
                    'modalGroupMembersSection'
                );

            const leaveSection =
                document.getElementById(
                    'modalGroupLeaveSection'
                );

            leaveSection?.classList.toggle(
                'hidden',
                !isGroup
            );

muteButton?.classList.toggle(
                'hidden',
                !isGroup
            );

            muteButton?.classList.toggle(
                'flex',
                isGroup
            );

            membersSection?.classList.toggle(
                'hidden',
                !isGroup
            );
if (!isGroup) {
                return;
            }

            refreshGroupMuteButton();
            renderGroupMembers();
            lucideCreateIcons();
        }


        function refreshGroupMuteButton() {

            const participant =
                getCurrentConversationParticipant();

            const isMuted =
                Boolean(
                    Number(
                        participant?.is_muted || 0
                    )
                );

            const label =
                document.getElementById(
                    'modalConversationMuteLabel'
                );

            const icon =
                document.getElementById(
                    'modalConversationMuteIcon'
                );

            if (label) {
                label.textContent =
                    isMuted
                        ? 'Unmute'
                        : 'Mute';
            }

            if (icon) {
                icon.setAttribute(
                    'data-lucide',
                    isMuted
                        ? 'bell'
                        : 'bell-off'
                );
            }

            lucideCreateIcons();
        }


        function renderGroupMembers() {

            const container =
                document.getElementById(
                    'modalGroupMembersList'
                );

            if (!container) {
                return;
            }

            const participants =
                Array.isArray(
                    currentConversationData?.participants
                )
                    ? currentConversationData.participants
                    : [];

            container.innerHTML =
                participants.map(participant => {

                    const user =
                        participant.user || {};

                    const userId =
                        Number(
                            participant.user_id ??
                            user.user_id
                        );

                    const name =
                        user.user_full_name ||
                        user.name ||
                        'Unknown user';

                    const role =
                        user.role?.role_name ||
                        '';

                    const picture =
                        getConversationInfoPictureUrl(
                            user
                        );

                    const avatar =
                        picture
                            ? `
                                <img
                                    src="${escapeHtml(picture)}"
                                    alt="${escapeHtml(name)}"
                                    class="h-10 w-10 rounded-full object-cover"
                                >
                            `
                            : `
                                <div
                                    class="
                                        flex
                                        h-10
                                        w-10
                                        items-center
                                        justify-center
                                        rounded-full
                                        bg-gray-100
                                        text-xs
                                        font-semibold
                                        text-gray-600
                                    "
                                >
                                    ${escapeHtml(getInitials(name))}
                                </div>
                            `;

                    const isCurrentUser =
                        userId === Number(currentUserId);

                    const memberLabel =
                        isCurrentUser
                            ? `${role || 'Member'} · You`
                            : (role || 'Member');

                    return `
                        <div
                            class="
                                flex
                                items-center
                                gap-3
                                rounded-lg
                                px-2
                                py-2
                                transition
                                hover:bg-gray-50
                            "
                        >
                            <div class="shrink-0">
                                ${avatar}
                            </div>

                            <div class="min-w-0 flex-1">
                                <p
                                    class="
                                        truncate
                                        text-sm
                                        font-semibold
                                        text-gray-900
                                    "
                                >
                                    ${escapeHtml(name)}
                                </p>

                                <p
                                    class="
                                        truncate
                                        text-xs
                                        text-gray-500
                                    "
                                >
                                    ${escapeHtml(memberLabel)}
                                </p>
                            </div>

                            <button
                                type="button"
                                class="
                                    group-member-menu-button
                                    flex
                                    h-9
                                    w-9
                                    shrink-0
                                    items-center
                                    justify-center
                                    rounded-full
                                    text-gray-500
                                    transition
                                    hover:bg-gray-100
                                    hover:text-gray-900
                                "
                                data-user-id="${userId}"
                                data-tooltip="Member options"
                                aria-label="Member options"
                            >
                                <i
                                    data-lucide="ellipsis"
                                    class="h-5 w-5"
                                ></i>
                            </button>
                        </div>
                    `;
                }).join('');

            container
                .querySelectorAll(
                    '.group-member-menu-button'
                )
                .forEach(button => {

                    button.addEventListener(
                        'click',
                        event => {

                            event.stopPropagation();

                            const menu =
                                document.getElementById(
                                    'groupMemberMenu'
                                );

                            const userId =
                                Number(
                                    button.dataset.userId
                                );

                            // =============================================
                            // CHECK IF THIS USER'S MENU IS ALREADY OPEN
                            // =============================================

                            const sameUserIsOpen =
                                menu &&
                                !menu.classList.contains('hidden') &&
                                Number(menu.dataset.userId) === userId;

                            // =============================================
                            // SAME 3 DOTS CLICKED AGAIN
                            // CLOSE THE MENU
                            // =============================================

                            if (sameUserIsOpen) {

                                closeGroupMemberMenu();

                                return;
                            }

                            // =============================================
                            // OTHERWISE OPEN THIS USER'S MENU
                            // =============================================

                            openGroupMemberMenu(
                                button,
                                userId
                            );
                        }
                    );
                });

            lucideCreateIcons();
        }

        function openGroupMuteModal() {

            if (
                !currentConversationId ||
                currentConversationType !== 'group'
            ) {
                return;
            }

            const participant =
                getCurrentConversationParticipant();

            const isMuted =
                Boolean(
                    Number(
                        participant?.is_muted || 0
                    )
                );

            const modal =
                document.getElementById(
                    'groupMuteModal'
                );

            const description =
                document.getElementById(
                    'groupMuteDescription'
                );

            const label =
                document.getElementById(
                    'groupMuteToggleLabel'
                );

            const icon =
                document.getElementById(
                    'groupMuteToggleIcon'
                );

            if (description) {
                description.textContent =
                    isMuted
                        ? 'Notifications are currently muted for this chat.'
                        : 'You are currently receiving notifications for this chat.';
            }

            if (label) {
                label.textContent =
                    isMuted
                        ? 'Unmute'
                        : 'Mute';
            }

            if (icon) {
                icon.setAttribute(
                    'data-lucide',
                    isMuted
                        ? 'bell'
                        : 'bell-off'
                );
            }

            modal?.classList.remove('hidden');
            modal?.classList.add('flex');

            lucideCreateIcons();
        }


        function closeGroupMuteModal() {

            const modal =
                document.getElementById(
                    'groupMuteModal'
                );

            modal?.classList.add('hidden');
            modal?.classList.remove('flex');
        }


        async function saveGroupMuteSetting() {

            if (
                !currentConversationId ||
                currentConversationType !== 'group'
            ) {
                return;
            }

            const participant =
                getCurrentConversationParticipant();

            const currentlyMuted =
                Boolean(
                    Number(
                        participant?.is_muted || 0
                    )
                );

            const selectedActivity =
                document.querySelector(
                    'input[name="groupNotificationActivity"]:checked'
                )?.value || 'all';

            // =====================================================
            // Current database supports a simple muted / unmuted flag.
            // "None" means muted.
            // Other activity choices keep the chat unmuted.
            // =====================================================
            const shouldMute =
                selectedActivity === 'none'
                    ? true
                    : currentlyMuted;

            const action =
                shouldMute
                    ? 'mute'
                    : 'unmute';

            const response =
                await fetch(
                    `/messages/conversations/${currentConversationId}/${action}`,
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

            const data =
                await response.json();

            if (!response.ok) {
                alert(
                    data.message ||
                    'Unable to update mute setting.'
                );
                return;
            }

            if (participant) {
                participant.is_muted =
                    data.is_muted
                        ? 1
                        : 0;
            }

            refreshGroupMuteButton();
            closeGroupMuteModal();
            if (data.is_muted) {
                mutedConversationIds.add(Number(currentConversationId));
            } else {
                mutedConversationIds.delete(Number(currentConversationId));
            }
            updateTopbarMessageBadge();
            scheduleLoadModalConversations();
        }


        async function toggleMuteFromModal() {

            if (
                !currentConversationId ||
                currentConversationType !== 'group'
            ) {
                return;
            }

            const participant =
                getCurrentConversationParticipant();

            const isMuted =
                Boolean(
                    Number(
                        participant?.is_muted || 0
                    )
                );

            const action =
                isMuted
                    ? 'unmute'
                    : 'mute';

            const response =
                await fetch(
                    `/messages/conversations/${currentConversationId}/${action}`,
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

            const data =
                await response.json();

            if (!response.ok) {
                alert(
                    data.message ||
                    'Unable to update mute setting.'
                );
                return;
            }

            if (participant) {
                participant.is_muted =
                    data.is_muted
                        ? 1
                        : 0;
            }

            refreshGroupMuteButton();
            openGroupMuteModal();
            if (data.is_muted) {
                mutedConversationIds.add(Number(currentConversationId));
            } else {
                mutedConversationIds.delete(Number(currentConversationId));
            }
            updateTopbarMessageBadge();
            scheduleLoadModalConversations();
        }


        function getGroupMemberByUserId(userId) {

            const participants =
                Array.isArray(
                    currentConversationData?.participants
                )
                    ? currentConversationData.participants
                    : [];

            return participants.find(
                participant =>
                    Number(
                        participant.user_id ??
                        participant.user?.user_id
                    ) === Number(userId)
            ) || null;
        }


        function closeGroupMemberMenu() {

            const menu =
                document.getElementById(
                    'groupMemberMenu'
                );

            menu?.classList.add('hidden');
            menu?.removeAttribute('data-user-id');
        }


        function openGroupMemberMenu(
            button,
            userId
        ) {

            const menu =
                document.getElementById(
                    'groupMemberMenu'
                );

            if (!menu) {
                return;
            }

            const participant =
                getGroupMemberByUserId(
                    userId
                );

            const user =
                participant?.user || {};

            const name =
                user.user_full_name ||
                user.name ||
                'Member';

            const isCurrentUser =
                Number(userId) ===
                Number(currentUserId);

            if (isCurrentUser) {

                menu.innerHTML = `
                    <button
                        type="button"
                        id="groupMemberViewProfileButton"
                        class="
                            flex
                            w-full
                            items-center
                            gap-3
                            rounded-lg
                            px-3
                            py-2.5
                            text-left
                            text-sm
                            font-semibold
                            text-gray-700
                            transition
                            hover:bg-gray-100
                        "
                    >
                        <i
                            data-lucide="circle-user-round"
                            class="h-5 w-5"
                        ></i>

                        <span>View profile</span>
                    </button>

                    <div class="my-1 border-t border-gray-100"></div>

                    <button
                        type="button"
                        id="groupMemberLeaveButton"
                        class="
                            flex
                            w-full
                            items-center
                            gap-3
                            rounded-lg
                            px-3
                            py-2.5
                            text-left
                            text-sm
                            font-semibold
                            text-gray-700
                            transition
                            hover:bg-gray-100
                        "
                    >
                        <i
                            data-lucide="log-out"
                            class="h-5 w-5"
                        ></i>

                        <span>Leave group</span>
                    </button>
                `;

            } else {

                menu.innerHTML = `
                    <button
                        type="button"
                        class="
                            group-member-action
                            flex
                            w-full
                            items-center
                            gap-3
                            rounded-lg
                            px-3
                            py-2.5
                            text-left
                            text-sm
                            font-semibold
                            text-gray-700
                            transition
                            hover:bg-gray-100
                        "
                        data-action="message"
                        data-user-id="${Number(userId)}"
                    >
                        <i
                            data-lucide="message-circle"
                            class="h-5 w-5"
                        ></i>

                        <span>Message</span>
                    </button>

                    <button
                        type="button"
                        class="
                            group-member-action
                            flex
                            w-full
                            items-center
                            gap-3
                            rounded-lg
                            px-3
                            py-2.5
                            text-left
                            text-sm
                            font-semibold
                            text-gray-700
                            transition
                            hover:bg-gray-100
                        "
                        data-action="audio"
                        data-user-id="${Number(userId)}"
                    >
                        <i
                            data-lucide="phone"
                            class="h-5 w-5"
                        ></i>

                        <span>Audio call</span>
                    </button>

                    <button
                        type="button"
                        class="
                            group-member-action
                            flex
                            w-full
                            items-center
                            gap-3
                            rounded-lg
                            px-3
                            py-2.5
                            text-left
                            text-sm
                            font-semibold
                            text-gray-700
                            transition
                            hover:bg-gray-100
                        "
                        data-action="video"
                        data-user-id="${Number(userId)}"
                    >
                        <i
                            data-lucide="video"
                            class="h-5 w-5"
                        ></i>

                        <span>Video chat</span>
                    </button>
                `;
            }

            const rect =
                button.getBoundingClientRect();

            menu.style.top =
                `${Math.min(
                    rect.bottom + 6,
                    window.innerHeight - 190
                )}px`;

            menu.style.left =
                `${Math.max(
                    8,
                    Math.min(
                        rect.right - 250,
                        window.innerWidth - 258
                    )
                )}px`;

            menu.dataset.userId =
                String(userId);

            menu.classList.remove('hidden');

            document
                .getElementById(
                    'groupMemberViewProfileButton'
                )
                ?.addEventListener(
                    'click',
                    () => {
                        closeGroupMemberMenu();

                        // =====================================================
                        // VIEW PROFILE
                        // Use the current user's profile page.
                        // =====================================================
                        window.location.href = '/profile';
                    }
                );

            document
                .getElementById(
                    'groupMemberLeaveButton'
                )
                ?.addEventListener(
                    'click',
                    () => {
                        closeGroupMemberMenu();
                        openLeaveGroupConfirmModal();
                    }
                );

            menu
                .querySelectorAll(
                    '.group-member-action'
                )
                .forEach(actionButton => {
                    actionButton.addEventListener(
                        'click',
                        () => {
                            const action =
                                actionButton.dataset.action;

                            closeGroupMemberMenu();

                            if (action === 'message') {
                                startDirectConversation(
                                    Number(userId)
                                );
                                return;
                            }

                            // =====================================================
                            // PRIVATE CALL TO THE SELECTED GROUP MEMBER
                            // =====================================================
                            if (action === 'audio' || action === 'video') {
                                const member = (currentConversationData?.participants || [])
                                    .map(participant => participant?.user || {})
                                    .find(user => Number(user.user_id) === Number(userId)) || {};

                                startPrivateCall(
                                    Number(userId),
                                    name,
                                    action === 'video' ? 'video' : 'audio',
                                    privateCallPicture(member),
                                    currentConversationId
                                );
                                return;
                            }
                        }
                    );
                });

            lucideCreateIcons();
        }


        function openLeaveGroupConfirmModal(conversationId = null) {

            closeGroupMemberMenu();
            pendingLeaveConversationId =
                Number(conversationId || currentConversationId || 0) || null;

            const modal =
                document.getElementById(
                    'leaveGroupConfirmModal'
                );

            modal?.classList.remove('hidden');
            modal?.classList.add('flex');

            lucideCreateIcons();
        }


        function closeLeaveGroupConfirmModal() {

            const modal =
                document.getElementById(
                    'leaveGroupConfirmModal'
                );

            modal?.classList.add('hidden');
            modal?.classList.remove('flex');
            pendingLeaveConversationId = null;
        }


        async function confirmLeaveCurrentGroup() {

            const conversationId =
                pendingLeaveConversationId || currentConversationId;

            if (!conversationId) {
                return;
            }

            const response =
                await fetch(
                    `/messages/conversations/${conversationId}/leave`,
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

            const data =
                await response.json();

            if (!response.ok) {
                alert(
                    data.message ||
                    'Unable to leave group.'
                );
                return;
            }

            closeLeaveGroupConfirmModal();
            closeConversationInfoSidebar();

            if (Number(currentConversationId) === Number(conversationId)) {
                currentConversationId = null;
                currentConversationData = null;
                currentConversationType = 'direct';
                currentConversationUser = null;
                currentConversationUserName = '';

                const messagesContainer =
                    document.getElementById(
                        'modalMessagesContainer'
                    );

                const chatHeader =
                    document.getElementById(
                        'modalChatHeader'
                    );

                const composer =
                    document.getElementById(
                        'modalComposer'
                    );

                const chatEmpty =
                    document.getElementById(
                        'modalChatEmptyState'
                    );

                messagesContainer?.classList.add('hidden');
                chatHeader?.classList.add('hidden');
                composer?.classList.add('hidden');
                chatEmpty?.classList.remove('hidden');
                updateScrollToLatestButton();
            }

            await loadModalConversations();
        }

        function toggleGroupMembersAccordion() {

            const content =
                document.getElementById(
                    'modalGroupMembersAccordionContent'
                );

            const chevron =
                document.getElementById(
                    'modalGroupMembersAccordionChevron'
                );

            if (!content) {
                return;
            }

            const willHide =
                !content.classList.contains('hidden');

            content.classList.toggle(
                'hidden',
                willHide
            );

            if (chevron) {
                chevron.setAttribute(
                    'data-lucide',
                    willHide
                        ? 'chevron-down'
                        : 'chevron-up'
                );
            }

            lucideCreateIcons();
        }


        function closeAddGroupPeopleModal() {

            const modal =
                document.getElementById(
                    'addGroupPeopleModal'
                );

            modal?.classList.add('hidden');
            modal?.classList.remove('flex');

            const search =
                document.getElementById(
                    'addGroupPeopleSearch'
                );

            if (search) {
                search.value = '';
            }
        }


        async function openAddGroupPeopleModal() {

            if (
                !currentConversationId ||
                currentConversationType !== 'group'
            ) {
                return;
            }

            const modal =
                document.getElementById(
                    'addGroupPeopleModal'
                );

            modal?.classList.remove('hidden');
            modal?.classList.add('flex');

            await loadAddGroupPeopleUsers();

            lucideCreateIcons();
        }


        async function loadAddGroupPeopleUsers() {

            const list =
                document.getElementById(
                    'addGroupPeopleList'
                );

            const search =
                document.getElementById(
                    'addGroupPeopleSearch'
                )?.value?.trim() || '';

            if (!list) {
                return;
            }

            list.innerHTML = `
                <div class="py-8 text-center text-sm text-gray-500">
                    Loading users...
                </div>
            `;

            const params =
                new URLSearchParams();

            if (search) {
                params.set(
                    'search',
                    search
                );
            }

            const response =
                await fetch(
                    `/messages/users?${params.toString()}`,
                    {
                        headers: {
                            'Accept':
                                'application/json'
                        }
                    }
                );

            const result =
                await response.json();

            if (!response.ok) {
                list.innerHTML = `
                    <div class="py-8 text-center text-sm text-red-600">
                        Unable to load users.
                    </div>
                `;
                return;
            }

            const users =
                Array.isArray(result.data)
                    ? result.data
                    : (
                        Array.isArray(result.data?.data)
                            ? result.data.data
                            : []
                    );

            const existingIds =
                new Set(
                    (
                        currentConversationData
                            ?.participants || []
                    ).map(
                        participant =>
                            Number(
                                participant.user_id ??
                                participant.user?.user_id
                            )
                    )
                );

            const availableUsers =
                users.filter(
                    user =>
                        !existingIds.has(
                            Number(
                                user.user_id
                            )
                        )
                );

            if (availableUsers.length === 0) {
                list.innerHTML = `
                    <div class="py-8 text-center text-sm text-gray-500">
                        No users available to add.
                    </div>
                `;
                return;
            }

            list.innerHTML =
                availableUsers.map(user => {

                    const name =
                        user.name ||
                        user.user_full_name ||
                        'Unknown user';

                    const role =
                        user.role ||
                        user.role_name ||
                        'User';

                    return `
                        <label
                            class="
                                flex
                                cursor-pointer
                                items-center
                                gap-3
                                rounded-xl
                                px-3
                                py-2.5
                                hover:bg-gray-50
                            "
                        >
                            <input
                                type="checkbox"
                                class="
                                    add-group-person-checkbox
                                    h-4
                                    w-4
                                    rounded
                                    border-gray-300
                                "
                                value="${Number(user.user_id)}"
                            >

                            <div
                                class="
                                    flex
                                    h-10
                                    w-10
                                    shrink-0
                                    items-center
                                    justify-center
                                    rounded-full
                                    bg-gray-100
                                    text-xs
                                    font-semibold
                                    text-gray-600
                                "
                            >
                                ${escapeHtml(getInitials(name))}
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-gray-900">
                                    ${escapeHtml(name)}
                                </p>

                                <p class="truncate text-xs text-gray-500">
                                    ${escapeHtml(String(role))}
                                </p>
                            </div>
                        </label>
                    `;
                }).join('');
        }


        async function submitAddGroupPeople() {

            if (!currentConversationId) {
                return;
            }

            const selected =
                Array.from(
                    document.querySelectorAll(
                        '.add-group-person-checkbox:checked'
                    )
                ).map(
                    checkbox =>
                        Number(checkbox.value)
                );

            const error =
                document.getElementById(
                    'addGroupPeopleError'
                );

            if (selected.length === 0) {
                if (error) {
                    error.textContent =
                        'Select at least one person.';
                    error.classList.remove('hidden');
                }
                return;
            }

            error?.classList.add('hidden');

            const response =
                await fetch(
                    `/messages/conversations/${currentConversationId}/members`,
                    {
                        method: 'POST',
                        headers: {
                            'Accept':
                                'application/json',
                            'Content-Type':
                                'application/json',
                            'X-CSRF-TOKEN':
                                csrfToken
                        },
                        body: JSON.stringify({
                            user_ids: selected
                        })
                    }
                );

            const data =
                await response.json();

            if (!response.ok) {
                if (error) {
                    error.textContent =
                        data.message ||
                        'Unable to add people.';
                    error.classList.remove('hidden');
                }
                return;
            }

            currentConversationData =
                data.data;

            refreshConversationInfoProfile();
            refreshGroupConversationSidebar();

            closeAddGroupPeopleModal();
            await loadModalConversations();

            messagesPage = 1;
            hasMoreMessages = true;
            isLoadingMessages = false;

            await loadModalMessages(
                currentConversationId,
                false
            );
        }




        // =====================================================
        // RENAME GROUP MODAL CONTROLS
        // =====================================================
        document.getElementById('renameGroupClose')?.addEventListener('click', closeRenameGroupModal);
        document.getElementById('renameGroupCancel')?.addEventListener('click', closeRenameGroupModal);
        document.getElementById('renameGroupBackdrop')?.addEventListener('click', closeRenameGroupModal);
        document.getElementById('renameGroupSave')?.addEventListener('click', saveRenamedGroup);
        document.getElementById('renameGroupInput')?.addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                event.preventDefault();
                saveRenamedGroup();
            }
        });

        // =====================================================
        // CHAT INFO ACCORDION
        // =====================================================
        function toggleChatInfoAccordion() {

            const content =
                document.getElementById(
                    'modalChatInfoAccordionContent'
                );

            const chevron =
                document.getElementById(
                    'modalChatInfoAccordionChevron'
                );

            if (!content) {
                return;
            }

            const willHide =
                !content.classList.contains('hidden');

            content.classList.toggle(
                'hidden',
                willHide
            );

            if (chevron) {
                chevron.setAttribute(
                    'data-lucide',
                    willHide
                        ? 'chevron-down'
                        : 'chevron-up'
                );

                lucideCreateIcons();
            }
        }


        function toggleMediaFilesAccordion() {

            const content =
                document.getElementById(
                    'modalMediaFilesAccordionContent'
                );

            const chevron =
                document.getElementById(
                    'modalMediaFilesAccordionChevron'
                );

            if (!content) {
                return;
            }

            const willHide =
                !content.classList.contains('hidden');

            content.classList.toggle(
                'hidden',
                willHide
            );

            if (chevron) {
                chevron.setAttribute(
                    'data-lucide',
                    willHide
                        ? 'chevron-down'
                        : 'chevron-up'
                );

                lucideCreateIcons();
            }
        }


        // =====================================================
        // PRIVATE AUDIO / VIDEO CALLING WITH WEBRTC
        // =====================================================
        const privateCallRtcConfig = {
            iceServers: [
                { urls: ['stun:stun.l.google.com:19302', 'stun:stun1.l.google.com:19302'] }
            ]
        };

        function privateCallUuid() {
            return window.crypto?.randomUUID
                ? window.crypto.randomUUID()
                : `call-${Date.now()}-${Math.random().toString(36).slice(2)}`;
        }

        function privateCallPicture(user) {
            const value = user?.user_profile_picture || user?.profile_picture || '';
            if (!value) return '';
            if (/^https?:\/\//i.test(value) || value.startsWith('/')) return value;
            return `/storage/${String(value).replace(/^storage\//, '')}`;
        }

        function privateCallAvatar(element, name, picture) {
            if (!element) return;
            if (picture) {
                element.innerHTML = `<img src="${escapeHtml(picture)}" alt="${escapeHtml(name || 'User')}" class="h-full w-full object-cover">`;
                return;
            }
            element.textContent = String(name || 'User').split(/\s+/).filter(Boolean).slice(0, 2).map(v => v[0]?.toUpperCase()).join('') || 'U';
        }

        async function sendPrivateCallSignal(targetUserId, signalType, payload = {}, overrides = {}) {
            if (!targetUserId) {
                console.error(
                    'sendPrivateCallSignal(): targetUserId is missing.',
                    {
                        targetUserId,
                        signalType,
                        payload,
                        overrides
                    }
                );

                return;
            }
            const response = await fetch('/messages/calls/signal', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    target_user_id: Number(targetUserId),
                    conversation_id: overrides.conversationId ?? privateCallConversationId ?? null,
                    call_id: overrides.callId ?? privateCallId,
                    signal_type: signalType,
                    call_type: overrides.callType ?? privateCallType,
                    payload
                })
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.message || 'Unable to send call signal.');
            return data;
        }

        function showPrivateCallWindow(status = 'Calling...') {
            document.getElementById('privateActiveCallName').textContent = privateCallTargetName || 'Call';
            document.getElementById('privateAudioCallName').textContent = privateCallTargetName || 'Call';
            document.getElementById('privateActiveCallStatus').textContent = status;
            privateCallAvatar(document.getElementById('privateActiveCallAvatar'), privateCallTargetName, privateCallTargetPicture);

            const isVideo = privateCallType === 'video';
            document.getElementById('privateAudioCallVisual')?.classList.toggle('hidden', isVideo);
            document.getElementById('privateRemoteVideo')?.classList.toggle('hidden', !isVideo);
            document.getElementById('privateLocalVideo')?.classList.toggle('hidden', !isVideo);
            const camera = document.getElementById('privateCallCameraButton');
            camera?.classList.toggle('hidden', !isVideo);
            camera?.classList.toggle('flex', isVideo);

            

                const modal =
                    document.getElementById('privateActiveCallModal');

                modal?.classList.remove('hidden');
                modal?.classList.add('flex');

            
        }

        function showIncomingPrivateCall(event) {
            document.getElementById('privateIncomingCallName').textContent = event.from_user_name || 'User';
            document.getElementById('privateIncomingCallType').textContent = event.call_type === 'video' ? 'Incoming video call' : 'Incoming audio call';
            privateCallAvatar(
                document.getElementById('privateIncomingCallAvatar'),
                event.from_user_name || 'User',
                privateCallPicture({ user_profile_picture: event.from_user_picture })
            );
            const modal = document.getElementById('privateIncomingCallModal');
            modal?.classList.remove('hidden');
            modal?.classList.add('flex');
            lucideCreateIcons();
        }

        function hideIncomingPrivateCall() {
            const modal = document.getElementById('privateIncomingCallModal');
            modal?.classList.add('hidden');
            modal?.classList.remove('flex');
        }

        // =====================================================
        // PRIVATE CALL SDP SERIALIZATION FIX
        // Encode SDP before sending so line breaks survive JSON/broadcasting.
        // =====================================================
        function encodePrivateCallDescription(description) {
            if (!description?.type || !description?.sdp) return null;

            return {
                type: String(description.type),
                sdp_base64: btoa(unescape(encodeURIComponent(String(description.sdp))))
            };
        }

        function decodePrivateCallDescription(description) {
            if (!description) return null;

            // New safe format.
            if (description.sdp_base64) {
                return {
                    type: String(description.type || ''),
                    sdp: decodeURIComponent(escape(atob(String(description.sdp_base64))))
                };
            }

            // Backward compatibility with an already-open older browser tab.
            if (description.type && description.sdp) {
                return {
                    type: String(description.type),
                    sdp: String(description.sdp)
                };
            }

            return null;
        }

        // =====================================================
        // PRIVATE CALL MEDIA
        // Video calls no longer fail completely when the webcam
        // is unavailable. PRISM falls back to microphone only.
        // =====================================================
        function updatePrivateCallCameraButton() {
            const button = document.getElementById('privateCallCameraButton');
            const icon = button?.querySelector('i');

            if (icon) {
                icon.setAttribute(
                    'data-lucide',
                    privateCallCameraEnabled ? 'video' : 'video-off'
                );
            }

            button?.classList.toggle('bg-white/15', privateCallCameraEnabled);
            button?.classList.toggle('bg-red-600/80', !privateCallCameraEnabled);
            button?.setAttribute(
                'title',
                privateCallCameraEnabled ? 'Turn camera off' : 'Turn camera on'
            );

            lucideCreateIcons();
        }

        async function privateCallMedia(type) {
            if (!navigator.mediaDevices?.getUserMedia) {
                throw new Error('Microphone/camera access is not supported by this browser.');
            }

            // Audio calls only need the microphone.
            if (type !== 'video') {
                privateCallCameraEnabled = false;

                return navigator.mediaDevices.getUserMedia({
                    audio: true,
                    video: false
                });
            }

            // First try the normal video-call setup.
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    audio: true,
                    video: true
                });

                privateCallCameraEnabled = stream.getVideoTracks().length > 0;
                return stream;
            } catch (videoError) {
                console.warn(
                    'Camera unavailable. Continuing the video call with microphone only.',
                    videoError
                );

                // The camera failed, but that should not kill the call.
                // Retry using only the microphone.
                const audioOnlyStream = await navigator.mediaDevices.getUserMedia({
                    audio: true,
                    video: false
                });

                privateCallCameraEnabled = false;
                return audioOnlyStream;
            }
        }

        function buildPrivateCallPeer(targetUserId) {

            // =====================================================
            // CLOSE ANY PREVIOUS PEER
            // =====================================================

            try {
                privateCallPeer?.close();
            } catch (e) {}

            privateCallPeer = new RTCPeerConnection(privateCallRtcConfig);

            // =====================================================
            // ADD LOCAL TRACKS
            // =====================================================

            privateCallLocalStream?.getTracks().forEach(track => {
                privateCallPeer.addTrack(track, privateCallLocalStream);
            });

            // =====================================================
            // KEEP VIDEO TRANSCEIVER READY
            // Allows enabling the camera later without restarting
            // =====================================================

            if (
                privateCallType === 'video' &&
                !(privateCallLocalStream?.getVideoTracks()?.length)
            ) {
                privateCallPeer.addTransceiver('video', {
                    direction: 'sendrecv'
                });
            }

            // =====================================================
            // REMOTE MEDIA
            // =====================================================

            privateCallPeer.ontrack = event => {

                const remoteVideo = document.getElementById('privateRemoteVideo');

                if (
                    remoteVideo &&
                    event.streams &&
                    event.streams[0]
                ) {
                    remoteVideo.srcObject = event.streams[0];
                    hideRemoteCameraPlaceholder();

                    remoteVideo.play?.().catch(() => {});
                }

                startPrivateCallTimer();

                // ===============================================
                // REMOTE CAMERA / MICROPHONE STOPPED
                // ===============================================

                event.track.addEventListener('ended', () => {

                    console.log(
                        '[Private Call] Remote track ended:',
                        event.track.kind
                    );

                    if (event.track.kind === 'video') {

                        document.getElementById(
                            'privateActiveCallStatus'
                        ).textContent = 'Remote camera turned off';

                    }

                });

            };

            

            // =====================================================
            // ICE CANDIDATES
            // =====================================================

            privateCallPeer.onicecandidate = event => {

                if (!event.candidate) {
                    return;
                }

                sendPrivateCallSignal(
                    targetUserId,
                    'ice_candidate',
                    {
                        candidate: event.candidate.toJSON()
                    }
                ).catch(console.error);

            };

            // =====================================================
            // ICE CONNECTION STATE
            // Handles network interruptions
            // =====================================================

            privateCallPeer.oniceconnectionstatechange = () => {

                const state =
                    privateCallPeer?.iceConnectionState;

                console.log(
                    '[Private Call] ICE:',
                    state
                );

                switch (state) {

                    case 'checking':

                        document.getElementById(
                            'privateActiveCallStatus'
                        ).textContent = 'Connecting...';

                        break;

                    case 'connected':

                    case 'completed':

                        document.getElementById(
                            'privateActiveCallStatus'
                        ).textContent = 'Connected';

                        break;

                    case 'disconnected':

                        document.getElementById(
                            'privateActiveCallStatus'
                        ).textContent = 'Connection lost';

                        break;

                    case 'failed':

                        document.getElementById(
                            'privateActiveCallStatus'
                        ).textContent = 'Connection failed';

                        cleanupPrivateCall();

                        break;

                    case 'closed':

                        cleanupPrivateCall();

                        break;

                }

            };

            // =====================================================
            // OVERALL CONNECTION STATE
            // =====================================================

            privateCallPeer.onconnectionstatechange = () => {

                const state =
                    privateCallPeer?.connectionState;

                console.log(
                    '[Private Call] Connection:',
                    state
                );

                switch (state) {

                    case 'new':

                        break;

                    case 'connecting':

                        document.getElementById(
                            'privateActiveCallStatus'
                        ).textContent = 'Connecting...';

                        break;

                    case 'connected':

                        document.getElementById(
                            'privateActiveCallStatus'
                        ).textContent = 'Connected';

                        break;

                    case 'disconnected':

                        document.getElementById(
                            'privateActiveCallStatus'
                        ).textContent = 'Connection lost';

                        break;

                    case 'failed':

                        document.getElementById(
                            'privateActiveCallStatus'
                        ).textContent = 'Connection failed';

                        cleanupPrivateCall();

                        break;

                    case 'closed':

                        cleanupPrivateCall();

                        break;

                }

            };

        }

        async function addQueuedPrivateIce() {
            if (!privateCallPeer?.remoteDescription) return;
            const queued = [...privateCallPendingIce];
            privateCallPendingIce = [];
            for (const candidate of queued) {
                try { await privateCallPeer.addIceCandidate(new RTCIceCandidate(candidate)); }
                catch (error) { console.error(error); }
            }
        }

        async function startPrivateCall(
            targetUserId,
            targetName,
            type = 'audio',
            targetPicture = '',
            conversationId = null
        ) {

            // =====================================================
            // VALIDATION
            // =====================================================

            if (!targetUserId) {
                return;
            }

            if (privateCallId) {
                alert('A call is already active.');
                return;
            }

            // =====================================================
            // INITIALIZE CALL VARIABLES
            // =====================================================

            privateCallId = privateCallUuid();

            privateCallTargetUserId = Number(targetUserId);
            privateCallTargetName = targetName || 'User';
            privateCallTargetPicture = targetPicture || '';

            document.getElementById(
                'privateRemotePlaceholderName'
            ).textContent =
                privateCallTargetName || 'User';

            privateCallAvatar(
                document.getElementById('privateRemotePlaceholderAvatar'),
                privateCallTargetName,
                privateCallTargetPicture
            );

            privateCallType =
                type === 'video'
                    ? 'video'
                    : 'audio';

            privateCallConversationId =
                conversationId ??
                currentConversationId ??
                null;

            privateCallPendingIce = [];

            try {

                // =================================================
                // GET CAMERA / MICROPHONE
                // =================================================

                privateCallLocalStream =
                    await privateCallMedia(privateCallType);

                const localVideo =
                    document.getElementById(
                        'privateLocalVideo'
                    );

                if (localVideo) {
                    localVideo.srcObject =
                        privateCallLocalStream;
                }

                updatePrivateCallCameraButton();

                // =================================================
                // CREATE PEER CONNECTION
                // =================================================

                buildPrivateCallPeer(
                    privateCallTargetUserId
                );

                // =================================================
                // CREATE SDP OFFER
                // =================================================

                const offer =
                    await privateCallPeer.createOffer();

                await privateCallPeer.setLocalDescription(
                    offer
                );

                // =================================================
                // SHOW CALL WINDOW
                // =================================================

                showPrivateCallWindow(
                    'Calling...'
                );

                // =================================================
                // SEND OFFER
                // =================================================

                await sendPrivateCallSignal(
                    privateCallTargetUserId,
                    'offer',
                    {
                        description:
                            encodePrivateCallDescription(
                                privateCallPeer.localDescription
                            )
                    }
                );

                console.log(
                    '[Private Call] Offer sent.'
                );

            }
            catch (error) {

                console.error(
                    '[Private Call]',
                    error
                );

                alert(
                    error?.message ??
                    'Unable to start the call.'
                );

                cleanupPrivateCall();

            }

        }

        async function acceptPrivateCall() {

            if (
                !privateIncomingOffer ||
                !privateCallTargetUserId
            ) {
                return;
            }

            hideIncomingPrivateCall();

            try {

                // ===============================================
                // GET LOCAL MEDIA
                // ===============================================

                privateCallLocalStream =
                    await privateCallMedia(
                        privateCallType
                    );

                const localVideo =
                    document.getElementById(
                        'privateLocalVideo'
                    );

                if (localVideo) {
                    localVideo.srcObject =
                        privateCallLocalStream;
                }

                updatePrivateCallCameraButton();

                // ===============================================
                // BUILD PEER
                // ===============================================

                buildPrivateCallPeer(
                    privateCallTargetUserId
                );

                // ===============================================
                // APPLY REMOTE OFFER
                // ===============================================

                const decodedOffer =
                    decodePrivateCallDescription(
                        privateIncomingOffer
                    );

                if (!decodedOffer) {
                    throw new Error(
                        'The incoming call offer is invalid.'
                    );
                }

                await privateCallPeer.setRemoteDescription(
                    decodedOffer
                );

                await addQueuedPrivateIce();

                // ===============================================
                // CREATE ANSWER
                // ===============================================

                const answer =
                    await privateCallPeer.createAnswer();

                await privateCallPeer.setLocalDescription(
                    answer
                );

                showPrivateCallWindow(
                    'Connecting...'
                );

                await sendPrivateCallSignal(
                    privateCallTargetUserId,
                    'answer',
                    {
                        description:
                            encodePrivateCallDescription(
                                privateCallPeer.localDescription
                            )
                    }
                );

                console.log(
                    '[Private Call] Answer sent.'
                );

                privateIncomingOffer = null;

            }
            catch (error) {

                console.error(
                    '[Private Call]',
                    error
                );

                alert(
                    error?.message ??
                    'Unable to accept the call.'
                );

                try {

                    await sendPrivateCallSignal(
                        privateCallTargetUserId,
                        'decline'
                    );

                } catch (e) {}

                cleanupPrivateCall();

            }

        }

        async function declinePrivateCall() {
            if (privateCallTargetUserId && privateCallId) await sendPrivateCallSignal(privateCallTargetUserId, 'decline').catch(console.error);
            cleanupPrivateCall();
        }

        async function endPrivateCall() {
            if (privateCallTargetUserId && privateCallId) await sendPrivateCallSignal(privateCallTargetUserId, 'end').catch(console.error);
            cleanupPrivateCall();
        }

        function showPrivateNoAnswerScreen() {

            document.getElementById('privateActiveCallStatus').textContent = 'No Answer';

            document.getElementById('privateCallRedialButton').classList.remove('hidden');

            document.getElementById('privateCallCloseButton').classList.remove('hidden');

            document.getElementById('privateCallMicButton').classList.add('hidden');

            document.getElementById('privateCallCameraButton').classList.add('hidden');

            document.getElementById('privateCallEndButton').classList.add('hidden');

        }

        function cleanupPrivateCall(closeModal = true) {
            stopPrivateCallTimer();

            if (privateCallTimeout) {

                clearTimeout(privateCallTimeout);

                privateCallTimeout = null;

            }

            privateCallAnswered = false;

            // =====================================================
            // PREVENT DOUBLE CLEANUP
            // =====================================================

            if (window.privateCallCleaningUp) {
                return;
            }

            window.privateCallCleaningUp = true;

            try {

                // =================================================
                // HIDE INCOMING CALL UI
                // =================================================

                hideIncomingPrivateCall();

                // =================================================
                // REMOVE PEER EVENTS
                // =================================================

                if (privateCallPeer) {

                    privateCallPeer.ontrack = null;
                    privateCallPeer.onicecandidate = null;
                    privateCallPeer.onconnectionstatechange = null;
                    privateCallPeer.oniceconnectionstatechange = null;

                    try {

                        privateCallPeer.getSenders().forEach(sender => {

                            try {

                                sender.track?.stop();

                            } catch (e) {}

                        });

                    } catch (e) {}

                    try {

                        privateCallPeer.getReceivers().forEach(receiver => {

                            try {

                                receiver.track?.stop();

                            } catch (e) {}

                        });

                    } catch (e) {}

                    try {

                        privateCallPeer.close();

                    } catch (e) {}

                }

                // =================================================
                // STOP LOCAL CAMERA / MICROPHONE
                // =================================================

                if (privateCallLocalStream) {

                    privateCallLocalStream.getTracks().forEach(track => {

                        try {

                            track.stop();

                        } catch (e) {}

                    });

                }

                // =================================================
                // CLEAR VIDEO ELEMENTS
                // =================================================

                const localVideo =
                    document.getElementById('privateLocalVideo');

                const remoteVideo =
                    document.getElementById('privateRemoteVideo');

                if (localVideo) {

                    localVideo.pause?.();
                    localVideo.srcObject = null;

                }

                if (remoteVideo) {

                    remoteVideo.pause?.();
                    remoteVideo.srcObject = null;

                }

                // =================================================
                // HIDE ACTIVE CALL MODAL
                // =================================================

                if (closeModal) {

                    const modal =
                        document.getElementById('privateActiveCallModal');

                    modal?.classList.add('hidden');
                    modal?.classList.remove('flex');

                }

                // =================================================
                // RESET CALL STATUS
                // =================================================

                const status =
                    document.getElementById('privateActiveCallStatus');

                if (status) {

                    status.textContent = '';

                }

                // =================================================
                // RESET VARIABLES
                // =================================================

                privateCallPeer = null;
                privateCallLocalStream = null;

                if (closeModal) {

                    privateCallId = null;
                    privateCallTargetUserId = null;
                    privateCallTargetName = '';
                    privateCallTargetPicture = '';

                    privateCallConversationId = null;
                    privateIncomingOffer = null;

                    privateCallPendingIce = [];

                    privateCallMuted = false;
                    privateCallCameraEnabled = true;
                    privateCallType = 'audio';

                }

            }
            finally {

                // ===============================================
                // ALLOW FUTURE CALLS
                // ===============================================

                window.privateCallCleaningUp = false;

            }

        }

        async function handlePrivateCallSignal(event) {

            if (
                !event ||
                Number(event.from_user_id) === Number(currentUserId)
            ) {
                return;
            }

            const fromUserId =
                Number(event.from_user_id);

            // ==================================================
            // OFFER
            // ==================================================

            if (event.signal_type === 'offer') {

                if (
                    privateCallId &&
                    String(privateCallId) !== String(event.call_id)
                ) {

                    await sendPrivateCallSignal(
                        fromUserId,
                        'busy',
                        {},
                        {
                            callId: event.call_id,
                            callType: event.call_type,
                            conversationId: event.conversation_id
                        }
                    ).catch(() => {});

                    return;
                }

                privateCallId = event.call_id;

                privateCallTargetUserId = fromUserId;
                privateCallTargetName =
                    event.from_user_name || 'User';

                privateCallTargetPicture =
                    privateCallPicture({
                        user_profile_picture:
                            event.from_user_picture
                    });

                privateCallType =
                    event.call_type === 'video'
                        ? 'video'
                        : 'audio';

                privateCallConversationId =
                    event.conversation_id || null;

                privateIncomingOffer =
                    event.payload?.description || null;

                privateCallPendingIce = [];

                showIncomingPrivateCall(event);

                return;

            }

            if (
                !privateCallId ||
                String(event.call_id) !==
                String(privateCallId)
            ) {
                return;
            }

            // ==================================================
            // ANSWER
            // ==================================================

            if (
                event.signal_type === 'answer' &&
                event.payload?.description &&
                privateCallPeer
            ) {

                try {

                    const decodedAnswer =
                        decodePrivateCallDescription(
                            event.payload.description
                        );

                    if (!decodedAnswer) {

                        throw new Error(
                            'The call answer is invalid.'
                        );

                    }

                    await privateCallPeer.setRemoteDescription(
                        decodedAnswer
                    );

                    await addQueuedPrivateIce();

                    privateCallAnswered = true;

                    clearTimeout(privateCallTimeout);

                    privateCallTimeout = null;

                    document.getElementById(
                        'privateActiveCallStatus'
                    ).textContent =
                        'Connecting...';

                }
                catch (error) {

                    console.error(error);

                    cleanupPrivateCall();

                }

                return;

            }

            if (event.signal_type === 'camera_state') {

                const remoteVideo =
                    document.getElementById('privateRemoteVideo');

                const status =
                    document.getElementById('privateActiveCallStatus');

                document.getElementById(
                    'privateRemotePlaceholderName'
                ).textContent =
                    privateCallTargetName || 'User';

                privateCallAvatar(
                    document.getElementById(
                        'privateRemotePlaceholderAvatar'
                    ),
                    privateCallTargetName,
                    privateCallTargetPicture
                );

                if (event.payload?.enabled) {

                    hideRemoteCameraPlaceholder();

                    remoteVideo?.play?.().catch(() => {});

                    if (status) {
                        status.textContent = 'Connected';
                    }

                } else {

                    if (remoteVideo) {
                        remoteVideo.pause();
                        remoteVideo.srcObject = null;
                    }

                    showRemoteCameraPlaceholder();

                    if (status) {
                        status.textContent = 'Connected · Camera off';
                    }

                }

                return;

            }

            

            // ==================================================
            // ICE
            // ==================================================

            if (
                event.signal_type === 'ice_candidate' &&
                event.payload?.candidate
            ) {

                if (
                    privateCallPeer?.remoteDescription
                ) {

                    try {

                        await privateCallPeer.addIceCandidate(
                            new RTCIceCandidate(
                                event.payload.candidate
                            )
                        );

                    }
                    catch (error) {

                        console.error(error);

                    }

                }
                else {

                    privateCallPendingIce.push(
                        event.payload.candidate
                    );

                }

                return;

            }

            // ==================================================
            // DECLINED
            // ==================================================

            if (event.signal_type === 'decline') {

                alert(
                    `${privateCallTargetName || 'The user'} declined the call.`
                );

                cleanupPrivateCall();

                return;

            }

            // ==================================================
            // BUSY
            // ==================================================

            if (event.signal_type === 'busy') {

                alert(
                    `${privateCallTargetName || 'The user'} is already on another call.`
                );

                cleanupPrivateCall();

                return;

            }

            // ==================================================
            // REMOTE ENDED CALL
            // ==================================================

            if (event.signal_type === 'end') {

                console.log(
                    '[Private Call] Remote ended call.'
                );

                cleanupPrivateCall();

                return;

            }

        }

        window.addEventListener('beforeunload', () => {

            if (!privateCallId || !privateCallTargetUserId) {
                return;
            }

            try {

                fetch('/messages/calls/signal', {

                    method: 'POST',

                    keepalive: true,

                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },

                    body: JSON.stringify({

                        target_user_id: Number(privateCallTargetUserId),

                        conversation_id: privateCallConversationId,

                        call_id: privateCallId,

                        signal_type: 'end',

                        call_type: privateCallType,

                        payload: {}

                    })

                });

            } catch (e) {}

        });



        function listenToPrivateCallsRealtime() {
            if (!window.Echo) return;
            window.Echo.private(`user.${currentUserId}`).listen('.call.signal', event => {
                handlePrivateCallSignal(event).catch(console.error);
            });
        }

        document.getElementById('privateCallAcceptButton')?.addEventListener('click', acceptPrivateCall);
        document.getElementById('privateCallDeclineButton')?.addEventListener('click', declinePrivateCall);
        document.getElementById('privateCallEndButton')?.addEventListener('click', endPrivateCall);

        document.getElementById('privateCallMuteButton')?.addEventListener('click', () => {
            const tracks = privateCallLocalStream?.getAudioTracks() || [];
            if (!tracks.length) return;
            privateCallMuted = !privateCallMuted;
            tracks.forEach(track => track.enabled = !privateCallMuted);
            document.getElementById('privateCallMuteButton')?.querySelector('i')?.setAttribute('data-lucide', privateCallMuted ? 'mic-off' : 'mic');
            lucideCreateIcons();
        });

        // =====================================================
        // CAMERA BUTTON
        // If a webcam track already exists, toggle it normally.
        // If the call started microphone only, clicking this
        // button retries the webcam and attaches it to WebRTC.
        // =====================================================
        document.getElementById('privateCallCameraButton')?.addEventListener('click', async () => {
            if (privateCallType !== 'video' || !privateCallPeer) {
                return;
            }

            const existingTracks =
                privateCallLocalStream?.getVideoTracks() || [];

            // Camera already exists. Just turn it on or off.
            if (existingTracks.length) {
                privateCallCameraEnabled = !privateCallCameraEnabled;

                existingTracks.forEach(track => {
                    track.enabled = privateCallCameraEnabled;
                });

                await sendPrivateCallSignal(
                    privateCallTargetUserId,
                    'camera_state',
                    {
                        enabled: privateCallCameraEnabled
                    }
                ).catch(console.error);

                updatePrivateCallCameraButton();

                return;
            }

            // No video track exists because the webcam failed when
            // the call started. Retry the webcam now.
            try {
                const cameraStream =
                    await navigator.mediaDevices.getUserMedia({
                        audio: false,
                        video: true
                    });

                const videoTrack =
                    cameraStream.getVideoTracks()[0];

                if (!videoTrack) {
                    throw new Error('No camera video track was available.');
                }

                if (!privateCallLocalStream) {
                    privateCallLocalStream = new MediaStream();
                }

                privateCallLocalStream.addTrack(videoTrack);

                const local =
                    document.getElementById('privateLocalVideo');

                if (local) {
                    local.srcObject = privateCallLocalStream;
                    local.play?.().catch(() => {});
                }

                // buildPrivateCallPeer() creates a video transceiver
                // for video calls that started without a webcam.
                // Reuse that sender instead of restarting the call.
                const videoSender =
                    privateCallPeer
                        .getSenders()
                        .find(sender =>
                            sender.track?.kind === 'video'
                        )
                    ||
                    privateCallPeer
                        .getTransceivers()
                        .find(transceiver =>
                            transceiver.receiver?.track?.kind === 'video'
                        )
                        ?.sender;

                if (!videoSender) {
                    videoTrack.stop();
                    privateCallLocalStream.removeTrack(videoTrack);

                    throw new Error(
                        'The video connection is not ready to enable the camera.'
                    );
                }

                await videoSender.replaceTrack(videoTrack);

                privateCallCameraEnabled = true;

                await sendPrivateCallSignal(
                    privateCallTargetUserId,
                    'camera_state',
                    {
                        enabled: true
                    }
                ).catch(console.error);

                updatePrivateCallCameraButton();

                videoTrack.addEventListener(
                    'ended',
                    () => {

                        privateCallCameraEnabled = false;

                        sendPrivateCallSignal(
                            privateCallTargetUserId,
                            'camera_state',
                            {
                                enabled: false
                            }
                        ).catch(console.error);

                        updatePrivateCallCameraButton();

                    },
                    { once: true }
                );
            } catch (error) {
                console.error('Unable to enable camera:', error);

                privateCallCameraEnabled = false;
                updatePrivateCallCameraButton();

                // Do not end the call. The microphone connection
                // remains active and the user can retry later.
                const status =
                    document.getElementById('privateActiveCallStatus');

                if (status && privateCallPeer?.connectionState === 'connected') {
                    status.textContent = 'Connected · Camera off';
                }
            }
        });

        document.getElementById('modalAudioCallButton')?.addEventListener('click', () => {
            if (currentConversationType !== 'direct') {
                alert('Group calling will be added after private calling is working.');
                return;
            }
            const userId = Number(currentConversationUser?.user_id);
            if (userId) startPrivateCall(userId, currentConversationUserName, 'audio', privateCallPicture(currentConversationUser), currentConversationId);
        });

        document.getElementById('modalVideoCallButton')?.addEventListener('click', () => {
            if (currentConversationType !== 'direct') {
                alert('Group calling will be added after private calling is working.');
                return;
            }
            const userId = Number(currentConversationUser?.user_id);
            if (userId) startPrivateCall(userId, currentConversationUserName, 'video', privateCallPicture(currentConversationUser), currentConversationId);
        });



        // =====================================================
        // CONVERSATION INFO BUTTON
        //
        // Click once  = open sidebar
        // Click again = close sidebar
        // =====================================================
        document
            .getElementById(
                'modalConversationInfoButton'
            )
            ?.addEventListener(
                'click',
                () => {
                    const sidebar =
                        document.getElementById(
                            'modalConversationInfoSidebar'
                        );

                    if (!sidebar) {
                        return;
                    }

                    const isOpen =
                        !sidebar.classList.contains('hidden');

                    if (isOpen) {
                        closeConversationInfoSidebar();
                        return;
                    }

                    openConversationInfoSidebar();
                }
            );


        document
            .getElementById(
                'modalConversationInfoClose'
            )
            ?.addEventListener(
                'click',
                closeConversationInfoSidebar
            );


        // =====================================================
        // GROUP CHAT SIDEBAR CONTROLS
        // =====================================================
        document
            .getElementById(
                'modalConversationMuteButton'
            )
            ?.addEventListener(
                'click',
                openGroupMuteModal
            );

        document
            .getElementById(
                'modalGroupMembersAccordionButton'
            )
            ?.addEventListener(
                'click',
                toggleGroupMembersAccordion
            );

        document
            .getElementById(
                'modalAddGroupPeopleButton'
            )
            ?.addEventListener(
                'click',
                openAddGroupPeopleModal
            );

        document
            .getElementById(
                'modalGroupLeaveButton'
            )
            ?.addEventListener(
                'click',
                openLeaveGroupConfirmModal
            );
        // =====================================================
        // MUTE MODAL CONTROLS
        // =====================================================
        document.getElementById('groupMuteClose')
            ?.addEventListener('click', closeGroupMuteModal);

        document.getElementById('groupMuteCancel')
            ?.addEventListener('click', closeGroupMuteModal);

        document.getElementById('groupMuteBackdrop')
            ?.addEventListener('click', closeGroupMuteModal);

        document.getElementById('groupMuteToggleButton')
            ?.addEventListener('click', toggleMuteFromModal);

        document.getElementById('groupMuteDone')
            ?.addEventListener('click', saveGroupMuteSetting);


        // =====================================================
        // LEAVE GROUP CONFIRMATION MODAL
        // =====================================================
        document.getElementById('leaveGroupConfirmClose')
            ?.addEventListener('click', closeLeaveGroupConfirmModal);

        document.getElementById('leaveGroupConfirmCancel')
            ?.addEventListener('click', closeLeaveGroupConfirmModal);

        document.getElementById('leaveGroupConfirmBackdrop')
            ?.addEventListener('click', closeLeaveGroupConfirmModal);

        document.getElementById('leaveGroupConfirmSubmit')
            ?.addEventListener('click', confirmLeaveCurrentGroup);


        // =====================================================
        // CLOSE MEMBER 3-DOT MENU WHEN CLICKING ELSEWHERE
        // =====================================================
        document.addEventListener('click', event => {
            const menu = document.getElementById('groupMemberMenu');

            if (
                menu &&
                !menu.classList.contains('hidden') &&
                !menu.contains(event.target) &&
                !event.target.closest('.group-member-menu-button')
            ) {
                closeGroupMemberMenu();
            }
        });


        document
            .getElementById(
                'addGroupPeopleClose'
            )
            ?.addEventListener(
                'click',
                closeAddGroupPeopleModal
            );

        document
            .getElementById(
                'addGroupPeopleCancel'
            )
            ?.addEventListener(
                'click',
                closeAddGroupPeopleModal
            );

        document
            .getElementById(
                'addGroupPeopleBackdrop'
            )
            ?.addEventListener(
                'click',
                closeAddGroupPeopleModal
            );

        document
            .getElementById(
                'addGroupPeopleSubmit'
            )
            ?.addEventListener(
                'click',
                submitAddGroupPeople
            );

        let addGroupPeopleSearchTimer = null;

        document
            .getElementById(
                'addGroupPeopleSearch'
            )
            ?.addEventListener(
                'input',
                () => {
                    clearTimeout(
                        addGroupPeopleSearchTimer
                    );

                    addGroupPeopleSearchTimer =
                        setTimeout(
                            loadAddGroupPeopleUsers,
                            250
                        );
                }
            );


        // =====================================================
        // CHAT INFO SIDEBAR CONTROLS
        // =====================================================
        document
            .getElementById(
                'modalChatInfoAccordionButton'
            )
            ?.addEventListener(
                'click',
                toggleChatInfoAccordion
            );


        document
            .getElementById(
                'modalViewPinnedMessagesButton'
            )
            ?.addEventListener(
                'click',
                openPinnedMessagesModal
            );


        // =====================================================
        // SEARCH BUTTON INSIDE CONVERSATION INFO SIDEBAR
        // =====================================================
        document
            .getElementById(
                'modalConversationSidebarSearchButton'
            )
            ?.addEventListener(
                'click',
                openConversationSidebarSearchView
            );


        document
            .getElementById(
                'modalConversationSidebarSearchBack'
            )
            ?.addEventListener(
                'click',
                closeConversationSidebarSearchView
            );


        document
            .getElementById(
                'modalConversationSidebarSearchInput'
            )
            ?.addEventListener(
                'input',
                queueConversationSidebarSearch
            );


        document
            .getElementById(
                'modalConversationSidebarSearchClear'
            )
            ?.addEventListener(
                'click',
                () => {

                    const input =
                        document.getElementById(
                            'modalConversationSidebarSearchInput'
                        );

                    if (input) {
                        input.value = '';
                        input.focus();
                    }

                    queueConversationSidebarSearch();
                }
            );


        document
            .getElementById(
                'modalConversationSidebarSearchResults'
            )
            ?.addEventListener(
                'click',
                event => {

                    const result =
                        event.target.closest(
                            '[data-sidebar-search-message-id]'
                        );

                    if (!result) {
                        return;
                    }

                    jumpToSidebarSearchMessage(
                        result.dataset
                            .sidebarSearchMessageId
                    );
                }
            );


        document
            .getElementById(
                'modalMediaFilesAccordionButton'
            )
            ?.addEventListener(
                'click',
                toggleMediaFilesAccordion
            );


        document
            .getElementById(
                'modalOpenConversationMedia'
            )
            ?.addEventListener(
                'click',
                () =>
                    openConversationAssetsView(
                        'media'
                    )
            );


        document
            .getElementById(
                'modalOpenConversationFiles'
            )
            ?.addEventListener(
                'click',
                () =>
                    openConversationAssetsView(
                        'file'
                    )
            );


        document
            .getElementById(
                'modalConversationAssetsBack'
            )
            ?.addEventListener(
                'click',
                closeConversationAssetsView
            );


        document
            .getElementById(
                'modalConversationMediaTab'
            )
            ?.addEventListener(
                'click',
                () =>
                    setConversationAssetsTab(
                        'media'
                    )
            );


        document
            .getElementById(
                'modalConversationFilesTab'
            )
            ?.addEventListener(
                'click',
                () =>
                    setConversationAssetsTab(
                        'file'
                    )
            );


        document
            .getElementById(
                'modalConversationMediaGrid'
            )
            ?.addEventListener(
                'click',
                event => {

                    const button =
                        event.target.closest(
                            '[data-conversation-asset-index]'
                        );

                    if (!button) {
                        return;
                    }

                    const records =
                        conversationAssets.filter(
                            item =>
                                item.kind === 'media'
                        );

                    const item =
                        records[
                            Number(
                                button.dataset
                                    .conversationAssetIndex
                            )
                        ];

                    if (!item) {
                        return;
                    }

                    // Reuse the existing Messenger style
                    // full image preview already in this file.
                    openImagePreviewFromMessage(
                        null,
                        item.attachment.url || '',
                        item.attachment.name || 'Image'
                    );
                }
            );


        window.openMessagingModal = function() {
            const modal = document.getElementById('messagingModal');
            const backdrop = document.getElementById('messagingModalBackdrop');
            const container = document.getElementById('messagingModalContainer');

            if (!modal || !backdrop || !container) return;

            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';

            backdrop.classList.remove('opacity-0');
            container.classList.remove('scale-[0.98]', 'scale-[0.95]', 'opacity-0');
            container.classList.add('scale-100', 'opacity-100');

            restoreMessagingFullscreenPreference();
            showMessagingConversationList();
            switchModalTab('conversations', { refresh: true });
            requestAnimationFrame(() => lucideCreateIcons(modal));
        };

        window.closeMessagingModal = function() {
            const modal = document.getElementById('messagingModal');
            const backdrop = document.getElementById('messagingModalBackdrop');
            const container = document.getElementById('messagingModalContainer');

            if (!modal || !backdrop || !container) return;

            backdrop.classList.add('opacity-0');
            container.classList.remove('scale-100', 'opacity-100');
            container.classList.add('scale-[0.98]', 'opacity-0');

            setTimeout(() => {
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                resetModalChat();
            }, 80);
        };


        // =====================================================
        // SEARCH INSIDE CONVERSATION
        //
        // This searches the ENTIRE opened conversation.
        // Older paginated messages are loaded automatically
        // before the final result list is created.
        // =====================================================

        function clearConversationSearchHighlight() {

            document
                .querySelectorAll(
                    '#modalMessagesContainer .conversation-search-match'
                )
                .forEach(element => {
                    element.classList.remove(
                        'conversation-search-match',
                        'conversation-search-current'
                    );
                });
        }


        function resetConversationSearchState(clearInput = true) {

            if (conversationSearchTimeout) {
                clearTimeout(conversationSearchTimeout);
                conversationSearchTimeout = null;
            }

            conversationSearchMatches = [];
            conversationSearchIndex = -1;
            conversationSearchLoadingAll = false;

            clearConversationSearchHighlight();

            const input =
                document.getElementById(
                    'modalConversationMessageSearch'
                );

            const count =
                document.getElementById(
                    'modalConversationSearchCount'
                );

            const previous =
                document.getElementById(
                    'modalConversationSearchPrevious'
                );

            const next =
                document.getElementById(
                    'modalConversationSearchNext'
                );

            if (clearInput && input) {
                input.value = '';
            }

            if (count) {
                count.textContent = '0 of 0';
            }

            if (previous) {
                previous.disabled = true;
            }

            if (next) {
                next.disabled = true;
            }
        }


        function openConversationMessageSearch() {

            if (!currentConversationId) {
                return;
            }

            const normalHeader =
                document.getElementById(
                    'modalChatHeaderNormal'
                );

            const searchBar =
                document.getElementById(
                    'modalConversationSearchBar'
                );

            const input =
                document.getElementById(
                    'modalConversationMessageSearch'
                );

            normalHeader?.classList.add('hidden');

            searchBar?.classList.remove('hidden');
            searchBar?.classList.add('flex');

            lucideCreateIcons();

            requestAnimationFrame(() => {
                input?.focus();
                input?.select();
            });
        }


        function closeConversationMessageSearch() {

            const normalHeader =
                document.getElementById(
                    'modalChatHeaderNormal'
                );

            const searchBar =
                document.getElementById(
                    'modalConversationSearchBar'
                );

            resetConversationSearchState(true);

            searchBar?.classList.add('hidden');
            searchBar?.classList.remove('flex');

            normalHeader?.classList.remove('hidden');

            lucideCreateIcons();
        }

        function handleMessagingSmartClose() {
            const searchBar = document.getElementById(
                'modalConversationSearchBar'
            );
            const searchIsOpen =
                searchBar &&
                !searchBar.classList.contains('hidden');

            if (searchIsOpen) {
                closeConversationMessageSearch();
                return;
            }

            window.closeMessagingModal();
        }

        [
            document.getElementById('messagingSmartCloseButton'),
            document.getElementById('messagingSmartCloseButtonThread')
        ].forEach(button => {
            button?.addEventListener('click', handleMessagingSmartClose);
        });

        [
            document.getElementById('messagingFullscreenButton'),
            document.getElementById('messagingFullscreenButtonThread')
        ].forEach(button => {
            button?.addEventListener('click', toggleMessagingFullscreen);
        });

        document
            .getElementById('modalChatBackButton')
            ?.addEventListener('click', () => {
                resetModalChat();
            });

        function isMessagingMobile() {
            return window.matchMedia('(max-width: 767px)').matches;
        }

        function showMessagingConversationList() {
            document
                .getElementById('messagingModalContainer')
                ?.classList.remove('messaging-thread-open');
        }

        function showMessagingThreadPane() {
            document
                .getElementById('messagingModalContainer')
                ?.classList.add('messaging-thread-open');
        }

        function isMessagingFullscreen() {
            return document
                .getElementById('messagingModalContainer')
                ?.classList.contains('is-fullscreen');
        }

        function restoreMessagingFullscreenPreference() {
            const saved = sessionStorage.getItem('prismMessagingFullscreen') === '1';
            setMessagingFullscreen(saved);
        }

        function setMessagingFullscreen(on) {
            const container = document.getElementById('messagingModalContainer');
            if (!container) return;

            container.classList.toggle('is-fullscreen', Boolean(on));

            try {
                sessionStorage.setItem('prismMessagingFullscreen', on ? '1' : '0');
            } catch (error) {
                // Ignore private-mode storage errors.
            }

            [
                document.getElementById('messagingFullscreenButton'),
                document.getElementById('messagingFullscreenButtonThread')
            ].forEach(button => {
                if (!button) return;
                button.setAttribute('data-tooltip', on ? 'Exit full screen' : 'Full screen');
                button.setAttribute('aria-label', on ? 'Exit full screen' : 'Full screen');
                button.innerHTML = on
                    ? '<i data-lucide="minimize-2" class="h-4 w-4"></i>'
                    : '<i data-lucide="maximize-2" class="h-4 w-4"></i>';
                lucideCreateIcons(button);
            });
        }

        function toggleMessagingFullscreen() {
            setMessagingFullscreen(!isMessagingFullscreen());
        }


        async function loadAllMessagesForConversationSearch() {

            if (!currentConversationId) {
                return;
            }

            // =============================================
            // ALREADY LOADING
            // Wait for the existing loading process.
            // =============================================
            if (conversationSearchLoadPromise) {
                await conversationSearchLoadPromise;
                return;
            }

            // =============================================
            // EVERYTHING IS ALREADY LOADED
            // Nothing else needs to be fetched.
            // =============================================
            if (!hasMoreMessages) {
                return;
            }

            conversationSearchLoadingAll = true;

            conversationSearchLoadPromise = (async () => {

                try {

                    // =============================================
                    // LOAD EVERY OLDER MESSAGE PAGE
                    // =============================================
                    while (hasMoreMessages) {

                        // Another message request may currently
                        // be running. Wait until it finishes.
                        while (isLoadingMessages) {
                            await new Promise(
                                resolve => setTimeout(resolve, 80)
                            );
                        }

                        // Check again because the request that
                        // just finished may have reached the end.
                        if (!hasMoreMessages) {
                            break;
                        }

                        messagesPage++;

                        await loadModalMessages(
                            currentConversationId,
                            true
                        );
                    }

                } finally {

                    conversationSearchLoadingAll = false;
                    conversationSearchLoadPromise = null;
                }

            })();

            await conversationSearchLoadPromise;
        }


        function updateConversationSearchCounter() {

            const count =
                document.getElementById(
                    'modalConversationSearchCount'
                );

            const previous =
                document.getElementById(
                    'modalConversationSearchPrevious'
                );

            const next =
                document.getElementById(
                    'modalConversationSearchNext'
                );

            const total =
                conversationSearchMatches.length;


            // =====================================================
            // UPDATE "1 of 3" TEXT
            // =====================================================
            if (count) {

                count.textContent =
                    total > 0 && conversationSearchIndex >= 0
                        ? `${conversationSearchIndex + 1} of ${total}`
                        : '0 of 0';
            }


            // =====================================================
            // NO RESULTS
            // Disable both arrows
            // =====================================================
            if (
                total === 0 ||
                conversationSearchIndex < 0
            ) {

                if (previous) {
                    previous.disabled = true;
                }

                if (next) {
                    next.disabled = true;
                }

                return;
            }


            // =====================================================
            // UP ARROW
            //
            // Disable when already at the FIRST result.
            //
            // Example:
            // 1 of 3 = disabled
            // 2 of 3 = enabled
            // 3 of 3 = enabled
            // =====================================================
            if (previous) {

                previous.disabled =
                    conversationSearchIndex === 0;
            }


            // =====================================================
            // DOWN ARROW
            //
            // Disable when already at the LAST result.
            //
            // Example:
            // 1 of 3 = enabled
            // 2 of 3 = enabled
            // 3 of 3 = disabled
            // =====================================================
            if (next) {

                next.disabled =
                    conversationSearchIndex === total - 1;
            }
        }


        function focusConversationSearchResult(index) {

            const total =
                conversationSearchMatches.length;


            // =====================================================
            // NO SEARCH RESULTS
            // =====================================================
            if (total === 0) {

                conversationSearchIndex = -1;

                updateConversationSearchCounter();

                return;
            }


            // =====================================================
            // HARD LIMIT
            //
            // Do not allow navigation before the first result
            // or after the last result.
            // =====================================================
            if (
                index < 0 ||
                index >= total
            ) {

                updateConversationSearchCounter();

                return;
            }


            // =====================================================
            // SET CURRENT RESULT
            // =====================================================
            conversationSearchIndex = index;


            // =====================================================
            // UPDATE HIGHLIGHT
            // =====================================================
            conversationSearchMatches.forEach(
                (element, elementIndex) => {

                    element.classList.toggle(
                        'conversation-search-current',
                        elementIndex === conversationSearchIndex
                    );
                }
            );


            // =====================================================
            // GET CURRENT MATCH
            // =====================================================
            const target =
                conversationSearchMatches[
                    conversationSearchIndex
                ];


            // =====================================================
            // SCROLL TO CURRENT MATCH
            // =====================================================
            target?.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });


            // =====================================================
            // UPDATE COUNTER AND ARROW STATES
            // =====================================================
            updateConversationSearchCounter();
        }


        async function searchCurrentConversation(query) {

            const normalizedQuery =
                String(query || '')
                    .trim()
                    .toLowerCase();

            clearConversationSearchHighlight();

            conversationSearchMatches = [];
            conversationSearchIndex = -1;

            if (!normalizedQuery) {
                updateConversationSearchCounter();
                return;
            }

            // =============================================
            // SEARCH THE WHOLE CHAT, NOT ONLY VISIBLE PAGE
            // =============================================
            await loadAllMessagesForConversationSearch();

            // User may have closed search while older pages loaded.
            const input =
                document.getElementById(
                    'modalConversationMessageSearch'
                );

            if (
                !input ||
                input.value.trim().toLowerCase() !==
                    normalizedQuery
            ) {
                return;
            }

            const rows =
                Array.from(
                    document.querySelectorAll(
                        '#modalMessagesContainer .message-row'
                    )
                );

            conversationSearchMatches =
                rows.filter(row => {

                    // Do not include messages that were unsent.
                    if (
                        row.dataset.messageUnsent === '1'
                    ) {
                        return false;
                    }

                    const messageContent =
                        String(
                            row.dataset.messageContent || ''
                        ).toLowerCase();

                    // Attachment filename is also searchable
                    // when it exists in the rendered message.
                    const attachmentName =
                        String(
                            row.querySelector(
                                '[data-attachment-name]'
                            )?.dataset.attachmentName ||
                            ''
                        ).toLowerCase();

                    return (
                        messageContent.includes(
                            normalizedQuery
                        ) ||
                        attachmentName.includes(
                            normalizedQuery
                        )
                    );
                });

            conversationSearchMatches.forEach(row => {
                row.classList.add(
                    'conversation-search-match'
                );
            });

            if (conversationSearchMatches.length > 0) {

                // Start at the newest matching message.
                focusConversationSearchResult(
                    conversationSearchMatches.length - 1
                );

            } else {

                updateConversationSearchCounter();
            }
        }


        function queueConversationSearch() {

            const input =
                document.getElementById(
                    'modalConversationMessageSearch'
                );

            if (!input) {
                return;
            }

            if (conversationSearchTimeout) {
                clearTimeout(
                    conversationSearchTimeout
                );
            }

            conversationSearchTimeout =
                setTimeout(() => {
                    searchCurrentConversation(
                        input.value
                    );
                }, 250);
        }


        function resetModalChat() {
            cancelReply();
            closeConversationMessageSearch();
            closeConversationInfoSidebar();
            resetConversationAssets();
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
            setThreadLoading(false);
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
            document.getElementById('modalPinnedBanner')?.classList.add('hidden');
            if (composer) composer.classList.add('hidden');
            if (chatArea) {
                chatArea.classList.remove('hidden');
                chatArea.classList.add('flex', 'md:flex');
            }

            showMessagingConversationList();
            updateScrollToLatestButton();
        }

        window.switchModalTab = function(tab, options = {}) {
            const refresh = options.refresh !== false;
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
                if (conversationsCache) {
                    renderModalConversations(conversationsCache);
                }
                if (refresh || !conversationsCache) {
                    loadModalConversations();
                }
            } else {
                conversationsSection?.classList.add('hidden');
                usersSection?.classList.remove('hidden');
                usersTab?.classList.add('bg-white', 'text-gray-900', 'shadow-sm');
                usersTab?.classList.remove('text-gray-500');
                conversationsTab?.classList.remove('bg-white', 'text-gray-900', 'shadow-sm');
                conversationsTab?.classList.add('text-gray-500');
                loadModalUsers('', true);
            }

            if (isMessagingMobile()) {
                showMessagingConversationList();
            }
        };

        // =====================================================
        // LOAD CONVERSATIONS
        // =====================================================

        let conversationsCache = null;
        let conversationsReloadTimer = null;
        let conversationsLoadPromise = null;
        let conversationsLoadedAt = 0;
        let conversationsRenderKey = '';
        let mutedConversationIds = new Set();
        let usersCache = null;
        let usersCacheSearch = '';
        let usersLoadedAt = 0;

        function conversationsListIsStale() {
            return !conversationsCache || (Date.now() - conversationsLoadedAt) > 20000;
        }

        function getConversationListItems(source = conversationsCache) {
            if (!source) {
                return [];
            }

            if (Array.isArray(source)) {
                return source;
            }

            return Array.isArray(source.data) ? source.data : [];
        }

        function currentUserIsMutedInConversation(conv) {
            const participants = Array.isArray(conv?.participants)
                ? conv.participants
                : [];

            const mine = participants.find(participant =>
                Number(participant.user_id ?? participant.user?.user_id) ===
                Number(currentUserId)
            );

            return Boolean(Number(mine?.is_muted || 0));
        }

        function syncMutedConversationIds(source = conversationsCache) {
            mutedConversationIds = new Set(
                getConversationListItems(source)
                    .filter(currentUserIsMutedInConversation)
                    .map(conv => Number(conv.conversation_id))
            );
        }

        function isConversationMuted(conversationId) {
            return mutedConversationIds.has(Number(conversationId));
        }

        function isDirectChatWithUserMuted(userId) {
            return getConversationListItems().some(conv => {
                if (conv.conversation_type === 'group') {
                    return false;
                }

                if (!currentUserIsMutedInConversation(conv)) {
                    return false;
                }

                return (conv.participants || []).some(participant =>
                    Number(participant.user_id ?? participant.user?.user_id) ===
                    Number(userId)
                );
            });
        }

        function scheduleLoadModalConversations() {
            if (conversationsReloadTimer) {
                return;
            }

            conversationsReloadTimer = setTimeout(() => {
                conversationsReloadTimer = null;
                loadModalConversations();
            }, 180);
        }

        async function loadModalConversations() {

            const search =
                document.getElementById('modalConversationSearch')?.value || '';

            if (conversationsLoadPromise) {
                return conversationsLoadPromise;
            }

            const params = new URLSearchParams();

            if (search) {
                params.set('search', search);
            }

            conversationsLoadPromise = (async () => {
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

                conversationsCache = result.data;
                conversationsLoadedAt = Date.now();

                renderModalConversations(
                    result.data
                );

            } catch (error) {

                console.error(
                    'Conversation loading error:',
                    error
                );
            }
            })();

            try {
                await conversationsLoadPromise;
            } finally {
                conversationsLoadPromise = null;
            }
        }

        function normalizeCallType(callType) {
            return String(callType).toLowerCase() === 'video'
                ? 'video'
                : 'audio';
        }

        function resolveCallDisplayStatus(call) {
            const status = String(call?.status || '').toLowerCase();
            const duration = Number(call?.duration || 0);
            const wasAnswered = Boolean(call?.answered_at);

            if (status === 'ended' && !wasAnswered && duration <= 0) {
                return 'missed';
            }

            if (status === 'ended' && duration > 0) {
                return 'completed';
            }

            return status;
        }

        function formatCallDurationClock(totalSeconds) {
            const safeSeconds = Math.max(0, Number(totalSeconds) || 0);
            const minutes = Math.floor(safeSeconds / 60);
            const seconds = safeSeconds % 60;

            return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }

        function formatCallDurationCompact(totalSeconds) {
            const safeSeconds = Math.max(0, Number(totalSeconds) || 0);
            const minutes = Math.floor(safeSeconds / 60);
            const seconds = safeSeconds % 60;

            if (minutes > 0 && seconds > 0) {
                return `${minutes}m ${seconds}s`;
            }

            if (minutes > 0) {
                return `${minutes}m`;
            }

            return `${seconds}s`;
        }

        function getCallCardTitle(call) {
            const callType = normalizeCallType(call?.call_type);
            const callTypeLabel = callType === 'video' ? 'video' : 'audio';
            const displayStatus = resolveCallDisplayStatus(call);

            switch (displayStatus) {
                case 'missed':
                    return `Missed ${callTypeLabel} call`;

                case 'declined':
                    return `Missed ${callTypeLabel} call`;

                case 'busy':
                    return `Missed ${callTypeLabel} call`;

                case 'completed':
                    return callType === 'video' ? 'Video call' : 'Audio call';

                default:
                    return callType === 'video' ? 'Video call' : 'Audio call';
            }
        }

        function getCallCardSubtitle(call) {
            const displayStatus = resolveCallDisplayStatus(call);
            const duration = Number(call?.duration || 0);

            if (displayStatus === 'completed' && duration > 0) {
                return `Duration ${formatCallDurationClock(duration)}`;
            }

            return '';
        }

        function getConversationCallPreview(call, isOwn, senderName, fallbackName) {
            const callType = normalizeCallType(call?.call_type);
            const callTypeLabel = callType === 'video' ? 'video' : 'audio';
            const duration = Number(call?.duration || 0);
            const remoteName = fallbackName || senderName || 'User';
            const displayStatus = resolveCallDisplayStatus(call);

            switch (displayStatus) {
                case 'missed':
                    return isOwn
                        ? `${remoteName} missed your ${callTypeLabel} call`
                        : `Missed your ${callTypeLabel} call`;

                case 'declined':
                    return isOwn
                        ? `${remoteName} declined your ${callTypeLabel} call`
                        : `You declined ${remoteName}'s ${callTypeLabel} call`;

                case 'busy':
                    return isOwn
                        ? `${remoteName} is busy`
                        : 'You were busy';

                case 'completed':
                    if (duration > 0) {
                        return `${callType === 'video' ? 'Video' : 'Audio'} call • ${formatCallDurationCompact(duration)}`;
                    }

                    return `${callType === 'video' ? 'Video' : 'Audio'} call`;

                default:
                    return `${callType === 'video' ? 'Video' : 'Audio'} call`;
            }
        }

        // =====================================================
        // RENDER CONVERSATIONS
        // Supports both direct chats and group chats
        // =====================================================

        function renderModalConversations(conversations) {

            const container =
                document.getElementById(
                    'modalConversationsList'
                );

            const emptyState =
                document.getElementById(
                    'modalConversationsEmpty'
                );

            if (!container) {
                return;
            }


            // =========================================
            // GET CONVERSATION ARRAY
            // =========================================

            const items = getConversationListItems(conversations);

            syncMutedConversationIds(conversations);

            if (items.length === 0) {

                conversationsRenderKey = '';
                container.innerHTML = '';

                emptyState?.classList.remove(
                    'hidden'
                );

                return;
            }

            emptyState?.classList.add(
                'hidden'
            );

            const pinPreviews = readConversationPinPreviews();

            const renderKey = items.map(conv => [
                conv.conversation_id,
                conv.last_message_at,
                conv.unread_count,
                currentUserIsMutedInConversation(conv) ? 1 : 0,
                conv.last_message?.message_content || '',
                conv.conversation_name || '',
                pinPreviews[String(conv.conversation_id)]?.kind || '',
                pinPreviews[String(conv.conversation_id)]?.at || ''
            ].join(':')).join('|');

            if (renderKey === conversationsRenderKey && container.childElementCount > 0) {
                return;
            }

            conversationsRenderKey = renderKey;

            items.sort((a, b) => {
                const activityAt = conv => {
                    const pinAt = new Date(
                        pinPreviews[String(conv.conversation_id)]?.at || 0
                    ).getTime();
                    const messageAt = new Date(
                        conv.last_message_at ||
                        conv.last_message?.created_at ||
                        0
                    ).getTime();
                    return Math.max(
                        Number.isNaN(pinAt) ? 0 : pinAt,
                        Number.isNaN(messageAt) ? 0 : messageAt
                    );
                };

                return activityAt(b) - activityAt(a);
            });


            // =========================================
            // RENDER CONVERSATION LIST
            // =========================================

            container.innerHTML =
                items.map(conv => {

                    const lastMessage =
                        conv.last_message || {};

                    const participants =
                        Array.isArray(conv.participants)
                            ? conv.participants
                            : [];


                    // =====================================
                    // DETECT GROUP CHAT
                    // =====================================

                    const isGroup =
                        conv.conversation_type === 'group';


                    // =====================================
                    // OTHER PARTICIPANT
                    // Only used for direct conversations
                    // =====================================

                    const otherParticipant =
                        participants.find(
                            participant =>
                                Number(
                                    participant.user?.user_id
                                ) !==
                                Number(currentUserId)
                        )?.user || {};


                    // =====================================
                    // CONVERSATION DISPLAY NAME
                    // =====================================

                    const name =
                        isGroup
                            ? (
                                conv.conversation_name ||
                                'Group chat'
                            )
                            : (
                                otherParticipant
                                    .user_full_name ||
                                'Unknown'
                            );


                    const initials =
                        getInitials(name);


                    // =====================================
                    // ONLINE STATUS
                    //
                    // Direct chat:
                    // Show the other user's status.
                    //
                    // Group chat:
                    // Do not pretend the whole group is
                    // online based on one participant.
                    // =====================================

                    let isOnline = false;

                    if (!isGroup) {

                        const lastActiveAt =
                            otherParticipant.last_active_at;

                        if (lastActiveAt) {

                            const lastActive =
                                new Date(lastActiveAt);

                            const now =
                                new Date();

                            const diffMinutes =
                                Math.floor(
                                    (now - lastActive) /
                                    60000
                                );

                            isOnline =
                                diffMinutes <= 2;
                        }
                    }


                    // =====================================
                    // LAST MESSAGE SENDER
                    // =====================================

                    const lastMessageIsMine =
                        Number(lastMessage.sender_id) ===
                        Number(currentUserId);

                    const lastMessageSender =
                        lastMessage.sender || {};

                    const lastMessageSenderName =
                        lastMessageSender.user_full_name ||
                        lastMessageSender.name ||
                        (
                            isGroup
                                ? 'Someone'
                                : name
                        );


                    // =====================================
                    // UNREAD COUNT
                    // =====================================

                    const unreadCount =
                        currentUserIsMutedInConversation(conv)
                            ? 0
                            : Number(
                                conv.unread_count || 0
                            );


                    // =====================================
                    // MESSAGE PREVIEW
                    // =====================================

                    let preview =
                        'No messages';
                    let previewTimeSource =
                        lastMessage.created_at ||
                        conv.last_message_at ||
                        '';

                    const pinPreview = getConversationPinPreview(
                        conv.conversation_id,
                        lastMessage.created_at || conv.last_message_at
                    );

                    const rawMessage =
                        lastMessage.message_content || '';

                    const messageType =
                        lastMessage.message_type || '';

                    if (pinPreview) {
                        preview = pinPreview.kind === 'unpinned'
                            ? 'You unpinned a message.'
                            : 'You pinned a message.';
                        previewTimeSource = pinPreview.at;
                    }
                    else if (
                        messageType === 'call' &&
                        lastMessage.call
                    ) {
                        preview = getConversationCallPreview(
                            lastMessage.call,
                            lastMessageIsMine,
                            lastMessageSenderName,
                            isGroup ? '' : name
                        );
                    }
                    else if (lastMessage.is_unsent) {
                        preview = lastMessageIsMine
                            ? 'You unsent a message'
                            : `${lastMessageSenderName} unsent a message`;
                    }
                    else if (
                        !lastMessageIsMine &&
                        unreadCount > 1
                    ) {

                        preview =
                            `${unreadCount} new messages`;

                    }


                    else if (isLikeStickerContent(rawMessage)) {
                        preview = lastMessageIsMine
                            ? 'You sent a like'
                            : (
                                isGroup
                                    ? `${lastMessageSenderName} sent a like`
                                    : 'Sent a like'
                            );
                    }


                    // =====================================
                    // PHOTO
                    // =====================================

                    else if (
                        rawMessage ===
                        '[attachment:image]'
                    ) {

                        preview =
                            lastMessageIsMine
                                ? 'You sent a photo.'
                                : `${lastMessageSenderName} sent a photo.`;

                    }


                    // =====================================
                    // FILE
                    // =====================================

                    else if (
                        rawMessage ===
                        '[attachment:file]'
                    ) {

                        preview =
                            lastMessageIsMine
                                ? 'You sent a file.'
                                : `${lastMessageSenderName} sent a file.`;

                    }


                    // =====================================
                    // MULTIPLE ATTACHMENTS
                    // =====================================

                    else if (
                        rawMessage ===
                        '[attachment:multiple]'
                    ) {

                        const attachmentCount =
                            Array.isArray(
                                lastMessage.attachments
                            )
                                ? lastMessage
                                    .attachments.length
                                : 0;

                        const attachmentLabel =
                            attachmentCount > 1
                                ? `${attachmentCount} attachments`
                                : 'attachments';

                        preview =
                            lastMessageIsMine
                                ? `You sent ${attachmentLabel}.`
                                : `${lastMessageSenderName} sent ${attachmentLabel}.`;

                    }


                    // =====================================
                    // NORMAL TEXT
                    // =====================================

                    else if (rawMessage) {

                        const shortenedMessage =
                            rawMessage.length > 50
                                ? rawMessage.substring(
                                    0,
                                    50
                                ) + '...'
                                : rawMessage;

                        if (lastMessageIsMine) {

                            preview =
                                `You: ${shortenedMessage}`;

                        } else if (isGroup) {

                            preview =
                                `${lastMessageSenderName}: ${shortenedMessage}`;

                        } else {

                            preview =
                                shortenedMessage;
                        }
                    }


                    // =====================================
                    // TIME
                    // =====================================

                    const time =
                        formatConversationRelativeTime(
                            previewTimeSource
                        );

                    let previewHtml = escapeHtml(preview);
                    if (pinPreview) {
                        previewHtml = `
                            <span class="inline-flex min-w-0 items-center gap-1">
                                                ${getPinActionIconHtml(pinPreview.kind === 'unpinned', 'h-3.5 w-3.5')}
                                <span class="truncate">${escapeHtml(preview)}</span>
                            </span>
                        `;
                    } else if (!pinPreview && isLikeStickerContent(rawMessage) && !lastMessage.is_unsent) {
                        const likePrefix = lastMessageIsMine
                            ? 'You:'
                            : (isGroup ? `${lastMessageSenderName}:` : '');
                        previewHtml = `
                            <span class="inline-flex min-w-0 items-center gap-1">
                                ${likePrefix ? `<span class="shrink-0">${escapeHtml(likePrefix)}</span>` : ''}
                                ${messengerLikeIconHtml('h-3.5 w-3.5 shrink-0')}
                            </span>
                        `;
                    }


                    // =====================================
                    // SEEN STATUS
                    //
                    // Direct chats can use the other
                    // participant's avatar.
                    //
                    // Group seen state needs separate
                    // per participant handling later.
                    // =====================================

                    let conversationMessageStatus = '';
                    let conversationSeenHtml = '';

                    if (
                        !isGroup &&
                        lastMessageIsMine &&
                        lastMessage.message_id
                    ) {

                        if (
                            lastMessage.is_read ||
                            lastMessage.read_at
                        ) {

                            let seenPicture =
                                otherParticipant
                                    .user_profile_picture ||
                                otherParticipant
                                    .profile_picture ||
                                '';

                            if (seenPicture) {

                                seenPicture =
                                    String(seenPicture);

                                if (
                                    !/^https?:\/\//i
                                        .test(seenPicture) &&
                                    !seenPicture
                                        .startsWith('/')
                                ) {

                                    seenPicture =
                                        `/storage/${
                                            seenPicture.replace(
                                                /^storage\//,
                                                ''
                                            )
                                        }`;
                                }

                                conversationSeenHtml = `
                                    <img
                                        src="${escapeHtml(
                                            seenPicture
                                        )}"
                                        alt="${escapeHtml(
                                            name
                                        )}"
                                        data-tooltip="Seen by ${escapeHtml(
                                            name
                                        )}"
                                        class="
                                            h-4
                                            w-4
                                            shrink-0
                                            rounded-full
                                            object-cover
                                        "
                                        onerror="
                                            this.outerHTML =
                                            '<div class=&quot;h-4 w-4 shrink-0 rounded-full bg-gray-300 flex items-center justify-center text-[7px] font-semibold text-gray-600&quot;>${escapeHtml(initials)}</div>'
                                        "
                                    >
                                `;

                            } else {

                                conversationSeenHtml = `
                                    <div
                                        data-tooltip="Seen by ${escapeHtml(
                                            name
                                        )}"
                                        class="
                                            h-4
                                            w-4
                                            shrink-0
                                            rounded-full
                                            bg-gray-300
                                            flex
                                            items-center
                                            justify-center
                                            text-[7px]
                                            font-semibold
                                            text-gray-600
                                        "
                                    >
                                        ${escapeHtml(
                                            initials
                                        )}
                                    </div>
                                `;
                            }
                        }
                    }


                    // =====================================
                    // ACTIVE CONVERSATION
                    // =====================================

                    const isActive =
                        Number(
                            conv.conversation_id
                        ) ===
                        Number(
                            currentConversationId
                        );

                    const activeClass =
                        isActive
                            ? 'bg-gray-100'
                            : '';


                    // =====================================
                    // CONVERSATION AVATAR
                    // =====================================

                    let conversationAvatarHtml = '';


                    // =====================================
                    // GROUP AVATAR
                    //
                    // For now groups use the initials of
                    // the group name.
                    //
                    // Example:
                    // Capstone Team = CT
                    // =====================================

                    if (isGroup) {

                    let groupImage =
                        conv.conversation_image || '';


                    // =================================
                    // GROUP HAS CUSTOM PICTURE
                    // =================================

                    if (groupImage) {

                        groupImage =
                            String(groupImage);

                        if (
                            !/^https?:\/\//i.test(groupImage) &&
                            !groupImage.startsWith('/')
                        ) {

                            groupImage =
                                `/storage/${
                                    groupImage.replace(
                                        /^storage\//,
                                        ''
                                    )
                                }`;
                        }


                        conversationAvatarHtml = `
                            <img
                                src="${escapeHtml(groupImage)}"
                                alt="${escapeHtml(name)}"
                                class="
                                    h-10
                                    w-10
                                    shrink-0
                                    rounded-full
                                    object-cover
                                "
                            >
                        `;

                    } else {

                        // =================================
                        // NO CUSTOM GROUP PICTURE
                        // Keep existing member collage
                        // =================================

                        conversationAvatarHtml = `
                            <div
                                class="
                                    flex
                                    h-[44px]
                                    w-[56px]
                                    shrink-0
                                    items-center
                                    justify-center
                                    overflow-visible
                                "
                            >
                                ${renderDefaultGroupAvatar(
                                    participants,
                                    'small'
                                )}
                            </div>
                        `;
                    }

                } else {

                        // =================================
                        // DIRECT CHAT PROFILE PICTURE
                        // =================================

                        let profilePicture =
                            otherParticipant
                                .user_profile_picture ||
                            otherParticipant
                                .profile_picture ||
                            '';

                        if (profilePicture) {

                            profilePicture =
                                String(profilePicture);

                            if (
                                !/^https?:\/\//i
                                    .test(profilePicture) &&
                                !profilePicture
                                    .startsWith('/')
                            ) {

                                profilePicture =
                                    `/storage/${
                                        profilePicture.replace(
                                            /^storage\//,
                                            ''
                                        )
                                    }`;
                            }

                            conversationAvatarHtml = `
                                <img
                                    src="${escapeHtml(
                                        profilePicture
                                    )}"
                                    alt="${escapeHtml(
                                        name
                                    )}"
                                    class="
                                        h-10
                                        w-10
                                        rounded-full
                                        object-cover
                                    "
                                    onerror="
                                        this.outerHTML =
                                        '<div class=&quot;h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center text-xs font-semibold text-gray-600&quot;>${escapeHtml(initials)}</div>'
                                    "
                                >
                            `;

                        } else {

                            conversationAvatarHtml = `
                                <div
                                    class="
                                        flex
                                        h-10
                                        w-10
                                        items-center
                                        justify-center
                                        rounded-full
                                        bg-gray-100
                                        text-xs
                                        font-semibold
                                        text-gray-600
                                    "
                                >
                                    ${escapeHtml(
                                        initials
                                    )}
                                </div>
                            `;
                        }
                    }


                    // =====================================
                    // STATUS DOT
                    // Groups use a group icon instead.
                    // =====================================

                    const avatarStatusHtml =
                        isGroup
                            ? `
                                <span
                                    class="
                                        absolute
                                        bottom-0
                                        right-0
                                        flex
                                        h-4
                                        w-4
                                        items-center
                                        justify-center
                                        rounded-full
                                        border-2
                                        border-white
                                        bg-gray-200
                                        text-gray-600
                                    "
                                    data-tooltip="Group chat"
                                >
                                    <i
                                        data-lucide="users"
                                        class="h-2.5 w-2.5"
                                    ></i>
                                </span>
                            `
                            : `
                                <span
                                    class="
                                        absolute
                                        bottom-0
                                        right-0
                                        h-3
                                        w-3
                                        rounded-full
                                        border-2
                                        border-white
                                        ${
                                            isOnline
                                                ? 'bg-emerald-500'
                                                : 'bg-gray-400'
                                        }
                                    "
                                    data-tooltip="${
                                        isOnline
                                            ? 'Active now'
                                            : 'Offline'
                                    }"
                                ></span>
                            `;


                    // =====================================
                    // HTML
                    // =====================================

                    return `
                        <div
                            class="
                                conversation-item
                                group
                                p-3
                                cursor-pointer
                                transition-all
                                duration-200
                                hover:bg-gray-50
                                ${activeClass}
                            "
                            data-id="${
                                conv.conversation_id
                            }"
                            data-conversation-type="${
                                isGroup
                                    ? 'group'
                                    : 'direct'
                            }"
                        >

                            <div
                                class="
                                    flex
                                    items-start
                                    gap-3
                                "
                            >

                                <div
                                    class="
                                        relative
                                        shrink-0
                                    "
                                >
                                    ${conversationAvatarHtml}

                                    ${avatarStatusHtml}
                                </div>


                                <div
                                    class="
                                        flex-1
                                        min-w-0
                                        pt-0.5
                                    "
                                >

                                    <div
                                        class="
                                            flex
                                            items-center
                                            justify-between
                                            gap-2
                                        "
                                    >

                                        <span
                                            class="
                                                text-sm
                                                font-semibold
                                                text-gray-900
                                                truncate
                                            "
                                        >
                                            ${escapeHtml(name)}
                                        </span>

                                    </div>


                                    <div
                                        class="
                                            flex
                                            items-center
                                            justify-between
                                            gap-2
                                            mt-0.5
                                        "
                                    >

                                        <div
                                            class="
                                                flex
                                                items-center
                                                gap-1
                                                flex-1
                                                min-w-0
                                            "
                                        >

                                            <p
                                                class="
                                                    conversation-preview
                                                    text-xs
                                                    flex
                                                    min-w-0
                                                    flex-1
                                                    items-center
                                                    gap-1
                                                    overflow-hidden
                                                    ${
                                                        unreadCount > 0
                                                            ? 'font-semibold text-gray-900'
                                                            : 'text-gray-500'
                                                    }
                                                "
                                                data-conversation-id="${
                                                    conv.conversation_id
                                                }"
                                                data-original-preview="${
                                                    escapeHtml(preview)
                                                }"
                                            >
                                                ${previewHtml}
                                            </p>


                                            ${
                                                previewTimeSource
                                                    ? `
                                                        <span
                                                            class="
                                                                conversation-relative-time
                                                                shrink-0
                                                                whitespace-nowrap
                                                                text-xs
                                                                font-normal
                                                                text-gray-400
                                                            "
                                                            data-created-at="${
                                                                escapeHtml(
                                                                    previewTimeSource
                                                                )
                                                            }"
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
                                                        class="
                                                            shrink-0
                                                            h-2.5
                                                            w-2.5
                                                            rounded-full
                                                            bg-gray-900
                                                        "
                                                        data-tooltip="${
                                                            unreadCount
                                                        } unread ${
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
                                                                class="
                                                                    conversation-message-status
                                                                    shrink-0
                                                                    text-[10px]
                                                                    text-gray-400
                                                                    font-medium
                                                                "
                                                                data-message-id="${
                                                                    lastMessage.message_id
                                                                }"
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
                                    class="
                                        conversation-options-button
                                        opacity-0
                                        group-hover:opacity-100
                                        h-7
                                        w-7
                                        flex
                                        items-center
                                        justify-center
                                        rounded-full
                                        text-gray-400
                                        hover:text-gray-900
                                        hover:bg-gray-200
                                        transition
                                    "
                                    data-conversation-id="${conv.conversation_id}"
                                    data-conversation-type="${isGroup ? 'group' : 'direct'}"
                                    data-tooltip="More"
                                    aria-label="More conversation options"
                                >
                                    <i
                                        data-lucide="more-vertical"
                                        class="h-4 w-4"
                                    ></i>
                                </button>

                            </div>

                        </div>
                    `;

                }).join('');

            lucideCreateIcons(container);
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

                scheduleLoadModalConversations();
                updateTopbarMessageBadge();

            } catch (error) {

                console.error(
                    'Mark as read error:',
                    error
                );

            }
        }

        // =====================================================
        // OPEN CONVERSATION
        // Supports direct and group conversations
        // =====================================================

        async function openModalConversation(
            conversationId
        ) {

            cancelReply();

            closeConversationMessageSearch();

            // =============================================
            // RESET PREVIOUS CONVERSATION
            // =============================================

            //closeConversationInfoSidebar();

            resetConversationAssets();
            document.getElementById('modalPinnedBanner')?.classList.add('hidden');


            currentConversationId =
                conversationId;
            lastPinNoticeEl = null;
            lastUnpinNoticeEl = null;
            pinNoticeExpiresAt = 0;
            unpinNoticeExpiresAt = 0;

            messagesPage = 1;

            hasMoreMessages = true;
            threadSettled = false;
            stickThreadToBottom = true;
            clearBottomSnapTimers();


            // =============================================
            // ACTIVE LEFT LIST ITEM
            // =============================================

            document
                .querySelectorAll(
                    '#modalConversationsList .conversation-item'
                )
                .forEach(el => {

                    el.classList.toggle(
                        'bg-gray-100',
                        el.dataset.id ==
                            conversationId
                    );
                });


            const chatEmpty =
                document.getElementById(
                    'modalChatEmptyState'
                );

            const messagesContainer =
                document.getElementById(
                    'modalMessagesContainer'
                );

            const chatHeader =
                document.getElementById(
                    'modalChatHeader'
                );

            const composer =
                document.getElementById(
                    'modalComposer'
                );

            const chatArea =
                document.getElementById(
                    'modalChatArea'
                );


            if (chatEmpty) {
                chatEmpty.classList.add(
                    'hidden'
                );
            }

            if (messagesContainer) {
                messagesContainer.classList.remove(
                    'hidden'
                );
            }

            setThreadLoading(true);

            if (chatHeader) {
                chatHeader.classList.remove(
                    'hidden'
                );
            }

            if (composer) {
                composer.classList.remove(
                    'hidden'
                );
                updateComposerActionButton();
            }

            if (chatArea) {

                chatArea.classList.remove(
                    'hidden'
                );

                chatArea.classList.add(
                    'flex',
                    'md:flex'
                );
            }

            showMessagingThreadPane();


            if (messagesAbortController) {
                messagesAbortController.abort();
            }

            messagesAbortController = new AbortController();
            const openSignal = messagesAbortController.signal;
            const openToken = ++conversationOpenToken;

            const messagesPromise = loadModalMessages(
                conversationId,
                false,
                openSignal
            );

            const cachedConversation = findCachedConversation(conversationId);

            if (cachedConversation) {
                currentConversationData = cachedConversation;
                currentConversationType =
                    cachedConversation.conversation_type === 'group'
                        ? 'group'
                        : 'direct';
            }


            // =============================================
            // GET CONVERSATION
            // =============================================

            let conversation = cachedConversation;

            const conversationPromise = fetch(
                `/messages/conversations/${conversationId}`,
                {
                    headers: {
                        'Accept': 'application/json'
                    },
                    signal: openSignal
                }
            )
                .then(response => response.ok ? response.json() : null)
                .catch(error => {
                    if (error.name === 'AbortError') {
                        return null;
                    }
                    return null;
                });

            if (!conversation) {
                const payload = await conversationPromise;
                conversation = payload?.data || null;
            } else {
                conversationPromise.then(payload => {
                    if (
                        openToken !== conversationOpenToken ||
                        !payload?.data
                    ) {
                        return;
                    }
                    currentConversationData = payload.data;
                });
            }

            if (openToken !== conversationOpenToken) {
                return;
            }

            if (!conversation) {
                await messagesPromise;
                return;
            }


            // =============================================
            // SAVE CURRENT CONVERSATION
            // =============================================

            currentConversationData =
                conversation;

            currentConversationType =
                conversation.conversation_type ===
                'group'
                    ? 'group'
                    : 'direct';

            const conversationInfoSidebar =
                document.getElementById(
                    'modalConversationInfoSidebar'
                );

            const conversationInfoIsOpen =
                conversationInfoSidebar &&
                !conversationInfoSidebar.classList.contains(
                    'hidden'
                );

            if (conversationInfoIsOpen) {

                // Update avatar, conversation name,
                // member count, online status, etc.
                refreshConversationInfoProfile();

                // Switch group only controls automatically.
                // For direct chats this hides:
                // Chat members
                // Add people
                // Leave group
                //
                // For group chats it shows them again.
                refreshGroupConversationSidebar();

                // Reset Media and Files because we changed
                // to another conversation.
                resetConversationAssets();

                // Load counts belonging to the NEW conversation.
                await Promise.all([
                    loadConversationAssets(),
                    updatePinnedMessagesCount()
                ]);

                lucideCreateIcons();
            }


            const isGroup =
                currentConversationType ===
                'group';


            const participants =
                Array.isArray(
                    conversation.participants
                )
                    ? conversation.participants
                    : [];


            // =============================================
            // DIRECT CHAT USER
            // =============================================

            const otherParticipant =
                participants.find(
                    participant =>
                        Number(
                            participant.user?.user_id
                        ) !==
                        Number(currentUserId)
                )?.user || {};


            // =============================================
            // GROUP CHAT
            // =============================================

            if (isGroup) {

                const groupName =
                    conversation.conversation_name ||
                    'Group chat';


                // =========================================
                // DO NOT STORE A RANDOM GROUP MEMBER
                // AS THE CURRENT CONVERSATION USER
                // =========================================

                currentConversationUser =
                    null;

                currentConversationUserName =
                    groupName;


                // =========================================
                // MEMBER COUNT
                // =========================================

                const memberCount =
                    participants.length;


                // =========================================
                // CHAT HEADER
                // =========================================

                const title =
                    document.getElementById(
                        'modalChatTitle'
                    );

                const subtitle =
                    document.getElementById(
                        'modalChatSubtitle'
                    );

                const avatar =
                    document.getElementById(
                        'modalChatAvatar'
                    );


                if (title) {

                    title.textContent =
                        groupName;
                }


                if (subtitle) {

                    subtitle.textContent =
                        `${memberCount} ${
                            memberCount === 1
                                ? 'member'
                                : 'members'
                        }`;
                }


                if (avatar) {

                    let groupImage =
                        conversation.conversation_image || '';


                    // =========================================
                    // CUSTOM GROUP PICTURE
                    // =========================================

                    if (groupImage) {

                        groupImage =
                            String(groupImage);

                        if (
                            !/^https?:\/\//i.test(groupImage) &&
                            !groupImage.startsWith('/')
                        ) {

                            groupImage =
                                `/storage/${
                                    groupImage.replace(
                                        /^storage\//,
                                        ''
                                    )
                                }`;
                        }


                        avatar.className = `
                            flex
                            h-10
                            w-10
                            shrink-0
                            items-center
                            justify-center
                            overflow-hidden
                            rounded-full
                            bg-gray-100
                        `;


                        avatar.innerHTML = `
                            <img
                                src="${escapeHtml(groupImage)}"
                                alt="${escapeHtml(groupName)}"
                                class="
                                    h-full
                                    w-full
                                    object-cover
                                "
                            >
                        `;

                    } else {

                        // =========================================
                        // NO CUSTOM GROUP PICTURE
                        // Keep existing member collage
                        // =========================================

                        avatar.className = `
                            flex
                            h-[44px]
                            w-[56px]
                            shrink-0
                            items-center
                            justify-center
                            overflow-visible
                        `;


                        avatar.innerHTML =
                            renderDefaultGroupAvatar(
                                participants,
                                'small'
                            );
                    }
                }

            } else {

                // =============================================
                // DIRECT CONVERSATION
                // =============================================

                const name =
                    otherParticipant
                        .user_full_name ||
                    'Unknown';


                currentConversationUserName =
                    name;

                currentConversationUser =
                    otherParticipant;


                // =========================================
                // ONLINE STATUS
                // =========================================

                const lastActiveAt =
                    otherParticipant
                        .last_active_at;

                const activityStatus =
                    formatUserActivity(
                        lastActiveAt
                    );


                // =========================================
                // HEADER
                // =========================================

                const title =
                    document.getElementById(
                        'modalChatTitle'
                    );

                const subtitle =
                    document.getElementById(
                        'modalChatSubtitle'
                    );

                const avatar =
                    document.getElementById(
                        'modalChatAvatar'
                    );


                if (title) {

                    title.textContent =
                        name;
                }


                if (subtitle) {

                    subtitle.textContent =
                        activityStatus;
                }


                if (avatar) {

                    avatar.innerHTML =
                        getInitials(name);

                    // =====================================
                    // RESTORE NORMAL DIRECT CHAT STYLE
                    // =====================================

                    avatar.className = `
                        h-9
                        w-9
                        shrink-0
                        rounded-full
                        bg-gradient-to-br
                        from-emerald-100
                        to-emerald-200
                        flex
                        items-center
                        justify-center
                        text-xs
                        font-semibold
                        text-emerald-700
                    `;
                }
            }


            // =============================================
            // UPDATE CONVERSATION INFO SIDEBAR
            // =============================================

            refreshConversationInfoProfile();


            // =============================================
            // LOAD MESSAGES IN PARALLEL WITH HEADER
            // =============================================

            await messagesPromise;

            if (openToken !== conversationOpenToken) {
                return;
            }

            threadSettled = true;

            markConversationAsRead(
                conversationId
            );


            // =============================================
            // REAL TIME
            // =============================================

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

            const time = date.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit'
            });

            const now = new Date();
            const startOfToday = new Date(
                now.getFullYear(),
                now.getMonth(),
                now.getDate()
            );
            const startOfMessageDay = new Date(
                date.getFullYear(),
                date.getMonth(),
                date.getDate()
            );
            const dayDiff = Math.round(
                (startOfToday - startOfMessageDay) / 86400000
            );

            if (dayDiff === 0) {
                return time;
            }

            if (dayDiff === 1) {
                return `Yesterday ${time}`;
            }

            return `${date.toLocaleDateString('en-US', {
                weekday: 'short',
                month: 'short',
                day: 'numeric'
            })} ${time}`;
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

        function getPinActionIconHtml(isPinned, sizeClass = 'h-4 w-4') {
            const box = `${sizeClass} message-pin-icon shrink-0`;
            if (isPinned) {
                return `
                    <span class="inline-flex shrink-0 text-gray-500">
                        <svg class="${box}" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="2" y1="2" x2="22" y2="22"></line>
                            <path d="M9 9v1.8a2 2 0 0 1-1.1 1.8l-1.8.9A2 2 0 0 0 5 15.2V16a1 1 0 0 0 1 1h8"></path>
                            <path d="M15 9.3V7a1 1 0 0 1 1-1 2 2 0 0 0 0-4H8"></path>
                            <path d="M12 17v5"></path>
                        </svg>
                    </span>
                `;
            }

            return `
                <span class="inline-flex shrink-0 text-gray-500">
                    <svg class="${box}" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 17v5"></path>
                        <path d="M8 2h8"></path>
                        <path d="M10 2v6.5S10 12 7 14v2h10v-2c-3-2-3-5.5-3-5.5V2"></path>
                    </svg>
                </span>
            `;
        }

        function getMessageMoreMenuHtml(
            isOwn,
            isUnsent,
            isPinned = false,
            canEdit = true
        ) {
            const editItem = isOwn && !isUnsent && canEdit ? `
                <button type="button" class="message-action-item message-edit-btn flex w-full items-center gap-3 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50">
                    <i data-lucide="pencil" class="h-4 w-4"></i><span>Edit</span>
                </button>` : '';
            const destructiveItem = (isOwn && !isUnsent) ? `
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
            const pinItem = !isUnsent ? `
                        <button
                            type="button"
                            class="message-action-item message-pin-btn flex w-full items-center gap-3 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
                        >
                            ${getPinActionIconHtml(isPinned)}
                            <span>
                                ${isPinned ? 'Unpin' : 'Pin'}
                            </span>
                        </button>` : '';

            return `
                <div class="message-more relative">
                    <button type="button" class="message-more-btn flex h-7 w-7 items-center justify-center rounded-full text-gray-400 opacity-0 transition group-hover:opacity-100 hover:bg-gray-100 hover:text-gray-700" data-tooltip="More">
                        <i data-lucide="more-vertical" class="h-4 w-4"></i>
                    </button>
                    <div class="message-more-menu absolute left-1/2 z-50 hidden min-w-[150px] -translate-x-1/2 rounded-xl border border-gray-200 bg-white py-1 shadow-xl">
                        ${destructiveItem}
                        ${forwardItem}
                        ${editItem}
                        ${pinItem}
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
                return `<img src="${escapeHtml(src)}" alt="${escapeHtml(name)}" data-tooltip="Seen by ${escapeHtml(name)}" class="message-seen-avatar h-4 w-4 shrink-0 rounded-full object-cover" onerror="this.outerHTML='<div data-tooltip=&quot;Seen by ${escapeHtml(name)}&quot; class=&quot;message-seen-avatar h-4 w-4 shrink-0 rounded-full bg-gray-300 flex items-center justify-center text-[7px] font-semibold text-gray-600&quot;>${escapeHtml(initials)}</div>'">`;
            }

            return `<div data-tooltip="Seen by ${escapeHtml(name)}" class="message-seen-avatar h-4 w-4 shrink-0 rounded-full bg-gray-300 flex items-center justify-center text-[7px] font-semibold text-gray-600">${escapeHtml(initials)}</div>`;
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
                    '.message-row[data-message-own="1"]'
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

        // =====================================================
        // ATTACHMENT NORMALIZER
        //
        // Upload API returns:
        // name, path, url, type, extension, size
        //
        // Saved Laravel attachment rows return:
        // attachment_name, attachment_path, attachment_url,
        // attachment_type, attachment_extension, attachment_size
        //
        // This converts BOTH formats into one format so images
        // and files are recognized after sending, refreshing,
        // forwarding, and receiving through realtime.
        // =====================================================
        function normalizeAttachment(attachment) {
            if (!attachment) {
                return null;
            }

            const path =
                attachment.path ||
                attachment.attachment_path ||
                '';

            let url =
                attachment.url ||
                attachment.attachment_url ||
                '';

            // =============================================
            // FALLBACK URL
            // If Laravel only returned the stored path,
            // build the public /storage URL automatically.
            // =============================================
            if (!url && path) {
                url = `/storage/${String(path).replace(/^\/+/, '')}`;
            }

            return {
                ...attachment,

                name:
                    attachment.name ||
                    attachment.attachment_name ||
                    'Attachment',

                path,

                url,

                type:
                    attachment.type ||
                    attachment.attachment_type ||
                    '',

                extension:
                    attachment.extension ||
                    attachment.attachment_extension ||
                    '',

                size:
                    attachment.size ??
                    attachment.attachment_size ??
                    0
            };
        }

        function normalizeAttachments(attachments) {
            if (!Array.isArray(attachments)) {
                return [];
            }

            return attachments
                .map(normalizeAttachment)
                .filter(Boolean);
        }

        function attachmentRecordId(attachment) {
            return attachment?.message_attachment_id
                || attachment?.attachment_id
                || attachment?.id
                || null;
        }

        function attachmentViewUrl(attachment) {
            const id = attachmentRecordId(attachment);
            const messageId = attachment?.message_id;
            if (currentConversationId && messageId && id) {
                return `/messages/conversations/${currentConversationId}/messages/${messageId}/attachments/${id}/view`;
            }
            return attachment?.url || '#';
        }

        function attachmentDownloadUrl(attachment) {
            const id = attachmentRecordId(attachment);
            const messageId = attachment?.message_id;
            if (currentConversationId && messageId && id) {
                return `/messages/conversations/${currentConversationId}/messages/${messageId}/attachments/${id}/download`;
            }
            return attachment?.url || '#';
        }

        function renderLargePersonAvatar(name, pictureUrl) {
            const initials = escapeHtml(getInitials(name));
            if (pictureUrl) {
                return `
                    <img
                        src="${escapeHtml(pictureUrl)}"
                        alt="${escapeHtml(name)}"
                        class="mb-3 h-24 w-24 rounded-full object-cover"
                    >
                `;
            }

            return `
                <div class="mb-3 flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-emerald-100 to-emerald-200 text-2xl font-semibold text-emerald-700">
                    ${initials}
                </div>
            `;
        }

        function conversationThreadHasMessages(container = null) {
            const root =
                container ||
                document.getElementById('modalMessagesContainer');

            return Boolean(root?.querySelector('.message-row'));
        }

        function renderConversationFirstGlance(conversationEvents = [], hasMessages = null) {
            if (currentConversationType === 'group') {
                return renderGroupFirstGlance(conversationEvents);
            }

            const name =
                currentConversationUser?.user_full_name ||
                currentConversationUser?.name ||
                currentConversationUserName ||
                'Conversation';
            const picture = getConversationInfoPictureUrl(
                currentConversationUser || {}
            );

            const hasThread =
                hasMessages === null
                    ? conversationThreadHasMessages()
                    : Boolean(hasMessages);

            const subtitle = hasThread
                ? `This conversation is between you and ${escapeHtml(name)}.`
                : 'You can now message and call each other and see info like Active Status and when you\'ve read messages.';

            return `
                <div class="conversation-first-glance group-first-glance flex w-full shrink-0 flex-col items-center px-4 pb-8 pt-6 text-center">
                    ${renderLargePersonAvatar(name, picture)}
                    <h3 class="max-w-[90%] text-lg font-semibold text-gray-900">${escapeHtml(name)}</h3>
                    <p class="mt-1 max-w-sm text-sm text-gray-500">
                        ${subtitle}
                    </p>
                </div>
            `;
        }

        function ensureConversationFirstGlance(container, conversationEvents = [], forceInsert = false, hasMessages = null) {
            if (!container) return;

            const threadHasMessages =
                hasMessages === null
                    ? conversationThreadHasMessages(container)
                    : Boolean(hasMessages);

            const html = renderConversationFirstGlance(
                conversationEvents,
                threadHasMessages
            );
            if (!html) return;

            const existing = container.querySelector('.conversation-first-glance');
            if (existing) {
                existing.outerHTML = html;
                lucideCreateIcons(container);
                return;
            }

            if (!forceInsert && hasMoreMessages) {
                return;
            }

            container.insertAdjacentHTML('afterbegin', html);
            lucideCreateIcons(container);
        }

        // =====================================================
        // GROUP FIRST GLANCE
        //
        // Permanent group introduction.
        //
        // Always stays at the beginning of a group conversation,
        // even when the group already contains messages.
        // =====================================================
        function renderGroupFirstGlance(conversationEvents = []) {
            if (currentConversationType !== 'group') {
                return '';
            }

            const participants = Array.isArray(currentConversationData?.participants)
                ? currentConversationData.participants
                : [];

            const groupName = currentConversationData?.conversation_name || 'Group chat';
            const createdEvent =
                Array.isArray(conversationEvents)
                    ? conversationEvents.find(
                        event =>
                            event.event_type === 'group_created'
                    )
                    : null;


            // =====================================================
            // CREATOR NAME
            // =====================================================

            let creatorName = 'Someone';

            if (createdEvent) {

                const creatorId =
                    Number(createdEvent.actor_user_id);

                const creatorIsMe =
                    creatorId ===
                    Number(currentUserId);


                // =============================================
                // CURRENT USER CREATED THE GROUP
                // =============================================

                if (creatorIsMe) {

                    creatorName = 'You';

                }


                // =============================================
                // LARAVEL RETURNED CREATOR NAME
                // =============================================

                else if (
                    createdEvent.actor_name &&
                    String(createdEvent.actor_name).trim()
                ) {

                    creatorName =
                        String(createdEvent.actor_name).trim();

                }


                // =============================================
                // FALLBACK
                //
                // Find creator from conversation participants.
                // =============================================

                else {

                    const creatorParticipant =
                        participants.find(participant => {

                            const user =
                                participant?.user || {};

                            return (
                                Number(user.user_id) ===
                                creatorId
                            );
                        });


                    const creatorUser =
                        creatorParticipant?.user || {};


                    creatorName =
                        creatorUser.user_full_name ||
                        creatorUser.name ||
                        'Someone';
                }
            }

            const groupImage = normalizeConversationImageUrl(
                currentConversationData?.conversation_image ||
                currentConversationData?.conversation_image_url ||
                ''
            );

            const avatarHtml = groupImage
                ? `
                    <img
                        src="${escapeHtml(groupImage)}"
                        alt="${escapeHtml(groupName)}"
                        class="mb-3 h-24 w-24 rounded-full object-cover"
                    >
                `
                : `<div class="relative mb-3">${renderDefaultGroupAvatar(participants, 'large')}</div>`;

            return `
                <div class="conversation-first-glance group-first-glance flex w-full shrink-0 flex-col items-center px-4 pb-8 pt-5 text-center">
                    ${avatarHtml}
                    <h3 class="max-w-[90%] text-lg font-semibold text-gray-900">${escapeHtml(groupName)}</h3>
                    <p class="mt-1 text-sm text-gray-500">${escapeHtml(creatorName)} created this group</p>
                    <div class="mt-5 flex items-start justify-center gap-8">
                        <button
                            type="button"
                            data-group-action="add"
                            class="group flex flex-col items-center gap-1.5 text-sm font-medium text-gray-700"
                        >
                            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 transition group-hover:bg-gray-200">
                                <i data-lucide="user-plus" class="h-5 w-5"></i>
                            </span>

                            <span>Add</span>
                        </button>

                        <button
                            type="button"
                            data-group-action="rename"
                            class="group flex flex-col items-center gap-1.5 text-sm font-medium text-gray-700"
                        >
                            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 transition group-hover:bg-gray-200">
                                <i data-lucide="pencil" class="h-5 w-5"></i>
                            </span>

                            <span>Name</span>
                        </button>
                    </div>
                </div>
            `;
        }

        function removeGroupFirstGlance() {

            // Do nothing.
            // The group introduction must always remain.

            return;
        }

        function openRenameGroupModal() {
            if (currentConversationType !== 'group' || !currentConversationId) return;
            const modal = document.getElementById('renameGroupModal');
            const input = document.getElementById('renameGroupInput');
            const error = document.getElementById('renameGroupError');
            if (!modal || !input) return;
            error?.classList.add('hidden');
            input.value = currentConversationData?.conversation_name || '';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => { input.focus(); input.select(); }, 50);
        }

        function closeRenameGroupModal() {
            const modal = document.getElementById('renameGroupModal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        async function saveRenamedGroup() {
            if (!currentConversationId) return;
            const input = document.getElementById('renameGroupInput');
            const error = document.getElementById('renameGroupError');
            const name = input?.value.trim() || '';
            if (!name) {
                if (error) { error.textContent = 'Enter a group name.'; error.classList.remove('hidden'); }
                return;
            }

            const response = await fetch(`/messages/conversations/${currentConversationId}/name`, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ conversation_name: name })
            });
            const data = await response.json();
            if (!response.ok) {
                if (error) { error.textContent = data.message || 'Unable to rename the group.'; error.classList.remove('hidden'); }
                return;
            }

            currentConversationData = data.data;
            closeRenameGroupModal();
            refreshConversationInfoProfile();
            refreshGroupConversationSidebar();
            document.getElementById('modalChatTitle').textContent = name;
            document.querySelector('#modalMessagesContainer .group-first-glance h3')?.replaceChildren(document.createTextNode(name));
            await loadModalConversations();
        }

        async function refreshOpenGroupAfterActivity() {
            if (!currentConversationId || currentConversationType !== 'group') return;
            const response = await fetch(`/messages/conversations/${currentConversationId}`, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) return;
            const data = await response.json();
            if (!data.data || Number(data.data.conversation_id) !== Number(currentConversationId)) return;
            currentConversationData = data.data;
            refreshConversationInfoProfile();
            refreshGroupConversationSidebar();
            ensureConversationFirstGlance(
                document.getElementById('modalMessagesContainer')
            );
        }

        function appendRealtimeConversationActivity(event) {
            if (!event || Number(event.conversation_id) !== Number(currentConversationId)) return;
            const container = document.getElementById('modalMessagesContainer');
            if (!container) return;
            const eventId = Number(event.conversation_event_id || 0);
            if (eventId && container.querySelector(`.conversation-event-row[data-event-id="${eventId}"]`)) return;

            const html = renderConversationEventRow({ ...event, item_type: 'conversation_event' });
            if (!html) return;
            const typingIndicator = document.getElementById('modalTypingIndicator');
            if (typingIndicator) typingIndicator.insertAdjacentHTML('beforebegin', html);
            else container.insertAdjacentHTML('beforeend', html);

            moveTypingIndicatorAfterLastMessage();
            lucideCreateIcons();
            scrollToBottom(false, true);
            refreshOpenGroupAfterActivity();
        }

        // =====================================================
        // RENDER GROUP CONVERSATION EVENT
        // Examples:
        // You created the group chat.
        // Kenn Mehares created the group chat.
        // You added Leo Legitimas to the group.
        // Kenn Mehares added you to the group.
        // Kenn Mehares added Leo Legitimas to the group.
        // You left the group.
        // Leo Legitimas left the group.
        // =====================================================

        function renderConversationEventRow(event) {

            if (!event) {
                return '';
            }


            // =================================================
            // CHECK WHO PERFORMED THE ACTION
            // =================================================

            const actorIsMe =
                Number(event.actor_user_id) ===
                Number(currentUserId);


            // =================================================
            // CHECK IF THE TARGET USER IS ME
            //
            // Example:
            // Kenn adds Leo.
            //
            // If Leo is logged in:
            // targetIsMe = true
            //
            // Result:
            // "Kenn Mehares added you to the group."
            // =================================================

            const targetIsMe =
                event.target_user_id !== null &&
                event.target_user_id !== undefined &&
                Number(event.target_user_id) ===
                Number(currentUserId);


            // =================================================
            // ACTOR NAME
            // =================================================

            const actorName =
                actorIsMe
                    ? 'You'
                    : (
                        event.actor_name ||
                        'Someone'
                    );


            // =================================================
            // TARGET NAME
            // =================================================

            const targetName =
                targetIsMe
                    ? 'you'
                    : (
                        event.target_name ||
                        'someone'
                    );


            let text = '';


            // =================================================
            // GROUP CREATED
            // =================================================

            if (
                event.event_type ===
                'group_created'
            ) {

                text = actorIsMe
                    ? 'You created the group chat.'
                    : `${actorName} created the group chat.`;
            }


            // =================================================
            // MEMBER ADDED
            // =================================================

            else if (
                event.event_type ===
                'member_added'
            ) {

                // =============================================
                // I ADDED SOMEONE
                //
                // Example:
                // You added Leo Legitimas to the group.
                // =============================================

                if (actorIsMe) {

                    text =
                        `You added ${targetName} to the group.`;
                }


                // =============================================
                // SOMEONE ADDED ME
                //
                // Example when Leo is logged in:
                // Kenn Mehares added you to the group.
                // =============================================

                else if (targetIsMe) {

                    text =
                        `${actorName} added you to the group.`;
                }


                // =============================================
                // SOMEONE ADDED ANOTHER PERSON
                //
                // Example:
                // Kenn Mehares added Leo Legitimas to the group.
                // =============================================

                else {

                    text =
                        `${actorName} added ${targetName} to the group.`;
                }
            }


            // =================================================
            // MEMBER LEFT
            // =================================================

            else if (
                event.event_type ===
                'member_left'
            ) {

                text = actorIsMe
                    ? 'You left the group.'
                    : `${actorName} left the group.`;
            }


            // =================================================
            // UNKNOWN EVENT
            // =================================================

            if (!text) {
                return '';
            }


            // =================================================
            // SYSTEM EVENT UI
            //
            // IMPORTANT:
            // This is NOT a normal chat message.
            //
            // Therefore it has:
            // no reactions
            // no reply
            // no edit
            // no pin
            // no forward
            // no 3-dot menu
            // =================================================

            return `
                <div
                    class="
                        conversation-event-row
                        flex
                        w-full
                        justify-center
                        px-6
                        py-2
                    "
                    data-event-id="${
                        Number(
                            event.conversation_event_id
                        )
                    }"
                    data-created-at="${
                        escapeHtml(
                            String(
                                event.created_at || ''
                            )
                        )
                    }"
                >
                    <p
                        class="
                            max-w-[75%]
                            text-center
                            text-xs
                            leading-5
                            text-gray-500
                        "
                    >
                        ${escapeHtml(text)}
                    </p>
                </div>
            `;
        }

        function renderMessengerMessageRow(msg, isOwn) {
            
            if (
                msg.message_type === 'call' &&
                typeof renderCallMessageRow === 'function'
            ) {
                return renderCallMessageRow(msg, isOwn);
            }
            const senderName = msg.sender?.name || msg.sender?.user_full_name || (isOwn ? 'You' : 'Unknown');

            const rawAttachments = Array.isArray(msg.attachments)
                ? msg.attachments
                : (msg.attachment ? [msg.attachment] : []);

            const isUnsent = Boolean(msg.is_unsent);
            const attachments = isUnsent
                ? []
                : normalizeAttachments(rawAttachments);
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
            // =============================================
            // INTERNAL ATTACHMENT MARKERS ARE NOT CHAT TEXT
            // Never show these markers inside message bubbles.
            // =============================================
            const attachmentMarkers = [
                '[attachment:image]',
                '[attachment:file]',
                '[attachment:multiple]'
            ];

            const displayContent =
                attachmentMarkers.includes(content)
                    ? ''
                    : content;
            const isLikeSticker = isLikeStickerContent(displayContent) && attachments.length === 0;
            const hasText = Boolean(displayContent.trim()) && !isLikeSticker;
            const hasAttachments = attachments.length > 0;
            const isPinned =
            msg.is_pinned === true ||
            msg.is_pinned === 1 ||
            msg.is_pinned === '1';
            const avatar = isOwn ? '' : getMessageAvatarHtml(msg, senderName);
            const replyButton = `
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
                        data-tooltip="Reply"
                    >
                        <i
                            data-lucide="reply"
                            class="h-4 w-4"
                        ></i>
                    </button>
            `;
            const moreMenu = getMessageMoreMenuHtml(
                isOwn,
                isUnsent,
                isPinned,
                hasText
            );
            const hoverActions = isOwn
                ? `${moreMenu}${replyButton}${getReactionPickerHtml()}`
                : `${getReactionPickerHtml()}${replyButton}${moreMenu}`;
            const actions = isUnsent ? `
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
                    ${getMessageMoreMenuHtml(
                        isOwn,
                        true,
                        false,
                        false
                    )}
                </div>
            ` : `
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
                    ${hoverActions}
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

                    ${isPinned
                        ? `
                            <div
                                class="
                                    message-pinned-label
                                    mb-1
                                    flex
                                    items-center
                                    gap-1
                                    px-1
                                    text-[11px]
                                    text-gray-400
                                "
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
                        : ''
                    }


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

                            ${isUnsent
                                ? `
                                    <!-- ================================= -->
                                    <!-- UNSENT MESSAGE -->
                                    <!-- ================================= -->

                                    <div
                                        class="
                                            message-bubble
                                            relative
                                            z-10
                                            inline-flex
                                            w-fit
                                            max-w-full
                                            flex-col
                                            rounded-2xl
                                            border
                                            border-gray-300
                                            bg-white
                                            px-3.5
                                            py-2
                                            text-gray-500
                                        "
                                        data-tooltip="${escapeHtml(fullTime)}"
                                    >
                                        <p class="text-sm italic">
                                            ${isOwn
                                                ? 'You deleted a message'
                                                : 'This message was unsent'}
                                        </p>
                                    </div>
                                `
                                : `
                                    <!-- ================================= -->
                                    <!-- TEXT MESSAGE -->
                                    <!-- ================================= -->

                                    ${isLikeSticker
                                        ? `
                                            <div
                                                class="
                                                    message-bubble
                                                    message-like-sticker
                                                    relative
                                                    z-10
                                                    inline-flex
                                                    w-fit
                                                    items-center
                                                    justify-center
                                                    bg-transparent
                                                    p-0
                                                    ${isOwn ? 'self-end' : 'self-start'}
                                                "
                                                data-tooltip="${escapeHtml(fullTime)}"
                                            >
                                                ${messengerLikeIconHtml('h-14 w-14')}
                                            </div>
                                        `
                                        : hasText
                                        ? `
                                            <div
                                                class="
                                                    message-bubble
                                                    relative
                                                    z-10
                                                    inline-flex
                                                    w-fit
                                                    max-w-full
                                                    flex-col

                                                    ${isOwn
                                                        ? 'self-end rounded-2xl rounded-br-sm bg-gray-900 text-white'
                                                        : 'self-start rounded-2xl rounded-bl-sm bg-gray-100 text-gray-900'
                                                    }

                                                    px-3.5
                                                    py-2
                                                "
                                                data-tooltip="${escapeHtml(fullTime)}"
                                            >
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
                                            </div>
                                        `
                                        : ''
                                    }


                                    <!-- ================================= -->
                                    <!-- ATTACHMENTS -->
                                    <!--
                                        Important:
                                        NO gray/black message bubble around images.
                                    -->
                                    <!-- ================================= -->

                                    ${hasAttachments
                                        ? `
                                            <div
                                                class="
                                                    message-attachments
                                                    relative
                                                    z-10
                                                    w-fit
                                                    max-w-full
                                                    ${hasText ? 'mt-1' : ''}
                                                    ${isOwn ? 'self-end' : 'self-start'}
                                                "
                                                data-tooltip="${escapeHtml(fullTime)}"
                                            >
                                                ${getAttachmentsMessageHtml(
                                                    attachments,
                                                    formatMessageTime(msg.created_at),
                                                    msg.message_id
                                                )}
                                            </div>
                                        `
                                        : ''
                                    }
                                `
                            }


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
                                            ${Array.isArray(msg.reactions) && msg.reactions.length
                                                ? ''
                                                : 'hidden'
                                            }
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

                    ${isOwn
                        ? `
                            <div
                                class="
                                    message-status-wrapper
                                    mt-2
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


        function renderCallMessageRow(msg, isOwn) {

            const call = msg.call || {};
            const fullTime = getFullMessageDateTime(msg.created_at);
            const callType = normalizeCallType(call.call_type);
            const title = getCallCardTitle(call);
            const subtitle = getCallCardSubtitle(call);
            const iconName = callType === 'video' ? 'video' : 'phone';
            const targetUserId = isOwn
                ? Number(call.receiver_id || 0)
                : Number(call.caller_id || 0);

            const subtitleHtml = subtitle
                ? `
                    <p class="mt-0.5 text-xs text-gray-500">
                        ${escapeHtml(subtitle)}
                    </p>
                `
                : '';

            return `
                <div class="
                    message-row
                    flex
                    ${isOwn ? 'justify-end' : 'justify-start'}
                    my-2
                "
                data-message-id="${Number(msg.message_id || 0)}">

                    <div class="
                        w-[min(100%,280px)]
                        overflow-hidden
                        rounded-2xl
                        border
                        border-gray-200
                        bg-white
                        shadow-sm
                    " data-tooltip="${escapeHtml(fullTime)}">

                        <div class="flex items-start gap-3 px-4 py-3">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-700">
                                <i data-lucide="${iconName}" class="h-4 w-4"></i>
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-gray-900">
                                    ${escapeHtml(title)}
                                </p>

                                ${subtitleHtml}

                                <p class="mt-1 text-xs text-gray-400">
                                    ${formatMessageTime(msg.created_at)}
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="call-again-button w-full border-t border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-semibold text-gray-900 transition hover:bg-gray-100"
                            data-target-user-id="${targetUserId}"
                            data-call-type="${escapeHtml(callType)}"
                        >
                            Call again
                        </button>

                    </div>

                </div>
            `;
        }



        

        function closeAllMessageMenus(except = null) {
            document.querySelectorAll('.message-more-menu').forEach(menu => {
                if (menu !== except) {
                    menu.classList.add('hidden');
                    menu.classList.remove(
                        'top-full',
                        'bottom-full',
                        'mt-2',
                        'mb-2',
                        'message-more-menu-up',
                        'message-more-menu-down'
                    );
                    menu.style.visibility = '';
                }
            });
        }

        function positionMessageMoreMenu(button, menu) {
            const thread = document.getElementById('modalMessagesContainer');
            const area = thread?.getBoundingClientRect() || {
                top: 0,
                bottom: window.innerHeight
            };
            const trigger = button.getBoundingClientRect();

            menu.classList.remove(
                'hidden',
                'top-full',
                'bottom-full',
                'mt-2',
                'mb-2',
                'message-more-menu-up',
                'message-more-menu-down'
            );
            menu.style.visibility = 'hidden';

            requestAnimationFrame(() => {
                const menuHeight = menu.getBoundingClientRect().height || 0;
                const gap = 10;
                const roomAbove = trigger.top - area.top;
                const openDown = roomAbove < menuHeight + gap;

                if (openDown) {
                    menu.classList.add('top-full', 'mt-2', 'message-more-menu-down');
                } else {
                    menu.classList.add('bottom-full', 'mb-2', 'message-more-menu-up');
                }

                menu.style.visibility = '';
                lucideCreateIcons(menu);
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
                lucideCreateIcons(overlay);
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
            updateComposerActionButton();
        }

        function startEditMessage(row) {
            const content = String(row?.dataset.messageContent || '').trim();
            const attachmentMarkers = [
                '[attachment:image]',
                '[attachment:file]',
                '[attachment:multiple]'
            ];
            if (!content || attachmentMarkers.includes(content)) {
                return;
            }

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
                <button type="button" id="modalCancelEdit" class="flex h-7 w-7 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100" data-tooltip="Cancel edit">
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
            updateComposerActionButton();
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

        function replaceThreadMessageRow(row, msg, isOwn) {
            const holder = document.createElement('div');
            holder.innerHTML = renderMessengerMessageRow(msg, isOwn).trim();
            const next = holder.firstElementChild;
            if (!next) return;
            row.replaceWith(next);
            lucideCreateIcons();
            refreshMessageDateSeparators();
            refreshSeenAvatar();
            scheduleLoadModalConversations();
        }

        function messagePayloadFromRow(row, extra = {}) {
            const isOwn = row.dataset.messageOwn === '1';
            return {
                message_id: row.dataset.messageId,
                conversation_id: currentConversationId,
                sender_id: isOwn ? currentUserId : row.dataset.messageSenderId,
                sender: {
                    user_id: isOwn ? currentUserId : row.dataset.messageSenderId,
                    user_full_name: row.dataset.messageSender || (isOwn ? 'You' : 'Unknown')
                },
                message_content: row.dataset.messageContent || '',
                created_at: row.dataset.messageCreatedAt || '',
                is_unsent: row.dataset.messageUnsent === '1',
                attachments: [],
                reactions: [],
                ...extra
            };
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
                lucideCreateIcons(overlay);

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
                await removeMessageFromRow(row, true);
                return;
            }

            const response = await fetch(`/messages/conversations/${currentConversationId}/messages/${row.dataset.messageId}/unsend`, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            });
            if (!response.ok) return;

            replaceThreadMessageRow(
                row,
                messagePayloadFromRow(row, {
                    is_unsent: true,
                    message_content: '',
                    attachments: [],
                    reactions: [],
                    is_pinned: false,
                    reply_to: null
                }),
                true
            );
        }

        async function removeMessageFromRow(row, skipConfirm = false) {
            if (!skipConfirm) {
                const confirmed = await showMessageActionDialog({
                    title: 'Remove for you',
                    text: 'This will remove the message from your devices. Other chat members will still be able to see it.',
                    confirmText: 'Remove', danger: false
                });
                if (!confirmed) return;
            }
            const response = await fetch(`/messages/conversations/${currentConversationId}/messages/${row.dataset.messageId}/remove`, {
                method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            });
            if (!response.ok) return;
            row.remove();
            refreshMessageDateSeparators();
            refreshSeenAvatar();
            scheduleLoadModalConversations();
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

            lucideCreateIcons(modal);
        }


        function closePinnedMessagesModal() {

            const modal =
                document.getElementById('pinnedMessagesModal');

            if (!modal) {
                return;
            }

            modal.classList.add('hidden');
        }

        function getPinnedBannerSnippet(msg) {
            if (!msg || msg.is_unsent) {
                return 'Unsent message';
            }

            const content = String(msg.message_content || '').trim();

            if (isLikeStickerContent(content)) {
                return 'Like';
            }

            if (content === '[attachment:image]') {
                return 'Photo';
            }

            if (content === '[attachment:file]') {
                return 'File';
            }

            if (content === '[attachment:multiple]') {
                return 'Attachments';
            }

            if (content && !content.startsWith('[attachment:')) {
                return content.length > 72 ? `${content.slice(0, 72)}...` : content;
            }

            const attachments = Array.isArray(msg.attachments) ? msg.attachments : [];
            if (attachments.length) {
                const type = String(attachments[0].attachment_type || attachments[0].type || '');
                return type.startsWith('image') ? 'Photo' : 'File';
            }

            return 'Pinned message';
        }

        function updatePinnedHeaderBar(messages) {
            const banner = document.getElementById('modalPinnedBanner');
            const senderEl = document.getElementById('modalPinnedBannerSender');
            const textEl = document.getElementById('modalPinnedBannerText');

            if (!banner || !senderEl || !textEl) {
                return;
            }

            const pins = Array.isArray(messages) ? messages : [];
            if (!pins.length) {
                banner.classList.add('hidden');
                return;
            }

            const latest = pins[0];
            const isMine = Number(latest.sender_id) === Number(currentUserId);
            const senderName = latest.sender?.user_full_name || latest.sender?.name || 'Someone';

            senderEl.textContent = isMine ? 'You' : senderName;
            textEl.textContent = getPinnedBannerSnippet(latest);
            banner.classList.remove('hidden');
            lucideCreateIcons(banner);
        }

        async function restorePinSystemNotice() {

            const container =
                document.getElementById(
                    'modalMessagesContainer'
                );

            if (
                !container ||
                !currentConversationId
            ) {
                return;
            }

            if (Date.now() < pinNoticeExpiresAt && lastPinNoticeEl && container.contains(lastPinNoticeEl)) {
                return;
            }

            container
                .querySelectorAll('.pin-system-notice')
                .forEach(notice => {
                    notice.remove();
                });

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
                    return;
                }

                const result =
                    await response.json();

                const messages =
                    Array.isArray(result.data)
                        ? result.data
                        : [];

                updatePinnedHeaderBar(messages);

                const persisted = readPersistedPinNotices(
                    currentConversationId
                );

                if (persisted.length) {
                    renderPersistedPinNotices(container, persisted);
                    return;
                }

                if (!messages.length) {
                    if (Date.now() >= pinNoticeExpiresAt) {
                        lastPinNoticeEl = null;
                        pinNoticeExpiresAt = 0;
                    }
                    return;
                }

                const pinEvents = Array.isArray(result.pins) && result.pins.length
                    ? result.pins
                    : messages;

                const clusters = groupPinNoticesByWindow(pinEvents);
                let lastInserted = null;

                clusters.forEach(cluster => {
                    const afterRow = getPinNoticeInsertAnchor(
                        container,
                        cluster.lastAt
                    );

                    if (!afterRow) {
                        return;
                    }

                    const notice = buildPinSystemNotice(true);
                    insertPinNoticeAfterRow(notice, afterRow);
                    lastInserted = {
                        notice,
                        lastAt: cluster.lastAt,
                    };
                });

                if (
                    lastInserted &&
                    Date.now() - lastInserted.lastAt <= PIN_NOTICE_WINDOW_MS
                ) {
                    lastInserted.notice.dataset.pinGroup = 'active';
                    lastPinNoticeEl = lastInserted.notice;
                    pinNoticeExpiresAt = lastInserted.lastAt + PIN_NOTICE_WINDOW_MS;
                }


            } catch (error) {

                console.error(
                    'Unable to restore pin notice:',
                    error
                );
            }
        }


        async function updatePinnedMessagesCount() {

            const countElement =
                document.getElementById(
                    'modalConversationPinnedCount'
                );

            if (!countElement) {
                return;
            }

            // No active conversation.
            if (!currentConversationId) {
                countElement.textContent = '0';
                return;
            }

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
                    countElement.textContent = '0';
                    return;
                }

                const result =
                    await response.json();

                const messages =
                    Array.isArray(result.data)
                        ? result.data
                        : [];

                countElement.textContent =
                    String(messages.length);

            } catch (error) {

                console.error(
                    'Unable to load pinned message count:',
                    error
                );

                countElement.textContent = '0';
            }
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
                    Array.isArray(result.data)
                        ? result.data
                        : [];

                if (!messages.length) {

                    container.innerHTML = `
                        <div class="py-10 text-center text-sm text-gray-400">
                            No pinned messages.
                        </div>
                    `;

                    return;
                }


                // =====================================================
                // CREATE EACH PINNED MESSAGE
                // =====================================================

                container.innerHTML =
                    messages.map(message => {

                        const sender =
                            message.sender || {};

                        const isOwn =
                            Number(
                                sender.user_id ??
                                message.sender_id
                            ) === Number(currentUserId);

                        const senderName =
                            isOwn
                                ? 'You'
                                : (
                                    sender.user_full_name ||
                                    sender.name ||
                                    'User'
                                );

                        const initials =
                            getInitials(senderName);


                        // =================================================
                        // PROFILE PICTURE
                        // =================================================

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
                                    `/storage/${picture.replace(
                                        /^storage\//,
                                        ''
                                    )}`;
                            }

                            avatarHtml = `
                                <img
                                    src="${escapeHtml(picture)}"
                                    alt="${escapeHtml(senderName)}"
                                    class="
                                        h-8
                                        w-8
                                        shrink-0
                                        rounded-full
                                        object-cover
                                    "
                                >
                            `;

                        } else {

                            avatarHtml = `
                                <div
                                    class="
                                        flex
                                        h-8
                                        w-8
                                        shrink-0
                                        items-center
                                        justify-center
                                        rounded-full
                                        bg-gray-200
                                        text-[10px]
                                        font-semibold
                                        text-gray-600
                                    "
                                >
                                    ${escapeHtml(initials)}
                                </div>
                            `;
                        }


                        // =================================================
                        // NORMALIZE ATTACHMENTS
                        // Same attachment system as the main conversation.
                        // =================================================

                        const rawAttachments =
                            Array.isArray(message.attachments)
                                ? message.attachments
                                : (
                                    message.attachment
                                        ? [message.attachment]
                                        : []
                                );

                        const attachments =
                            normalizeAttachments(rawAttachments);

                        const hasAttachments =
                            attachments.length > 0;


                        // =================================================
                        // REMOVE INTERNAL ATTACHMENT MARKERS
                        // =================================================

                        const attachmentMarkers = [
                            '[attachment:image]',
                            '[attachment:file]',
                            '[attachment:multiple]'
                        ];

                        const rawContent =
                            message.message_content || '';

                        const displayContent =
                            attachmentMarkers.includes(rawContent)
                                ? ''
                                : rawContent;

                        const hasText =
                            Boolean(displayContent.trim());


                        // =================================================
                        // ACTUAL MESSAGE CONTENT
                        // =================================================

                        let messageContentHtml = '';


                        // =================================================
                        // TEXT
                        // =================================================

                        if (hasText) {

                            messageContentHtml += `
                                <div
                                    class="
                                        w-fit
                                        max-w-full
                                        rounded-2xl
                                        bg-gray-100
                                        px-3.5
                                        py-2
                                        text-sm
                                        leading-snug
                                        text-gray-900
                                    "
                                >
                                    <span
                                        class="
                                            whitespace-pre-wrap
                                            break-words
                                        "
                                    >${escapeHtml(displayContent)}</span>
                                </div>
                            `;
                        }


                        // =================================================
                        // IMAGES / FILES / DOCUMENTS
                        //
                        // Reuses your normal Messenger attachment renderer.
                        // =================================================

                        if (hasAttachments) {

                            messageContentHtml += `
                                <div
                                    class="
                                        ${hasText ? 'mt-1.5' : ''}
                                        w-fit
                                        max-w-full
                                    "
                                >
                                    ${getAttachmentsMessageHtml(
                                        attachments,
                                        formatMessageTime(
                                            message.created_at
                                        ),
                                        message.message_id
                                    )}
                                </div>
                            `;
                        }


                        // =================================================
                        // FALLBACK
                        // Only appears if Laravel returned neither text
                        // nor attachment information.
                        // =================================================

                        if (!hasText && !hasAttachments) {

                            messageContentHtml = `
                                <div
                                    class="
                                        rounded-2xl
                                        bg-gray-100
                                        px-3.5
                                        py-2
                                        text-sm
                                        text-gray-500
                                    "
                                >
                                    Message
                                </div>
                            `;
                        }


                        // =================================================
                        // PINNED MESSAGE CARD
                        // =================================================

                        return `
                            <div
                                class="
                                    border-b
                                    border-gray-100
                                    py-4
                                    last:border-0
                                "
                            >

                                <!-- ===================================== -->
                                <!-- SENDER + TIME -->
                                <!-- ===================================== -->

                                <div
                                    class="
                                        mb-2
                                        flex
                                        items-center
                                        justify-between
                                        gap-3
                                        pl-10
                                    "
                                >
                                    <span
                                        class="
                                            text-xs
                                            text-gray-500
                                        "
                                    >
                                        ${escapeHtml(senderName)}
                                    </span>

                                    <span
                                        class="
                                            text-xs
                                            text-gray-400
                                        "
                                    >
                                        ${formatTime(
                                            message.created_at
                                        )}
                                    </span>
                                </div>


                                <!-- ===================================== -->
                                <!-- AVATAR + ACTUAL MESSAGE -->
                                <!-- ===================================== -->

                                <div
                                    class="
                                        flex
                                        items-end
                                        gap-2
                                    "
                                >

                                    ${avatarHtml}

                                    <div
                                        class="
                                            min-w-0
                                            max-w-[80%]
                                        "
                                    >
                                        ${messageContentHtml}
                                    </div>

                                </div>

                            </div>
                        `;

                    }).join('');


                // Rebuild Lucide icons used by file cards.
                lucideCreateIcons(document.getElementById('pinnedMessagesModal'));

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


                // =====================================================
                // UPDATE PIN / UNPIN ICON IMMEDIATELY
                // =====================================================

                const pinIcon =
                    row.querySelector(
                        '.message-pin-btn .message-pin-icon, .message-pin-btn i, .message-pin-btn svg'
                    );

                if (pinIcon) {
                    pinIcon.outerHTML = getPinActionIconHtml(
                        data.is_pinned
                    );
                }


                // =====================================================
                // UPDATE MESSAGE PIN STATE IN DOM
                // =====================================================

                row.dataset.messagePinned =
                    data.is_pinned
                        ? '1'
                        : '0';


                // =============================================
                // SHOW MESSENGER STYLE SYSTEM NOTICE
                // =============================================

                showPinSystemNotice(
                    row,
                    data.is_pinned
                );
                applyConversationPinPreview(
                    currentConversationId,
                    data.is_pinned
                );

                updatePinnedMessagesCount();
                fetch(
                    `/messages/conversations/${currentConversationId}/pinned`,
                    { headers: { 'Accept': 'application/json' } }
                )
                    .then(response => response.ok ? response.json() : null)
                    .then(result => {
                        updatePinnedHeaderBar(
                            Array.isArray(result?.data) ? result.data : []
                        );
                    })
                    .catch(() => {});

            } catch (error) {

                console.error(
                    'Unable to pin/unpin message:',
                    error
                );
            }
        }

        // =====================================================
        // PIN SYSTEM NOTICE
        // In-thread system line, like Messenger. Newer messages
        // continue below it instead of leaving it stuck at the end.
        // =====================================================

        function conversationPinPreviewStorageKey() {
            return `sti-prism-conversation-pin-preview:${currentUserId}`;
        }

        function readConversationPinPreviews() {
            try {
                const raw = localStorage.getItem(
                    conversationPinPreviewStorageKey()
                );
                const data = JSON.parse(raw || '{}');
                return data && typeof data === 'object' ? data : {};
            } catch (error) {
                return {};
            }
        }

        function setConversationPinPreview(conversationId, kind) {
            if (!conversationId || !kind) {
                return;
            }

            const all = readConversationPinPreviews();
            all[String(conversationId)] = {
                kind,
                at: new Date().toISOString(),
            };
            localStorage.setItem(
                conversationPinPreviewStorageKey(),
                JSON.stringify(all)
            );
        }

        function getConversationPinPreview(conversationId, lastMessageAt) {
            const entry = readConversationPinPreviews()[
                String(conversationId)
            ];

            if (!entry?.kind || !entry?.at) {
                return null;
            }

            const pinAt = new Date(entry.at).getTime();
            const messageAt = lastMessageAt
                ? new Date(lastMessageAt).getTime()
                : 0;

            if (Number.isNaN(pinAt)) {
                return null;
            }

            if (messageAt && messageAt > pinAt + 2000) {
                return null;
            }

            return entry;
        }

        function applyConversationPinPreview(conversationId, isPinned) {
            setConversationPinPreview(
                conversationId,
                isPinned ? 'pinned' : 'unpinned'
            );
            conversationsRenderKey = '';
            if (conversationsCache) {
                renderModalConversations(conversationsCache);
            }
        }

        function pinNoticeStorageKey(conversationId) {
            return `sti-prism-pin-notices:${currentUserId}:${conversationId}`;
        }

        function readPersistedPinNotices(conversationId) {
            try {
                const raw = localStorage.getItem(
                    pinNoticeStorageKey(conversationId)
                );
                const list = JSON.parse(raw || '[]');
                return Array.isArray(list) ? list : [];
            } catch (error) {
                return [];
            }
        }

        function getMessageRowBeforeNode(node) {
            let current = node;
            while (current) {
                if (current.classList?.contains('message-row')) {
                    return current;
                }
                current = current.previousElementSibling;
            }
            return null;
        }

        function snapshotPinSystemNotices(container) {
            if (!currentConversationId || !container) {
                return;
            }

            const events = Array.from(
                container.querySelectorAll('.pin-system-notice')
            ).map(notice => {
                const messageRow = getMessageRowBeforeNode(notice);
                return {
                    kind: notice.dataset.pinKind === 'unpinned'
                        ? 'unpinned'
                        : 'pinned',
                    at: Number(notice.dataset.pinCreatedAt || Date.now()),
                    afterMessageId: String(
                        notice.dataset.afterMessageId ||
                        messageRow?.dataset?.messageId ||
                        ''
                    ),
                };
            });

            localStorage.setItem(
                pinNoticeStorageKey(currentConversationId),
                JSON.stringify(events)
            );
        }

        function renderPersistedPinNotices(container, events) {
            events.forEach(event => {
                const isPinned = event.kind !== 'unpinned';
                const notice = buildPinSystemNotice(
                    isPinned,
                    isPinned
                );
                notice.dataset.pinCreatedAt = String(
                    event.at || Date.now()
                );
                if (event.afterMessageId) {
                    notice.dataset.afterMessageId = String(
                        event.afterMessageId
                    );
                }

                insertPinNoticeAfterRow(
                    notice,
                    getPersistedNoticeAnchor(container, event)
                );
            });
        }

        function buildPinSystemNotice(isPinned, showSeeAll = true) {
            const notice = document.createElement('div');
            notice.className = 'pin-system-notice flex justify-center py-3';
            notice.dataset.pinKind = isPinned ? 'pinned' : 'unpinned';
            notice.dataset.pinCreatedAt = String(Date.now());
            notice.innerHTML = `
                <div class="flex items-center gap-1.5 text-xs text-gray-400">
                    <span>${isPinned ? 'You pinned a message.' : 'You unpinned a message.'}</span>
                    ${showSeeAll ? `
                        <button
                            type="button"
                            class="pinned-see-all font-semibold text-blue-600 hover:underline"
                        >
                            See all
                        </button>
                    ` : ''}
                </div>
            `;
            return notice;
        }

        function getLastThreadMessageRow(container, asOfTime = null) {
            const rows = Array.from(
                container.querySelectorAll('.message-row[data-message-id]')
            );

            if (!rows.length) {
                return null;
            }

            if (asOfTime == null || Number.isNaN(asOfTime)) {
                return rows[rows.length - 1];
            }

            let afterRow = null;
            for (const row of rows) {
                const rowTime = new Date(row.dataset.messageCreatedAt || '').getTime();
                if (!Number.isNaN(rowTime) && rowTime <= asOfTime) {
                    afterRow = row;
                }
            }

            return afterRow || rows[rows.length - 1];
        }

        function getPinNoticeInsertAnchor(container, asOfTime = null) {
            const lastMessage = getLastThreadMessageRow(container, asOfTime);
            if (!lastMessage) {
                return Array.from(
                    container.querySelectorAll('.pin-system-notice')
                ).pop() || null;
            }

            let anchor = lastMessage;
            let sibling = lastMessage.nextElementSibling;
            while (
                sibling &&
                sibling.classList.contains('pin-system-notice')
            ) {
                anchor = sibling;
                sibling = sibling.nextElementSibling;
            }

            return anchor;
        }

        function getPersistedNoticeAnchor(container, event) {
            const messageId = String(event?.afterMessageId || '');
            const messageRow = messageId
                ? container.querySelector(
                    `.message-row[data-message-id="${CSS.escape(messageId)}"]`
                )
                : getLastThreadMessageRow(container, event?.at);

            if (!messageRow) {
                return getPinNoticeInsertAnchor(container, event?.at);
            }

            let anchor = messageRow;
            let sibling = messageRow.nextElementSibling;
            while (
                sibling &&
                sibling.classList.contains('pin-system-notice')
            ) {
                anchor = sibling;
                sibling = sibling.nextElementSibling;
            }

            return anchor;
        }

        function insertPinNoticeAfterRow(notice, row) {
            if (!notice || !row || !row.parentNode) {
                return;
            }

            row.after(notice);
        }

        function groupPinNoticesByWindow(pinsNewestFirst = []) {
            const pins = [...pinsNewestFirst]
                .reverse()
                .filter(pin => pin?.pinned_at);

            const clusters = [];

            pins.forEach(pin => {
                const at = new Date(pin.pinned_at).getTime();
                if (Number.isNaN(at)) {
                    return;
                }

                const last = clusters[clusters.length - 1];
                if (last && at - last.lastAt <= PIN_NOTICE_WINDOW_MS) {
                    last.pins.push(pin);
                    last.lastPin = pin;
                    last.lastAt = at;
                    return;
                }

                clusters.push({
                    pins: [pin],
                    lastPin: pin,
                    lastAt: at,
                });
            });

            return clusters;
        }

        // =====================================================
        // PIN / UNPIN NOTICE 10-SECOND WINDOW
        //
        // Each kind has its own line and timer:
        // - "You pinned a message. See all"
        // - "You unpinned a message. See all"
        //
        // Another action of the SAME kind within 10 seconds
        // reuses that line and restarts the countdown.
        // A new line is created only after 10 seconds.
        // Pin and unpin notices can both stay in the thread.
        // =====================================================

        function getNoticeKind(isPinned) {
            return isPinned ? 'pinned' : 'unpinned';
        }

        function getActiveNoticeEl(kind) {
            return kind === 'unpinned' ? lastUnpinNoticeEl : lastPinNoticeEl;
        }

        function getNoticeExpiry(kind, notice) {
            const stored = kind === 'unpinned' ? unpinNoticeExpiresAt : pinNoticeExpiresAt;
            const fromNotice = Number(notice?.dataset?.pinExpiresAt || 0);
            return Math.max(stored, fromNotice);
        }

        function getActivePinNotice(container, kind = 'pinned') {
            const current = getActiveNoticeEl(kind);
            if (current && container.contains(current) && current.dataset.pinKind === kind) {
                return current;
            }

            return container.querySelector(
                `.pin-system-notice[data-pin-kind="${kind}"][data-pin-group="active"]`
            );
        }

        function isWithinPinNoticeWindow(kind, notice) {
            return getNoticeExpiry(kind, notice) > Date.now();
        }

        function shouldReusePinNotice(container, kind = 'pinned') {
            const activeNotice = getActivePinNotice(container, kind);
            return Boolean(activeNotice && isWithinPinNoticeWindow(kind, activeNotice));
        }

        function extendPinNoticeWindow(notice, kind = 'pinned') {
            const expiresAt = Date.now() + PIN_NOTICE_WINDOW_MS;

            if (kind === 'unpinned') {
                unpinNoticeExpiresAt = expiresAt;
                lastUnpinNoticeEl = notice || lastUnpinNoticeEl;
            } else {
                pinNoticeExpiresAt = expiresAt;
                lastPinNoticeEl = notice || lastPinNoticeEl;
            }

            if (notice) {
                notice.dataset.pinKind = kind;
                notice.dataset.pinGroup = 'active';
                notice.dataset.pinExpiresAt = String(expiresAt);
            }
        }

        function closeActivePinNotices(container, kind = 'pinned') {
            container
                .querySelectorAll(
                    `.pin-system-notice[data-pin-kind="${kind}"][data-pin-group="active"]`
                )
                .forEach(notice => {
                    notice.dataset.pinGroup = 'closed';
                    delete notice.dataset.pinExpiresAt;
                });
        }

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

                    const messageContent =
                        row.querySelector(
                            '.message-bubble, .message-attachments'
                        );

                    if (messageContent) {

                        messageContent.insertAdjacentHTML(
                            'beforebegin',
                            `
                                <div
                                    class="
                                        message-pinned-label
                                        mb-1
                                        flex
                                        items-center
                                        gap-1
                                        text-[11px]
                                        text-gray-400
                                    "
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
            // ADD SYSTEM NOTICE UNDER THAT MESSAGE
            // Pins within 10 seconds share one notice.
            // Each pin inside that window resets the timer.
            // =========================================

            let noticeToShow = null;
            const kind = getNoticeKind(isPinned);

            if (shouldReusePinNotice(container, kind)) {
                const activeNotice = getActivePinNotice(container, kind);
                extendPinNoticeWindow(activeNotice, kind);
                noticeToShow = activeNotice;
            } else {
                closeActivePinNotices(container, kind);

                const lastMessage = getLastThreadMessageRow(container);
                const notice = buildPinSystemNotice(isPinned);
                if (lastMessage?.dataset?.messageId) {
                    notice.dataset.afterMessageId = String(
                        lastMessage.dataset.messageId
                    );
                }
                insertPinNoticeAfterRow(
                    notice,
                    getPinNoticeInsertAnchor(container)
                );
                extendPinNoticeWindow(notice, kind);
                noticeToShow = notice;
            }

            lucideCreateIcons(row || container);
            snapshotPinSystemNotices(container);
            scrollPinNoticeIntoView(noticeToShow);
        }

        function scrollPinNoticeIntoView(notice) {
            const container = document.getElementById('modalMessagesContainer');
            if (!container || !notice || !container.contains(notice)) {
                return;
            }

            stickThreadToBottom = true;

            requestAnimationFrame(() => {
                const noticeRect = notice.getBoundingClientRect();
                const containerRect = container.getBoundingClientRect();
                const offset =
                    noticeRect.top -
                    containerRect.top -
                    (container.clientHeight / 2) +
                    (noticeRect.height / 2);

                container.scrollTo({
                    top: container.scrollTop + offset,
                    behavior: 'smooth'
                });

                notice.classList.remove('pin-system-notice-flash');
                void notice.offsetWidth;
                notice.classList.add('pin-system-notice-flash');

                setTimeout(() => {
                    notice.classList.remove('pin-system-notice-flash');
                }, 1600);
            });
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
            // =========================================================
            // FORWARD MODAL MUST SIT ABOVE THE FULL SCREEN IMAGE
            //
            // Image preview uses z-[99999].
            // Therefore Forward needs a higher z-index.
            //
            // Result:
            // Messages modal
            //      ↓
            // Full screen image preview
            //      ↓
            // Forward popup
            // =========================================================
            overlay.className = 'fixed inset-0 z-[100000] flex items-center justify-center bg-black/55 p-4';
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
            lucideCreateIcons(overlay);

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

        function findCachedConversation(conversationId) {
            const page = conversationsCache?.data || conversationsCache;
            const list = Array.isArray(page) ? page : [];

            return list.find(item =>
                Number(item.conversation_id) === Number(conversationId)
            ) || null;
        }

        function setThreadLoading(visible) {
            const loader = document.getElementById('modalThreadLoading');
            if (!loader) return;

            loader.classList.toggle('hidden', !visible);
            loader.classList.toggle('flex', visible);
            loader.setAttribute('aria-hidden', visible ? 'false' : 'true');
        }

        async function loadModalMessages(conversationId, append = false, signal = null) {
            if (append && (isLoadingMessages || !hasMoreMessages)) {
                return;
            }

            if (!append) {
                isLoadingMessages = false;
                hasMoreMessages = true;
            }

            if (isLoadingMessages) {
                return;
            }

            isLoadingMessages = true;

            try {
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

            let response;

            try {
                response = await fetch(`/messages/conversations/${conversationId}/messages?page=${messagesPage}`, {
                    headers: {
                        'Accept': 'application/json'
                    },
                    signal: signal || undefined
                });
            } catch (error) {
                isLoadingMessages = false;
                if (error.name === 'AbortError') {
                    return;
                }
                return;
            }

            if (Number(conversationId) !== Number(currentConversationId)) {
                isLoadingMessages = false;
                return;
            }
            if (!response.ok) {
                isLoadingMessages = false;
                return;
            }

            const data = await response.json();
            const messages = data.data;

            const conversationEvents =
                Array.isArray(
                    data.conversation_events
                )
                    ? data.conversation_events
                    : [];

            hasMoreMessages = Boolean(messages.next_page_url) || (
                Number(messages.current_page || 1) <
                Number(messages.last_page || 1)
            );

            // =====================================================
            // NO MESSAGES YET
            // KEEP TYPING INDICATOR ALIVE
            // =====================================================

            if (
                messages.data.length === 0 &&
                conversationEvents.length === 0 &&
                !append
            ) {

                const typingIndicator =
                    document.getElementById('modalTypingIndicator');

                ensureConversationFirstGlance(
                    container,
                    conversationEvents,
                    true,
                    false
                );

                // Keep typing indicator last
                if (typingIndicator) {
                    container.appendChild(typingIndicator);
                }

                lucideCreateIcons(container);

                isLoadingMessages = false;

                return;
            }

            if (
                messages.data.length === 0 &&
                conversationEvents.length === 0
            ) {

                hasMoreMessages = false;
                ensureConversationFirstGlance(
                    container,
                    conversationEvents,
                    true
                );
                isLoadingMessages = false;

                return;
            }


            // =====================================================
            // THERE MAY BE EVENTS BUT ZERO NORMAL MESSAGES
            // =====================================================

            if (messages.data.length === 0) {
                hasMoreMessages = false;
            }

            const userId = {{ auth()->id() }};
            const orderedMessages =
                [...messages.data]
                    .reverse()
                    .map(message => ({
                        ...message,

                        // =========================================
                        // IDENTIFY NORMAL MESSAGE
                        // =========================================

                        item_type:
                            'message'
                    }));


            // =====================================================
            // CONVERSATION EVENTS
            //
            // Only insert them on the initial load.
            //
            // We do not insert the entire event history again when
            // loading older paginated messages.
            // =====================================================

            const eventItems =
                !append
                    ? conversationEvents.map(event => ({
                        ...event,

                        item_type:
                            'conversation_event'
                    }))
                    : [];


            // =====================================================
            // COMBINE MESSAGES + EVENTS
            // =====================================================

            const conversationItems = [
                ...orderedMessages,
                ...eventItems
            ];


            // =====================================================
            // CHRONOLOGICAL ORDER
            //
            // Example:
            //
            // You created the group chat.
            // 2:00 PM message
            // You added Leo.
            // 2:05 PM message
            // =====================================================

            conversationItems.sort(
                (a, b) => {

                    const first =
                        new Date(
                            a.created_at
                        ).getTime();

                    const second =
                        new Date(
                            b.created_at
                        ).getTime();

                    return first - second;
                }
            );


            // =====================================================
            // RENDER CORRECT TYPE
            // =====================================================

            const firstGlanceHtml =
                (!append && !hasMoreMessages)
                    ? renderConversationFirstGlance(
                        conversationEvents,
                        conversationItems.some(item => item.item_type === 'message')
                    )
                    : '';

            const html =
                firstGlanceHtml +
                conversationItems
                    .map(item => {

                        // =========================================
                        // GROUP ACTIVITY
                        // =========================================

                        if (
                            item.item_type ===
                            'conversation_event'
                        ) {

                            return renderConversationEventRow(
                                item
                            );
                        }


                        // =========================================
                        // NORMAL CHAT MESSAGE
                        // =========================================

                        const isOwn =
                            Number(
                                item.sender?.user_id ??
                                item.sender_id
                            ) ===
                            Number(userId);


                        return renderMessengerMessageRow(
                            item,
                            isOwn
                        );
                    })
                    .join('');

            // =====================================================
            // INSERT MESSAGES WITHOUT DESTROYING TYPING INDICATOR
            // =====================================================

            if (append) {

                const glance = container.querySelector('.conversation-first-glance');
                if (glance) {
                    glance.insertAdjacentHTML('afterend', html);
                } else {
                    container.insertAdjacentHTML(
                        'afterbegin',
                        html
                    );
                }

                if (!hasMoreMessages) {
                    ensureConversationFirstGlance(
                        container,
                        conversationEvents,
                        true
                    );
                }

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

            lucideCreateIcons(container);
            refreshSeenAvatar();

            moveTypingIndicatorAfterLastMessage();

            if (!append) {
                stickThreadToBottom = true;
                await restorePinSystemNotice();
                await waitForThreadImages(container);
                await settleOpenedThread(container);
            }

            isLoadingMessages = false;
            } finally {
                isLoadingMessages = false;
                if (!append) {
                    setThreadLoading(false);
                }
            }
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


            // =================================================
            // GET THE OUTER POSITIONED REACTION CONTAINER
            // =================================================

            const reactionContainer =
                row.querySelector(
                    '.message-content-wrapper > .message-reactions'
                );

            if (!reactionContainer) {
                return;
            }


            // =================================================
            // GENERATE NEW REACTION HTML
            // =================================================

            reactionContainer.innerHTML =
                getMessageReactionsHtml(reactions);

            if (
                Array.isArray(reactions) &&
                reactions.length > 0
            ) {
                reactionContainer.classList.remove('hidden');
            } else {
                reactionContainer.classList.add('hidden');
            }
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
                if (!preview.dataset.originalPreviewHtml) {
                    preview.dataset.originalPreviewHtml = preview.innerHTML;
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

                if (preview.dataset.originalPreviewHtml) {
                    preview.innerHTML = preview.dataset.originalPreviewHtml;
                    delete preview.dataset.originalPreviewHtml;
                } else {
                    preview.textContent =
                        preview.dataset.originalPreview || '';
                }
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
                check: '✅',
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
                return '';
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
                                gap-px
                                bg-transparent
                                border-0
                                p-0
                                leading-none
                                ${reactedByMe ? 'is-own-reaction' : ''}
                            "
                            data-reaction="${escapeHtml(type)}"
                            data-reactions="${encodeURIComponent(
                                JSON.stringify(items)
                            )}"
                            data-tooltip="${escapeHtml(tooltip)}"
                        >

                            <span class="message-reaction-emoji">
                                ${emoji}
                            </span>

                            ${
                                items.length > 1
                                    ? `<span class="message-reaction-count">${items.length}</span>`
                                    : ''
                            }

                        </button>
                    `;
                })
                .join('');


            return html;
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


            lucideCreateIcons(overlay);


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
                        data-tooltip="React"
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
                            data-tooltip="Like"
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
                            data-tooltip="Heart"
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
                            data-tooltip="Check"
                        >
                            ✅
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

            if (message === '[attachment:multiple]') {
                message = 'Attachments';
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
                    data-tooltip="View original message"
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

                    // A real message is the ONLY thing that removes the group first glance.
                    removeGroupFirstGlance();

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
                    ensureConversationFirstGlance(container, [], false, true);


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
                // REALTIME GROUP ACTIVITY
                // member added / member left
                // =====================================================
                .listen('.conversation.activity', (event) => {
                    appendRealtimeConversationActivity(event);
                })

                // =====================================================
                // REALTIME GROUP RENAME
                // =====================================================
                .listen('.conversation.renamed', async (event) => {

                    if (
                        !event ||
                        Number(event.conversation_id) !==
                        Number(currentConversationId)
                    ) {
                        return;
                    }

                    if (currentConversationData) {
                        currentConversationData.conversation_name =
                            event.conversation_name;
                    }

                    const title =
                        document.getElementById(
                            'modalChatTitle'
                        );

                    if (title) {
                        title.textContent =
                            event.conversation_name ||
                            'Group chat';
                    }

                    const introTitle =
                        document.querySelector(
                            '#modalMessagesContainer .group-first-glance h3'
                        );

                    if (introTitle) {
                        introTitle.textContent =
                            event.conversation_name ||
                            'Group chat';
                    }

                    refreshConversationInfoProfile();

                    await loadModalConversations();
                })

                .listen('.conversation.image.updated', async (event) => {

                    if (!event) {
                        return;
                    }


                    const conversationId =
                        Number(event.conversation_id);


                    // =================================================
                    // UPDATE OPEN CONVERSATION
                    // =================================================

                    if (
                        conversationId ===
                        Number(currentConversationId)
                    ) {

                        if (currentConversationData) {

                            currentConversationData
                                .conversation_image =
                                event.conversation_image ||
                                event.conversation_image_url ||
                                '';
                        }


                        // =============================================
                        // CONVERSATION INFO PROFILE
                        // =============================================

                        refreshConversationInfoProfile();


                        // =============================================
                        // MAIN CHAT HEADER
                        // Reload the conversation so the header receives
                        // the new conversation_image too.
                        // =============================================

                        await refreshOpenGroupAfterActivity();
                    }


                    // =================================================
                    // UPDATE LEFT CONVERSATION LIST
                    // =================================================

                    await loadModalConversations();
                })

                // =====================================================
                // REALTIME EDIT / UNSEND
                // =====================================================

                .listen('.message.updated', (event) => {
                    const msg = event.message;
                    if (!msg || Number(msg.conversation_id) !== Number(currentConversationId)) return;

                    const row = document.querySelector(`.message-row[data-message-id="${msg.message_id}"]`);
                    if (!row) {
                        scheduleLoadModalConversations();
                        return;
                    }

                    const isOwn = Number(msg.sender_id) === Number(currentUserId);
                    const merged = {
                        message_id: msg.message_id,
                        conversation_id: msg.conversation_id,
                        sender_id: msg.sender_id,
                        sender: {
                            user_id: msg.sender_id,
                            user_full_name: row.dataset.messageSender || (isOwn ? 'You' : 'Unknown')
                        },
                        message_content: msg.is_unsent ? '' : msg.message_content,
                        created_at: row.dataset.messageCreatedAt || msg.updated_at,
                        is_unsent: msg.is_unsent,
                        is_edited: msg.is_edited,
                        attachments: msg.is_unsent ? [] : undefined,
                        reactions: [],
                        reply_to: msg.is_unsent ? null : undefined
                    };

                    replaceThreadMessageRow(row, merged, isOwn);
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


        // =====================================================
        // MESSENGER STYLE JUMP TO LATEST BUTTON
        //
        // Hidden while viewing the newest messages.
        // Appears once the user scrolls upward.
        // Clicking it always returns to the latest message.
        // =====================================================

        function updateScrollToLatestButton() {

            const container =
                document.getElementById('modalMessagesContainer');

            const button =
                document.getElementById('modalScrollToLatestButton');

            if (!container || !button) {
                return;
            }

            const threadOpen =
                Boolean(currentConversationId) &&
                !container.classList.contains('hidden');

            const distanceFromBottom =
                container.scrollHeight -
                container.scrollTop -
                container.clientHeight;

            const shouldShow =
                threadOpen &&
                distanceFromBottom > 120;

            button.classList.toggle('opacity-0', !shouldShow);
            button.classList.toggle('translate-y-2', !shouldShow);
            button.classList.toggle('pointer-events-none', !shouldShow);

            button.classList.toggle('opacity-100', shouldShow);
            button.classList.toggle('translate-y-0', shouldShow);
            button.classList.toggle('pointer-events-auto', shouldShow);
        }


        function jumpToLatestMessage() {

            const container =
                document.getElementById('modalMessagesContainer');

            if (!container) {
                return;
            }

            stickThreadToBottom = true;
            animateThreadToBottom();
        }


        const modalMessagesScrollContainer =
            document.getElementById('modalMessagesContainer');

        const modalScrollToLatestButton =
            document.getElementById('modalScrollToLatestButton');


        modalMessagesScrollContainer?.addEventListener(
            'scroll',
            updateScrollToLatestButton,
            {
                passive: true
            }
        );


        modalScrollToLatestButton?.addEventListener(
            'click',
            jumpToLatestMessage
        );


        function cancelThreadScrollAnimation() {
            if (threadScrollAnimation) {
                cancelAnimationFrame(threadScrollAnimation);
                threadScrollAnimation = null;
            }
        }

        function clearBottomSnapTimers() {
            bottomSnapTimers.forEach(id => clearTimeout(id));
            bottomSnapTimers = [];
            cancelThreadScrollAnimation();
        }

        function withProgrammaticScroll(fn) {
            ignoreProgrammaticScroll = true;
            fn();
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    ignoreProgrammaticScroll = false;
                });
            });
        }

        function snapThreadToBottom() {
            const container = document.getElementById('modalMessagesContainer');
            if (!container) {
                return;
            }

            withProgrammaticScroll(() => {
                container.scrollTop = container.scrollHeight;
            });
        }

        async function settleOpenedThread(container) {
            if (!container) {
                return;
            }

            stickThreadToBottom = true;

            const waitFrame = () => new Promise(resolve => requestAnimationFrame(resolve));

            for (let i = 0; i < 12; i++) {
                snapThreadToBottom();
                if (container.clientHeight > 48) {
                    break;
                }
                await waitFrame();
            }

            snapThreadToBottom();
            setThreadLoading(false);
            await waitFrame();
            if (stickThreadToBottom) {
                snapThreadToBottom();
            }
        }

        function easeOutCubic(t) {
            return 1 - Math.pow(1 - t, 3);
        }

        function animateThreadToBottom(duration = 360) {
            const container = document.getElementById('modalMessagesContainer');
            if (!container) {
                return;
            }

            cancelThreadScrollAnimation();
            ignoreProgrammaticScroll = true;

            const maxScroll = () => Math.max(
                0,
                container.scrollHeight - container.clientHeight
            );

            const end = maxScroll();
            const current = container.scrollTop;
            if (end <= 8 || (end - current) <= 24) {
                container.scrollTop = end;
                ignoreProgrammaticScroll = false;
                return;
            }

            const start = current;
            const startedAt = performance.now();

            const step = (now) => {
                const target = maxScroll();
                const progress = Math.min(1, (now - startedAt) / duration);
                container.scrollTop =
                    start + ((target - start) * easeOutCubic(progress));

                if (progress < 1) {
                    threadScrollAnimation = requestAnimationFrame(step);
                    return;
                }

                container.scrollTop = maxScroll();
                threadScrollAnimation = null;
                requestAnimationFrame(() => {
                    ignoreProgrammaticScroll = false;
                });
            };

            threadScrollAnimation = requestAnimationFrame(step);
        }

        function forceScrollThreadToBottom(smooth = false) {
            clearBottomSnapTimers();
            if (smooth) {
                animateThreadToBottom();
                return;
            }
            snapThreadToBottom();
        }

        async function waitForThreadImages(container, timeoutMs = 800) {
            if (!container) {
                return;
            }

            const images = Array.from(container.querySelectorAll('img'))
                .filter(image => !image.complete);

            if (!images.length) {
                return;
            }

            await Promise.race([
                Promise.all(images.map(image => new Promise(resolve => {
                    image.addEventListener('load', resolve, { once: true });
                    image.addEventListener('error', resolve, { once: true });
                }))),
                new Promise(resolve => setTimeout(resolve, timeoutMs))
            ]);
        }

        function scrollToBottom(smooth = false, force = false) {
            const container = document.getElementById('modalMessagesContainer');
            if (!container) return;

            const isNearBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 120;

            if (!force && !isNearBottom) {
                return;
            }

            container.scrollTop = container.scrollHeight;
        }

        async function scrollOpenedConversationToBottom() {

            const container =
                document.getElementById(
                    'modalMessagesContainer'
                );

            if (!container) {
                return;
            }


            // =====================================================
            // WAIT FOR IMAGES INSIDE THE OPENED CONVERSATION
            // =====================================================

            const images =
                Array.from(
                    container.querySelectorAll('img')
                );

            const pendingImages =
                images.filter(
                    image => !image.complete
                );

            if (pendingImages.length > 0) {

                await Promise.all(
                    pendingImages.map(
                        image =>
                            new Promise(resolve => {

                                image.addEventListener(
                                    'load',
                                    resolve,
                                    { once: true }
                                );

                                image.addEventListener(
                                    'error',
                                    resolve,
                                    { once: true }
                                );

                            })
                    )
                );
            }


            // =====================================================
            // WAIT FOR BROWSER TO FINISH CALCULATING HEIGHTS
            // =====================================================

            await new Promise(resolve =>
                requestAnimationFrame(() =>
                    requestAnimationFrame(resolve)
                )
            );


            // =====================================================
            // GO TO THE ACTUAL BOTTOM
            // =====================================================

            container.scrollTop =
                container.scrollHeight;


            // =====================================================
            // SAFETY CHECK
            //
            // Some attachment layouts can still resize shortly
            // after the image load event.
            // =====================================================

            setTimeout(() => {

                container.scrollTop =
                    container.scrollHeight;

            }, 100);
        }

        function isLikeStickerContent(text) {
            return /^\u{1F44D}[\u{1F3FB}-\u{1F3FF}]?$/u.test(
                String(text || '').trim()
            );
        }

        function messengerLikeIconHtml(sizeClass = 'h-8 w-8') {
            return `
                <svg
                    class="messenger-like-icon ${sizeClass}"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                    aria-hidden="true"
                >
                    <path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z"/>
                </svg>
            `;
        }

        function composerHasSendableContent() {
            const input = document.getElementById('modalMessageInput');
            const text = (input?.value || '').trim();

            return Boolean(text)
                || selectedAttachments.length > 0
                || attachmentsUploading > 0
                || Boolean(editingMessageRow);
        }

        function updateComposerActionButton() {
            const button = document.getElementById('modalSendButton');
            if (!button) return;

            const canSend = composerHasSendableContent();
            const likeIcon = button.querySelector('[data-composer-action="like"]');
            const sendIcon = button.querySelector('[data-composer-action="send"]');

            button.dataset.composerMode = canSend ? 'send' : 'like';
            button.type = canSend ? 'submit' : 'button';
            button.setAttribute('data-tooltip', canSend ? 'Send' : 'Send a like');
            button.setAttribute('aria-label', canSend ? 'Send' : 'Send a like');
            button.classList.toggle('bg-[#0084FF]', canSend);
            button.classList.toggle('text-white', canSend);
            button.classList.toggle('hover:bg-[#0078E8]', canSend);
            button.classList.toggle('bg-gray-900', false);
            button.classList.toggle('hover:bg-gray-800', false);
            button.classList.toggle('bg-transparent', !canSend);
            button.classList.toggle('text-[#0084FF]', !canSend);
            button.classList.toggle('hover:bg-transparent', !canSend);
            button.classList.toggle('hover:opacity-80', !canSend);
            button.classList.toggle('hover:bg-gray-100', false);
            button.classList.toggle('text-gray-900', false);

            likeIcon?.classList.toggle('hidden', canSend);
            sendIcon?.classList.toggle('hidden', !canSend);

            if (canSend) {
                lucideCreateIcons(button);
            }
        }

        async function sendLikeFromComposer() {
            if (composerHasSendableContent() || !currentConversationId) {
                return;
            }

            const input = document.getElementById('modalMessageInput');
            if (input) {
                input.value = '👍';
            }

            await sendModalMessage({
                preventDefault() {},
                stopPropagation() {}
            });
        }

        async function sendModalMessage(e) {
            e.preventDefault();
            e.stopPropagation();

            if (isSendingMessage) {
                return;
            }

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

            if (attachmentsUploading > 0) {
                const started = Date.now();
                while (attachmentsUploading > 0 && Date.now() - started < 12000) {
                    await new Promise(resolve => setTimeout(resolve, 80));
                }
            }

            const outgoingAttachments = selectedAttachments.slice().map(attachment => ({
                name: attachment.name || attachment.attachment_name || 'Attachment',
                path: attachment.path || attachment.attachment_path || '',
                url: attachment.url || attachment.attachment_url || '',
                type: attachment.type || attachment.attachment_type || '',
                extension: attachment.extension || attachment.attachment_extension || '',
                size: attachment.size ?? attachment.attachment_size ?? 0
            }));
            if (!content && !outgoingAttachments.length) return;
            if (!currentConversationId) return;

            const form = document.getElementById('modalMessageForm');
            if (!form) return;

            isSendingMessage = true;

            try {
            if (input) {
                input.value = '';
                input.style.height = 'auto';
                input.focus();
            }

            clearSelectedAttachments();

            const tempId = 'temp-' + Date.now();
            const time = new Date().toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            const container = document.getElementById('modalMessagesContainer');

            const attachmentPreview =
            getAttachmentsMessageHtml(
                outgoingAttachments,
                time
            );

            const hasContent = Boolean(content);
            const hasAttachments = outgoingAttachments.length > 0;

            const tempHtml = `
                <div
                    class="flex justify-end"
                    id="${tempId}"
                    style="animation: messageSlideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1)"
                >
                    <div class="max-w-[70%] opacity-70">

                        ${
                            hasContent && isLikeStickerContent(content)
                                ? `
                                    <div class="ml-auto w-fit bg-transparent p-0">
                                        ${messengerLikeIconHtml('h-14 w-14')}
                                    </div>
                                `
                                : hasContent
                                ? `
                                    <div
                                        class="
                                            ml-auto
                                            w-fit
                                            max-w-full
                                            rounded-2xl
                                            rounded-br-md
                                            bg-gray-900
                                            px-4
                                            py-2.5
                                            text-white
                                        "
                                    >
                                        <p
                                            class="
                                                whitespace-pre-wrap
                                                break-words
                                                text-sm
                                            "
                                        >${escapeHtml(content)}</p>
                                    </div>
                                `
                                : ''
                        }

                        ${
                            hasAttachments
                                ? `
                                    <div class="${hasContent ? 'mt-1.5' : ''}">
                                        ${attachmentPreview}
                                    </div>
                                `
                                : ''
                        }

                        <div
                            class="
                                mt-1
                                text-right
                                text-[10px]
                                text-gray-400
                            "
                        >
                            Sending...
                        </div>

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

            forceScrollThreadToBottom();

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

            if (outgoingAttachments.length) {
                formData.append(
                    'attachments',
                    JSON.stringify(outgoingAttachments)
                );
            }

            let response;

            try {
                response = await fetch(`/messages/conversations/${currentConversationId}/send`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                });
            } catch (error) {
                const temp = document.getElementById(tempId);
                if (temp) temp.remove();
                if (input && !input.value && content) {
                    input.value = content;
                    input.focus();
                }
                if (outgoingAttachments.length) {
                    selectedAttachments = outgoingAttachments;
                    renderSelectedAttachments();
                }
                return;
            }

            if (response.ok) {
                const data = await response.json();

                const msg = data.data || {};
                if (!Array.isArray(msg.attachments) || !msg.attachments.length) {
                    msg.attachments = outgoingAttachments;
                }
                if (!msg.created_at) {
                    msg.created_at = new Date().toISOString();
                }
                const msgTime = formatMessageTime(msg.created_at);
                const attachments = msg.attachments || outgoingAttachments;
                const isFirstMessage = container.querySelector('.text-center.py-12') !== null;
                // =====================================================
                // CREATE REAL SENT MESSAGE
                //
                // This replaces the temporary sending bubble after
                // Laravel successfully saves the message.
                // =====================================================

                // A successfully saved real message removes the group first glance.
                removeGroupFirstGlance();

                let realHtml = '';
                try {
                    realHtml = renderMessengerMessageRow(msg, true);
                } catch (error) {
                    realHtml = `<div class="flex justify-end">${getAttachmentsMessageHtml(attachments, msgTime, msg.message_id)}</div>`;
                }
                const emptyState =
                    container.querySelector(
                        '.message-empty-state, .text-center.py-12'
                    );

                if (emptyState) {
                    emptyState.remove();
                }

                const typingIndicatorAfterSend =
                    document.getElementById(
                        'modalTypingIndicator'
                    );

                try {
                    if (typingIndicatorAfterSend) {
                        typingIndicatorAfterSend.insertAdjacentHTML(
                            'beforebegin',
                            realHtml
                        );
                    } else {
                        container.insertAdjacentHTML(
                            'beforeend',
                            realHtml
                        );
                    }

                    const temp = document.getElementById(tempId);
                    if (temp) temp.remove();
                } catch (error) {
                    const tempKeep = document.getElementById(tempId);
                    if (tempKeep) {
                        const status = tempKeep.querySelector('[class*="text-[10px]"]');
                        if (status) status.textContent = 'Sent';
                    }
                }

                moveTypingIndicatorAfterLastMessage();

                // =====================================================
                // DATE SEPARATORS
                // Also covers the first message sent on a new day.
                // =====================================================

                refreshMessageDateSeparators();
                lucideCreateIcons(container);
                refreshSeenAvatar();
                ensureConversationFirstGlance(container, [], false, true);

                cancelReply();

                if (input) {
                    input.value = '';
                    input.style.height = 'auto';
                    input.focus();
                }

                forceScrollThreadToBottom();
                scheduleLoadModalConversations();
            } else {
                const temp = document.getElementById(tempId);
                if (temp) temp.remove();
                if (input && !input.value && content) {
                    input.value = content;
                    input.focus();
                }
                if (outgoingAttachments.length) {
                    selectedAttachments = outgoingAttachments;
                    renderSelectedAttachments();
                }
            }
            } finally {
                isSendingMessage = false;
                updateComposerActionButton();
            }
        }

        function getAttachmentsMessageHtml(attachments, time, messageId = null) {

            attachments =
                normalizeAttachments(attachments)
                    .map(item => ({
                        ...item,
                        message_id: item.message_id || messageId
                    }));

            if (!attachments.length) {
                return '';
            }

            const images =
                attachments.filter(isImageAttachment);

            const files =
                attachments.filter(
                    attachment =>
                        !isImageAttachment(attachment)
                );

            let html = '';


            // =====================================================
            // MESSENGER STYLE IMAGE GALLERY
            //
            // 1 image:
            // Large natural image
            //
            // 2 images:
            // Two equal images beside each other
            //
            // 3 images:
            // Two images on top
            // One wide image below
            //
            // 4+ images:
            // 2 x 2 gallery
            //
            // Images after the fourth image are represented
            // by a "+N" overlay on the fourth image.
            // =====================================================

            if (images.length) {

                const visibleImages =
                    images.slice(0, 4);

                const remainingImages =
                    Math.max(
                        images.length - 4,
                        0
                    );


                // =================================================
                // ONE IMAGE
                // Large image with its natural aspect ratio.
                // =================================================

                if (images.length === 1) {

                    const attachment =
                        visibleImages[0];

                    html += `
                        <div
                            class="
                                mt-1
                                overflow-hidden
                                rounded-2xl
                                max-w-[320px]
                            "
                        >
                            <button
                                type="button"
                                class="
                                    block
                                    w-full
                                    overflow-hidden
                                    rounded-2xl
                                    bg-gray-100
                                "
                                data-preview-images="${escapeHtml(encodeURIComponent(JSON.stringify(images.map(img => ({
                                        url: img.url || '',
                                        name: img.name || 'Image'
                                    }))))) }"
                                    data-preview-message-id="${messageId ?? ''}"
                                    onclick="openImagePreviewFromMessage(event.currentTarget, 
                                    decodeURIComponent(
                                        '${encodeURIComponent(
                                            String(
                                                attachment.url || ''
                                            )
                                        )}'
                                    ),
                                    decodeURIComponent(
                                        '${encodeURIComponent(
                                            String(
                                                attachment.name ||
                                                'Image'
                                            )
                                        )}'
                                    )
                                )"
                            >
                                <img
                                    src="${escapeHtml(
                                        attachment.url || ''
                                    )}"
                                    alt="${escapeHtml(
                                        attachment.name ||
                                        'Image'
                                    )}"
                                    class="
                                        block
                                        h-auto
                                        max-h-[360px]
                                        min-h-[180px]
                                        w-full
                                        object-contain
                                        bg-gray-100
                                    "
                                >
                            </button>
                        </div>
                    `;

                }


                // =================================================
                // TWO IMAGES
                // Equal side by side gallery.
                // =================================================

                else if (images.length === 2) {

                    html += `
                        <div
                            class="
                                mt-1
                                grid
                                w-[320px]
                                max-w-full
                                grid-cols-2
                                gap-1
                                overflow-hidden
                                rounded-2xl
                            "
                        >
                    `;

                    html += visibleImages
                        .map((attachment, imageIndex) => {

                            return `
                                <button
                                    type="button"
                                    class="
                                        block
                                        h-[210px]
                                        overflow-hidden
                                        bg-gray-100
                                    "
                                    data-preview-images="${escapeHtml(encodeURIComponent(JSON.stringify(images.map(img => ({
                                        url: img.url || '',
                                        name: img.name || 'Image'
                                    }))))) }"
                                    data-preview-message-id="${messageId ?? ''}"
                                    onclick="openImagePreviewFromMessage(event.currentTarget, 
                                        decodeURIComponent(
                                            '${encodeURIComponent(
                                                String(
                                                    attachment.url || ''
                                                )
                                            )}'
                                        ),
                                        decodeURIComponent(
                                            '${encodeURIComponent(
                                                String(
                                                    attachment.name ||
                                                    'Image'
                                                )
                                            )}'
                                        )
                                    )"
                                >
                                    <img
                                        src="${escapeHtml(
                                            attachment.url || ''
                                        )}"
                                        alt="${escapeHtml(
                                            attachment.name ||
                                            'Image'
                                        )}"
                                        class="
                                            h-full
                                            w-full
                                            object-cover
                                        "
                                        loading="eager"
                                    >
                                </button>
                            `;

                        })
                        .join('');

                    html += '</div>';

                }


                // =================================================
                // THREE IMAGES
                //
                // IMG 1 | IMG 2
                // -------------
                //     IMG 3
                // =================================================

                else if (images.length === 3) {

                    html += `
                        <div
                            class="
                                mt-1
                                grid
                                w-[320px]
                                max-w-full
                                grid-cols-2
                                gap-1
                                overflow-hidden
                                rounded-2xl
                            "
                        >
                    `;

                    html += visibleImages
                        .map((attachment, imageIndex) => {

                            // FIX: use the current image's index
                            const isBottomImage =
                                imageIndex === 2;

                            return `
                                <button
                                    type="button"
                                    class="
                                        block
                                        overflow-hidden
                                        bg-gray-100

                                        ${
                                            isBottomImage
                                                ? 'col-span-2 h-[180px]'
                                                : 'h-[180px]'
                                        }
                                    "
                                    data-preview-images="${escapeHtml(encodeURIComponent(JSON.stringify(images.map(img => ({
                                        url: img.url || '',
                                        name: img.name || 'Image'
                                    }))))) }"
                                    data-preview-message-id="${messageId ?? ''}"
                                    onclick="openImagePreviewFromMessage(event.currentTarget, 
                                        decodeURIComponent(
                                            '${encodeURIComponent(
                                                String(
                                                    attachment.url || ''
                                                )
                                            )}'
                                        ),
                                        decodeURIComponent(
                                            '${encodeURIComponent(
                                                String(
                                                    attachment.name ||
                                                    'Image'
                                                )
                                            )}'
                                        )
                                    )"
                                >
                                    <img
                                        src="${escapeHtml(
                                            attachment.url || ''
                                        )}"
                                        alt="${escapeHtml(
                                            attachment.name ||
                                            'Image'
                                        )}"
                                        class="
                                            h-full
                                            w-full
                                            object-cover
                                        "
                                        loading="eager"
                                    >
                                </button>
                            `;

                        })
                        .join('');

                    html += '</div>';

                }


                // =================================================
                // FOUR OR MORE IMAGES
                //
                // IMG 1 | IMG 2
                // -------------
                // IMG 3 | IMG 4
                //
                // Example with 7 images:
                //
                // IMG 1 | IMG 2
                // -------------
                // IMG 3 | +3
                // =================================================

                else {

                    html += `
                        <div
                            class="
                                mt-1
                                grid
                                w-[320px]
                                max-w-full
                                grid-cols-2
                                gap-1
                                overflow-hidden
                                rounded-2xl
                            "
                        >
                    `;

                    html += visibleImages
                        .map((attachment, imageIndex) => {

                            // FIX: use the current image's index
                            const showMore =
                                imageIndex === 3 &&
                                remainingImages > 0;

                            return `
                                <button
                                    type="button"
                                    class="
                                        relative
                                        block
                                        h-[160px]
                                        overflow-hidden
                                        bg-gray-100
                                    "
                                    data-preview-images="${escapeHtml(encodeURIComponent(JSON.stringify(images.map(img => ({
                                        url: img.url || '',
                                        name: img.name || 'Image'
                                    }))))) }"
                                    data-preview-message-id="${messageId ?? ''}"
                                    onclick="openImagePreviewFromMessage(event.currentTarget, 
                                        decodeURIComponent(
                                            '${encodeURIComponent(
                                                String(
                                                    attachment.url || ''
                                                )
                                            )}'
                                        ),
                                        decodeURIComponent(
                                            '${encodeURIComponent(
                                                String(
                                                    attachment.name ||
                                                    'Image'
                                                )
                                            )}'
                                        )
                                    )"
                                >
                                    <img
                                        src="${escapeHtml(
                                            attachment.url || ''
                                        )}"
                                        alt="${escapeHtml(
                                            attachment.name ||
                                            'Image'
                                        )}"
                                        class="
                                            h-full
                                            w-full
                                            object-cover
                                        "
                                        loading="eager"
                                    >

                                    ${
                                        showMore
                                            ? `
                                                <div
                                                    class="
                                                        absolute
                                                        inset-0
                                                        flex
                                                        items-center
                                                        justify-center
                                                        bg-black/50
                                                        text-2xl
                                                        font-semibold
                                                        text-white
                                                    "
                                                >
                                                    +${remainingImages}
                                                </div>
                                            `
                                            : ''
                                    }

                                </button>
                            `;

                        })
                        .join('');

                    html += '</div>';
                }
            }


            // =====================================================
            // NORMAL FILE ATTACHMENTS
            // PDF, DOCX, XLSX, ZIP, TXT, etc.
            // =====================================================

            if (files.length) {

                html += `
                    <div
                        class="
                            ${images.length ? 'mt-1.5' : ''}
                            space-y-1.5
                        "
                    >
                `;

                html += files
                    .map(
                        attachment =>
                            getAttachmentPreviewHtml(
                                attachment,
                                time
                            )
                    )
                    .join('');

                html += '</div>';
            }


            return html;
        }

        function isImageAttachment(attachment) {
            if (!attachment) return false;

            const imageExtensions = [
                'jpg',
                'jpeg',
                'png',
                'gif',
                'webp',
                'bmp',
                'svg'
            ];

            const type = String(
                attachment.type ||
                attachment.attachment_type ||
                ''
            ).toLowerCase();

            const ext = String(
                attachment.extension ||
                attachment.attachment_extension ||
                ''
            )
                .toLowerCase()
                .replace(/^\./, '');

            const name = String(
                attachment.name ||
                attachment.attachment_name ||
                ''
            ).toLowerCase();

            const nameExtension =
                name.includes('.')
                    ? name.split('.').pop()
                    : '';

            return (
                type.startsWith('image/') ||
                imageExtensions.includes(type) ||
                imageExtensions.includes(ext) ||
                imageExtensions.includes(nameExtension)
            );
        }

        function getAttachmentPreviewHtml(attachment, time, isTemp = false) {
            if (!attachment) return '';

            const isImage =
                isImageAttachment(attachment);

            const opacity =
                isTemp ? 'opacity-80' : '';

            const removeBtn = isTemp ? `
                <button
                    type="button"
                    onclick="this.closest('.attachment-preview').remove()"
                    class="
                        absolute
                        -right-1.5
                        -top-1.5
                        z-20
                        flex
                        h-5
                        w-5
                        items-center
                        justify-center
                        rounded-full
                        bg-gray-700
                        text-white
                        shadow
                        transition
                        hover:bg-gray-900
                    "
                    data-tooltip="Remove"
                >
                    <i data-lucide="x" class="h-3 w-3"></i>
                </button>
            ` : '';

            if (isImage && attachment.url) {
                return getImageMessageHtml(
                    attachment,
                    time,
                    isTemp,
                    removeBtn
                );
            }

            const fileName =
                attachment.name ||
                attachment.attachment_name ||
                'File';

            const fileSize =
                formatFileSize(
                    Number(
                        attachment.size ||
                        attachment.attachment_size ||
                        0
                    )
                );

            const fileUrl =
                attachmentViewUrl(attachment);

            const downloadUrl =
                attachmentDownloadUrl(attachment);

            const ext = String(
                attachment.extension ||
                attachment.attachment_extension ||
                (fileName.includes('.') ? fileName.split('.').pop() : '')
            ).toLowerCase().replace(/^\./, '');

            const openInline = [
                'pdf',
                'txt',
                'csv',
                'jpg',
                'jpeg',
                'png',
                'gif',
                'webp'
            ].includes(ext);

            const primaryUrl = openInline ? fileUrl : downloadUrl;

            // =====================================================
            // NON-IMAGE FILE CARD
            //
            // Matches the compact Messenger-style reference:
            // document icon + filename + file size.
            // Clicking the card opens/downloads the file.
            // =====================================================

            return `
                <div
                    class="
                        attachment-preview
                        relative
                        mt-1
                        ${opacity}
                    "
                    style="
                        animation: messageSlideIn 0.3s
                        cubic-bezier(0.4, 0, 0.2, 1)
                    "
                >
                    <div
                        class="
                            flex
                            min-h-[72px]
                            w-[220px]
                            max-w-full
                            items-center
                            gap-2
                            rounded-[18px]
                            bg-[#4b4b4b]
                            px-3.5
                            py-3
                            shadow-sm
                        "
                    >
                        <a
                            href="${escapeHtml(primaryUrl)}"
                            target="${openInline ? '_blank' : '_self'}"
                            rel="noopener noreferrer"
                            ${openInline ? '' : `download="${escapeHtml(fileName)}"`}
                            class="
                                group/file
                                flex
                                min-w-0
                                flex-1
                                items-center
                                gap-3
                                text-left
                            "
                            data-tooltip="${escapeHtml(fileName)}"
                        >
                            <div
                                class="
                                    flex
                                    h-11
                                    w-11
                                    shrink-0
                                    items-center
                                    justify-center
                                    rounded-full
                                    bg-white/[0.04]
                                    text-gray-100
                                "
                            >
                                <i
                                    data-lucide="file-text"
                                    class="h-5 w-5"
                                ></i>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p
                                    class="
                                        truncate
                                        text-[15px]
                                        font-semibold
                                        leading-5
                                        text-white
                                    "
                                >
                                    ${escapeHtml(fileName)}
                                </p>

                                <p
                                    class="
                                        mt-0.5
                                        truncate
                                        text-[13px]
                                        leading-4
                                        text-gray-300
                                    "
                                >
                                    ${escapeHtml(fileSize)}
                                </p>
                            </div>
                        </a>
                        <a
                            href="${escapeHtml(downloadUrl)}"
                            download="${escapeHtml(fileName)}"
                            class="
                                flex
                                h-8
                                w-8
                                shrink-0
                                items-center
                                justify-center
                                rounded-full
                                text-gray-200
                                hover:bg-white/10
                                hover:text-white
                            "
                            data-tooltip="Download"
                        >
                            <i data-lucide="download" class="h-4 w-4"></i>
                        </a>
                    </div>

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
                        loading="eager"
                        onload="this.classList.add('image-loaded'); document.getElementById('${uniqueId}-skeleton')?.remove()"
                        onerror="document.getElementById('${uniqueId}-skeleton')?.remove()"
                    />
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-200 rounded-xl flex items-center justify-center">
                        <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                            <div class="flex items-center gap-1">
                                <button type="button" onclick="event.stopPropagation(); openImagePreview('${url}', '${name.replace(/'/g, "\\'")}')" class="h-8 w-8 rounded-full bg-white/90 text-gray-900 flex items-center justify-center hover:bg-white transition shadow-lg" data-tooltip="Preview">
                                    <i data-lucide="maximize-2" class="h-4 w-4"></i>
                                </button>
                                <a href="${url}" target="_blank" download="${name}" class="h-8 w-8 rounded-full bg-white/90 text-gray-900 flex items-center justify-center hover:bg-white transition shadow-lg" data-tooltip="Download">
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

        // =====================================================
        // MESSENGER STYLE IMAGE VIEWER
        //
        // Features:
        // 1. Previous and next arrows for multi-image messages
        // 2. Keyboard left / right navigation
        // 3. Download beside Forward and Close
        // 4. Forward uses the existing message forward system
        // =====================================================

        async function openImagePreviewFromMessage(
            button,
            url,
            name
        ) {
            const messageId =
                button?.dataset?.previewMessageId || null;

            let messageImages = [];
            try {
                const encodedGallery =
                    button?.dataset?.previewImages || '';
                if (encodedGallery) {
                    const parsed = JSON.parse(
                        decodeURIComponent(encodedGallery)
                    );
                    if (Array.isArray(parsed)) {
                        messageImages = parsed.filter(image =>
                            String(image?.url || '')
                        );
                    }
                }
            } catch (error) {
                messageImages = [];
            }

            const isGroupedAlbum = messageImages.length > 1;

            // Grouped album: carousel only the images in this message.
            if (isGroupedAlbum) {
                const gallery = messageImages.map(image => ({
                    url: String(image.url || ''),
                    name: image.name || 'Image',
                    messageId
                }));

                let clickedIndex = gallery.findIndex(
                    image => String(image.url || '') === String(url || '')
                );
                if (clickedIndex < 0) {
                    clickedIndex = 0;
                }

                openImagePreview(
                    gallery[clickedIndex]?.url || url,
                    gallery[clickedIndex]?.name || name,
                    gallery,
                    clickedIndex,
                    messageId
                );
                return;
            }

            // Single image: carousel every image in the opened conversation.
            if (
                currentConversationId &&
                typeof loadAllMessagesForConversationSearch ===
                    'function'
            ) {
                try {
                    await loadAllMessagesForConversationSearch();
                } catch (error) {
                    console.error(
                        'Unable to load all conversation images:',
                        error
                    );
                }
            }

            const gallery = [];
            const seen = new Set();

            document
                .querySelectorAll(
                    '#modalMessagesContainer [data-preview-images]'
                )
                .forEach(previewButton => {
                    try {
                        const encodedGallery =
                            previewButton.dataset.previewImages || '';

                        if (!encodedGallery) {
                            return;
                        }

                        const images = JSON.parse(
                            decodeURIComponent(encodedGallery)
                        );

                        const previewMessageId =
                            previewButton.dataset.previewMessageId ||
                            null;

                        if (!Array.isArray(images)) {
                            return;
                        }

                        images.forEach(image => {
                            const imageUrl =
                                String(image?.url || '');

                            if (!imageUrl) {
                                return;
                            }

                            const key =
                                `${previewMessageId || ''}::${imageUrl}`;

                            if (seen.has(key)) {
                                return;
                            }

                            seen.add(key);

                            gallery.push({
                                url: imageUrl,
                                name:
                                    image?.name ||
                                    'Image',
                                messageId:
                                    previewMessageId || null
                            });
                        });
                    } catch (error) {
                        console.error(
                            'Unable to read conversation image gallery:',
                            error
                        );
                    }
                });

            if (!gallery.length) {
                gallery.push({
                    url: url || '',
                    name: name || 'Image',
                    messageId
                });
            }

            let clickedIndex =
                gallery.findIndex(
                    image =>
                        String(image.url || '') ===
                            String(url || '') &&
                        String(image.messageId || '') ===
                            String(messageId || '')
                );

            if (clickedIndex < 0) {
                clickedIndex =
                    gallery.findIndex(
                        image =>
                            String(image.url || '') ===
                            String(url || '')
                    );
            }

            if (clickedIndex < 0) {
                clickedIndex = 0;
            }

            openImagePreview(
                url,
                name,
                gallery,
                clickedIndex,
                gallery[clickedIndex]?.messageId || messageId
            );
        }


        function openImagePreview(
            url,
            name,
            gallery = null,
            index = 0,
            messageId = null
        ) {
            if (Array.isArray(gallery) && gallery.length) {
                imagePreviewGallery =
                    gallery.map(image => ({
                        url: image?.url || '',
                        name: image?.name || 'Image',
                        messageId:
                            image?.messageId ||
                            messageId ||
                            null
                    }));
            } else {
                imagePreviewGallery = [{
                    url: url || '',
                    name: name || 'Image',
                    messageId: messageId || null
                }];
            }

            imagePreviewIndex =
                Math.min(
                    Math.max(Number(index) || 0, 0),
                    imagePreviewGallery.length - 1
                );

            imagePreviewMessageId =
                messageId || null;

            let overlay =
                document.getElementById(
                    'imagePreviewOverlay'
                );

            if (!overlay) {
                overlay =
                    document.createElement('div');

                overlay.id =
                    'imagePreviewOverlay';

                overlay.innerHTML = `
                    <div
                        id="imagePreviewBackdrop"
                        class="
                            fixed inset-0 z-[99999]
                            bg-black/90
                            backdrop-blur-sm
                            opacity-0
                            transition-opacity
                            duration-200
                        "
                    >
                        <!-- =====================================
                             TOP RIGHT ACTIONS
                             Download, Forward, Close
                             ===================================== -->
                        <div
                            class="
                                absolute
                                right-5
                                top-5
                                z-30
                                flex
                                items-center
                                gap-2
                            "
                        >
                            <a
                                id="imagePreviewDownload"
                                href="#"
                                download
                                class="
                                    flex h-11 w-11
                                    items-center justify-center
                                    rounded-full
                                    bg-white/10
                                    text-white
                                    transition
                                    hover:bg-white/20
                                "
                                data-tooltip="Download"
                                aria-label="Download"
                            >
                                <i
                                    data-lucide="download"
                                    class="h-5 w-5"
                                ></i>
                            </a>

                            <button
                                type="button"
                                id="imagePreviewForward"
                                class="
                                    flex h-11 w-11
                                    items-center justify-center
                                    rounded-full
                                    bg-white/10
                                    text-white
                                    transition
                                    hover:bg-white/20
                                    disabled:cursor-not-allowed
                                    disabled:opacity-40
                                "
                                data-tooltip="Forward"
                                aria-label="Forward"
                            >
                                <i
                                    data-lucide="forward"
                                    class="h-5 w-5"
                                ></i>
                            </button>

                            <button
                                type="button"
                                id="imagePreviewClose"
                                class="
                                    flex h-11 w-11
                                    items-center justify-center
                                    rounded-full
                                    bg-white/10
                                    text-white
                                    transition
                                    hover:bg-white/20
                                "
                                data-tooltip="Close"
                                aria-label="Close"
                            >
                                <i
                                    data-lucide="x"
                                    class="h-6 w-6"
                                ></i>
                            </button>
                        </div>


                        <!-- =====================================
                             PREVIOUS IMAGE
                             ===================================== -->
                        <button
                            type="button"
                            id="imagePreviewPrevious"
                            class="
                                absolute
                                left-5
                                top-1/2
                                z-30
                                hidden
                                h-12 w-12
                                -translate-y-1/2
                                items-center justify-center
                                rounded-full
                                bg-white/10
                                text-white
                                transition
                                hover:bg-white/20
                            "
                            data-tooltip="Previous image"
                            aria-label="Previous image"
                        >
                            <i
                                data-lucide="chevron-left"
                                class="h-7 w-7"
                            ></i>
                        </button>


                        <!-- =====================================
                             IMAGE
                             ===================================== -->
                        <div
                            class="
                                flex
                                h-full
                                w-full
                                items-center
                                justify-center
                                px-20
                                py-16
                            "
                        >
                            <div
                                id="imagePreviewContainer"
                                class="
                                    relative
                                    flex
                                    max-h-[88vh]
                                    max-w-[90vw]
                                    scale-95
                                    items-center
                                    justify-center
                                    opacity-0
                                    transition-all
                                    duration-200
                                "
                            >
                                <img
                                    id="imagePreviewImg"
                                    src=""
                                    alt=""
                                    class="
                                        max-h-[88vh]
                                        max-w-[90vw]
                                        object-contain
                                        shadow-2xl
                                    "
                                >
                            </div>
                        </div>


                        <!-- =====================================
                             NEXT IMAGE
                             ===================================== -->
                        <button
                            type="button"
                            id="imagePreviewNext"
                            class="
                                absolute
                                right-5
                                top-1/2
                                z-30
                                hidden
                                h-12 w-12
                                -translate-y-1/2
                                items-center justify-center
                                rounded-full
                                bg-white/10
                                text-white
                                transition
                                hover:bg-white/20
                            "
                            data-tooltip="Next image"
                            aria-label="Next image"
                        >
                            <i
                                data-lucide="chevron-right"
                                class="h-7 w-7"
                            ></i>
                        </button>


                        <!-- =====================================
                             IMAGE NUMBER
                             Example: 2 / 5
                             ===================================== -->
                        <div
                            id="imagePreviewCounter"
                            class="
                                absolute
                                bottom-5
                                left-1/2
                                z-30
                                hidden
                                -translate-x-1/2
                                rounded-full
                                bg-black/45
                                px-3
                                py-1.5
                                text-xs
                                font-medium
                                text-white
                                backdrop-blur-sm
                            "
                        ></div>
                    </div>
                `;

                document.body.appendChild(overlay);
                lucideCreateIcons(overlay);

                const backdrop =
                    document.getElementById(
                        'imagePreviewBackdrop'
                    );

                const previousButton =
                    document.getElementById(
                        'imagePreviewPrevious'
                    );

                const nextButton =
                    document.getElementById(
                        'imagePreviewNext'
                    );

                const forwardButton =
                    document.getElementById(
                        'imagePreviewForward'
                    );

                backdrop?.addEventListener(
                    'click',
                    event => {
                        if (
                            event.target === backdrop ||
                            event.target.closest(
                                '#imagePreviewClose'
                            )
                        ) {
                            closeImagePreview();
                        }
                    }
                );

                previousButton?.addEventListener(
                    'click',
                    event => {
                        event.stopPropagation();
                        showPreviousPreviewImage();
                    }
                );

                nextButton?.addEventListener(
                    'click',
                    event => {
                        event.stopPropagation();
                        showNextPreviewImage();
                    }
                );

                forwardButton?.addEventListener(
                    'click',
                    async event => {
                        event.stopPropagation();

                        if (!imagePreviewMessageId) {
                            return;
                        }

                        const sourceMessageId =
                            imagePreviewMessageId;

                        // =================================================
                        // KEEP IMAGE VIEWER OPEN
                        //
                        // Do NOT close the image preview here.
                        // The Forward modal opens on top of the image.
                        // =================================================

                        // =================================================
                        // USE THE SAME FORWARD MODAL AS THE THREE-DOT MENU
                        // =================================================

                        try {
                            const response =
                                await fetch(
                                    '/messages/conversations',
                                    {
                                        headers: {
                                            'Accept':
                                                'application/json'
                                        }
                                    }
                                );

                            if (!response.ok) {
                                return;
                            }

                            const payload =
                                await response.json();

                            const conversations =
                                payload.data?.data ||
                                payload.data ||
                                [];

                            const choices =
                                conversations.filter(
                                    conversation =>
                                        Number(
                                            conversation.conversation_id
                                        ) !==
                                        Number(
                                            currentConversationId
                                        )
                                );

                            if (!choices.length) {
                                await showMessageActionDialog({
                                    title: 'Forward',
                                    text:
                                        'There is no other conversation to forward this message to.',
                                    confirmText: 'OK'
                                });

                                return;
                            }

                            showForwardMessageDialog(
                                choices,
                                sourceMessageId
                            );
                        } catch (error) {
                            console.error(
                                'Unable to open forward dialog:',
                                error
                            );
                        }
                    }
                );

                if (!window.__prismImagePreviewKeysBound) {
                    window.__prismImagePreviewKeysBound = true;

                    document.addEventListener(
                        'keydown',
                        event => {
                            if (
                                !document.getElementById(
                                    'imagePreviewOverlay'
                                )
                            ) {
                                return;
                            }

                            if (event.key === 'Escape') {
                                closeImagePreview();
                                return;
                            }

                            if (event.key === 'ArrowLeft') {
                                showPreviousPreviewImage();
                                return;
                            }

                            if (event.key === 'ArrowRight') {
                                showNextPreviewImage();
                            }
                        }
                    );
                }
            }

            updateImagePreview();

            const backdrop =
                document.getElementById(
                    'imagePreviewBackdrop'
                );

            const container =
                document.getElementById(
                    'imagePreviewContainer'
                );

            backdrop?.classList.remove(
                'opacity-0'
            );

            requestAnimationFrame(() => {
                container?.classList.remove(
                    'scale-95',
                    'opacity-0'
                );

                container?.classList.add(
                    'scale-100',
                    'opacity-100'
                );
            });

            lucideCreateIcons(container || document.getElementById('messagingModal'));
        }


        function updateImagePreview() {
            const image =
                imagePreviewGallery[
                    imagePreviewIndex
                ];

            if (!image) {
                return;
            }

            // =====================================================
            // CURRENT IMAGE'S ORIGINAL MESSAGE
            // Forward must follow the image after Previous / Next.
            // =====================================================

            imagePreviewMessageId =
                image.messageId ||
                imagePreviewMessageId ||
                null;

            const img =
                document.getElementById(
                    'imagePreviewImg'
                );

            const downloadButton =
                document.getElementById(
                    'imagePreviewDownload'
                );

            const previousButton =
                document.getElementById(
                    'imagePreviewPrevious'
                );

            const nextButton =
                document.getElementById(
                    'imagePreviewNext'
                );

            const counter =
                document.getElementById(
                    'imagePreviewCounter'
                );

            const forwardButton =
                document.getElementById(
                    'imagePreviewForward'
                );

            if (img) {
                img.src = image.url || '';
                img.alt =
                    image.name ||
                    'Image preview';
            }

            if (downloadButton) {
                downloadButton.href =
                    image.downloadUrl ||
                    image.url ||
                    '#';

                downloadButton.download =
                    image.name || 'image';
            }

            const hasMultiple =
                imagePreviewGallery.length > 1;

            // =====================================================
            // PREVIOUS / NEXT EDGE VISIBILITY
            //
            // First image:
            // hide Previous
            //
            // Middle images:
            // show both
            //
            // Last image:
            // hide Next
            // =====================================================

            const canGoPrevious =
                hasMultiple &&
                imagePreviewIndex > 0;

            const canGoNext =
                hasMultiple &&
                imagePreviewIndex <
                    imagePreviewGallery.length - 1;

            if (previousButton) {
                previousButton.classList.toggle(
                    'hidden',
                    !canGoPrevious
                );

                previousButton.classList.toggle(
                    'flex',
                    canGoPrevious
                );
            }

            if (nextButton) {
                nextButton.classList.toggle(
                    'hidden',
                    !canGoNext
                );

                nextButton.classList.toggle(
                    'flex',
                    canGoNext
                );
            }

            if (counter) {
                counter.textContent =
                    `${imagePreviewIndex + 1} / ${imagePreviewGallery.length}`;

                counter.classList.toggle(
                    'hidden',
                    !hasMultiple
                );
            }

            if (forwardButton) {
                forwardButton.disabled =
                    !imagePreviewMessageId;
            }
        }


        function showPreviousPreviewImage() {
            // =====================================================
            // HARD STOP AT THE FIRST IMAGE
            //
            // Example:
            // 1 / 8 cannot go farther left.
            // =====================================================

            if (
                imagePreviewGallery.length <= 1 ||
                imagePreviewIndex <= 0
            ) {
                updateImagePreview();
                return;
            }

            imagePreviewIndex--;

            updateImagePreview();
        }


        function showNextPreviewImage() {
            // =====================================================
            // HARD STOP AT THE LAST IMAGE
            //
            // Example:
            // 8 / 8 cannot go farther right.
            // =====================================================

            if (
                imagePreviewGallery.length <= 1 ||
                imagePreviewIndex >=
                    imagePreviewGallery.length - 1
            ) {
                updateImagePreview();
                return;
            }

            imagePreviewIndex++;

            updateImagePreview();
        }


        function closeImagePreview() {
            const backdrop =
                document.getElementById(
                    'imagePreviewBackdrop'
                );

            const container =
                document.getElementById(
                    'imagePreviewContainer'
                );

            if (!backdrop || !container) {
                return;
            }

            container.classList.remove(
                'scale-100',
                'opacity-100'
            );

            container.classList.add(
                'scale-95',
                'opacity-0'
            );

            backdrop.classList.add(
                'opacity-0'
            );

            setTimeout(() => {
                document.getElementById(
                    'imagePreviewOverlay'
                )?.remove();
            }, 200);
        }


        // IMAGE PREVIEW GLOBAL EXPORTS
        //
        // The message image buttons use inline onclick handlers.
        // Inline handlers can only access functions on window.
        // =====================================================
        window.openImagePreview = openImagePreview;
        window.openImagePreviewFromMessage = openImagePreviewFromMessage;
        window.closeImagePreview = closeImagePreview;
        window.showPreviousPreviewImage = showPreviousPreviewImage;
        window.showNextPreviewImage = showNextPreviewImage;


        function clearSelectedAttachments() {
            selectedAttachments = [];
            const input = document.getElementById('modalAttachmentInput');
            if (input) input.value = '';
            renderSelectedAttachments();
        }

        function removeSelectedAttachment(index) {
            selectedAttachments.splice(index, 1);
            renderSelectedAttachments();
        }

        function renderSelectedAttachments() {

            const preview =
                document.getElementById('modalAttachmentPreview');

            const items =
                document.getElementById('modalAttachmentItems');

            if (!preview || !items) {
                updateComposerActionButton();
                return;
            }


            // =====================================================
            // NOTHING SELECTED
            // =====================================================

            if (!selectedAttachments.length) {

                preview.classList.add('hidden');

                items.innerHTML = '';
                updateComposerActionButton();

                return;
            }


            preview.classList.remove('hidden');


            // =====================================================
            // CREATE ATTACHMENT THUMBNAILS
            // =====================================================

            const cards = selectedAttachments
                .map((attachment, index) => {

                    const isImage =
                        isImageAttachment(attachment);


                    // =================================================
                    // IMAGE
                    // =================================================

                    if (isImage && attachment.url) {

                        return `
                            <div
                                class="
                                    group
                                    relative
                                    h-16
                                    w-16
                                    shrink-0
                                    overflow-visible
                                "
                            >

                                <div
                                    class="
                                        h-full
                                        w-full
                                        overflow-hidden
                                        rounded-xl
                                        bg-gray-100
                                    "
                                >
                                    <img
                                        src="${escapeHtml(attachment.url)}"
                                        alt="${escapeHtml(attachment.name || 'Image')}"
                                        class="
                                            h-full
                                            w-full
                                            object-cover
                                        "
                                    >
                                </div>


                                <button
                                    type="button"
                                    data-remove-attachment="${index}"
                                    class="
                                        absolute
                                        -right-1.5
                                        -top-1.5
                                        flex
                                        h-5
                                        w-5
                                        items-center
                                        justify-center
                                        rounded-full
                                        bg-gray-700
                                        text-white
                                        shadow
                                        transition
                                        hover:bg-gray-900
                                    "
                                    data-tooltip="Remove"
                                >
                                    <i
                                        data-lucide="x"
                                        class="h-3 w-3"
                                    ></i>
                                </button>

                            </div>
                        `;
                    }


                    // =================================================
                    // NORMAL FILE
                    // PDF / WORD / EXCEL / ZIP ETC.
                    // =================================================

                    const icon =
                        getFileIcon(
                            attachment.type || '',
                            attachment.extension || ''
                        );

                    return `
                        <div
                            class="
                                relative
                                flex
                                h-16
                                w-[150px]
                                shrink-0
                                items-center
                                gap-2
                                rounded-xl
                                bg-gray-100
                                px-3
                            "
                        >

                            <div
                                class="
                                    flex
                                    h-9
                                    w-9
                                    shrink-0
                                    items-center
                                    justify-center
                                    rounded-lg
                                    ${icon.color}
                                "
                            >
                                <i
                                    data-lucide="${icon.icon}"
                                    class="h-4 w-4"
                                ></i>
                            </div>


                            <div class="min-w-0 flex-1">

                                <p
                                    class="
                                        truncate
                                        text-xs
                                        font-medium
                                        text-gray-900
                                    "
                                >
                                    ${escapeHtml(attachment.name || 'File')}
                                </p>

                                <p class="text-[10px] text-gray-500">
                                    ${formatFileSize(attachment.size || 0)}
                                </p>

                            </div>


                            <button
                                type="button"
                                data-remove-attachment="${index}"
                                class="
                                    absolute
                                    -right-1.5
                                    -top-1.5
                                    flex
                                    h-5
                                    w-5
                                    items-center
                                    justify-center
                                    rounded-full
                                    bg-gray-700
                                    text-white
                                    shadow
                                    hover:bg-gray-900
                                "
                                data-tooltip="Remove"
                            >
                                <i
                                    data-lucide="x"
                                    class="h-3 w-3"
                                ></i>
                            </button>

                        </div>
                    `;

                })
                .join('');


            // =====================================================
            // ADD MORE BUTTON
            //
            // Same idea as Messenger's:
            // "Upload another file"
            // =====================================================

            items.innerHTML = `

                <button
                    type="button"
                    id="modalAttachmentAddMore"
                    class="
                        flex
                        h-16
                        w-12
                        shrink-0
                        items-center
                        justify-center
                        rounded-xl
                        bg-gray-200
                        text-gray-500
                        transition
                        hover:bg-gray-300
                        hover:text-gray-900
                    "
                    data-tooltip="Upload another file"
                >
                    <i
                        data-lucide="plus"
                        class="h-5 w-5"
                    ></i>
                </button>

                ${cards}
            `;


            // =====================================================
            // + BUTTON
            // OPEN WINDOWS FILE EXPLORER AGAIN
            // =====================================================

            items
                .querySelector('#modalAttachmentAddMore')
                ?.addEventListener('click', () => {

                    document
                        .getElementById('modalAttachmentInput')
                        ?.click();

                });


            // =====================================================
            // X BUTTON
            // REMOVE INDIVIDUAL ATTACHMENT
            // =====================================================

            items
                .querySelectorAll('[data-remove-attachment]')
                .forEach(button => {

                    button.addEventListener('click', () => {

                        removeSelectedAttachment(
                            Number(
                                button.dataset.removeAttachment
                            )
                        );

                    });

                });


            lucideCreateIcons();
            updateComposerActionButton();
        }

        async function uploadModalAttachments(files) {
            if (!files?.length || !currentConversationId) return;

            const maxSize = 25 * 1024 * 1024;
            attachmentsUploading += 1;
            try {
            for (const file of files) {
                if (file.size > maxSize) {
                    alert(`${file.name} must be less than 25MB.`);
                    continue;
                }

                const formData = new FormData();
                formData.append('file', file);
                formData.append('conversation_id', currentConversationId);

                try {
                    const response = await fetch('/messages/upload', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: formData
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        const firstError = data.errors
                            ? Object.values(data.errors).flat()[0]
                            : null;
                        throw new Error(
                            firstError || data.message || `Failed to upload ${file.name}.`
                        );
                    }
                    selectedAttachments.push(data.data);
                    renderSelectedAttachments();
                } catch (error) {
                    alert(error.message || `Failed to upload ${file.name}.`);
                }
            }

            const input = document.getElementById('modalAttachmentInput');
            if (input) input.value = '';
            } finally {
                attachmentsUploading = Math.max(0, attachmentsUploading - 1);
                updateComposerActionButton();
            }
        }

        async function loadModalUsers(search = '', force = false) {
            const normalizedSearch = String(search || '');

            if (
                !force &&
                !normalizedSearch &&
                usersCache &&
                (Date.now() - usersLoadedAt) < 30000
            ) {
                renderModalUsers(usersCache);
                return;
            }

            const params = new URLSearchParams();
            if (normalizedSearch) params.set('search', normalizedSearch);

            const response = await fetch(`/messages/users?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            if (!response.ok) return;
            const data = await response.json();
            if (!normalizedSearch) {
                usersCache = data.data;
                usersCacheSearch = '';
                usersLoadedAt = Date.now();
            }
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

                const activityStatus =
                    formatUserActivity(user.last_active_at);

                const isOnline =
                    activityStatus === 'Active now';

                const statusDotClass =
                    isOnline
                        ? 'bg-emerald-500'
                        : 'bg-gray-300';

                const statusTextClass =
                    isOnline
                        ? 'text-emerald-600'
                        : 'text-gray-400';


                // =================================================
                // SAFE USER NAME FOR DATA ATTRIBUTE
                // =================================================

                const safeName =
                    escapeHtml(user.name);


                return `

                    <!-- ========================================= -->
                    <!-- USER ROW WRAPPER -->
                    <!-- ========================================= -->

                    <div
                        class="
                            user-list-item
                            group
                            relative
                            w-full
                        "
                        data-user-id="${user.user_id}"
                        data-user-name="${safeName}"
                    >

                        <!-- ===================================== -->
                        <!-- MAIN USER BUTTON -->
                        <!-- ===================================== -->

                        <button
                            type="button"
                            class="
                                user-row
                                w-full
                                flex
                                items-center
                                gap-3
                                px-3
                                pr-12
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
                        >

                            <!-- ================================= -->
                            <!-- AVATAR -->
                            <!-- ================================= -->

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


                                <!-- ============================= -->
                                <!-- ONLINE / OFFLINE DOT -->
                                <!-- ============================= -->

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


                            <!-- ================================= -->
                            <!-- USER INFORMATION -->
                            <!-- ================================= -->

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


                        <!-- ===================================== -->
                        <!-- THREE DOT USER MENU BUTTON -->
                        <!-- Dropdown itself is rendered outside the scroll list -->
                        <!-- ===================================== -->
                        <div
                            class="user-options-wrapper absolute right-2 top-1/2 z-30 -translate-y-1/2"
                        >
                            <button
                                type="button"
                                class="
                                    user-options-button
                                    flex
                                    h-8
                                    w-8
                                    shrink-0
                                    items-center
                                    justify-center
                                    rounded-full
                                    text-gray-500
                                    
                                    transition
                                    duration-150
                                    hover:bg-gray-200
                                    hover:text-gray-900
                                    
                                    
                                "
                                data-user-id="${user.user_id}"
                                data-user-name="${safeName}"
                                data-conversation-id="${Number(user.direct_conversation_id || 0)}"
                                data-is-hidden="${user.is_hidden ? '1' : '0'}"
                                aria-label="More options for ${safeName}"
                            >
                                <i data-lucide="more-vertical" class="h-4 w-4"></i>
                            </button>
                        </div>

                    </div>
                `;

            }).join('');


            lucideCreateIcons(container);
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

                switchModalTab('conversations', { refresh: false });
                conversationsRenderKey = '';
                usersCache = null;
                usersLoadedAt = 0;
                closeUserOptionsMenu();
                await loadModalConversations();
                await openModalConversation(conversation.conversation_id);
                showMessagingThreadPane();

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

        // =====================================================
        // CREATE GROUP CHAT STATE
        // =====================================================

        let createGroupUsers = [];
        let createGroupSelectedIds = new Set();
        let createGroupRequiredUserId = null;


        // =====================================================
        // LOAD USERS FOR GROUP MODAL
        // =====================================================

        async function loadCreateGroupUsers() {

            const response = await fetch('/messages/users', {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Unable to load users.');
            }

            const data = await response.json();

            createGroupUsers = Array.isArray(data.data)
                ? data.data
                : [];

            renderCreateGroupMembers();
        }


        // =====================================================
        // RENDER GROUP MEMBERS
        // =====================================================

        function renderCreateGroupMembers() {

            const container =
                document.getElementById(
                    'createGroupMembersList'
                );

            if (!container) return;

            const users = createGroupUsers.filter(
                user =>
                    Number(user.user_id) !==
                    Number(currentUserId)
            );

            if (!users.length) {

                container.innerHTML = `
                    <div class="p-5 text-center text-sm text-gray-500">
                        No other users available.
                    </div>
                `;

                updateCreateGroupSelectedCount();

                return;
            }

            container.innerHTML = users.map(user => {

                const userId =
                    Number(user.user_id);

                const name =
                    user.user_full_name ||
                    user.name ||
                    'User';

                const role =
                    user.role?.role_name ||
                    user.role_name ||
                    '';

                const isRequired =
                    userId ===
                    Number(createGroupRequiredUserId);

                const isSelected =
                    createGroupSelectedIds.has(userId);

                return `
                    <button
                        type="button"
                        class="
                            create-group-member
                            flex
                            w-full
                            items-center
                            gap-3
                            border-b
                            border-gray-100
                            px-4
                            py-3
                            text-left
                            transition
                            last:border-b-0
                            hover:bg-gray-50
                        "
                        data-user-id="${userId}"
                        ${isRequired ? 'disabled' : ''}
                    >

                        <div
                            class="
                                flex
                                h-9
                                w-9
                                shrink-0
                                items-center
                                justify-center
                                rounded-full
                                bg-gray-100
                                text-xs
                                font-semibold
                                text-gray-600
                            "
                        >
                            ${escapeHtml(getInitials(name))}
                        </div>

                        <div class="min-w-0 flex-1">

                            <p
                                class="
                                    truncate
                                    text-sm
                                    font-semibold
                                    text-gray-900
                                "
                            >
                                ${escapeHtml(name)}
                            </p>

                            <p
                                class="
                                    truncate
                                    text-xs
                                    text-gray-500
                                "
                            >
                                ${escapeHtml(role)}
                            </p>

                        </div>

                        <div
                            class="
                                flex
                                h-5
                                w-5
                                shrink-0
                                items-center
                                justify-center
                                rounded-md
                                border
                                ${
                                    isSelected
                                        ? 'border-gray-900 bg-gray-900 text-white'
                                        : 'border-gray-300 bg-white text-transparent'
                                }
                            "
                        >
                            <i
                                data-lucide="check"
                                class="h-3.5 w-3.5"
                            ></i>
                        </div>

                    </button>
                `;

            }).join('');

            updateCreateGroupSelectedCount();

            lucideCreateIcons();
        }


        // =====================================================
        // SELECT / UNSELECT GROUP MEMBER
        // =====================================================

        function toggleCreateGroupMember(userId) {

            userId = Number(userId);

            // =============================================
            // USER WHO STARTED THE GROUP CANNOT BE REMOVED
            // =============================================

            if (
                userId ===
                Number(createGroupRequiredUserId)
            ) {
                return;
            }

            if (createGroupSelectedIds.has(userId)) {
                createGroupSelectedIds.delete(userId);
            } else {
                createGroupSelectedIds.add(userId);
            }

            renderCreateGroupMembers();
        }


        // =====================================================
        // UPDATE MEMBER COUNT
        // =====================================================

        function updateCreateGroupSelectedCount() {

            const counter =
                document.getElementById(
                    'createGroupSelectedCount'
                );

            if (!counter) return;

            const count =
                createGroupSelectedIds.size;

            counter.textContent =
                `${count} selected`;
        }


        // =====================================================
        // OPEN CREATE GROUP MODAL
        // =====================================================

        async function openCreateGroupChatModal(
            requiredUserId,
            requiredUserName
        ) {

            createGroupRequiredUserId =
                Number(requiredUserId);

            createGroupSelectedIds =
                new Set([
                    Number(requiredUserId)
                ]);

            const modal =
                document.getElementById(
                    'createGroupChatModal'
                );

            const nameInput =
                document.getElementById(
                    'createGroupChatName'
                );

            const error =
                document.getElementById(
                    'createGroupChatError'
                );

            if (!modal) return;

            if (nameInput) {
                nameInput.value = '';
            }

            if (error) {
                error.textContent = '';
                error.classList.add('hidden');
            }

            modal.classList.remove('hidden');

            document.body.style.overflow =
                'hidden';

            try {

                await loadCreateGroupUsers();

                requestAnimationFrame(() => {
                    nameInput?.focus();
                });

            } catch (error) {

                showCreateGroupError(
                    error.message ||
                    'Unable to load users.'
                );
            }

            lucideCreateIcons();
        }


        // =====================================================
        // CLOSE CREATE GROUP MODAL
        // =====================================================

        function closeCreateGroupChatModal() {

            const modal =
                document.getElementById(
                    'createGroupChatModal'
                );

            modal?.classList.add('hidden');

            createGroupUsers = [];

            createGroupSelectedIds.clear();

            createGroupRequiredUserId = null;

            // Keep body locked if main Messages modal is open.

            const messagingModal =
                document.getElementById(
                    'messagingModal'
                );

            if (
                !messagingModal ||
                messagingModal.classList.contains('hidden')
            ) {
                document.body.style.overflow = '';
            }
        }


        // =====================================================
        // GROUP MODAL ERROR
        // =====================================================

        function showCreateGroupError(message) {

            const error =
                document.getElementById(
                    'createGroupChatError'
                );

            if (!error) return;

            error.textContent =
                message || 'Something went wrong.';

            error.classList.remove('hidden');
        }


        // =====================================================
        // CREATE GROUP ON SERVER
        // =====================================================

        async function createGroupConversation() {

            const nameInput =
                document.getElementById(
                    'createGroupChatName'
                );

            const submitButton =
                document.getElementById(
                    'createGroupChatSubmit'
                );

            const conversationName =
                nameInput?.value.trim() || '';

            const userIds =
                Array.from(
                    createGroupSelectedIds
                );

            if (!conversationName) {

                showCreateGroupError(
                    'Enter a group name.'
                );

                nameInput?.focus();

                return;
            }

            // Backend requires two OTHER users.
            if (userIds.length < 2) {

                showCreateGroupError(
                    'Select at least one more person.'
                );

                return;
            }

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent =
                    'Creating...';
            }

            try {

                const response = await fetch(
                    '/messages/conversations/group',
                    {
                        method: 'POST',

                        headers: {
                            'Accept':
                                'application/json',

                            'Content-Type':
                                'application/json',

                            'X-CSRF-TOKEN':
                                csrfToken
                        },

                        body: JSON.stringify({
                            conversation_name:
                                conversationName,

                            user_ids:
                                userIds
                        })
                    }
                );

                const data =
                    await response.json();

                if (!response.ok) {

                    throw new Error(
                        data.message ||
                        Object.values(
                            data.errors || {}
                        )?.[0]?.[0] ||
                        'Unable to create group.'
                    );
                }

                const conversation =
                    data.data;

                closeCreateGroupChatModal();

                switchModalTab(
                    'conversations'
                );

                await loadModalConversations();

                if (conversation?.conversation_id) {

                    await openModalConversation(
                        conversation.conversation_id
                    );
                }

            } catch (error) {

                showCreateGroupError(
                    error.message ||
                    'Unable to create group.'
                );

            } finally {

                if (submitButton) {

                    submitButton.disabled = false;

                    submitButton.textContent =
                        'Create group';
                }
            }
        }

        // =====================================================
        // USER THREE DOT OPTIONS MENU
        // One floating dropdown is used for every user.
        // It is outside the scroll list so it cannot overlap badly
        // or get clipped by the Users panel overflow.
        // =====================================================

        let selectedOptionsUserId = null;
        let selectedOptionsUserName = '';
        let selectedOptionsUserConversationId = null;
        let selectedOptionsUserHidden = false;
        let activeUserOptionsButton = null;
        let activeUserOptionsMenu = null;

        function getUserOptionsMenu() {
            let menu = document.getElementById('floatingUserOptionsMenu');

            if (menu) return menu;

            menu = document.createElement('div');
            menu.id = 'floatingUserOptionsMenu';
            menu.className = `
                user-options-menu
                hidden
                fixed
                z-[10050]
                w-44
                overflow-hidden
                rounded-lg
                border
                border-gray-200
                bg-white
                p-1
                shadow-[0_8px_20px_rgba(0,0,0,0.12)]
            `;

            menu.innerHTML = `
                <button
                    type="button"
                    class="user-create-group-button flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-xs font-medium text-gray-700 transition hover:bg-gray-100 hover:text-gray-900"
                >
                    <i data-lucide="users" class="h-3.5 w-3.5 shrink-0"></i>
                    <span class="user-create-group-text whitespace-nowrap">Create group chat</span>
                </button>
                <button
                    type="button"
                    class="user-unhide-button hidden w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-xs font-medium text-gray-700 transition hover:bg-gray-100 hover:text-gray-900"
                >
                    <i data-lucide="eye" class="h-3.5 w-3.5 shrink-0"></i>
                    <span>Unhide</span>
                </button>
            `;

            document.body.appendChild(menu);
            lucideCreateIcons();
            return menu;
        }

        function closeUserOptionsMenu() {

            // =====================================
            // CLOSE CURRENT USER DROPDOWN
            // Three dot buttons always stay visible
            // =====================================
            if (activeUserOptionsMenu) {
                activeUserOptionsMenu.classList.add('hidden');
            }

            activeUserOptionsMenu = null;
            activeUserOptionsButton = null;
            selectedOptionsUserId = null;
            selectedOptionsUserName = '';
            selectedOptionsUserConversationId = null;
            selectedOptionsUserHidden = false;
        }

        function openUserOptionsMenu(button) {
            if (!button) return;

            closeConversationOptionsMenu();
            const menu = getUserOptionsMenu();

            selectedOptionsUserId = Number(button.dataset.userId || 0);
            selectedOptionsUserName = button.dataset.userName || 'User';
            selectedOptionsUserConversationId = Number(button.dataset.conversationId || 0) || null;
            selectedOptionsUserHidden = button.dataset.isHidden === '1';
            activeUserOptionsButton = button;
            activeUserOptionsMenu = menu;
            

            const groupText = menu.querySelector('.user-create-group-text');
            if (groupText) {
                groupText.textContent = 'Create group chat';
            }

            const unhideButton = menu.querySelector('.user-unhide-button');
            const showUnhide = Boolean(
                selectedOptionsUserConversationId &&
                selectedOptionsUserHidden
            );
            unhideButton?.classList.toggle('hidden', !showUnhide);
            unhideButton?.classList.toggle('flex', showUnhide);
            lucideCreateIcons(menu);

            // =====================================================
            // FIRST OPEN POSITION FIX
            // =====================================================
            // The menu is created dynamically on the first click.
            // Make it render invisibly first, then measure it on the
            // next browser frame so its width and height are correct.
            // =====================================================
            menu.classList.remove('hidden');
            menu.style.visibility = 'hidden';
            menu.style.left = '0px';
            menu.style.top = '0px';

            requestAnimationFrame(() => {
                // The user may have closed the menu before this frame runs.
                if (
                    activeUserOptionsMenu !== menu ||
                    activeUserOptionsButton !== button ||
                    menu.classList.contains('hidden')
                ) {
                    menu.style.visibility = '';
                    return;
                }

                const icon = button.querySelector('svg, i') || button;
                const anchorRect = icon.getBoundingClientRect();
                const menuRect = menu.getBoundingClientRect();
                const gap = 2;
                const screenPadding = 10;

                // Tuck the menu under the 3-dot icon, not the padded hit area.
                let left = anchorRect.right - menuRect.width + 8;
                left = Math.max(
                    screenPadding,
                    Math.min(
                        left,
                        window.innerWidth - menuRect.width - screenPadding
                    )
                );

                const roomBelow = window.innerHeight - anchorRect.bottom;
                const roomAbove = anchorRect.top;
                let top;

                if (
                    roomBelow >= menuRect.height + gap ||
                    roomBelow >= roomAbove
                ) {
                    top = anchorRect.bottom + gap;
                } else {
                    top = anchorRect.top - menuRect.height - gap;
                }

                top = Math.max(
                    screenPadding,
                    Math.min(
                        top,
                        window.innerHeight - menuRect.height - screenPadding
                    )
                );

                menu.style.left = `${Math.round(left)}px`;
                menu.style.top = `${Math.round(top)}px`;
                menu.style.visibility = 'visible';

                lucideCreateIcons();
            });
        }

        let selectedOptionsConversationId = null;
        let selectedOptionsConversationType = 'direct';
        let activeConversationOptionsButton = null;
        let activeConversationOptionsMenu = null;

        function getConversationOptionsMenu() {
            let menu = document.getElementById('floatingConversationOptionsMenu');
            if (menu) {
                return menu;
            }

            menu = document.createElement('div');
            menu.id = 'floatingConversationOptionsMenu';
            menu.className = `
                hidden fixed z-[10050] w-44 overflow-hidden rounded-lg
                border border-gray-200 bg-white p-1
                shadow-[0_8px_20px_rgba(0,0,0,0.12)]
            `;
            menu.innerHTML = `
                <button type="button" class="conversation-mute-button flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-xs font-medium text-gray-700 transition hover:bg-gray-100 hover:text-gray-900">
                    <i data-lucide="bell-off" class="conversation-mute-icon h-3.5 w-3.5 shrink-0"></i>
                    <span class="conversation-mute-text">Mute</span>
                </button>
                <button type="button" class="conversation-hide-button flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-xs font-medium text-gray-700 transition hover:bg-gray-100 hover:text-gray-900">
                    <i data-lucide="eye-off" class="h-3.5 w-3.5 shrink-0"></i>
                    <span>Hide</span>
                </button>
                <button type="button" class="conversation-leave-button hidden w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-xs font-medium text-red-600 transition hover:bg-red-50">
                    <i data-lucide="log-out" class="h-3.5 w-3.5 shrink-0"></i>
                    <span>Leave</span>
                </button>
            `;
            document.body.appendChild(menu);
            lucideCreateIcons(menu);
            return menu;
        }

        function closeConversationOptionsMenu() {
            if (activeConversationOptionsMenu) {
                activeConversationOptionsMenu.classList.add('hidden');
            }
            activeConversationOptionsMenu = null;
            activeConversationOptionsButton = null;
            selectedOptionsConversationId = null;
            selectedOptionsConversationType = 'direct';
        }

        function openConversationOptionsMenu(button) {
            if (!button) {
                return;
            }

            closeUserOptionsMenu();
            const menu = getConversationOptionsMenu();
            selectedOptionsConversationId = Number(button.dataset.conversationId || 0);
            selectedOptionsConversationType = button.dataset.conversationType || 'direct';
            activeConversationOptionsButton = button;
            activeConversationOptionsMenu = menu;

            const isGroup = selectedOptionsConversationType === 'group';
            const conv = findCachedConversation(selectedOptionsConversationId);
            const muted = currentUserIsMutedInConversation(conv);
            const muteText = menu.querySelector('.conversation-mute-text');
            const muteIcon = menu.querySelector('.conversation-mute-icon');
            if (muteText) {
                muteText.textContent = muted ? 'Unmute' : 'Mute';
            }
            if (muteIcon) {
                const nextIcon = document.createElement('i');
                nextIcon.setAttribute('data-lucide', muted ? 'bell' : 'bell-off');
                nextIcon.className = 'conversation-mute-icon h-3.5 w-3.5 shrink-0';
                muteIcon.replaceWith(nextIcon);
            }

            menu.querySelector('.conversation-hide-button')?.classList.toggle('hidden', isGroup);
            menu.querySelector('.conversation-leave-button')?.classList.toggle('hidden', !isGroup);
            menu.querySelector('.conversation-leave-button')?.classList.toggle('flex', isGroup);

            menu.classList.remove('hidden');
            menu.style.visibility = 'hidden';
            menu.style.left = '0px';
            menu.style.top = '0px';

            requestAnimationFrame(() => {
                if (
                    activeConversationOptionsMenu !== menu ||
                    activeConversationOptionsButton !== button ||
                    menu.classList.contains('hidden')
                ) {
                    menu.style.visibility = '';
                    return;
                }

                const icon = button.querySelector('svg, i') || button;
                const anchorRect = icon.getBoundingClientRect();
                const menuRect = menu.getBoundingClientRect();
                const gap = 2;
                const screenPadding = 10;
                let left = anchorRect.right - menuRect.width + 8;
                left = Math.max(
                    screenPadding,
                    Math.min(left, window.innerWidth - menuRect.width - screenPadding)
                );

                const roomBelow = window.innerHeight - anchorRect.bottom;
                let top = roomBelow >= menuRect.height + gap
                    ? anchorRect.bottom + gap
                    : anchorRect.top - menuRect.height - gap;
                top = Math.max(
                    screenPadding,
                    Math.min(top, window.innerHeight - menuRect.height - screenPadding)
                );

                menu.style.left = `${Math.round(left)}px`;
                menu.style.top = `${Math.round(top)}px`;
                menu.style.visibility = 'visible';
                lucideCreateIcons(menu);
            });
        }

        async function toggleMuteConversationById(conversationId) {
            const conv = findCachedConversation(conversationId);
            const muted = currentUserIsMutedInConversation(conv);
            const action = muted ? 'unmute' : 'mute';
            const response = await fetch(
                `/messages/conversations/${conversationId}/${action}`,
                {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                }
            );
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || `Unable to ${action} conversation.`);
            }
            if (action === 'mute') {
                mutedConversationIds.add(Number(conversationId));
            } else {
                mutedConversationIds.delete(Number(conversationId));
            }
            await loadModalConversations();
            await updateTopbarMessageBadge();
        }

        async function hideConversationFromList(conversationId) {
            const response = await fetch(
                `/messages/conversations/${conversationId}/hide`,
                {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                }
            );
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || 'Unable to hide conversation.');
            }

            if (Number(currentConversationId) === Number(conversationId)) {
                resetModalChat();
            }

            conversationsRenderKey = '';
            usersCache = null;
            usersLoadedAt = 0;
            await loadModalConversations();
        }

        async function unhideConversationFromList(conversationId) {
            const response = await fetch(
                `/messages/conversations/${conversationId}/unhide`,
                {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                }
            );
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || 'Unable to unhide conversation.');
            }

            conversationsRenderKey = '';
            usersCache = null;
            usersLoadedAt = 0;
            closeUserOptionsMenu();
            switchModalTab('conversations', { refresh: false });
            await loadModalConversations();
            await openModalConversation(conversationId);
            showMessagingThreadPane();
        }

        function bindMessagingHoverTooltips() {
            const tip = document.getElementById('messagingHoverTooltip');
            if (!tip) {
                return;
            }

            let active = null;
            let hideTimer = null;

            const hide = () => {
                active = null;
                tip.classList.add('hidden');
                tip.classList.remove('is-visible');
            };

            const place = (el) => {
                const text = String(el.getAttribute('data-tooltip') || '').trim();
                if (!text) {
                    hide();
                    return;
                }

                tip.textContent = text;
                tip.classList.remove('hidden');
                tip.style.left = '0px';
                tip.style.top = '0px';

                const rect = el.getBoundingClientRect();
                const tipRect = tip.getBoundingClientRect();
                const gap = 8;
                const pad = 8;
                let left = rect.left + (rect.width / 2) - (tipRect.width / 2);
                left = Math.max(pad, Math.min(left, window.innerWidth - tipRect.width - pad));

                let top = rect.top - tipRect.height - gap;
                if (top < pad) {
                    top = rect.bottom + gap;
                }

                tip.style.left = `${Math.round(left)}px`;
                tip.style.top = `${Math.round(top)}px`;
                tip.classList.add('is-visible');
            };

            document.addEventListener('pointerover', (event) => {
                const el = event.target.closest?.('[data-tooltip]');
                if (!el || el === active) {
                    return;
                }

                clearTimeout(hideTimer);
                active = el;
                place(el);
            });

            document.addEventListener('pointerout', (event) => {
                const el = event.target.closest?.('[data-tooltip]');
                if (!el || el !== active) {
                    return;
                }

                const next = event.relatedTarget;
                if (next && el.contains(next)) {
                    return;
                }

                hideTimer = setTimeout(hide, 40);
            });

            document.addEventListener('scroll', () => {
                if (active) {
                    hide();
                }
            }, true);
        }

        document.addEventListener('DOMContentLoaded', () => {
            bindMessagingHoverTooltips();
            updateTopbarMessageBadge();
            if (window.requestIdleCallback) {
                requestIdleCallback(() => loadModalConversations(), { timeout: 3000 });
            } else {
                setTimeout(loadModalConversations, 600);
            }

            document
                .getElementById(
                    'closeCreateGroupChatModal'
                )
                ?.addEventListener(
                    'click',
                    closeCreateGroupChatModal
                );

            document
                .getElementById(
                    'cancelCreateGroupChat'
                )
                ?.addEventListener(
                    'click',
                    closeCreateGroupChatModal
                );

            document
                .getElementById(
                    'createGroupChatBackdrop'
                )
                ?.addEventListener(
                    'click',
                    closeCreateGroupChatModal
                );

            document
                .getElementById(
                    'createGroupMembersList'
                )
                ?.addEventListener(
                    'click',
                    event => {

                        const member =
                            event.target.closest(
                                '.create-group-member'
                            );

                        if (!member) return;

                        toggleCreateGroupMember(
                            member.dataset.userId
                        );
                    }
                );

            document
                .getElementById('modalMessagesContainer')
                ?.addEventListener('click', event => {

                    const button =
                        event.target.closest(
                            '[data-group-action]'
                        );

                    if (!button) {
                        return;
                    }

                    const action =
                        button.dataset.groupAction;


                    // =============================================
                    // ADD PEOPLE
                    // =============================================

                    if (action === 'add') {

                        openAddGroupPeopleModal();

                        return;
                    }


                    // =============================================
                    // RENAME GROUP
                    // =============================================

                    if (action === 'rename') {

                        openRenameGroupModal();

                        return;
                    }
                });

            document
                .getElementById(
                    'createGroupChatForm'
                )
                ?.addEventListener(
                    'submit',
                    async event => {

                        event.preventDefault();

                        await createGroupConversation();
                    }
                );

            document
                .getElementById('modalPinnedBanner')
                ?.addEventListener(
                    'click',
                    openPinnedMessagesModal
                );

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
                    const optionsButton = e.target.closest('.conversation-options-button');
                    if (optionsButton) {
                        e.preventDefault();
                        e.stopPropagation();
                        const sameButtonIsOpen =
                            activeConversationOptionsButton === optionsButton &&
                            activeConversationOptionsMenu &&
                            !activeConversationOptionsMenu.classList.contains('hidden');
                        if (sameButtonIsOpen) {
                            closeConversationOptionsMenu();
                        } else {
                            openConversationOptionsMenu(optionsButton);
                        }
                        return;
                    }

                    const item = e.target.closest('.conversation-item');
                    if (item) {
                        const id = item.dataset.id;
                        if (id) openModalConversation(id);
                    }
                });
            }

            // =====================================================
            // USERS LIST CLICK HANDLING
            // =====================================================

            const usersList = document.getElementById('modalUsersList');

            if (usersList) {
                usersList.addEventListener('click', (e) => {

                    // =============================================
                    // THREE DOT BUTTON
                    // =============================================
                    const optionsButton = e.target.closest('.user-options-button');

                    if (optionsButton) {
                        e.preventDefault();
                        e.stopPropagation();

                        const sameButtonIsOpen =
                            activeUserOptionsButton === optionsButton &&
                            activeUserOptionsMenu &&
                            !activeUserOptionsMenu.classList.contains('hidden');

                        if (sameButtonIsOpen) {
                            closeUserOptionsMenu();
                        } else {
                            openUserOptionsMenu(optionsButton);
                        }

                        return;
                    }

                    // =============================================
                    // NORMAL USER ROW
                    // =============================================
                    const row = e.target.closest('.user-row');
                    if (!row) return;

                    const wrapper = row.closest('.user-list-item');
                    const userId = Number(wrapper?.dataset.userId || 0);

                    if (userId) {
                        closeUserOptionsMenu();
                        startConversationWithUser(userId);
                    }
                });
            }

            // =====================================================
            // FIND DIRECT CONVERSATION WITH USER
            // =====================================================

            async function getDirectConversationForUser(
                userId
            ) {

                // =============================================
                // storeConversation already returns the existing
                // direct conversation when one already exists.
                // =============================================

                const response = await fetch(
                    '/messages/conversations',
                    {
                        method: 'POST',

                        headers: {
                            'Accept':
                                'application/json',

                            'Content-Type':
                                'application/json',

                            'X-CSRF-TOKEN':
                                csrfToken
                        },

                        body: JSON.stringify({
                            user_id:
                                Number(userId)
                        })
                    }
                );

                const data =
                    await response.json();

                if (!response.ok) {

                    throw new Error(
                        data.message ||
                        'Unable to open conversation.'
                    );
                }

                return data.data;
            }


            // =====================================================
            // MUTE / UNMUTE DIRECT CONVERSATION
            // =====================================================

            async function toggleMuteUserConversation(
                userId
            ) {

                try {

                    const conversation =
                        await getDirectConversationForUser(
                            userId
                        );

                    const conversationId =
                        conversation?.conversation_id;

                    if (!conversationId) {
                        throw new Error(
                            'Conversation was not found.'
                        );
                    }

                    // =============================================
                    // CURRENT USER PARTICIPANT
                    // =============================================

                    const currentParticipant =
                        conversation.participants?.find(
                            participant =>
                                Number(
                                    participant.user_id ??
                                    participant.user?.user_id
                                ) ===
                                Number(currentUserId)
                        );

                    const isMuted =
                        Boolean(
                            Number(
                                currentParticipant?.is_muted ||
                                0
                            )
                        );

                    const action =
                        isMuted
                            ? 'unmute'
                            : 'mute';

                    const response = await fetch(
                        `/messages/conversations/${conversationId}/${action}`,
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

                    const data =
                        await response.json();

                    if (!response.ok) {

                        throw new Error(
                            data.message ||
                            `Unable to ${action} conversation.`
                        );
                    }

                    const conversationIdNumber = Number(conversationId);
                    if (action === 'mute') {
                        mutedConversationIds.add(conversationIdNumber);
                    } else {
                        mutedConversationIds.delete(conversationIdNumber);
                    }

                    await loadModalConversations();
                    await updateTopbarMessageBadge();

                    // Refresh Users tab so the dropdown state
                    // is correct the next time it is opened.

                    await loadModalUsers(
                        document.getElementById(
                            'modalUserSearch'
                        )?.value || ''
                    );

                } catch (error) {

                    console.error(
                        'Mute conversation error:',
                        error
                    );

                    alert(
                        error.message ||
                        'Unable to update mute setting.'
                    );
                }
            }

            // =====================================================
            // CLICK OUTSIDE DROPDOWN + USER ACTIONS
            // =====================================================

            document.addEventListener(
                'click',
                async (e) => {

                    // =============================================
                    // CREATE GROUP CHAT
                    // =============================================

                    const createGroupButton =
                        e.target.closest(
                            '.user-create-group-button'
                        );

                    if (createGroupButton) {

                        e.preventDefault();
                        e.stopPropagation();

                        const userId =
                            selectedOptionsUserId;

                        const userName =
                            selectedOptionsUserName;

                        closeUserOptionsMenu();

                        if (!userId) return;

                        await openCreateGroupChatModal(
                            userId,
                            userName
                        );

                        return;
                    }


                    const userUnhideButton =
                        e.target.closest('.user-unhide-button');
                    if (userUnhideButton) {
                        e.preventDefault();
                        e.stopPropagation();
                        const conversationId = selectedOptionsUserConversationId;
                        closeUserOptionsMenu();
                        if (!conversationId) return;
                        try {
                            await unhideConversationFromList(conversationId);
                        } catch (error) {
                            alert(error.message || 'Unable to unhide conversation.');
                        }
                        return;
                    }

                    const conversationMuteButton =
                        e.target.closest('.conversation-mute-button');
                    if (conversationMuteButton) {
                        e.preventDefault();
                        e.stopPropagation();
                        const conversationId = selectedOptionsConversationId;
                        closeConversationOptionsMenu();
                        if (!conversationId) return;
                        try {
                            await toggleMuteConversationById(conversationId);
                        } catch (error) {
                            alert(error.message || 'Unable to update mute setting.');
                        }
                        return;
                    }

                    const conversationHideButton =
                        e.target.closest('.conversation-hide-button');
                    if (conversationHideButton) {
                        e.preventDefault();
                        e.stopPropagation();
                        const conversationId = selectedOptionsConversationId;
                        closeConversationOptionsMenu();
                        if (!conversationId) return;
                        try {
                            await hideConversationFromList(conversationId);
                        } catch (error) {
                            alert(error.message || 'Unable to hide conversation.');
                        }
                        return;
                    }

                    const conversationLeaveButton =
                        e.target.closest('.conversation-leave-button');
                    if (conversationLeaveButton) {
                        e.preventDefault();
                        e.stopPropagation();
                        const conversationId = selectedOptionsConversationId;
                        closeConversationOptionsMenu();
                        if (!conversationId) return;
                        openLeaveGroupConfirmModal(conversationId);
                        return;
                    }


                    // =============================================
                    // CLOSE DROPDOWN WHEN CLICKING ELSEWHERE
                    // =============================================

                    if (
                        !e.target.closest(
                            '.user-options-wrapper'
                        ) &&
                        !e.target.closest(
                            '#floatingUserOptionsMenu'
                        )
                    ) {
                        closeUserOptionsMenu();
                    }

                    if (
                        !e.target.closest('.conversation-options-button') &&
                        !e.target.closest('#floatingConversationOptionsMenu')
                    ) {
                        closeConversationOptionsMenu();
                    }
                }
            );

            // Keep the floating menu attached visually to the button.
            // Closing on scroll/resize avoids leaving it behind.
            document
                .getElementById('modalUsersContainer')
                ?.addEventListener('scroll', closeUserOptionsMenu, { passive: true });

            document
                .getElementById('modalConversationsContainer')
                ?.addEventListener('scroll', closeConversationOptionsMenu, { passive: true });

            window.addEventListener('resize', closeUserOptionsMenu);
            window.addEventListener('resize', closeConversationOptionsMenu);

            // =====================================================
            // ESCAPE CLOSES DROPDOWN
            // =====================================================
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    closeUserOptionsMenu();
                    closeConversationOptionsMenu();
                }
            });

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

            const sendButton = document.getElementById('modalSendButton');
            if (sendButton) {
                sendButton.addEventListener('click', event => {
                    if (sendButton.dataset.composerMode !== 'like') {
                        return;
                    }
                    event.preventDefault();
                    sendLikeFromComposer();
                });
            }

            const messageInput = document.getElementById('modalMessageInput');
            if (messageInput) {
                messageInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        if (!composerHasSendableContent()) {
                            return;
                        }
                        sendModalMessage(e);
                    }
                });

                messageInput.addEventListener('input', () => {
                    messageInput.style.height = 'auto';
                    messageInput.style.height = Math.min(messageInput.scrollHeight, 120) + 'px';
                    updateComposerActionButton();
                });
            }

            const attachmentButton = document.getElementById('modalAttachmentButton');
            const attachmentInput = document.getElementById('modalAttachmentInput');

            if (attachmentButton && attachmentInput) {
                attachmentButton.addEventListener('click', () => {
                    attachmentInput.click();
                });

                attachmentInput.addEventListener('change', (e) => {
                    const files = Array.from(e.target.files || []);
                    if (files.length) {
                        uploadModalAttachments(files);
                    }
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
                                if (opening) {
                                    positionMessageMoreMenu(moreButton, menu);
                                } else {
                                    menu.classList.add('hidden');
                                }
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
                    if (ignoreProgrammaticScroll) {
                        return;
                    }

                    const distanceFromBottom =
                        messagesContainer.scrollHeight -
                        messagesContainer.scrollTop -
                        messagesContainer.clientHeight;

                    stickThreadToBottom = distanceFromBottom < 80;

                    if (
                        !threadSettled ||
                        stickThreadToBottom ||
                        messagesContainer.scrollTop > 8 ||
                        !hasMoreMessages ||
                        isLoadingMessages
                    ) {
                        return;
                    }

                    const prevHeight = messagesContainer.scrollHeight;
                    messagesPage++;
                    loadModalMessages(currentConversationId, true).then(() => {
                        withProgrammaticScroll(() => {
                            messagesContainer.scrollTop =
                                messagesContainer.scrollHeight - prevHeight;
                        });
                    });
                }, { passive: true });

                messagesContainer.addEventListener('load', event => {
                    if (!(event.target instanceof HTMLImageElement)) {
                        return;
                    }

                    const lastRow = Array.from(
                        messagesContainer.querySelectorAll('.message-row')
                    ).pop();

                    if (
                        lastRow?.contains(event.target) &&
                        stickThreadToBottom &&
                        !threadScrollAnimation
                    ) {
                        snapThreadToBottom();
                    }
                }, true);
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
            listenToPrivateCallsRealtime();
            lucideCreateIcons();
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
                    if (isMessagingFullscreen()) {
                        setMessagingFullscreen(false);
                        return;
                    }
                    if (
                        isMessagingMobile() &&
                        document
                            .getElementById('messagingModalContainer')
                            ?.classList.contains('messaging-thread-open')
                    ) {
                        resetModalChat();
                        return;
                    }
                    closeMessagingModal();
                }
            }
        });

        // =====================================================
        // CONVERSATION SEARCH BUTTONS
        // =====================================================

        document
            .getElementById(
                'modalConversationSearchButton'
            )
            ?.addEventListener(
                'click',
                openConversationMessageSearch
            );

        document
            .getElementById(
                'modalConversationSearchClose'
            )
            ?.addEventListener(
                'click',
                closeConversationMessageSearch
            );

        document
            .getElementById(
                'modalConversationSearchPrevious'
            )
            ?.addEventListener(
                'click',
                () => {
                    focusConversationSearchResult(
                        conversationSearchIndex - 1
                    );
                }
            );

        document
            .getElementById(
                'modalConversationSearchNext'
            )
            ?.addEventListener(
                'click',
                () => {
                    focusConversationSearchResult(
                        conversationSearchIndex + 1
                    );
                }
            );

        document
            .getElementById(
                'modalConversationMessageSearch'
            )
            ?.addEventListener(
                'input',
                queueConversationSearch
            );

        document
            .getElementById(
                'modalConversationMessageSearch'
            )
            ?.addEventListener(
                'keydown',
                event => {

                    if (event.key === 'Enter') {

                        event.preventDefault();

                        if (event.shiftKey) {
                            focusConversationSearchResult(
                                conversationSearchIndex - 1
                            );
                        } else {
                            focusConversationSearchResult(
                                conversationSearchIndex + 1
                            );
                        }
                    }

                    if (event.key === 'Escape') {
                        closeConversationMessageSearch();
                    }
                }
            );


    })();

    

</script>

<style>

    .message-more {
        overflow: visible;
    }

    .message-more-menu {
        overflow: visible;
    }

    .message-more-menu::after {
        content: '';
        position: absolute;
        left: 50%;
        width: 0;
        height: 0;
        margin-left: -6px;
        border: 6px solid transparent;
    }

    .message-more-menu-up::after {
        top: 100%;
        border-top-color: #fff;
        filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.08));
    }

    .message-more-menu-down::after {
        bottom: 100%;
        border-bottom-color: #fff;
        filter: drop-shadow(0 -1px 1px rgba(0, 0, 0, 0.08));
    }

    /* ======================================
    SEARCH INSIDE CONVERSATION
    ====================================== */

    .conversation-search-match .message-bubble {
        outline: 1px solid rgba(17, 24, 39, 0.16);
        outline-offset: 3px;
        transition:
            outline-color 0.2s ease,
            box-shadow 0.2s ease,
            transform 0.2s ease;
    }

    .conversation-search-current .message-bubble {
        outline: 2px solid rgba(17, 24, 39, 0.55);
        outline-offset: 3px;
        box-shadow: 0 0 0 5px rgba(17, 24, 39, 0.08);
    }


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

    .messaging-hover-tooltip {
        opacity: 0;
        transform: translateY(4px) scale(0.98);
        transition: opacity 0.12s ease, transform 0.12s ease;
    }

    .messaging-hover-tooltip.is-visible {
        opacity: 1;
        transform: translateY(0) scale(1);
    }

    /* ======================================
       MESSAGING MODAL CONTAINER
    ====================================== */

    #modalMessagesContainer:not(.hidden) {
        display: block;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
        overflow-anchor: none;
        scrollbar-gutter: stable;
    }

    #modalMessagesContainer > * + * {
        margin-top: 0.625rem;
    }

    .pin-system-notice-flash {
        animation: pinNoticeFlash 1.4s ease;
    }

    @keyframes pinNoticeFlash {
        0% {
            background-color: rgb(219 234 254);
        }
        100% {
            background-color: transparent;
        }
    }

    .message-bubble-line,
    .message-content-wrapper,
    .message-bubble {
        overflow: visible;
    }

    .message-content-wrapper {
        position: relative;
    }

    .message-content-wrapper > .message-reactions {
        position: absolute;
        right: 6px;
        bottom: 2px;
        z-index: 30;
        display: flex;
        align-items: flex-end;
        gap: 1px;
        line-height: 0;
        transform: translate(50%, 50%);
        pointer-events: auto;
    }

    .message-content-wrapper:has(.message-reaction-chip) {
        padding-bottom: 0;
    }

    .message-row:has(.message-reaction-chip) .message-status-wrapper {
        margin-top: 0.7rem;
    }

    .message-reaction-chip {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
        min-width: 0;
        cursor: pointer;
    }

    .message-reaction-chip:hover {
        background: transparent !important;
        transform: scale(1.12);
    }

    .message-reaction-emoji {
        font-size: 16px;
        line-height: 1;
        filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.28));
    }

    .message-reaction-count {
        font-size: 10px;
        line-height: 1;
        color: #6b7280;
        font-weight: 600;
    }

    #modalConversationsContainer,
    #modalUsersContainer {
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
        contain: content;
    }

    #messagingModalContainer {
        will-change: auto;
        min-width: 0;
    }

    #messagingModalContainer.is-fullscreen {
        width: 100% !important;
        max-width: none !important;
        height: 100dvh !important;
        max-height: 100dvh !important;
        margin: 0 !important;
        border-radius: 0 !important;
    }

    #messagingModalBackdrop:has(#messagingModalContainer.is-fullscreen) {
        align-items: stretch;
        justify-content: stretch;
        padding: 0;
    }

    #messagingModalWindowControls {
        top: max(0.65rem, env(safe-area-inset-top));
        right: max(0.65rem, env(safe-area-inset-right));
    }

    #messagingModalContainer:has(#modalChatHeader:not(.hidden)) #messagingModalWindowControls {
        display: none;
    }

    #modalComposer {
        padding-bottom: env(safe-area-inset-bottom);
    }

    @media (max-width: 767px) {
        #messagingModalBackdrop {
            align-items: stretch;
            justify-content: stretch;
            padding: 0;
        }

        #messagingModalContainer:not(.is-fullscreen) {
            width: 100%;
            max-width: none;
            height: 100dvh;
            max-height: 100dvh;
            margin: 0;
            border-radius: 0;
        }

        #messagingModalContainer:not(.messaging-thread-open) #modalChatArea {
            display: none !important;
        }

        #messagingModalContainer.messaging-thread-open #modalConversationsPane {
            display: none !important;
        }

        #messagingModalContainer.messaging-thread-open #modalChatArea {
            display: flex !important;
            width: 100%;
            min-width: 0;
        }

        #modalConversationInfoSidebar:not(.hidden) {
            position: absolute;
            inset: 0;
            z-index: 40;
            width: 100% !important;
            max-width: none !important;
            border-left: 0;
        }

        #modalComposer .px-4 {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }
    }

    @media (max-width: 379px) {
        #modalAudioCallButton,
        #modalVideoCallButton {
            display: none;
        }
    }

    /* ======================================
       CONVERSATION LIST
    ====================================== */

    #modalConversationsList .conversation-item {
        transition: background-color 0.12s ease;
    }

    /* ======================================
       CONVERSATION DELETE BUTTON
    ====================================== */

    .conversation-delete-btn {
        will-change: auto;
    }

    .conversation-delete-btn:active {
        transform: scale(0.9) !important;
    }

    /* ======================================
       USER LIST
    ====================================== */

    .user-row {
        will-change: auto;
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

    #modalMessagesContainer [style*="messageSlideIn"] {
        animation: fadeInUp 0.12s ease-out both;
    }

    /* ======================================
       MESSAGE COMPOSER
    ====================================== */

    #modalMessageInput {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        outline: none !important;
    }

    #modalMessageInput:focus {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        outline: none !important;
    }

    #modalMessageInput::placeholder {
        color: #9CA3AF;
    }

    .messenger-like-icon {
        color: #0084FF;
        fill: currentColor;
        stroke: none;
    }

    #modalSendButton:active:not(:disabled) {
        transform: scale(0.92);
    }

    #modalSendButton:hover:not(:disabled) {
        transform: scale(1.05);
    }

    #modalAttachmentButton {
        transition: background-color 0.12s ease, color 0.12s ease;
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
        scroll-behavior: auto;
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

    /* Chat header and composer stay static so opening a
       conversation does not shrink the thread after scroll. */

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
