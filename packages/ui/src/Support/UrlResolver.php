<?php

namespace Froxlor\UI\Support;

class UrlResolver
{
    /**
     * Löst eine intended-Definition gegen einen konkreten Datensatz auf.
     *
     * Unterstützte Formate für $intended:
     *   - string:  wird direkt zurückgegeben
     *   - array:   ['route' => '...', 'attributes' => ['id' => '{id}']]
     *              Platzhalter wie {id} werden gegen $item aufgelöst
     *   - null:    gibt null zurück
     */
    public static function resolve(mixed $intended, array $item = []): ?string
    {
        if (is_string($intended) && strlen($intended)) {
            return $intended;
        }

        if (is_array($intended) && isset($intended['route'])) {
            $attributes = collect($intended['attributes'] ?? [])
                ->mapWithKeys(function ($value, $key) use ($item) {
                    return [
                        $key => preg_replace_callback(
                            '/\{(.*?)\}/',
                            fn($matches) => data_get($item, $matches[1], $matches[0]),
                            $value
                        ),
                    ];
                })
                ->toArray();

            return route($intended['route'], $attributes);
        }

        return null;
    }
}
