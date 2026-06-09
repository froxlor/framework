<?php

namespace Froxlor\UI\Contracts;

interface Payloadable
{
    public function toPayload(): array;
}
