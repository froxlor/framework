<?php

namespace Froxlor\Core\Services\Node;

use Froxlor\Core\Models\Node as NodeModel;

class Node
{
    public static function current(): ?NodeModel
    {
        return NodeModel::query()->first();
    }
}
