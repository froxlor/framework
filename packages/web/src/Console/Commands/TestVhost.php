<?php

namespace Froxlor\Web\Console\Commands;

use Froxlor\Web\Enums\SslMode;
use Froxlor\Web\Jobs\GenerateDomainVhostJob;
use Froxlor\Web\Models\DomainVhost;
use Illuminate\Console\Command;

class TestVhost extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'web:test-vhost';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a vhost-content for testing purposes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dvhost = DomainVhost::query()->first();

        if (!$dvhost->domainSslVhost()->exists()) {
            $this->output->note('Creating domain-ssl-vhost');
            $dvhost->domainSslVhost()->create([
                'ssl_redirect' => true,
                'ssl_mode' => SslMode::Auto
            ]);
        }

        (new GenerateDomainVhostJob($dvhost))->handle();
    }
}
