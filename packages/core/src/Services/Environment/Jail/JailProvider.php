<?php

namespace Froxlor\Core\Services\Environment\Jail;

/** Trusted package code declares desired state, never arbitrary shell operations. */
interface JailProvider
{
    /** Globally unique package-qualified key, e.g. froxlor/web:php-cli. */
    public function key(): string;

    /** Return an empty plan when this environment does not use the package feature. */
    public function plan(JailContext $context): JailPlan;
}
