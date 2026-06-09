<?php

namespace Froxlor\Core\Http\Requests;

use Froxlor\Core\Http\Requests\Abstract\FroxlorFormRequest;
use Froxlor\Core\Models\User;

class StoreUserRequest extends FroxlorFormRequest
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
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'company_name' => 'nullable',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string',
            'tenant_id' => 'nullable|string|exists:tenants,id',
            'role_id' => 'required_without:role|string|exists:roles,id',
            'plan_id' => 'nullable|string|exists:plans,id',
            'role' => 'required_without:role_id|string|exists:roles,id',
            'plan' => 'nullable|string|exists:plans,id',
        ];
    }

    public function withEventRules(): array
    {
        return [User::class, 'store'];
    }
}
