<?php

namespace Froxlor\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use phpseclib3\Crypt\PublicKeyLoader;
use Throwable;

class SshPrivateKey implements ValidationRule
{
    /**
     * Create a new SSH private key validation rule.
     *
     * @param string|null $password Optional passphrase used for encrypted keys.
     */
    public function __construct(private readonly ?string $password = null)
    {
    }

    /**
     * Validate that the value is a loadable SSH private key.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The :attribute must be a valid SSH private key.');

            return;
        }

        try {
            PublicKeyLoader::loadPrivateKey($value, $this->password ?: false);
        } catch (Throwable) {
            $fail('The :attribute must be a valid SSH private key and the password must match when the key is encrypted.');
        }
    }
}
