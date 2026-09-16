<?php

namespace Froxlor\Core\Services\Node\Setup;

use InvalidArgumentException;

/** Typed provider setting. Values are normalized before templates receive them. */
final readonly class SettingDefinition
{
    private function __construct(
        public string $type,
        public int|bool|string $default,
        public ?int $min = null,
        public ?int $max = null,
        public array $choices = [],
    ) {
        $this->normalize($default);
    }

    public static function integer(int $default, int $min, int $max): self
    {
        if ($min > $max) {
            throw new InvalidArgumentException('Invalid setting bounds.');
        }

        return new self('integer', $default, $min, $max);
    }

    public static function boolean(bool $default): self
    {
        return new self('boolean', $default);
    }

    /** @param list<string> $choices */
    public static function choice(string $default, array $choices): self
    {
        return new self('choice', $default, choices: $choices);
    }

    /** Never include rejected values in exceptions: settings may contain sensitive data. */
    public function normalize(mixed $value): int|bool|string
    {
        if ($this->type === 'integer') {
            if (is_string($value) && preg_match('/^-?(0|[1-9][0-9]*)$/D', $value)) {
                $value = filter_var($value, FILTER_VALIDATE_INT);
            }
            if (is_int($value) && $value >= $this->min && $value <= $this->max) {
                return $value;
            }
        } elseif ($this->type === 'boolean') {
            if (in_array($value, [true, 1, '1'], true)) {
                return true;
            }
            if (in_array($value, [false, 0, '0'], true)) {
                return false;
            }
        } elseif (is_string($value) && in_array($value, $this->choices, true)) {
            return $value;
        }

        throw new InvalidArgumentException('Invalid node service setting value.');
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
