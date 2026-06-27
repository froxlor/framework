<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Resource;
use Froxlor\Core\Models\User;
use Tests\TestCase;

class ResourceValidationTest extends TestCase
{
    public function test_super_admin_can_list_available_plan_resources(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();

        $resources = collect($this->actingAs($user, 'sanctum')
            ->getJson('/api/plans/resources')
            ->assertOk()
            ->json('data'));

        $this->assertNotNull($resources->firstWhere('key', 'users'));
    }

    public function test_tenant_admin_cannot_list_available_plan_resources(): void
    {
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/plans/resources')
            ->assertForbidden();
    }

    public function test_resources_are_not_crud_managed_over_api(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/resources', [
                'key' => 'forbidden-resource-crud-' . str()->ulid(),
                'name' => 'Forbidden Resource CRUD',
                'model_type' => Resource::class,
                'type' => 'tenant',
            ])
            ->assertNotFound();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/resources/' . Resource::query()->firstOrFail()->id, [
                'name' => 'Forbidden update',
            ])
            ->assertNotFound();
    }
}
