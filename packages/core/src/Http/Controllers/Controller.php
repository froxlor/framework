<?php

namespace Froxlor\Core\Http\Controllers;

use Froxlor\Core\Support\Setting;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class Controller
{

    protected function checkFeatureEnabled(string $setting, ?string $errMessage = 'feature not enabled', ?int $errCode = 503)
    {
        if (!Setting::get($setting)) {
            return $this->errorResponse($errMessage, $errCode);
        }
    }

    protected function errorResponse(string $errMessage, ?int $errCode = 500)
    {
        return JsonResource::make(['error' => $errMessage])
            ->response()
            ->setStatusCode($errCode);
    }

    /**
     * returns a value from a request array and unsets it
     *
     * @param string $key
     * @param array $reqData
     * @return mixed
     */
    protected function getNonModelRequestData(string $key, array &$reqData): mixed
    {
        $value = $reqData[$key] ?? null;
        unset($reqData[$key]);
        return $value;
    }

    /**
     * Return validated API event payload when the request supports event rules.
     *
     * Store requests based on FroxlorFormRequest can expose additional event
     * data through validatedEvent(). Plain Laravel FormRequest instances do
     * not, so callers receive an empty payload until their request class opts in.
     *
     * @param object $request Request object passed to the controller action.
     * @return array<string, mixed>
     */
    protected function validatedEventData(object $request): array
    {
        return method_exists($request, 'validatedEvent') ? $request->validatedEvent() : [];
    }
}
