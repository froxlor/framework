<?php

namespace Froxlor\Ftp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFtpServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'driver' => 'nullable|string|max:50',
            'listen_address' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'allow_local_users' => 'nullable|boolean',
            'allow_write' => 'nullable|boolean',
            'chroot_local_users' => 'nullable|boolean',
            'allow_writable_chroot' => 'nullable|boolean',
            'passive_min_port' => 'required|integer|min:1|max:65535',
            'passive_max_port' => 'required|integer|min:1|max:65535',
        ];
    }
}
