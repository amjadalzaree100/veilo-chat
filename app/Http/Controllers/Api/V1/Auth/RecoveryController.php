<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Authentication\Actions\RecoverAccount;
use App\Domain\Authentication\Actions\RegenerateRecoverySecret;
use App\Domain\Authentication\Actions\RevealRecoverySecret;
use App\Domain\Authentication\Actions\SetRecoverySecretVisibility;
use App\Domain\Authentication\Models\Device;
use App\Domain\Identity\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RecoveryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class RecoveryController extends Controller
{
    public function recover(RecoveryRequest $request, RecoverAccount $recover): JsonResponse
    {
        try {
            $result = $recover->handle($request->validated(), $request->ip(), $request->userAgent());
        } catch (RuntimeException) {
            return response()->json(['message' => 'Recovery failed.'], 401);
        }

        return response()->json([
            'data' => [
                'user' => [
                    'id' => str_replace('-', '', $result['user']->id),
                    'public_id' => str_replace('-', '', $result['user']->public_id),
                    'username' => $result['user']->username,
                ],
                'device' => [
                    'id' => str_replace('-', '', $result['device']->id),
                    'is_primary' => $result['device']->is_primary,
                ],
                'device_secret' => $result['session']['device_secret'],
                'tokens' => [
                    'access_token' => $result['session']['access_token'],
                    'refresh_token' => $result['session']['refresh_token'],
                    'token_type' => 'Bearer',
                    'expires_in' => $result['session']['expires_in'],
                ],
            ],
        ], 201);
    }

    public function show(Request $request, RevealRecoverySecret $reveal): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            return response()->json(['data' => ['recovery_secret' => $reveal->handle($user)]]);
        } catch (RuntimeException) {
            return response()->json(['message' => 'The recovery secret is hidden.'], 409);
        }
    }

    public function setVisibility(Request $request, SetRecoverySecretVisibility $visibility): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var Device $device */
        $device = $request->attributes->get('authenticated_device');
        $visible = $request->boolean('visible');

        try {
            $visibility->handle($user, $device, $visible, $request->ip(), $request->userAgent());
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return response()->json(['message' => $visible ? 'The recovery secret is visible.' : 'The recovery secret is hidden.']);
    }

    public function regenerate(Request $request, RegenerateRecoverySecret $regenerate): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var Device $device */
        $device = $request->attributes->get('authenticated_device');

        try {
            $secret = $regenerate->handle($user, $device, $request->ip(), $request->userAgent());
        } catch (RuntimeException) {
            return response()->json(['message' => 'The recovery secret is hidden.'], 409);
        }

        return response()->json(['data' => ['recovery_secret' => $secret]]);
    }
}
