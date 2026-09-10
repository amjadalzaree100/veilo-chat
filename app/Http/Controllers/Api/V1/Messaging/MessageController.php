<?php

namespace App\Http\Controllers\Api\V1\Messaging;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Actions\DeleteMessage;
use App\Domain\Messaging\Actions\EditMessage;
use App\Domain\Messaging\Actions\SendMessage;
use App\Domain\Messaging\Exceptions\MessagingException;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\Message;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Messaging\EditMessageRequest;
use App\Http\Requests\Api\V1\Messaging\ListMessagesRequest;
use App\Http\Requests\Api\V1\Messaging\SendMessageRequest;
use App\Http\Resources\Api\V1\Messaging\MessageResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(ListMessagesRequest $request, Conversation $conversation): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $conversation->containsUser((string) $user->getKey())) {
            return $this->failure('You are not a participant in this conversation.', 403);
        }

        $paginator = $conversation->messages()
            ->whereNull('deleted_at')
            ->orderByDesc('sequence_no')
            ->cursorPaginate($request->integer('per_page', 50), ['*'], 'cursor', $request->input('cursor'));

        return ApiResponse::success([
            'messages' => MessageResource::collection($paginator->getCollection())->resolve($request),
            'pagination' => [
                'per_page' => $paginator->perPage(),
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'previous_cursor' => $paginator->previousCursor()?->encode(),
            ],
        ], 'Messages retrieved.');
    }

    public function store(SendMessageRequest $request, Conversation $conversation, SendMessage $send): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $result = $send->handle($user, $conversation, $request->string('body')->toString(), $request->input('client_message_id'), $request->input('reply_to_message_id'));
        } catch (MessagingException $exception) {
            return $this->failure($exception->getMessage(), $exception->status);
        }

        return ApiResponse::success(['message' => (new MessageResource($result['message']))->resolve($request)], $result['created'] ? 'Message sent.' : 'Existing message returned.', $result['created'] ? 201 : 200);
    }

    public function update(EditMessageRequest $request, Message $message, EditMessage $edit): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $message = $edit->handle($user, $message, $request->string('body')->toString());
        } catch (MessagingException $exception) {
            return $this->failure($exception->getMessage(), $exception->status);
        }

        return ApiResponse::success(['message' => (new MessageResource($message))->resolve($request)], 'Message updated.');
    }

    public function destroy(Request $request, Message $message, DeleteMessage $delete): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $message = $delete->handle($user, $message);
        } catch (MessagingException $exception) {
            return $this->failure($exception->getMessage(), $exception->status);
        }

        return ApiResponse::success(['message' => (new MessageResource($message))->resolve($request)], 'Message deleted.');
    }

    private function failure(string $message, int $status): JsonResponse
    {
        return ApiResponse::failure($message, $status);
    }
}
