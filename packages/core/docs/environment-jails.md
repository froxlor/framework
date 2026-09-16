# Environment jails and package extensions

Node service installation and environment provisioning remain independent.
`CreateEnvironment` creates the base Jailkit jail; installed packages declare
additional jail contents through `Services/Environment/Jail`. No web/mail/FTP
service is installed by a jail provider.

## Package contract

Register a `JailProvider` in the package service provider's `boot()`:

```php
app(JailRegistry::class)->register(PhpCliJailProvider::class);
```

The imports are from `Froxlor\Core\Services\Environment\Jail`. A minimal example:

```php
final class PhpCliJailProvider implements JailProvider
{
    public function key(): string
    {
        return 'froxlor/web:php-cli';
    }

    public function plan(JailContext $context): JailPlan
    {
        // The package reads its own persisted, validated configuration using
        // $context->environmentId, $context->tenantId and $context->nodeId.
        // Return new JailPlan when CLI access is disabled for this environment.
        return (new JailPlan)->binary('/usr/bin/php8.4');
    }
}
```

The example version is illustrative: the web package selects the actual binary
from its configuration, and its separate node service provider installs it on the
host. The jail layer copies it at the same path plus its Jailkit-discovered
dependencies. It does not install arbitrary OS packages requested by providers.

For a jail-local identity, a provider can additionally declare:

```php
$plan->user('webworker', $allocatedUid, $allocatedGid,
    home: '/home/worker', shell: '/usr/sbin/nologin');
```

Packages must choose/persist suitable UID/GID mappings. UID/GID 0, the primary
environment identity, duplicate IDs and conflicting user definitions are rejected.
These are **jail-local passwd/group identities**, not panel users or host accounts.
Passwords remain locked. No SSH login, host account, home directory, recursive
chown or process restart is implied. A future SSH/FTP package must separately
define its authentication and host-account lifecycle. Changing a UID/GID does not
change file ownership; packages must coordinate existing data/processes explicitly.

Providers receive an immutable identity context, not an adapter. Their output is
declarative: absolute system binary paths and typed user definitions, not shell
snippets. Providers are nevertheless installed, trusted PHP code, not a sandbox.
Do not expose arbitrary provider registration or filesystem paths to customer input.

## Applying changes

The registry combines **all** active providers deterministically. Shared binaries
and identical user definitions are merged; conflicting declarations fail before
node writes. Returning an empty plan disables a provider's contribution.

After an authorized package configuration/resource mutation has committed:

```php
use Froxlor\Core\Jobs\Environment\SyncEnvironmentJail;

foreach ($environment->nodes as $node) {
    SyncEnvironmentJail::dispatch($environment, $node)->afterCommit();
}
```

The job recomputes the complete current plan at execution time, not dispatch time.
Package upgrades, removal and settings changes must enqueue affected environments;
there is deliberately no global settings observer or implicit periodic scan.
After uninstalling a provider, reload long-running workers before reconciliation.
No new HTTP endpoint is introduced. Calling packages must retain their existing
policy checks before dispatching; the reconciler is an internal trusted service.

Creation runs the same reconciler immediately after attaching the base jail. A
package failure leaves that attachment/jail intact; retry applies package state
without recreating or deleting customer data. A delayed sync for a removed
attachment is a no-op. Base provisioning failure cleans only artifacts carrying
that attempt's random creation token, never pre-existing host accounts.

## Ownership and safety

- The jail must be below root-owned, non-group/world-writable real directories.
  Traversal, account-file symlinks and escaping/writable directory symlinks fail.
- Creation scripts are shell-escaped and streamed as base64, not uploaded to a
  predictable executable in `/tmp`. A final marker confirms remote success even
  for adapters that do not propagate exit status reliably.
- Host binaries must be root-owned and non-writable, without setuid/setgid bits.
  Jailkit copies into a private root-owned staging tree under `/var/lib`; the
  reconciler then installs checked files. It never executes jail binaries as root.
- Existing base-jail files are borrowed: never overwritten, adopted or deleted by
  a package. Such files continue to require base-jail maintenance separately.
- Only files introduced by this reconciler are updated or removed. Dependencies
  are retained while present in the combined desired dependency tree, and removed
  after the last declared consumer disappears. Unmanaged/customer files remain.
- A root-owned `.froxlor-jail-state.json` records ownership and content hashes.
  Changed managed files or account entries cause refusal, not blind overwrite.
  Write-ahead records allow retries after partial changes; individual file writes
  are atomic, but the entire remote operation is **not** a filesystem transaction.
  Package workload/session coordination remains the calling package's responsibility.
- Removing a user removes only managed passwd/group/shadow/gshadow entries. Home
  directories and customer files are retained, and the primary identity is protected.
- Create, sync and delete share the node environment lifecycle cache lock. Sync
  and delete also use the same node-side per-jail file lock. Deletion checks host
  identity, refuses unexpected mounts and uses normal (not lazy) unmounts before
  removing files. Busy/unexpected mounts leave the attachment available for retry.

The current base jail still uses the existing Jailkit profile groups
`basicshell jk_lsh editors netutils sftp scp rsync`. Their contents are not claimed
by package providers. A chroot is **not** container/VM isolation: this work does not
add PID/network/user namespaces or an isolation guarantee against hostile native
code. In particular, the existing proc mount shares the node's PID view.

## Deployment and operations

Apply migration `0001_01_01_000097_add_environment_jail_state.php` first. Each
`node_environments` attachment records its actual `jail_path` and the last applied
package declarations (`jail_manifest`). Later `node.basedir` changes do not relocate
existing jails. Legacy attachments resolve the current base setting on their first
sync; verify it still points to their actual jail before applying the update. The
node-side identity check rejects a mismatch; there is no automatic path migration.

Provisioning requires Jailkit and Python 3.9+ on the node. New jail creation installs
`sudo jailkit python3` if needed, independently of base node service setup. Existing
jails need these prerequisites before sync/delete. Missing runtime or binary paths
fail closed rather than silently skipping work.

Create/sync jobs use the separate `environment-jails` queue connection, with
`retry_after=2100`, job timeout 1860 seconds and cache-lock lease 2040 seconds.
Configure a worker (or equivalent Horizon supervisor):

```sh
php artisan queue:work environment-jails --queue=environment-jails --timeout=1860 --tries=3
```

Use a shared cache store across workers. Root helpers have their own timeouts.
Environment deletion remains synchronous in Core so remote failure prevents
database deletion. Remote provisioning must never be wrapped in a quota database
transaction. Crash recovery may need operator intervention if base creation was
interrupted before the database attachment; unknown directories are not adopted.

Tests: `CreateEnvironmentTest`, `JailPlanTest` and `test_jail_filesystem.py`.
The PHP tests use transactional MariaDB fixtures and a recording adapter. The
Python suite runs as root **only on the isolated Docker node**, including real
Jailkit copying and an optional full create/reconcile/delete lifecycle. Test trees,
users and mounts are independently allocated and cleaned; mounted leftovers cause
cleanup to stop instead of recursively removing mounted data.
