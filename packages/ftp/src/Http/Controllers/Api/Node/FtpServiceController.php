<?php

namespace Froxlor\Ftp\Http\Controllers\Api\Node;

use Froxlor\Core\Models\Node;
use Froxlor\Core\Support\Response;
use Froxlor\Ftp\Http\Controllers\Controller;
use Froxlor\Ftp\Http\Requests\StoreFtpServiceRequest;
use Froxlor\Ftp\Http\Requests\UpdateFtpServiceRequest;
use Froxlor\Ftp\Jobs\FtpService\CheckFtpService;
use Froxlor\Ftp\Jobs\FtpService\ConfigureFtpService;
use Froxlor\Ftp\Jobs\FtpService\InstallFtpService;
use Illuminate\Http\Request;

class FtpServiceController extends Controller
{
    public function show(Request $request, Node $node)
    {
        abort_if(! $node->ftpService, 404);

        return Response::jsonResource($node->ftpService);
    }

    public function store(StoreFtpServiceRequest $request, Node $node)
    {
        abort_if($node->ftpService()->exists(), 422, 'FTP service already configured for this node.');

        $ftpService = $node->ftpService()->create($this->normalizedData($request->validated()));

        return Response::jsonResource($ftpService);
    }

    public function update(UpdateFtpServiceRequest $request, Node $node)
    {
        $ftpService = $this->ftpService($node);
        $ftpService->update($this->normalizedData($request->validated(), false));

        return Response::jsonResource($ftpService->fresh());
    }

    public function install(Request $request, Node $node)
    {
        $ftpService = $this->ftpService($node);
        $ftpService->forceFill([
            'status' => 'install_queued',
            'last_error' => null,
        ])->save();

        dispatch(new InstallFtpService($ftpService->fresh()));

        return Response::jsonResource($ftpService->fresh());
    }

    public function configure(Request $request, Node $node)
    {
        $ftpService = $this->ftpService($node);
        $ftpService->forceFill([
            'status' => 'configure_queued',
            'last_error' => null,
        ])->save();

        dispatch(new ConfigureFtpService($ftpService->fresh()));

        return Response::jsonResource($ftpService->fresh());
    }

    public function check(Request $request, Node $node)
    {
        $ftpService = $this->ftpService($node);
        $ftpService->forceFill([
            'status' => 'check_queued',
            'last_error' => null,
        ])->save();

        dispatch(new CheckFtpService($ftpService->fresh()));

        return Response::jsonResource($ftpService->fresh());
    }

    public function destroy(Request $request, Node $node)
    {
        $ftpService = $this->ftpService($node);
        $ftpService->delete();

        return response()->noContent();
    }

    private function normalizedData(array $data, bool $withDefaults = true): array
    {
        if ($withDefaults) {
            $data['driver'] = $data['driver'] ?? 'vsftpd';
            $data['status'] = $data['status'] ?? 'defined';
            $data['allow_local_users'] = $data['allow_local_users'] ?? true;
            $data['allow_write'] = $data['allow_write'] ?? true;
            $data['chroot_local_users'] = $data['chroot_local_users'] ?? true;
            $data['allow_writable_chroot'] = $data['allow_writable_chroot'] ?? true;
        }

        return $data;
    }

    private function ftpService(Node $node)
    {
        abort_if(! $node->ftpService, 404);

        return $node->ftpService;
    }
}
