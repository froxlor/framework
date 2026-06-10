<?php

namespace Froxlor\Core\Http\Requests;

use Froxlor\Core\Http\Requests\Abstract\FroxlorFormRequest;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Services\Node\Adapter\Adapter;
use Illuminate\Validation\Rule;

class StoreNodeRequest extends FroxlorFormRequest
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
            'adapter' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (!is_string($value) || !class_exists($value) || !is_a($value, Adapter::class, true)) {
                        $fail('The selected ' . $attribute . ' must be a valid node adapter class.');
                    }
                },
                Rule::in(Node::adapters()),
            ],
            'name' => 'required|string',
            'hostname' => 'required|string',
            'username' => 'required|string',
            'password' => 'nullable|string',
            'sshkey' => 'nullable|string',
            'sudo' => 'nullable|boolean',
            'description' => 'nullable|string',
        ];
    }

    public function withEventRules(): array
    {
        return [Node::class, 'store'];
    }
}
