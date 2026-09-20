<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Domain\Authentication\Actions\LinkEmail;
use App\Domain\Authentication\Models\Device;
use App\Domain\Identity\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\RequestEmailOtpRequest;
use App\Http\Requests\Api\V1\Account\VerifyEmailOtpRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class EmailController extends Controller
{
    public function requestOtp(RequestEmailOtpRequest $request, LinkEmail $linkEmail): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var Device $device */
        $device = $request->attributes->get('authenticated_device');

        try {
            $expiresIn = $linkEmail->requestOtp(
                $user,
                $device,
                $request->string('email')->toString(),
                $request->ip(),
                $request->userAgent(),
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return response()->json(['data' => ['expires_in' => $expiresIn]], 202);
    }

    public function resendOtp(RequestEmailOtpRequest $request, LinkEmail $linkEmail): JsonResponse
    {
        return $this->requestOtp($request, $linkEmail);
    }

    public function verify(VerifyEmailOtpRequest $request, LinkEmail $linkEmail): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var Device $device */
        $device = $request->attributes->get('authenticated_device');

        try {
            $linkEmail->verify(
                $user,
                $device,
                $request->string('email')->toString(),
                $request->string('code')->toString(),
                $request->ip(),
                $request->userAgent(),
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return response()->json([
            'data' => [
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toISOString(),
            ],
        ]);
    }

    public function unlink(Request $request, LinkEmail $linkEmail): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var Device $device */
        $device = $request->attributes->get('authenticated_device');

        try {
            $linkEmail->unlink($user, $device, $request->ip(), $request->userAgent());
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return response()->json(['message' => 'The email was unlinked from your account.']);
    }
}
