<?php

namespace Froxlor\Core\Services\Traits;

use Froxlor\Core\Support\Quota;

/** Keep object/pivot persistence and synchronous usage observers in one transaction. */
trait SavesWithinQuotaTransaction
{
    public function save(array $options = [])
    {
        return Quota::transaction(fn () => parent::save($options));
    }
}
