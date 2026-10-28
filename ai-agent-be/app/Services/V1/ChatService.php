<?php

namespace App\Services\V1;

use App\Contracts\AnswerGenerator;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\User;
use Illuminate\Support\Str;
use Throwable;

class ChatService
{
    public function __construct(
        private readonly KnowledgeRetriever $knowledgeRetriever,
        private readonly AnswerGenerator $answerGenerator,
    ) {}

    /**
     * Create a conversation and send its first message.
     *
     * @return array{conversation: Conversation, user_message: ConversationMessage, assistant_message: ConversationMessage}
     */
    public function createConversation(User $user, string $message): array
    {
        $conversation = Conversation::query()->create([
            'user_id' => $user->id,
            'title' => Str::limit($message, 80),
        ]);

        return $this->sendMessage($conversation, $message);
    }

    /**
     * Send a message and generate a grounded assistant response.
     *
     * @return array{conversation: Conversation, user_message: ConversationMessage, assistant_message: ConversationMessage}
     */
    public function sendMessage(Conversation $conversation, string $message): array
    {
        $userMessage = $conversation->messages()->create([
            'role' => 'user',
            'content' => $message,
        ]);

        try {
            $sources = $this->knowledgeRetriever->retrieve($message);
            $answer = $this->answerGenerator->generate($message, $sources);

            $assistantMessage = $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $answer['answer'],
                'retrieval_context' => $sources,
                'model' => $answer['model'],
                'prompt_tokens' => $answer['prompt_tokens'],
                'completion_tokens' => $answer['completion_tokens'],
                'total_tokens' => $answer['total_tokens'],
                'latency_ms' => $answer['latency_ms'],
                'cost_usd' => $answer['cost_usd'],
            ]);
        } catch (Throwable $exception) {
            $assistantMessage = $conversation->messages()->create([
                'role' => 'assistant',
                'content' => 'I could not generate an answer at this time.',
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            $conversation->update(['last_message_at' => now()]);

            throw $exception;
        }

        $conversation->update(['last_message_at' => now()]);

        return compact('conversation', 'userMessage', 'assistantMessage');
    }
}
