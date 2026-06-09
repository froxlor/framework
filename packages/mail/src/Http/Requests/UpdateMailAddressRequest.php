<?php

namespace Froxlor\Mail\Http\Requests;

use Froxlor\Core\Http\Requests\Abstract\FroxlorFormRequest;
use Froxlor\Mail\Models\MailAddress;

class UpdateMailAddressRequest extends FroxlorFormRequest
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
            'description' => 'nullable|string',
            'is_catchall' => 'sometimes|boolean',
            function ($attribute, $value, $fail) {
                if ($value) {
                    $exists = MailAddress::query()->where('domain_id', (int)$this->input('domain_id'))
                        ->where('is_catchall', true)
                        ->whereNot('id', $this->input('id')) // @todo is that right?
                        ->exists();

                    if ($exists) {
                        $fail('You have already defined a catchall address for this domain.');
                    }
                }
            },
            'rewrite_subject' => 'sometimes|boolean',
            'bypass_spam' => 'sometimes|boolean',
            'policy_greylist' => 'sometimes|boolean',
            'spam_tag_level' => 'sometimes|decimal:8,2',
            'spam_kill_level' => 'sometimes', 'decimal:8,2',
            function ($attribute, $value, $fail) {
                $tag = $this->input('spam_tag_level');
                if ($tag !== null && (float)$value <= (float)$tag) {
                    $fail('Spam kill level must be greater than the spam tag level.');
                }
            },
        ];
    }

    public function withEventRules(): array
    {
        return [MailAddress::class, 'update'];
    }
}
