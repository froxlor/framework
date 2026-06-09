<?php

namespace Froxlor\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateResourceRequest extends FormRequest
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
            'key' => [
                'sometimes',
                'string',
                Rule::unique('resources', 'key')
                    ->where(fn($query) => $query
                        ->where('model_type', $this->input('model_type'))
                        ->where('type', $this->input('type')))
                    ->ignore($this->resource),
            ],
            'name' => 'sometimes|string',
            'description' => 'sometimes|nullable|string',
            'model_type' => 'sometimes|string',
            'type' => 'sometimes|string|in:tenant,environment',
        ];
    }
}
