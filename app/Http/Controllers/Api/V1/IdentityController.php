<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Identity\Actions\SetPrivacy;
use App\Domain\Identity\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IdentityController extends Controller
{
    public function setPrivacy(Request $request, SetPrivacy $setPrivacy): JsonResponse
    {
        $data = $request->validate([
            'privacy' => ['required', Rule::in(['public', 'private'])],
        ]);

        /** @var User $user */
        $user = $request->user();
        $setPrivacy->handle($user, $data['privacy']);

        return response()->json(['data' => ['privacy' => $data['privacy']]]);
    }
}
