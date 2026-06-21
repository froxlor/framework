<?php

namespace Froxlor\Core\Http\Requests;

use Froxlor\Core\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

class UpdatePlanRequest extends FormRequest
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
            'name' => 'sometimes|string',
            'type' => 'sometimes|string|in:tenant,environment',
            'description' => 'sometimes|nullable|string',
            'tenant_id' => 'prohibited',
        ];
    }

    /**
     * Prevent changing the quota scope of plans that already affect assignments.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Plan|null $plan */
            $plan = $this->route('plan');
            $type = $this->input('type');

            if (!$plan instanceof Plan || $type === null || $type === $plan->type) {
                return;
            }

            if ($plan->resources()->exists()) {
                $validator->errors()->add('type', 'The plan type cannot be changed while resources are assigned.');
            }

            if ($plan->environments()->exists()) {
                $validator->errors()->add('type', 'The plan type cannot be changed while environments use this plan.');
            }

            if (DB::table('tenants')->where('plan_id', $plan->id)->exists()) {
                $validator->errors()->add('type', 'The plan type cannot be changed while tenants use this plan.');
            }

            if (DB::table('tenant_user')->where('plan_id', $plan->id)->exists()) {
                $validator->errors()->add('type', 'The plan type cannot be changed while tenant users use this plan.');
            }

            if (DB::table('environment_user')->where('plan_id', $plan->id)->exists()) {
                $validator->errors()->add('type', 'The plan type cannot be changed while environment users use this plan.');
            }
        });
    }
}
