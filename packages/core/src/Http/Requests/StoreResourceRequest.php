<?php

namespace Froxlor\Core\Http\Requests;

use Froxlor\Core\Rules\ResourceModelType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResourceRequest extends FormRequest
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
                'required',
                'string',
                Rule::unique('resources', 'key')
                    ->where(fn($query) => $query
                        ->where('model_type', $this->input('model_type'))
                        ->where('type', $this->input('type'))),
            ],
            'name' => 'required|string',
            'description' => 'nullable|string',
            'model_type' => ['required', 'string', new ResourceModelType()],
            'type' => 'required|string|in:tenant,environment',
        ];
    }
}
