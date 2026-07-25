<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Events\MessageDelivered;
use App\Events\UserTyping;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;



class MessageController extends Controller
{
    /**
     * Display a listing of the user's conversations.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $search = trim((string) $request->get('search', ''));

        $conversations = Conversation::whereHas(
            'participants',
            function ($query) use ($userId) {
                $query->where(
                    'user_id',
                    $userId
                );
            }
        )
        ->with([
            'lastMessage:message_id,conversation_id,sender_id,message_content,is_read,read_at,delivered_at,created_at',
            'participants.user.role',
        ])

        // =========================================
        // COUNT UNREAD MESSAGES FOR CURRENT USER
        // =========================================
        ->withCount([
            'messages as unread_count' => function ($query) use ($userId) {
                $query
                    ->where('is_read', false)
                    ->where('sender_id', '!=', $userId);
            }
        ])

        // =========================================
        // NEWEST CONVERSATION FIRST
        // =========================================
        ->orderByDesc('last_message_at');


        // =========================================
        // SEARCH
        // =========================================
        if ($search !== '') {
            $conversations->whereHas(
                'participants.user',
                function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where(
                            'user_full_name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhereHas(
                            'role',
                            function ($roleQuery) use ($search) {
                                $roleQuery->where(
                                    'role_name',
                                    'like',
                                    "%{$search}%"
                                );
                            }
                        );
                    });
                }
            );
        }


        // =========================================
        // PAGINATION
        // =========================================
        $conversations = $conversations->paginate(20);


        return response()->json([
            'data' => $conversations,
        ]);
    }

    /**
     * Display the specified conversation with messages.
     */
    public function show(Conversation $conversation): JsonResponse
    {
        $userId = Auth::id();

        $this->authorizeConversation(
            $conversation,
            $userId
        );

        // =========================================
        // ONLY LOAD DATA NEEDED FOR CHAT HEADER
        // Messages are loaded separately with pagination
        // =========================================

        $conversation->load([
            'participants.user.role'
        ]);

        return response()->json([
            'data' => $conversation,
        ]);
    }

    /**
     * Send a message to the conversation.
     */
    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $userId = Auth::id();

        $this->authorizeConversation($conversation, $userId);

