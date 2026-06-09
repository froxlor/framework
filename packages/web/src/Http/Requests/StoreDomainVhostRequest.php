<?php

namespace Froxlor\Web\Http\Requests;

use Froxlor\Core\Http\Requests\Abstract\FroxlorFormRequest;
use Froxlor\Core\Support\Setting;
use Froxlor\Web\Enums\HstsMode;
use Froxlor\Web\Enums\SslMode;
use Froxlor\Web\Models\DomainVhost;
use Froxlor\Web\Services\SslService;
use Illuminate\Validation\Rule;

class StoreDomainVhostRequest extends FroxlorFormRequest
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
        $rules = [
            'is_http_domain' => 'required|boolean',
            'vhost' => 'required_if:is_http_domain,true|array',
            'vhost.documentroot' => 'required_with:vhost|string',
            'vhost.access_log' => 'sometimes|bool',
            'vhost.error_log' => 'sometimes|bool',
            'vhost.alias_mode' => ['sometimes', Rule::in(['none', 'www', 'wildcard'])],
            'vhost.notryfiles' => 'sometimes|bool',
            'vhost.custom_vhost' => 'sometimes|string',
        ];
        if (Setting::get('web.ssl_enabled')) {
            $rules = array_merge($rules, [
                'is_https_domain' => 'required_if:is_http_domain,true|boolean',
                'ssl_vhost' => 'exclude_unless:is_http_domain,true|required_if:is_https_domain,true|array',
                'ssl_vhost.ssl_redirect' => 'nullable|bool',
                'ssl_vhost.ssl_mode' => ['nullable', Rule::enum(SslMode::class)],
                'ssl_vhost.http2' => 'nullable|bool',
                'ssl_vhost.http3' => 'nullable|bool',
                'ssl_vhost.hsts_enabled' => 'nullable|boolean',
                'ssl_vhost.hsts_mode' => ['exclude_unless:hsts_enabled,true', 'nullable', Rule::enum(HstsMode::class)],
                'ssl_vhost.hsts_maxage' => 'exclude_unless:hsts_enabled,true|nullable|numeric|min:0',
                'ssl_vhost.oscp_stapling' => 'nullable|boolean',
                'ssl_vhost.override_tls' => 'nullable|boolean',
                'ssl_vhost.ssl_protocols' => ['exclude_unless:override_tls,true', 'nullable', 'array',
                    function ($attribute, $value, $fail) {
                        foreach ($value as $item) {
                            if (!in_array($item, SslService::SSL_PROTOCOLS_AVAILABLE)) {
                                $fail("The $attribute contains an invalid value: $item. Must be one or more of " . implode(", ", SslService::SSL_PROTOCOLS_AVAILABLE));
                            }
                        }
                    }],
            ]);
        }
        return $rules;
    }

    public function withEventRules(): array
    {
        return [DomainVhost::class, 'store'];
    }
}
