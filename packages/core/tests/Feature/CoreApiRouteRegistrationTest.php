<?php

namespace Tests\Feature;

use Froxlor\Core\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CoreApiRouteRegistrationTest extends TestCase
{
    /**
     * Ensure the core API surface is registered with stable route names, URIs,
     * HTTP verbs, and controller actions.
     *
     */
    #[DataProvider('coreApiRoutes')]
    public function test_core_api_route_is_registered(string $name, string $method, string $uri, string $controller, string $action): void
    {
        $route = Route::getRoutes()->getByName($name);

        $this->assertNotNull($route, 'Route [' . $name . '] is not registered.');
        $this->assertSame($uri, $route->uri(), 'Route [' . $name . '] URI changed.');
        $this->assertContains($method, $route->methods(), 'Route [' . $name . '] method changed.');
        $this->assertSame($controller . '@' . $action, $route->getActionName(), 'Route [' . $name . '] action changed.');
    }

    /**
     * Current core API route contract.
     *
     * @return array<string, array{string, string, string, class-string, string}>
     */
    public static function coreApiRoutes(): array
    {
        return [
            'audit-log.index' => ['api.audit-log.index', 'GET', 'api/audit-log', Api\AuditLogController::class, 'index'],

            'nodes.index' => ['api.nodes.index', 'GET', 'api/nodes', Api\NodeController::class, 'index'],
            'nodes.store' => ['api.nodes.store', 'POST', 'api/nodes', Api\NodeController::class, 'store'],
            'nodes.show' => ['api.nodes.show', 'GET', 'api/nodes/{node}', Api\NodeController::class, 'show'],
            'nodes.update' => ['api.nodes.update', 'PUT', 'api/nodes/{node}', Api\NodeController::class, 'update'],
            'nodes.destroy' => ['api.nodes.destroy', 'DELETE', 'api/nodes/{node}', Api\NodeController::class, 'destroy'],

            'users.index' => ['api.users.index', 'GET', 'api/users', Api\UserController::class, 'index'],
            'users.store' => ['api.users.store', 'POST', 'api/users', Api\UserController::class, 'store'],
            'users.show' => ['api.users.show', 'GET', 'api/users/{user}', Api\UserController::class, 'show'],
            'users.update' => ['api.users.update', 'PUT', 'api/users/{user}', Api\UserController::class, 'update'],
            'users.destroy' => ['api.users.destroy', 'DELETE', 'api/users/{user}', Api\UserController::class, 'destroy'],

            'api-keys.index' => ['api.api-keys.index', 'GET', 'api/api-keys', Api\ApiKeyController::class, 'index'],
            'api-keys.store' => ['api.api-keys.store', 'POST', 'api/api-keys', Api\ApiKeyController::class, 'store'],
            'api-keys.show' => ['api.api-keys.show', 'GET', 'api/api-keys/{api_key}', Api\ApiKeyController::class, 'show'],
            'api-keys.destroy' => ['api.api-keys.destroy', 'DELETE', 'api/api-keys/{api_key}', Api\ApiKeyController::class, 'destroy'],

            'settings.index' => ['api.settings.index', 'GET', 'api/settings/{resource?}/{resource_id?}', Api\SettingsController::class, 'index'],
            'settings.store' => ['api.settings.store', 'POST', 'api/settings/{resource?}/{resource_id?}', Api\SettingsController::class, 'store'],

            'tenants.index' => ['api.tenants.index', 'GET', 'api/tenants', Api\TenantController::class, 'index'],
            'tenants.store' => ['api.tenants.store', 'POST', 'api/tenants', Api\TenantController::class, 'store'],
            'tenants.show' => ['api.tenants.show', 'GET', 'api/tenants/{tenant}', Api\TenantController::class, 'show'],
            'tenants.update' => ['api.tenants.update', 'PUT', 'api/tenants/{tenant}', Api\TenantController::class, 'update'],
            'tenants.destroy' => ['api.tenants.destroy', 'DELETE', 'api/tenants/{tenant}', Api\TenantController::class, 'destroy'],

            'tenants.audit-log.index' => ['api.tenants.audit-log.index', 'GET', 'api/tenants/{tenant}/audit-log', Api\Tenant\AuditLogController::class, 'index'],

            'tenants.nodes.index' => ['api.tenants.nodes.index', 'GET', 'api/tenants/{tenant}/nodes', Api\Tenant\NodeController::class, 'index'],
            'tenants.nodes.store' => ['api.tenants.nodes.store', 'POST', 'api/tenants/{tenant}/nodes', Api\Tenant\NodeController::class, 'store'],
            'tenants.nodes.show' => ['api.tenants.nodes.show', 'GET', 'api/tenants/{tenant}/nodes/{node}', Api\Tenant\NodeController::class, 'show'],
            'tenants.nodes.update' => ['api.tenants.nodes.update', 'PUT', 'api/tenants/{tenant}/nodes/{node}', Api\Tenant\NodeController::class, 'update'],
            'tenants.nodes.destroy' => ['api.tenants.nodes.destroy', 'DELETE', 'api/tenants/{tenant}/nodes/{node}', Api\Tenant\NodeController::class, 'destroy'],

            'tenants.environments.index' => ['api.tenants.environments.index', 'GET', 'api/tenants/{tenant}/environments', Api\Tenant\EnvironmentController::class, 'index'],
            'tenants.environments.store' => ['api.tenants.environments.store', 'POST', 'api/tenants/{tenant}/environments', Api\Tenant\EnvironmentController::class, 'store'],
            'tenants.environments.show' => ['api.tenants.environments.show', 'GET', 'api/tenants/{tenant}/environments/{environment}', Api\Tenant\EnvironmentController::class, 'show'],
            'tenants.environments.update' => ['api.tenants.environments.update', 'PUT', 'api/tenants/{tenant}/environments/{environment}', Api\Tenant\EnvironmentController::class, 'update'],
            'tenants.environments.destroy' => ['api.tenants.environments.destroy', 'DELETE', 'api/tenants/{tenant}/environments/{environment}', Api\Tenant\EnvironmentController::class, 'destroy'],

            'tenants.environments.audit-log.index' => ['api.tenants.environments.audit-log.index', 'GET', 'api/tenants/{tenant}/environments/{environment}/audit-log', Api\Tenant\Environment\AuditLogController::class, 'index'],

            'tenants.environments.users.index' => ['api.tenants.environments.users.index', 'GET', 'api/tenants/{tenant}/environments/{environment}/users', Api\Tenant\Environment\UserController::class, 'index'],
            'tenants.environments.users.store' => ['api.tenants.environments.users.store', 'POST', 'api/tenants/{tenant}/environments/{environment}/users', Api\Tenant\Environment\UserController::class, 'store'],
            'tenants.environments.users.show' => ['api.tenants.environments.users.show', 'GET', 'api/tenants/{tenant}/environments/{environment}/users/{user}', Api\Tenant\Environment\UserController::class, 'show'],
            'tenants.environments.users.update' => ['api.tenants.environments.users.update', 'PUT', 'api/tenants/{tenant}/environments/{environment}/users/{user}', Api\Tenant\Environment\UserController::class, 'update'],
            'tenants.environments.users.destroy' => ['api.tenants.environments.users.destroy', 'DELETE', 'api/tenants/{tenant}/environments/{environment}/users/{user}', Api\Tenant\Environment\UserController::class, 'destroy'],

            'tenants.users.index' => ['api.tenants.users.index', 'GET', 'api/tenants/{tenant}/users', Api\Tenant\UserController::class, 'index'],
            'tenants.users.store' => ['api.tenants.users.store', 'POST', 'api/tenants/{tenant}/users', Api\Tenant\UserController::class, 'store'],
            'tenants.users.show' => ['api.tenants.users.show', 'GET', 'api/tenants/{tenant}/users/{user}', Api\Tenant\UserController::class, 'show'],
            'tenants.users.update' => ['api.tenants.users.update', 'PUT', 'api/tenants/{tenant}/users/{user}', Api\Tenant\UserController::class, 'update'],
            'tenants.users.destroy' => ['api.tenants.users.destroy', 'DELETE', 'api/tenants/{tenant}/users/{user}', Api\Tenant\UserController::class, 'destroy'],

            'tenants.plans.index' => ['api.tenants.plans.index', 'GET', 'api/tenants/{tenant}/plans', Api\Tenant\PlanController::class, 'index'],
            'tenants.plans.store' => ['api.tenants.plans.store', 'POST', 'api/tenants/{tenant}/plans', Api\Tenant\PlanController::class, 'store'],
            'tenants.plans.show' => ['api.tenants.plans.show', 'GET', 'api/tenants/{tenant}/plans/{plan}', Api\Tenant\PlanController::class, 'show'],
            'tenants.plans.update' => ['api.tenants.plans.update', 'PUT', 'api/tenants/{tenant}/plans/{plan}', Api\Tenant\PlanController::class, 'update'],
            'tenants.plans.destroy' => ['api.tenants.plans.destroy', 'DELETE', 'api/tenants/{tenant}/plans/{plan}', Api\Tenant\PlanController::class, 'destroy'],
            'tenants.plans.resources.index' => ['api.tenants.plans.resources.index', 'GET', 'api/tenants/{tenant}/plans/{plan}/resources', Api\Tenant\Plan\PlanResourceController::class, 'index'],
            'tenants.plans.resources.store' => ['api.tenants.plans.resources.store', 'POST', 'api/tenants/{tenant}/plans/{plan}/resources', Api\Tenant\Plan\PlanResourceController::class, 'store'],
            'tenants.plans.resources.destroy' => ['api.tenants.plans.resources.destroy', 'DELETE', 'api/tenants/{tenant}/plans/{plan}/resources/{resource}', Api\Tenant\Plan\PlanResourceController::class, 'destroy'],

            'tenants.roles.index' => ['api.tenants.roles.index', 'GET', 'api/tenants/{tenant}/roles', Api\Tenant\RoleController::class, 'index'],
            'tenants.roles.store' => ['api.tenants.roles.store', 'POST', 'api/tenants/{tenant}/roles', Api\Tenant\RoleController::class, 'store'],
            'tenants.roles.show' => ['api.tenants.roles.show', 'GET', 'api/tenants/{tenant}/roles/{role}', Api\Tenant\RoleController::class, 'show'],
            'tenants.roles.update' => ['api.tenants.roles.update', 'PUT', 'api/tenants/{tenant}/roles/{role}', Api\Tenant\RoleController::class, 'update'],
            'tenants.roles.destroy' => ['api.tenants.roles.destroy', 'DELETE', 'api/tenants/{tenant}/roles/{role}', Api\Tenant\RoleController::class, 'destroy'],
            'tenants.roles.permissions.index' => ['api.tenants.roles.permissions.index', 'GET', 'api/tenants/{tenant}/roles/{role}/permissions', Api\Tenant\Role\RolePermissionController::class, 'index'],
            'tenants.roles.permissions.store' => ['api.tenants.roles.permissions.store', 'POST', 'api/tenants/{tenant}/roles/{role}/permissions', Api\Tenant\Role\RolePermissionController::class, 'store'],
            'tenants.roles.permissions.destroy' => ['api.tenants.roles.permissions.destroy', 'DELETE', 'api/tenants/{tenant}/roles/{role}/permissions/{permission}', Api\Tenant\Role\RolePermissionController::class, 'destroy'],

            'plans.resources.available' => ['api.plans.resources.available', 'GET', 'api/plans/resources', Api\ResourceController::class, 'index'],
            'plans.index' => ['api.plans.index', 'GET', 'api/plans', Api\PlanController::class, 'index'],
            'plans.store' => ['api.plans.store', 'POST', 'api/plans', Api\PlanController::class, 'store'],
            'plans.show' => ['api.plans.show', 'GET', 'api/plans/{plan}', Api\PlanController::class, 'show'],
            'plans.update' => ['api.plans.update', 'PUT', 'api/plans/{plan}', Api\PlanController::class, 'update'],
            'plans.destroy' => ['api.plans.destroy', 'DELETE', 'api/plans/{plan}', Api\PlanController::class, 'destroy'],
            'plans.resources.index' => ['api.plans.resources.index', 'GET', 'api/plans/{plan}/resources', Api\Plan\PlanResourceController::class, 'index'],
            'plans.resources.store' => ['api.plans.resources.store', 'POST', 'api/plans/{plan}/resources', Api\Plan\PlanResourceController::class, 'store'],
            'plans.resources.destroy' => ['api.plans.resources.destroy', 'DELETE', 'api/plans/{plan}/resources/{resource}', Api\Plan\PlanResourceController::class, 'destroy'],
            'plans.users.index' => ['api.plans.users.index', 'GET', 'api/plans/{plan}/users', Api\Plan\UserController::class, 'index'],

            'roles.permissions.available' => ['api.roles.permissions.available', 'GET', 'api/roles/permissions', Api\PermissionController::class, 'index'],
            'roles.index' => ['api.roles.index', 'GET', 'api/roles', Api\RoleController::class, 'index'],
            'roles.store' => ['api.roles.store', 'POST', 'api/roles', Api\RoleController::class, 'store'],
            'roles.show' => ['api.roles.show', 'GET', 'api/roles/{role}', Api\RoleController::class, 'show'],
            'roles.update' => ['api.roles.update', 'PUT', 'api/roles/{role}', Api\RoleController::class, 'update'],
            'roles.destroy' => ['api.roles.destroy', 'DELETE', 'api/roles/{role}', Api\RoleController::class, 'destroy'],
            'roles.permissions.index' => ['api.roles.permissions.index', 'GET', 'api/roles/{role}/permissions', Api\Role\RolePermissionController::class, 'index'],
            'roles.permissions.store' => ['api.roles.permissions.store', 'POST', 'api/roles/{role}/permissions', Api\Role\RolePermissionController::class, 'store'],
            'roles.permissions.destroy' => ['api.roles.permissions.destroy', 'DELETE', 'api/roles/{role}/permissions/{permission}', Api\Role\RolePermissionController::class, 'destroy'],
            'roles.users.index' => ['api.roles.users.index', 'GET', 'api/roles/{role}/users', Api\Role\UserController::class, 'index'],
        ];
    }
}
