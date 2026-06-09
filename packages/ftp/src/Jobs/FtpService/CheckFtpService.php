<?php

namespace Froxlor\Ftp\Jobs\FtpService;

use Froxlor\Ftp\Models\FtpService;
use Froxlor\Ftp\Services\FtpServiceLifecycle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckFtpService implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly FtpService $ftpService)
    {
    }

    public function handle(FtpServiceLifecycle $lifecycle): void
    {
        $lifecycle->check($this->ftpService->fresh());
    }
}
