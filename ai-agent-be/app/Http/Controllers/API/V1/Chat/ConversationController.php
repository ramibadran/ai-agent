<?php

namespace App\Http\Controllers\API\V1\Chat;

use App\Http\Controllers\API\V1\BaseApiController;
use App\Http\Requests\V1\Chat\StoreConversationMessageRequest;
use App\Http\Requests\V1\Chat\StoreConversationRequest;
use App\Http\Resources\V1\ConversationMessageResource;
use App\Http\Resources\V1\ConversationResource;
use App\Models\Conversation;
use App\Services\V1\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConversationController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $conversations = $request->user()->conversations()
            ->withCount('messages')
            ->orderByDesc('last_message_at')
            ->latest()
            ->get();

        return $this->apiResponse('Success', ConversationResource::collection($conversations));
    }

    public function store(StoreConversationRequest $request, ChatService $chatService): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request, $chatService): JsonResponse {
            $result = $chatService->createConversation($request->user(), $request->string('message')->toString());
            $conversation = $result['conversation']->load('messages');

            return $this->apiResponse('Conversation created.', new ConversationResource($conversation), Response::HTTP_CREATED);
        });
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        if (! $this->isOwnedBy($request, $conversation)) {
            return $this->apiResponse('Conversation not found.', statusCode: Response::HTTP_NOT_FOUND);
        }

        return $this->apiResponse('Success', new ConversationResource($conversation->load('messages')));
    }

    public function storeMessage(StoreConversationMessageRequest $request, Conversation $conversation, ChatService $chatService): JsonResponse
    {
        if (! $this->isOwnedBy($request, $conversation)) {
            return $this->apiResponse('Conversation not found.', statusCode: Response::HTTP_NOT_FOUND);
        }

        return $this->executeWithErrorHandling(function () use ($request, $conversation, $chatService): JsonResponse {
            $result = $chatService->sendMessage($conversation, $request->string('message')->toString());

            return $this->apiResponse('Message sent.', [
                'user_message' => new ConversationMessageResource($result['userMessage']),
                'assistant_message' => new ConversationMessageResource($result['assistantMessage']),
            ], Response::HTTP_CREATED);
        });
    }

    public function destroy(Request $request, Conversation $conversation): JsonResponse
    {
        if (! $this->isOwnedBy($request, $conversation)) {
            return $this->apiResponse('Conversation not found.', statusCode: Response::HTTP_NOT_FOUND);
        }

        $conversation->delete();

        return $this->apiResponse('Conversation deleted.');
    }

    private function isOwnedBy(Request $request, Conversation $conversation): bool
    {
        return $conversation->user_id === $request->user()?->id;
    }
}
