<?php

namespace Froxlor\Core\Console\Commands;

use Froxlor\Core\Jobs\Node\ExploreNode as ExplodeNodeJob;
use Froxlor\Core\Models\Node;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

class ExploreNode extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'core:explore-node
                            {hostname? : Hostname of the node to be explored }
                            {--i|initial : Initial exploring includes ip-addresses of given node }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Connect to node to obtain/update system data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hostname = $this->argument('hostname');

        $nodes = Node::query()
            ->when($hostname, fn($query) => $query->where('hostname', $hostname))
            ->get();

        if ($nodes->isNotEmpty()) {
            foreach ($nodes as $node) {
                $this->output->info(__('Exploring node :node', ['node' => $node->hostname]));
                $initial = $this->option('initial');
                ExplodeNodeJob::dispatchSync($node, $initial);
            }
            return self::SUCCESS;
        }

        $this->output->error(__('froxlor-core::cli.nodenotfound', ['node' => $hostname]));
        return self::FAILURE;
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array<string, string>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'hostname' => 'Specify hostname of the node to be explored',
        ];
    }
}
