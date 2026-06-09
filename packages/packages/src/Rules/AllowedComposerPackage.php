<?php

namespace Froxlor\Packages\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class AllowedComposerPackage implements ValidationRule
{
    public function __construct(private array $allowed)
    {
        //
    }

    /**
     * Run the validation rule.
     *
     * @param \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $name = explode(':', $value, 2)[0];

        $matches = collect($this->allowed)
            ->map(fn($p) => explode(':', $p, 2)[0]) // auch hier nur Namen
            ->contains(fn($pattern) => Str::is($pattern, $name));

        if (!$matches) {
            $fail("The package {$name} is not allowed.");
        }
    }
}
