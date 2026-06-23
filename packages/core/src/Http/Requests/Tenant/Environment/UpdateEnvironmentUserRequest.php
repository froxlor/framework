<?php

namespace Froxlor\Core\Http\Requests\Tenant\Environment;

use Froxlor\Core\Http\Requests\UpdateUserRequest;

class UpdateEnvironmentUserRequest extends UpdateUserRequest
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
            'tenant_role' => ['sometimes', 'string', 'ulid', 'exists:roles,id'],
            'tenant_plan' => ['sometimes', 'nullable', 'string', 'ulid', 'exists:plans,id'],
            'environment_role' => ['sometimes', 'string', 'ulid', 'exists:roles,id'],
            'environment_plan' => ['sometimes', 'nullable', 'string', 'ulid', 'exists:plans,id'],
        ]);
    }
}
