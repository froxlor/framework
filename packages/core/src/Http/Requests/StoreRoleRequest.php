<?php

namespace Froxlor\Core\Http\Requests;

use Froxlor\Core\Http\Requests\Abstract\FroxlorFormRequest;
use Froxlor\Core\Models\Role;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FroxlorFormRequest
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
            'name' => [
                'required',
                'string',
                Rule::unique('roles', 'name')
                    ->where(fn($query) => $this->input('tenant_id') === null
                        ? $query->whereNull('tenant_id')
                        : $query->where('tenant_id', $this->input('tenant_id'))),
            ],
            'description' => 'string|nullable',
            'tenant_id' => 'nullable|string|ulid|exists:tenants,id',
        ];
    }

    public function withEventRules(): array
    {
        return [Role::class, 'store'];
    }
}
