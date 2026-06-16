<?php

namespace Froxlor\Mail\Providers;

use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Support\FroxlorVersion;
use Froxlor\Core\Support\PackageServiceProvider;
use Froxlor\Domain\Models\Domain;
use Froxlor\Mail\Models;
use Froxlor\Mail\Resources\Schemas\MailSchema;
use Froxlor\UI\Schemas;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Console\AboutCommand;

class FroxlorMailServiceProvider extends PackageServiceProvider
{
    public function boot(): void
    {
        AboutCommand::add('froxlor packages', fn() => [
            'mail' => FroxlorVersion::installedApplicationVersion('froxlor/mail', FroxlorVersion::release())
        ]);

        // Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

        // Views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'froxlor-mail');

        // Policies, Events etc. hier registrieren

        Relation::morphMap([
            'mail_addresses' => Models\MailAddress::class,
        ]);

        // Relations
        $this->extendRelations();
    }

    public function register(): void
    {
        //
    }

    private function extendRelations(): void
    {
        Domain::resolveRelationUsing('mail_addresses', function (Domain $domain) {
            return $domain->hasMany(Models\MailAddress::class);
        });

        // ui view relations
        $mailIndexSchema = MailSchema::indexSchema();
        Schemas\Schema::stack('tenants.domains.show.tabs', fn(Tenant $tenant, Domain $domain) => Schemas\Components\Tab::make('tenants.domains.show.tabs.domains')
            ->label(trans('froxlor-mail::generic.mails'))
            ->sort(5000)
            ->components([
                Schemas\Components\Relation::make('tenants.domains.show.relations.mails')
                    ->fetch(route('api.tenants.domains.mail.index', $tenant))
                    ->intendedRoute('tenants.domains.mail.show', ['tenant' => $tenant->id, 'domain' => $domain->id, 'mail' => '{id}'])
                    ->columns($mailIndexSchema['columns'])
                    ->actions($mailIndexSchema['actions'])
            ])
        );
    }

}
