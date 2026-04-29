<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConversationService
{
    /**
     * Find existing 1:1 conversation between two users, or create one.
     */
    public function findOrCreateDirectConversation(User $a, User $b): Conversation
    {
        if ($a->id === $b->id) {
            throw new \InvalidArgumentException('Cannot start a conversation with yourself.');
        }

        $existing = Conversation::query()
            ->has('users', '=', 2)
            ->whereHas('users', fn ($q) => $q->where('users.id', $a->id))
            ->whereHas('users', fn ($q) => $q->where('users.id', $b->id))
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($a, $b) {
            $conversation = Conversation::query()->create([
                'last_message_at' => null,
            ]);
            $conversation->users()->attach([$a->id => [], $b->id => []]);

            return $conversation;
        });
    }

    public function userIsParticipant(Conversation $conversation, User $user): bool
    {
        return $conversation->users()->where('users.id', $user->id)->exists();
    }
}
