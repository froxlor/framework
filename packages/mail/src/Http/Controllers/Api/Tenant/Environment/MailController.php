<?php

namespace Froxlor\Mail\Http\Controllers\Api\Tenant\Environment;

use Froxlor\Core\Events as CoreEvents;
use Froxlor\Core\Exceptions\InvalidResourceException;
use Froxlor\Core\Exceptions\ResourceLimitException;
use Froxlor\Core\Exceptions\ResourceNotFoundException;
use Froxlor\Core\Exceptions\UnknownEnvironmentUserException;
use Froxlor\Core\Exceptions\UnknownTenantUserException;
use Froxlor\Core\Http\Controllers\Controller as CoreController;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Models\Tenant;
use Froxlor\Core\Services\Traits\TenantAccessPermission;
use Froxlor\Core\Support\Resource;
use Froxlor\Core\Support\Response;
use Froxlor\Domain\Models\Domain;
use Froxlor\Mail\Http\Requests\StoreMailAddressRequest;
use Froxlor\Mail\Http\Requests\UpdateMailAddressRequest;
use Froxlor\Mail\Models\MailAddress;
use Froxlor\Mail\Services\CreateMail;
use Froxlor\Mail\Services\UpdateMail;
use Illuminate\Http\Request;

class MailController extends CoreController
{
    use TenantAccessPermission;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Tenant $tenant, Environment $environment, Domain $domain)
    {
        return Response::jsonResourceCollection($domain->mail_addresses());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws UnknownTenantUserException
     * @throws UnknownEnvironmentUserException
     * @throws ResourceNotFoundException
     * @throws ResourceLimitException
     * @throws InvalidResourceException
     */
    public function store(StoreMailAddressRequest $request, Tenant $tenant, Environment $environment, Domain $domain)
    {
        if ($environment->userHasResourceAvailable($request->user(), MailAddress::class)) {
            // return resource
            return Response::jsonResource(new CreateMail()->execute($request, $domain, $environment));
        }
        abort(403);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(UpdateMailAddressRequest $request, Tenant $tenant, Environment $environment, Domain $domain, MailAddress $mailAddress)
    {
        return Response::jsonResource(new UpdateMail()->execute($request, $mailAddress));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws InvalidResourceException
     */
    public function destroy(Request $request, Tenant $tenant, Environment $environment, Domain $domain, MailAddress $mailAddress)
    {
        Resource::removeEnvironmentUsage($environment, $mailAddress);

        $removedAddress = clone $mailAddress;
        $removedAccount = $mailAddress->mailAccount()->exists() ? clone $mailAddress->mailAccount : null;

        if (!is_null($removedAccount)) {
            Resource::removeEnvironmentUsage($environment, $mailAddress->mailAccount);
        }
        $mailAddress->delete();

        event(new CoreEvents\Api\ResourceDeleted($removedAddress, []));
        event(new CoreEvents\Api\ResourceDeleted($removedAccount, []));

        return response()->noContent();
    }
}
