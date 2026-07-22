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
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <h2 class="text-base font-bold text-gray-900">Messages</h2>
                            <p class="text-[11px] text-gray-500 mt-0.5">Your conversations</p>
                        </div>
                        <button type="button" onclick="switchModalTab('users')" class="inline-flex items-center gap-1.5 rounded-lg bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-gray-800 active:scale-95">
                            <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                            New Message
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
            <main id="modalChatArea" class="hidden md:flex flex-1 flex-col bg-white">
                
                {{-- Empty state --}}
                <div id="modalChatEmptyState" class="flex-1 flex items-center justify-center">
                    <div class="text-center p-8">
                        <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-gray-400 mb-4">
                            <i data-lucide="messages-square" class="h-8 w-8"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Select a conversation</h3>
                        <p class="text-sm text-gray-500 mt-1 max-w-sm">Choose a conversation from the list to view messages and start chatting.</p>
                    </div>
                </div>

                {{-- Active conversation --}}
                <div id="modalChatActive" class="hidden flex-1 flex-col">
                    
                    {{-- Chat Header --}}
                    <div id="modalChatHeader" class="p-4 border-b border-gray-100 flex items-center gap-3">
                        <div id="modalChatAvatar" class="h-9 w-9 rounded-full bg-gradient-to-br from-emerald-100 to-emerald-200 flex items-center justify-center text-xs font-semibold text-emerald-700"></div>
                        <div class="min-w-0">
                            <h3 id="modalChatTitle" class="text-sm font-semibold text-gray-900 truncate"></h3>
                            <p id="modalChatSubtitle" class="text-xs text-gray-500 truncate"></p>
                        </div>
                    </div>

                    {{-- Messages --}}
                    <div id="modalMessagesContainer" class="flex-1 overflow-y-auto p-4 space-y-3">
                    </div>

                    {{-- Input --}}
                    <div class="p-4 border-t border-gray-100">
                        <form id="modalMessageForm" class="flex items-end gap-2">
                            <textarea
                                id="modalMessageInput"
                                rows="1"
                                placeholder="Type a message..."
                                class="flex-1 rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-900 outline-none focus:border-gray-900 focus:ring-4 focus:ring-gray-100 transition-all duration-200 resize-none"
                                style="min-height: 42px; max-height: 120px;"
                            ></textarea>
                            <button
                                type="submit"
                                class="h-10 w-10 shrink-0 flex items-center justify-center rounded-lg bg-gray-900 text-white transition hover:bg-gray-800 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                                id="modalSendButton"
                            >
                                <i data-lucide="send" class="h-4 w-4"></i>
                            </button>
                        </form>
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
    let currentConversationId = null;
    let messagesPage = 1;
    let isLoadingMessages = false;
    let hasMoreMessages = true;
    let userSearchTimeout = null;

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
        loadModalConversations();
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
        const chatActive = document.getElementById('modalChatActive');
        const chatArea = document.getElementById('modalChatArea');

        if (chatEmpty) chatEmpty.classList.remove('hidden');
        if (chatActive) chatActive.classList.add('hidden');
        if (chatArea) {
            chatArea.classList.remove('hidden');
            chatArea.classList.add('md:flex');
        }

        const container = document.getElementById('modalMessagesContainer');
        if (container) container.innerHTML = '';
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
            return;
        }

        emptyState?.classList.add('hidden');

        container.innerHTML = conversations.data.map(conv => {
            const lastMessage = conv.last_message || {};
            const otherParticipant = conv.participants?.find(p => p.user?.user_full_name)?.user || {};
            const name = otherParticipant.user_full_name || 'Unknown';
            const initials = getInitials(name);
            const preview = lastMessage.message_content ? lastMessage.message_content.substring(0, 50) + (lastMessage.message_content.length > 50 ? '...' : '') : 'No messages';
            const time = formatTime(lastMessage.created_at);
            const unreadCount = conv.unread_count || 0;
            const activeClass = conv.conversation_id === currentConversationId ? 'bg-gray-100' : '';

            return `
                <div class="conversation-item p-3 cursor-pointer transition hover:bg-gray-50 ${activeClass}" data-id="${conv.conversation_id}">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 shrink-0 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center text-xs font-semibold text-gray-600">
                            ${initials}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm font-semibold text-gray-900 truncate">${name}</span>
                                <span class="text-[11px] text-gray-400 shrink-0">${time}</span>
                            </div>
                            <div class="flex items-center justify-between gap-2 mt-0.5">
                                <p class="text-xs text-gray-500 truncate">${preview}</p>
                                ${unreadCount > 0 ? `<span class="shrink-0 inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-gray-900 px-1.5 text-[10px] font-bold text-white">${unreadCount}</span>` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        container.querySelectorAll('.conversation-item').forEach(item => {
            item.addEventListener('click', () => {
                const id = item.dataset.id;
                openModalConversation(id);
            });
        });

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
        const chatActive = document.getElementById('modalChatActive');
        const chatArea = document.getElementById('modalChatArea');

        if (chatEmpty) chatEmpty.classList.add('hidden');
        if (chatActive) chatActive.classList.remove('hidden');
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

        const otherParticipant = conversation.participants?.find(p => p.user?.user_full_name)?.user || {};
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
            const senderInitials = getInitials(senderName);

            if (isOwn) {
                return `
                    <div class="flex justify-end message-bubble">
                        <div class="max-w-[70%] rounded-2xl rounded-br-md bg-gray-900 text-white px-4 py-2.5">
                            <p class="text-sm whitespace-pre-wrap break-words">${msg.message_content}</p>
                            <span class="text-[10px] text-gray-400 mt-1 block text-right">${time}</span>
                        </div>
                    </div>
                `;
            } else {
                return `
                    <div class="flex justify-start message-bubble">
                        <div class="max-w-[70%] rounded-2xl rounded-bl-md bg-gray-100 text-gray-900 px-4 py-2.5">
                            <p class="text-[11px] font-semibold text-gray-500 mb-0.5">${senderName}</p>
                            <p class="text-sm whitespace-pre-wrap break-words">${msg.message_content}</p>
                            <span class="text-[10px] text-gray-400 mt-1 block text-right">${time}</span>
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
        scrollToBottom();
        isLoadingMessages = false;
    }

    function scrollToBottom() {
        const container = document.getElementById('modalMessagesContainer');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }

    async function sendModalMessage(e) {
        e.preventDefault();

        const input = document.getElementById('modalMessageInput');
        const content = input?.value.trim();
        if (!content || !currentConversationId) return;

        const form = document.getElementById('modalMessageForm');
        if (!form) return;

        const tempId = 'temp-' + Date.now();
        const time = new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
        const container = document.getElementById('modalMessagesContainer');

        const tempHtml = `
            <div class="flex justify-end message-bubble" id="${tempId}">
                <div class="max-w-[70%] rounded-2xl rounded-br-md bg-gray-900 text-white px-4 py-2.5 opacity-70">
                    <p class="text-sm whitespace-pre-wrap break-words">${content}</p>
                    <span class="text-[10px] text-gray-400 mt-1 block text-right">${time}</span>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', tempHtml);
        scrollToBottom();
        input.value = '';
        input.style.height = 'auto';

        const response = await fetch(`/messages/conversations/${currentConversationId}/send`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ message_content: content })
        });

        if (response.ok) {
            const data = await response.json();
            const temp = document.getElementById(tempId);
            if (temp) temp.remove();
            
            const msg = data.data;
            const msgTime = formatMessageTime(msg.created_at);
            const realHtml = `
                <div class="flex justify-end message-bubble">
                    <div class="max-w-[70%] rounded-2xl rounded-br-md bg-gray-900 text-white px-4 py-2.5">
                        <p class="text-sm whitespace-pre-wrap break-words">${msg.message_content}</p>
                        <span class="text-[10px] text-gray-400 mt-1 block text-right">${msgTime}</span>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', realHtml);
            scrollToBottom();

            await loadModalConversations();
        } else {
            const temp = document.getElementById(tempId);
            if (temp) temp.remove();
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

        container.innerHTML = users.map(user => `
            <button type="button" data-user-id="${user.user_id}" class="user-row w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-left cursor-pointer transition-all duration-200 ease-out hover:bg-gray-50 active:bg-gray-100">
                <div class="relative shrink-0">
                    <div class="h-9 w-9 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center text-xs font-bold text-gray-700 shadow-sm ring-2 ring-white">
                        ${user.initials}
                    </div>
                    <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-white bg-emerald-500 shadow-sm"></span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate leading-tight">${user.name}</p>
                    <p class="text-xs text-gray-500 truncate leading-tight">${user.role}</p>
                </div>
            </button>
        `).join('');

        container.querySelectorAll('.user-row').forEach(row => {
            row.addEventListener('click', () => {
                const userId = parseInt(row.getAttribute('data-user-id') || '0');
                if (userId) startConversationWithUser(userId);
            });
        });

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
    .message-bubble {
        animation: messageSlideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes messageSlideIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .user-card {
        animation: userCardIn 0.35s cubic-bezier(0.4, 0, 0.2, 1) both;
    }

    @keyframes userCardIn {
        from {
            opacity: 0;
            transform: translateY(12px) scale(0.96);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .messaging-user-scroll::-webkit-scrollbar {
        width: 5px;
    }

    .messaging-user-scroll::-webkit-scrollbar-track {
        background: transparent;
        border-radius: 20px;
    }

    .messaging-user-scroll::-webkit-scrollbar-thumb {
        background: #E2E8F0;
        border-radius: 20px;
        transition: background 0.2s ease;
    }

    .messaging-user-scroll::-webkit-scrollbar-thumb:hover {
        background: #CBD5E1;
    }

    .messaging-user-scroll {
        scrollbar-width: thin;
        scrollbar-color: #E2E8F0 transparent;
    }

    #modalMessagesContainer {
        scroll-behavior: smooth;
    }

    #modalMessagesContainer::-webkit-scrollbar {
        width: 5px;
    }

    #modalMessagesContainer::-webkit-scrollbar-track {
        background: transparent;
        border-radius: 20px;
    }

    #modalMessagesContainer::-webkit-scrollbar-thumb {
        background: #E2E8F0;
        border-radius: 20px;
    }

    #modalMessagesContainer::-webkit-scrollbar-thumb:hover {
        background: #CBD5E1;
    }

    #modalConversationsList .conversation-item {
        transition: background-color 0.15s ease, transform 0.15s ease;
    }

    #modalUserSearch:focus {
        box-shadow: 0 0 0 4px rgba(15, 23, 42, 0.06);
    }

    #modalConversationSearch:focus {
        box-shadow: 0 0 0 4px rgba(15, 23, 42, 0.06);
    }
</style>
