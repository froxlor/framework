<?php

namespace Froxlor\Core\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Scoped membership management must never change global login credentials. */
class UpdateTenantUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'required', 'string'],
            'last_name' => ['sometimes', 'required', 'string'],
            'company_name' => ['sometimes', 'nullable', 'string'],
            'email' => ['missing'],
            'password' => ['missing'],
            'tenant_id' => ['missing'],
            'role_id' => ['sometimes', 'nullable', 'string', 'ulid', 'exists:roles,id'],
            'role' => ['sometimes', 'nullable', 'string', 'ulid', 'exists:roles,id'],
            'plan_id' => ['sometimes', 'nullable', 'string', 'ulid', 'exists:plans,id'],
            'plan' => ['sometimes', 'nullable', 'string', 'ulid', 'exists:plans,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach (['role', 'plan'] as $alias) {
                if ($this->exists($alias) && $this->exists($alias.'_id')) {
                    $validator->errors()->add($alias, 'Use either the alias or its _id field, not both.');
                }
            }
        });
    }
}
