<?php

namespace Froxlor\Core\Http\Requests\Tenant;

use Froxlor\Core\Http\Requests\Abstract\FroxlorFormRequest;
use Froxlor\Core\Models\User;

class StoreTenantUserRequest extends FroxlorFormRequest
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
            'role_id' => 'required_without:role|string|ulid|exists:roles,id',
            'plan_id' => 'nullable|string|ulid|exists:plans,id',
            'role' => 'required_without:role_id|string|ulid|exists:roles,id',
            'plan' => 'nullable|string|ulid|exists:plans,id',
        ];
    }

    public function withEventRules(): array
    {
        return [User::class, 'tenantStore'];
    }
}
