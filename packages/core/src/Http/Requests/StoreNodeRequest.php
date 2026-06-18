<?php

namespace Froxlor\Core\Http\Requests;

use Froxlor\Core\Http\Requests\Abstract\FroxlorFormRequest;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Rules\SshPrivateKey;
use Froxlor\Core\Services\Node\Adapter\Adapter;
use Froxlor\Core\Services\Node\Adapter\Local;
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

                    if ($value === Local::class && Node::query()->where('adapter', Local::class)->exists()) {
                        $fail('Only one local node can exist.');
                    }
                },
                Rule::in(Node::adapters()),
            ],
            'name' => 'required|string',
            'hostname' => 'required|string',
            'username' => 'required|string',
            'password' => 'nullable|string',
            'ssh_key' => ['nullable', 'string', new SshPrivateKey($this->input('password'))],
            'sudo' => 'nullable|boolean',
            'description' => 'nullable|string',
            'tenant_id' => 'nullable|string|ulid|exists:tenants,id',
            'inheritable' => 'nullable|boolean',
        ];
    }

    public function withEventRules(): array
    {
        return [Node::class, 'store'];
    }
}
