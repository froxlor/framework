<?php

namespace Froxlor\Web\Jobs;

use Froxlor\Core\Services\Node\Provisioning\ScriptDeployer;
use Froxlor\Core\Services\Node\Provisioning\ScriptRegistry;
use Froxlor\Web\Models\DomainVhost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class GenerateDomainVhostJob implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public function __construct(private readonly DomainVhost $domainVhost)
	{
	}

    /**
     * @throws \Throwable
     */
    public function handle(): void
	{
        $domainVhost = $this->domainVhost->loadMissing([
            'domain.node',
            'nodeInterfaces',
            'domainSslVhost',
        ]);
        $node = $domainVhost->domain->node;

        if (! $node) {
            throw new RuntimeException('Domain vhost has no assigned node.');
        }

        $http_flavor = $node->getSetting('web.httpd');
        $definition = ScriptRegistry::resolve('web-vhost', 'configure', $node, $http_flavor);

        if (! $definition) {
            throw new RuntimeException(sprintf(
                'No web-vhost configure script registered for platform %s and variant %s.',
                $node->platform()->key(),
                $http_flavor
            ));
        }

        $context = [
            'domainVhost' => $domainVhost,
            'node' => $node,
            'platform' => $node->platform(),
        ];

        $plan = app(ScriptDeployer::class)->plan($definition, $context);
        app(ScriptDeployer::class)->apply($node, $plan);
	}
}
