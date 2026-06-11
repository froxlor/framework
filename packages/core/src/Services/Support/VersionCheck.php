<?php

namespace Froxlor\Core\Services\Support;

use Froxlor\Core\Support\FroxlorVersion;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VersionCheck
{
    const UPDATE_URI = "https://version.froxlor.org/froxlor/api/v3/";

    public static function checkVersion(): array
    {
        $version = FroxlorVersion::release();
        $channel = '/stable';

        try {
            $latestversion = Http::withHeader('User-Agent', FroxlorVersion::userAgent())->get(self::UPDATE_URI . $version . $channel);
        } catch (ConnectionException $e) {
            Log::error($e->getMessage());
            return [
                'code' => -1,
                'msg' => $e->getMessage()
            ];
        }

        $version_result = $latestversion->json();
        if (is_array($version_result)) {
            if (!empty($version_result['error'])) {
                return [
                    'code' => -1,
                    'msg' => $version_result['message']
                ];
            } elseif (isset($version_result['has_latest'])) {
                if (!$version_result['has_latest']) {
                    return [
                        'code' => 1,
                        'msg' => $version_result['version']
                    ];
                }
                return $version_result;
            }
        }
        return [
            'code' => -1,
            'msg' => 'Unknown error'
        ];
    }
}
