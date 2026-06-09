<?php

namespace Froxlor\Mail\Http\Requests;

use Froxlor\Core\Http\Requests\Abstract\FroxlorFormRequest;
use Froxlor\Mail\Models\MailAccount;

class StoreMailAccountRequest extends FroxlorFormRequest
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
            'username' => 'required|string|unique:mail_accounts,username',
            'password' => 'required|string',
            'smtp_enabled' => 'sometimes|boolean',
            'pop3_enabled' => 'sometimes|boolean',
            'imap_enabled' => 'sometimes|boolean',
            'quota' => 'sometimes|integer',
        ];
    }

    public function withEventRules(): array
    {
        return [MailAccount::class, 'store'];
    }
}
