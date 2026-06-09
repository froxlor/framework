<?php

namespace Froxlor\Core\Http\Requests\Tenant\Environment;

use Froxlor\Core\Http\Requests\Abstract\FroxlorFormRequest;
use Froxlor\Core\Models\Plan;
use Illuminate\Validation\Rule;

class StoreEnvironmentPlanRequest extends FroxlorFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'description' => 'string|nullable',
        ];
    }

    public function withEventRules(): array
    {
        return [Plan::class, 'environmentStore'];
    }
}
