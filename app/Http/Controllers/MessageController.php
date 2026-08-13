<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Events\CallSignal;
use App\Events\MessageDelivered;
use App\Events\UserTyping;
use App\Events\MessageReactionUpdated;
use App\Events\MessageUpdated;
use App\Models\Call;
use App\Models\MessageReaction;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\MessageHiddenUser;
use App\Models\User;
use App\Models\MessageAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
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
            'lastMessage:message_id,conversation_id,sender_id,reply_to_message_id,message_content,message_type,call_id,is_read,read_at,delivered_at,created_at',
            'lastMessage.call:call_id,caller_id,receiver_id,call_type,status,duration,answered_at',
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

            'message_content' => [
                'nullable',
                'string',
                'max:5000'
            ],

            'attachments' => [
                'nullable',
                'string'
            ],

            // =============================================
            // OPTIONAL MESSAGE BEING REPLIED TO
            // =============================================

            'reply_to_message_id' => [
                'nullable',
                'integer',
                'exists:messages,message_id',
            ],
        ]);

        $replyToMessage = null;

        if (!empty($validated['reply_to_message_id'])) {

            $replyToMessage = Message::where(
                    'message_id',
                    $validated['reply_to_message_id']
                )
                ->where(
                    'conversation_id',
                    $conversation->conversation_id
                )
                ->firstOrFail();
        }

        $attachments = [];

        if (!empty($validated['attachments'])) {

            $decoded = json_decode(
                $validated['attachments'],
                true
            );

            if (is_array($decoded)) {

                $attachments = array_values(
                    array_filter(
                        $decoded,
                        fn ($attachment) =>
                            is_array($attachment)
                    )
                );
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
            !empty($attachments)
        ) {

            // =============================================
            // MORE THAN ONE ATTACHMENT
            // =============================================

            if (count($attachments) > 1) {

                $messageContent = '[attachment:multiple]';

            } else {

                // =============================================
                // ONE ATTACHMENT
                // =============================================

                $attachmentType =
                    $attachments[0]['type'] ?? '';

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
        }


        // =====================================================
        // SAVE MESSAGE
        // =====================================================

        $message = new Message([

            'conversation_id' =>
                $conversation->conversation_id,

            'sender_id' =>
                $userId,


            // =============================================
            // ORIGINAL MESSAGE BEING REPLIED TO
            // NULL FOR NORMAL MESSAGES
            // =============================================

            'reply_to_message_id' =>
                $replyToMessage?->message_id,


            'message_content' =>
                $messageContent,
        ]);

        $message->save();

        if (!empty($attachments)) {

            foreach ($attachments as $attachment) {

                MessageAttachment::create([
                    'message_id' => $message->message_id,

                    'attachment_name' =>
                        $attachment['name'] ?? 'Attachment',

                    'attachment_path' =>
                        $attachment['path'] ?? '',

                    'attachment_url' =>
                        $attachment['url'] ?? null,

                    'attachment_type' =>
                        $attachment['type'] ?? null,

                    'attachment_extension' =>
                        $attachment['extension'] ?? null,

                    'attachment_size' =>
                        isset($attachment['size'])
                            ? (int) $attachment['size']
                            : null,
                ]);
            }
        }
        

        $conversation->update([
            'last_message_id' => $message->message_id,
            'last_message_at' => now(),
        ]);

        $message->load([
            'sender',
            'replyTo.sender',
            'reactions.user',
            'attachments',
        ]);

        broadcast(
            new MessageSent($message)
        )->toOthers();

        $response = [
            'message' => 'Message sent successfully.',
            'data' => $message,
        ];

        

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
    // REACT TO MESSAGE
    //
    // BEHAVIOR:
    //
    // No existing reaction + click 👍
    //     => add 👍
    //
    // Existing 👍 + click 👍
    //     => remove 👍
    //
    // Existing 👍 + click ❤️
    //     => change 👍 to ❤️
    // =====================================================

    public function reactToMessage(
        Request $request,
        Conversation $conversation,
        Message $message
    ): JsonResponse
    {
        $userId = Auth::id();


        // =====================================================
        // MAKE SURE USER BELONGS TO CONVERSATION
        // =====================================================

        $this->authorizeConversation(
            $conversation,
            $userId
        );


        // =====================================================
        // MAKE SURE MESSAGE BELONGS TO THIS CONVERSATION
        // =====================================================

        if (
            (int) $message->conversation_id !==
            (int) $conversation->conversation_id
        ) {
            abort(404);
        }


        // =====================================================
        // ONLY ALLOW OUR THREE REACTIONS
        //
        // like  = 👍
        // heart = ❤️
        // check = ✓
        // =====================================================

        $validated = $request->validate([
            'reaction' => [
                'required',
                'string',
                'in:like,heart,check',
            ],
        ]);


        $reactionType =
            $validated['reaction'];


        // =====================================================
        // CHECK CURRENT USER'S EXISTING REACTION
        // =====================================================

        $existingReaction = MessageReaction::where(
                'message_id',
                $message->message_id
            )
            ->where(
                'user_id',
                $userId
            )
            ->first();


        // =====================================================
        // SAME REACTION CLICKED AGAIN
        //
        // Example:
        // Existing 👍 + click 👍
        //
        // Remove reaction.
        // =====================================================

        if (
            $existingReaction &&
            $existingReaction->reaction === $reactionType
        ) {

            $existingReaction->delete();

        }


        // =====================================================
        // DIFFERENT REACTION
        //
        // Example:
        // Existing 👍 + click ❤️
        //
        // Change it to ❤️.
        // =====================================================

        elseif ($existingReaction) {

            $existingReaction->update([
                'reaction' => $reactionType,
            ]);

        }


        // =====================================================
        // NO EXISTING REACTION
        //
        // Create one.
        // =====================================================

        else {

            MessageReaction::create([
                'message_id' =>
                    $message->message_id,

                'user_id' =>
                    $userId,

                'reaction' =>
                    $reactionType,
            ]);

        }


        // =====================================================
        // GET UPDATED REACTIONS
        // =====================================================

        $reactions = MessageReaction::where(
                'message_id',
                $message->message_id
            )
            ->with([
                'user:user_id,user_full_name,user_profile_picture',
            ])
            ->orderBy('created_at')
            ->get();

        // =====================================================
        // PREPARE REACTIONS FOR REALTIME BROADCAST
        // =====================================================

        $reactionData = $reactions
            ->map(function ($reaction) {

                return [
                    'message_reaction_id' =>
                        $reaction->message_reaction_id,

                    'message_id' =>
                        $reaction->message_id,

                    'user_id' =>
                        $reaction->user_id,

                    'reaction' =>
                        $reaction->reaction,

                    'user' => [
                        'user_id' =>
                            $reaction->user?->user_id,

                        'user_full_name' =>
                            $reaction->user?->user_full_name,

                        // =============================================
                        // PROFILE PICTURE FOR REACTION MODAL / TOOLTIP
                        // =============================================
                        'user_profile_picture' =>
                            $reaction->user?->user_profile_picture,
                    ],
                ];

            })
            ->values()
            ->toArray();


        // =====================================================
        // BROADCAST UPDATED REACTIONS
        //
        // toOthers() means the person who clicked the reaction
        // already gets the HTTP response.
        //
        // Everyone else receives this through Reverb.
        // =====================================================

        broadcast(
            new MessageReactionUpdated(
                (int) $conversation->conversation_id,
                (int) $message->message_id,
                $reactionData
            )
        )->toOthers();


        // =====================================================
        // RETURN UPDATED REACTION LIST
        // =====================================================

        return response()->json([
            'success' => true,

            'message_id' =>
                $message->message_id,

            'reactions' =>
                $reactionData,
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
    // =====================================================
    // LOAD MESSAGES FOR A CONVERSATION
    // =====================================================

    public function messages(
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
        // GET ALL MESSAGE IDS PINNED BY CURRENT USER
        // =====================================================

        $pinnedMessageIds = DB::table(
            'conversation_pinned_messages'
        )
            ->where(
                'conversation_id',
                $conversation->conversation_id
            )
            ->where(
                'user_id',
                $userId
            )
            ->pluck('message_id')
            ->map(fn ($id) => (int) $id)
            ->all();


        // =====================================================
        // LOAD CONVERSATION MESSAGES
        // =====================================================

        $messages = $conversation->messages()

            // =============================================
            // DO NOT SHOW MESSAGES REMOVED FOR THIS USER
            // =============================================

            ->whereDoesntHave(
                'hiddenUsers',
                function ($query) use ($userId) {
                    $query->where(
                        'user_id',
                        $userId
                    );
                }
            )

            // =============================================
            // LOAD MESSAGE INFORMATION
            // =============================================

            ->with([
                'sender',
                'replyTo.sender',
                'reactions.user',
                'attachments',
                'call',
            ])

            ->orderByDesc('created_at')
            ->paginate(20);


        // =====================================================
        // ADD is_pinned TO EVERY MESSAGE
        //
        // Example:
        // is_pinned: true
        // is_pinned: false
        // =====================================================

        $messages->getCollection()
            ->transform(
                function ($message) use ($pinnedMessageIds) {

                    $message->setAttribute(
                        'is_pinned',
                        in_array(
                            (int) $message->message_id,
                            $pinnedMessageIds,
                            true
                        )
                    );

                    return $message;
                }
            );

        $conversationEvents = collect();

        if ($conversation->conversation_type === 'group') {

            $conversationEvents =
                DB::table('conversation_events as ce')

                    // =================================================
                    // PERSON WHO PERFORMED THE ACTION
                    // =================================================

                    ->leftJoin(
                        'users_table as actor',
                        'actor.user_id',
                        '=',
                        'ce.actor_user_id'
                    )

                    // =================================================
                    // PERSON WHO WAS ADDED
                    // =================================================

                    ->leftJoin(
                        'users_table as target',
                        'target.user_id',
                        '=',
                        'ce.target_user_id'
                    )

                    ->where(
                        'ce.conversation_id',
                        $conversation->conversation_id
                    )

                    ->select([
                        'ce.conversation_event_id',
                        'ce.conversation_id',
                        'ce.actor_user_id',
                        'ce.target_user_id',
                        'ce.event_type',
                        'ce.created_at',
                        'ce.updated_at',

                        'actor.user_full_name as actor_name',
                        'target.user_full_name as target_name',
                    ])

                    ->orderBy(
                        'ce.created_at'
                    )

                    ->get()

                    ->map(function ($event) {

                        return [
                            // =========================================
                            // TELLS JAVASCRIPT THIS IS NOT A MESSAGE
                            // =========================================

                            'item_type' =>
                                'conversation_event',

                            'conversation_event_id' =>
                                (int) $event->conversation_event_id,

                            'conversation_id' =>
                                (int) $event->conversation_id,

                            'actor_user_id' =>
                                $event->actor_user_id !== null
                                    ? (int) $event->actor_user_id
                                    : null,

                            'target_user_id' =>
                                $event->target_user_id !== null
                                    ? (int) $event->target_user_id
                                    : null,

                            'event_type' =>
                                $event->event_type,

                            'actor_name' =>
                                $event->actor_name,

                            'target_name' =>
                                $event->target_name,

                            'created_at' =>
                                $event->created_at,

                            'updated_at' =>
                                $event->updated_at,
                        ];
                    });
        }

        return response()->json([
            'data' => $messages,
            'conversation_events' =>
                $conversationEvents,
        ]);
    }
    // =====================================================
    // EDIT OWN MESSAGE
    // =====================================================

    public function editMessage(
        Request $request,
        Conversation $conversation,
        Message $message
    ): JsonResponse
    {
        $userId = Auth::id();

        $this->authorizeConversation(
            $conversation,
            $userId
        );

        // =============================================
        // MAKE SURE MESSAGE BELONGS TO CONVERSATION
        // =============================================

        if (
            (int) $message->conversation_id !==
            (int) $conversation->conversation_id
        ) {
            abort(404);
        }

        // =============================================
        // ONLY THE SENDER CAN EDIT
        // =============================================

        if ((int) $message->sender_id !== (int) $userId) {
            abort(403, 'You cannot edit this message.');
        }

        // =============================================
        // UNSENT MESSAGES CANNOT BE EDITED
        // =============================================

        if ($message->is_unsent) {
            return response()->json([
                'message' => 'An unsent message cannot be edited.',
            ], 422);
        }

        $validated = $request->validate([
            'message_content' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);

        $message->update([
            'message_content' =>
                trim($validated['message_content']),

            'is_edited' => true,
            'edited_at' => now(),
        ]);

        $message->refresh();

        // =====================================================
        // LOAD REPLY DATA IMMEDIATELY AFTER EDIT
        // Keeps the quoted replied message visible without refresh.
        // =====================================================

        $message->load([
            'sender',
            'replyTo.sender',
            'reactions.user',
            'attachments',
        ]);

        broadcast(
            new MessageUpdated(
                $message,
                'edited'
            )
        )->toOthers();

        return response()->json([
            'message' => 'Message edited successfully.',
            'data' => $message,
        ]);
    }


    // =====================================================
    // UNSEND OWN MESSAGE FOR EVERYONE
    // =====================================================

    public function unsendMessage(
        Conversation $conversation,
        Message $message
    ): JsonResponse
    {
        $userId = Auth::id();

        $this->authorizeConversation(
            $conversation,
            $userId
        );

        // =============================================
        // MAKE SURE MESSAGE BELONGS TO CONVERSATION
        // =============================================

        if (
            (int) $message->conversation_id !==
            (int) $conversation->conversation_id
        ) {
            abort(404);
        }

        // =============================================
        // ONLY ORIGINAL SENDER CAN UNSEND
        // =============================================

        if ((int) $message->sender_id !== (int) $userId) {
            abort(403, 'You cannot unsend this message.');
        }

        if (!$message->is_unsent) {

            $message->update([
                'is_unsent' => true,
                'unsent_at' => now(),
            ]);

            // =============================================
            // REMOVE REACTIONS FROM UNSENT MESSAGE
            // =============================================

            MessageReaction::where(
                'message_id',
                $message->message_id
            )->delete();

            // =============================================
            // REFRESH UPDATED MESSAGE
            // =============================================

            $message->refresh();


            // =============================================
            // TELL OTHER USER MESSAGE WAS UNSENT
            // =============================================

            broadcast(
                new MessageUpdated(
                    $message,
                    'unsent'
                )
            )->toOthers();
        }

        return response()->json([
            'message' => 'Message unsent successfully.',
            'message_id' => $message->message_id,
            'is_unsent' => true,
            'unsent_at' => $message->unsent_at,
        ]);
    }


    // =====================================================
    // REMOVE MESSAGE FOR CURRENT USER ONLY
    // =====================================================

    public function removeMessageForUser(
        Conversation $conversation,
        Message $message
    ): JsonResponse
    {
        $userId = Auth::id();

        $this->authorizeConversation(
            $conversation,
            $userId
        );

        // =============================================
        // MAKE SURE MESSAGE BELONGS TO CONVERSATION
        // =============================================

        if (
            (int) $message->conversation_id !==
            (int) $conversation->conversation_id
        ) {
            abort(404);
        }

        MessageHiddenUser::firstOrCreate([
            'message_id' => $message->message_id,
            'user_id' => $userId,
        ]);

        return response()->json([
            'message' => 'Message removed for you.',
            'message_id' => $message->message_id,
        ]);
    }


    // =====================================================
    // PIN OR UNPIN MESSAGE FOR CURRENT USER
    //
    // Uses conversation_pinned_messages so one user can
    // keep MULTIPLE pinned messages in the same conversation.
    // =====================================================

    public function pinMessage(
        Conversation $conversation,
        Message $message
    ): JsonResponse
    {
        $userId = Auth::id();

        $this->authorizeConversation(
            $conversation,
            $userId
        );

        // =============================================
        // MAKE SURE MESSAGE BELONGS TO CONVERSATION
        // =============================================

        if (
            (int) $message->conversation_id !==
            (int) $conversation->conversation_id
        ) {
            abort(404);
        }

        // =============================================
        // UNSENT MESSAGES CANNOT BE PINNED
        // =============================================

        if ($message->is_unsent) {
            return response()->json([
                'message' => 'An unsent message cannot be pinned.',
            ], 422);
        }

        // =============================================
        // CHECK IF CURRENT USER ALREADY PINNED IT
        // =============================================

        $existingPin = DB::table('conversation_pinned_messages')
            ->where('conversation_id', $conversation->conversation_id)
            ->where('message_id', $message->message_id)
            ->where('user_id', $userId)
            ->first();

        // =============================================
        // CLICK PINNED MESSAGE AGAIN = UNPIN
        // =============================================

        if ($existingPin) {
            DB::table('conversation_pinned_messages')
                ->where(
                    'conversation_pinned_message_id',
                    $existingPin->conversation_pinned_message_id
                )
                ->delete();

            return response()->json([
                'message' => 'Message unpinned.',
                'message_id' => $message->message_id,
                'is_pinned' => false,
            ]);
        }

        // =============================================
        // OTHERWISE ADD A NEW PIN
        // =============================================

        DB::table('conversation_pinned_messages')->insert([
            'conversation_id' => $conversation->conversation_id,
            'message_id' => $message->message_id,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Message pinned.',
            'message_id' => $message->message_id,
            'is_pinned' => true,
        ]);
    }


    // =====================================================
    // FORWARD MESSAGE TO ANOTHER CONVERSATION
    // =====================================================

    public function forwardMessage(
        Request $request,
        Conversation $conversation,
        Message $message
    ): JsonResponse
    {
        $userId = Auth::id();

        $this->authorizeConversation(
            $conversation,
            $userId
        );

        // =============================================
        // MAKE SURE ORIGINAL MESSAGE BELONGS HERE
        // =============================================

        if (
            (int) $message->conversation_id !==
            (int) $conversation->conversation_id
        ) {
            abort(404);
        }

        if ($message->is_unsent) {
            return response()->json([
                'message' => 'An unsent message cannot be forwarded.',
            ], 422);
        }

        $validated = $request->validate([
            'target_conversation_id' => [
                'required',
                'integer',
                'exists:conversations,conversation_id',
            ],
        ]);

        $targetConversation = Conversation::findOrFail(
            $validated['target_conversation_id']
        );

        // =============================================
        // CURRENT USER MUST BELONG TO TARGET CHAT
        // =============================================

        $this->authorizeConversation(
            $targetConversation,
            $userId
        );

        // =============================================
        // LOAD ORIGINAL ATTACHMENTS
        //
        // We reuse the existing stored physical files.
        // New message_attachments rows will point to the
        // same stored files.
        // =============================================

        $message->loadMissing('attachments');

        // =============================================
        // CREATE FORWARDED MESSAGE + COPY ATTACHMENTS
        // AS ONE DATABASE TRANSACTION
        // =============================================

        $forwardedMessage = DB::transaction(
            function () use (
                $message,
                $targetConversation,
                $userId
            ) {
                // =====================================
                // CREATE THE NEW FORWARDED MESSAGE
                // =====================================

                $forwardedMessage = Message::create([

                    'conversation_id' =>
                        $targetConversation->conversation_id,

                    'sender_id' =>
                        $userId,

                    'reply_to_message_id' =>
                        null,

                    // =================================
                    // KEEP REFERENCE TO ORIGINAL
                    // =================================

                    'forwarded_from_message_id' =>
                        $message->message_id,

                    // =================================
                    // KEEP ORIGINAL TEXT / ATTACHMENT
                    // MARKER EXACTLY AS IT WAS
                    // =================================

                    'message_content' =>
                        $message->message_content,

                    'is_read' =>
                        false,
                ]);

                // =====================================
                // COPY ATTACHMENT DATABASE RECORDS
                //
                // IMPORTANT:
                // Do NOT upload/copy the physical file.
                // We only create new attachment records
                // for the forwarded message.
                // =====================================

                foreach ($message->attachments as $attachment) {

                    MessageAttachment::create([

                        'message_id' =>
                            $forwardedMessage->message_id,

                        'attachment_name' =>
                            $attachment->attachment_name,

                        'attachment_path' =>
                            $attachment->attachment_path,

                        'attachment_url' =>
                            $attachment->attachment_url,

                        'attachment_type' =>
                            $attachment->attachment_type,

                        'attachment_extension' =>
                            $attachment->attachment_extension,

                        'attachment_size' =>
                            $attachment->attachment_size,
                    ]);
                }

                // =====================================
                // UPDATE TARGET CONVERSATION PREVIEW
                // =====================================

                $targetConversation->update([

                    'last_message_id' =>
                        $forwardedMessage->message_id,

                    'last_message_at' =>
                        now(),
                ]);

                return $forwardedMessage;
            }
        );

        // =============================================
        // LOAD EVERYTHING NEEDED BY THE FRONTEND
        // =============================================

        $forwardedMessage->load([
            'sender',
            'replyTo.sender',
            'reactions.user',
            'attachments',
        ]);

        // =============================================
        // REALTIME MESSAGE TO TARGET CONVERSATION
        // =============================================

        broadcast(
            new MessageSent($forwardedMessage)
        )->toOthers();

        return response()->json([
            'message' => 'Message forwarded successfully.',
            'data' => $forwardedMessage,
        ], 201);
    }

    /**
     * Ensure the authenticated user belongs to the conversation.
     */
    // =====================================================
    // RENAME GROUP CONVERSATION
    // Any current member can rename the group.
    // =====================================================
    public function renameGroup(
        Request $request,
        Conversation $conversation
    ): JsonResponse
    {
        $userId = Auth::id();

        $this->authorizeConversation($conversation, $userId);

        if ($conversation->conversation_type !== 'group') {
            return response()->json([
                'message' => 'Only group conversations can be renamed.',
            ], 422);
        }

        $validated = $request->validate([
            'conversation_name' => ['required', 'string', 'max:255'],
        ]);

        $name = trim($validated['conversation_name']);

        if ($name === '') {
            return response()->json([
                'message' => 'Group name is required.',
            ], 422);
        }

        $conversation->update([
            'conversation_name' => $name,
        ]);

        $conversation->load([
            'lastMessage.sender',
            'lastMessage.call',
            'participants.user.role',
        ]);

        Broadcast::private("conversation.{$conversation->conversation_id}")
            ->as('conversation.renamed')
            ->with([
                'conversation_id' => (int) $conversation->conversation_id,
                'conversation_name' => $name,
                'actor_user_id' => (int) $userId,
            ])
            ->send();

        return response()->json([
            'message' => 'Group name updated successfully.',
            'data' => $conversation,
        ]);
    }

    // =====================================================
    // UPDATE GROUP PICTURE
    // Any current group member can change the group picture.
    // =====================================================

    public function updateGroupImage(
        Request $request,
        Conversation $conversation
    ): JsonResponse
    {
        $userId = Auth::id();


        // =================================================
        // USER MUST CURRENTLY BELONG TO THIS CONVERSATION
        // =================================================

        $this->authorizeConversation(
            $conversation,
            $userId
        );


        // =================================================
        // GROUP CHAT ONLY
        // =================================================

        if ($conversation->conversation_type !== 'group') {

            return response()->json([
                'message' =>
                    'Only group conversations can have a group picture.',
            ], 422);
        }


        // =================================================
        // VALIDATE IMAGE
        //
        // max:5120 = maximum 5 MB
        // =================================================

        $request->validate([
            'conversation_image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);


        $file =
            $request->file('conversation_image');


        // =================================================
        // DELETE OLD CUSTOM GROUP PICTURE
        // =================================================

        if (
            $conversation->conversation_image &&
            Storage::disk('public')->exists(
                $conversation->conversation_image
            )
        ) {

            Storage::disk('public')->delete(
                $conversation->conversation_image
            );
        }


        // =================================================
        // SAVE NEW GROUP PICTURE
        // =================================================

        $path = $file->store(
            "messaging/groups/{$conversation->conversation_id}",
            'public'
        );


        // =================================================
        // UPDATE DATABASE
        // =================================================

        // =================================================
        // SAVE THE IMAGE PATH DIRECTLY
        //
        // IMPORTANT:
        // Do not rely on mass assignment here.
        // If conversation_image is missing from the
        // Conversation model's $fillable array, update([...])
        // can leave the database value unchanged.
        // =================================================

        $conversation->conversation_image = $path;
        $conversation->save();
        $conversation->refresh();


        // =================================================
        // REALTIME GROUP PICTURE UPDATE
        //
        // Example:
        // Kenn changes the picture.
        // Leo already has the group open.
        // Leo receives this event immediately.
        // =================================================

        Broadcast::private(
            "conversation.{$conversation->conversation_id}"
        )
            ->as('conversation.image.updated')
            ->with([
                'conversation_id' =>
                    (int) $conversation->conversation_id,

                'conversation_image' =>
                    $path,

                'conversation_image_url' =>
                    asset('storage/' . $path),

                'actor_user_id' =>
                    (int) $userId,
            ])
            ->send();


        // =================================================
        // RETURN UPDATED PICTURE TO PERSON WHO CHANGED IT
        // =================================================

        return response()->json([
            'success' => true,

            'message' =>
                'Group picture updated successfully.',

            'conversation_id' =>
                (int) $conversation->conversation_id,

            'conversation_image' =>
                $path,

            'conversation_image_url' =>
                asset('storage/' . $path),
        ]);
    }


    // =====================================================
    // BROADCAST A SAVED GROUP ACTIVITY EVENT
    // =====================================================
    private function broadcastConversationActivity(
        Conversation $conversation,
        int $eventId,
        string $eventType,
        ?int $actorUserId,
        ?int $targetUserId = null
    ): void
    {
        $actorName = $actorUserId
            ? User::where('user_id', $actorUserId)->value('user_full_name')
            : null;

        $targetName = $targetUserId
            ? User::where('user_id', $targetUserId)->value('user_full_name')
            : null;

        Broadcast::private("conversation.{$conversation->conversation_id}")
            ->as('conversation.activity')
            ->with([
                'conversation_id' => (int) $conversation->conversation_id,
                'conversation_event_id' => $eventId,
                'event_type' => $eventType,
                'actor_user_id' => $actorUserId,
                'actor_name' => $actorName,
                'target_user_id' => $targetUserId,
                'target_name' => $targetName,
                'created_at' => now()->toISOString(),
            ])
            ->send();
    }



    // =====================================================
    // PRIVATE AUDIO / VIDEO CALL SIGNALING
    // WebRTC carries media. Laravel relays signaling only.
    // =====================================================
    public function callSignal(Request $request): JsonResponse
    {
        $userId = (int) Auth::id();

        $validated = $request->validate([
            'target_user_id' => ['required', 'integer', 'exists:users_table,user_id'],
            'conversation_id' => ['nullable', 'integer', 'exists:conversations,conversation_id'],
            'call_id' => ['required', 'string', 'max:100'],
            'signal_type' => ['required', 'in:offer,answer,ice_candidate,decline,end,busy,camera_state',],
            'call_type' => ['nullable', 'in:audio,video'],
            'payload' => ['nullable', 'array'],
        ]);

        $targetUserId = (int) $validated['target_user_id'];

        if ($targetUserId === $userId) {
            return response()->json(['message' => 'You cannot call yourself.'], 422);
        }

        if (!empty($validated['conversation_id'])) {
            $conversation = Conversation::findOrFail((int) $validated['conversation_id']);
            $this->authorizeConversation($conversation, $userId);
        }

        $caller = User::query()
            ->select([
                'user_id',
                'user_full_name',
                'user_profile_picture',
            ])
            ->findOrFail($userId);

        $callType = $validated['call_type'] ?? 'audio';

        $call = Call::where(
            'call_uuid',
            $validated['call_id']
        )->first();

        switch ($validated['signal_type']) {

            // ==========================================
            // START CALL
            // ==========================================
            case 'offer':

                if (!$call) {

                    $call = Call::create([

                        'call_uuid' => $validated['call_id'],

                        'conversation_id' =>
                            $validated['conversation_id'] ?? null,

                        'caller_id' =>
                            $userId,

                        'receiver_id' =>
                            $targetUserId,

                        'call_type' =>
                            $callType,

                        'status' =>
                            'calling',

                        'started_at' =>
                            now(),
                    ]);
                }

                break;

            // ==========================================
            // ACCEPTED
            // ==========================================
            case 'answer':

                if ($call) {

                    $call->update([

                        'status' => 'accepted',

                        'answered_at' => now(),
                    ]);
                }

                break;

            // ==========================================
            // DECLINED
            // ==========================================
            case 'decline':

                if ($call) {

                    $call->update([

                        'status' => 'declined',

                        'ended_at' => now(),
                    ]);

                    $call->refresh();

                    $this->createCallHistoryMessage($call);
                }

                break;

            // ==========================================
            // BUSY
            // ==========================================
            case 'busy':

                if ($call) {

                    $call->update([

                        'status' => 'busy',

                        'ended_at' => now(),
                    ]);

                    $call->refresh();

                    $this->createCallHistoryMessage($call);
                }

                break;


            case 'camera_state':

                break;

            // ==========================================
            // ENDED
            // ==========================================
            case 'end':

                if ($call) {

                    $wasAnswered = (bool) $call->answered_at;

                    $duration = 0;

                    if ($wasAnswered) {

                        $duration = now()->diffInSeconds(
                            $call->answered_at
                        );
                    }

                    $call->update([

                        'status' => $wasAnswered ? 'ended' : 'missed',

                        'ended_at' => now(),

                        'duration' => $duration,
                    ]);

                    $call->refresh();

                    $this->createCallHistoryMessage($call);
                }

                break;

            
        }

        broadcast(new CallSignal(

            targetUserId: $targetUserId,

            fromUserId: $userId,

            fromUserName: $caller->user_full_name ?: 'User',

            fromUserPicture: $caller->user_profile_picture,

            conversationId: isset($validated['conversation_id'])
                ? (int) $validated['conversation_id']
                : null,

            callId: $validated['call_id'],

            signalType: $validated['signal_type'],

            callType: $callType,

            payload: $validated['payload'] ?? [],
        ));

        return response()->json([
            'success' => true,
        ]);
    }
    

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
    private function findConversationBetween(
        int $userIdA,
        int $userIdB
    ): ?Conversation
    {
        $conversationIds = Conversation::where(
                'conversation_type',
                'direct'
            )
            ->whereHas(
                'participants',
                function ($query) use ($userIdA) {
                    $query->where(
                        'user_id',
                        $userIdA
                    );
                }
            )
            ->whereHas(
                'participants',
                function ($query) use ($userIdB) {
                    $query->where(
                        'user_id',
                        $userIdB
                    );
                }
            )
            ->pluck('conversation_id');

        foreach ($conversationIds as $conversationId) {

            $participantCount =
                ConversationParticipant::where(
                    'conversation_id',
                    $conversationId
                )->count();

            if ($participantCount === 2) {
                return Conversation::find(
                    $conversationId
                );
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
            'user_profile_picture',
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
    public function storeConversation(
        Request $request
    ): JsonResponse
    {
        $userId = Auth::id();

        $validated = $request->validate([
            'user_id' => [
                'required',
                'integer',
                'exists:users_table,user_id'
            ],
        ]);

        $otherUserId =
            (int) $validated['user_id'];

        // =================================================
        // CANNOT MESSAGE YOURSELF
        // =================================================

        if ($otherUserId === $userId) {

            return response()->json([
                'message' =>
                    'You cannot start a conversation with yourself.',
            ], 422);
        }

        // =================================================
        // CHECK FOR EXISTING DIRECT CONVERSATION
        // =================================================

        $conversation =
            $this->findConversationBetween(
                $userId,
                $otherUserId
            );

        // =================================================
        // CREATE DIRECT CONVERSATION IF NONE EXISTS
        // =================================================

        if (! $conversation) {

            $conversation = DB::transaction(
                function () use (
                    $userId,
                    $otherUserId
                ) {

                    $conversation =
                        Conversation::create([
                            'conversation_type' =>
                                'direct',

                            'conversation_name' =>
                                null,

                            'last_message_at' =>
                                null,
                        ]);

                    ConversationParticipant::insert([
                        [
                            'conversation_id' =>
                                $conversation->conversation_id,

                            'user_id' =>
                                $userId,

                            'is_muted' =>
                                false,

                            'created_at' =>
                                now(),

                            'updated_at' =>
                                now(),
                        ],
                        [
                            'conversation_id' =>
                                $conversation->conversation_id,

                            'user_id' =>
                                $otherUserId,

                            'is_muted' =>
                                false,

                            'created_at' =>
                                now(),

                            'updated_at' =>
                                now(),
                        ],
                    ]);

                    return $conversation;
                }
            );
        }

        $conversation->load([
            'lastMessage.sender',
            'lastMessage.call',
            'participants.user.role',
        ]);

        return response()->json([
            'message' =>
                'Conversation created successfully.',

            'data' =>
                $conversation,
        ], 201);
    }

    public function storeGroupConversation(
        Request $request
    ): JsonResponse
    {
        $userId = Auth::id();

        $validated = $request->validate([

            // =============================================
            // GROUP NAME
            // =============================================

            'conversation_name' => [
                'required',
                'string',
                'max:255',
            ],

            // =============================================
            // OTHER GROUP MEMBERS
            //
            // Current logged in user is added automatically.
            // =============================================

            'user_ids' => [
                'required',
                'array',
                'min:2',
            ],

            'user_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:users_table,user_id',
            ],
        ]);

        // =================================================
        // CLEAN GROUP NAME
        // =================================================

        $groupName = trim(
            $validated['conversation_name']
        );

        if ($groupName === '') {

            return response()->json([
                'message' =>
                    'The group name is required.',
            ], 422);
        }

        // =================================================
        // CLEAN MEMBER IDS
        //
        // Remove current user if JavaScript accidentally
        // sends their ID because they are added automatically.
        // =================================================

        $memberIds = collect(
            $validated['user_ids']
        )
            ->map(
                fn ($id) => (int) $id
            )
            ->reject(
                fn ($id) =>
                    $id === (int) $userId
            )
            ->unique()
            ->values();

        // =================================================
        // REQUIRE AT LEAST TWO OTHER USERS
        //
        // Group = You + at least 2 other users.
        // =================================================

        if ($memberIds->count() < 2) {

            return response()->json([
                'message' =>
                    'Select at least two other users for the group.',
            ], 422);
        }

        // =================================================
        // CREATE GROUP
        // =================================================

        $conversation = DB::transaction(
            function () use (
                $userId,
                $groupName,
                $memberIds
            ) {

                $conversation =
                    Conversation::create([
                        'conversation_type' =>
                            'group',

                        'conversation_name' =>
                            $groupName,

                        'last_message_at' =>
                            null,
                    ]);

                // =========================================
                // ADD CURRENT USER FIRST
                // =========================================

                $participants = [
                    [
                        'conversation_id' =>
                            $conversation->conversation_id,

                        'user_id' =>
                            $userId,

                        'is_muted' =>
                            false,

                        'created_at' =>
                            now(),

                        'updated_at' =>
                            now(),
                    ]
                ];

                // =========================================
                // ADD SELECTED MEMBERS
                // =========================================

                foreach ($memberIds as $memberId) {

                    $participants[] = [
                        'conversation_id' =>
                            $conversation->conversation_id,

                        'user_id' =>
                            $memberId,

                        'is_muted' =>
                            false,

                        'created_at' =>
                            now(),

                        'updated_at' =>
                            now(),
                    ];
                }

                ConversationParticipant::insert(
                    $participants
                );

                DB::table('conversation_events')->insert([
                    'conversation_id' =>
                        $conversation->conversation_id,

                    'actor_user_id' =>
                        $userId,

                    'target_user_id' =>
                        null,

                    'event_type' =>
                        'group_created',

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);

                return $conversation;
            }
        );

        // =================================================
        // RETURN COMPLETE GROUP
        // =================================================

        $conversation->load([
            'lastMessage.sender',
            'lastMessage.call',
            'participants.user.role',
        ]);

        return response()->json([
            'message' =>
                'Group conversation created successfully.',

            'data' =>
                $conversation,
        ], 201);
    }

    public function muteConversation(
        Conversation $conversation
    ): JsonResponse
    {
        $userId = Auth::id();

        $this->authorizeConversation(
            $conversation,
            $userId
        );

        ConversationParticipant::where(
                'conversation_id',
                $conversation->conversation_id
            )
            ->where(
                'user_id',
                $userId
            )
            ->update([
                'is_muted' => true,
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' =>
                'Conversation muted successfully.',

            'is_muted' =>
                true,
        ]);
    }


    // =====================================================
    // UNMUTE CONVERSATION FOR CURRENT USER
    // =====================================================

    public function unmuteConversation(
        Conversation $conversation
    ): JsonResponse
    {
        $userId = Auth::id();

        $this->authorizeConversation(
            $conversation,
            $userId
        );

        ConversationParticipant::where(
                'conversation_id',
                $conversation->conversation_id
            )
            ->where(
                'user_id',
                $userId
            )
            ->update([
                'is_muted' => false,
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' =>
                'Conversation unmuted successfully.',

            'is_muted' =>
                false,
        ]);
    }

    // =====================================================
    // ADD PEOPLE TO GROUP
    // Adds selected users without removing existing members.
    // =====================================================

    public function addGroupMembers(
        Request $request,
        Conversation $conversation
    ): JsonResponse
    {
        $userId = Auth::id();

        $this->authorizeConversation(
            $conversation,
            $userId
        );

        if ($conversation->conversation_type !== 'group') {
            return response()->json([
                'message' =>
                    'People can only be added to a group conversation.',
            ], 422);
        }

        $validated = $request->validate([
            'user_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'user_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:users_table,user_id',
            ],
        ]);

        $memberIds = collect(
            $validated['user_ids']
        )
            ->map(
                fn ($id) => (int) $id
            )
            ->unique()
            ->values();

        $existingMemberIds =
            ConversationParticipant::where(
                'conversation_id',
                $conversation->conversation_id
            )
            ->pluck('user_id')
            ->map(
                fn ($id) => (int) $id
            );

        $newMemberIds =
            $memberIds->diff(
                $existingMemberIds
            );

        foreach ($newMemberIds as $memberId) {

            // =================================================
            // ADD PERSON TO GROUP
            // =================================================

            ConversationParticipant::create([
                'conversation_id' =>
                    $conversation->conversation_id,

                'user_id' =>
                    $memberId,

                'is_muted' =>
                    false,
            ]);


            // =================================================
            // RECORD WHO ADDED THIS PERSON
            //
            // Example:
            // You added Ms. Receiving to the group.
            // =================================================

            $eventId = DB::table('conversation_events')->insertGetId([
                'conversation_id' => $conversation->conversation_id,
                'actor_user_id' => $userId,
                'target_user_id' => $memberId,
                'event_type' => 'member_added',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // =================================================
            // REALTIME GROUP ACTIVITY
            // Everyone already inside the open group receives this
            // immediately through the existing private conversation channel.
            // =================================================
            $this->broadcastConversationActivity(
                $conversation,
                $eventId,
                'member_added',
                $userId,
                $memberId
            );
        }

        $conversation->load([
            'lastMessage.sender',
            'lastMessage.call',
            'participants.user.role',
        ]);

        return response()->json([
            'message' =>
                $newMemberIds->isEmpty()
                    ? 'Selected users are already in the group.'
                    : 'People added to the group successfully.',

            'added_count' =>
                $newMemberIds->count(),

            'data' =>
                $conversation,
        ]);
    }


    // =====================================================
    // LEAVE GROUP
    // Removes ONLY the logged in user from the group.
    // The conversation and other members remain untouched.
    // =====================================================

    public function leaveGroup(
        Conversation $conversation
    ): JsonResponse
    {
        $userId = Auth::id();

        $this->authorizeConversation(
            $conversation,
            $userId
        );

        if ($conversation->conversation_type !== 'group') {
            return response()->json([
                'message' =>
                    'You can only leave a group conversation.',
            ], 422);
        }

        $eventId = DB::table('conversation_events')->insertGetId([
            'conversation_id' => $conversation->conversation_id,
            'actor_user_id' => $userId,
            'target_user_id' => null,
            'event_type' => 'member_left',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Save the actor name before removing the participant.
        $leavingUserName = User::where('user_id', $userId)
            ->value('user_full_name');


        // =====================================================
        // REMOVE CURRENT USER FROM GROUP
        // =====================================================

        ConversationParticipant::where(
                'conversation_id',
                $conversation->conversation_id
            )
            ->where(
                'user_id',
                $userId
            )
            ->delete();

        // =====================================================
        // REALTIME LEAVE ACTIVITY
        // The remaining members see "Name left the group."
        // without refreshing the page.
        // =====================================================
        Broadcast::private("conversation.{$conversation->conversation_id}")
            ->as('conversation.activity')
            ->with([
                'conversation_id' => (int) $conversation->conversation_id,
                'conversation_event_id' => (int) $eventId,
                'event_type' => 'member_left',
                'actor_user_id' => (int) $userId,
                'actor_name' => $leavingUserName,
                'target_user_id' => null,
                'target_name' => null,
                'created_at' => now()->toISOString(),
            ])
            ->send();

        return response()->json([
            'message' =>
                'You left the group successfully.',
        ]);
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

    // =====================================================
    // GET ALL PINNED MESSAGES FOR CURRENT USER
    // =====================================================

    public function pinnedMessages(
        Conversation $conversation
    ): JsonResponse
    {
        $userId = Auth::id();

        $this->authorizeConversation(
            $conversation,
            $userId
        );

        // =============================================
        // GET ALL PINS, NEWEST PIN FIRST
        // =============================================

        $pinnedRows = DB::table('conversation_pinned_messages')
            ->where('conversation_id', $conversation->conversation_id)
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        if ($pinnedRows->isEmpty()) {
            return response()->json([
                'data' => [],
            ]);
        }

        // =============================================
        // LOAD ACTUAL MESSAGES AND THEIR UI DATA
        // =============================================

        $messageIds = $pinnedRows
            ->pluck('message_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $messages = Message::with([
                'sender',
                'replyTo.sender',
                'reactions.user',
                'attachments',
            ])
            ->where('conversation_id', $conversation->conversation_id)
            ->whereIn('message_id', $messageIds)
            ->get()
            ->keyBy('message_id');

        // =============================================
        // KEEP THE SAME ORDER AS THE PIN TABLE
        // =============================================

        $orderedMessages = $pinnedRows
            ->map(function ($pin) use ($messages) {
                $message = $messages->get(
                    (int) $pin->message_id
                );

                if (!$message) {
                    return null;
                }

                $message->setAttribute(
                    'pinned_at',
                    $pin->created_at
                );

                return $message;
            })
            ->filter()
            ->values();

        return response()->json([
            'data' => $orderedMessages,
        ]);
    }

    // =====================================================
    // CREATE CALL HISTORY MESSAGE
    // =====================================================

    private function createCallHistoryMessage(Call $call): Message
    {
        if (
            Message::where('call_id', $call->call_id)->exists()
        ) {
            return Message::where(
                'call_id',
                $call->call_id
            )->first();
        }

        $message = Message::create([

            'conversation_id' => $call->conversation_id,

            // The caller "owns" the timeline entry
            'sender_id' => $call->caller_id,

            'message_type' => 'call',

            'call_id' => $call->call_id,

            // Leave blank for call messages
            'message_content' => '',

        ]);

        Conversation::where(
            'conversation_id',
            $call->conversation_id
        )->update([

            'last_message_id' => $message->message_id,

            'last_message_at' => now(),

        ]);

        $message->load([
            'sender',
            'call',
        ]);

        broadcast(
            new MessageSent($message)
        )->toOthers();

        return $message;
    }
}
