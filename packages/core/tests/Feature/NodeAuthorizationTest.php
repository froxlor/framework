<?php

namespace Tests\Feature;

use Froxlor\Core\Jobs\Node\ExploreNode;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Models\User;
use Froxlor\Core\Services\Node\Adapter\Local;
use Illuminate\Support\Facades\Queue;
use Tests\Fakes\FakeNodeAdapter;
use Tests\TestCase;

class NodeAuthorizationTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        require_once dirname(__DIR__) . '/Fakes/FakeNodeAdapter.php';

        if (!in_array(FakeNodeAdapter::class, Node::adapters(), true)) {
            Node::registerAdapter(FakeNodeAdapter::class);
        }
    }

    public function test_super_admin_can_list_nodes(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/nodes')
            ->assertOk();
    }

    public function test_tenant_admin_cannot_list_nodes(): void
    {
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/nodes')
            ->assertForbidden();
    }

    public function test_super_admin_can_manage_node(): void
    {
        Queue::fake();

        $user = User::query()->where('email', config('dev.email'))->firstOrFail();

        $nodeId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/nodes', [
                'adapter' => FakeNodeAdapter::class,
                'name' => 'Policy Test Node ' . str()->ulid(),
                'hostname' => 'node-policy-test.local',
                'username' => 'root',
                'sudo' => true,
            ])
            ->assertCreated()
            ->json('data.id');

        Queue::assertPushed(ExploreNode::class);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/nodes/' . $nodeId)
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/nodes/' . $nodeId, [
                'name' => 'Updated Policy Test Node',
                'hostname' => 'updated-node-policy-test.local',
                'username' => 'root',
                'sudo' => true,
            ])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/nodes/' . $nodeId)
            ->assertNoContent();
    }

    public function test_node_adapter_must_be_registered_adapter_fqcn(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/nodes', [
                'adapter' => 'not-a-class',
                'name' => 'Invalid Adapter Policy Test Node ' . str()->ulid(),
                'hostname' => 'invalid-adapter-node-policy-test.local',
                'username' => 'root',
                'sudo' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['adapter']);
    }

    public function test_node_ssh_key_is_stored_in_properties(): void
    {
        Queue::fake();

        $user = User::query()->where('email', config('dev.email'))->firstOrFail();

        $nodeId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/nodes', [
                'adapter' => FakeNodeAdapter::class,
                'name' => 'SSH Key Policy Test Node ' . str()->ulid(),
                'hostname' => 'ssh-key-node-policy-test.local',
                'username' => 'root',
                'ssh_key' => $this->plainPrivateKey(),
                'sudo' => true,
            ])
            ->assertCreated()
            ->json('data.id');

        $node = Node::query()->findOrFail($nodeId);

        $this->assertSame($this->plainPrivateKey(), $node->properties['ssh_key']);

        $node->update([
            'properties' => array_merge($node->properties, [
                'os' => ['pretty_name' => 'froxlor test linux'],
            ]),
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/nodes/' . $nodeId, [
                'name' => 'Updated SSH Key Policy Test Node',
                'hostname' => 'updated-ssh-key-node-policy-test.local',
                'username' => 'root',
                'ssh_key' => $this->encryptedPrivateKey(),
                'password' => 'correct-passphrase',
                'sudo' => true,
            ])
            ->assertOk();

        $node = Node::query()->findOrFail($nodeId);

        $this->assertSame($this->encryptedPrivateKey(), $node->properties['ssh_key']);
        $this->assertSame('froxlor test linux', $node->properties['os']['pretty_name']);
    }

    public function test_node_ssh_key_must_be_valid_and_match_password_when_encrypted(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/nodes', [
                'adapter' => FakeNodeAdapter::class,
                'name' => 'Invalid SSH Key Policy Test Node ' . str()->ulid(),
                'hostname' => 'invalid-ssh-key-node-policy-test.local',
                'username' => 'root',
                'ssh_key' => 'not-a-private-key',
                'sudo' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ssh_key']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/nodes', [
                'adapter' => FakeNodeAdapter::class,
                'name' => 'Encrypted SSH Key Policy Test Node ' . str()->ulid(),
                'hostname' => 'encrypted-ssh-key-node-policy-test.local',
                'username' => 'root',
                'ssh_key' => $this->encryptedPrivateKey(),
                'password' => 'wrong-passphrase',
                'sudo' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ssh_key']);
    }

    public function test_node_update_allows_partial_payload(): void
    {
        Queue::fake();

        $user = User::query()->where('email', config('dev.email'))->firstOrFail();

        $nodeId = $this->actingAs($user, 'sanctum')
            ->postJson('/api/nodes', [
                'adapter' => FakeNodeAdapter::class,
                'name' => 'Partial Update Policy Test Node ' . str()->ulid(),
                'hostname' => 'partial-update-node-policy-test.local',
                'username' => 'root',
                'sudo' => true,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/nodes/' . $nodeId, [
                'description' => 'Only the description changed',
            ])
            ->assertOk()
            ->assertJsonPath('data.description', 'Only the description changed');

        $node = Node::query()->findOrFail($nodeId);

        $this->assertSame('partial-update-node-policy-test.local', $node->hostname);
        $this->assertSame('root', $node->username);
    }

    public function test_second_local_node_is_rejected(): void
    {
        $user = User::query()->where('email', config('dev.email'))->firstOrFail();

        if (!Node::query()->where('adapter', Local::class)->exists()) {
            Node::query()->create([
                'adapter' => Local::class,
                'name' => 'Existing Local Policy Test Node ' . str()->ulid(),
                'hostname' => 'existing-local-node-policy-test.local',
                'username' => 'root',
                'sudo' => true,
            ]);
        }

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/nodes', [
                'adapter' => Local::class,
                'name' => 'Duplicate Local Policy Test Node ' . str()->ulid(),
                'hostname' => 'duplicate-local-node-policy-test.local',
                'username' => 'root',
                'sudo' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['adapter']);
    }

    public function test_tenant_admin_cannot_manage_node(): void
    {
        $user = User::query()->where('email', 'dev2@froxlor.org')->firstOrFail();
        $node = Node::query()->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/nodes/' . $node->id)
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/nodes', [
                'adapter' => FakeNodeAdapter::class,
                'name' => 'Forbidden Policy Test Node ' . str()->ulid(),
                'hostname' => 'forbidden-node-policy-test.local',
                'username' => 'root',
                'sudo' => true,
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/nodes/' . $node->id, [
                'name' => 'Forbidden Node Update',
                'hostname' => 'forbidden-node-update.local',
                'username' => 'root',
                'sudo' => true,
            ])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/nodes/' . $node->id)
            ->assertForbidden();
    }

    private function plainPrivateKey(): string
    {
        return <<<'KEY'
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtzc2gtZW
QyNTUxOQAAACCs2CxGcs4TQl/F1x81l6HTOD61NQhsNnmOm2YxSxfZUgAAAKgZlBamGZQW
pgAAAAtzc2gtZWQyNTUxOQAAACCs2CxGcs4TQl/F1x81l6HTOD61NQhsNnmOm2YxSxfZUg
AAAEA+Vs6mNNXXCJVZdBZuuCcgsotrxy4DPJ3uQlQVbhxXvKzYLEZyzhNCX8XXHzWXodM4
PrU1CGw2eY6bZjFLF9lSAAAAImQwMHBATWFjQm9vay1Qcm8tdm9uLU1pY2hhZWwubG9jYW
wBAgM=
-----END OPENSSH PRIVATE KEY-----
KEY;
    }

    private function encryptedPrivateKey(): string
    {
        return <<<'KEY'
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAACmFlczI1Ni1jdHIAAAAGYmNyeXB0AAAAGAAAABDfXbAQAV
fg3coZbBiX4tuAAAAAGAAAAAEAAAAzAAAAC3NzaC1lZDI1NTE5AAAAIB+FL2wxH7bnq4Es
g2Kvqv7AjHO6zFVMzHXwrGNz4oh2AAAAsJOj9rndjQHJyFfTcNy6G9iBkq9N93EOVwvn9K
d5bZIWG4nSyYiKElfIqDt90Pyvg6FExESLx4bBXOIVjEjqzU2MtWNeYxdv6fmTne+lJCgV
3eMAWS3hsi+v+GK41F0yjFd7kQ3a0tavrJtWwUo6yZuf6wEpZervhZrAcfhKYygd7JLgh4
+UcY+swRFXELSr1wXjWEBIKTZLg8OENE7/3cRI9JiL38YAwnoZswwRuE5S
-----END OPENSSH PRIVATE KEY-----
KEY;
    }
}
