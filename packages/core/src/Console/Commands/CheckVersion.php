<?php

namespace Froxlor\Core\Console\Commands;

use Froxlor\Core\Events\System\FroxlorUpdateFound;
use Froxlor\Core\Services\Support\VersionCheck;
use Froxlor\Core\Support\FroxlorVersion;
use Illuminate\Console\Command;

class CheckVersion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'core:check-version';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check current version from upstream and fire event in case an update was found';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->output->info(trans('Checking for froxlor updates. Current version is :version', ['version' => FroxlorVersion::release()]));
        $result = VersionCheck::checkVersion();
        if ($result['code'] == 1) {
            event(new FroxlorUpdateFound($result));
        }
        dd($result);
    }
}
