<?php

namespace Froxlor\Core\Services\Environment\Jail;

use Froxlor\Core\Models\Environment;
use Froxlor\Core\Services\Node\Setup\SettingDefinition;
use Froxlor\Core\Support\Setting;
use InvalidArgumentException;
use Illuminate\Support\Facades\DB;

/** Typed, package-owned environment settings shared by jail providers. */
final readonly class EnvironmentSettings
{
    private function __construct(private array $values) {}

    public static function path(JailProvider $provider, string $name): string
    {
        return 'environment.services.'.$provider->key().'.'.$name;
    }

    public static function resolve(Environment $environment, JailProvider $provider, array $overrides = []): self
    {
        $definitions = $provider->settings();
        if (array_diff_key($overrides, $definitions) !== []) {
            throw new InvalidArgumentException('Unknown environment jail settings.');
        }
        $values = [];
        foreach ($definitions as $name => $definition) {
            if (!$definition instanceof SettingDefinition) {
                throw new InvalidArgumentException('Invalid environment jail setting definition.');
            }
            $value = array_key_exists($name, $overrides)
                ? $overrides[$name]
                : Setting::getForModel($environment, self::path($provider, $name), $definition->default);
            $values[$name] = $definition->normalize($value);
        }
        return new self($values);
    }

    public static function store(Environment $environment, JailProvider $provider, array $values): void
    {
        $resolved = self::resolve($environment, $provider, $values);
        DB::transaction(function () use ($environment, $provider, $resolved): void {
            foreach ($provider->settings() as $name => $definition) {
                Setting::setValueForModel($environment, self::path($provider, $name), $resolved->values[$name], $definition->type, $provider->package());
            }
        });
        if (app()->bound(EnvironmentJailReconcileDispatcher::class)) {
            app(EnvironmentJailReconcileDispatcher::class)->dispatchForEnvironment($environment);
        }
    }

    public function integer(string $name): int { return $this->typed($name, 'integer'); }
    public function boolean(string $name): bool { return $this->typed($name, 'boolean'); }
    public function string(string $name): string { return $this->typed($name, 'string'); }

    private function typed(string $name, string $type): int|bool|string
    {
        if (!array_key_exists($name, $this->values) || gettype($this->values[$name]) !== $type) {
            throw new InvalidArgumentException('Unknown environment setting or incorrect accessor.');
        }
        return $this->values[$name];
    }
}
