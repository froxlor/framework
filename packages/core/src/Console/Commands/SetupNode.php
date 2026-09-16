<?php

namespace Froxlor\Core\Console\Commands;

use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\User;
use Froxlor\Core\Services\Node\Setup\NodeSetupService;
use Illuminate\Console\Command;

/** Explicit node ID avoids accidentally selecting several nodes sharing a hostname. */
class SetupNode extends Command
{
    protected $signature = 'core:setup-node {node : Node ULID} {--user= : Initiating user ULID}';

    protected $description = 'Queue or repeat node service setup as an explicitly identified user';

    public function handle(NodeSetupService $setups): int
    {
        $actor = $this->option('user') ? User::query()->find($this->option('user')) : null;
        if ($actor === null) {
            $this->error('An existing initiating user is required: --user=<user-ulid>.');

            return self::FAILURE;
        }
        $node = Node::query()->findOrFail($this->argument('node'));
        $requested = $setups->request($node, $actor);
        $this->info('Node setup queued: '.$requested->setup_request_id);

        return self::SUCCESS;
    }
}
