# Node service extension contract

Core owns provider registration, setting validation, planning and execution contracts.
Service packages own their implementations. Environment provisioning, Unix accounts,
jails and customer resources are independent and are not part of this subsystem.

The initial `froxlor/core:base-system` provider supports exactly Debian 13 and Ubuntu
24.04. It ensures these packages are installed: `ca-certificates`, `logrotate`, `sudo`,
`curl`, `dnsutils`, `iproute2`, `iputils-ping`, `procps`, `lsof`, `less`, `jq`.
It does not change sudo policy, install hosting daemons, create customer directories,
upgrade installed packages, or configure customer log rotation.

## Register a provider

Implement `Froxlor\Core\Services\Node\Setup\NodeServiceProvider`. In the package's
enabled Laravel service provider, register the implementation during `boot()`:

```php
use Froxlor\Core\Services\Node\Setup\NodeServiceRegistry;

$this->app->make(NodeServiceRegistry::class)->register(RspamdProvider::class);
```

Registration is application-scoped, idempotent for an identical definition, and
does not write to the database or connect to a node. A provider key has the format
`vendor/package:provider`, for example `froxlor/antispam:rspamd`. The declared package
must own that prefix. Registry metadata includes role, revision, platforms,
dependencies, conflicts and settings schemas. `available($node->platform())` filters
the catalog by exact supported platform. Unknown platforms fail closed.

Provider methods:

| Method | Meaning |
| --- | --- |
| `key()` | Stable package-qualified implementation ID |
| `role()` | Capability, e.g. `antispam`; one provider per selected role |
| `package()` | Composer package owning the provider/settings |
| `revision()` | Change whenever provider logic or templates change |
| `platforms()` | Exact keys, e.g. `debian@13`, `ubuntu@24.04` |
| `requires()` | Required roles that must be explicitly selected |
| `conflicts()` | Incompatible roles or qualified provider IDs |
| `settings()` | Local names mapped to `SettingDefinition` objects |
| `plan($context)` | Deterministic `ServicePlan`, no infrastructure side effects |

Dependencies are topologically sorted. Missing dependencies, cycles, role mismatches,
duplicate configuration paths and duplicate service ownership reject the entire plan.
There is no silent provider replacement or platform fallback. Persist provider keys,
not PHP class names, in a future selection UI. Missing packages must remain a visible
error, never an instruction to uninstall an existing service.

## Settings and templates

```php
public function settings(): array
{
    return [
        'workers' => SettingDefinition::integer(default: 4, min: 1, max: 32),
        'enabled' => SettingDefinition::boolean(default: true),
        'mode' => SettingDefinition::choice(default: 'normal', choices: ['normal', 'strict']),
    ];
}
```

Values use the existing settings precedence: node value, node-type value, global
value, provider default. The path is `services.<provider-key>.<local-name>`, e.g.
`services.froxlor/antispam:rspamd.workers`. Registry ownership is the provider's
package, not `froxlor/core` simply because the value belongs to a Node model.

After the caller authorizes node administration, use:

```php
ServiceSettings::store($node, $provider, ['workers' => 8]);
```

The entire update is validated before its transactional write. Unknown setting names
are rejected. These settings are for ordinary configuration, not secret storage.
Package names, filesystem targets, executables and service names belong in trusted
provider code rather than user-editable settings.

`NodeServiceContext` exposes the node ID, `NodePlatform` and typed `ServiceSettings`,
but no adapter or Environment object. Use `$context->settings->integer('workers')`.
For platform views, `$context->platformTemplate('my-package', 'daemon')` resolves to
`my-package::node.services.debian-13.daemon` or
`my-package::node.services.ubuntu-24-04.daemon`. Missing views fail; no fallback is used.

```php
return ServicePlan::make()
    ->ensurePackages(['example-daemon'])
    ->managedConfig(
        path: '/etc/example/daemon.conf',
        template: $context->platformTemplate('my-package', 'daemon'),
        data: ['workers' => $context->settings->integer('workers')],
    )
    ->validateCommand(['/usr/sbin/example-daemon', '--check-config'])
    ->activateService('example-daemon', onChange: 'reload')
    ->healthCheck(['/usr/sbin/example-daemon', '--check-health']);
```

