<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendConversationMessageRequest;
use App\Http\Resources\ConversationMessageResource;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Services\Support\ConversationService;
use Illuminate\Http\JsonResponse;

/**
 * The conversation token is an unguessable UUID and acts as the customer's access key.
 */
class ConversationController extends Controller
{
    public function store(ConversationService $service): JsonResponse
    {
        $conversation = $service->start()->load('messages');

        return (new ConversationResource($conversation))->response()->setStatusCode(201);
    }

    public function show(Conversation $conversation): ConversationResource
    {
        return new ConversationResource($conversation->load('messages'));
    }

    public function sendMessage(SendConversationMessageRequest $request, Conversation $conversation, ConversationService $service): JsonResponse
    {
        $messages = $service->reply($conversation, $request->validated('content'));

        return response()->json([
            'data' => [
                'stage' => $conversation->stage->value,
                'messages' => ConversationMessageResource::collection($messages),
            ],
        ]);
    }
}
