<?php

namespace App\Http\Middleware;

use App\Domain\Authentication\Models\Device;
use App\Domain\Identity\Models\User;
use App\Infrastructure\Jwt\JwtTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthenticateWithJwt
{
    public function __construct(private readonly JwtTokenService $jwtTokens)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization');

        if (! preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        try {
            $token = $this->jwtTokens->parseAndValidate($matches[1]);
            $userId = (string) $token->claims()->get('sub');
            $deviceId = (string) $token->claims()->get('device_id');
            $user = User::query()->find($userId);
            $device = Device::query()->whereKey($deviceId)->where('user_id', $userId)->first();

            if (! $user || ! $device || $device->revoked_at !== null) {
                return response()->json(['message' => 'Authentication failed.'], 401);
            }

            $request->setUserResolver(fn () => $user);
            $request->attributes->set('authenticated_device', $device);
        } catch (Throwable) {
            return response()->json(['message' => 'Authentication failed.'], 401);
        }

        return $next($request);
    }
}