This is illustrative; the executable and switches must be implemented by the actual
service package. Use service-specific template escaping: Blade's HTML escaping is
not shell/configuration escaping. Config templates must never embed arbitrary commands.

The current operation vocabulary covers packages, root-owned configuration files,
validation commands, service enable/start/reload/restart and health checks. Config
files are restricted to canonical paths below `/etc` and modes 0600/0640/0644. Their
parent directory must already exist and be root-owned without group/world write
access. Each plan with files needs a validator; each plan with services needs a health
check. Commands are explicit argument arrays, not shell strings. Providers remain
trusted code: an argument array alone cannot make a dangerous executable safe.

Plans are immutable. `toArray()` is a redacted preview; it omits rendered contents and
command arguments. `payload()` is execution-only and must never be returned from APIs,
logged or serialized into queue jobs. Fingerprints include rendered content; do not
put low-entropy secrets in these plans.

## Plan and explicitly apply

```php
$plan = app(NodeServicePlanner::class)->plan($node, [
    'base-system' => 'froxlor/core:base-system',
]);

$preview = $plan->toArray();

// Separate, explicitly authorized action:
$result = app(NodeServiceExecutor::class)->apply($node, $plan);
```

Planning permits validated temporary overrides for previews via its third argument,
keyed by provider ID. Applying rebuilds the plan from registered code and persisted
settings and rejects changed fingerprints. Persist approved settings first and then
generate a new plan. A submitted serialized plan is never an execution API.

The default executor checks the Node `update` policy, validates node identity and
replans after refreshing the node. It writes start/success/failure audit events and
returns only run ID and fingerprint. It invokes the existing Node adapter explicitly.
Nothing runs during registration, periodic exploration, package updates or application
boot. Successful **initial** exploration queues the Core base setup as described below.
There is no UI, Environment readiness gating or automatic Apache/nginx migration.

## Initial setup, queue and repeat requests

Apply the `0001_01_01_000094_add_node_setup_state` migration before deploying the code.
Both node creation endpoints pass the initiating user ID into initial `ExploreNode`.
After OS detection and successful exploration, it calls `NodeSetupService`, which
authorizes the user, persists a pending request and dispatches `Jobs\Node\SetupNode`
after the database transaction commits. Repeated initial explorations never reschedule
an existing setup request, including a failed one. Regular scheduled explorations do
not schedule setup at all. If no valid actor or supported plan is available, the node
records a failed initial setup instead of installing anything.

`SetupNode` serializes only node/request IDs. At execution it atomically claims the
matching pending request, reloads the initiating user, checks current Node update
permissions and compares the current plan against the requested fingerprint. Deleted
users, revoked permissions and changed connection/configuration/provider revisions
fail closed. The user context is applied to Gate and Audit and restored in a `finally`
block, including synchronous execution. Raw adapter errors never enter job error text.

The Node exposes `setup_status` (`null`, `pending`, `running`, `succeeded`, `failed`),
`setup_request_id`, `setup_requested_by`, `setup_selection`, `setup_fingerprint`,
`setup_run_id`, `setup_requested_at`, `setup_started_at`, `setup_finished_at` and
`setup_error`. `null` means setup has not been requested. These fields describe the
latest request, with audit events retaining its history. For the default executor,
request ID and remote journal run ID match, including failed attempts.

The dedicated `node-setup` queue connection uses the application's Redis driver when
the default connection is `redis`; otherwise it uses the database queue. It has a
1500-second retry window. An explicit `queue.connections.node-setup` configuration
takes precedence and must retain a retry window greater than the 1260-second job
timeout. Setup makes one attempt; failures require an explicit new request.

Run a dedicated supervised worker (inside the application container):

```sh
php artisan queue:work node-setup --queue=node-setup --timeout=1260 --tries=1
```

The ordinary default-queue worker does not consume this queue. If using Horizon,
configure a separate supervisor for connection/queue `node-setup` with a timeout
greater than 1260 seconds and a Redis-backed connection. No worker is started by
application boot. Restart long-lived workers after deployment.

Authenticated API endpoints (also described in `node-setup.openapi.yaml`):

