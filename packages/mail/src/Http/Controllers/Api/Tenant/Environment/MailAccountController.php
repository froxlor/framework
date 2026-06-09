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
use Froxlor\Mail\Http\Requests\StoreMailAccountRequest;
use Froxlor\Mail\Http\Requests\UpdateMailAccountRequest;
use Froxlor\Mail\Models\MailAccount;
use Froxlor\Mail\Models\MailAddress;
use Froxlor\Mail\Services\CreateAccount;
use Froxlor\Mail\Services\UpdateAccount;
use Illuminate\Http\Request;

class MailAccountController extends CoreController
{
    use TenantAccessPermission;

    /**
     * Store a newly created resource in storage.
     *
     * @throws UnknownTenantUserException
     * @throws UnknownEnvironmentUserException
     * @throws ResourceNotFoundException
     * @throws ResourceLimitException
     * @throws InvalidResourceException
     */
    public function store(StoreMailAccountRequest $request, Tenant $tenant, Environment $environment, Domain $domain, MailAddress $mailAddress)
    {
        if ($environment->userHasResourceAvailable($request->user(), MailAccount::class)) {
            // return resource
            return Response::jsonResource(new CreateAccount()->execute($request, $mailAddress, $environment));
        }
        abort(403);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMailAccountRequest $request, Tenant $tenant, Environment $environment, Domain $domain, MailAddress $mailAddress, MailAccount $mailAccount)
    {
        return Response::jsonResource(new UpdateAccount()->execute($request, $mailAccount));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws InvalidResourceException
     */
    public function destroy(Request $request, Tenant $tenant, Environment $environment, Domain $domain, MailAddress $mailAddress, MailAccount $mailAccount)
    {
        Resource::removeEnvironmentUsage($environment, $mailAccount);

        $removedAccount = clone $mailAccount;
        $mailAccount->delete();

        event(new CoreEvents\Api\ResourceDeleted($removedAccount, []));
        return response()->noContent();
    }
}
