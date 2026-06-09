<?php

namespace Froxlor\Packages\Observer;

use Exception;
use Froxlor\Packages\Models\Repository;
use Froxlor\Packages\Services\PackageService;

class RepositoryObserver
{
    public function created(Repository $repository): void
    {
        app(PackageService::class)->updateRepositories();
    }

    public function updated(Repository $repository): void
    {
        app(PackageService::class)->updateRepositories();
    }

    /**
     * @throws Exception
     */
    public function deleting(Repository $repository): void
    {
        app(PackageService::class)->removeRepository($repository);
    }
}
