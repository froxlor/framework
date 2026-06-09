<?php

namespace Froxlor\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEnvironmentRequest extends FormRequest
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
            'description' => 'sometimes|nullable|string',
            'plan_id' => 'sometimes|nullable|exists:plans,id',
            'node_id' => 'sometimes|nullable|exists:nodes,id',
        ];
    }
}
