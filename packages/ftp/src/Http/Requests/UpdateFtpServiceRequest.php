<?php

namespace Froxlor\Ftp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFtpServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'driver' => 'sometimes|nullable|string|max:50',
            'listen_address' => 'sometimes|string|max:255',
            'port' => 'sometimes|integer|min:1|max:65535',
            'allow_local_users' => 'sometimes|boolean',
            'allow_write' => 'sometimes|boolean',
            'chroot_local_users' => 'sometimes|boolean',
            'allow_writable_chroot' => 'sometimes|boolean',
            'passive_min_port' => 'sometimes|integer|min:1|max:65535',
            'passive_max_port' => 'sometimes|integer|min:1|max:65535',
        ];
    }
}
