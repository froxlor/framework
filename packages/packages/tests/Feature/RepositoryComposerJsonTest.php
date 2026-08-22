<?php

namespace Tests\Feature;

use Froxlor\Core\Models\Tenant;
use Froxlor\Packages\Services\PackageService;
use Tests\TestCase;

class RepositoryComposerJsonTest extends TestCase
{
    private const TEST_REPOSITORY = 'tests-fixture-repository';

    private const TEST_SLASHED_REPOSITORY = 'tests/fixture-repository';

    protected function tearDown(): void
    {
        app(PackageService::class)->removeRepository(self::TEST_REPOSITORY);
        app(PackageService::class)->removeRepository(self::TEST_SLASHED_REPOSITORY);

        parent::tearDown();
    }

    public function test_adding_a_repository_persists_it_to_composer_json(): void
    {
        $packageService = app(PackageService::class);

        $packageService->addRepository(self::TEST_REPOSITORY, 'composer', 'https://example.test/packages');

        $repository = $packageService->findRepository(self::TEST_REPOSITORY);

        $this->assertNotNull($repository);
        $this->assertSame('composer', $repository['type']);
        $this->assertSame('https://example.test/packages', $repository['url']);
    }

    public function test_removing_a_repository_drops_it_from_composer_json(): void
    {
        $packageService = app(PackageService::class);

        $packageService->addRepository(self::TEST_REPOSITORY, 'composer', 'https://example.test/packages');
        $packageService->removeRepository(self::TEST_REPOSITORY);

        $this->assertNull($packageService->findRepository(self::TEST_REPOSITORY));
    }

    public function test_repositories_expose_a_url_safe_id(): void
    {
        $packageService = app(PackageService::class);

        $packageService->addRepository(self::TEST_SLASHED_REPOSITORY, 'path', '/tmp/tests-fixture');

        $repository = $packageService->findRepository(self::TEST_SLASHED_REPOSITORY);

        $this->assertNotNull($repository);
        $this->assertSame('tests:fixture-repository', $repository['id']);
        $this->assertStringNotContainsString('/', $repository['id']);
    }

    public function test_repository_with_slash_in_name_is_addressable_via_api_by_id(): void
    {
        $packageService = app(PackageService::class);
        $packageService->addRepository(self::TEST_SLASHED_REPOSITORY, 'path', '/tmp/tests-fixture');

        $user = Tenant::query()->root()->firstOrFail()->users()->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/repositories/tests:fixture-repository')
            ->assertOk()
            ->assertJsonFragment(['name' => self::TEST_SLASHED_REPOSITORY]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/repositories/tests:fixture-repository')
            ->assertNoContent();

        $this->assertNull($packageService->findRepository(self::TEST_SLASHED_REPOSITORY));
    }

    public function test_default_froxlor_repository_is_protected_and_verified(): void
    {
        $repository = app(PackageService::class)->findRepository('froxlor');

        $this->assertNotNull($repository);
        $this->assertTrue($repository['protected']);
        $this->assertTrue($repository['verified']);
    }
}