        $validated = $request->validate([
            'message_content' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'string'],
        ]);

        $attachment = null;

        if (!empty($validated['attachment'])) {
            $decoded = json_decode($validated['attachment'], true);

            if (is_array($decoded)) {
                $attachment = $decoded;
            }
        }

        // =====================================================
        // PREPARE MESSAGE CONTENT
        //
        // For normal text:
        // Stores the actual message.
        //
        // For attachment only:
        // Stores a small internal marker so the conversation
        // list knows whether it was a photo or file.
        // =====================================================

        $messageContent = trim(
            (string) $validated['message_content']
        );

        if (
            $messageContent === '' &&
            $attachment
        ) {

            $attachmentType =
                $attachment['type'] ?? '';

            if (
                str_starts_with(
                    $attachmentType,
                    'image/'
                )
            ) {

                $messageContent = '[attachment:image]';

            } else {

                $messageContent = '[attachment:file]';
            }
        }


        // =====================================================
        // SAVE MESSAGE
        // =====================================================

        $message = new Message([
            'conversation_id' =>
                $conversation->conversation_id,

            'sender_id' =>
                $userId,

            'message_content' =>
                $messageContent,
        ]);

        $message->save();
        

        $conversation->update([
            'last_message_id' => $message->message_id,
            'last_message_at' => now(),
        ]);

        $message->load('sender');

        broadcast(
            new MessageSent($message)
        )->toOthers();

        $response = [
            'message' => 'Message sent successfully.',
            'data' => $message,
        ];

        if ($attachment) {
            $response['data']->setAttribute('attachment', $attachment);
        }

        return response()->json($response, 201);
    }

    /**
     * Mark a message as delivered.
     */
    public function markAsDelivered(
        Request $request,
        Conversation $conversation,
        Message $message
    ): JsonResponse
    {
        $userId = Auth::id();

        // =========================================
        // MAKE SURE USER BELONGS TO CONVERSATION
        // =========================================

        $this->authorizeConversation(
            $conversation,
            $userId
        );

        // =========================================
        // MAKE SURE MESSAGE BELONGS TO CONVERSATION
        // =========================================

        if (
            (int) $message->conversation_id !==
            (int) $conversation->conversation_id
        ) {
            abort(404);
        }

        // =========================================
        // SENDER CANNOT DELIVER THEIR OWN MESSAGE
        // =========================================

        if (
            (int) $message->sender_id ===
            (int) $userId
        ) {
            return response()->json([
                'message' => 'Sender cannot mark own message as delivered.',
            ], 422);
        }

        // =========================================
        // MARK AS DELIVERED ONLY ONCE
        // =========================================

        if (!$message->delivered_at) {

            $message->delivered_at = now();
            $message->save();

            // =========================================
            // TELL SENDER IN REAL TIME
            // =========================================

            broadcast(
                new MessageDelivered(
                    $conversation->conversation_id,
                    $message->message_id,
                    $userId,
                    $message->delivered_at->toISOString()
                )
            )->toOthers();
        }

        return response()->json([
            'message' => 'Message marked as delivered.',
            'message_id' => $message->message_id,
            'delivered_at' => $message->delivered_at,
        ]);
    }

    // =====================================================
    // SYNC PENDING DELIVERIES WHEN USER COMES ONLINE
    // =====================================================

    public function syncDeliveredMessages(): JsonResponse
    {
        $userId = Auth::id();

        // =====================================================
        // GET CONVERSATIONS THAT CURRENT USER BELONGS TO
        // =====================================================

        $conversationIds = ConversationParticipant::where(
            'user_id',
            $userId
        )
        ->pluck('conversation_id');


        // =====================================================
        // FIND MESSAGES SENT TO THIS USER
        // THAT HAVE NOT YET BEEN DELIVERED
        // =====================================================

        $messages = Message::whereIn(
                'conversation_id',
                $conversationIds
            )
            ->where(
                'sender_id',
                '!=',
                $userId
            )
            ->whereNull('delivered_at')
            ->get();


        // =====================================================
        // MARK EACH MESSAGE AS DELIVERED
        // =====================================================

        foreach ($messages as $message) {

            $message->delivered_at = now();

            $message->save();


            // =================================================
            // TELL ORIGINAL SENDER IN REAL TIME
            // =================================================

            broadcast(
                new MessageDelivered(
                    $message->conversation_id,
                    $message->message_id,
                    $userId,
                    $message->delivered_at->toISOString()
                )
            )->toOthers();
        }


        return response()->json([
            'success' => true,
            'delivered_count' => $messages->count(),
            'message_ids' => $messages
                ->pluck('message_id')
                ->values(),
        ]);
    }

    /**
     * Mark messages in the conversation as read.
     */
    public function markAsRead(
        Request $request,
        Conversation $conversation
    ): JsonResponse
    {
        $userId = Auth::id();

        $this->authorizeConversation(
            $conversation,
            $userId
        );


        // =====================================================
        // FIND UNREAD MESSAGES SENT BY THE OTHER USER
        // =====================================================

        $messageIds = Message::where(
                'conversation_id',
                $conversation->conversation_id
            )
            ->where('is_read', false)
            ->where('sender_id', '!=', $userId)
            ->pluck('message_id')
            ->toArray();


        // =====================================================
        // MARK THEM AS READ
        // =====================================================

        if (!empty($messageIds)) {

            Message::whereIn(
                'message_id',
                $messageIds
            )->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }


        // =====================================================
        // UPDATE PARTICIPANT LAST READ TIME
        // =====================================================

        ConversationParticipant::where(
                'conversation_id',
                $conversation->conversation_id
            )
            ->where(
                'user_id',
                $userId
            )
            ->update([
                'last_read_at' => now()
            ]);


        // =====================================================
        // REALTIME SEEN RECEIPT
        //
        // Tell the sender that these messages were opened.
        // =====================================================

        if (!empty($messageIds)) {

            broadcast(
                new MessagesRead(
                    $conversation->conversation_id,
                    $userId,
                    $messageIds
                )
            )->toOthers();
        }


        return response()->json([
            'message' => 'Messages marked as read.',
            'message_ids' => $messageIds,
        ]);
    }

    /**
     * Load more messages for a conversation (paginated).
     */
    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $userId = Auth::id();

        $this->authorizeConversation($conversation, $userId);

        $messages = $conversation->messages()
            ->with('sender')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $messages,
        ]);
    }

    /**
     * Ensure the authenticated user belongs to the conversation.
     */
    private function authorizeConversation(Conversation $conversation, int $userId): void
    {
        $isParticipant = ConversationParticipant::where('conversation_id', $conversation->conversation_id)
            ->where('user_id', $userId)
            ->exists();

        if (! $isParticipant) {
            abort(403, 'You are not authorized to access this conversation.');
        }
    }

    /**
     * Find an existing conversation between two users if it exists.
     */
    private function findConversationBetween(int $userIdA, int $userIdB): ?Conversation
    {
        $conversationIds = Conversation::whereHas('participants', function ($query) use ($userIdA) {
                $query->where('user_id', $userIdA);
            })
            ->whereHas('participants', function ($query) use ($userIdB) {
                $query->where('user_id', $userIdB);
            })
            ->pluck('conversation_id');

        foreach ($conversationIds as $conversationId) {
            $participantCount = ConversationParticipant::where('conversation_id', $conversationId)->count();

            if ($participantCount === 2) {
                return Conversation::find($conversationId);
            }
        }

        return null;
    }

    /**
     * Return users available for new conversations.
     */
    // =====================================================
    // GET USERS FOR MESSAGING
    // =====================================================

    public function users(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $search = trim(
            (string) $request->get('search', '')
        );

        // =====================================================
        // GET ALL USERS EXCEPT CURRENT USER
        // Also get last_active_at for online status
        // =====================================================

        $query = User::where(
            'user_id',
            '!=',
            $userId
        )
        ->select(
            'user_id',
            'user_full_name',
            'user_email_address',
            'user_role_id',
            'last_active_at'
        )
        ->with([
            'role:role_id,role_name'
        ]);

        // =====================================================
        // SEARCH USER
        // =====================================================

        if ($search !== '') {

            $query->where(function ($q) use ($search) {

                $q->where(
                    'user_full_name',
                    'like',
                    "%{$search}%"
                )

                ->orWhere(
                    'user_email_address',
                    'like',
                    "%{$search}%"
                )

                ->orWhereHas(
                    'role',
                    function ($roleQuery) use ($search) {

                        $roleQuery->where(
                            'role_name',
                            'like',
                            "%{$search}%"
                        );
                    }
                );
            });
        }

        // =====================================================
        // FORMAT USERS
        // =====================================================

        $users = $query
            ->get()
            ->map(function ($user) {

                return [

                    'user_id' =>
                        $user->user_id,

                    'name' =>
                        $user->user_full_name,

                    'email' =>
                        $user->user_email_address,

                    'role' =>
                        $user->role->role_name ?? 'User',

                    // =========================================
                    // NEEDED FOR ONLINE STATUS
                    // =========================================

                    'last_active_at' =>
                        $user->last_active_at,

                    'initials' =>
                        strtoupper(
                            collect(
                                explode(
                                    ' ',
                                    $user->user_full_name
                                )
                            )
                            ->take(2)
                            ->map(
                                fn ($name) =>
                                    $name[0] ?? ''
                            )
                            ->join('')
                        ),
                ];
            });

        return response()->json([
            'data' => $users,
        ]);
    }

    /**
     * Create a new conversation between the authenticated user and another user.
     */
    public function storeConversation(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users_table,user_id'],
        ]);

        $otherUserId = (int) $request->input('user_id');

        if ($otherUserId === $userId) {
            return response()->json([
                'message' => 'You cannot start a conversation with yourself.',
            ], 422);
        }

        $conversation = $this->findConversationBetween($userId, $otherUserId);

        if (! $conversation) {
            $conversation = Conversation::create([
                'last_message_at' => null,
            ]);

            ConversationParticipant::insert([
                [
                    'conversation_id' => $conversation->conversation_id,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'conversation_id' => $conversation->conversation_id,
                    'user_id' => $otherUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        $conversation->load([
            'lastMessage.sender',
            'participants.user',
        ]);

        return response()->json([
            'message' => 'Conversation created successfully.',
            'data' => $conversation,
        ], 201);
    }

    /**
     * Delete a conversation and all its messages.
     */
    public function destroy(Conversation $conversation): JsonResponse
    {
        $userId = Auth::id();

        $this->authorizeConversation($conversation, $userId);

        $conversation->messages()->delete();
        ConversationParticipant::where('conversation_id', $conversation->conversation_id)->delete();
        $conversation->delete();

        return response()->json([
            'message' => 'Conversation deleted successfully.',
        ]);
    }

    /**
     * Upload a file attachment for a conversation message.
     */
    public function uploadAttachment(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $request->validate([
            'conversation_id' => ['required', 'integer', 'exists:conversations,conversation_id'],
            'file' => ['required', 'file', 'max:25600'],
        ]);

        $conversation = Conversation::findOrFail($request->input('conversation_id'));

        $isParticipant = ConversationParticipant::where('conversation_id', $conversation->conversation_id)
            ->where('user_id', $userId)
            ->exists();

        if (! $isParticipant) {
            abort(403, 'You are not authorized to upload files to this conversation.');
        }

        $file = $request->file('file');
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());
        $fileName = time() . '_' . uniqid() . '.' . $extension;
        $path = $file->storeAs("messaging/{$conversation->conversation_id}", $fileName, 'public');

        if (! $path) {
            return response()->json([
                'message' => 'Failed to upload file.',
            ], 500);
        }

        $url = asset('storage/' . $path);

        return response()->json([
            'message' => 'File uploaded successfully.',
            'data' => [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'url' => $url,
                'size' => $file->getSize(),
                'type' => $mimeType,
                'extension' => $extension,
            ],
        ], 201);
    }

    // =====================================================
    // TOTAL UNREAD MESSAGE COUNT FOR TOPBAR
    // PLACE THIS INSIDE MessageController
    // BEFORE THE FINAL }
    // =====================================================

    public function unreadCount(): JsonResponse
    {
        $userId = Auth::id();

        $conversationIds = ConversationParticipant::where(
            'user_id',
            $userId
        )->pluck('conversation_id');

        $unreadCount = Message::whereIn(
                'conversation_id',
                $conversationIds
            )
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'unread_count' => $unreadCount,
        ]);
    }

    // =====================================================
    // GLOBAL TYPING INDICATOR
    //
    // PURPOSE:
    // Sends typing status to the OTHER participant through
    // their private user.{id} channel.
    //
    // This allows the conversation list to show typing even
    // when that conversation is not currently open.
    // =====================================================

    public function typing(
        Request $request,
        Conversation $conversation
    ): JsonResponse
    {
        $userId = Auth::id();


        // =====================================================
        // MAKE SURE CURRENT USER BELONGS TO CONVERSATION
        // =====================================================

        $this->authorizeConversation(
            $conversation,
            $userId
        );


        // =====================================================
        // VALIDATE TYPING STATUS
        // =====================================================

        $validated = $request->validate([
            'is_typing' => [
                'required',
                'boolean',
            ],
        ]);


        // =====================================================
        // GET OTHER PARTICIPANTS
        //
        // Do NOT accept receiver_id from JavaScript.
        //
        // The server determines who should receive the event.
        // =====================================================

        $receiverIds = ConversationParticipant::where(
                'conversation_id',
                $conversation->conversation_id
            )
            ->where(
                'user_id',
                '!=',
                $userId
            )
            ->pluck('user_id');


        // =====================================================
        // SEND TYPING EVENT TO EACH RECEIVER
        // =====================================================

        foreach ($receiverIds as $receiverId) {

            broadcast(
                new UserTyping(
                    (int) $conversation->conversation_id,
                    (int) $userId,
                    (int) $receiverId,
                    (bool) $validated['is_typing']
                )
            )->toOthers();

        }


        return response()->json([
            'success' => true,
        ]);
    }
}
