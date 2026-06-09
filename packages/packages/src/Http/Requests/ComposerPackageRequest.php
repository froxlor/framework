<?php

namespace Froxlor\Packages\Http\Requests;

use Froxlor\Packages\Rules\AllowedComposerPackage;
use Illuminate\Foundation\Http\FormRequest;

class ComposerPackageRequest extends FormRequest
{
    private static array $whitelistedPackages = [
        'froxlor/*',
    ];

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
            'package' => ['nullable', new AllowedComposerPackage(self::$whitelistedPackages)],
        ];
    }
}
