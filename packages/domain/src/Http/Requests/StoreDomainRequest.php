<?php

namespace Froxlor\Domain\Http\Requests;

use Froxlor\Core\Http\Requests\Abstract\FroxlorFormRequest;
use Froxlor\Domain\Models\Domain;

class StoreDomainRequest extends FroxlorFormRequest
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
            'domain' => 'required|string|unique:domains,domain',
            'properties' => 'sometimes|array',
            'parent_domain_id' => 'sometimes|exists:domains,id',
            'tenant_id' => 'required|exists:tenants,id',
            'environment_id' => 'sometimes|exists:environments,id',
            'node_id' => 'required_with:environment_id|exists:nodes,id',
        ];
    }

    public function withEventRules(): array
    {
        return [Domain::class, 'store'];
    }
}
