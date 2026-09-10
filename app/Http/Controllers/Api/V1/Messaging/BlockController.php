<?php

namespace App\Http\Controllers\Api\V1\Messaging;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Actions\BlockUser;
use App\Domain\Messaging\Actions\UnblockUser;
use App\Domain\Messaging\Exceptions\MessagingException;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    public function store(Request $request, BlockUser $block, string $blockedPublicId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $block->handle($user, $blockedPublicId);
        } catch (MessagingException $exception) {
            return $this->failure($exception->getMessage(), $exception->status);
        }

        return ApiResponse::success([], 'User blocked.');
    }

    public function destroy(Request $request, UnblockUser $unblock, string $blockedPublicId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $unblock->handle($user, $blockedPublicId);
        } catch (MessagingException $exception) {
            return $this->failure($exception->getMessage(), $exception->status);
        }

        return ApiResponse::success([], 'User unblocked.');
    }

    private function failure(string $message, int $status): JsonResponse
    {
        return ApiResponse::failure($message, $status);
    }
}
