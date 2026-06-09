<?php

namespace Froxlor\Database\Policies;

use Froxlor\Core\Models\User;
use Froxlor\Database\Models\Database;

class DatabasePolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Database $database): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Database $database): bool
    {
        return false;
    }

    public function delete(User $user, Database $database): bool
    {
        return false;
    }

    public function restore(User $user, Database $database): bool
    {
        return false;
    }

    public function forceDelete(User $user, Database $database): bool
    {
        return false;
    }
}
