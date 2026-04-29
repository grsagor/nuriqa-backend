<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConversationRequest;
use App\Http\Requests\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ConversationController extends Controller
{
    public function __construct(
        private ConversationService $conversationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $conversations = Conversation::query()
            ->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
            ->with([
                'users' => fn ($q) => $q->select('users.id', 'users.name', 'users.image'),
                'latestMessage.sender' => fn ($q) => $q->select('users.id', 'users.name', 'users.image'),
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();

        $data = $conversations->map(function (Conversation $c) use ($user) {
            $other = $c->users->firstWhere('id', '!=', $user->id);
            $last = $c->latestMessage;
            $me = $c->users->firstWhere('id', $user->id);
            $lastRead = $me?->pivot?->last_read_at;

            $unread = 0;
            if ($lastRead) {
                $unread = $c->messages()
                    ->where('user_id', '!=', $user->id)
                    ->where('created_at', '>', $lastRead)
                    ->count();
            } else {
                $unread = $c->messages()->where('user_id', '!=', $user->id)->count();
            }

            return [
                'id' => $c->id,
                'updated_at' => $c->updated_at?->toIso8601String(),
                'last_message_at' => $c->last_message_at?->toIso8601String(),
                'other_user' => $other ? $this->formatUser($other) : null,
                'last_message' => $last ? $this->formatMessage($last) : null,
                'unread_count' => $unread,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function store(StoreConversationRequest $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();
        $other = User::query()->findOrFail($request->validated('user_id'));

        $conversation = $this->conversationService->findOrCreateDirectConversation($user, $other);
        $conversation->load(['users' => fn ($q) => $q->select('users.id', 'users.name', 'users.image')]);

        $otherUser = $conversation->users->firstWhere('id', '!=', $user->id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $conversation->id,
                'other_user' => $otherUser ? $this->formatUser($otherUser) : null,
            ],
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();
        $conversation = Conversation::query()
            ->with(['users' => fn ($q) => $q->select('users.id', 'users.name', 'users.image')])
            ->findOrFail($id);

        if (! $this->conversationService->userIsParticipant($conversation, $user)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $other = $conversation->users->firstWhere('id', '!=', $user->id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $conversation->id,
                'other_user' => $other ? $this->formatUser($other) : null,
            ],
        ]);
    }

    public function messages(Request $request, int $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();
        $conversation = Conversation::query()->findOrFail($id);

        if (! $this->conversationService->userIsParticipant($conversation, $user)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $perPage = min(100, max(1, (int) $request->get('per_page', 50)));

        $paginator = Message::query()
            ->where('conversation_id', $conversation->id)
            ->with(['sender' => fn ($q) => $q->select('users.id', 'users.name', 'users.image')])
            ->orderByDesc('id')
            ->paginate($perPage);

        $items = collect($paginator->items())->reverse()->values()->map(fn (Message $m) => $this->formatMessage($m));

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function storeMessage(StoreMessageRequest $request, int $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();
        $conversation = Conversation::query()->findOrFail($id);

        if (! $this->conversationService->userIsParticipant($conversation, $user)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $validated = $request->validated();

        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'type' => $validated['type'],
            'body' => $validated['body'],
        ]);

        $conversation->update(['last_message_at' => now()]);
        $message->load(['sender' => fn ($q) => $q->select('users.id', 'users.name', 'users.image')]);

        return response()->json([
            'success' => true,
            'data' => $this->formatMessage($message),
        ], 201);
    }

    public function upload(Request $request, int $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();
        $conversation = Conversation::query()->findOrFail($id);

        if (! $this->conversationService->userIsParticipant($conversation, $user)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx'],
        ]);

        $file = $request->file('file');
        $original = $file->getClientOriginalName();
        $path = $file->store('messages/'.date('Y/m'), 'public');

        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'type' => 'file',
            'body' => null,
            'attachment_path' => $path,
            'attachment_name' => Str::limit($original, 255, ''),
        ]);

        $conversation->update(['last_message_at' => now()]);
        $message->load(['sender' => fn ($q) => $q->select('users.id', 'users.name', 'users.image')]);

        return response()->json([
            'success' => true,
            'data' => $this->formatMessage($message),
        ], 201);
    }

    public function markRead(int $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();
        $conversation = Conversation::query()->findOrFail($id);

        if (! $this->conversationService->userIsParticipant($conversation, $user)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $conversation->users()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * @return array{id: int, name: string, image_url: string|null}
     */
    private function formatUser(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'image_url' => $user->image_url ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatMessage(Message $message): array
    {
        $sender = $message->relationLoaded('sender') ? $message->sender : $message->sender()->first();
        $url = null;
        if ($message->attachment_path) {
            $url = Storage::disk('public')->url($message->attachment_path);
        }

        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'type' => $message->type,
            'body' => $message->body,
            'attachment_url' => $url,
            'attachment_name' => $message->attachment_name,
            'created_at' => $message->created_at?->toIso8601String(),
            'sender' => $sender ? [
                'id' => (int) $sender->id,
                'name' => (string) $sender->name,
                'image_url' => $sender->image_url ?? null,
            ] : null,
        ];
    }
}
