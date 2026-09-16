# Plans and resource quotas

## Contract

A resource is identified by `(type, key)`, not by its key alone. `tenant:users`
and `environment:users` are separate resources. A missing limit or `0` disables
creation; `-1` means unlimited; positive integers are finite limits. Quotas count
objects/memberships, not bytes or sampled runtime consumption.

Every booking must fit all applicable budgets:

- Tenant resources: owner's total usage **plus child reservations**, and the
  consuming user's optional personal plan (otherwise the tenant plan).
- Environment resources: aggregate usage across the owner's environments plus
  child reservations, the environment's effective plan, and the environment
  user's effective plan. An explicit tenant-user plan additionally limits that
  user's usage across all of the customer's environments.
- An environment without a plan inherits its customer's plan. An environment
  user without a personal plan inherits the effective environment plan.
- A child's plan reserves its limits in its immediate parent. Actual child
  objects are booked only to the owner, not again to ancestors. The child tenant
  itself consumes one `tenant:tenants` slot in its parent.
- The same user in two environments consumes two environment memberships.
  Environment-only users still have a tenant membership; a null tenant role
  grants no customer-wide permissions.

Core books tenant-owned nodes, environments, plans, roles, child tenants and
tenant memberships. Environment memberships are booked separately. The creator
is the consuming user; subsequent idempotent bookings preserve that attribution.
Removal releases the matching booking. An existing account is not charged
globally: membership is the quota-bearing user resource.

Plan edits and switches validate existing usage, omitted resources, child
reservations, owned plan templates and dependent explicit user/environment plans.
All affected reservations of a shared plan change together or roll back together.
An assigned plan cannot be deleted. Personal plans are caps, not reservations.
Permissions remain independent: a quota check never replaces a policy check.

## Atomic operations and packages

Register resource models using `IsResource` plus `IsTenantResource` and/or
`IsEnvironmentResource` and `ResourceRegistry::registerModel(...)` (standard
package model discovery also uses these markers). Registry registration alone
does **not** implement booking for a package model.

Create the object and book it inside the same transaction. Example for an
environment resource in an external package:

```php
Gate::authorize('create', [Mailbox::class, $environment]);

$mailbox = Resource::transaction(function () use ($environment, $actor, $data) {
    $mailbox = $environment->mailboxes()->create($data);
    Resource::addEnvironmentUsage($environment, $mailbox, $actor);
    return $mailbox;
});

ProvisionMailbox::dispatch($mailbox)->afterCommit();
```

`Resource` above is `Froxlor\Core\Support\Resource`. For tenant resources use
`addUsage($tenant, $object, $actor)`. Use `removeEnvironmentUsage` / `removeUsage`
in the matching database deletion transaction. Bookings/removals are idempotent
per owner, key and object. `hasUsageAvailable` and model availability helpers are
advisory; booking always rechecks. Background work must supply an actor explicitly.

Core model saves and synchronous observers share a transaction through
`SavesWithinQuotaTransaction`. HTTP creation flows additionally include account
and pivot creation so rejection leaves no orphan account/object. Unauthenticated
bootstrap creation of nodes/environments/metadata does not automatically book an
actor; package jobs must use the explicit facade contract. Do not use quiet saves,
bulk updates, raw ledger writes or direct plan-resource pivot writes to implement
quota-bearing application operations.

Use `PlanAssignments::updatePlanResourceLimit` / `removePlanResource` for plan
limits. Extensions performing assignment changes must validate and persist them
inside `Resource::transaction`; standalone `ensure*` calls do not reserve capacity.

The `quota_locks` row serializes quota transactions across workers. Always acquire
it before reading budgets or modifying quota-bearing rows. This deliberately
conservative global mutex favors correctness over write throughput; transactions
must be short. Never perform SSH, network provisioning or other external effects
under the quota lock. Queue infrastructure work after commit. If an operation also
needs `AdministrationGuard`, acquire the administration guard first, then quota.

If a package already owns an outer database transaction, that caller owns retries
of the **entire** operation. In particular, MariaDB can reject a locking read after
an older snapshot with error 1020. Use Laravel's bounded outer transaction retry
for replay-safe callbacks; retrying just an inner savepoint is insufficient. Do
not replay network effects or reuse mutated Eloquent instances from a failed attempt.

## API and rollout

Quota exhaustion returns HTTP 422 with `errors.resources`; invalid plan edits or
assignments return 422 with their validation field. See `quotas.openapi.yaml` for
the focused plan-resource API and shared error schema. Existing authorization
and request-field contracts remain in effect.

Apply migration `0001_01_01_000096_create_quota_lock.php` before deploying the
new code. This migration creates only the quota mutex. It does not fabricate
historical creators or rewrite old usage ledgers. Before applying finite limits
to an existing installation, reconcile historical unbooked objects/memberships
and old ancestor double-bookings against actual ownership. Do not infer missing
creators or silently discard historical data. Bootstrap/quiet/raw writes are
outside automatic accounting and must be explicitly accounted for when importing.

## Verification

Use Docker/MariaDB, not SQLite. Focused suites: `QuotaIntegrityTest`,
`QuotaConcurrencyTest`, `NodeResourceUsageTest`, `EnvironmentResourceUsageTest`
and `UserSecurityBoundariesTest`. They use owned fixtures and rollbacks;
the two-process test needs committed fixtures and removes only its exact IDs in
`finally`. It verifies one successful booking and one rejection even when both
processes start with an old snapshot. No seed reset is required.
