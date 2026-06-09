<?php

namespace Froxlor\Core\Services\Node\Traits;

use Froxlor\Core\Exceptions\UnregisteredNodeAdapterException;
use Froxlor\Core\Services\Node\Adapter\Adapter;

/**
 * @property string $adapter
 */
trait HasAdapter
{
    public static array $registeredAdapter = [];

    /**
     * get the nodes connection adapter, e.g. local or remote
     *
     * @return Adapter
     * @throws UnregisteredNodeAdapterException
     */
    public function adapter(): Adapter
    {
        $adapterClass = $this->adapter;
        if (!in_array($adapterClass, self::adapters())) {
            throw new UnregisteredNodeAdapterException(printf("Adapter '%s' for node '%s' is not a registered adapter", $adapterClass, $this->hostname));
        }
        return new $adapterClass($this);
    }

    public static function registerAdapter(string $class): void
    {
        self::$registeredAdapter[] = $class;
    }

    public static function adapters(): array
    {
        return self::$registeredAdapter;
    }
}
