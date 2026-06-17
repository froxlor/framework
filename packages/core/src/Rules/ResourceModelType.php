<?php

namespace Froxlor\Core\Rules;

use Closure;
use Froxlor\Core\Services\Traits\IsResource;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

class ResourceModelType implements ValidationRule
{
    /**
     * Validate that a resource model type points to a concrete froxlor resource model.
     *
     * Resource definitions store the owning model as FQCN because plans and usage checks
     * resolve the class later. Accepting arbitrary strings or unrelated model classes would
     * make those resource definitions unusable at runtime.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (
            !is_string($value)
            || !class_exists($value)
            || !is_a($value, Model::class, true)
            || !in_array(IsResource::class, class_uses_recursive($value), true)
        ) {
            $fail('The :attribute must be a valid froxlor resource model class.');
        }
    }
}
