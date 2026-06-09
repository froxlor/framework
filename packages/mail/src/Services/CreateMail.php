<?php

namespace Froxlor\Mail\Services;

use Froxlor\Core\Events as CoreEvents;
use Froxlor\Core\Exceptions\InvalidResourceException;
use Froxlor\Core\Exceptions\ResourceLimitException;
use Froxlor\Core\Exceptions\ResourceNotFoundException;
use Froxlor\Core\Exceptions\UnknownEnvironmentUserException;
use Froxlor\Core\Exceptions\UnknownTenantUserException;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Support\Resource;
use Froxlor\Domain\Models\Domain;
use Froxlor\Mail\Http\Requests\StoreMailAddressRequest;
use Froxlor\Mail\Models\MailAddress;

class CreateMail
{
    /**
     * @throws UnknownTenantUserException
     * @throws UnknownEnvironmentUserException
     * @throws ResourceNotFoundException
     * @throws ResourceLimitException
     * @throws InvalidResourceException
     */
    public function execute(StoreMailAddressRequest $request, Domain $domain, Environment $environment): MailAddress
    {
        // get validated data only for ourselves
        $mailAddressData = $request->validatedResource();
        // set domain
        $mailAddressData['domain_id'] = $domain->id;
        // create resource
        $mailAddress = MailAddress::query()->create($mailAddressData);
        // build up validated data for others
        $eventData = $request->validatedEvent();
        // throw event that resource was created and append validated data
        event(new CoreEvents\Api\ResourceCreated($mailAddress, $eventData));
        // add resource usage
        Resource::addEnvironmentUsage($environment, $mailAddress);

        return $mailAddress->refresh();
    }
}
