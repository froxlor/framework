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
}
