<?php

namespace Froxlor\Mail\Services;

use Froxlor\Core\Events as CoreEvents;
use Froxlor\Core\Exceptions\InvalidResourceException;
use Froxlor\Core\Exceptions\ResourceLimitException;
use Froxlor\Core\Exceptions\UnknownEnvironmentUserException;
use Froxlor\Core\Models\Environment;
use Froxlor\Core\Support\Resource;
use Froxlor\Mail\Http\Requests\StoreMailAccountRequest;
use Froxlor\Mail\Models\MailAccount;
use Froxlor\Mail\Models\MailAddress;

class CreateAccount
{
    /**
     * @throws UnknownEnvironmentUserException
     * @throws ResourceLimitException
     * @throws InvalidResourceException
     */
    public function execute(StoreMailAccountRequest $request, MailAddress $mailAddress, Environment $environment): MailAccount
    {
        // get validated data only for ourselves
        $mailAccountData = $request->validatedResource();
        // set address
        $mailAccountData['mail_address_id'] = $mailAddress->id;
        // create resource
        $mailAccount = MailAccount::query()->create($mailAccountData);
        // build up validated data for others
        $eventData = $request->validatedEvent();
        // throw event that resource was created and append validated data
        event(new CoreEvents\Api\ResourceCreated($mailAccount, $eventData));
        // add resource usage
        Resource::addEnvironmentUsage($environment, $mailAccount);

        return $mailAccount->refresh();
    }
}
