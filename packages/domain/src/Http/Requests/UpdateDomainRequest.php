<?php

namespace Froxlor\Domain\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDomainRequest extends FormRequest
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
            'domain' => [
                'sometimes',
                'string',
                Rule::unique('domains', 'domain')->ignore($this->domain),
            ],
            'properties' => 'sometimes|array',
            'parent_domain_id' => 'sometimes|nullable|exists:domains,id',
            'tenant_id' => 'sometimes|exists:tenants,id',
            'environment_id' => 'sometimes|nullable|exists:environments,id',
            'node_id' => 'sometimes|nullable|exists:nodes,id',
        ];
    }
}
