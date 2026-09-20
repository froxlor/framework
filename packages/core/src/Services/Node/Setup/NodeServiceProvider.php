<?php

namespace Froxlor\Core\Services\Node\Setup;

/**
 * Trusted package extension for node-wide services; never provisions environments.
 * Implementations must build deterministic plans without performing infrastructure I/O.
 */
interface NodeServiceProvider
{
    public function key(): string;

    public function role(): string;

    /** Package owning this provider and its setting definitions. */
    public function package(): string;

    /** Bump whenever provisioning behavior or templates change. */
    public function revision(): string;

    /** @return list<string> Exact platform keys; no wildcard or family fallback. */
    public function platforms(): array;

    /** @return list<string> Required service roles, selected explicitly by the caller. */
    public function requires(): array;

    /** @return list<string> Conflicting service roles or provider keys. */
    public function conflicts(): array;

    /** @return array<string, SettingDefinition> Provider-local setting names. */
    public function settings(): array;

    public function plan(NodeServiceContext $context): ServicePlan;
}
