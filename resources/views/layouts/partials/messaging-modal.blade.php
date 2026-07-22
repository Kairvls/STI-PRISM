{{-- ===================================================== --}}
{{-- MESSAGING MODAL --}}
{{-- Shared across all authenticated modules --}}
{{-- ===================================================== --}}

<div id="messagingModal" class="hidden" aria-hidden="true">
    {{-- Backdrop --}}
    <div id="messagingModalBackdrop" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm opacity-0 transition-opacity duration-300 ease-out">
        {{-- Modal Container --}}
        <div id="messagingModalContainer" class="relative mx-4 w-full max-w-[960px] h-[85vh] max-h-[720px] bg-white rounded-2xl shadow-[0_25px_60px_rgba(0,0,0,0.25)] overflow-hidden scale-[0.95] opacity-0 transition-all duration-300 ease-out flex">
            
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
                                autocomplete="off"
                            />
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
                                autocomplete="off"
                            />
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

                    {{-- Messages (scrollable) --}}
                    <div id="modalMessagesContainer" class="hidden flex flex-col flex-1 overflow-y-auto min-h-0 gap-3 p-4">
                    </div>
                </div>

                {{-- Composer --}}
                <div id="modalComposer" class="hidden shrink-0 border-t border-gray-100">
                    <div class="p-4">
                        <form id="modalMessageForm" class="flex items-end gap-2">
                            <textarea
                                id="modalMessageInput"
                                rows="1"
                                placeholder="Type a message..."
                                class="flex-1 rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-900 outline-none focus:border-gray-900 focus:ring-4 focus:ring-gray-100 transition-all duration-200 resize-none"
                                style="min-height: 42px; max-height: 120px;"
                            ></textarea>
                            <input type="file" id="modalAttachmentInput" class="hidden" />
                            <button
                                type="button"
                                id="modalAttachmentButton"
                                class="h-10 w-10 shrink-0 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-900"
                                title="Attach file"
                            >
                                <i data-lucide="paperclip" class="h-4 w-4"></i>
                            </button>
                            <button
                                type="submit"
                                class="h-10 w-10 shrink-0 flex items-center justify-center rounded-lg bg-gray-900 text-white transition hover:bg-gray-800 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                                id="modalSendButton"
                            >
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
            return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
        } else if (days === 1) {
            return 'Yesterday';
        } else if (days < 7) {
            return date.toLocaleDateString('en-US', { weekday: 'short' });
        } else {
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }
    }

    function formatMessageTime(dateString) {
        if (!dateString) return '';
        return new Date(dateString).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
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
            return { icon: 'image', color: 'text-purple-600 bg-purple-50' };
        } else if (type.startsWith('video/')) {
            return { icon: 'film', color: 'text-indigo-600 bg-indigo-50' };
        } else if (type.startsWith('audio/')) {
            return { icon: 'music', color: 'text-pink-600 bg-pink-50' };
        } else if (type === 'application/pdf') {
            return { icon: 'file-text', color: 'text-red-600 bg-red-50' };
        } else if (['doc', 'docx'].includes(ext)) {
            return { icon: 'file-text', color: 'text-blue-600 bg-blue-50' };
        } else if (['xls', 'xlsx'].includes(ext)) {
            return { icon: 'file-spreadsheet', color: 'text-emerald-600 bg-emerald-50' };
        } else if (['ppt', 'pptx'].includes(ext)) {
            return { icon: 'presentation', color: 'text-orange-600 bg-orange-50' };
        } else if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) {
            return { icon: 'archive', color: 'text-yellow-600 bg-yellow-50' };
        } else if (ext === 'txt') {
            return { icon: 'file', color: 'text-gray-600 bg-gray-50' };
        }
        return { icon: 'file', color: 'text-gray-500 bg-gray-100' };
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
        currentConversationId = null;
        messagesPage = 1;
        isLoadingMessages = false;
        hasMoreMessages = true;

        const chatEmpty = document.getElementById('modalChatEmptyState');
        const messagesContainer = document.getElementById('modalMessagesContainer');
        const chatHeader = document.getElementById('modalChatHeader');
        const composer = document.getElementById('modalComposer');
        const chatArea = document.getElementById('modalChatArea');

        if (chatEmpty) chatEmpty.classList.remove('hidden');
        if (messagesContainer) {
            messagesContainer.classList.add('hidden');
            messagesContainer.innerHTML = '';
        }
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

    async function loadModalConversations() {
        const search = document.getElementById('modalConversationSearch')?.value || '';
        const params = new URLSearchParams();
        if (search) params.set('search', search);

        const response = await fetch(`/messages/conversations?${params.toString()}`, {
            headers: { 'Accept': 'application/json' }
        });
        if (!response.ok) return;
        const data = await response.json();
        renderModalConversations(data.data);
    }

    function renderModalConversations(conversations) {
        const container = document.getElementById('modalConversationsList');
        const emptyState = document.getElementById('modalConversationsEmpty');

        if (!container) return;

        if (!conversations || conversations.data.length === 0) {
            container.innerHTML = '';
            emptyState?.classList.remove('hidden');
            emptyState?.classList.add('animate-fade-in');
            return;
        }

        emptyState?.classList.add('hidden');

        container.innerHTML = conversations.data.map(conv => {
            const lastMessage = conv.last_message || {};
            const otherParticipant = conv.participants?.find(p => p.user?.user_id !== currentUserId)?.user || {};
            const name = otherParticipant.user_full_name || 'Unknown';
            const initials = getInitials(name);
            const preview = lastMessage.message_content ? lastMessage.message_content.substring(0, 50) + (lastMessage.message_content.length > 50 ? '...' : '') : 'No messages';
            const time = formatTime(lastMessage.created_at);
            const unreadCount = conv.unread_count || 0;
            const activeClass = conv.conversation_id === currentConversationId ? 'bg-gray-100' : '';

            return `
                <div class="conversation-item group p-3 cursor-pointer transition-all duration-200 ease-out hover:bg-gray-50 ${activeClass}" data-id="${conv.conversation_id}">
                    <div class="flex items-start gap-3">
                        <div class="h-10 w-10 shrink-0 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center text-xs font-semibold text-gray-600 transition-all duration-200">
                            ${initials}
                        </div>
                        <div class="flex-1 min-w-0 pt-0.5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm font-semibold text-gray-900 truncate transition-colors duration-200">${name}</span>
                                <span class="text-[11px] text-gray-400 shrink-0 transition-colors duration-200">${time}</span>
                            </div>
                            <div class="flex items-center justify-between gap-2 mt-0.5">
                                <p class="text-xs text-gray-500 truncate transition-colors duration-200">${preview}</p>
                                ${unreadCount > 0 ? `<span class="shrink-0 inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-gray-900 px-1.5 text-[10px] font-bold text-white transition-all duration-200 hover:bg-gray-800">${unreadCount}</span>` : ''}
                            </div>
                        </div>
                        <div class="shrink-0 pt-0.5">
                            <button type="button" onclick="event.stopPropagation(); confirmDeleteConversation(${conv.conversation_id})" class="conversation-delete-btn opacity-0 group-hover:opacity-100 ${activeClass ? 'opacity-100' : ''} h-7 w-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-all duration-200 hover:scale-110">
                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        lucideCreateIcons();
    }

    async function openModalConversation(conversationId) {
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
            headers: { 'Accept': 'application/json' }
        });
        if (!response.ok) return;
        const data = await response.json();
        const conversation = data.data;

        const otherParticipant = conversation.participants?.find(p => p.user?.user_id !== currentUserId)?.user || {};
        const name = otherParticipant.user_full_name || 'Unknown';
        const role = otherParticipant.role?.role_name || '';

        document.getElementById('modalChatTitle').textContent = name;
        document.getElementById('modalChatSubtitle').textContent = role;
        document.getElementById('modalChatAvatar').innerHTML = getInitials(name);

        await loadModalMessages(conversationId);
    }

    async function loadModalMessages(conversationId, append = false) {
        if (isLoadingMessages || !hasMoreMessages) return;
        isLoadingMessages = true;

        const container = document.getElementById('modalMessagesContainer');
        if (!append) container.innerHTML = '';

        const response = await fetch(`/messages/conversations/${conversationId}/messages?page=${messagesPage}`, {
            headers: { 'Accept': 'application/json' }
        });
        if (!response.ok) {
            isLoadingMessages = false;
            return;
        }

        const data = await response.json();
        const messages = data.data;

        if (messages.data.length === 0 && !append) {
            container.innerHTML = `
                <div class="text-center py-12">
                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 mb-3">
                        <i data-lucide="message-square" class="h-6 w-6"></i>
                    </div>
                    <p class="text-sm font-semibold text-gray-800">No messages yet</p>
                    <p class="text-xs text-gray-500 mt-1">Send the first message below</p>
                </div>
            `;
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
        const html = orderedMessages.map(msg => {
            const isOwn = msg.sender?.user_id === userId;
            const time = formatMessageTime(msg.created_at);
            const senderName = msg.sender?.name || msg.sender?.user_full_name || 'Unknown';
            const attachment = msg.attachment || null;

            if (isOwn) {
                return `
                    <div class="flex justify-end">
                        <div class="max-w-[70%] rounded-2xl rounded-br-md bg-gray-900 text-white px-4 py-2.5">
                            ${msg.message_content ? `<p class="text-sm whitespace-pre-wrap break-words">${msg.message_content}</p>` : ''}
                            ${attachment ? getAttachmentPreviewHtml(attachment, time) : ''}
                            ${!msg.message_content && !attachment ? '' : `<span class="text-[10px] text-gray-400 mt-1 block text-right">${time}</span>`}
                        </div>
                    </div>
                `;
            } else {
                return `
                    <div class="flex justify-start">
                        <div class="max-w-[70%] rounded-2xl rounded-bl-md bg-gray-100 text-gray-900 px-4 py-2.5">
                            <p class="text-[11px] font-semibold text-gray-500 mb-0.5">${senderName}</p>
                            ${msg.message_content ? `<p class="text-sm whitespace-pre-wrap break-words">${msg.message_content}</p>` : ''}
                            ${attachment ? getAttachmentPreviewHtml(attachment, time) : ''}
                            ${!msg.message_content && !attachment ? '' : `<span class="text-[10px] text-gray-400 mt-1 block text-right">${time}</span>`}
                        </div>
                    </div>
                `;
            }
        }).join('');

        if (append) {
            container.insertAdjacentHTML('afterbegin', html);
        } else {
            container.innerHTML = html;
        }

        lucideCreateIcons();
        scrollToBottom(false, !append);
        isLoadingMessages = false;
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

        const input = document.getElementById('modalMessageInput');
        const content = input?.value.trim();
        if (!content && !selectedAttachment) return;
        if (!currentConversationId) return;

        const form = document.getElementById('modalMessageForm');
        if (!form) return;

        const tempId = 'temp-' + Date.now();
        const time = new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
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
        container.insertAdjacentHTML('beforeend', tempHtml);
        scrollToBottom(true, true);

        const formData = new FormData();
        formData.append('message_content', content || '');
        
        if (selectedAttachment) {
            formData.append('attachment', JSON.stringify(selectedAttachment));
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
            const realHtml = `
                <div class="flex justify-end" style="animation: messageSlideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1)">
                    <div class="max-w-[70%] rounded-2xl rounded-br-md bg-gray-900 text-white px-4 py-2.5">
                        ${msg.message_content ? `<p class="text-sm whitespace-pre-wrap break-words">${msg.message_content}</p>` : ''}
                        ${attachment ? getAttachmentPreviewHtml(attachment, msgTime, false) : ''}
                        ${!msg.message_content && !attachment ? `<p class="text-sm text-gray-400">Empty message</p>` : ''}
                        ${msg.message_content || attachment ? `<span class="text-[10px] text-gray-400 mt-1 block text-right">${msgTime}</span>` : ''}
                    </div>
                </div>
            `;
            if (isFirstMessage) {
                container.innerHTML = realHtml;
            } else {
                container.insertAdjacentHTML('beforeend', realHtml);
            }
            scrollToBottom(true, true);

            clearSelectedAttachment();
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
                xhr.onload = () => resolve({ status: xhr.status, data: JSON.parse(xhr.responseText) });
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
            headers: { 'Accept': 'application/json' }
        });
        if (!response.ok) return;
        const data = await response.json();
        renderModalUsers(data.data);
    }

    function renderModalUsers(users) {
        const container = document.getElementById('modalUsersList');
        const emptyState = document.getElementById('modalUsersEmpty');

        if (!container) return;

        if (!users || users.length === 0) {
            container.innerHTML = '';
            emptyState?.classList.remove('hidden');
            lucideCreateIcons();
            return;
        }

        emptyState?.classList.add('hidden');

        container.innerHTML = users.map((user, index) => `
            <button type="button" data-user-id="${user.user_id}" class="user-row w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-left cursor-pointer transition-all duration-200 ease-out hover:bg-gray-50 active:bg-gray-100" style="animation: userCardIn 0.3s cubic-bezier(0.4, 0, 0.2, 1) both; animation-delay: ${index * 25}ms;">
                <div class="relative shrink-0">
                    <div class="h-9 w-9 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center text-xs font-bold text-gray-700 shadow-sm ring-2 ring-white transition-all duration-200">
                        ${user.initials}
                    </div>
                    <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-white bg-emerald-500 shadow-sm"></span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate leading-tight transition-colors duration-200">${user.name}</p>
                    <p class="text-xs text-gray-500 truncate leading-tight transition-colors duration-200">${user.role}</p>
                </div>
            </button>
        `).join('');

        lucideCreateIcons();
    }

    async function startConversationWithUser(userId) {
        const response = await fetch('/messages/conversations', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ user_id: userId })
        });

        if (!response.ok) return;
        const data = await response.json();
        const conversation = data.data;

        switchModalTab('conversations');
        await loadModalConversations();
        
        setTimeout(() => {
            openModalConversation(conversation.conversation_id);
        }, 150);
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

        const messagesContainer = document.getElementById('modalMessagesContainer');
        if (messagesContainer) {
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
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeInScale {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }

    @keyframes slideInLeft {
        from { opacity: 0; transform: translateX(-10px); }
        to { opacity: 1; transform: translateX(0); }
    }

    @keyframes slideInRight {
        from { opacity: 0; transform: translateX(10px); }
        to { opacity: 1; transform: translateX(0); }
    }

    @keyframes scaleIn {
        from { transform: scale(0.92); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    @keyframes badgePop {
        0% { transform: scale(0); }
        60% { transform: scale(1.15); }
        100% { transform: scale(1); }
    }

    @keyframes shimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }

    @keyframes conversationSlideIn {
        from { opacity: 0; transform: translateX(-10px); }
        to { opacity: 1; transform: translateX(0); }
    }

    @keyframes userCardIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes inputFocusGlow {
        0% { box-shadow: 0 0 0 0 rgba(15, 23, 42, 0.08); }
        50% { box-shadow: 0 0 0 4px rgba(15, 23, 42, 0.08); }
        100% { box-shadow: 0 0 0 4px rgba(15, 23, 42, 0.06); }
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
        background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.05) 50%, rgba(255,255,255,0) 100%);
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
</style>
