# User accounts, scoped memberships and administration continuity

## Separate account credentials from scoped administration

`PATCH`/`PUT /api/tenants/{tenant}/users/{user}` and
`PATCH`/`PUT /api/tenants/{tenant}/environments/{environment}/users/{user}`
reject `email` and `password` with HTTP 422, even if their values are null or the
caller is a global administrator. A rejected request does not partially update
the profile or memberships. Clients must omit these fields from scoped edit forms.
Names/company remain editable under the existing scoped user-update permission.

Global credentials may still be changed through `PATCH`/`PUT /api/users/{user}`,
authorized by the existing **global** `users.update` permission. A tenant or
environment pivot permission does not satisfy that permission, even for the caller's
own account. This change does not add a self-service credentials endpoint or change
authentication-package flows. Creating a new account still accepts initial credentials;
the scoped create endpoints do not attach/overwrite an existing account by email.

## Explicit role revocation

Apply `0001_01_01_000095_allow_roleless_scoped_memberships` before deploying.

- Tenant update: `{"role_id": null}` (or the legacy alias `{"role": null}`).
- Environment update: `{"environment_role": null}`.
- Environment update with `{"tenant_role": null}` additionally requires the target
  user's tenant-update authorization, just like changes to `tenant_plan`. Environment
  user administration alone does not permit changing tenant privileges.
- An omitted role field leaves the assignment unchanged. Non-null role assignments
  still require scope availability and delegability of every permission.
- Aliases and canonical fields cannot both be submitted, including null combinations.
  Environment updates reject tenant-route aliases (`role`, `role_id`, `plan`, `plan_id`).

Revocation sets only the relevant pivot's `role_id` to null. The membership and its
plan are preserved. A user with permissions only in an environment still belongs to
that environment's customer; customer membership alone grants no tenant permissions.
Permission/delegation checks on a roleless membership return false. Other independent
roles may still grant permissions. This is not a deny/override mechanism.

The migration keeps foreign keys and permits null role IDs. Its rollback refuses to
run while roleless memberships exist rather than assigning arbitrary roles or deleting
memberships. Existing create contracts are otherwise unchanged.

## Last global administrator

The Core's bootstrap semantics define a recoverable global administrator as a
non-soft-deleted user with a globally assigned global role granting `*` with
`inheritable=true`. Role names are irrelevant. Scoped `*` grants and deleted users
do not qualify; multiple users sharing one role do not protect against removal of
that role's wildcard. Removing delegation also counts as loss of administration.

`AdministrationGuard::run()` wraps the mutation in a database transaction and takes
an exclusive lock on the persistent `permissions.key = '*'` row. It uses locking
current reads before and after the mutation, not cached roles or snapshot-only counts.
It rejects a transition from existing global administration to none with HTTP 422
(`errors.administration`) and rolls back the mutation. It also preserves administrator
membership for every previously administered root tenant. Changes remain allowed when
another independent administrator retains the necessary access, including self-demotion.

The guard covers global user deletion, global role-permission removal/downgrade,
tenant membership removal, and tenant deletion/reparenting. Assigned-role deletion
already has a separate rejection check. No global role-assignment write endpoint is
introduced by this change.

Packages implementing global role assignment/revocation or other administration-affecting
writes must call the same guard **before** reading/checking/mutating security state,
and perform their authorization inside its callback. Do not bypass it with direct SQL.
The guard is an application contract, not a database trigger. It does not repair an
installation that already has no administrators or guarantee that credentials/MFA are
usable. Missing bootstrap `*` metadata fails closed and requires registry recovery.

## Verification

`tests/Feature/UserSecurityBoundariesTest.php` uses its own users/roles/memberships and
MariaDB transactions. It does not depend on named development seed users, reset the
database, or persist its fixtures. Tests exercise actual policies, credential rejection,
null/omitted roles, foreign scope rejection, self-demotion, shared admin roles, soft
deletion, root access, rollback, and mutex contention from a second database connection.
The second-connection test verifies locking; it is not a full parallel HTTP load test.

The accompanying `user-security.openapi.yaml` describes the changed scoped-update
contract and administration-protection error, not the entire user/role API.
