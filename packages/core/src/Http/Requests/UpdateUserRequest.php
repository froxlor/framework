<?php

namespace Froxlor\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
            'first_name' => ['sometimes', 'required'],
            'last_name' => ['sometimes', 'required'],
            'company_name' => ['sometimes', 'nullable'],
            'email' => ['sometimes', 'required', 'email'],
            'password' => ['sometimes', 'nullable', 'min:8'],
            'tenant_id' => ['sometimes', 'nullable', 'string', 'exists:tenants,id', 'required_with:role_id,plan_id'],
            'role_id' => ['sometimes', 'string', 'exists:roles,id', 'required_with:tenant_id'],
            'plan_id' => ['sometimes', 'nullable', 'string', 'exists:plans,id'],
            'role' => ['sometimes', 'string', 'exists:roles,id', 'required_with:tenant_id'],
            'plan' => ['sometimes', 'nullable', 'string', 'exists:plans,id'],
        ];
    }

    /**
     * Get the validated data from the request.
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        if ($key === null && isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }
        if ($key === 'password' && $this->input('password')) {
            return bcrypt($this->input('password'));
        }
        return $data;
    }
}
