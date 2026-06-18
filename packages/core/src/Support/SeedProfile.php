<?php

namespace Froxlor\Core\Support;

use Illuminate\Support\Facades\App;

class SeedProfile
{
    /**
     * Determine whether development and test fixture seeders should be executed.
     *
     * Production seeding must stay limited to package-owned baseline data such as
     * settings, permissions, resources, and default roles. Local and testing
     * environments need additional sample tenants, users, environments, and package
     * records so feature tests and developer UIs have a representative data graph.
     */
    public static function includesDevelopmentData(): bool
    {
        return App::environment(['local', 'testing'])
            || (bool) config('dev.seed_development_data', false);
    }

    /**
     * Human readable label for audit log entries and seeder output.
     */
    public static function developmentDataLabel(): string
    {
        return App::environment('testing') ? 'testing' : 'development';
    }
}
