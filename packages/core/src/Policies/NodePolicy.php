<?php

namespace Froxlor\Core\Policies;

use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\User;

class NodePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('nodes.index');
    }

    public function view(User $user, Node $node): bool
    {
        return $user->hasPermission('nodes.index');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('nodes.store');
    }

    public function update(User $user, Node $node): bool
    {
        return $user->hasPermission('nodes.update');
    }

    public function delete(User $user, Node $node): bool
    {
        return $user->hasPermission('nodes.destroy');
    }
}
