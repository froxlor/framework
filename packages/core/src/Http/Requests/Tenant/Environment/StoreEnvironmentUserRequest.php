<?php

namespace Froxlor\Core\Http\Requests\Tenant\Environment;

use Froxlor\Core\Http\Requests\Abstract\FroxlorFormRequest;
use Froxlor\Core\Models\User;

class StoreEnvironmentUserRequest extends FroxlorFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'company_name' => 'nullable',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string',
            'tenant_role' => 'required|string|exists:roles,id',
            'tenant_plan' => 'nullable|string|exists:plans,id',
            'environment_role' => 'required|string|exists:roles,id',
            'environment_plan' => 'nullable|string|exists:plans,id',
        ];
    }

    public function withEventRules(): array
    {
        return [User::class, 'environmentStore'];
    }
}
