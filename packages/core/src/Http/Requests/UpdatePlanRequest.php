<?php

namespace Froxlor\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanRequest extends FormRequest
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
            'name' => 'sometimes|string',
            'type' => 'sometimes|string|in:tenant,environment',
            'description' => 'sometimes|nullable|string',
            'tenant_id' => 'sometimes|nullable|string|ulid|exists:tenants,id',
        ];
    }
}
