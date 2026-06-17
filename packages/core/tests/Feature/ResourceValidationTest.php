<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\Resource;
use Froxlor\Core\Models\User;
use Tests\TestCase;

class ResourceValidationTest extends TestCase
{
    public function test_resource_model_type_must_be_an_existing_resource_model_class(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/resources', [
                'key' => 'invalid-resource-' . str()->ulid(),
                'name' => 'Invalid Resource',
                'model_type' => 'not-a-class',
                'type' => 'tenant',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['model_type']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/resources', [
                'key' => 'non-resource-model-' . str()->ulid(),
                'name' => 'Non Resource Model',
                'model_type' => Resource::class,
                'type' => 'tenant',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['model_type']);
    }

    public function test_resource_model_type_accepts_resource_model_classes(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $modelType = Node::class;

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/resources', [
                'key' => 'valid-resource-' . str()->ulid(),
                'name' => 'Valid Resource',
                'model_type' => $modelType,
                'type' => 'tenant',
            ])
            ->assertCreated()
            ->assertJsonPath('data.model_type', $modelType);
    }

    public function test_resource_model_type_is_validated_on_update(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();
        $resource = Resource::query()->create([
            'key' => 'updatable-resource-' . str()->ulid(),
            'name' => 'Updatable Resource',
            'model_type' => Node::class,
            'type' => 'tenant',
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/resources/' . $resource->id, [
                'model_type' => Resource::class,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['model_type']);
    }
}
