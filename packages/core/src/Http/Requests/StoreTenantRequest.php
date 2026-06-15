<?php

namespace Froxlor\Core\Http\Requests;

use Froxlor\Core\Http\Requests\Abstract\FroxlorFormRequest;
use Froxlor\Core\Models\Tenant;

class StoreTenantRequest extends FroxlorFormRequest
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
            'parent_tenant_id' => 'required|exists:tenants,id',
            'plan_id' => 'required|exists:plans,id',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'nodes' => 'nullable|array',
            'nodes.*.id' => 'required_with:nodes|string|ulid|exists:nodes,id',
            'nodes.*.inheritable' => 'nullable|boolean',
        ];
    }

    public function withEventRules(): array
    {
        return [Tenant::class, 'store'];
    }
}
