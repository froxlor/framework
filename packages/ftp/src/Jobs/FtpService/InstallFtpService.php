<?php

namespace Froxlor\Ftp\Jobs\FtpService;

use Froxlor\Ftp\Models\FtpService;
use Froxlor\Ftp\Services\FtpServiceLifecycle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class InstallFtpService implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly FtpService $ftpService)
    {
    }

    public function handle(FtpServiceLifecycle $lifecycle): void
    {
        $lifecycle->install($this->ftpService->fresh());
    }
}
