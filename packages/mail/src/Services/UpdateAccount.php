<?php

namespace Froxlor\Mail\Services;

use Froxlor\Core\Events as CoreEvents;
use Froxlor\Mail\Http\Requests\UpdateMailAccountRequest;
use Froxlor\Mail\Models\MailAccount;

class UpdateAccount
{
    /**
     */
    public function execute(UpdateMailAccountRequest $request, MailAccount $mailAccount): MailAccount
    {
        // get validated data only for ourselves
        $mailAccountData = $request->validatedResource();
        // build up validated data for others
        $eventData = $request->validatedEvent();
        // update resource
        $mailAccount->update($mailAccountData);
        // throw event that resource was updated
        event(new CoreEvents\Api\ResourceUpdated($mailAccount, $eventData));

        return $mailAccount->refresh();
    }
}
