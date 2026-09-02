<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name_display' => ['required', 'string', 'max:100'],
            'device_identifier' => ['required', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'platform' => ['nullable', 'string', 'max:50'],
            'push_token' => ['nullable', 'string', 'max:2048'],
        ]);

        $recoverySecret = Str::random(32);

        [$user, $device] = DB::transaction(function () use ($validated, $recoverySecret): array {
            $user = User::create([
                'public_id' => $this->newPublicId(),
                'name_display' => $validated['name_display'],
                'recovery_secret' => $recoverySecret,
                'recovery_secret_updated_at' => now(),
            ]);

            $device = $user->devices()->create([
                'device_identifier' => $validated['device_identifier'],
                'device_name' => $validated['device_name'] ?? null,
                'platform' => $validated['platform'] ?? null,
                'push_token' => $validated['push_token'] ?? null,
                'last_seen_at' => now(),
            ]);

            return [$user, $device];
        });

        return response()->json([
            'user' => [
                'id' => $user->id,
                'public_id' => $user->public_id,
                'name_display' => $user->name_display,
                'is_discoverable' => $user->is_discoverable,
                'show_public_id_on_profile' => $user->show_public_id_on_profile,
            ],
            'device' => [
                'id' => $device->id,
                'device_identifier' => $device->device_identifier,
            ],
            'recovery_secret' => $recoverySecret,
            'message' => 'Store the recovery secret securely. It will not be shown again by this response.',
        ], 201);
    }

    private function newPublicId(): string
    {
        do {
            $publicId = Str::upper(Str::random(16));
        } while (User::where('public_id', $publicId)->exists());

        return $publicId;
    }
}
