<?php

namespace Froxlor\Core\Http\Controllers\Web;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\User;
use Froxlor\Core\Resources\ApiKeys\ApiKeyResource;
use Froxlor\Core\Support\Audit;
use Froxlor\UI\Support\UI;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class ApiKeyController extends Controller
{
    public function index()
    {
        return UI::render(ApiKeyResource::class, 'index');
    }

    public function create()
    {
        return UI::render(ApiKeyResource::class, 'create');
    }

    public function show(Request $request, PersonalAccessToken $apiKey)
    {
        $responseItem = $request->session()->pull('_ui.response_item');
        $plainTextToken = is_array($responseItem) && (string)($responseItem['id'] ?? '') === (string)$apiKey->getKey()
            ? ($responseItem['plain_text_token'] ?? null)
            : null;

        return UI::render(ApiKeyResource::class, 'show', [
            'apiKey' => $apiKey,
            'plainTextToken' => $plainTextToken,
        ]);
    }

    public function destroy(PersonalAccessToken $apiKey): RedirectResponse
    {
        $apiKey->loadMissing('tokenable');
        $user = $apiKey->tokenable instanceof User ? $apiKey->tokenable : null;
        $tenant = $user?->tenants()->first();
        $apiKeyId = $apiKey->id;
        $apiKeyName = $apiKey->name;

        $apiKey->delete();

        Audit::info('api key "' . $apiKeyName . '" deleted', $tenant, context: [
            'api_key_id' => $apiKeyId,
            'user_id' => $user?->id,
        ]);

        return redirect()->route('auth.api-keys.index');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $selected = collect($request->input('selected', []))
            ->map(fn (mixed $value) => (string) $value)
            ->filter()
            ->values()
            ->all();

        if ($selected !== []) {
            $tokens = PersonalAccessToken::query()
                ->with('tokenable')
                ->whereIn('id', $selected)
                ->get();

            PersonalAccessToken::query()
                ->whereIn('id', $tokens->modelKeys())
                ->delete();

            if ($tokens->isNotEmpty()) {
                Audit::info('api keys deleted', context: [
                    'api_key_ids' => $tokens->modelKeys(),
                    'count' => $tokens->count(),
                ]);
            }
        }

        return redirect()->route('auth.api-keys.index');
    }
}
