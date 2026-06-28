<?php

namespace Froxlor\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallCore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'froxlor:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install froxlor and setup the initial configuration';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->output->info(trans('Starting froxlor initialisation...'));

        $this->output->block(trans('Migrating fresh database...'));

        Artisan::call('migrate:fresh --seed');

        $this->output->success(trans('Initialisation complete.'));
    }
}
