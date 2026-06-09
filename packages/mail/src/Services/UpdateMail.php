<?php

namespace Froxlor\Mail\Services;

use Froxlor\Core\Events as CoreEvents;
use Froxlor\Core\Models\Environment;
use Froxlor\Domain\Models\Domain;
use Froxlor\Mail\Http\Requests\UpdateMailAddressRequest;
use Froxlor\Mail\Models\MailAddress;

class UpdateMail
{
    /**
     */
    public function execute(UpdateMailAddressRequest $request, MailAddress $mailAddress): MailAddress
    {
        $mailAddressData = $request->validatedResource();
        // update resource
        $mailAddress->update($mailAddressData);
        // build up validated data for others
        $eventData = $request->validatedEvent();
        // throw event that resource was updated
        event(new CoreEvents\Api\ResourceUpdated($mailAddress, $eventData));

        return $mailAddress->refresh();
    }
}
