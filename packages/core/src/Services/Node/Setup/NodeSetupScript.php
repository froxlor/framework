<?php

namespace Froxlor\Core\Services\Node\Setup;

use InvalidArgumentException;

/** Compiles trusted typed operations into a single locked, bounded adapter invocation. */
final class NodeSetupScript
{
    public function compile(NodeSetupPlan $plan, string $runId): string
    {
        if (! preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/D', $runId)) {
            throw new InvalidArgumentException('Invalid setup run identifier.');
        }
        $lines = [
            '#!/bin/bash', 'set -Eeuo pipefail', 'umask 077',
            'export PATH=/usr/sbin:/usr/bin:/sbin:/bin',
            '[ "$(id -u)" = 0 ] || exit 1',
            // Reject symlinks and writable ancestors before using a privileged path.
            <<<'SH'
assert_directory() {
    local current="$1"
    while [ "$current" != / ]; do
        [ ! -L "$current" ] && [ -d "$current" ] || return 1
        [ "$(stat -c %u "$current")" = 0 ] || return 1
        local mode
        mode=$(stat -c %a "$current")
        (( (8#$mode & 0022) == 0 )) || return 1
        current=$(dirname "$current")
    done
}
assert_directory /var/lib
if [ ! -e /var/lib/froxlor ]; then mkdir -m 0700 /var/lib/froxlor; fi
assert_directory /var/lib/froxlor
if [ ! -e /var/lib/froxlor/node-setup ]; then mkdir -m 0700 /var/lib/froxlor/node-setup; fi
assert_directory /var/lib/froxlor/node-setup
cd /var/lib/froxlor/node-setup
[ ! -L setup.lock ] && { [ ! -e setup.lock ] || [ -f setup.lock ]; } || exit 1
exec 9>setup.lock
flock -w 300 9
SH,
            'run='.escapeshellarg($runId),
            '[ ! -e "$run" ] && mkdir -m 0700 "$run" || exit 1',
            'work="$PWD/$run"',
            'exec 3>&1',
            // Output may contain secrets; retain only the phase journal, not raw tool output.
            'exec >/dev/null 2>&1',
            'printf "%s\n" running > "$work/status"',
            'printf "%s\n" '.escapeshellarg($plan->fingerprint()).' > "$work/fingerprint"',
            // Keep a non-sensitive failure code next to the phase for UI diagnostics.
            'trap \'code=$?; printf "%s\\n" "$code" > "$work/exit-code"; exit "$code"\' ERR',
            'targets=(); backups=(); existed=(); activated=(); was_active=(); was_enabled=()',
            'policy_tmp=', 'success=0',
            <<<'SH'
cleanup() {
    local code=$? failed=0 i
    trap - EXIT INT TERM HUP
    set +e
    if [ -n "$policy_tmp" ]; then
        if [ /usr/sbin/policy-rc.d -ef "$policy_tmp" ]; then
            rm -- /usr/sbin/policy-rc.d || failed=1
        elif [ -e /usr/sbin/policy-rc.d ]; then
            failed=1
        fi
        rm -f -- "$policy_tmp" || failed=1
    fi
    if [ "$code" != 0 ]; then
        set +e
        for ((i=${#targets[@]}-1; i>=0; i--)); do
            if [ "${existed[$i]}" = 1 ]; then
                cp -p -- "${backups[$i]}" "${targets[$i]}.froxlor-$run" &&
                    mv -fT -- "${targets[$i]}.froxlor-$run" "${targets[$i]}" || failed=1
            else
                rm -f -- "${targets[$i]}" || failed=1
            fi
        done
        for ((i=${#activated[@]}-1; i>=0; i--)); do
            if [ "${was_active[$i]}" = 1 ]; then
                systemctl reload-or-restart "${activated[$i]}" || failed=1
            else
                systemctl stop "${activated[$i]}" || failed=1
            fi
            if [ "${was_enabled[$i]}" = 0 ]; then
                systemctl disable "${activated[$i]}" || failed=1
            fi
        done
        if [ "$failed" = 0 ]; then
            printf '%s\n' failed > "$work/status"
        else
            printf '%s\n' recovery-required > "$work/status"
        fi
    elif [ "$failed" != 0 ] || [ "$success" != 1 ]; then
        printf '%s\n' recovery-required > "$work/status"
        code=1
    else
        printf '%s\n' succeeded > "$work/status"
        printf '%s\n' "FROXLOR_SETUP_OK:$run" >&3
    fi
    exit "$code"
}
trap cleanup EXIT
trap 'exit 1' INT TERM HUP
# Do not start another mutation after an interrupted or unrecovered run.
for previous in */status; do
    [ "$previous" = "$run/status" ] && continue
    [ -f "$previous" ] || continue
    state=$(cat "$previous")
    [ "$state" != running ] && [ "$state" != recovery-required ] || exit 1
done
SH,
            // Read os-release as data, not shell input.
            'os_id=$(sed -n \'s/^ID=//p\' /etc/os-release | tr -d \'"\')',
            'os_version=$(sed -n \'s/^VERSION_ID=//p\' /etc/os-release | tr -d \'"\')',
            '[ "$os_id@$os_version" = '.escapeshellarg($plan->platform).' ] || exit 1',
        ];

        $index = 0;
        foreach ($plan->services as $service) {
            $service['plan']->assertValid();
            $lines[] = 'changed=0';
            foreach (['packages', 'config', 'validate', 'service', 'health'] as $phase) {
                $lines[] = 'printf "%s\n" '.escapeshellarg($service['role'].':'.$phase).' > "$work/phase"';
                foreach ($service['plan']->operations() as $operation) {
                    if ($operation->type !== $phase) {
                        continue;
                    }
                    $parameters = $operation->payload();
                    array_push($lines, ...match ($phase) {
                        'packages' => $this->packages($parameters['packages']),
                        'config' => $this->config($parameters, $index++),
                        'validate', 'health' => [$this->command($parameters['argv'])],
                        'service' => $this->service($parameters),
                    });
                }
            }
        }

        $lines[] = 'success=1';

        return implode("\n", $lines)."\n";
    }

    private function packages(array $packages): array
    {
        return [
            'missing=()',
            'for package in '.implode(' ', array_map(escapeshellarg(...), $packages)).'; do',
            '  [ "$(dpkg-query -W -f=\'${Status}\' "$package" 2>/dev/null || true)" = "install ok installed" ] || missing+=("$package")',
            'done',
            'if [ "${#missing[@]}" != 0 ]; then',
            '  printf "%s\\n" "${missing[@]}" > "$work/missing-packages"',
            // Respect an administrator-managed policy; only create a temporary one when absent.
            '  if [ ! -e /usr/sbin/policy-rc.d ] && [ ! -L /usr/sbin/policy-rc.d ]; then',
            '    assert_directory /usr/sbin',
            '    printf \'#!/bin/sh\nexit 101\n\' > "$work/policy"',
            '    chmod 0755 "$work/policy"',
            '    policy_tmp=$(mktemp /usr/sbin/.froxlor-policy.XXXXXX)',
            '    install -m 0755 "$work/policy" "$policy_tmp"',
            '    ln "$policy_tmp" /usr/sbin/policy-rc.d',
            '  fi',
            '  export DEBIAN_FRONTEND=noninteractive',
            '  if ! apt-get -o DPkg::Lock::Timeout=120 update; then printf "%s\\n" 100 > "$work/exit-code"; exit 100; fi',
            '  if ! apt-get -o DPkg::Lock::Timeout=120 --no-install-recommends --no-upgrade --no-remove -y install "${missing[@]}"; then printf "%s\\n" 100 > "$work/exit-code"; exit 100; fi',
            '  for package in "${missing[@]}"; do',
            '    [ "$(dpkg-query -W -f=\'${Status}\' "$package")" = "install ok installed" ] || exit 1',
            '  done',
            '  if [ -n "$policy_tmp" ]; then',
            '    [ /usr/sbin/policy-rc.d -ef "$policy_tmp" ] || exit 1',
            '    rm -- /usr/sbin/policy-rc.d',
            '    rm -- "$policy_tmp"',
            '  fi',
            '  policy_tmp=',
            '  changed=1',
            'fi',
        ];
    }

    private function config(array $parameters, int $index): array
    {
        $path = escapeshellarg($parameters['path']);
        $mode = escapeshellarg($parameters['mode']);

        return [
            'target='.$path,
            'assert_directory "$(dirname "$target")"',
            '[ ! -L "$target" ] && { [ ! -e "$target" ] || [ -f "$target" ]; } || exit 1',
            'printf %s '.escapeshellarg(base64_encode($parameters['content'])).' | base64 -d > "$work/config-'.$index.'"',
            'if ! cmp -s "$work/config-'.$index.'" "$target" || [ "$(stat -c %u:%g:%a "$target" 2>/dev/null || true)" != "0:0:'.substr($parameters['mode'], 1).'" ]; then',
            '  if [ -e "$target" ]; then',
            '    cp -p -- "$target" "$work/backup-'.$index.'"',
            '    original_exists=1',
            '  else',
            '    original_exists=0',
            '  fi',
            '  printf "%s\\n" "$target" > "$work/target-'.$index.'"',
            '  printf "%s\\n" "$original_exists" > "$work/existed-'.$index.'"',
            '  targets+=("$target"); backups+=("$work/backup-'.$index.'"); existed+=("$original_exists")',
            '  [ ! -e "$target.froxlor-$run" ] && [ ! -L "$target.froxlor-$run" ] || exit 1',
            '  install -o root -g root -m '.$mode.' "$work/config-'.$index.'" "$target.froxlor-$run"',
            '  mv -fT -- "$target.froxlor-$run" "$target"',
            '  changed=1',
            'fi',
        ];
    }

    private function service(array $parameters): array
    {
        return [
            'service='.escapeshellarg($parameters['name']),
            'active=0; enabled=0',
            'systemctl is-active --quiet "$service" && active=1',
            'systemctl is-enabled --quiet "$service" && enabled=1',
            'if [ "$active" = 0 ] || [ "$enabled" = 0 ] || [ "$changed" = 1 ]; then',
            '  activated+=("$service"); was_active+=("$active"); was_enabled+=("$enabled")',
            '  systemctl enable "$service"',
            '  if [ "$active" = 0 ]; then systemctl start "$service";',
            '  elif [ "$changed" = 1 ]; then systemctl '.$parameters['onChange'].' "$service"; fi',
            'fi',
        ];
    }

    private function command(array $argv): string
    {
        return implode(' ', array_map(escapeshellarg(...), $argv));
    }
}