| Endpoint | Authorization | Result |
| --- | --- | --- |
| `GET /api/nodes/{node}/setup` | Node `view` | Latest status, 200 |
| `POST /api/nodes/{node}/setup` | Node `update` | Queue initial/repeat setup, 202 |
| `GET /api/tenants/{tenant}/nodes/{node}/setup` | `tenantView` | Latest status, 200 |
| `POST /api/tenants/{tenant}/nodes/{node}/setup` | `tenantUpdate` and Node `update` | Queue setup for an owned node, 202 |

POST has no request parameters: it reuses the persisted provider selection, defaulting
to `base-system => froxlor/core:base-system`. It does not accept a serialized plan,
arbitrary commands, settings or provider overrides. Pending/running requests return
409; an unsupported platform or invalid plan returns 422; unauthorized callers receive
401/403. Status responses wrap the fields in `data`, using `status`, `request_id`,
`requested_by`, `selection`, `fingerprint`, `run_id`, the three timestamps and `error`,
plus `node_id`. A successful/failed setup can be repeated via POST with a new request ID.
An inherited/shared node cannot be set up through another tenant's ownership route.

CLI alternatives:

```sh
php artisan core:explore-node node.example.test --initial --user=<user-ulid>
php artisan core:setup-node <node-ulid> --user=<user-ulid>
```

Initial CLI exploration requires an explicit user. Regular exploration retains its
existing unattended behavior. CLI setup applies the same authorization as the API;
there is no implicit root/super-admin user. New requests are transactional, duplicate
job deliveries are ignored, and a timeout failure hook cannot overwrite a newer request.
After a hard worker crash, an explicit repeat may replace an active request older than
30 minutes. This never bypasses the node-side lock or unrecovered remote journal:
inspect and recover the remote state before repeating interrupted installations.

## Execution and recovery boundaries

`AdapterNodeServiceExecutor` is a compatibility implementation using the existing
privileged adapter. It is **not** the separate, least-privilege node helper discussed
for a hardened deployment. SSH identity/host-key validation and privilege separation
remain transport/bootstrap concerns. A different backend can implement
`NodeServiceExecutor` and replace the container binding.

The adapter backend requires root, Bash, coreutils, util-linux/flock and apt/dpkg;
service operations additionally require systemd. The OS is rechecked on the node.
Execution is serialized by a node-local flock, with a 300-second acquisition timeout
and 1200-second process limit. Existing environment jobs do not acquire this lock;
node setup must be completed before provisioning environments.

Missing packages are installed without recommends, explicit upgrades or removals.
A temporary `policy-rc.d` suppresses maintainer-script service starts. An existing
administrator policy is preserved: if packages are missing, installation fails
rather than overriding it. Packages with maintainer scripts bypassing this policy
are not supported. Installation can alter dependencies and is not rolled back.

For each provider, all packages are ensured, changed files are backed up and replaced
atomically per file, the resulting configuration is validated, services are activated,
then health checks run. The running service is not intentionally reloaded before
validation. This is not an atomic transaction across multiple files/services; concurrent
manual service reloads must be avoided. Unchanged files retain their inode and active
services are not reloaded on a no-op run. Initially stopped services are started.

On failure the backend restores files and attempts to restore previously activated
services. Newly enabled services are disabled again when appropriate. Root-owned
run directories under `/var/lib/froxlor/node-setup/<run-id>` contain phase/status,
fingerprint, candidate files, backups and target/existence metadata. Raw command
output is suppressed to avoid leaking secrets. Audit logs contain no command output.
Run directories are retained for operator recovery and need an administrator-managed
retention policy, especially when configs contain credentials.

On a killed process, power loss or failed rollback, the next run refuses mutation
while any journal is `running` or `recovery-required`. An administrator must inspect
the phase, backup/target records and current node state, restore as needed, then mark
the reviewed journal `recovered`. No automatic recovery is claimed. A connection
loss after remote success is also possible; the node journal is authoritative for
that run. A repeated apply will recheck the actual state.

## Verification

Run Core tests in the documented Docker/MariaDB stack. Unit/contract tests use fake
nodes/adapters and syntax-check compiled scripts. They must never apply a setup plan
to the development host. Before production use, provider releases require integration
tests on disposable Debian/Ubuntu VMs, including package installation, service health,
rollback, interrupted execution and repeated no-op application.
