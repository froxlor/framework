<?php

namespace Froxlor\Core\Http\Controllers\Api;

use Carbon\Carbon;
use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Http\Requests\StoreApiKeyRequest;
use Froxlor\Core\Models\User;
use Froxlor\Core\Support\Response;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class ApiKeyController extends Controller
{
    public function index(Request $request)
    {
        $userMorphClass = (new User())->getMorphClass();

        return Response::jsonResourceCollection(
            PersonalAccessToken::query()
                ->where('tokenable_type', $userMorphClass)
                ->with('tokenable')
        );
    }

    public function store(StoreApiKeyRequest $request)
    {
        $user = $request->filled('user_id')
            ? User::query()->find($request->string('user_id'))
            : $request->user();

        if (!$user instanceof User) {
            return $this->errorResponse('No target user found for API key creation.', 422);
        }

        $abilities = collect(explode(',', (string)$request->input('abilities', '*')))
            ->map(fn(string $ability) => trim($ability))
            ->filter()
            ->values()
            ->all();

        if ($abilities === []) {
            $abilities = ['*'];
        }

        $expiresAt = $request->filled('expires_at')
            ? Carbon::parse($request->input('expires_at'))
            : null;

        $newToken = $user->createToken(
            name: $request->string('name')->toString(),
            abilities: $abilities,
            expiresAt: $expiresAt,
        );

        $token = $newToken->accessToken->load('tokenable');

        $token->setAttribute('plain_text_token', $newToken->plainTextToken);

        return Response::jsonResource($token);
    }

    public function show(PersonalAccessToken $apiKey)
    {
        $apiKey->load('tokenable');

        return Response::jsonResource($apiKey);
    }

    public function destroy(PersonalAccessToken $apiKey)
    {
        $apiKey->delete();

        return response()->noContent();
    }
}
