<?php

namespace Froxlor\Core\Http\Requests;

use Froxlor\Core\Http\Requests\Abstract\FroxlorFormRequest;
use Froxlor\Core\Models\Node;

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
