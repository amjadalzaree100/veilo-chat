<?php

namespace App\Http\Controllers\Api\V1\Messaging;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Actions\CreateConversation;
use App\Domain\Messaging\Exceptions\MessagingException;
use App\Domain\Messaging\Models\Conversation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Messaging\CreateConversationRequest;
use App\Http\Requests\Api\V1\Messaging\ListConversationsRequest;
use App\Http\Resources\Api\V1\Messaging\ConversationResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class ConversationController extends Controller
{
    public function index(ListConversationsRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $userId = (string) $user->getKey();

        $paginator = Conversation::query()
            ->where(function ($query) use ($userId): void {
                $query->where('user_low_id', $userId)->orWhere('user_high_id', $userId);
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->cursorPaginate($request->integer('per_page', 50), ['*'], 'cursor', $request->input('cursor'));

        return ApiResponse::success([
            'conversations' => ConversationResource::collection($paginator->getCollection())->resolve($request),
            'pagination' => [
                'per_page' => $paginator->perPage(),
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'previous_cursor' => $paginator->previousCursor()?->encode(),
            ],
        ], 'Conversations retrieved.');
    }

    public function store(CreateConversationRequest $request, CreateConversation $create): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $conversation = $create->handle($user, $request->string('participant_public_id')->toString());
        } catch (MessagingException $exception) {
            return $this->failure($exception->getMessage(), $exception->status);
        }

        return ApiResponse::success([
            'conversation' => (new ConversationResource($conversation))->resolve($request),
        ], $conversation->wasRecentlyCreated ? 'Conversation created.' : 'Conversation already exists.', $conversation->wasRecentlyCreated ? 201 : 200);
    }

    private function failure(string $message, int $status): JsonResponse
    {
        return ApiResponse::failure($message, $status);
    }
}
