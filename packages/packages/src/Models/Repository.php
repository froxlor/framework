<?php

namespace Froxlor\Packages\Models;

use Froxlor\Packages\Observer\RepositoryObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $type
 * @property string $url
 * @property array $options
 * @property array $auth
 * @property bool $enabled
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 */
#[ObservedBy([RepositoryObserver::class])]
class Repository extends Model
{
    use HasUlids;

    protected $attributes = [
        'enabled' => false,
    ];

    protected $guarded = [];

    protected $hidden = [
        'auth',
    ];

    protected $appends = [
        'protected',
        'verified',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'auth' => 'encrypted:array',
        ];
    }

    protected function protected(): Attribute
    {
        return Attribute::get(fn() => $this->url == 'https://packages.froxlor.org');
    }

    protected function verified(): Attribute
    {
        return Attribute::get(fn() => in_array($this->url, [
            'https://packages.froxlor.org',
            'https://packages.froxlor.com',
            'https://packages.froxlor.dev',
        ]));
    }
}
