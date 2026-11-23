{{-- ===================================================== --}}
{{-- MESSAGING MODAL --}}
{{-- Shared across all authenticated modules --}}
{{-- ===================================================== --}}
<div
    id="messagingHoverTooltip"
    class="messaging-hover-tooltip pointer-events-none fixed z-[10080] hidden max-w-[240px] rounded-xl border border-slate-200/80 bg-white px-2.5 py-1.5 text-center text-[11px] font-medium leading-snug text-slate-700 shadow-[0_8px_24px_rgba(15,23,42,0.14)]"
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
                            <p id="messagingModalSubtitle" class="text-[11px] text-gray-500 mt-0.5">Your conversations</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            <button
                                type="button"
                                id="messagingMuteAllButton"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-900"
                                data-tooltip="Mute all notifications"
                                aria-label="Mute all notifications"
                                aria-pressed="false"
                            >
                                <i data-lucide="bell" class="h-4 w-4"></i>
                            </button>
                            <button
                                type="button"
                                id="messagingFullscreenButton"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-900"
                                data-tooltip="Full screen"
                                aria-label="Full screen"
                            >
                                <i data-lucide="maximize-2" class="h-4 w-4"></i>
                            </button>
                        </div>
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

                {{-- Empty state: shown until a conversation is selected --}}
                <div
                    id="modalChatEmptyState"
                    class="flex min-h-0 flex-1 items-center justify-center bg-white px-6"
                    aria-hidden="false"
                >
                    <div class="text-center">
                        <div class="mx-auto mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M14 9a2 2 0 0 1-2 2H6l-4 4V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2z"></path>
                                <path d="M18 9h2a2 2 0 0 1 2 2v11l-4-4h-6a2 2 0 0 1-2-2v-1"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Select a conversation to show</h3>
                        <p class="mx-auto mt-1 max-w-sm text-sm text-gray-500">Choose a conversation to start chatting.</p>
                    </div>
                </div>

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
                        {{-- HEADER ACTIONS: INFO (+ call/video kept but hidden for later) --}}
                        {{-- ===================================================== --}}
                        <div
                            id="modalChatHeaderActions"
                            class="ml-auto flex shrink-0 items-center justify-end gap-1"
                        >
                            {{-- Deferred: audio/video call UI — feature code remains; re-show by removing `hidden`. --}}
                            <button
                                type="button"
                                id="modalAudioCallButton"
                                class="hidden h-9 w-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-900"
                                data-tooltip="Audio call"
                                aria-label="Audio call"
                                aria-hidden="true"
                                tabindex="-1"
                            >
                                <i data-lucide="phone" class="h-5 w-5"></i>
                            </button>

                            <button
                                type="button"
                                id="modalVideoCallButton"
                                class="hidden h-9 w-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-900"
                                data-tooltip="Video call"
                                aria-label="Video call"
                                aria-hidden="true"
                                tabindex="-1"
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
                        hidden
                        min-w-0
                        max-w-full
                        flex-1
                        flex-col
                        overflow-hidden
                        min-h-0
                    "
                >

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
                            z-40
                            hidden
                            flex-col
                            justify-end
                            gap-3
                            overflow-hidden
                            bg-white
                            px-5
                            py-6
                        "
                        aria-hidden="true"
                    >
                        <div class="flex animate-pulse flex-col gap-3" role="status" aria-label="Loading messages">
                            <div class="mx-auto h-3 w-24 rounded-full bg-gray-100"></div>

                            <div class="flex items-end gap-2">
                                <div class="h-7 w-7 shrink-0 rounded-full bg-gray-200"></div>
                                <div class="flex flex-col gap-1.5">
                                    <div class="h-9 w-48 rounded-2xl rounded-bl-sm bg-gray-200"></div>
                                    <div class="h-9 w-32 rounded-2xl rounded-bl-sm bg-gray-200"></div>
                                </div>
                            </div>

                            <div class="flex flex-col items-end gap-1.5">
                                <div class="h-9 w-56 rounded-2xl rounded-br-sm bg-gray-200"></div>
                                <div class="h-9 w-36 rounded-2xl rounded-br-sm bg-gray-200"></div>
                            </div>

                            <div class="flex items-end gap-2">
                                <div class="h-7 w-7 shrink-0 rounded-full bg-gray-200"></div>
                                <div class="h-16 w-52 rounded-2xl rounded-bl-sm bg-gray-200"></div>
                            </div>

                            <div class="flex flex-col items-end gap-1.5">
                                <div class="h-9 w-40 rounded-2xl rounded-br-sm bg-gray-200"></div>
                            </div>

                            <div class="flex items-end gap-2">
                                <div class="h-7 w-7 shrink-0 rounded-full bg-gray-200"></div>
                                <div class="h-9 w-44 rounded-2xl rounded-bl-sm bg-gray-200"></div>
                            </div>

                            <div class="flex flex-col items-end gap-1.5">
                                <div class="h-9 w-28 rounded-2xl rounded-br-sm bg-gray-200"></div>
                                <div class="h-9 w-48 rounded-2xl rounded-br-sm bg-gray-200"></div>
                            </div>
                        </div>
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
                        Group name <span class="text-red-500">*</span>
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
                            Members <span class="text-red-500">*</span>
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
                            bg-[#0025cc]
                            px-4
                            py-2
                            text-sm
                            font-semibold
                            text-white
                            transition
                            hover:bg-blue-800
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

@php
    // Shared Open always uses the messages document viewer (any role can open in a new tab).
    $procurementDocUrlTemplates = [
        'ris' => route('messages.documents.show', ['type' => 'ris', 'id' => 987654321]),
        'atp' => route('messages.documents.show', ['type' => 'atp', 'id' => 987654321]),
        'rfc' => route('messages.documents.show', ['type' => 'rfc', 'id' => 987654321]),
        'dv' => route('messages.documents.show', ['type' => 'dv', 'id' => 987654321]),
        'rr' => route('messages.documents.show', ['type' => 'rr', 'id' => 987654321]),
        'liq' => route('messages.documents.show', ['type' => 'liq', 'id' => 987654321]),
        'lr' => route('messages.documents.show', ['type' => 'liq', 'id' => 987654321]),
    ];
    foreach ($procurementDocUrlTemplates as $k => $url) {
        $procurementDocUrlTemplates[$k] = str_replace('987654321', '__ID__', $url);
    }
    $procurementDocStatusTemplates = [];
    $procurementDocApproveTemplates = [];
    foreach (['ris', 'atp', 'rfc', 'dv', 'rr', 'liq', 'lr'] as $docType) {
        $statusType = $docType === 'lr' ? 'liq' : $docType;
        $procurementDocStatusTemplates[$docType] = str_replace(
            '987654321',
            '__ID__',
            route('messages.documents.status', ['type' => $statusType, 'id' => 987654321])
        );
        $procurementDocApproveTemplates[$docType] = str_replace(
            '987654321',
            '__ID__',
            route('messages.documents.approve', ['type' => $statusType, 'id' => 987654321])
        );
    }
    $messagingModalConfig = [
        'currentUserId' => auth()->id(),
        'currentUserName' => auth()->user()?->user_full_name ?? 'You',
        'currentUserProfilePicture' => auth()->user()?->user_profile_picture,
        'currentUserRoleIds' => \App\Support\RoleAccess::roleIds(auth()->user()),
        'procurementDocUrlTemplates' => $procurementDocUrlTemplates,
        'procurementDocStatusTemplates' => $procurementDocStatusTemplates,
        'procurementDocApproveTemplates' => $procurementDocApproveTemplates,
    ];
    $messagingCssVersion = @filemtime(public_path('css/messaging-modal.css')) ?: 1;
    $messagingJsVersion = @filemtime(public_path('js/messaging-modal.js')) ?: 1;
@endphp
<link rel="stylesheet" href="{{ asset('css/messaging-modal.css') }}?v={{ $messagingCssVersion }}">
<script>
    window.PaAyoMessagingConfig = @json($messagingModalConfig);
    window.currentUserId = window.PaAyoMessagingConfig.currentUserId;
</script>
<script src="{{ asset('js/messaging-modal.js') }}?v={{ $messagingJsVersion }}" defer></script>
