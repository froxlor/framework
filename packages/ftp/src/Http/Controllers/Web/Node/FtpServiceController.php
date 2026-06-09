<?php

namespace Froxlor\Ftp\Http\Controllers\Web\Node;

use Froxlor\Core\Models\Node;
use Froxlor\Ftp\Http\Controllers\Controller;
use Froxlor\Ftp\Jobs\FtpService\CheckFtpService;
use Froxlor\Ftp\Jobs\FtpService\ConfigureFtpService;
use Froxlor\Ftp\Jobs\FtpService\InstallFtpService;
use Froxlor\Ftp\Resources\Nodes\FtpServiceResource;
use Froxlor\UI\Support\UI;
use Illuminate\Http\RedirectResponse;

class FtpServiceController extends Controller
{
    public function show(Node $node)
    {
        if (! $node->ftpService) {
            return UI::render(FtpServiceResource::class, 'create', [$node]);
        }

        return UI::render(FtpServiceResource::class, 'show', [$node]);
    }

    public function create(Node $node)
    {
        return UI::render(FtpServiceResource::class, 'create', [$node]);
    }

    public function edit(Node $node)
    {
        if (! $node->ftpService) {
            return UI::render(FtpServiceResource::class, 'create', [$node]);
        }

        return UI::render(FtpServiceResource::class, 'edit', [$node]);
    }

    public function install(Node $node): RedirectResponse
    {
        abort_if(! $node->ftpService, 404);

        $node->ftpService->forceFill([
            'status' => 'install_queued',
            'last_error' => null,
        ])->save();

        dispatch(new InstallFtpService($node->ftpService->fresh()));

        return redirect()->route('resources.nodes.ftp-service.show', ['node' => $node]);
    }

    public function configure(Node $node): RedirectResponse
    {
        abort_if(! $node->ftpService, 404);

        $node->ftpService->forceFill([
            'status' => 'configure_queued',
            'last_error' => null,
        ])->save();

        dispatch(new ConfigureFtpService($node->ftpService->fresh()));

        return redirect()->route('resources.nodes.ftp-service.show', ['node' => $node]);
    }

    public function check(Node $node): RedirectResponse
    {
        abort_if(! $node->ftpService, 404);

        $node->ftpService->forceFill([
            'status' => 'check_queued',
            'last_error' => null,
        ])->save();

        dispatch(new CheckFtpService($node->ftpService->fresh()));

        return redirect()->route('resources.nodes.ftp-service.show', ['node' => $node]);
    }
}
