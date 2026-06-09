<?php

namespace Froxlor\UI\Exceptions;

use Throwable;

class ApiException extends \RuntimeException
{
    private array $errors = [];

    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public static function fromResponse(int $status, ?string $message, array $errors = []): self
    {
        $resolved = $message
            ?? ($errors !== [] ? $errors[array_key_first($errors)] : null)
            ?? 'An error occurred while fetching data from the internal API.';

        $exception = new self($resolved, $status);
        $exception->setErrors($errors);

        return $exception;
    }

    public static function fromThrowable(string $context, \Throwable $previous): self
    {
        return new self(
            message: $context,
            code: $previous->getCode() ?: 500,
            previous: $previous
        );
    }
}
