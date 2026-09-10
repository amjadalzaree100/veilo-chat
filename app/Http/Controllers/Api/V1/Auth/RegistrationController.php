<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Authentication\Actions\RegisterAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use Illuminate\Http\JsonResponse;

class RegistrationController extends Controller
{
    public function __invoke(RegisterRequest $request, RegisterAccount $register): JsonResponse
    {
        $result = $register->handle($request->validated());
        $user = $result['user'];
        $device = $result['device'];

        return response()->json([
            'data' => [
                'user' => [
                    'id' => str_replace('-', '', $user->id),
                    'public_id' => str_replace('-', '', $user->public_id),
                    'username' => $user->username,
                    'display_name' => $user->display_name,
                    'privacy' => $user->privacy,
                ],
                'device' => [
                    'id' => str_replace('-', '', $device->id),
                    'is_primary' => $device->is_primary,
                ],
                'recovery_secret' => $result['recovery_secret'],
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
}
