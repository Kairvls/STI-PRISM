<?php

namespace App\Http\Controllers;

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

        $conversations = Conversation::whereHas('participants', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with(['lastMessage.sender', 'participants.user'])
            ->orderByDesc('last_message_at');

        if ($search !== '') {
            $conversations->whereHas('participants.user', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('user_full_name', 'like', "%{$search}%")
                      ->orWhereHas('role', function ($roleQuery) use ($search) {
                          $roleQuery->where('role_name', 'like', "%{$search}%");
                      });
                });
            });
        }

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

        $this->authorizeConversation($conversation, $userId);

        $conversation->load([
            'messages.sender',
            'participants.user'
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

        $message = new Message([
            'conversation_id' => $conversation->conversation_id,
            'sender_id' => $userId,
            'message_content' => $validated['message_content'],
        ]);
        $message->save();

        $conversation->update([
            'last_message_id' => $message->message_id,
            'last_message_at' => now(),
        ]);

        $message->load('sender');

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
     * Mark messages in the conversation as read.
     */
    public function markAsRead(Request $request, Conversation $conversation): JsonResponse
    {
        $userId = Auth::id();

        $this->authorizeConversation($conversation, $userId);

        Message::where('conversation_id', $conversation->conversation_id)
            ->where('is_read', false)
            ->where('sender_id', '!=', $userId)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        ConversationParticipant::where('conversation_id', $conversation->conversation_id)
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);

        return response()->json([
            'message' => 'Messages marked as read.',
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
            ->paginate(50);

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
    public function users(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $search = trim((string) $request->get('search', ''));

        $query = User::where('user_id', '!=', $userId)
            ->select('user_id', 'user_full_name', 'user_email_address', 'user_role_id')
            ->with(['role:role_id,role_name']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('user_full_name', 'like', "%{$search}%")
                  ->orWhere('user_email_address', 'like', "%{$search}%")
                  ->orWhereHas('role', function ($rq) use ($search) {
                      $rq->where('role_name', 'like', "%{$search}%");
                  });
            });
        }

        $users = $query->get()->map(function ($user) {
            return [
                'user_id' => $user->user_id,
                'name' => $user->user_full_name,
                'email' => $user->user_email_address,
                'role' => $user->role->role_name ?? 'User',
                'initials' => strtoupper(collect(explode(' ', $user->user_full_name))->take(2)->map(fn ($n) => $n[0] ?? '')->join('')),
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
}
