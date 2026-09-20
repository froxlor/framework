<?php

namespace Froxlor\Core\Http\Requests\Tenant\Environment;

use Froxlor\Core\Http\Requests\Tenant\UpdateTenantUserRequest;

class UpdateEnvironmentUserRequest extends UpdateTenantUserRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * Environment user updates can change the user's profile data and the tenant /
     * environment scoped role and plan assignments in one request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'role' => ['missing'],
            'role_id' => ['missing'],
            'plan' => ['missing'],
            'plan_id' => ['missing'],
            'tenant_role' => ['sometimes', 'nullable', 'string', 'ulid', 'exists:roles,id'],
            'tenant_plan' => ['sometimes', 'nullable', 'string', 'ulid', 'exists:plans,id'],
            'environment_role' => ['sometimes', 'nullable', 'string', 'ulid', 'exists:roles,id'],
            'environment_plan' => ['sometimes', 'nullable', 'string', 'ulid', 'exists:plans,id'],
        ]);
    }
}
