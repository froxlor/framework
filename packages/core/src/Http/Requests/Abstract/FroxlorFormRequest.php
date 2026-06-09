<?php

namespace Froxlor\Core\Http\Requests\Abstract;

use Exception;
use Froxlor\Core\Events\Api\ResourceValidating;
use Illuminate\Foundation\Http\FormRequest;

abstract class FroxlorFormRequest extends FormRequest
{
    private array $mainRules = [];
    private array $eventRules = [];

    public function validatedResource(): array
    {
        $data = $this->validated();

        return collect($data)->only(array_keys($this->mainRules))->toArray();
    }

    public function validatedEvent(): array
    {
        $data = $this->validated();

        return collect($data)->except(array_keys($this->mainRules))->toArray();
    }

    /**
     * @throws Exception
     */
    protected function validationRules(): array
    {
        $this->mainRules = parent::validationRules();
        // extend rules by event rules
        $eventRuleData = $this->withEventRules();
        if (count($eventRuleData) != 2) {
            throw new Exception('Return value of FroxlorFormRequest::withEventRules() needs to be an array with exactly two values.');
        }
        [$class, $action] = $eventRuleData;
        if ($class && $action) {
            // extend validation rules if required
            event(new ResourceValidating($class, $action, $this->eventRules));
        }
        return array_merge($this->mainRules, $this->eventRules);
    }

    public function rulesWithEventRules(): array
    {
        return $this->validationRules();
    }

    /**
     * define the class and action the request-event is for, e.g. for "StoreNodeRequest" a return would be [Node::class, 'store']
     *
     * @return null[]
     */
    public function withEventRules(): array
    {
        return [null, null];
    }
}
