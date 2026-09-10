<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Authentication\Actions\RefreshDeviceSession;
use App\Domain\Authentication\Actions\RevokeAllSessions;
use App\Domain\Authentication\Actions\RevokeDeviceSession;
use App\Domain\Authentication\Actions\RestoreSessionByDeviceSecret;
use App\Domain\Authentication\Models\Device;
use App\Domain\Identity\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RefreshTokenRequest;
use App\Http\Requests\Api\V1\Auth\RestoreSessionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class SessionController extends Controller
{
    public function refresh(RefreshTokenRequest $request, RefreshDeviceSession $refresh): JsonResponse
    {
        try {
            $session = $refresh->handle(
                $request->string('refresh_token')->toString(),
                (string) $request->ip(),
                $request->userAgent(),
            );
        } catch (RuntimeException) {
            return response()->json(['message' => 'Invalid refresh token.'], 401);
        }

        return response()->json(['data' => $this->sessionPayload($session)]);
    }

    public function restore(RestoreSessionRequest $request, RestoreSessionByDeviceSecret $restore): JsonResponse
    {
        try {
            $session = $restore->handle(
                $request->string('device_identifier')->toString(),
                $request->string('device_secret')->toString(),
                (string) $request->ip(),
                $request->userAgent(),
            );
        } catch (RuntimeException) {
            return response()->json(['message' => 'Session restoration failed.'], 401);
        }

        return response()->json(['data' => $this->sessionPayload($session)]);
    }

    public function logout(Request $request, RevokeDeviceSession $revoke): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var Device $device */
        $device = $request->attributes->get('authenticated_device');
        $revoke->handle($user, $device, $request->ip(), $request->userAgent());

        return response()->json(['message' => 'The current device session was revoked.']);
    }

    public function logoutAll(Request $request, RevokeAllSessions $revoke): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $revoke->handle($user, $request->ip(), $request->userAgent());

        return response()->json(['message' => 'All device sessions were revoked.']);
    }

    /**
     * @param array{user: User, device: Device, access_token: string, refresh_token: string, expires_in: int} $session
     */
    private function sessionPayload(array $session): array
    {
        return [
            'user' => [
                'id' => str_replace('-', '', $session['user']->id),
                'public_id' => str_replace('-', '', $session['user']->public_id),
                'username' => $session['user']->username,
            ],
            'device' => [
                'id' => str_replace('-', '', $session['device']->id),
                'is_primary' => $session['device']->is_primary,
            ],
            'tokens' => [
                'access_token' => $session['access_token'],
                'refresh_token' => $session['refresh_token'],
                'token_type' => 'Bearer',
                'expires_in' => $session['expires_in'],
            ],
        ];
    }
}
